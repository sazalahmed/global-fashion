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
        $onlyDelivered = !empty($filters['only_delivered']);

        $pl = $this->accountingReportService->getProfitAndLoss($from, $to);

        $totalSales = (float) $pl['total_revenue'];
        $totalCogs = (float) $pl['total_cogs'];
        $grossProfit = (float) $pl['gross_profit'];
        $totalExpenses = (float) $pl['total_operating_expenses'] + (float) $pl['total_other_expenses'];

        if ($onlyDelivered) {
            $deliveredData = $this->getOnlyDeliveredProfitAndLossData($from, $to);
            $totalSales = $deliveredData['total_sales'];
            $totalCogs = $deliveredData['total_cogs'];
            $grossProfit = $deliveredData['gross_profit'];
            $totalExpenses = $deliveredData['total_expenses'];
            $manualExpenses = $deliveredData['manual_expenses'];
            $salaryGiven = $deliveredData['salary_given'];
            $codCharge = $deliveredData['cod_charge'];
            $courierDelivery = $deliveredData['courier_delivery'];
        }

        $netProfit = $grossProfit - $totalExpenses;
        $netMargin = $totalSales > 0 ? round(($netProfit / $totalSales) * 100, 1) : 0;

        return [
            'total_sales'      => $totalSales,
            'total_cogs'       => $totalCogs,
            'gross_profit'     => $grossProfit,
            'total_expenses'   => $totalExpenses,
            'manual_expenses'  => $manualExpenses ?? null,
            'salary_given'     => $salaryGiven ?? null,
            'cod_charge'       => $codCharge ?? null,
            'courier_delivery' => $courierDelivery ?? null,
            'net_profit'       => $netProfit,
            'margin_percent'   => $netMargin,
            'is_loss'          => $netProfit < 0,
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

    /**
     * Custom formula for "Only Delivered" filter.
     * Note: This was done as requested by the owner Sazal Ahmed.
     */
    private function getOnlyDeliveredProfitAndLossData(string $from, string $to): array
    {
        $totalSales = (float) DB::table('sales')
            ->where('status', 'delivered')
            ->whereBetween('sale_date', [$from, $to])
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $totalCogs = (float) DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
            ->where('sales.status', 'delivered')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereNull('sales.deleted_at')
            ->sum(DB::raw('sale_items.quantity * COALESCE(product_variants.cost_price, products.cost_price, 0)'));

        $grossProfit = $totalSales - $totalCogs;

        // Breakdown of expenses as requested (Matched exactly with Cashflow)
        $cashflow = app(\Modules\Accounting\Services\SimpleMoneyService::class)->cashFlow($from, $to);
        $cashflowData = $cashflow['data'];

        $manualExpenses = $cashflowData['expense'] ?? 0;
        $salaryGiven = $cashflowData['salary'] ?? 0;
        $codCharge = $cashflowData['courier_cod_charge'] ?? 0;
        $courierDelivery = $cashflowData['courier_delivery_charge'] ?? 0;

        $totalExpenses = $manualExpenses + $salaryGiven + $codCharge + $courierDelivery;

        return [
            'total_sales'      => $totalSales,
            'total_cogs'       => $totalCogs,
            'gross_profit'     => $grossProfit,
            'total_expenses'   => $totalExpenses,
            'manual_expenses'  => $manualExpenses,
            'salary_given'     => $salaryGiven,
            'cod_charge'       => $codCharge,
            'courier_delivery' => $courierDelivery,
        ];
    }
}
