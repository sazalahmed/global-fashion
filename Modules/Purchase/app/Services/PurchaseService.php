<?php

namespace Modules\Purchase\Services;

use Modules\Purchase\Models\Purchase;
use Modules\Purchase\Models\PurchaseItem;
use Modules\Supplier\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Services\AccountingIntegrationService;
use Modules\Inventory\Services\InventoryService;
use Modules\Payment\Services\PaymentService;
use Modules\PurchaseReturn\Services\PurchaseReturnService;

class PurchaseService
{
    public function __construct(
        private readonly AccountingIntegrationService $accountingService,
        private readonly InventoryService $inventoryService,
        private readonly PaymentService $paymentService,
        private readonly PurchaseReturnService $purchaseReturnService,
    ) {}
    /**
     * Get paginated list of purchases with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Purchase::with(['supplier', 'branch'])
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

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['from']) || !empty($filters['to'])) {
            $query->byDateRange($filters['from'] ?? null, $filters['to'] ?? null);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new purchase order with items.
     */
    public function create(array $data, array $payments = []): Purchase
    {
        return DB::transaction(function () use ($data, $payments) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['created_by'] = Auth::id();
            $data['due_amount'] = 0;
            $data['paid_amount'] = 0;

            $purchase = Purchase::create($data);

            $this->createItems($purchase, $items);
            $this->calculateTotals($purchase);

            // Record purchase in general ledger only for non-draft purchases
            if (($data['status'] ?? 'draft') !== Purchase::STATUS_DRAFT) {
                try {
                    $this->accountingService->recordPurchase($purchase);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to record purchase journal entry', [
                        'purchase_id' => $purchase->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Record any supplier payments made at creation time. PaymentService
            // updates the purchase's paid/due/status and the supplier totals.
            $this->recordPayments($purchase, $payments);

            return $purchase->refresh();
        });
    }

    /**
     * Record supplier payments captured on the purchase form against this PO.
     */
    private function recordPayments(Purchase $purchase, array $payments): void
    {
        foreach ($payments as $payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $accountId = $payment['payment_account_id'] ?? null;
            $method = $accountId
                ? (\Modules\Payment\Models\PaymentAccount::where('id', $accountId)->value('account_type') ?? 'Cash')
                : 'Cash';

            $this->paymentService->create([
                'direction'          => 'pay',
                'party_type'         => 'supplier',
                'party_id'           => $purchase->supplier_id,
                'payment_type'       => 'purchase_payment',
                'amount'             => $amount,
                'payment_method'     => $method,
                'payment_account_id' => $accountId,
                'payment_date'       => $purchase->po_date,
                'reference'          => $payment['reference'] ?? $purchase->po_number,
                'branch_id'          => $purchase->branch_id,
            ], [
                [
                    'allocatable_type' => Purchase::class,
                    'allocatable_id'   => $purchase->id,
                    'amount'           => $amount,
                ],
            ]);
        }
    }

    /**
     * Update a purchase order. Any non-cancelled PO can be edited; if goods were
     * already received, the received stock and the purchase's journal entry are
     * reversed first, the items are replaced, and the PO is set back to
     * 'approved' so it can be received again with the new quantities.
     */
    public function update(Purchase $purchase, array $data, array $payments = []): Purchase
    {
        if ($purchase->status === Purchase::STATUS_CANCELLED) {
            throw new \RuntimeException('Cancelled purchase orders cannot be edited.');
        }

        $hasReturns = \Modules\PurchaseReturn\Models\PurchaseReturn::where('purchase_id', $purchase->id)
            ->where('status', '!=', 'cancelled')->exists();
        if ($hasReturns) {
            throw new \RuntimeException('This purchase has returns. Reverse the returns before editing.');
        }

        return DB::transaction(function () use ($purchase, $data, $payments) {
            $hadGrns = $purchase->grns()->exists();

            // 1. Reverse received stock + remove GRNs (safe: only unwinds on-hand).
            if ($hadGrns) {
                $this->reverseReceivedStock($purchase, 'purchase_edit');
            }

            // 2. Void the existing purchase journal entry (re-posted below).
            try {
                $this->accountingService->voidJournalEntry('purchase', $purchase->id);
            } catch (\Throwable $e) {
                \Log::warning("Failed to void JE while editing purchase {$purchase->po_number}: {$e->getMessage()}");
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            // 3. Un-receiving resets the PO to approved so it can be received again.
            if ($hadGrns) {
                $data['status'] = Purchase::STATUS_APPROVED;
            }

            $purchase->update($data);
            $purchase->items()->delete();
            $this->createItems($purchase, $items);
            $this->calculateTotals($purchase);

            // 4. Re-post the purchase journal entry for non-draft POs.
            if ($purchase->status !== Purchase::STATUS_DRAFT) {
                try {
                    $this->accountingService->recordPurchase($purchase->refresh());
                } catch (\Throwable $e) {
                    \Log::warning("Failed to re-record JE while editing purchase {$purchase->po_number}: {$e->getMessage()}");
                }
            }

            // 5. Any new supplier payments captured on the edit form.
            $this->recordPayments($purchase, $payments);

            return $purchase->refresh();
        });
    }

    /**
     * Reverse received GRN stock for a purchase and remove its GRNs. Only unwinds
     * what is still physically on hand (min(accepted, on-hand)); shortfalls are
     * logged. Shared by delete() and update().
     */
    private function reverseReceivedStock(Purchase $purchase, string $reason): void
    {
        $purchase->load(['grns.items']);
        foreach ($purchase->grns as $grn) {
            foreach ($grn->items as $grnItem) {
                $accepted = (int) $grnItem->quantity_accepted;
                if ($accepted <= 0) {
                    continue;
                }

                $onHand = $this->inventoryService->getStockLevel($grnItem->product_id, $grnItem->variant_id);
                $reversible = min($accepted, max(0, $onHand));

                if ($reversible > 0) {
                    try {
                        $this->inventoryService->adjustStock(
                            $grnItem->product_id,
                            $grnItem->variant_id,
                            -$reversible,
                            $reason,
                            $purchase->id,
                            0,
                            "Purchase {$reason}: {$purchase->po_number}"
                        );
                    } catch (\Throwable $e) {
                        \Log::warning("Failed to reverse GRN stock ({$reason}) for {$purchase->po_number}: {$e->getMessage()}");
                    }
                }

                if ($reversible < $accepted) {
                    \Log::warning(
                        "Purchase {$reason} {$purchase->po_number}: reversed {$reversible} of {$accepted} accepted units"
                        . " for product #{$grnItem->product_id}"
                        . ($grnItem->variant_id ? " (variant #{$grnItem->variant_id})" : '')
                        . " — the remaining " . ($accepted - $reversible) . " were already sold/moved."
                    );
                }
            }
            $grn->items()->delete();
            $grn->delete();
        }
    }

    /**
     * Create purchase items from normalized or form data.
     * Accepts both form field names (qty/discount) and canonical names (quantity/discount_amount).
     */
    private function createItems(Purchase $purchase, array $items): void
    {
        // Each line's VAT comes from the product's own vat_rate (0 for exempt),
        // not a blanket 15%. An explicit tax_rate on the item still wins.
        $productVat = \Modules\Product\Models\Product::whereIn('id', array_filter(array_column($items, 'product_id')))
            ->pluck('vat_rate', 'id');

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? $item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $discount = (float) ($item['discount'] ?? $item['discount_amount'] ?? 0);
            $taxRate = isset($item['tax_rate'])
                ? (float) $item['tax_rate']
                : (float) ($productVat[$item['product_id']] ?? 0);

            $taxableAmount = ($unitPrice * $qty) - $discount;
            $taxAmount = $taxableAmount * $taxRate / 100;
            $lineTotal = $taxableAmount + $taxAmount;

            $purchase->items()->create([
                'product_id'      => $item['product_id'],
                'variant_id'      => $item['variant_id'] ?? null,
                'unit_id'         => $item['unit_id'] ?? null,
                'quantity'        => $qty,
                'unit_price'      => $unitPrice,
                'discount_amount' => $discount,
                'tax_rate'        => $taxRate,
                'tax_amount'      => $taxAmount,
                'line_total'      => $lineTotal,
            ]);
        }
    }

    /**
     * Delete a purchase of ANY status, cascading to everything attached to it:
     * purchase returns, payments, received stock and accounting entries are all
     * reversed/removed so inventory counts and the ledger stay consistent.
     *
     * Order matters: returns are reversed first (they re-add the stock they had
     * sent back), then the GRN receipts are reversed, so on-hand stock unwinds
     * back to its pre-purchase value.
     */
    public function delete(Purchase $purchase): bool
    {
        return DB::transaction(function () use ($purchase) {
            $supplierId = $purchase->supplier_id;

            // 1. Purchase returns — reverse each (re-adds returned stock, voids its
            //    journal entry, restores supplier balance) then remove it.
            $returns = \Modules\PurchaseReturn\Models\PurchaseReturn::where('purchase_id', $purchase->id)->get();
            foreach ($returns as $return) {
                if ($return->status !== 'cancelled') {
                    $this->purchaseReturnService->cancel($return);
                }
                $return->items()->delete();
                $return->delete();
            }

            // 2. Payments — drop this purchase's allocations, then delete any
            //    payment left with no allocations (voiding its journal entry).
            //    A payment split across several documents keeps its other links.
            $allocations = \Modules\Payment\Models\PaymentAllocation::where('allocatable_type', Purchase::class)
                ->where('allocatable_id', $purchase->id)
                ->get();
            $paymentIds = $allocations->pluck('payment_id')->unique();

            \Modules\Payment\Models\PaymentAllocation::where('allocatable_type', Purchase::class)
                ->where('allocatable_id', $purchase->id)
                ->delete();

            foreach ($paymentIds as $paymentId) {
                $payment = \Modules\Payment\Models\Payment::find($paymentId);
                if ($payment && $payment->allocations()->count() === 0) {
                    if ($payment->journal_entry_id) {
                        try {
                            $this->accountingService->voidJournalEntry('payment', $payment->id);
                        } catch (\Throwable $e) {
                            \Log::warning("Failed to void journal entry for payment on purchase delete: {$e->getMessage()}");
                        }
                    }
                    $payment->delete();
                }
            }

            // 3. Reverse received stock from GRNs, then remove the GRNs + items.
            //    Only unwind what is still physically on hand: units already sold or
            //    transferred out cannot be reversed without driving stock negative.
            $this->reverseReceivedStock($purchase, 'purchase_delete');

            // 4. Void the purchase's own journal entry.
            try {
                $this->accountingService->voidJournalEntry('purchase', $purchase->id);
            } catch (\Throwable $e) {
                \Log::warning("Failed to void journal entry for deleted purchase {$purchase->po_number}: {$e->getMessage()}");
            }

            // 5. Remove the purchase items and the purchase itself.
            $purchase->items()->delete();
            $purchase->delete();

            $this->updateSupplierTotals($supplierId);

            return true;
        });
    }

    /**
     * Approve a purchase order.
     */
    public function approve(Purchase $purchase, int $userId): Purchase
    {
        if (!$purchase->canBeApproved()) {
            return $purchase;
        }

        $purchase->update([
            'status' => Purchase::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        // Record journal entry on approval (if not already recorded)
        $hasJournal = \Modules\Accounting\Models\JournalEntry::where('source_type', 'purchase')
            ->where('source_id', $purchase->id)
            ->where('status', 'posted')
            ->exists();

        if (!$hasJournal) {
            try {
                $this->accountingService->recordPurchase($purchase);
            } catch (\Throwable $e) {
                \Log::warning("Failed to record journal entry for approved purchase {$purchase->po_number}: {$e->getMessage()}");
            }
        }

        $this->updateSupplierTotals($purchase->supplier_id);

        return $purchase;
    }

    /**
     * Cancel a purchase order.
     */
    public function cancel(Purchase $purchase): Purchase
    {
        if (!$purchase->canBeCancelled()) {
            return $purchase;
        }

        // Prevent cancellation if active purchase returns exist
        $activeReturns = \Modules\PurchaseReturn\Models\PurchaseReturn::where('purchase_id', $purchase->id)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        if ($activeReturns > 0) {
            throw new \RuntimeException("Cannot cancel this purchase — {$activeReturns} active return(s) exist. Cancel the return(s) first.");
        }

        return DB::transaction(function () use ($purchase) {
            // Reverse any received stock from GRNs
            $purchase->load(['grns.items', 'items']);

            foreach ($purchase->grns as $grn) {
                foreach ($grn->items as $grnItem) {
                    $accepted = (int) $grnItem->quantity_accepted;
                    if ($accepted <= 0) {
                        continue;
                    }
                    try {
                        $this->inventoryService->adjustStock(
                            $grnItem->product_id,
                            $grnItem->variant_id,
                            -$accepted,
                            'purchase_cancel',
                            $purchase->id,
                            0,
                            "Purchase cancelled: {$purchase->po_number}"
                        );
                    } catch (\Throwable $e) {
                        \Log::warning("Failed to reverse GRN stock on purchase cancel: {$e->getMessage()}");
                    }
                }
            }

            // Clean up Payment module allocations linked to this purchase
            $allocations = \Modules\Payment\Models\PaymentAllocation::where('allocatable_type', Purchase::class)
                ->where('allocatable_id', $purchase->id)
                ->get();

            $paymentIdsToCheck = $allocations->pluck('payment_id')->unique()->toArray();
            \Modules\Payment\Models\PaymentAllocation::where('allocatable_type', Purchase::class)
                ->where('allocatable_id', $purchase->id)
                ->delete();

            // Delete payments that have no remaining allocations
            foreach ($paymentIdsToCheck as $paymentId) {
                $payment = \Modules\Payment\Models\Payment::find($paymentId);
                if ($payment && $payment->allocations()->count() === 0) {
                    // The purchase's own journal is voided below, but the
                    // payment carries a separate entry that has to go too —
                    // otherwise the GL keeps the cash movement for a payment
                    // that no longer exists.
                    if ($payment->journal_entry_id) {
                        $this->accountingService->voidJournalEntry('payment', $payment->id);
                    }
                    $payment->delete();
                }
            }

            // Void accounting journal entry
            try {
                $this->accountingService->voidJournalEntry('purchase', $purchase->id);
            } catch (\Throwable $e) {
                \Log::warning("Failed to void journal entry for cancelled purchase {$purchase->po_number}: {$e->getMessage()}");
            }

            $purchase->update([
                'status' => Purchase::STATUS_CANCELLED,
                'paid_amount' => 0,
                'due_amount' => 0,
                'payment_status' => 'unpaid',
            ]);
            $this->updateSupplierTotals($purchase->supplier_id);

            return $purchase;
        });
    }

    /**
     * Calculate and update purchase totals from items.
     */
    public function calculateTotals(Purchase $purchase): void
    {
        $purchase->load('items');

        // Subtotal is the pre-tax, pre-discount line amount (qty × unit price) so
        // the invoice breakdown reads correctly: Subtotal − Discount + Tax + Shipping
        // = Grand Total. Grand total is still the sum of tax-inclusive line totals
        // plus shipping, so it is unchanged from before this calc.
        $subtotal = $purchase->items->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_price);
        $lineTax = (float) $purchase->items->sum('tax_amount');
        $lineDiscount = (float) $purchase->items->sum('discount_amount');
        $lineTotalSum = (float) $purchase->items->sum('line_total');

        // Invoice-level (whole-order) discount + VAT/Tax, on top of per-line values.
        $orderDiscount = (float) ($purchase->order_discount ?? 0);
        $taxableForOrder = max(0, $subtotal - $lineDiscount - $orderDiscount);
        $orderTax = ($purchase->order_tax_mode ?? 'percent') === 'flat'
            ? (float) ($purchase->order_tax_amount ?? 0)
            : round($taxableForOrder * (float) ($purchase->order_tax_rate ?? 0) / 100, 2);

        $shipping = (float) ($purchase->shipping_cost ?? 0);
        $grandTotal = round($lineTotalSum - $orderDiscount + $orderTax + $shipping, 2);
        $dueAmount = $grandTotal - (float) $purchase->paid_amount;

        $purchase->update([
            'subtotal' => $subtotal,
            // Header discount/tax reflect the combined per-line + order-level values.
            'discount_amount' => $lineDiscount + $orderDiscount,
            'order_tax_amount' => $orderTax,
            'tax_amount' => $lineTax + $orderTax,
            'grand_total' => $grandTotal,
            'due_amount' => max(0, $dueAmount),
        ]);

        $this->updateSupplierTotals($purchase->supplier_id);
    }

    /**
     * Recalculate supplier's total_purchase, total_paid, and due_balance from all their purchases.
     */
    public function updateSupplierTotals(?int $supplierId): void
    {
        if (!$supplierId) {
            return;
        }

        $supplier = \Modules\Supplier\Models\Supplier::find($supplierId);
        if (!$supplier) {
            return;
        }

        $purchases = Purchase::where('supplier_id', $supplierId)
            ->whereNotIn('status', [Purchase::STATUS_DRAFT, Purchase::STATUS_CANCELLED])
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total_purchase, COALESCE(SUM(paid_amount), 0) as total_paid')
            ->first();

        $totalPurchase = (float) ($purchases->total_purchase ?? 0);
        $totalPaid = (float) ($purchases->total_paid ?? 0);

        $supplier->update([
            'total_purchase' => $totalPurchase,
            'total_paid'     => $totalPaid,
            'due_balance'    => max(0, $totalPurchase - $totalPaid),
        ]);
    }

    /**
     * Update payment status based on amounts.
     */
    public function updatePaymentStatus(Purchase $purchase): void
    {
        $paid = (float) $purchase->paid_amount;
        $total = (float) $purchase->grand_total;

        if ($paid <= 0) {
            $status = Purchase::PAYMENT_UNPAID;
        } elseif ($paid >= $total) {
            $status = Purchase::PAYMENT_PAID;
        } else {
            $status = Purchase::PAYMENT_PARTIAL;
        }

        $purchase->update([
            'payment_status' => $status,
            'due_amount' => max(0, $total - $paid),
        ]);
    }

    /**
     * Get purchase statistics for the index page.
     */
    public function getStats(array $filters = []): array
    {
        $base = Purchase::query()
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', '%' . $search . '%')
                      ->orWhereHas('supplier', function ($sq) use ($search) {
                          $sq->where('company_name', 'like', '%' . $search . '%');
                      });
                });
            })
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['payment_status']), fn ($q) => $q->where('payment_status', $filters['payment_status']))
            ->when(!empty($filters['supplier_id']), fn ($q) => $q->where('supplier_id', $filters['supplier_id']))
            ->when(!empty($filters['from']), fn ($q) => $q->where('po_date', '>=', $filters['from']))
            ->when(!empty($filters['to']), fn ($q) => $q->where('po_date', '<=', $filters['to']));

        $hasFilters = !empty(array_filter($filters));

        if ($hasFilters) {
            return [
                'thisMonthPurchases' => (clone $base)->sum('grand_total'),
                'pendingOrders'      => (clone $base)->whereIn('status', [Purchase::STATUS_DRAFT, Purchase::STATUS_PENDING])->count(),
                'totalPayable'       => (clone $base)->where('payment_status', '!=', Purchase::PAYMENT_PAID)->sum('due_amount'),
                'receivedThisMonth'  => (clone $base)->where('status', Purchase::STATUS_RECEIVED)->count(),
            ];
        }

        $currentMonth = now();

        return [
            'thisMonthPurchases' => (clone $base)->whereMonth('po_date', $currentMonth->month)
                ->whereYear('po_date', $currentMonth->year)
                ->sum('grand_total'),
            'pendingOrders' => (clone $base)->whereIn('status', [Purchase::STATUS_DRAFT, Purchase::STATUS_PENDING])
                ->count(),
            'totalPayable' => (clone $base)->where('payment_status', '!=', Purchase::PAYMENT_PAID)
                ->sum('due_amount'),
            'receivedThisMonth' => (clone $base)->where('status', Purchase::STATUS_RECEIVED)
                ->whereMonth('updated_at', $currentMonth->month)
                ->whereYear('updated_at', $currentMonth->year)
                ->count(),
        ];
    }
}
