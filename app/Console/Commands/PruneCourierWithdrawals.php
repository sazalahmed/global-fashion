<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Ecommerce\Models\CourierWithdrawal;
use Modules\Ecommerce\Services\CourierWithdrawalService;
use Modules\Ecommerce\Services\SteadfastApiService;
use Modules\Setting\Models\Setting;
use Modules\Setting\Services\SettingService;

/**
 * Removes courier payouts that should never have been on the books.
 *
 * Two distinct ways they get there, so two opt-in rules rather than one
 * catch-all — each says plainly what it will take:
 *
 *   --before      Settlements predating the business itself. The sync drops
 *                 anything earlier than business_start_date, so when that
 *                 setting is missing the whole of the courier's history is
 *                 booked, including trading that belongs to nobody here.
 *
 *   --duplicates  A payout entered by hand before the sync existed, which a
 *                 settlement has since booked again. These carry no
 *                 settlement_id, so the sync's own reference check cannot see
 *                 them and counts the money twice.
 *
 * Withdrawals are removed through CourierWithdrawalService::delete(), which
 * posts a reversing journal entry rather than erasing one — the cash unwinds
 * and the history survives.
 */
class PruneCourierWithdrawals extends Command
{
    protected $signature = 'courier:prune-withdrawals
        {--before= : Remove withdrawals dated before this date (YYYY-MM-DD)}
        {--duplicates : Remove hand-entered withdrawals a synced settlement already covers}
        {--set-start-date : Also set business_start_date to the --before value, so the sync stops re-adding them}
        {--dry-run : Report what would change without writing}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Remove courier withdrawals that predate the business or duplicate a synced settlement, voiding their journals.';

    public function __construct(
        private readonly CourierWithdrawalService $withdrawals,
        private readonly SteadfastApiService $steadfast,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $before = $this->option('before');

        if ($before) {
            try {
                $before = Carbon::parse($before)->toDateString();
            } catch (\Throwable) {
                $this->error("--before='{$this->option('before')}' is not a date I can read.");

                return self::FAILURE;
            }
        }

        if (! $before && ! $this->option('duplicates')) {
            $this->error('Nothing selected. Pass --before=YYYY-MM-DD, --duplicates, or both.');

            return self::FAILURE;
        }

        $targets = $this->collect($before);

        if ($targets->isEmpty()) {
            $this->info('Nothing to do — no withdrawal matches.');
            $this->maybeSetStartDate($before, (bool) $this->option('dry-run'));

            return self::SUCCESS;
        }

        $this->report($targets);

        if ($this->option('dry-run')) {
            $this->maybeSetStartDate($before, true);
            $this->newLine();
            $this->comment('Dry run — nothing was written. Run again without --dry-run to apply.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Remove {$targets->count()} withdrawal(s)? Each journal is voided with a reversing entry.")) {
            $this->comment('Aborted — nothing was written.');

            return self::SUCCESS;
        }

        return $this->prune($targets, $before);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{withdrawal: CourierWithdrawal, reason: string}>
     */
    private function collect(?string $before)
    {
        $targets = collect();

        if ($before) {
            CourierWithdrawal::whereDate('withdrawal_date', '<', $before)
                ->get()
                ->each(fn (CourierWithdrawal $w) => $targets->push([
                    'withdrawal' => $w,
                    'reason'     => "before {$before}",
                ]));
        }

        if ($this->option('duplicates')) {
            // Matched on date, not amount: the hand-entered figures are
            // usually the wrong ones, which is why the sync replaced manual
            // entry in the first place.
            foreach (CourierWithdrawal::whereNull('settlement_id')->get() as $manual) {
                if ($targets->contains(fn ($t) => $t['withdrawal']->is($manual))) {
                    continue;
                }

                $date = substr((string) $manual->withdrawal_date, 0, 10);

                $covered = CourierWithdrawal::whereNotNull('settlement_id')
                    ->whereDate('withdrawal_date', $date)
                    ->first();

                // A manual payout no settlement covers is real money the sync
                // never captured. Leaving it alone is the only safe choice.
                if ($covered) {
                    $targets->push([
                        'withdrawal' => $manual,
                        'reason'     => "duplicate of {$covered->withdrawal_number} ({$covered->settlement_id})",
                    ]);
                }
            }
        }

        return $targets;
    }

    private function report($targets): void
    {
        $this->table(
            ['Withdrawal', 'Date', 'Amount', 'Delivery', 'COD', 'Settlement', 'Journal', 'Why'],
            $targets->map(fn (array $t) => [
                $t['withdrawal']->withdrawal_number,
                substr((string) $t['withdrawal']->withdrawal_date, 0, 10),
                number_format((float) $t['withdrawal']->amount, 2),
                number_format((float) $t['withdrawal']->delivery_charge, 2),
                number_format((float) $t['withdrawal']->cod_charge, 2),
                $t['withdrawal']->settlement_id ?: '—',
                optional($t['withdrawal']->journalEntry)->status ?? 'none',
                $t['reason'],
            ])->all()
        );

        $cash  = $targets->sum(fn (array $t) => (float) $t['withdrawal']->amount);
        $gross = $targets->sum(fn (array $t) => (float) $t['withdrawal']->amount
            + (float) $t['withdrawal']->delivery_charge
            + (float) $t['withdrawal']->cod_charge);

        $this->line(sprintf(
            'Cash will fall by %s; the Cash Flow courier row by %s (the difference is the courier fees).',
            number_format($cash, 2),
            number_format($gross, 2),
        ));

        $unmatched = CourierWithdrawal::whereNull('settlement_id')->count()
            - $targets->filter(fn (array $t) => blank($t['withdrawal']->settlement_id))->count();

        if ($unmatched > 0) {
            $this->warn("{$unmatched} hand-entered withdrawal(s) left alone — no synced settlement covers them, so they may be real payouts the sync never captured.");
        }
    }

    private function prune($targets, ?string $before): int
    {
        $done = 0;
        $failed = 0;

        foreach ($targets as $t) {
            $w = $t['withdrawal'];
            $je = optional($w->journalEntry)->entry_number;

            try {
                $this->withdrawals->delete($w);
                $this->line("  <info>removed</info>  {$w->withdrawal_number}  journal={$je}");
                $done++;
            } catch (\Throwable $e) {
                $this->line("  <error>failed</error>   {$w->withdrawal_number}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("{$done} withdrawal(s) removed.");

        $this->maybeSetStartDate($before, false);

        // The settlement list is cached and filtered on the start date, so a
        // stale copy would re-book what was just removed on the next page view.
        $this->steadfast->forgetPaymentsCache();
        $this->line('Settlement cache cleared.');

        if ($failed > 0) {
            $this->error("{$failed} withdrawal(s) failed — re-run to retry, the successful ones are already gone.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function maybeSetStartDate(?string $before, bool $dryRun): void
    {
        if (! $this->option('set-start-date')) {
            return;
        }

        if (! $before) {
            $this->warn('--set-start-date ignored: it takes its value from --before, which was not given.');

            return;
        }

        if ((string) Setting::get('business', 'business_start_date') === $before) {
            $this->line("Business start date: already {$before}.");

            return;
        }

        if ($dryRun) {
            $this->line("Business start date: would set to {$before}.");

            return;
        }

        app(SettingService::class)->updateBusinessProfile(['business_start_date' => $before]);
        Setting::flushCache();
        $this->line("Business start date: set to {$before}.");
    }
}
