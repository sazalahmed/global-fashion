<?php

namespace Modules\SaleReturn\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\SaleReturn\Models\SaleReturn;
use Modules\SaleReturn\Models\SaleReturnItem;
use Modules\Inventory\Services\InventoryService;
use Modules\Accounting\Services\AccountingIntegrationService;
use Modules\Accounting\Services\CreditNoteService;

class SaleReturnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly AccountingIntegrationService $accountingService,
        private readonly CreditNoteService $creditNoteService,
    ) {}

    /**
     * List sale returns with filters and pagination.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return SaleReturn::with(['sale', 'customer', 'branch', 'creator'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->byStatus($s))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('return_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('return_date', '<=', $d))
            ->latest('return_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Find a sale return with all related data.
     */
    public function find(int $id): SaleReturn
    {
        return SaleReturn::with([
            'sale.customer',
            'customer',
            'branch',
            'items.product',
            'items.variant.attributeValues.attribute',
            'creditNote',
            'journalEntry.lines.account',
            'creator',
        ])->findOrFail($id);
    }

    /**
     * Create a new sale return with items.
     */
    public function create(array $data, array $items): SaleReturn
    {
        return DB::transaction(function () use ($data, $items) {
            $return = SaleReturn::create([
                'return_number' => $this->generateReturnNumber(),
                'sale_id'       => $data['sale_id'],
                'customer_id'   => $data['customer_id'] ?? null,
                'branch_id'     => $data['branch_id'],
                'return_date'   => $data['return_date'],
                'reason'        => $data['reason'],
                'refund_method' => $data['refund_method'] ?? 'cash',
                'notes'         => $data['notes'] ?? null,
                'status'        => 'draft',
                'created_by'    => auth()->id(),
            ]);

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($items as $item) {
                // Re-validate unit_price from original sale item if linked
                $validatedPrice = (float) ($item['unit_price'] ?? 0);
                if (!empty($item['sale_item_id'])) {
                    $origItem = \Modules\Sale\Models\SaleItem::find($item['sale_item_id']);
                    if ($origItem) {
                        $validatedPrice = (float) $origItem->unit_price;
                    }
                }

                $itemSubtotal = $item['quantity'] * $validatedPrice;
                $itemTax = $item['tax_amount'] ?? 0;

                $return->items()->create([
                    'sale_item_id' => $item['sale_item_id'] ?? null,
                    'product_id'   => $item['product_id'],
                    'variant_id'   => $item['variant_id'] ?? null,
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $validatedPrice,
                    'tax_amount'   => $itemTax,
                    'subtotal'     => $itemSubtotal,
                    'condition'    => $item['condition'] ?? 'good',
                    'note'         => $item['note'] ?? null,
                ]);

                $subtotal += $itemSubtotal;
                $taxTotal += $itemTax;
            }

            $return->update([
                'subtotal'     => $subtotal,
                'tax_amount'   => $taxTotal,
                'total_amount' => $subtotal + $taxTotal,
            ]);

            return $return->load('items.product');
        });
    }

    /**
     * Update an existing draft sale return.
     */
    public function update(SaleReturn $return, array $data, array $items): SaleReturn
    {
        return DB::transaction(function () use ($return, $data, $items) {
            $return->update([
                'sale_id'       => $data['sale_id'],
                'customer_id'   => $data['customer_id'] ?? $return->customer_id,
                'branch_id'     => $data['branch_id'] ?? $return->branch_id,
                'return_date'   => $data['return_date'],
                'reason'        => $data['reason'],
                'refund_method' => $data['refund_method'] ?? $return->refund_method,
                'notes'         => $data['notes'] ?? null,
            ]);

            $return->items()->delete();

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($items as $item) {
                // Re-validate unit_price from original sale item if linked
                $validatedPrice = (float) ($item['unit_price'] ?? 0);
                if (!empty($item['sale_item_id'])) {
                    $origItem = \Modules\Sale\Models\SaleItem::find($item['sale_item_id']);
                    if ($origItem) {
                        $validatedPrice = (float) $origItem->unit_price;
                    }
                }

                $itemSubtotal = $item['quantity'] * $validatedPrice;
                $itemTax = $item['tax_amount'] ?? 0;

                $return->items()->create([
                    'sale_item_id' => $item['sale_item_id'] ?? null,
                    'product_id'   => $item['product_id'],
                    'variant_id'   => $item['variant_id'] ?? null,
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $validatedPrice,
                    'tax_amount'   => $itemTax,
                    'subtotal'     => $itemSubtotal,
                    'condition'    => $item['condition'] ?? 'good',
                    'note'         => $item['note'] ?? null,
                ]);

                $subtotal += $itemSubtotal;
                $taxTotal += $itemTax;
            }

            $return->update([
                'subtotal'     => $subtotal,
                'tax_amount'   => $taxTotal,
                'total_amount' => $subtotal + $taxTotal,
            ]);

            return $return->load('items.product');
        });
    }

    /**
     * Approve a draft sale return.
     */
    public function approve(SaleReturn $return): SaleReturn
    {
        if ($return->status !== 'draft') {
            throw new \Exception('Only draft returns can be approved.');
        }

        // Validate quantities don't exceed original sale item quantities
        $return->load('items', 'sale.items');
        $saleItems = $return->sale->items->keyBy('id');

        foreach ($return->items as $item) {
            if ($item->sale_item_id && $saleItems->has($item->sale_item_id)) {
                $saleItem = $saleItems->get($item->sale_item_id);
                if ($item->quantity > $saleItem->quantity) {
                    throw new \Exception(
                        "Return quantity ({$item->quantity}) exceeds sale quantity ({$saleItem->quantity}) for product #{$item->product_id}."
                    );
                }
            }
        }

        $return->update(['status' => 'approved']);

        return $return->fresh();
    }

    /**
     * Complete an approved sale return: restock, journal entry, credit note, update sale.
     */
    public function complete(SaleReturn $return): SaleReturn
    {
        if ($return->status !== 'approved') {
            throw new \Exception('Only approved returns can be completed.');
        }

        return DB::transaction(function () use ($return) {
            $return->load('items.product', 'branch', 'sale');

            // 1. Restock items in good condition via InventoryService
            foreach ($return->items as $item) {
                if ($item->condition === 'good') {
                    $this->inventoryService->adjustStock(
                        $item->product_id,
                        $item->variant_id,
                        $item->quantity,
                        'sale_return',
                        $return->id,
                        $item->unit_price,
                        "Return: {$return->return_number}"
                    );
                }
            }

            // 2. Create journal entry via AccountingIntegrationService
            $je = null;
            try {
                $je = $this->accountingService->recordSaleReturn($return);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to record sale return journal: {$e->getMessage()}");
            }

            // 3. If refund_method is credit_note, create credit note
            $creditNoteId = null;
            if ($return->refund_method === 'credit_note') {
                $cn = $this->creditNoteService->create([
                    'customer_id'    => $return->customer_id,
                    'sale_id'        => $return->sale_id,
                    'sale_return_id' => $return->id,
                    'issue_date'     => $return->return_date,
                    'reason'         => "Sale return: {$return->return_number}",
                    'items'          => $return->items->map(fn ($item) => [
                        'product_id'  => $item->product_id,
                        'description' => $item->product->name,
                        'quantity'    => $item->quantity,
                        'unit_price'  => $item->unit_price,
                        'tax_amount'  => $item->tax_amount,
                    ])->toArray(),
                ]);
                $creditNoteId = $cn->id;
            }

            // 4. Update sale amounts — reduce due, prevent negative
            $sale = $return->sale;
            $returnAmount = (float) $return->total_amount;

            $sale->due_amount = max(0, (float) $sale->due_amount - $returnAmount);
            $sale->grand_total = max(0, (float) $sale->grand_total - $returnAmount);
            $sale->payment_status = $sale->due_amount <= 0 ? 'paid' : ($sale->paid_amount > 0 ? 'partial' : 'unpaid');
            // The returned items are now restocked — clear the courier
            // partial-delivery "needs return" flag if it was set.
            $sale->needs_return = false;
            $sale->save();

            // 4b. Update customer totals
            if ($sale->customer_id && $sale->customer) {
                $sale->customer->decrement('total_purchased', $returnAmount);
            }

            // 5. Mark as completed
            $return->update([
                'status'           => 'completed',
                'journal_entry_id' => $je?->id,
                'credit_note_id'   => $creditNoteId,
            ]);

            return $return->fresh();
        });
    }

    /**
     * Cancel a draft or approved sale return.
     */
    public function cancel(SaleReturn $return): SaleReturn
    {
        if ($return->status === 'cancelled') {
            throw new \Exception('This return is already cancelled.');
        }

        return DB::transaction(function () use ($return) {
            // If completed, reverse stock adjustments
            if ($return->status === 'completed') {
                $return->load('items.product', 'branch');

                foreach ($return->items as $item) {
                    if ($item->condition === 'good') {
                        try {
                            $this->inventoryService->adjustStock(
                                $item->product_id,
                                $item->variant_id,
                                -(int) $item->quantity,
                                'sale_return_cancel',
                                $return->id,
                                (float) $item->unit_price,
                                "Sale return cancelled: {$return->return_number}"
                            );
                        } catch (\Throwable $e) {
                            \Log::warning("Failed to reverse stock on sale return cancel: {$e->getMessage()}");
                        }
                    }
                }

                // Reverse sale amount adjustments
                $sale = $return->sale;
                if ($sale) {
                    $returnAmount = (float) $return->total_amount;
                    $sale->grand_total = (float) $sale->grand_total + $returnAmount;
                    $sale->due_amount = (float) $sale->due_amount + $returnAmount;
                    $sale->payment_status = $sale->due_amount <= 0 ? 'paid' : ($sale->paid_amount > 0 ? 'partial' : 'unpaid');
                    $sale->save();

                    if ($sale->customer_id && $sale->customer) {
                        $sale->customer->increment('total_purchased', $returnAmount);
                    }
                }

                // Void journal entry
                if ($return->journal_entry_id) {
                    try {
                        $this->accountingService->voidJournalEntry('sale_return', $return->id);
                    } catch (\Throwable $e) {
                        \Log::warning("Failed to void journal entry for sale return cancel: {$e->getMessage()}");
                    }
                }

                // Cancel associated credit note
                if ($return->credit_note_id) {
                    try {
                        $creditNote = $return->creditNote;
                        if ($creditNote) {
                            $creditNote->update(['status' => 'cancelled']);
                        }
                    } catch (\Throwable $e) {
                        \Log::warning("Failed to cancel credit note for sale return cancel: {$e->getMessage()}");
                    }
                }
            }

            $return->update(['status' => 'cancelled']);

            return $return->fresh();
        });
    }

    /**
     * Get summary statistics for the index page.
     */
    public function getStats(): array
    {
        return [
            'total'       => SaleReturn::count(),
            'pending'     => SaleReturn::byStatus('draft')->count() + SaleReturn::byStatus('approved')->count(),
            'completed'   => SaleReturn::byStatus('completed')->count(),
            'total_value' => SaleReturn::byStatus('completed')->sum('total_amount'),
        ];
    }

    /**
     * Get sale items for AJAX (used when selecting a sale in the create form).
     */
    public function getSaleItems(int $saleId): Collection
    {
        return \Modules\Sale\Models\SaleItem::where('sale_id', $saleId)
            ->with(['product', 'variant.attributeValues.attribute'])
            ->get();
    }

    /**
     * Generate a unique return number in format SR-YYYY-XXXX.
     */
    private function generateReturnNumber(): string
    {
        $year = now()->format('Y');
        $last = SaleReturn::whereYear('created_at', $year)->count() + 1;

        return 'SR-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
