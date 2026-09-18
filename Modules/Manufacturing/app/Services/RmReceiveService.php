<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\RmPurchaseOrder;
use Modules\Manufacturing\Models\RmPurchaseItem;
use Modules\Manufacturing\Models\RmReceive;
use Modules\Manufacturing\Models\RmReceiveItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RmReceiveService
{
    public function __construct(
        private readonly RmStockService $stockService,
    ) {}

    /**
     * Create a goods receive note (GRN) for an RM purchase order.
     *
     * $data should contain:
     *   - receive_date (date)
     *   - notes (string|null)
     *   - items (array) — each with: purchase_item_id, raw_material_id, quantity_received, quantity_damaged?, damage_notes?
     */
    public function create(RmPurchaseOrder $po, array $data): RmReceive
    {
        if (!$po->canBeReceived()) {
            throw new \RuntimeException('This purchase order is not in a receivable status.');
        }

        return DB::transaction(function () use ($po, $data) {
            $receiveItems = $data['items'] ?? [];

            $receive = RmReceive::create([
                'purchase_order_id' => $po->id,
                'receive_date' => $data['receive_date'] ?? now()->toDateString(),
                'received_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($receiveItems as $itemData) {
                $purchaseItem = RmPurchaseItem::findOrFail($itemData['purchase_item_id']);
                $qtyReceived = (float) ($itemData['quantity_received'] ?? 0);
                $qtyDamaged = (float) ($itemData['quantity_damaged'] ?? 0);

                if ($qtyReceived <= 0) {
                    continue;
                }

                // Validate against remaining quantity
                $remaining = $purchaseItem->remaining_quantity;
                if ($qtyReceived > $remaining) {
                    throw new \RuntimeException(
                        "Cannot receive {$qtyReceived} — only {$remaining} remaining for item #{$purchaseItem->id}."
                    );
                }

                // Create receive item
                $receiveItem = RmReceiveItem::create([
                    'receive_id' => $receive->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'raw_material_id' => $itemData['raw_material_id'] ?? $purchaseItem->raw_material_id,
                    'quantity_received' => $qtyReceived,
                    'quantity_damaged' => $qtyDamaged,
                    'damage_notes' => $itemData['damage_notes'] ?? null,
                ]);

                // Update purchase item received_quantity
                $purchaseItem->increment('received_quantity', $qtyReceived);

                // Add good quantity to stock (received minus damaged)
                $goodQty = $qtyReceived - $qtyDamaged;
                if ($goodQty > 0) {
                    $this->stockService->adjustStock(
                        $receiveItem->raw_material_id,
                        $goodQty,
                        (float) $purchaseItem->unit_price,
                        'purchase_receive',
                        RmReceive::class,
                        $receive->id,
                        'Received via ' . $po->po_number
                    );
                }

                // Log damaged quantity separately if any
                if ($qtyDamaged > 0) {
                    $this->stockService->adjustStock(
                        $receiveItem->raw_material_id,
                        0,
                        (float) $purchaseItem->unit_price,
                        'damage',
                        RmReceive::class,
                        $receive->id,
                        'Damaged goods from ' . $po->po_number . ': ' . ($itemData['damage_notes'] ?? '')
                    );
                }
            }

            // Update PO status based on whether all items are fully received
            $this->updatePoReceiveStatus($po);

            return $receive->load('items.rawMaterial');
        });
    }

    /**
     * Get all receives for a purchase order.
     */
    public function getReceivesForPo(int $poId): Collection
    {
        return RmReceive::with(['items.rawMaterial', 'items.purchaseItem', 'receiver'])
            ->where('purchase_order_id', $poId)
            ->orderByDesc('receive_date')
            ->get();
    }

    /**
     * Update the PO status based on received quantities.
     */
    private function updatePoReceiveStatus(RmPurchaseOrder $po): void
    {
        $po->load('items');

        $allReceived = true;
        $anyReceived = false;

        foreach ($po->items as $item) {
            if ((float) $item->received_quantity > 0) {
                $anyReceived = true;
            }
            if ((float) $item->received_quantity < (float) $item->quantity) {
                $allReceived = false;
            }
        }

        if ($allReceived) {
            $po->update(['status' => RmPurchaseOrder::STATUS_RECEIVED]);
        } elseif ($anyReceived) {
            $po->update(['status' => RmPurchaseOrder::STATUS_PARTIAL_RECEIVED]);
        }
    }
}
