<?php

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntryLine;

class AccountingReportService
{
    public function __construct(
        private readonly ChartOfAccountsService $accountService,
        private readonly SimpleMoneyService $moneyService,
    ) {}

    /**
     * General Ledger for a specific account.
     */
    public function getGeneralLedger(int $accountId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $account = Account::findOrFail($accountId);

        // Opening balance = account opening + posted lines before $from
        $openingBalance = $this->accountService->computeBalanceAsOf($account, $from?->copy()->subDay());

        // Transactions within date range
        $lines = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($from, $to) {
                $q->where('status', 'posted');
                if ($from) {
                    $q->where('entry_date', '>=', $from);
                }
                if ($to) {
                    $q->where('entry_date', '<=', $to);
                }
            })
            ->with('journalEntry')
            ->get()
            ->sortBy(fn ($line) => $line->journalEntry->entry_date->format('Y-m-d') . '-' . str_pad($line->journalEntry->id, 10, '0', STR_PAD_LEFT));

        // Build running balance
        $runningBalance = $openingBalance;
        $transactions = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $movement = $account->isDebitNormal()
                ? ((float) $line->debit_amount - (float) $line->credit_amount)
                : ((float) $line->credit_amount - (float) $line->debit_amount);

            $runningBalance += $movement;
            $totalDebit += (float) $line->debit_amount;
            $totalCredit += (float) $line->credit_amount;

            $transactions[] = [
                'date' => $line->journalEntry->entry_date,
                'entry_id' => $line->journalEntry->id,
                'entry_number' => $line->journalEntry->entry_number,
                'description' => $line->description ?? $line->journalEntry->description,
                'reference' => $line->journalEntry->reference,
                'debit' => (float) $line->debit_amount,
                'credit' => (float) $line->credit_amount,
                'balance' => $runningBalance,
            ];
        }

        return [
            'account' => $account,
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'closing_balance' => $runningBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    /**
     * Trial Balance as of a specific date.
     */
    public function getTrialBalance(?Carbon $asOfDate = null, bool $showZero = false): array
    {
        $accounts = Account::active()->orderBy('account_code')->get();
        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $balance = $this->accountService->computeBalanceAsOf($account, $asOfDate);

            if (!$showZero && $balance == 0) {
                continue;
            }

            $debit = 0;
            $credit = 0;

            if ($balance >= 0) {
                if ($account->isDebitNormal()) {
                    $debit = $balance;
                } else {
                    $credit = $balance;
                }
            } else {
                // Negative balance: opposite side
                if ($account->isDebitNormal()) {
                    $credit = abs($balance);
                } else {
                    $debit = abs($balance);
                }
            }

            $rows[] = [
                'account' => $account,
                'debit' => $debit,
                'credit' => $credit,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        return [
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => bccomp((string) $totalDebit, (string) $totalCredit, 2) === 0,
        ];
    }

    /**
     * Profit & Loss statement for a date range.
     */
    public function getProfitAndLoss(Carbon $from, Carbon $to): array
    {
        $revenueAccounts = Account::active()->byType('revenue')->orderBy('account_code')->get();
        $expenseAccounts = Account::active()->byType('expense')->orderBy('account_code')->get();

        $revenue = [];
        $totalRevenue = 0;

        foreach ($revenueAccounts as $account) {
            $balance = $this->computeMovementForPeriod($account, $from, $to);
            if ($balance != 0) {
                $revenue[$account->sub_type][] = [
                    'account' => $account,
                    'amount' => $balance,
                ];
                $totalRevenue += $balance;
            }
        }

        $expenses = [];
        $totalCogs = 0;
        $totalOperatingExpenses = 0;
        $totalOtherExpenses = 0;

        foreach ($expenseAccounts as $account) {
            $balance = $this->computeMovementForPeriod($account, $from, $to);
            if ($balance != 0) {
                $expenses[$account->sub_type][] = [
                    'account' => $account,
                    'amount' => $balance,
                ];

                if ($account->sub_type === 'cost_of_sales') {
                    $totalCogs += $balance;
                } elseif ($account->sub_type === 'operating_expense') {
                    $totalOperatingExpenses += $balance;
                } else {
                    $totalOtherExpenses += $balance;
                }
            }
        }

        $totalExpenses = $totalCogs + $totalOperatingExpenses + $totalOtherExpenses;
        $grossProfit = $totalRevenue - $totalCogs;
        $operatingProfit = $grossProfit - $totalOperatingExpenses;
        $netProfit = $totalRevenue - $totalExpenses;
        $netMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0;

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'total_operating_expenses' => $totalOperatingExpenses,
            'total_other_expenses' => $totalOtherExpenses,
            'operating_profit' => $operatingProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'net_margin' => $netMargin,
        ];
    }

    /**
     * Balance Sheet as of a specific date.
     */
    public function getBalanceSheet(Carbon $asOfDate): array
    {
        $result = [
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_equity' => 0,
            'is_balanced' => false,
        ];

        foreach (['asset', 'liability', 'equity'] as $type) {
            $accounts = Account::active()->byType($type)->orderBy('account_code')->get();
            $key = $type === 'asset' ? 'assets' : ($type === 'liability' ? 'liabilities' : 'equity');
            $total = 0;

            foreach ($accounts as $account) {
                $balance = $this->accountService->computeBalanceAsOf($account, $asOfDate);
                if ($balance != 0) {
                    $result[$key][$account->sub_type][] = [
                        'account' => $account,
                        'balance' => $balance,
                    ];
                    $total += $balance;
                }
            }

            $result['total_' . $key] = $total;
        }

        // Retained earnings = net income (revenue - expenses) up to this date
        $netIncome = $this->computeNetIncomeUpTo($asOfDate);
        if ($netIncome != 0) {
            $result['equity']['retained_earnings'][] = [
                'account' => (object) ['account_code' => '', 'account_name' => 'Net Income (Current Period)'],
                'balance' => $netIncome,
            ];
            $result['total_equity'] += $netIncome;
        }

        $result['is_balanced'] = bccomp(
            (string) $result['total_assets'],
            (string) ($result['total_liabilities'] + $result['total_equity']),
            2
        ) === 0;

        return $result;
    }

    /**
     * Cash Flow Statement for a date range.
     *
     * Delegates to SimpleMoneyService::cashFlow() — the two used to be
     * independent calculations that could disagree (this one never excluded
     * void_reversal entries from its posted-only sums, so a voided
     * transaction counted backwards instead of netting to zero; it also
     * mislabelled every supplier due-payment as "Customer Due Received"
     * because resolveCategory() mapped payment_type='against_invoice' to a
     * single category regardless of direction). Reshaped into the same
     * return structure this method has always produced, so the controller
     * and accounting::cash-flow view need no changes.
     */
    public function getCashFlowStatement(Carbon $from, Carbon $to): array
    {
        $result = $this->moneyService->cashFlow($from->toDateString(), $to->toDateString());

        $cashInItems = $this->buildLabeledItems($result['data'], $this->cashInLabels());
        $cashOutItems = $this->buildLabeledItems($result['data'], $this->cashOutLabels());

        return [
            'opening_cash' => (float) $result['openingBalance'],
            'cash_in_items' => $cashInItems,
            'cash_out_items' => $cashOutItems,
            'total_cash_in' => (float) $result['totalReceive'],
            'total_cash_out' => (float) $result['totalPay'],
            'net_change' => (float) $result['totalReceive'] - (float) $result['totalPay'],
            'closing_cash' => (float) $result['currentBalance'],
        ];
    }

    /**
     * Build labeled items array from SimpleMoneyService's flat category →
     * amount map, keeping only the keys named in $labels (cash-in and
     * cash-out are built from the same source map with two different label
     * sets, since each key already belongs to exactly one side).
     */
    private function buildLabeledItems(array $byType, array $labels): array
    {
        $items = [];

        foreach ($labels as $type => $meta) {
            $amount = (float) ($byType[$type] ?? 0);
            if ($amount > 0) {
                $items[] = ['label' => $meta['label'], 'icon' => $meta['icon'], 'amount' => $amount];
            }
        }

        return $items;
    }

    /**
     * Cash-in category → label/icon mapping, matching the keys
     * SimpleMoneyService::cashFlow() puts on the cash-in side.
     */
    private function cashInLabels(): array
    {
        return [
            'product_sale'         => ['label' => 'Product Sale', 'icon' => 'fa-cart-shopping'],
            'customer_due_receive' => ['label' => 'Customer Due Received', 'icon' => 'fa-hand-holding-dollar'],
            'customer_advance'     => ['label' => 'Customer Advance', 'icon' => 'fa-user-plus'],
            'supplier_adv_refund'  => ['label' => 'Supplier Advance Refund', 'icon' => 'fa-rotate-left'],
            'purchase_return'      => ['label' => 'Purchase Return', 'icon' => 'fa-rotate-left'],
            'investor_capital'     => ['label' => 'Investor Capital', 'icon' => 'fa-building-columns'],
            'loan_received'        => ['label' => 'Loan Received', 'icon' => 'fa-landmark'],
            'loan_recovered'       => ['label' => 'Personal Loan Recovered', 'icon' => 'fa-hand-holding-dollar'],
            'loan_taken'           => ['label' => 'Personal Loan Taken', 'icon' => 'fa-hand-holding-dollar'],
            'courier_withdrawal'   => ['label' => 'Courier COD Payout', 'icon' => 'fa-truck-fast'],
            'emp_adv_recovered'    => ['label' => 'Employee Advance Recovered', 'icon' => 'fa-hand-holding-dollar'],
            'salary_refund'        => ['label' => 'Salary Refund', 'icon' => 'fa-users'],
            'asset_disposal'       => ['label' => 'Asset Disposal', 'icon' => 'fa-building'],
            'other_in'             => ['label' => 'Other Receipts', 'icon' => 'fa-circle-plus'],
        ];
    }

    /**
     * Cash-out category → label/icon mapping, matching the keys
     * SimpleMoneyService::cashFlow() puts on the cash-out side.
     */
    private function cashOutLabels(): array
    {
        return [
            'sale_return'             => ['label' => 'Sale Return', 'icon' => 'fa-rotate-left'],
            'customer_adv_refund'     => ['label' => 'Customer Advance Refund', 'icon' => 'fa-rotate-left'],
            'supplier_due_pay'        => ['label' => 'Supplier Due Paid', 'icon' => 'fa-truck-field'],
            'supplier_advance'        => ['label' => 'Supplier Advance', 'icon' => 'fa-truck-field'],
            'expense'                 => ['label' => 'Expenses', 'icon' => 'fa-file-invoice-dollar'],
            'salary'                  => ['label' => 'Salaries', 'icon' => 'fa-users'],
            'investor_withdraw'       => ['label' => 'Investor Withdrawal', 'icon' => 'fa-building-columns'],
            'investor_dist'           => ['label' => 'Investor Distribution', 'icon' => 'fa-building-columns'],
            'loan_given'              => ['label' => 'Personal Loan Given', 'icon' => 'fa-hand-holding-dollar'],
            'loan_repay'              => ['label' => 'Loan Repayment', 'icon' => 'fa-landmark'],
            'loan_returned'           => ['label' => 'Personal Loan Paid Back', 'icon' => 'fa-hand-holding-dollar'],
            'courier_delivery_charge' => ['label' => 'Courier Delivery Charge', 'icon' => 'fa-truck-fast'],
            'courier_cod_charge'      => ['label' => 'Courier COD Charge', 'icon' => 'fa-truck-fast'],
            'bank_charge'             => ['label' => 'Bank Charges', 'icon' => 'fa-building-columns'],
            'emp_advance'             => ['label' => 'Employee Advance', 'icon' => 'fa-hand-holding-dollar'],
            'asset_purchase'          => ['label' => 'Asset Purchase', 'icon' => 'fa-building'],
            'other_out'               => ['label' => 'Other Payments', 'icon' => 'fa-circle-minus'],
        ];
    }

    /**
     * Compute net movement for an account during a specific period.
     * For revenue: credit - debit (net credit = income)
     * For expenses: debit - credit (net debit = cost)
     */
    private function computeMovementForPeriod(Account $account, Carbon $from, Carbon $to): float
    {
        $result = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('journalEntry', fn ($q) => $q->postedEffective()->byDateRange($from, $to))
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
            ->first();

        $totalDebit = (float) ($result->total_debit ?? 0);
        $totalCredit = (float) ($result->total_credit ?? 0);

        return $account->isDebitNormal()
            ? ($totalDebit - $totalCredit)
            : ($totalCredit - $totalDebit);
    }

    /**
     * Compute net income (revenue - expenses) up to a date.
     */
    private function computeNetIncomeUpTo(Carbon $asOfDate): float
    {
        $totalRevenue = 0;
        $totalExpenses = 0;

        foreach (Account::active()->byType('revenue')->get() as $account) {
            $totalRevenue += $this->accountService->computeBalanceAsOf($account, $asOfDate);
        }

        foreach (Account::active()->byType('expense')->get() as $account) {
            $totalExpenses += $this->accountService->computeBalanceAsOf($account, $asOfDate);
        }

        return $totalRevenue - $totalExpenses;
    }
}
