<?php

namespace Modules\Purchase\Services;

use Modules\Purchase\Models\Purchase;
use Modules\Purchase\Models\GoodsReceiveNote;
use Modules\Inventory\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GrnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}
    /**
     * Create a GRN for a purchase order — receives stock.
     */
    public function create(Purchase $purchase, array $data): GoodsReceiveNote
    {
        if (!$purchase->canBeReceived()) {
            throw new \RuntimeException('This purchase order cannot receive stock.');
        }

        return DB::transaction(function () use ($purchase, $data) {
            $grn = GoodsReceiveNote::create([
                'purchase_id' => $purchase->id,
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'branch_id' => $data['branch_id'] ?? $purchase->branch_id,
                'received_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $allFullyReceived = true;

            foreach ($data['items'] ?? [] as $item) {
                $quantityReceived = (float) ($item['quantity_received'] ?? 0);
                if ($quantityReceived <= 0) {
                    continue;
                }

                // Accepted = received − rejected. The form sends received + rejected
                // (not accepted), so accepted must be derived here. Only accepted
                // units enter stock and count toward the PO; rejected stay pending.
                $quantityRejected = min((float) ($item['quantity_rejected'] ?? 0), $quantityReceived);
                $quantityAccepted = max(0, $quantityReceived - $quantityRejected);

                $grn->items()->create([
                    'purchase_item_id' => $item['purchase_item_id'],
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity_received' => $quantityReceived,
                    'quantity_accepted' => $quantityAccepted,
                    'quantity_rejected' => $quantityRejected,
                    'reject_reason' => $item['reject_reason'] ?? null,
                ]);

                // Update purchase item received progress — accepted units only, so
                // rejected quantities remain on the PO as outstanding/remaining.
                $purchaseItem = $purchase->items()->findOrFail($item['purchase_item_id']);
                $purchaseItem->increment('received_quantity', $quantityAccepted);

                // Check if this item is fully received
                if ((float) $purchaseItem->fresh()->received_quantity < (float) $purchaseItem->quantity) {
                    $allFullyReceived = false;
                }

                // Update stock via InventoryService (creates ledger entry)
                if ($quantityAccepted > 0) {
                    $this->inventoryService->adjustStock(
                        (int) $item['product_id'],
                        !empty($item['variant_id']) ? (int) $item['variant_id'] : null,
                        (int) $quantityAccepted,
                        'purchase_receive',
                        $grn->id,
                        (float) ($purchaseItem->unit_price ?? 0),
                        "GRN received for PO: {$purchase->po_number}"
                    );
                }
            }

            // Update purchase status
            $purchase->load('items');
            $allItemsReceived = $purchase->items->every(function ($item) {
                return (float) $item->received_quantity >= (float) $item->quantity;
            });

            $purchase->update([
                'status' => $allItemsReceived
                    ? Purchase::STATUS_RECEIVED
                    : Purchase::STATUS_PARTIAL_RECEIVED,
            ]);

            return $grn;
        });
    }

    /**
     * List GRNs for a purchase order.
     */
    public function listByPurchase(Purchase $purchase)
    {
        return $purchase->grns()
            ->with(['receivedBy', 'items'])
            ->orderByDesc('received_date')
            ->get();
    }
}
