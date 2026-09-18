<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\ProductWaste;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductWasteService
{
    public function __construct(
        private ProductionOrderService $orderService,
        private ManufacturingAccountingService $accountingService
    ) {
    }

    /**
     * Get paginated list of product wastes with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductWaste::with(['productionOrder.factory', 'lot', 'catalog', 'color', 'size', 'creator'])
            ->orderByDesc('waste_date')
            ->orderByDesc('id');

        if (!empty($filters['production_order_id'])) {
            $query->where('production_order_id', $filters['production_order_id']);
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
     * Create a product waste record.
     */
    public function create(array $data): ProductWaste
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = Auth::id();
            $data['total_cost'] = (int) ($data['quantity_wasted'] ?? 0) * (float) ($data['unit_cost'] ?? 0);

            $waste = ProductWaste::create($data);

            // Update production order waste costs and quantities
            if ($waste->production_order_id) {
                $order = $waste->productionOrder;
                $this->orderService->updateQuantities($order);
                $this->orderService->updateCosts($order);
            }

            // Update lot product_waste_cost if associated with a lot
            if ($waste->lot_id) {
                $lotWasteCost = ProductWaste::where('lot_id', $waste->lot_id)->sum('total_cost');
                $waste->lot()->update(['product_waste_cost' => $lotWasteCost]);
            }

            if ((float) $waste->total_cost > 0) {
                try {
                    $this->accountingService->recordProductWaste($waste->fresh());
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record product waste JE #{$waste->id}: {$e->getMessage()}");
                }
            }

            return $waste;
        });
    }
}
