<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\RmWaste;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RmWasteService
{
    public function __construct(
        private ProductionOrderService $orderService,
        private ManufacturingAccountingService $accountingService
    ) {
    }

    /**
     * Get paginated list of RM wastes with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RmWaste::with(['productionOrder.factory', 'lot', 'rawMaterial', 'creator'])
            ->orderByDesc('waste_date')
            ->orderByDesc('id');

        if (!empty($filters['production_order_id'])) {
            $query->where('production_order_id', $filters['production_order_id']);
        }

        if (!empty($filters['raw_material_id'])) {
            $query->where('raw_material_id', $filters['raw_material_id']);
        }

        if (!empty($filters['waste_type'])) {
            $query->where('waste_type', $filters['waste_type']);
        }

        if (isset($filters['is_normal']) && $filters['is_normal'] !== '') {
            $query->where('is_normal', (bool) $filters['is_normal']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create an RM waste record.
     */
    public function create(array $data): RmWaste
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = Auth::id();
            $data['total_cost'] = (float) ($data['quantity_wasted'] ?? 0) * (float) ($data['unit_cost'] ?? 0);

            $waste = RmWaste::create($data);

            // Update production order waste costs
            if ($waste->production_order_id) {
                $this->orderService->updateCosts($waste->productionOrder);
            }

            // Update lot rm_waste_cost if associated with a lot
            if ($waste->lot_id) {
                $lotWasteCost = RmWaste::where('lot_id', $waste->lot_id)->sum('total_cost');
                $waste->lot()->update(['rm_waste_cost' => $lotWasteCost]);
            }

            if ((float) $waste->total_cost > 0) {
                try {
                    $this->accountingService->recordRmWaste($waste->fresh());
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record RM waste JE #{$waste->id}: {$e->getMessage()}");
                }
            }

            return $waste;
        });
    }
}
