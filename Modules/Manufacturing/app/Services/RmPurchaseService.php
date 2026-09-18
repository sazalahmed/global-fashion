<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\RmPurchaseOrder;
use Modules\Manufacturing\Models\RmPurchaseItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RmPurchaseService
{
    public function __construct(
        private readonly ManufacturingAccountingService $accountingService,
    ) {}

    /**
     * Get paginated list of RM purchase orders with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RmPurchaseOrder::with(['supplier'])
            ->withCount('items')
            ->orderByDesc('po_date')
            ->orderByDesc('id');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', '%' . $search . '%')
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('company_name', 'like', '%' . $search . '%');
                  });
            });
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->byPaymentStatus($filters['payment_status']);
        }

        if (!empty($filters['from']) || !empty($filters['to'])) {
            $query->byDateRange($filters['from'] ?? null, $filters['to'] ?? null);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new RM purchase order with items.
     */
    public function create(array $data): RmPurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['created_by'] = Auth::id();
            $data['paid_amount'] = 0;
            $data['due_amount'] = 0;

            $po = RmPurchaseOrder::create($data);

            $this->createItems($po, $items);
            $this->calculateTotals($po);

            return $po;
        });
    }

    /**
     * Update an RM purchase order (only if draft or pending).
     */
    public function update(RmPurchaseOrder $po, array $data): RmPurchaseOrder
    {
        if (!in_array($po->status, [RmPurchaseOrder::STATUS_DRAFT, RmPurchaseOrder::STATUS_PENDING])) {
            throw new \RuntimeException('Cannot update a purchase order that is not in draft or pending status.');
        }

        return DB::transaction(function () use ($po, $data) {
            // Prevent item replacement if receives exist
            $hasReceiveItems = $po->receives()->whereHas('items')->exists();
            if ($hasReceiveItems) {
                throw new \RuntimeException('Cannot replace items — goods have already been received against this purchase order.');
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            $po->update($data);
            $po->items()->delete();

            $this->createItems($po, $items);
            $this->calculateTotals($po);

            return $po;
        });
    }

    /**
     * Delete an RM purchase order (only if draft).
     */
    public function delete(RmPurchaseOrder $po): bool
    {
        if ($po->status !== RmPurchaseOrder::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft purchase orders can be deleted.');
        }

        return $po->delete();
    }

    /**
     * Find an RM purchase order by ID with relationships.
     */
    public function find(int $id): RmPurchaseOrder
    {
        return RmPurchaseOrder::with([
            'supplier',
            'items.rawMaterial',
            'receives.items',
            'creator',
            'approver',
        ])->findOrFail($id);
    }

    /**
     * Approve an RM purchase order.
     */
    public function approve(RmPurchaseOrder $po): RmPurchaseOrder
    {
        if (!$po->canBeApproved()) {
            throw new \RuntimeException('This purchase order cannot be approved in its current status.');
        }

        $po->update([
            'status' => RmPurchaseOrder::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $hasJournal = \Modules\Accounting\Models\JournalEntry::where('source_type', 'rm_purchase')
            ->where('source_id', $po->id)
            ->where('status', 'posted')
            ->exists();

        if (!$hasJournal && (float) $po->grand_total > 0) {
            try {
                $this->accountingService->recordRmPurchase($po);
            } catch (\Throwable $e) {
                Log::warning("Failed to record RM purchase JE {$po->po_number}: {$e->getMessage()}");
            }
        }

        return $po;
    }

    /**
     * Cancel an RM purchase order.
     */
    public function cancel(RmPurchaseOrder $po, RmStockService $stockService): RmPurchaseOrder
    {
        if (!$po->canBeCancelled()) {
            throw new \RuntimeException('This purchase order cannot be cancelled in its current status.');
        }

        return DB::transaction(function () use ($po, $stockService) {
            // Reverse any GRN stock entries
            $po->load('receives.items');

            foreach ($po->receives as $receive) {
                foreach ($receive->items as $receiveItem) {
                    // Reverse the stock that was added during receive
                    $goodQty = (float) $receiveItem->quantity_received - (float) $receiveItem->quantity_damaged;
                    if ($goodQty > 0) {
                        $stockService->adjustStock(
                            $receiveItem->raw_material_id,
                            -$goodQty,
                            0,
                            'return',
                            RmPurchaseOrder::class,
                            $po->id,
                            'Stock reversed due to PO cancellation: ' . $po->po_number
                        );
                    }
                }
            }

            try {
                $this->accountingService->voidEntry('rm_purchase', $po->id);
            } catch (\Throwable $e) {
                Log::warning("Failed to void RM purchase JE {$po->po_number}: {$e->getMessage()}");
            }

            $po->update([
                'status' => RmPurchaseOrder::STATUS_CANCELLED,
            ]);

            return $po;
        });
    }

    /**
     * Recalculate totals from items.
     */
    public function calculateTotals(RmPurchaseOrder $po): void
    {
        $items = $po->items;

        $subtotal = $items->sum(function ($item) {
            return (float) $item->unit_price * (float) $item->quantity;
        });

        $totalDiscount = $items->sum('discount_amount');
        $totalTax = $items->sum('tax_amount');
        $grandTotal = $subtotal - $totalDiscount + $totalTax + (float) $po->shipping_cost;
        $dueAmount = $grandTotal - (float) $po->paid_amount;

        $po->update([
            'subtotal' => $subtotal,
            'discount_amount' => $totalDiscount,
            'tax_amount' => $totalTax,
            'grand_total' => $grandTotal,
            'due_amount' => max(0, $dueAmount),
        ]);
    }

    /**
     * Update payment status based on paid vs grand total.
     */
    public function updatePaymentStatus(RmPurchaseOrder $po): void
    {
        $paidAmount = (float) $po->paid_amount;
        $grandTotal = (float) $po->grand_total;

        if ($paidAmount <= 0) {
            $status = RmPurchaseOrder::PAYMENT_UNPAID;
        } elseif ($paidAmount >= $grandTotal) {
            $status = RmPurchaseOrder::PAYMENT_PAID;
        } else {
            $status = RmPurchaseOrder::PAYMENT_PARTIAL;
        }

        $po->update([
            'payment_status' => $status,
            'due_amount' => max(0, $grandTotal - $paidAmount),
        ]);
    }

    /**
     * Get stats for dashboard.
     */
    public function getStats(): array
    {
        return [
            'total_orders' => RmPurchaseOrder::count(),
            'draft_orders' => RmPurchaseOrder::byStatus(RmPurchaseOrder::STATUS_DRAFT)->count(),
            'pending_orders' => RmPurchaseOrder::byStatus(RmPurchaseOrder::STATUS_PENDING)->count(),
            'approved_orders' => RmPurchaseOrder::byStatus(RmPurchaseOrder::STATUS_APPROVED)->count(),
            'total_value' => RmPurchaseOrder::whereNotIn('status', [RmPurchaseOrder::STATUS_CANCELLED, RmPurchaseOrder::STATUS_DRAFT])->sum('grand_total'),
            'total_due' => RmPurchaseOrder::whereNotIn('status', [RmPurchaseOrder::STATUS_CANCELLED, RmPurchaseOrder::STATUS_DRAFT])->sum('due_amount'),
        ];
    }

    /**
     * Create purchase items from data array.
     */
    private function createItems(RmPurchaseOrder $po, array $items): void
    {
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? $item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $discount = (float) ($item['discount'] ?? $item['discount_amount'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $taxableAmount = ($unitPrice * $qty) - $discount;
            $taxAmount = $taxableAmount > 0 ? $taxableAmount * $taxRate / 100 : 0;
            $lineTotal = $taxableAmount + $taxAmount;

            $po->items()->create([
                'raw_material_id' => $item['raw_material_id'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'attachment_path' => $item['attachment_path'] ?? null,
                'attachment_name' => $item['attachment_name'] ?? null,
            ]);
        }
    }
}
