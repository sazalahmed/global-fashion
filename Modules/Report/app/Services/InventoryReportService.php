<?php

namespace Modules\Report\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    public function stockSummary(array $filters = []): Collection
    {
        $query = DB::table('warehouse_stock')
            ->join('products', 'products.id', '=', 'warehouse_stock.product_id')
            ->whereNull('products.deleted_at')
            ->select(
                'products.id', 'products.name', 'products.sku',
                DB::raw('SUM(warehouse_stock.quantity) as quantity'),
                DB::raw('MAX(warehouse_stock.reorder_level) as reorder_level'),
                DB::raw('products.cost_price * SUM(warehouse_stock.quantity) as stock_value')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.cost_price');

        if ($filters['low_stock'] ?? null) {
            $query->havingRaw('SUM(warehouse_stock.quantity) <= MAX(warehouse_stock.reorder_level)')
                ->havingRaw('MAX(warehouse_stock.reorder_level) > 0');
        }

        return $query->orderBy('products.name')->get();
    }

    public function stockMovement(array $filters = []): Collection
    {
        $query = DB::table('stock_ledger')
            ->join('products', 'products.id', '=', 'stock_ledger.product_id')
            ->whereNull('products.deleted_at')
            ->select(
                'stock_ledger.*',
                'products.name as product_name', 'products.sku'
            );

        if ($filters['product_id'] ?? null) $query->where('stock_ledger.product_id', $filters['product_id']);
        if ($filters['source_type'] ?? null) $query->where('stock_ledger.source_type', $filters['source_type']);
        if ($filters['from_date'] ?? null) $query->where('stock_ledger.created_at', '>=', $filters['from_date']);
        if ($filters['to_date'] ?? null) $query->where('stock_ledger.created_at', '<=', $filters['to_date']);

        return $query->orderByDesc('stock_ledger.created_at')->limit(500)->get();
    }

    public function lowStockReport(): Collection
    {
        return DB::table('warehouse_stock')
            ->join('products', 'products.id', '=', 'warehouse_stock.product_id')
            ->whereNull('products.deleted_at')
            ->select(
                'products.name', 'products.sku',
                DB::raw('SUM(warehouse_stock.quantity) as quantity'),
                DB::raw('MAX(warehouse_stock.reorder_level) as reorder_level'),
                DB::raw('MAX(warehouse_stock.reorder_level) - SUM(warehouse_stock.quantity) as deficit')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->havingRaw('SUM(warehouse_stock.quantity) <= MAX(warehouse_stock.reorder_level)')
            ->havingRaw('MAX(warehouse_stock.reorder_level) > 0')
            ->orderBy('deficit', 'desc')
            ->get();
    }

    public function getSummary(): array
    {
        return [
            'total_products' => DB::table('products')->whereNull('deleted_at')->count(),
            'total_stock_value' => DB::table('warehouse_stock')
                ->join('products', 'products.id', '=', 'warehouse_stock.product_id')
                ->whereNull('products.deleted_at')
                ->sum(DB::raw('products.cost_price * warehouse_stock.quantity')),
            'low_stock_count' => DB::table('warehouse_stock')
                ->join('products', 'products.id', '=', 'warehouse_stock.product_id')
                ->whereNull('products.deleted_at')
                ->whereColumn('warehouse_stock.quantity', '<=', 'warehouse_stock.reorder_level')
                ->where('warehouse_stock.reorder_level', '>', 0)
                ->count(),
            'out_of_stock' => DB::table('warehouse_stock')
                ->join('products', 'products.id', '=', 'warehouse_stock.product_id')
                ->whereNull('products.deleted_at')
                ->where('warehouse_stock.quantity', '<=', 0)
                ->count(),
        ];
    }
}
