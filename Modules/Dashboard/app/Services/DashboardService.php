<?php

namespace Modules\Dashboard\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Customer\Services\CustomerService;
use Modules\Ecommerce\Services\SteadfastApiService;

class DashboardService
{
    /**
     * Date-filterable KPI cards. Pass a date range (defaults to today) and every
     * period-based metric is scoped to it. "Cash in Hand" stays a running balance
     * (point-in-time), so it is not date-filtered.
     */
    public function getKpiCards(?string $from = null, ?string $to = null): array
    {
        $start = $from ? Carbon::parse($from)->toDateString() : Carbon::today()->toDateString();
        $end = $to ? Carbon::parse($to)->toDateString() : $start;

        // ── Sales ──
        $sales = (float) DB::table('sales')
            ->whereBetween('sale_date', [$start, $end])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $deliveredSales = (float) DB::table('sales')
            ->whereBetween('sale_date', [$start, $end])
            ->where('status', 'delivered')
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $cancelledSales = (float) DB::table('sales')
            ->whereBetween('sale_date', [$start, $end])
            ->where('status', 'cancelled')
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $dues = (float) DB::table('sales')
            ->whereBetween('sale_date', [$start, $end])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNull('deleted_at')
            ->sum('due_amount');

        // ── Returns ──
        $salesReturn = (float) DB::table('sale_returns')
            ->whereBetween('return_date', [$start, $end])
            ->whereIn('status', ['approved', 'completed'])
            ->whereNull('deleted_at')
            ->sum('total_amount');

        $purchaseReturn = (float) DB::table('purchase_returns')
            ->whereBetween('return_date', [$start, $end])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereNull('deleted_at')
            ->sum('total');

        // ── Purchases ──
        $purchase = (float) DB::table('purchases')
            ->whereBetween('po_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->sum('grand_total');

        // ── Payments received ──
        $received = (float) DB::table('payments')
            ->whereBetween('payment_date', [$start, $end])
            ->where('direction', 'receive')
            ->whereNull('deleted_at')
            ->sum('amount');

        // ── Expenses ──
        $expenses = (float) DB::table('expenses')
            ->whereBetween('expense_date', [$start, $end])
            ->where('status', 'approved')
            ->sum('amount');

        // ── Cost of goods sold for those sales ──
        // Uses the product's current cost_price (sale_items does not snapshot
        // cost at sale time), so this is an approximation if costs have since
        // changed. Variant-level cost is not tracked separately here.
        $cogs = (float) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->whereIn('sales.status', ['confirmed', 'delivered'])
            ->whereNull('sales.deleted_at')
            ->sum(DB::raw('sale_items.quantity * products.cost_price'));

        // ── Profit: net sales (less returns) minus COGS and expenses (VAT kept in) ──
        $profit = ($sales - $salesReturn) - $cogs - $expenses;

        return [
            'period_start'       => $start,
            'period_end'         => $end,
            'today_sales'        => $sales,
            'sales_return'       => $salesReturn,
            'cancelled_sales'    => $cancelledSales,
            'delivered'          => $deliveredSales,
            'today_dues'         => $dues,
            'today_received'     => $received,
            'cash_in_hand'       => $this->getCashInHand($start, $end),
            'courier_receivable' => $this->getCourierReceivable(),
            'purchase'           => $purchase,
            'purchase_return'    => $purchaseReturn,
            'today_expenses'     => $expenses,
            'today_profit'       => $profit,
        ];
    }

    /**
     * Order-count cards for the dashboard. Counts sales grouped by status,
     * scoped to sale_date within the given range (defaults to today), mirroring
     * the date-defaulting used by getKpiCards(). Returns integer counts — not
     * amounts. "Processing" buckets packing + courier (orders being fulfilled);
     * "total" is every non-deleted sale in the range except incompleted/draft
     * placeholders (not orders yet), so it is >= the sum of the five named
     * buckets (there are other statuses like returned/exchange).
     */
    public function getOrderCounts(?string $from = null, ?string $to = null): array
    {
        $start = $from ? Carbon::parse($from)->toDateString() : Carbon::today()->toDateString();
        $end = $to ? Carbon::parse($to)->toDateString() : $start;

        $counts = DB::table('sales')
            ->whereBetween('sale_date', [$start, $end])
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as total')
            ->pluck('total', 'status');

        return [
            'period_start' => $start,
            'period_end'   => $end,
            'total'        => (int) $counts->except(['incompleted', 'draft'])->sum(),
            'pending'      => (int) ($counts['pending'] ?? 0),
            'processing'   => (int) (($counts['packing'] ?? 0) + ($counts['courier'] ?? 0)),
            'delivered'    => (int) ($counts['delivered'] ?? 0),
            'hold'         => (int) ($counts['on_hold'] ?? 0),
            'cancelled'    => (int) ($counts['cancelled'] ?? 0),
        ];
    }

    /**
     * Kept for callers that bust the cache directly — the canonical logic now
     * lives in CourierBalanceService (shared with the cashflow page).
     */
    public const COURIER_RECEIVABLE_CACHE_KEY = \Modules\Ecommerce\Services\CourierBalanceService::CACHE_KEY;

    /**
     * Total amount currently receivable from couriers — the COD they have
     * collected and still owe us, read via the shared cached service.
     */
    private function getCourierReceivable(): float
    {
        return app(\Modules\Ecommerce\Services\CourierBalanceService::class)->get();
    }

    /**
     * Net cash movement from journal entry lines for cash accounts, scoped to
     * the dashboard date filter (defaults to today) so the card matches the
     * other period-based KPIs.
     */
    private function getCashInHand(string $start, string $end): float
    {
        // Liquid funds on hand = the cash/cash-equivalent ledger accounts:
        // 1001 Cash in Hand, 1002 bKash, 1004 Bank — DBBL.
        // NOTE: this previously summed 1010/1011, but 1010 is Accounts Receivable
        // (and 1011 is unused). Because received payments CREDIT AR (DR Cash /
        // CR AR), reading AR made the balance go negative. These are the real
        // cash/bank asset accounts, so the figure reflects actual funds on hand.
        $cashAccountIds = DB::table('accounts')
            ->where('account_type', 'asset')
            ->where('status', 'active')
            ->whereIn('account_code', ['1001', '1002', '1004'])
            ->pluck('id');

        if ($cashAccountIds->isEmpty()) {
            return 0.0;
        }

        $row = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entry_lines.account_id', $cashAccountIds)
            ->whereBetween('journal_entries.entry_date', [$start, $end])
            ->whereNull('journal_entries.deleted_at')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit_amount), 0) AS debits, COALESCE(SUM(journal_entry_lines.credit_amount), 0) AS credits')
            ->first();

        return (float) ($row->debits ?? 0) - (float) ($row->credits ?? 0);
    }

    public function getSalesTrend(int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days - 1);

        $sales = DB::table('sales')
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(grand_total) as total'))
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNull('deleted_at')
            ->where('sale_date', '>=', $startDate)
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('d M');
            $data[] = (float) ($sales[$key]->total ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * "How customers paid us" for the given period — defaults to today, same
     * as getOrderCounts(), so the widget tracks whatever range the
     * dashboard's date picker is set to instead of being stuck on a fixed
     * period the picker has no effect on.
     */
    public function getPaymentMethodBreakdown(?string $from = null, ?string $to = null): array
    {
        $start = $from ? Carbon::parse($from)->toDateString() : Carbon::today()->toDateString();
        $end = $to ? Carbon::parse($to)->toDateString() : $start;

        // direction must be 'receive' or this pulls in outbound
        // supplier/expense payments too, wildly inflating whichever method
        // they happen to share (a single method like Cash covers both
        // collections and payouts). Filtered by payment_date (the
        // transaction date), not created_at.
        $breakdown = DB::table('payments')
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->where('direction', 'receive')
            ->whereBetween('payment_date', [$start, $end])
            ->whereNull('deleted_at')
            ->groupBy('payment_method')
            ->get();

        return [
            'labels' => $breakdown->pluck('payment_method')->map(fn($m) => ucfirst(str_replace('_', ' ', $m)))->toArray(),
            'data' => $breakdown->pluck('total')->map(fn($v) => (float) $v)->toArray(),
        ];
    }

    /**
     * Defaults to today, same as getOrderCounts() / getPaymentMethodBreakdown(),
     * so the widget tracks the dashboard's date picker instead of being stuck
     * on a fixed "this month" the picker has no effect on.
     */
    public function getTopSellingProducts(int $limit = 10, ?string $from = null, ?string $to = null): Collection
    {
        $start = $from ? Carbon::parse($from)->toDateString() : Carbon::today()->toDateString();
        $end = $to ? Carbon::parse($to)->toDateString() : $start;

        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('sales.deleted_at')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();
    }

    public function getRecentSales(int $limit = 10): Collection
    {
        return DB::table('sales')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->select(
                'sales.id',
                'sales.invoice_number',
                'sales.grand_total',
                'sales.payment_status',
                'sales.sale_date',
                \Illuminate\Support\Facades\DB::raw("COALESCE(customers.name, sales.customer_name_snapshot, 'Walk-in Customer') as customer_name")
            )
            ->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('sales.deleted_at')
            ->orderByDesc('sales.created_at')
            ->limit($limit)
            ->get();
    }

    public function getRecentExpenses(int $limit = 10): Collection
    {
        return DB::table('expenses')
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->select(
                'expenses.id',
                'expenses.expense_number',
                'expenses.amount',
                'expenses.status',
                'expenses.expense_date',
                'expense_categories.name as category_name'
            )
            ->orderByDesc('expenses.created_at')
            ->limit($limit)
            ->get();
    }

    public function getLowStockAlerts(int $limit = 10): Collection
    {
        // Reuse the Product model's canonical low-stock scope so this card matches
        // the Products list / stats exactly: it thresholds on the product's own
        // min_stock_alert (0 < total stock <= min_stock_alert), not the
        // warehouse_stock.reorder_level column, which is never populated here.
        // The min_stock_alert value is surfaced as `reorder_level` for the view.
        $sumSql = '(SELECT COALESCE(SUM(ws.quantity), 0) FROM warehouse_stock ws WHERE ws.product_id = products.id)';

        return \Modules\Product\Models\Product::lowStock()
            ->select('products.id', 'products.name', 'products.sku')
            ->selectRaw("products.min_stock_alert as reorder_level")
            ->selectRaw("{$sumSql} as quantity")
            ->orderByRaw("{$sumSql} asc")
            ->limit($limit)
            ->get();
    }

    public function getMonthlySummary(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $currentSales = DB::table('sales')
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNull('deleted_at')
            ->where('sale_date', '>=', $currentMonth)
            ->sum('grand_total');

        $lastSales = DB::table('sales')
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNull('deleted_at')
            ->where('sale_date', '>=', $lastMonth)
            ->where('sale_date', '<', $currentMonth)
            ->sum('grand_total');

        $currentExpenses = DB::table('expenses')
            ->where('status', 'approved')
            ->where('expense_date', '>=', $currentMonth)
            ->sum('amount');

        $lastExpenses = DB::table('expenses')
            ->where('status', 'approved')
            ->where('expense_date', '>=', $lastMonth)
            ->where('expense_date', '<', $currentMonth)
            ->sum('amount');

        return [
            'current_sales' => $currentSales ?? 0,
            'last_sales' => $lastSales ?? 0,
            'sales_change' => $lastSales > 0 ? round((($currentSales - $lastSales) / $lastSales) * 100, 1) : 0,
            'current_expenses' => $currentExpenses ?? 0,
            'last_expenses' => $lastExpenses ?? 0,
            'expenses_change' => $lastExpenses > 0 ? round((($currentExpenses - $lastExpenses) / $lastExpenses) * 100, 1) : 0,
        ];
    }
}
