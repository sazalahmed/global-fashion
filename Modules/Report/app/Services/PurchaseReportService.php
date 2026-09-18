<?php

namespace Modules\Report\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseReportService
{
    public function dailyPurchases(array $filters = []): Collection
    {
        $query = DB::table('purchases')
            ->select(
                DB::raw('DATE(po_date) as date'),
                DB::raw('COUNT(*) as total_bills'),
                DB::raw('SUM(grand_total) as total_purchases'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(due_amount) as total_due')
            )
            ->where('status', '!=', 'cancelled');

        if ($filters['from_date'] ?? null) $query->where('po_date', '>=', $filters['from_date']);
        if ($filters['to_date'] ?? null) $query->where('po_date', '<=', $filters['to_date']);
        if ($filters['branch_id'] ?? null) $query->where('branch_id', $filters['branch_id']);

        return $query->groupBy(DB::raw('DATE(po_date)'))
            ->orderByDesc('date')
            ->get();
    }

    public function purchaseBySupplier(array $filters = []): Collection
    {
        $query = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->select(
                'suppliers.id', 'suppliers.name as supplier_name', 'suppliers.phone',
                DB::raw('COUNT(*) as total_bills'),
                DB::raw('SUM(purchases.grand_total) as total_amount'),
                DB::raw('SUM(purchases.paid_amount) as total_paid'),
                DB::raw('SUM(purchases.due_amount) as total_due')
            )
            ->where('purchases.status', '!=', 'cancelled');

        if ($filters['from_date'] ?? null) $query->where('purchases.po_date', '>=', $filters['from_date']);
        if ($filters['to_date'] ?? null) $query->where('purchases.po_date', '<=', $filters['to_date']);

        return $query->groupBy('suppliers.id', 'suppliers.name', 'suppliers.phone')
            ->orderByDesc('total_amount')
            ->get();
    }

    public function purchaseByProduct(array $filters = []): Collection
    {
        $query = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->join('products', 'products.id', '=', 'purchase_items.product_id')
            ->select(
                'products.id', 'products.name', 'products.sku',
                DB::raw('SUM(purchase_items.quantity) as total_quantity'),
                DB::raw('SUM(purchase_items.subtotal) as total_cost'),
                DB::raw('COUNT(DISTINCT purchases.id) as bill_count')
            )
            ->where('purchases.status', '!=', 'cancelled');

        if ($filters['from_date'] ?? null) $query->where('purchases.po_date', '>=', $filters['from_date']);
        if ($filters['to_date'] ?? null) $query->where('purchases.po_date', '<=', $filters['to_date']);

        return $query->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_cost')
            ->get();
    }

    public function getSummary(array $filters = []): array
    {
        $query = DB::table('purchases')->where('status', '!=', 'cancelled');
        if ($filters['from_date'] ?? null) $query->where('po_date', '>=', $filters['from_date']);
        if ($filters['to_date'] ?? null) $query->where('po_date', '<=', $filters['to_date']);
        if ($filters['branch_id'] ?? null) $query->where('branch_id', $filters['branch_id']);

        return [
            'total_bills' => (clone $query)->count(),
            'total_purchases' => (clone $query)->sum('grand_total') ?? 0,
            'total_paid' => (clone $query)->sum('paid_amount') ?? 0,
            'total_due' => (clone $query)->sum('due_amount') ?? 0,
        ];
    }
}
