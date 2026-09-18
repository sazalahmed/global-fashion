<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Models\FabricReturn;
use Modules\Manufacturing\Models\FabricReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FabricReturnService
{
    public function __construct(
        private RmStockService $stockService,
        private ProductionOrderService $orderService,
        private ManufacturingAccountingService $accountingService
    ) {
    }

    /**
     * Create a fabric return for a production order.
     */
    public function create(ProductionOrder $order, array $data): FabricReturn
    {
        return DB::transaction(function () use ($order, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['production_order_id'] = $order->id;
            $data['factory_id'] = $order->factory_id;
            $data['received_by'] = Auth::id();
            $data['total_cost'] = 0;

            $return = FabricReturn::create($data);

            $totalCost = 0;

            foreach ($items as $item) {
                $qty = (float) ($item['quantity_returned'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $lineTotal = $qty * $unitCost;
                $totalCost += $lineTotal;
                $condition = $item['condition'] ?? FabricReturnItem::CONDITION_GOOD;

                $return->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'bom_item_id' => $item['bom_item_id'] ?? null,
                    'quantity_returned' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                    'condition' => $condition,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Add good items back to RM stock
                if ($condition === FabricReturnItem::CONDITION_GOOD) {
                    $this->stockService->adjustStock(
                        $item['raw_material_id'],
                        $qty,
                        $unitCost,
                        'return',
                        FabricReturn::class,
                        $return->id,
                        'Fabric returned from PO: ' . $order->po_number
                    );
                }

                // Update BOM actual_returned_quantity
                if (!empty($item['bom_item_id'])) {
                    $bomItem = $order->materials()->find($item['bom_item_id']);
                    if ($bomItem) {
                        $bomItem->increment('actual_returned_quantity', $qty);
                    }
                }
            }

            $return->update(['total_cost' => $totalCost]);

            // Update order costs
            $this->orderService->updateCosts($order);

            if ($totalCost > 0) {
                try {
                    $this->accountingService->recordFabricReturn($return->fresh('items'));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record fabric return JE {$return->return_number}: {$e->getMessage()}");
                }
            }

            return $return;
        });
    }

    /**
     * Get all fabric returns for a production order.
     */
    public function getReturnsForOrder(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return FabricReturn::with(['items.rawMaterial', 'receiver'])
            ->where('production_order_id', $orderId)
            ->orderByDesc('return_date')
            ->get();
    }
}
