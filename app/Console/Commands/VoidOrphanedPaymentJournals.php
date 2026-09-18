<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Payment\Models\Payment;

/**
 * Voids journal entries left posted behind a deleted payment.
 *
 * Payment Accounts derives balances from the payments table, where the
 * SoftDeletes scope hides a deleted row immediately. Cash Flow derives from the
 * general ledger, which keeps the entry until it is voided. A payment deleted
 * without its journal being voided therefore makes the two pages disagree by
 * that payment's amount, permanently, and the difference surfaces on Cash Flow
 * as an unexplained "Other Receipts" residual.
 *
 * Two delete paths caused this — SaleService::syncPayments() and
 * PurchaseService::cancel() — both fixed. This command repairs the entries
 * those paths already orphaned. It voids the same way the application does
 * (JournalEntryService::void posts a reversing entry rather than mutating
 * history), so the correction is auditable rather than silent.
 *
 * Idempotent: an entry that is no longer posted is not a candidate, so a second
 * run reports nothing to do.
 */
class VoidOrphanedPaymentJournals extends Command
{
    protected $signature = 'payments:void-orphaned-journals
        {--payment= : Restrict to specific payment IDs, comma-separated}
        {--dry-run : Report what would change without writing}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Void journal entries still posted against soft-deleted payments, reconciling Cash Flow with Payment Accounts.';

    public function __construct(private readonly JournalEntryService $journals)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $orphans = $this->findOrphans();

        if ($orphans->isEmpty()) {
            $this->info('Nothing to do — no deleted payment has a posted journal entry.');

            return self::SUCCESS;
        }

        $this->reportCandidates($orphans);

        if ($dryRun) {
            $this->newLine();
            $this->comment('Dry run — nothing was written. Run again without --dry-run to apply.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Void {$orphans->count()} journal entr(ies)? A reversing entry is posted for each.")) {
            $this->comment('Aborted — nothing was written.');

            return self::SUCCESS;
        }

        return $this->void($orphans);
    }

    /**
     * Soft-deleted payments whose journal entry is still posted.
     *
     * Deliberately keyed off the entry's status rather than the presence of a
     * reversal: status is what every balance query reads, and it is the single
     * field that decides whether the GL still counts this cash.
     */
    private function findOrphans()
    {
        $ids = array_filter(array_map('trim', explode(',', (string) $this->option('payment'))));

        return Payment::onlyTrashed()
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
            ->with('journalEntry')
            ->orderBy('id')
            ->get();
    }

    private function reportCandidates($orphans): void
    {
        $this->table(
            ['Payment', 'Type', 'Direction', 'Amount', 'Deleted', 'Journal', 'Status'],
            $orphans->map(fn (Payment $p) => [
                $p->payment_number,
                $p->payment_type,
                $p->direction,
                number_format((float) $p->amount, 2),
                optional($p->deleted_at)->toDateString(),
                $p->journalEntry->entry_number,
                $p->journalEntry->status,
            ])->all()
        );

        $net = $orphans->sum(fn (Payment $p) => $p->direction === 'receive' ? (float) $p->amount : -(float) $p->amount);

        $this->line(sprintf(
            'Cash Flow is overstated by %s against Payment Accounts; voiding these closes that gap.',
            number_format($net, 2)
        ));
    }

    private function void($orphans): int
    {
        $voided = 0;
        $failed = 0;

        foreach ($orphans as $payment) {
            $entry = $payment->journalEntry;

            try {
                // Per entry rather than one transaction around the loop: a
                // single bad entry should not roll back corrections that
                // already succeeded, and each void is independent.
                DB::transaction(fn () => $this->journals->void(
                    $entry,
                    "Payment {$payment->payment_number} deleted — orphaned journal corrected",
                ));

                $this->line("  <info>voided</info>  {$entry->entry_number}  ({$payment->payment_number})");
                $voided++;
            } catch (\Throwable $e) {
                $this->line("  <error>failed</error>  {$entry->entry_number}  ({$payment->payment_number}): {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("{$voided} entr(ies) voided.");

        if ($failed > 0) {
            $this->error("{$failed} entr(ies) failed — re-run to retry, the successful ones are already skipped.");

            return self::FAILURE;
        }

        $this->comment('Cash Flow and Payment Accounts should now agree. Verify on /admin/money/cashflow.');

        return self::SUCCESS;
    }
}
