<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Models\ProductionOrderItem;
use Modules\Manufacturing\Models\ProductionOrderMaterial;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionOrderService
{
    /**
     * Get paginated list of production orders with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductionOrder::with(['factory'])
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', '%' . $search . '%')
                  ->orWhereHas('factory', function ($sq) use ($search) {
                      $sq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        if (!empty($filters['factory_id'])) {
            $query->byFactory($filters['factory_id']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['from'])) {
            $query->where('order_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('order_date', '<=', $filters['to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new production order with items and BOM materials.
     */
    public function create(array $data): ProductionOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            $materials = $data['materials'] ?? [];
            unset($data['items'], $data['materials']);

            $data['created_by'] = Auth::id();
            $data['paid_amount'] = 0;
            $data['due_amount'] = 0;

            $order = ProductionOrder::create($data);

            $this->createItems($order, $items);
            $this->createMaterials($order, $materials);
            $this->calculateEstimatedCosts($order);

            return $order;
        });
    }

    /**
     * Update a production order (only if draft).
     */
    public function update(ProductionOrder $order, array $data): ProductionOrder
    {
        if ($order->status !== ProductionOrder::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft production orders can be updated.');
        }

        return DB::transaction(function () use ($order, $data) {
            $items = $data['items'] ?? [];
            $materials = $data['materials'] ?? [];
            unset($data['items'], $data['materials']);

            $order->update($data);

            // Replace items and materials
            $order->items()->delete();
            $order->materials()->delete();

            $this->createItems($order, $items);
            $this->createMaterials($order, $materials);
            $this->calculateEstimatedCosts($order);

            return $order;
        });
    }

    /**
     * Delete a production order (only if draft).
     */
    public function delete(ProductionOrder $order): bool
    {
        if ($order->status !== ProductionOrder::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft production orders can be deleted.');
        }

        return DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->materials()->delete();
            return $order->delete();
        });
    }

    /**
     * Find a production order by ID with all relationships.
     */
    public function find(int $id): ProductionOrder
    {
        return ProductionOrder::with([
            'factory',
            'items.catalog', 'items.color', 'items.size', 'items.product', 'items.variant',
            'materials.rawMaterial',
            'lots.items', 'lots.receiver',
            'fabricIssuances.items.rawMaterial',
            'fabricReturns.items.rawMaterial',
            'damages.compensations',
            'rmWastes.rawMaterial',
            'productWastes',
            'creator', 'approver',
        ])->findOrFail($id);
    }

    /**
     * Approve a production order.
     */
    public function approve(ProductionOrder $order): ProductionOrder
    {
        if (!$order->canBeApproved()) {
            throw new \RuntimeException('This production order cannot be approved in its current status.');
        }

        $order->update([
            'status' => ProductionOrder::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $order;
    }

    /**
     * Cancel a production order.
     */
    public function cancel(ProductionOrder $order, RmStockService $stockService): ProductionOrder
    {
        if (!$order->canBeCancelled()) {
            throw new \RuntimeException('This production order cannot be cancelled in its current status.');
        }

        return DB::transaction(function () use ($order, $stockService) {
            // Reverse any fabric issuance stock deductions
            $order->load('fabricIssuances.items');
            foreach ($order->fabricIssuances as $issuance) {
                foreach ($issuance->items as $item) {
                    if ((float) $item->quantity_issued > 0) {
                        $stockService->adjustStock(
                            $item->raw_material_id,
                            (float) $item->quantity_issued,
                            (float) $item->unit_cost,
                            'return',
                            ProductionOrder::class,
                            $order->id,
                            'Fabric stock reversed due to PO cancellation: ' . $order->po_number
                        );
                    }
                }
            }

            $order->update([
                'status' => ProductionOrder::STATUS_CANCELLED,
            ]);

            return $order;
        });
    }

    /**
     * Complete a production order.
     */
    public function complete(ProductionOrder $order): ProductionOrder
    {
        if (!$order->canBeCompleted()) {
            throw new \RuntimeException('This production order cannot be completed in its current status.');
        }

        $order->update([
            'status' => ProductionOrder::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // TODO: Trigger final cost reconciliation in Phase 4

        return $order;
    }

    /**
     * Get stats for the production orders dashboard.
     */
    public function getStats(): array
    {
        $activeStatuses = [
            ProductionOrder::STATUS_APPROVED,
            ProductionOrder::STATUS_IN_PROGRESS,
            ProductionOrder::STATUS_PARTIAL_DELIVERED,
        ];

        return [
            'total_orders' => ProductionOrder::count(),
            'active_orders' => ProductionOrder::whereIn('status', $activeStatuses)->count(),
            'pending_orders' => ProductionOrder::byStatus(ProductionOrder::STATUS_DRAFT)->count(),
            'completed_orders' => ProductionOrder::byStatus(ProductionOrder::STATUS_COMPLETED)->count(),
            'total_due' => ProductionOrder::whereNotIn('status', [ProductionOrder::STATUS_CANCELLED, ProductionOrder::STATUS_DRAFT])->sum('due_amount'),
        ];
    }

    /**
     * Recalculate received/damaged/wasted/good from lots.
     */
    public function updateQuantities(ProductionOrder $order): void
    {
        $order->load('lots.items');

        $totalReceived = 0;
        $totalDamaged = 0;
        $totalGood = 0;

        foreach ($order->lots as $lot) {
            $totalReceived += (int) $lot->total_received;
            $totalDamaged += (int) $lot->total_damaged;
            $totalGood += (int) $lot->total_good;
        }

        // Wasted from product wastes
        $totalWasted = (int) $order->productWastes()->sum('quantity_wasted');

        $order->update([
            'received_quantity' => $totalReceived,
            'damaged_quantity' => $totalDamaged,
            'wasted_quantity' => $totalWasted,
            'good_quantity' => $totalGood,
        ]);

        // Also update each order item's received/damaged quantities
        foreach ($order->items as $item) {
            $lotItems = ProductionOrderItem::find($item->id)
                ->lotItems()
                ->selectRaw('SUM(quantity_received) as total_received, SUM(quantity_damaged) as total_damaged')
                ->first();

            $item->update([
                'received_quantity' => (int) ($lotItems->total_received ?? 0),
                'damaged_quantity' => (int) ($lotItems->total_damaged ?? 0),
            ]);
        }

        // Update order status based on received quantities
        $this->updateStatusFromQuantities($order);
    }

    /**
     * Recalculate all cost totals from lots, damages, wastes, fabric issuances/returns.
     */
    public function updateCosts(ProductionOrder $order): void
    {
        $order->refresh();

        $totalFabricCost = (float) $order->fabricIssuances()->sum('total_cost');
        $totalFabricReturnedCost = (float) $order->fabricReturns()->sum('total_cost');
        $totalMakingCost = (float) $order->lots()->sum('making_cost');
        $totalDeliveryCost = (float) $order->lots()->sum('delivery_charge');
        $totalOtherCost = (float) $order->lots()->sum('other_costs');
        $totalDamageCost = (float) $order->damages()->sum('total_damage_cost');
        $totalCompensation = (float) $order->damages()->sum('compensation_received');
        $totalRmWasteCost = (float) $order->rmWastes()->sum('total_cost');
        $totalRmWasteAbnormalCost = (float) $order->rmWastes()->where('is_normal', false)->sum('total_cost');
        $totalProductWasteCost = (float) $order->productWastes()->sum('total_cost');
        $totalProductWasteAbnormalCost = (float) $order->productWastes()->where('is_normal', false)->sum('total_cost');

        $grandTotal = $totalFabricCost + $totalMakingCost + $totalDeliveryCost
            + $totalOtherCost + $totalDamageCost - $totalCompensation - $totalFabricReturnedCost;

        $dueAmount = max(0, $grandTotal - (float) $order->paid_amount);

        $goodQty = max(1, (int) $order->good_quantity);
        $actualMakingCostPerUnit = $grandTotal / $goodQty;

        $order->update([
            'total_fabric_cost' => $totalFabricCost,
            'total_fabric_returned_cost' => $totalFabricReturnedCost,
            'total_making_cost' => $totalMakingCost,
            'total_delivery_cost' => $totalDeliveryCost,
            'total_other_cost' => $totalOtherCost,
            'total_damage_cost' => $totalDamageCost,
            'total_compensation' => $totalCompensation,
            'total_rm_waste_cost' => $totalRmWasteCost,
            'total_rm_waste_abnormal_cost' => $totalRmWasteAbnormalCost,
            'total_product_waste_cost' => $totalProductWasteCost,
            'total_product_waste_abnormal_cost' => $totalProductWasteAbnormalCost,
            'grand_total' => $grandTotal,
            'due_amount' => $dueAmount,
            'actual_making_cost_per_unit' => round($actualMakingCostPerUnit, 2),
        ]);

        $this->updatePaymentStatus($order);
    }

    /**
     * Update payment status based on paid vs grand total.
     */
    public function updatePaymentStatus(ProductionOrder $order): void
    {
        $paidAmount = (float) $order->paid_amount;
        $grandTotal = (float) $order->grand_total;

        if ($paidAmount <= 0) {
            $status = ProductionOrder::PAYMENT_UNPAID;
        } elseif ($paidAmount >= $grandTotal) {
            $status = ProductionOrder::PAYMENT_PAID;
        } else {
            $status = ProductionOrder::PAYMENT_PARTIAL;
        }

        $order->update([
            'payment_status' => $status,
            'due_amount' => max(0, $grandTotal - $paidAmount),
        ]);
    }

    /**
     * Create production order items from data array.
     */
    private function createItems(ProductionOrder $order, array $items): void
    {
        $totalQuantity = 0;

        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $totalQuantity += $qty;

            $order->items()->create([
                'catalog_id' => $item['catalog_id'] ?? null,
                'color_id' => $item['color_id'] ?? null,
                'size_id' => $item['size_id'] ?? null,
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $qty,
                'received_quantity' => 0,
                'damaged_quantity' => 0,
                'making_cost_per_unit' => $item['making_cost_per_unit'] ?? 0,
            ]);
        }

        $order->update(['total_quantity' => $totalQuantity]);
    }

    /**
     * Create BOM materials from data array.
     */
    private function createMaterials(ProductionOrder $order, array $materials): void
    {
        foreach ($materials as $material) {
            $order->materials()->create([
                'raw_material_id' => $material['raw_material_id'],
                'planned_quantity' => $material['planned_quantity'] ?? 0,
                'expected_return_quantity' => $material['expected_return_quantity'] ?? 0,
                'unit_cost' => $material['unit_cost'] ?? 0,
                'actual_issued_quantity' => 0,
                'actual_returned_quantity' => 0,
                'notes' => $material['notes'] ?? null,
            ]);
        }
    }

    /**
     * Calculate estimated total cost from items and materials.
     */
    private function calculateEstimatedCosts(ProductionOrder $order): void
    {
        $order->load('items', 'materials');

        $estimatedMakingCost = $order->items->sum(function ($item) {
            return (int) $item->quantity * (float) $item->making_cost_per_unit;
        });

        $estimatedMaterialCost = $order->materials->sum(function ($mat) {
            $netConsumption = (float) $mat->planned_quantity - (float) $mat->expected_return_quantity;
            return max(0, $netConsumption) * (float) $mat->unit_cost;
        });

        $estimatedTotal = $estimatedMakingCost + $estimatedMaterialCost;
        $totalQty = max(1, (int) $order->total_quantity);

        $order->update([
            'estimated_total_cost' => $estimatedTotal,
            'estimated_making_cost_per_unit' => round($estimatedTotal / $totalQty, 2),
        ]);
    }

    /**
     * Update order status based on received quantities.
     */
    private function updateStatusFromQuantities(ProductionOrder $order): void
    {
        $order->refresh();

        $totalQty = (int) $order->total_quantity;
        $receivedQty = (int) $order->received_quantity;

        if ($receivedQty <= 0) {
            return; // Keep current status
        }

        if ($receivedQty < $totalQty) {
            $newStatus = in_array($order->status, [ProductionOrder::STATUS_APPROVED])
                ? ProductionOrder::STATUS_IN_PROGRESS
                : ProductionOrder::STATUS_PARTIAL_DELIVERED;

            if ($order->status !== $newStatus && in_array($order->status, [
                ProductionOrder::STATUS_APPROVED,
                ProductionOrder::STATUS_IN_PROGRESS,
                ProductionOrder::STATUS_PARTIAL_DELIVERED,
            ])) {
                $order->update(['status' => $newStatus]);
            }
        }
    }
}
