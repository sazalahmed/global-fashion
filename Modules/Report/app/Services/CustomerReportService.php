<?php

namespace Modules\Report\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Services\CustomerService;

class CustomerReportService
{
    public function topCustomers(array $filters = []): Collection
    {
        $query = DB::table('sales')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->select(
                'customers.id', 'customers.name', 'customers.phone',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(sales.grand_total) as total_spent'),
                DB::raw('SUM(sales.due_amount) as total_due')
            )
            ->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('sales.deleted_at');

        if ($filters['from_date'] ?? null) $query->where('sales.sale_date', '>=', $filters['from_date']);
        if ($filters['to_date'] ?? null) $query->where('sales.sale_date', '<=', $filters['to_date']);

        return $query->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->orderByDesc('total_spent')
            ->limit($filters['limit'] ?? 50)
            ->get();
    }

    public function customerLedger(int $customerId, array $filters = []): Collection
    {
        $query = DB::table('sales')
            ->select('id', 'invoice_number', 'sale_date as date', 'grand_total as amount', 'paid_amount', 'due_amount', DB::raw("'sale' as type"))
            ->where('customer_id', $customerId)
            ->whereNotIn('status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('deleted_at');

        if ($filters['from_date'] ?? null) $query->where('sale_date', '>=', $filters['from_date']);
        if ($filters['to_date'] ?? null) $query->where('sale_date', '<=', $filters['to_date']);

        return $query->orderByDesc('date')->get();
    }

    public function agingReport(): Collection
    {
        return DB::table('sales')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->select(
                'customers.id', 'customers.name', 'customers.phone',
                DB::raw("SUM(CASE WHEN DATEDIFF(CURDATE(), sale_date) <= 30 THEN due_amount ELSE 0 END) as current_due"),
                DB::raw("SUM(CASE WHEN DATEDIFF(CURDATE(), sale_date) BETWEEN 31 AND 60 THEN due_amount ELSE 0 END) as days_31_60"),
                DB::raw("SUM(CASE WHEN DATEDIFF(CURDATE(), sale_date) BETWEEN 61 AND 90 THEN due_amount ELSE 0 END) as days_61_90"),
                DB::raw("SUM(CASE WHEN DATEDIFF(CURDATE(), sale_date) > 90 THEN due_amount ELSE 0 END) as over_90"),
                DB::raw("SUM(due_amount) as total_due")
            )
            ->whereNotIn('sales.status', CustomerService::HIDDEN_SALE_STATUSES)
            ->whereNull('sales.deleted_at')
            ->where('sales.due_amount', '>', 0)
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->orderByDesc('total_due')
            ->get();
    }
}
