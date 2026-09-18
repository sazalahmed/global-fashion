<?php

namespace Modules\PurchaseReturn\Services;

use Modules\PurchaseReturn\Models\PurchaseReturn;
use Modules\Supplier\Models\Supplier;
use Modules\Accounting\Services\AccountingIntegrationService;
use Modules\Inventory\Services\InventoryService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly AccountingIntegrationService $accountingService,
    ) {}
    /**
     * Get paginated list of purchase returns.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseReturn::with(['purchase', 'supplier'])
            ->orderByDesc('return_date')
            ->orderByDesc('id');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', '%' . $search . '%')
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('company_name', 'like', '%' . $search . '%');
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('return_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('return_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a purchase return with items.
     */
    public function create(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['created_by'] = Auth::id();

            // The presence of a refund amount decides how the return settles:
            // cash refund vs. credit note against the payable.
            if ((float) ($data['refunded_amount'] ?? 0) > 0) {
                $data['resolution'] = 'refund';
            } else {
                $data['resolution'] = 'credit_note';
                $data['refunded_amount'] = 0;
                $data['refund_account_id'] = null;
            }

            $return = PurchaseReturn::create($data);

            $subtotal = 0;
            $totalTax = 0;

            foreach ($items as $item) {
                // Re-validate unit_price from original purchase item
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                if ($return->purchase_id) {
                    $origItem = \Modules\Purchase\Models\PurchaseItem::where('purchase_id', $return->purchase_id)
                        ->where('product_id', $item['product_id'])
                        ->first();
                    if ($origItem) {
                        $unitPrice = (float) $origItem->unit_price;
                    }
                }

                $lineTotal = ($unitPrice * (float) $item['quantity']);
                $taxAmount = (float) ($item['tax_amount'] ?? 0);
                $lineTotal += $taxAmount;

                $return->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                    'reason' => $item['reason'] ?? null,
                ]);

                $subtotal += ($unitPrice * (float) $item['quantity']);
                $totalTax += $taxAmount;
            }

            $return->update([
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'total' => $subtotal + $totalTax,
            ]);

            return $return;
        });
    }

    /**
     * Update an existing draft purchase return.
     */
    public function update(PurchaseReturn $return, array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($return, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $return->update([
                'purchase_id'  => $data['purchase_id'],
                'supplier_id'  => $data['supplier_id'],
                'return_date'  => $data['return_date'],
                'reason'       => $data['reason'] ?? $return->reason,
                'notes'        => $data['notes'] ?? null,
                'branch_id'    => $data['branch_id'] ?? $return->branch_id,
            ]);

            $return->items()->delete();

            $subtotal = 0;
            $totalTax = 0;

            foreach ($items as $item) {
                $lineTotal = ((float) $item['unit_price'] * (float) $item['quantity']);
                $taxAmount = (float) ($item['tax_amount'] ?? 0);
                $lineTotal += $taxAmount;

                $return->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                    'reason'     => $item['reason'] ?? null,
                ]);

                $subtotal += ((float) $item['unit_price'] * (float) $item['quantity']);
                $totalTax += $taxAmount;
            }

            $return->update([
                'subtotal'   => $subtotal,
                'tax_amount' => $totalTax,
                'total'      => $subtotal + $totalTax,
            ]);

            return $return->fresh();
        });
    }

    /**
     * Complete a purchase return — deduct stock and credit supplier.
     */
    public function complete(PurchaseReturn $return): PurchaseReturn
    {
        if ($return->status === PurchaseReturn::STATUS_COMPLETED) {
            return $return;
        }

        return DB::transaction(function () use ($return) {
            $return->load(['items', 'purchase']);

            // Deduct stock via InventoryService (creates stock ledger entry)
            foreach ($return->items as $item) {
                $this->inventoryService->adjustStock(
                    $item->product_id,
                    $item->variant_id,
                    -(int) $item->quantity,
                    'purchase_return',
                    $return->id,
                    (float) $item->unit_price,
                    "Purchase return: {$return->return_number}"
                );
            }

            $supplier = $return->supplier;
            $returnTotal = (float) $return->total;
            $isCashRefund = (float) $return->refunded_amount > 0;

            // Record accounting. A cash refund debits Cash/Bank (no payable change);
            // a credit note debits Accounts Payable (reduces what we owe).
            try {
                if ($isCashRefund) {
                    $this->accountingService->recordPurchaseReturnRefund($return);
                } else {
                    $this->accountingService->recordPurchaseReturn($return);
                }
            } catch (\Throwable $e) {
                \Log::warning("Failed to record journal entry for purchase return {$return->return_number}: {$e->getMessage()}");
            }

            // Update supplier balances. Goods left, so total_purchase always drops.
            // A credit note also reduces the payable; a cash refund does not (we got
            // cash back instead), so recover the paid amount instead.
            $supplier->decrement('total_purchase', $returnTotal);
            if ($isCashRefund) {
                $refund = (float) $return->refunded_amount;
                $supplier->decrement('total_paid', min($refund, (float) $supplier->total_paid));
            } else {
                $supplier->decrement('due_balance', min($returnTotal, (float) $supplier->due_balance));
            }

            $return->update(['status' => PurchaseReturn::STATUS_COMPLETED]);

            return $return;
        });
    }

    /**
     * Cancel a purchase return — reverse stock if it was completed.
     */
    public function cancel(PurchaseReturn $return): PurchaseReturn
    {
        if ($return->status === 'cancelled') {
            throw new \Exception('This return is already cancelled.');
        }

        return DB::transaction(function () use ($return) {
            if ($return->status === PurchaseReturn::STATUS_COMPLETED) {
                $return->load(['items', 'purchase']);

                foreach ($return->items as $item) {
                    try {
                        $this->inventoryService->adjustStock(
                            $item->product_id,
                            $item->variant_id,
                            (int) $item->quantity,
                            'purchase_return_cancel',
                            $return->id,
                            (float) $item->unit_price,
                            "Purchase return cancelled: {$return->return_number}"
                        );
                    } catch (\Throwable $e) {
                        \Log::warning("Failed to reverse stock on purchase return cancel: {$e->getMessage()}");
                    }
                }

                // Reverse supplier balance adjustments (mirror of complete()).
                $supplier = $return->supplier;
                if ($supplier) {
                    $returnTotal = (float) $return->total;
                    $supplier->increment('total_purchase', $returnTotal);
                    if ((float) $return->refunded_amount > 0) {
                        $supplier->increment('total_paid', (float) $return->refunded_amount);
                    } else {
                        $supplier->increment('due_balance', $returnTotal);
                    }
                }

                // Void accounting journal entry
                try {
                    $this->accountingService->voidJournalEntry('purchase_return', $return->id);
                } catch (\Throwable $e) {
                    \Log::warning("Failed to void journal entry for purchase return cancel: {$e->getMessage()}");
                }
            }

            $return->update(['status' => 'cancelled']);

            return $return->fresh();
        });
    }

    /**
     * Get return statistics.
     */
    public function getStats(): array
    {
        return [
            'total' => PurchaseReturn::count(),
            'draft' => PurchaseReturn::where('status', PurchaseReturn::STATUS_DRAFT)->count(),
            'completed' => PurchaseReturn::where('status', PurchaseReturn::STATUS_COMPLETED)->count(),
            'totalValue' => PurchaseReturn::where('status', PurchaseReturn::STATUS_COMPLETED)->sum('total'),
        ];
    }
}
