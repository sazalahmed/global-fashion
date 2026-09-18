<?php

namespace Modules\Report\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Services\CustomerService;

class SalesReportService
{
    public function dailySales(array $filters = []): Collection
    {
        $query = DB::table('sales')
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw('SUM(grand_total) as total_sales'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(paid_amount) as total_collected'),
                DB::raw('SUM(due_amount) as total_due')
            )
            ->whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('deleted_at');

        if ($filters['from_date'] ?? null) {
            $query->where('sale_date', '>=', $filters['from_date']);
        }
        if ($filters['to_date'] ?? null) {
            $query->where('sale_date', '<=', $filters['to_date']);
        }
        if ($filters['branch_id'] ?? null) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return $query->groupBy(DB::raw('DATE(sale_date)'))
            ->orderByDesc('date')
            ->get();
    }

    public function monthlySales(array $filters = []): Collection
    {
        $query = DB::table('sales')
            ->select(
                DB::raw("DATE_FORMAT(sale_date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw('SUM(grand_total) as total_sales'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(paid_amount) as total_collected'),
                DB::raw('SUM(due_amount) as total_due')
            )
            ->whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('deleted_at');

        if ($filters['year'] ?? null) {
            $query->whereYear('sale_date', $filters['year']);
        }
        if ($filters['branch_id'] ?? null) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return $query->groupBy(DB::raw("DATE_FORMAT(sale_date, '%Y-%m')"))
            ->orderByDesc('month')
            ->get();
    }

    public function salesByProduct(array $filters = []): Collection
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT sales.id) as invoice_count')
            )
            ->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('sales.deleted_at');

        if ($filters['from_date'] ?? null) {
            $query->where('sales.sale_date', '>=', $filters['from_date']);
        }
        if ($filters['to_date'] ?? null) {
            $query->where('sales.sale_date', '<=', $filters['to_date']);
        }
        if ($filters['branch_id'] ?? null) {
            $query->where('sales.branch_id', $filters['branch_id']);
        }

        return $query->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->get();
    }

    public function salesByCustomer(array $filters = []): Collection
    {
        $query = DB::table('sales')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->select(
                'customers.id',
                DB::raw("COALESCE(customers.name, 'Walk-in Customer') as customer_name"),
                'customers.phone',
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw('SUM(sales.grand_total) as total_amount'),
                DB::raw('SUM(sales.paid_amount) as total_paid'),
                DB::raw('SUM(sales.due_amount) as total_due')
            )
            ->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('sales.deleted_at');

        if ($filters['from_date'] ?? null) {
            $query->where('sales.sale_date', '>=', $filters['from_date']);
        }
        if ($filters['to_date'] ?? null) {
            $query->where('sales.sale_date', '<=', $filters['to_date']);
        }

        return $query->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->orderByDesc('total_amount')
            ->get();
    }

    public function salesByBranch(array $filters = []): Collection
    {
        $query = DB::table('sales')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->select(
                'branches.id',
                'branches.name as branch_name',
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw('SUM(sales.grand_total) as total_sales'),
                DB::raw('SUM(sales.paid_amount) as total_collected'),
                DB::raw('SUM(sales.due_amount) as total_due')
            )
            ->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('sales.deleted_at');

        if ($filters['from_date'] ?? null) {
            $query->where('sales.sale_date', '>=', $filters['from_date']);
        }
        if ($filters['to_date'] ?? null) {
            $query->where('sales.sale_date', '<=', $filters['to_date']);
        }

        return $query->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function getSummary(array $filters = []): array
    {
        $query = DB::table('sales')
            ->whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('deleted_at');

        if ($filters['from_date'] ?? null) {
            $query->where('sale_date', '>=', $filters['from_date']);
        }
        if ($filters['to_date'] ?? null) {
            $query->where('sale_date', '<=', $filters['to_date']);
        }
        if ($filters['branch_id'] ?? null) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return [
            'total_invoices' => (clone $query)->count(),
            'total_sales' => (clone $query)->sum('grand_total') ?? 0,
            'total_discount' => (clone $query)->sum('discount_amount') ?? 0,
            'total_tax' => (clone $query)->sum('tax_amount') ?? 0,
            'total_collected' => (clone $query)->sum('paid_amount') ?? 0,
            'total_due' => (clone $query)->sum('due_amount') ?? 0,
        ];
    }
}
