<?php

namespace Modules\Report\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Services\AccountingReportService;
use Modules\Customer\Services\CustomerService;

class FinancialReportService
{
    public function __construct(
        private readonly AccountingReportService $accountingReportService,
    ) {}

    public function incomeExpenseSummary(array $filters = []): array
    {
        $salesQuery = DB::table('sales')
            ->whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('deleted_at');
        $expenseQuery = DB::table('expenses')->where('status', 'approved');
        $purchaseQuery = DB::table('purchases')->where('status', '!=', 'cancelled');

        if ($filters['from_date'] ?? null) {
            $salesQuery->where('sale_date', '>=', $filters['from_date']);
            $expenseQuery->where('expense_date', '>=', $filters['from_date']);
            $purchaseQuery->where('po_date', '>=', $filters['from_date']);
        }
        if ($filters['to_date'] ?? null) {
            $salesQuery->where('sale_date', '<=', $filters['to_date']);
            $expenseQuery->where('expense_date', '<=', $filters['to_date']);
            $purchaseQuery->where('po_date', '<=', $filters['to_date']);
        }

        $totalSales = $salesQuery->sum('grand_total') ?? 0;
        $totalExpenses = $expenseQuery->sum('amount') ?? 0;
        $totalPurchases = $purchaseQuery->sum('grand_total') ?? 0;

        return [
            'total_income' => $totalSales,
            'total_expenses' => $totalExpenses,
            'total_purchases' => $totalPurchases,
            'gross_profit' => $totalSales - $totalPurchases,
            'net_profit' => $totalSales - $totalPurchases - $totalExpenses,
        ];
    }

    public function profitTrend(array $filters = []): Collection
    {
        $year = $filters['year'] ?? date('Y');

        $sales = DB::table('sales')
            ->select(DB::raw("MONTH(sale_date) as month"), DB::raw('SUM(grand_total) as total'))
            ->whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('deleted_at')
            ->whereYear('sale_date', $year)
            ->groupBy(DB::raw('MONTH(sale_date)'))
            ->pluck('total', 'month');

        $expenses = DB::table('expenses')
            ->select(DB::raw("MONTH(expense_date) as month"), DB::raw('SUM(amount) as total'))
            ->where('status', 'approved')
            ->whereYear('expense_date', $year)
            ->groupBy(DB::raw('MONTH(expense_date)'))
            ->pluck('total', 'month');

        $purchases = DB::table('purchases')
            ->select(DB::raw("MONTH(po_date) as month"), DB::raw('SUM(grand_total) as total'))
            ->where('status', '!=', 'cancelled')
            ->whereYear('po_date', $year)
            ->groupBy(DB::raw('MONTH(po_date)'))
            ->pluck('total', 'month');

        $result = collect();
        for ($m = 1; $m <= 12; $m++) {
            $s = (float) ($sales[$m] ?? 0);
            $e = (float) ($expenses[$m] ?? 0);
            $p = (float) ($purchases[$m] ?? 0);
            $result->push([
                'month' => date('M', mktime(0, 0, 0, $m, 1)),
                'sales' => $s,
                'expenses' => $e,
                'purchases' => $p,
                'profit' => $s - $p - $e,
            ]);
        }

        return $result;
    }

    /**
     * Delegates to the ledger-based Accounting P&L (AccountingReportService)
     * so this page and Accounting > Profit & Loss always agree on the same
     * numbers. This used to run its own, independent calculation — summing
     * sale_items.subtotal for "sales" (recognizing revenue the moment an
     * order is placed, not delivered) and the expenses table alone for
     * "expenses" (silently missing COGS, salary, bank charges, and any
     * other cost booked only as a journal entry) — which could disagree
     * with the Accounting report by hundreds of thousands of taka for the
     * same period. The return shape is kept identical to what
     * report::profit-loss expects, so the view needed no changes.
     */
    public function profitAndLoss(array $filters = []): array
    {
        $from = Carbon::parse($filters['from_date'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to   = Carbon::parse($filters['to_date'] ?? now()->endOfMonth()->toDateString())->endOfDay();

        $pl = $this->accountingReportService->getProfitAndLoss($from, $to);

        $totalSales = (float) $pl['total_revenue'];
        // Gross profit already nets out COGS, so "Total Expenses" here is
        // operating + other only — matching the view's
        // "Gross Profit − Total Expenses = Net Profit" arithmetic without
        // subtracting COGS a second time.
        $totalExpenses = (float) $pl['total_operating_expenses'] + (float) $pl['total_other_expenses'];
        $netProfit = (float) $pl['net_profit'];

        return [
            'total_sales'    => $totalSales,
            'total_cogs'     => (float) $pl['total_cogs'],
            'gross_profit'   => (float) $pl['gross_profit'],
            'total_expenses' => $totalExpenses,
            'net_profit'     => $netProfit,
            'margin_percent' => (float) $pl['net_margin'],
            'is_loss'        => $netProfit < 0,
        ];
    }

    public function accountBalances(): Collection
    {
        return DB::table('accounts')
            ->leftJoin('journal_entry_lines', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->select(
                'accounts.id',
                'accounts.account_code',
                'accounts.account_name',
                'accounts.account_type',
                DB::raw('COALESCE(SUM(journal_entry_lines.debit_amount), 0) - COALESCE(SUM(journal_entry_lines.credit_amount), 0) as current_balance')
            )
            ->where('accounts.status', 'active')
            ->groupBy('accounts.id', 'accounts.account_code', 'accounts.account_name', 'accounts.account_type')
            ->orderBy('accounts.account_code')
            ->get();
    }
}
