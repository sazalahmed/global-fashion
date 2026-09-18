<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Models\FabricIssuance;
use Modules\Manufacturing\Models\FabricIssuanceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FabricIssuanceService
{
    public function __construct(
        private RmStockService $stockService,
        private ProductionOrderService $orderService,
        private ManufacturingAccountingService $accountingService
    ) {
    }

    /**
     * Create a fabric issuance for a production order.
     */
    public function create(ProductionOrder $order, array $data): FabricIssuance
    {
        return DB::transaction(function () use ($order, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['production_order_id'] = $order->id;
            $data['factory_id'] = $order->factory_id;
            $data['issued_by'] = Auth::id();
            $data['total_cost'] = 0;

            $issuance = FabricIssuance::create($data);

            $totalCost = 0;

            foreach ($items as $item) {
                $qty = (float) ($item['quantity_issued'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $lineTotal = $qty * $unitCost;
                $totalCost += $lineTotal;

                $issuance->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'bom_item_id' => $item['bom_item_id'] ?? null,
                    'quantity_issued' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);

                // Deduct from RM stock
                $this->stockService->adjustStock(
                    $item['raw_material_id'],
                    -$qty,
                    $unitCost,
                    'issuance',
                    FabricIssuance::class,
                    $issuance->id,
                    'Fabric issued for PO: ' . $order->po_number
                );

                // Update BOM actual_issued_quantity
                if (!empty($item['bom_item_id'])) {
                    $bomItem = $order->materials()->find($item['bom_item_id']);
                    if ($bomItem) {
                        $bomItem->increment('actual_issued_quantity', $qty);
                    }
                }
            }

            $issuance->update(['total_cost' => $totalCost]);

            // Update order costs
            $this->orderService->updateCosts($order);

            if ($totalCost > 0) {
                try {
                    $this->accountingService->recordFabricIssuance($issuance->fresh('items'));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record fabric issuance JE {$issuance->issuance_number}: {$e->getMessage()}");
                }
            }

            return $issuance;
        });
    }

    /**
     * Get all issuances for a production order.
     */
    public function getIssuancesForOrder(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return FabricIssuance::with(['items.rawMaterial', 'issuer'])
            ->where('production_order_id', $orderId)
            ->orderByDesc('issuance_date')
            ->get();
    }
}
