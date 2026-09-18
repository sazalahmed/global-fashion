<?php

namespace Modules\Sale\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Services\AccountingIntegrationService;
use Modules\Sale\Models\Sale;
use Modules\Sale\Services\SaleService;

/**
 * Post revenue for sales that reached a revenue-recognition status while the
 * ledger was not being written. Sales only posted when their status was
 * literally 'confirmed', so everything delivered without passing through that
 * state left revenue, receivables, VAT and COGS unrecorded.
 *
 * Idempotent: a sale that already has a posted journal is skipped, so the
 * command is safe to re-run.
 */
class BackfillSaleJournalsCommand extends Command
{
    protected $signature = 'sales:backfill-journals
                            {--dry-run : Report what would be posted without writing anything}
                            {--repost-stale : Also refresh posted entries that disagree with their sale}';

    protected $description = 'Post missing general-ledger entries for delivered sales';

    public function handle(SaleService $sales, AccountingIntegrationService $accounting): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $repostStale = (bool) $this->option('repost-stale');

        // Recognition depends on the sale's channel, so filter in PHP against
        // the same rule the service uses rather than duplicating it in SQL.
        $candidates = Sale::query()
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (Sale $sale) => in_array(
                $sale->status,
                SaleService::revenueStatusesFor($sale),
                true
            ));

        $pending = $candidates->reject(function (Sale $sale) use ($accounting, $repostStale) {
            if (! $accounting->hasPostedJournal('sale', $sale->id)) {
                return false;
            }

            return ! ($repostStale && $this->journalIsStale($sale));
        });

        if ($pending->isEmpty()) {
            $this->info('Nothing to backfill — every delivered sale already has a posted journal entry.');

            return self::SUCCESS;
        }

        $this->line(sprintf(
            '%s %d sale(s) totalling %s.',
            $dryRun ? 'Would post' : 'Posting',
            $pending->count(),
            number_format((float) $pending->sum('grand_total'), 2)
        ));

        $rows = [];
        $posted = 0;
        $failed = 0;

        foreach ($pending as $sale) {
            if ($dryRun) {
                $rows[] = [$sale->invoice_number, $sale->sale_date, number_format((float) $sale->grand_total, 2), 'would post'];

                continue;
            }

            try {
                DB::transaction(function () use ($accounting, $sale) {
                    // A stale entry has to be reversed before the replacement
                    // goes in, or the sale would be counted twice.
                    if ($accounting->hasPostedJournal('sale', $sale->id)) {
                        $accounting->voidJournalEntry('sale', $sale->id);
                    }
                    $accounting->recordSale($sale);
                });
                $posted++;
                $rows[] = [$sale->invoice_number, $sale->sale_date, number_format((float) $sale->grand_total, 2), 'posted'];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [$sale->invoice_number, $sale->sale_date, number_format((float) $sale->grand_total, 2), 'FAILED: ' . $e->getMessage()];
            }
        }

        $this->table(['Invoice', 'Date', 'Total', 'Result'], $rows);

        if ($dryRun) {
            $this->warn('Dry run — no journal entries were written.');

            return self::SUCCESS;
        }

        $this->info("Posted {$posted} sale(s).");

        if ($failed > 0) {
            $this->error("{$failed} sale(s) failed — see the table above.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * A posted entry is stale when it no longer describes its sale: either the
     * receivable it booked has drifted from the sale's total (the sale was
     * edited while nothing refreshed the ledger), or it carries no cost of
     * goods for a sale that shipped stock.
     */
    private function journalIsStale(Sale $sale): bool
    {
        $entry = DB::table('journal_entries')
            ->where('source_type', 'sale')->where('source_id', $sale->id)
            ->where('status', 'posted')->whereNull('deleted_at')
            ->value('id');

        if (! $entry) {
            return false;
        }

        $bookedReceivable = (float) DB::table('journal_entry_lines as jel')
            ->join('accounts as a', 'a.id', '=', 'jel.account_id')
            ->where('jel.journal_entry_id', $entry)->where('a.account_code', '1010')
            ->selectRaw('COALESCE(SUM(jel.debit_amount), 0) AS dr')->value('dr');

        if (abs($bookedReceivable - (float) $sale->grand_total) > 0.01) {
            return true;
        }

        $hasCogsLine = DB::table('journal_entry_lines as jel')
            ->join('accounts as a', 'a.id', '=', 'jel.account_id')
            ->where('jel.journal_entry_id', $entry)->where('a.account_code', '5001')
            ->exists();

        $shipsStock = DB::table('sale_items as si')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->where('si.sale_id', $sale->id)->where('p.product_type', '!=', 'service')
            ->exists();

        return $shipsStock && ! $hasCogsLine;
    }
}
