<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    /**
     * Compare warehouse_stock quantities against stock_ledger totals.
     * Returns discrepancies where the two don't match.
     */
    public function reconcile(?int $productId = null): Collection
    {
        return DB::table('warehouse_stock as ws')
            ->join('products as p', 'ws.product_id', '=', 'p.id')
            ->leftJoin(DB::raw("(
                SELECT product_id, variant_id,
                       COALESCE(SUM(quantity_change), 0) as ledger_qty
                FROM stock_ledger
                GROUP BY product_id, variant_id
            ) as sl"), function ($join) {
                $join->on('ws.product_id', '=', 'sl.product_id')
                     ->on(DB::raw('COALESCE(ws.variant_id, 0)'), '=', DB::raw('COALESCE(sl.variant_id, 0)'));
            })
            ->select([
                'ws.id',
                'ws.product_id',
                'ws.variant_id',
                'p.name as product_name',
                'p.sku as product_sku',
                'ws.quantity as stored_qty',
                DB::raw('COALESCE(sl.ledger_qty, 0) as ledger_qty'),
                DB::raw('ws.quantity - COALESCE(sl.ledger_qty, 0) as discrepancy'),
            ])
            ->when($productId, fn ($q) => $q->where('ws.product_id', $productId))
            ->whereRaw('ws.quantity != COALESCE(sl.ledger_qty, 0)')
            ->orderBy('p.name')
            ->get();
    }

    /**
     * Get reconciliation statistics.
     */
    public function getStats(): array
    {
        $discrepancies = $this->reconcile();

        return [
            'total_products_checked' => DB::table('warehouse_stock')->count(),
            'discrepancies_found'    => $discrepancies->count(),
            'over_count'             => $discrepancies->where('discrepancy', '>', 0)->count(),
            'under_count'            => $discrepancies->where('discrepancy', '<', 0)->count(),
        ];
    }
}
