<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\ProductionDamage;
use Modules\Manufacturing\Models\DamageCompensation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionDamageService
{
    public function __construct(
        private ProductionOrderService $orderService,
        private ManufacturingAccountingService $accountingService
    ) {
    }

    /**
     * Get paginated list of damages with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductionDamage::with([
            'productionOrder.factory', 'lot', 'catalog', 'color', 'size', 'compensations',
        ])->orderByDesc('damage_date')->orderByDesc('id');

        if (!empty($filters['production_order_id'])) {
            $query->where('production_order_id', $filters['production_order_id']);
        }

        if (!empty($filters['factory_id'])) {
            $query->whereHas('productionOrder', function ($q) use ($filters) {
                $q->where('factory_id', $filters['factory_id']);
            });
        }

        if (!empty($filters['damage_type'])) {
            $query->where('damage_type', $filters['damage_type']);
        }

        if (!empty($filters['responsibility'])) {
            $query->where('responsibility', $filters['responsibility']);
        }

        if (!empty($filters['compensation_status'])) {
            $query->where('compensation_status', $filters['compensation_status']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a damage record.
     */
    public function create(array $data): ProductionDamage
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = Auth::id();
            $data['total_damage_cost'] = (int) ($data['quantity'] ?? 0) * (float) ($data['estimated_cost_per_unit'] ?? 0);
            $data['compensation_received'] = 0;
            $data['compensation_status'] = $data['compensation_status'] ?? ProductionDamage::COMP_PENDING;

            $damage = ProductionDamage::create($data);

            // Update production order damage cost
            if ($damage->production_order_id) {
                $order = $damage->productionOrder;
                $this->orderService->updateCosts($order);
            }

            if ((float) $damage->total_damage_cost > 0) {
                try {
                    $this->accountingService->recordDamage($damage->fresh());
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record production damage JE #{$damage->id}: {$e->getMessage()}");
                }
            }

            return $damage;
        });
    }

    /**
     * Record a compensation for a damage.
     */
    public function recordCompensation(ProductionDamage $damage, array $data): DamageCompensation
    {
        return DB::transaction(function () use ($damage, $data) {
            $data['damage_id'] = $damage->id;
            $data['created_by'] = Auth::id();

            $compensation = DamageCompensation::create($data);

            // Update damage compensation_received total
            $totalReceived = $damage->compensations()->sum('amount');
            $totalDamageCost = (float) $damage->total_damage_cost;

            if ($totalReceived >= $totalDamageCost) {
                $compStatus = ProductionDamage::COMP_RECEIVED;
            } elseif ($totalReceived > 0) {
                $compStatus = ProductionDamage::COMP_PARTIAL;
            } else {
                $compStatus = ProductionDamage::COMP_PENDING;
            }

            $damage->update([
                'compensation_received' => $totalReceived,
                'compensation_status' => $compStatus,
            ]);

            // Update production order costs
            if ($damage->production_order_id) {
                $this->orderService->updateCosts($damage->productionOrder);
            }

            if ((float) $compensation->amount > 0) {
                try {
                    $this->accountingService->recordCompensation($compensation->fresh());
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record damage compensation JE #{$compensation->id}: {$e->getMessage()}");
                }
            }

            return $compensation;
        });
    }
}
