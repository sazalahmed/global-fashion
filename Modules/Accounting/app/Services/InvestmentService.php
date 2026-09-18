<?php

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Investor;
use Modules\Accounting\Models\InvestorCapital;
use Modules\Accounting\Models\InvestorDistribution;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Payment\Models\PaymentAccount;

class InvestmentService
{
    public function __construct(
        private readonly JournalEntryService $journal,
    ) {}

    // ─────────────────────────────────────────────────────────────────
    // Dashboard
    // ─────────────────────────────────────────────────────────────────

    public function dashboard(): array
    {
        $shareholderCount = Investor::active()->shareholders()->count();
        $investorCount    = Investor::active()->investors()->count();

        $totalShareholderCapital = (float) DB::table('investor_capital as ic')
            ->join('investors as i', 'i.id', '=', 'ic.investor_id')
            ->where('i.type', 'shareholder')
            ->selectRaw("SUM(CASE WHEN ic.type = 'inject' THEN ic.amount ELSE -ic.amount END) as total")
            ->value('total');

        $totalInvestorCapital = (float) DB::table('investor_capital as ic')
            ->join('investors as i', 'i.id', '=', 'ic.investor_id')
            ->where('i.type', 'investor')
            ->selectRaw("SUM(CASE WHEN ic.type = 'inject' THEN ic.amount ELSE -ic.amount END) as total")
            ->value('total');

        $totalDistributed = (float) InvestorDistribution::sum('distribution_amount');

        $recentInvestors = Investor::active()
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $recentDistributions = InvestorDistribution::with('investor')
            ->latest('distribution_date')
            ->take(5)
            ->get();

        return compact(
            'shareholderCount', 'investorCount',
            'totalShareholderCapital', 'totalInvestorCapital', 'totalDistributed',
            'recentInvestors', 'recentDistributions'
        );
    }

    /**
     * Toggle an investor's active/inactive status.
     */
    public function toggleInvestorStatus(Investor $investor): Investor
    {
        $investor->update(['is_active' => ! $investor->is_active]);

        return $investor->fresh();
    }

    // ─────────────────────────────────────────────────────────────────
    // Capital injection / withdrawal
    // ─────────────────────────────────────────────────────────────────

    /**
     * Record an injection or withdrawal of capital for an investor and post
     * the matching journal entry.
     *
     *   Inject  → DR Cash/Bank   / CR Owner's Capital  (shareholder)
     *           → DR Cash/Bank   / CR Investor Capital (pure investor)
     *   Withdraw → reverse of above.
     */
    public function recordCapital(array $data): InvestorCapital
    {
        return DB::transaction(function () use ($data) {
            $investor = Investor::findOrFail($data['investor_id']);
            $paymentAccount = PaymentAccount::findOrFail($data['payment_account_id']);

            $cashAccountId = $this->resolveCashAccountId($paymentAccount);
            $equityAccountId = $this->resolveEquityAccountId($investor);

            $amount = (float) $data['amount'];
            $isInject = $data['type'] === 'inject';

            $description = sprintf(
                '%s — %s capital %s',
                $investor->name,
                $investor->type === 'shareholder' ? 'Shareholder' : 'Investor',
                $isInject ? 'injection' : 'withdrawal',
            );

            // Inject: cash up (debit), equity up (credit). Withdraw flips both.
            $lines = $isInject
                ? [
                    ['account_id' => $cashAccountId,   'debit_amount' => $amount, 'credit_amount' => 0, 'description' => $description],
                    ['account_id' => $equityAccountId, 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => $description],
                ]
                : [
                    ['account_id' => $equityAccountId, 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => $description],
                    ['account_id' => $cashAccountId,   'debit_amount' => 0, 'credit_amount' => $amount, 'description' => $description],
                ];

            $capital = InvestorCapital::create([
                'investor_id'        => $investor->id,
                'type'               => $data['type'],
                'transaction_date'   => $data['transaction_date'],
                'amount'             => $amount,
                'payment_account_id' => $paymentAccount->id,
                'reference'          => $data['reference'] ?? null,
                'note'               => $data['note'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            $je = $this->journal->createFromSource(
                'investor_capital',
                $capital->id,
                $lines,
                $description,
                $data['reference'] ?? null,
                $data['transaction_date'],
            );

            $capital->update(['journal_entry_id' => $je->id]);

            return $capital->fresh();
        });
    }

    public function deleteCapital(InvestorCapital $capital): void
    {
        DB::transaction(function () use ($capital) {
            if ($capital->journal_entry_id && $capital->journalEntry && $capital->journalEntry->status === 'posted') {
                $this->journal->void($capital->journalEntry, "Reversed: investor capital entry #{$capital->id}");
            }
            $capital->delete();
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Profit distribution
    // ─────────────────────────────────────────────────────────────────

    /**
     * Compute net profit for a period from posted journal entries.
     * Profit = revenue credits − expense debits (within the window).
     */
    public function netProfitForPeriod(string $from, string $to): float
    {
        $revenue = (float) JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->where('accounts.account_type', 'revenue')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->selectRaw('SUM(credit_amount) - SUM(debit_amount) as net')
            ->value('net');

        $expense = (float) JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->where('accounts.account_type', 'expense')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->selectRaw('SUM(debit_amount) - SUM(credit_amount) as net')
            ->value('net');

        return $revenue - $expense;
    }

    /**
     * Build a preview of what each active investor would receive for the
     * given period. Does not write anything.
     *
     * Rules:
     *   shareholder → dividend = net_profit × (their_shares / total_shares)
     *   pure investor → share = net_profit × profit_share_pct
     *
     * Pure-investor shares come "off the top" first — they are contractual.
     * Shareholders then split what's left of the net profit.
     */
    public function previewDistribution(string $from, string $to): array
    {
        $netProfit = $this->netProfitForPeriod($from, $to);

        $investors = Investor::active()->investors()->get();
        $shareholders = Investor::active()->shareholders()->get();

        $rows = [];
        $totalInvestorShare = 0.0;

        foreach ($investors as $inv) {
            $pct = (float) $inv->profit_share_pct;
            $amount = max(0, $netProfit) * ($pct / 100);
            $totalInvestorShare += $amount;
            $rows[] = [
                'investor'      => $inv,
                'type'          => 'investor',
                'share_pct'     => $pct,
                'amount'        => round($amount, 2),
            ];
        }

        $remainingForShareholders = max(0, $netProfit - $totalInvestorShare);
        $totalShares = (float) $shareholders->sum('shares_owned');

        foreach ($shareholders as $sh) {
            $pct = $totalShares > 0 ? ((float) $sh->shares_owned / $totalShares) * 100 : 0;
            $amount = $remainingForShareholders * ($pct / 100);
            $rows[] = [
                'investor'      => $sh,
                'type'          => 'shareholder',
                'share_pct'     => $pct,
                'amount'        => round($amount, 2),
            ];
        }

        return [
            'net_profit'                  => round($netProfit, 2),
            'investor_pool'               => round($totalInvestorShare, 2),
            'shareholder_pool'            => round($remainingForShareholders, 2),
            'rows'                        => $rows,
        ];
    }

    /**
     * Persist the distribution. One DB row + one journal entry per investor.
     * Shareholder dividend  → DR Owner's Drawing / CR Cash
     * Pure investor share   → DR Profit Share Expense / CR Cash
     */
    public function postDistribution(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $paymentAccount = PaymentAccount::findOrFail($data['payment_account_id']);
            $cashAccountId  = $this->resolveCashAccountId($paymentAccount);
            $batchRef       = 'DIST-' . now()->format('Ymd-His');

            $created = [];

            foreach ($data['rows'] as $row) {
                $amount = (float) $row['amount'];
                if ($amount <= 0) continue;

                $investor = Investor::findOrFail($row['investor_id']);

                $payoutAccountId = $investor->type === 'shareholder'
                    ? Account::where('account_code', '3020')->value('id')   // Owner's Drawing
                    : Account::where('account_code', '5195')->value('id');  // Investor Profit Share

                $description = sprintf(
                    'Profit distribution to %s (%s) for %s..%s',
                    $investor->name,
                    $investor->type,
                    $data['period_start'],
                    $data['period_end'],
                );

                $distribution = InvestorDistribution::create([
                    'investor_id'         => $investor->id,
                    'period_start'        => $data['period_start'],
                    'period_end'          => $data['period_end'],
                    'net_profit_snapshot' => $data['net_profit_snapshot'],
                    'share_pct_used'      => $row['share_pct'],
                    'distribution_amount' => $amount,
                    'distribution_date'   => $data['distribution_date'],
                    'payment_account_id'  => $paymentAccount->id,
                    'batch_ref'           => $batchRef,
                    'note'                => $data['note'] ?? null,
                    'created_by'          => auth()->id(),
                ]);

                $je = $this->journal->createFromSource(
                    'investor_distribution',
                    $distribution->id,
                    [
                        ['account_id' => $payoutAccountId, 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => $description],
                        ['account_id' => $cashAccountId,   'debit_amount' => 0, 'credit_amount' => $amount, 'description' => $description],
                    ],
                    $description,
                    $batchRef,
                    $data['distribution_date'],
                );

                $distribution->update(['journal_entry_id' => $je->id]);
                $created[] = $distribution;
            }

            return $created;
        });
    }

    public function deleteDistribution(InvestorDistribution $distribution): void
    {
        DB::transaction(function () use ($distribution) {
            if ($distribution->journal_entry_id && $distribution->journalEntry && $distribution->journalEntry->status === 'posted') {
                $this->journal->void($distribution->journalEntry, "Reversed: distribution #{$distribution->id}");
            }
            $distribution->delete();
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Map a PaymentAccount (the cash/bank/mobile wallet the money flows
     * through) to the matching GL asset account. Mirrors the lookup used
     * elsewhere in ExpenseService::resolveAssetAccount.
     */
    private function resolveCashAccountId(PaymentAccount $account): int
    {
        $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];
        $code = $typeToCode[$account->account_type] ?? '1001';

        return (int) (
            Account::where('account_code', $code)->value('id')
            ?? Account::where('account_type', 'asset')->value('id')
        );
    }

    /**
     * Shareholder capital posts to Owner's Capital (3001), a single equity
     * bucket — the cap-table sits in the investors table separately.
     * Pure investor capital posts to a Liability account (2030) — money the
     * business owes back to outside investors, not their equity.
     */
    private function resolveEquityAccountId(Investor $investor): int
    {
        $code = $investor->type === 'shareholder' ? '3001' : '2030';

        return (int) Account::where('account_code', $code)->value('id');
    }
}
