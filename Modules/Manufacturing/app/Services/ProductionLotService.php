<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Models\ProductionLot;
use Modules\Manufacturing\Models\ProductionLotItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionLotService
{
    public function __construct(
        private ProductionOrderService $orderService,
        private ManufacturingAccountingService $accountingService
    ) {
    }

    /**
     * Create a production lot (receive goods from factory).
     */
    public function create(ProductionOrder $order, array $data): ProductionLot
    {
        if (!$order->canReceiveLot()) {
            throw new \RuntimeException('This production order cannot receive lots in its current status.');
        }

        return DB::transaction(function () use ($order, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['production_order_id'] = $order->id;
            $data['received_by'] = Auth::id();
            $data['stock_added'] = false;

            $lot = ProductionLot::create($data);

            $totalReceived = 0;
            $totalDamaged = 0;
            $totalGood = 0;

            foreach ($items as $item) {
                $received = (int) ($item['quantity_received'] ?? 0);
                $damaged = (int) ($item['quantity_damaged'] ?? 0);
                $good = $received - $damaged;

                if ($received <= 0) {
                    continue;
                }

                $totalReceived += $received;
                $totalDamaged += $damaged;
                $totalGood += $good;

                // Find the matching production order item
                $orderItem = $order->items()->find($item['production_order_item_id']);

                $lot->items()->create([
                    'production_order_item_id' => $item['production_order_item_id'],
                    'catalog_id' => $orderItem->catalog_id ?? null,
                    'color_id' => $orderItem->color_id ?? null,
                    'size_id' => $orderItem->size_id ?? null,
                    'product_id' => $orderItem->product_id ?? null,
                    'variant_id' => $orderItem->variant_id ?? null,
                    'quantity_received' => $received,
                    'quantity_damaged' => $damaged,
                    'quantity_good' => $good,
                    'unit_cost' => 0, // Will be calculated below
                ]);
            }

            // Calculate lot totals
            $lot->update([
                'total_received' => $totalReceived,
                'total_damaged' => $totalDamaged,
                'total_good' => $totalGood,
            ]);

            // Calculate provisional cost per unit
            $this->calculateProvisionalCost($lot);

            // Update production order quantities and costs
            $this->orderService->updateQuantities($order);
            $this->orderService->updateCosts($order);

            $totalLotCost = (float) $lot->making_cost + (float) $lot->delivery_charge + (float) $lot->other_costs;
            if ($totalLotCost > 0) {
                try {
                    $this->accountingService->recordLotReceived($lot->fresh());
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record production lot JE {$lot->lot_number}: {$e->getMessage()}");
                }
            }

            return $lot;
        });
    }

    /**
     * Get all lots for a production order.
     */
    public function getLotsForOrder(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return ProductionLot::with(['items.catalog', 'items.color', 'items.size', 'receiver'])
            ->where('production_order_id', $orderId)
            ->orderByDesc('delivery_date')
            ->get();
    }

    /**
     * Find a lot by ID with relationships.
     */
    public function find(int $id): ProductionLot
    {
        return ProductionLot::with([
            'productionOrder.factory',
            'items.catalog', 'items.color', 'items.size', 'items.product', 'items.variant',
            'damages', 'rmWastes.rawMaterial', 'productWastes',
            'receiver',
        ])->findOrFail($id);
    }

    /**
     * Calculate provisional cost per unit for a lot.
     */
    private function calculateProvisionalCost(ProductionLot $lot): void
    {
        $makingCost = (float) $lot->making_cost;
        $deliveryCost = (float) $lot->delivery_charge;
        $otherCosts = (float) $lot->other_costs;

        $totalLotCost = $makingCost + $deliveryCost + $otherCosts;
        $goodQty = max(1, (int) $lot->total_good);

        $provisionalCost = round($totalLotCost / $goodQty, 2);

        $lot->update([
            'provisional_cost_per_unit' => $provisionalCost,
        ]);

        // Update unit cost on each lot item
        foreach ($lot->items as $item) {
            $item->update(['unit_cost' => $provisionalCost]);
        }
    }
}
