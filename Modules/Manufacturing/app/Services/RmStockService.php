<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\RmStock;
use Modules\Manufacturing\Models\RmStockLedger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RmStockService
{
    /**
     * Adjust stock for a raw material.
     * Positive quantityChange = stock in, negative = stock out.
     */
    public function adjustStock(
        int $rawMaterialId,
        float $quantityChange,
        float $unitCost,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): RmStock {
        return DB::transaction(function () use ($rawMaterialId, $quantityChange, $unitCost, $type, $referenceType, $referenceId, $notes) {
            // Lock the stock record for update
            $stock = RmStock::where('raw_material_id', $rawMaterialId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = RmStock::create([
                    'raw_material_id' => $rawMaterialId,
                    'quantity' => 0,
                    'avg_cost' => 0,
                    'last_cost' => 0,
                ]);
            }

            $oldQuantity = (float) $stock->quantity;
            $oldAvgCost = (float) $stock->avg_cost;
            $newQuantity = $oldQuantity + $quantityChange;

            // Calculate weighted average cost (only for stock-in with positive cost)
            if ($quantityChange > 0 && $unitCost > 0) {
                $totalOldValue = $oldQuantity * $oldAvgCost;
                $totalNewValue = $quantityChange * $unitCost;
                $newAvgCost = $newQuantity > 0
                    ? ($totalOldValue + $totalNewValue) / $newQuantity
                    : 0;

                $stock->avg_cost = round($newAvgCost, 2);
                $stock->last_cost = $unitCost;
            }

            $stock->quantity = max(0, $newQuantity);
            $stock->save();

            // Create ledger entry
            $quantityIn = $quantityChange > 0 ? $quantityChange : 0;
            $quantityOut = $quantityChange < 0 ? abs($quantityChange) : 0;

            RmStockLedger::create([
                'raw_material_id' => $rawMaterialId,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity_in' => $quantityIn,
                'quantity_out' => $quantityOut,
                'balance' => $stock->quantity,
                'unit_cost' => $unitCost,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            return $stock;
        });
    }

    /**
     * Get current stock for a raw material.
     */
    public function getStock(int $rawMaterialId): mixed
    {
        return RmStock::with(['rawMaterial'])
            ->where('raw_material_id', $rawMaterialId)
            ->first();
    }

    /**
     * Get stock report with optional filters.
     */
    public function getStockReport(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RmStock::with(['rawMaterial'])
            ->where('quantity', '>', 0);

        if (!empty($filters['raw_material_id'])) {
            $query->byMaterial($filters['raw_material_id']);
        }

        if (!empty($filters['low_stock'])) {
            $query->lowStock();
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('rawMaterial', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get ledger entries for a raw material.
     */
    public function getLedger(int $rawMaterialId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RmStockLedger::with(['createdBy'])
            ->where('raw_material_id', $rawMaterialId)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to'] . ' 23:59:59');
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
