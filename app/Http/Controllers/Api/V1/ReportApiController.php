<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Services\CustomerService;
use Modules\Sale\Models\Sale;
use Modules\Purchase\Models\Purchase;

class ReportApiController extends BaseApiController
{
    public function sales(Request $request): JsonResponse
    {
        $from = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('date_to', now()->format('Y-m-d'));
        $groupBy = $request->input('group_by', 'day');

        $format = match ($groupBy) { 'week' => '%x-W%v', 'month' => '%Y-%m', default => '%Y-%m-%d' };

        $rows = Sale::whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereBetween('sale_date', [$from, $to])
            ->select(DB::raw("DATE_FORMAT(sale_date, '{$format}') as period"), DB::raw('COUNT(*) as orders'), DB::raw('SUM(grand_total) as gross_sales'), DB::raw('SUM(discount_amount) as discounts'), DB::raw('SUM(tax_amount) as tax'))
            ->groupBy('period')->orderBy('period')->get();

        return $this->success(['rows' => $rows, 'date_from' => $from, 'date_to' => $to]);
    }

    public function salesByProduct(Request $request): JsonResponse
    {
        $from = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('date_to', now()->format('Y-m-d'));

        $rows = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('sales.deleted_at')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->select('sale_items.product_name', 'sale_items.product_sku', DB::raw('SUM(sale_items.quantity) as quantity_sold'), DB::raw('SUM(sale_items.subtotal) as total_revenue'))
            ->groupBy('sale_items.product_name', 'sale_items.product_sku')
            ->orderByDesc('total_revenue')->limit(50)->get();

        return $this->success(['rows' => $rows, 'date_from' => $from, 'date_to' => $to]);
    }

    public function salesByCustomer(Request $request): JsonResponse
    {
        $from = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('date_to', now()->format('Y-m-d'));

        $rows = Sale::whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereBetween('sale_date', [$from, $to])
            ->whereNotNull('customer_id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select('customers.name', 'customers.phone', DB::raw('COUNT(*) as orders'), DB::raw('SUM(sales.grand_total) as total_sales'), DB::raw('SUM(sales.due_amount) as total_due'))
            ->groupBy('customers.name', 'customers.phone')
            ->orderByDesc('total_sales')->limit(50)->get();

        return $this->success(['rows' => $rows, 'date_from' => $from, 'date_to' => $to]);
    }

    public function purchases(Request $request): JsonResponse
    {
        $from = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('date_to', now()->format('Y-m-d'));

        $rows = Purchase::where('status', '!=', 'cancelled')
            ->whereBetween('po_date', [$from, $to])
            ->select(DB::raw("DATE_FORMAT(po_date, '%Y-%m-%d') as period"), DB::raw('COUNT(*) as orders'), DB::raw('SUM(grand_total) as total'))
            ->groupBy('period')->orderBy('period')->get();

        return $this->success(['rows' => $rows, 'date_from' => $from, 'date_to' => $to]);
    }

    public function inventoryValuation(): JsonResponse
    {
        $rows = DB::table('warehouse_stock')
            ->join('products', 'warehouse_stock.product_id', '=', 'products.id')
            ->where('warehouse_stock.quantity', '>', 0)
            ->select('products.name as product_name', 'products.sku', 'warehouse_stock.quantity', 'products.cost_price', DB::raw('warehouse_stock.quantity * products.cost_price as stock_value'))
            ->orderByDesc('stock_value')->limit(100)->get();

        $total = $rows->sum('stock_value');
        return $this->success(['rows' => $rows, 'total_value' => $total]);
    }

    public function profitAndLoss(Request $request): JsonResponse
    {
        $from = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('date_to', now()->format('Y-m-d'));

        $revenue = (float) Sale::whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)->whereBetween('sale_date', [$from, $to])->sum('grand_total');
        $cogs = (float) DB::table('sale_items')->join('sales', 'sale_items.sale_id', '=', 'sales.id')->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)->whereNull('sales.deleted_at')->whereBetween('sales.sale_date', [$from, $to])->sum(DB::raw('sale_items.quantity * sale_items.unit_price * 0.6'));
        $expenses = (float) DB::table('expenses')->where('status', '!=', 'rejected')->whereBetween('expense_date', [$from, $to])->sum('total_amount');

        return $this->success([
            'revenue' => $revenue, 'cost_of_goods' => $cogs, 'gross_profit' => $revenue - $cogs,
            'expenses' => $expenses, 'net_profit' => $revenue - $cogs - $expenses,
            'date_from' => $from, 'date_to' => $to,
        ]);
    }

    public function expenseReport(Request $request): JsonResponse
    {
        $from = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('date_to', now()->format('Y-m-d'));

        $rows = DB::table('expenses')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->where('expenses.status', '!=', 'rejected')
            ->whereBetween('expenses.expense_date', [$from, $to])
            ->select('expense_categories.name as category', DB::raw('COUNT(*) as count'), DB::raw('SUM(expenses.total_amount) as total'))
            ->groupBy('expense_categories.name')->orderByDesc('total')->get();

        return $this->success(['rows' => $rows, 'total' => $rows->sum('total'), 'date_from' => $from, 'date_to' => $to]);
    }

    public function receivablesAging(): JsonResponse
    {
        $rows = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('sales.deleted_at')
            ->where('sales.due_amount', '>', 0)
            ->select('customers.name', 'customers.phone',
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), sales.sale_date) <= 30 THEN sales.due_amount ELSE 0 END) as current_due"),
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), sales.sale_date) BETWEEN 31 AND 60 THEN sales.due_amount ELSE 0 END) as days_31_60"),
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), sales.sale_date) BETWEEN 61 AND 90 THEN sales.due_amount ELSE 0 END) as days_61_90"),
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), sales.sale_date) > 90 THEN sales.due_amount ELSE 0 END) as days_over_90"),
                DB::raw("SUM(sales.due_amount) as total"))
            ->groupBy('customers.name', 'customers.phone')
            ->orderByDesc('total')->get();

        return $this->success(['rows' => $rows]);
    }

    public function payablesAging(): JsonResponse
    {
        $rows = DB::table('purchases')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->where('purchases.status', '!=', 'cancelled')
            ->where('purchases.due_amount', '>', 0)
            ->select('suppliers.company_name as name', 'suppliers.phone',
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), purchases.po_date) <= 30 THEN purchases.due_amount ELSE 0 END) as current_due"),
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), purchases.po_date) BETWEEN 31 AND 60 THEN purchases.due_amount ELSE 0 END) as days_31_60"),
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), purchases.po_date) BETWEEN 61 AND 90 THEN purchases.due_amount ELSE 0 END) as days_61_90"),
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), purchases.po_date) > 90 THEN purchases.due_amount ELSE 0 END) as days_over_90"),
                DB::raw("SUM(purchases.due_amount) as total"))
            ->groupBy('suppliers.company_name', 'suppliers.phone')
            ->orderByDesc('total')->get();

        return $this->success(['rows' => $rows]);
    }

    public function taxReport(Request $request): JsonResponse
    {
        $from = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('date_to', now()->format('Y-m-d'));

        $taxCollected = (float) Sale::whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)->whereBetween('sale_date', [$from, $to])->sum('tax_amount');
        $taxPaid = (float) Purchase::where('status', '!=', 'cancelled')->whereBetween('po_date', [$from, $to])->sum('tax_amount');

        return $this->success(['tax_collected' => $taxCollected, 'tax_paid' => $taxPaid, 'net_tax' => $taxCollected - $taxPaid, 'date_from' => $from, 'date_to' => $to]);
    }
}
