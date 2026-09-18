<?php

namespace Modules\Sale\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Modules\Payment\Services\PaymentService;
use Modules\Product\Models\Product;
use Modules\Sale\Models\Sale;
use Modules\Sale\Models\SaleItem;
use Modules\Variant\Models\ProductVariant;
use Modules\Accounting\Services\AccountingIntegrationService;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Models\StockLedger;

class SaleService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly AccountingIntegrationService $accountingService,
        private readonly InventoryService $inventoryService,
    ) {}

    // ── List ──

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyListFilters(
            Sale::with(['customer', 'branch', 'creator', 'assignedStaff:id,name', 'district', 'thana', 'items.product:id,name,model,thumbnail', 'items.combo:id,name,thumbnail', 'items.variant.attributeValues.attribute', 'ecommerceOrder:id,sale_id,customer_phone']),
            $filters
        )
            ->latest('sale_date')
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Sums over the SAME result set list() returns for these filters — feeds
     * the calculation row under the sales table. Unlike the stat cards
     * (fixed today/this-month KPIs), these follow the active filters and
     * cover every page of the result, not just the visible one.
     */
    public function getListTotals(array $filters = []): array
    {
        $totals = $this->applyListFilters(Sale::query(), $filters)
            ->selectRaw('COALESCE(SUM(grand_total), 0) as grand_total')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
            ->selectRaw('COALESCE(SUM(due_amount), 0) as due_amount')
            ->selectRaw('COUNT(*) as sales_count')
            ->first();

        return [
            'grand_total' => (float) $totals->grand_total,
            'paid_amount' => (float) $totals->paid_amount,
            'due_amount'  => (float) $totals->due_amount,
            'sales_count' => (int) $totals->sales_count,
        ];
    }

    /**
     * The one place the sales-list filters are translated into query
     * constraints — shared by list() and getListTotals() so the calculation
     * row always sums exactly what the table shows.
     */
    private function applyListFilters($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('sale_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('sale_date', '<=', $d))
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['branch_id'] ?? null, fn ($q, $v) => $q->where('branch_id', $v));
    }

    // ── Find ──

    public function find(int $id): Sale
    {
        return Sale::with([
            'customer', 'branch', 'creator',
            'items.product', 'items.variant.attributeValues.attribute',
            'allocations.payment.paymentAccount',
            'allocations.payment.creator',
            'trackingEvents',
        ])->findOrFail($id);
    }

    // ── Create ──

    public function createSale(array $data, array $items, array $payments = []): Sale
    {
        return DB::transaction(function () use ($data, $items, $payments) {
            // Map invoice_date to sale_date if present
            $saleDate = $data['invoice_date'] ?? $data['sale_date'] ?? now()->toDateString();

            $invoiceNumber = $this->generateInvoiceNumber($saleDate);
            $totals = $this->calculateTotals($items, $data);

            $sale = Sale::create([
                'invoice_number'         => $invoiceNumber,
                'reference_number'       => $data['reference_number'] ?? null,
                'customer_id'            => $data['customer_id'] ?? null,
                'customer_name_snapshot' => $data['customer_name_snapshot'] ?? null,
                'customer_phone_snapshot'=> $data['customer_phone_snapshot'] ?? null,
                'customer_address'       => $data['customer_address'] ?? null,
                'billing_address'        => $data['billing_address'] ?? null,
                'branch_id'              => $data['branch_id'] ?? auth()->user()->branch_id ?? \Modules\Branch\Models\Branch::where('is_active', true)->value('id'),
                'sale_date'              => $saleDate,
                'due_date'               => $data['due_date'] ?? null,
                'source'                 => $data['source'] ?? 'store',
                'price_type'             => $data['price_type'] ?? 'regular',
                'status'                 => $data['sale_status'] ?? $data['status'] ?? 'confirmed',
                'payment_status'         => 'unpaid',
                'subtotal'               => $totals['subtotal'],
                'discount_type'          => $data['discount_type'] ?? null,
                'discount_value'         => $data['discount_value'] ?? 0,
                'discount_amount'        => $totals['discount_amount'],
                'tax_rate'               => $data['tax_rate'] ?? 0,
                'tax_amount'             => $totals['tax_amount'],
                'shipping_charge'        => $data['shipping_charge'] ?? 0,
                'grand_total'            => $totals['grand_total'],
                'paid_amount'            => 0,
                'due_amount'             => $totals['grand_total'],
                'notes'                  => $data['notes'] ?? null,
                'item_description'       => $data['item_description'] ?? null,
                'staff_note'             => $data['staff_note'] ?? null,
                'created_by'             => $data['created_by'] ?? auth()->id(),
            ]);

            // Create sale items with product snapshots
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                $variantId = $item['variant_id'] ?? null;
                $variant = $variantId ? ProductVariant::with('attributeValues.attribute')->find($variantId) : null;

                $itemName = $product ? $product->name : ($item['product_name'] ?? 'Unknown');
                $itemSku = $product ? $product->sku : ($item['product_sku'] ?? '');

                // Append variant name and use variant SKU when applicable
                if ($variant) {
                    $itemName .= ' — ' . $variant->variant_name;
                    $itemSku = $variant->sku;
                }

                $itemSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0);
                $itemTax = $product
                    ? $this->lineVat($itemSubtotal, (float) $product->vat_rate, (bool) $product->vat_inclusive)
                    : 0;

                SaleItem::create([
                    'sale_id'         => $sale->id,
                    'product_id'      => $item['product_id'],
                    'variant_id'      => $variantId,
                    'variant_label'   => $item['variant_label'] ?? ($variant?->variant_name),
                    'product_name'    => $itemName,
                    'product_sku'     => $itemSku,
                    'quantity'        => $item['quantity'],
                    'batch_no'        => $item['batch_no'] ?? null,
                    'expired_date'    => $item['expired_date'] ?? null,
                    'unit_price'      => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_amount'      => $itemTax,
                    'subtotal'        => $itemSubtotal,
                    // Combo provenance, if this line was expanded from a combo.
                    'combo_id'        => $item['combo_id'] ?? null,
                    'combo_group'     => $item['combo_group'] ?? null,
                    'combo_name'      => $item['combo_name'] ?? null,
                    'combo_price'     => $item['combo_price'] ?? null,
                ]);
            }

            // Deduct inventory when the sale is created in a stock-out status.
            // Counter sales (POS/store) go out at 'confirmed'; online/delivery
            // sales only go out once handed to the courier (stockOutStatusesFor).
            if (in_array($sale->status, $this->stockOutStatusesFor($sale), true)) {
                $this->deductStockForItems($sale, $items);
            }

            // Process payments
            $totalPaid = 0;

            foreach ($payments as $payment) {
                $isAdvanceAdjust = ($payment['method'] ?? null) === 'advance';
                $paymentAccountId = $payment['payment_account_id'] ?? null;
                $paymentMethod = $payment['method'] ?? null;

                // No account picked on the form — fall back to the default
                // (Cash) account so the advance is still recorded. Advance
                // adjustments move no new money, so they keep no account.
                if (!$paymentAccountId && !$isAdvanceAdjust) {
                    $paymentAccountId = $this->defaultPaymentAccountId();
                }

                // Resolve payment_method from payment_account if not provided
                if (!$paymentMethod && $paymentAccountId) {
                    $paymentMethod = \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type') ?? 'cash';
                }

                $this->paymentService->create([
                    'direction'          => 'receive',
                    'party_type'         => $sale->customer_id ? 'customer' : null,
                    'party_id'           => $sale->customer_id,
                    'payment_type'       => $isAdvanceAdjust ? 'advance_return' : 'sale_payment',
                    'amount'             => $payment['amount'],
                    'payment_method'     => $isAdvanceAdjust ? 'cash' : $paymentMethod,
                    'payment_account_id' => $paymentAccountId,
                    'payment_date'       => $sale->sale_date,
                    'reference'          => $sale->invoice_number,
                    'note'               => $isAdvanceAdjust ? 'Adjusted from customer advance' : null,
                ], [
                    [
                        'allocatable_type' => Sale::class,
                        'allocatable_id'   => $sale->id,
                        'amount'           => $payment['amount'],
                    ],
                ]);

                $totalPaid += $payment['amount'];
            }

            // Update payment totals on the sale
            $sale->paid_amount = $totalPaid;
            $sale->due_amount = $sale->grand_total - $totalPaid - (float) $sale->due_discount_amount;

            if ($sale->due_amount <= 0) {
                $sale->payment_status = 'paid';
                $sale->due_amount = 0;
            } elseif ($totalPaid > 0) {
                $sale->payment_status = 'partial';
            }

            $sale->save();

            // Keep the customer's stored total_purchased in step with the
            // accessor by recomputing from delivered sales. A freshly created
            // sale is typically 'confirmed' (POS/admin) and so contributes
            // nothing until it is delivered. total_paid is handled by
            // PaymentService::updatePartyTotals().
            $this->recomputeCustomerPurchased($sale->customer_id);

            // Post to the ledger if this sale was created already delivered
            // (counter sales often are). Anything earlier in the lifecycle
            // posts later, when changeStatus() reaches delivery.
            $this->syncSaleJournal($sale);

            // Notify admin/manager users about new sale.
            // Ecommerce orders are placed by storefront customers (separate guard, not in `users`),
            // so we never self-exclude — the admin who happens to be browsing isn't the "creator".
            // For store/POS sales we still want every admin to see it, including the cashier,
            // since a single-admin shop would otherwise see zero notifications.
            // Incompleted placeholders (abandoned checkouts) are not orders yet —
            // the real notification fires when the shopper places the order.
            if ($sale->status !== 'incompleted') {
                $this->notifyAdminsOfNewSale($sale);
            }

            return $sale->load(['customer', 'branch', 'creator', 'items.product', 'allocations']);
        });
    }

    private function notifyAdminsOfNewSale(Sale $sale): void
    {
        try {
            $admins = \App\Models\User::whereHas('roles', function ($q) {
                    $q->whereIn('name', ['Super Admin', 'Manager']);
                })
                ->limit(10)
                ->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\NewSaleNotification(
                    $sale->invoice_number,
                    (float) $sale->grand_total,
                    $sale->source,
                    $sale->id,
                ));
            }
        } catch (\Throwable $e) {
            // Non-critical — don't block the sale
        }
    }

    // ── Calculate Totals ──

    public function calculateTotals(array $items, array $data): array
    {
        $vat = $this->productVatMap($items);

        $subtotal = 0;
        $taxAmount = 0;   // total VAT across lines (shown on the invoice)
        $addedTax = 0;    // exclusive VAT that is added on top of the price

        foreach ($items as $item) {
            $base = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0);
            $subtotal += $base;

            $info = $vat[$item['product_id']] ?? ['rate' => 0.0, 'inclusive' => false];
            $lineTax = $this->lineVat($base, $info['rate'], $info['inclusive']);
            $taxAmount += $lineTax;
            if (! $info['inclusive']) {
                $addedTax += $lineTax;
            }
        }

        // Calculate overall discount
        $discountType = $data['discount_type'] ?? null;
        $discountValue = (float) ($data['discount_value'] ?? 0);

        if ($discountType === 'percentage') {
            $discountAmount = round($subtotal * ($discountValue / 100), 2);
        } elseif ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        } else {
            $discountAmount = 0;
        }

        // Shipping
        $shippingCharge = (float) ($data['shipping_charge'] ?? 0);

        // Only exclusive VAT is added to the total; VAT-inclusive prices already
        // contain it, so the displayed tax_amount is informational there.
        $grandTotal = $subtotal - $discountAmount + $addedTax + $shippingCharge;

        return [
            'subtotal'        => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount'      => round($taxAmount, 2),
            'grand_total'     => round($grandTotal, 2),
        ];
    }

    /**
     * Per-product VAT info keyed by product id: ['rate' => float, 'inclusive' => bool].
     */
    private function productVatMap(array $items): array
    {
        $ids = array_filter(array_column($items, 'product_id'));

        return Product::whereIn('id', $ids)
            ->get(['id', 'vat_rate', 'vat_inclusive'])
            ->mapWithKeys(fn ($p) => [$p->id => [
                'rate'      => (float) $p->vat_rate,
                'inclusive' => (bool) $p->vat_inclusive,
            ]])
            ->all();
    }

    /**
     * VAT for one line. Extracted from the price when inclusive, else added on top.
     */
    private function lineVat(float $base, float $rate, bool $inclusive): float
    {
        if ($rate <= 0 || $base <= 0) {
            return 0.0;
        }

        return $inclusive
            ? round($base * $rate / (100 + $rate), 2)
            : round($base * $rate / 100, 2);
    }

    // ── Update Sale ──

    public function updateSale(Sale $sale, array $data, array $items, array $payments = []): Sale
    {
        return DB::transaction(function () use ($sale, $data, $items, $payments) {
            $saleDate = $data['invoice_date'] ?? $data['sale_date'] ?? $sale->sale_date;

            // Captured before the update() below overwrites them — needed to
            // detect whether this edit actually changed the address (see
            // syncCustomerAddressFromSale()).
            $originalCustomerId = $sale->customer_id;
            $originalBillingAddress = $sale->billing_address;
            $originalCustomerAddress = $sale->customer_address;

            // Normalize items for calculateTotals (product_id is required for the
            // per-line VAT lookup in calculateTotals()).
            $normalizedItems = array_map(function ($item) {
                return [
                    'product_id'      => $item['product_id'],
                    'quantity'        => $item['quantity'],
                    'unit_price'      => $item['price'] ?? $item['unit_price'] ?? 0,
                    'discount_amount' => $item['discount'] ?? $item['discount_amount'] ?? 0,
                ];
            }, $items);

            $totals = $this->calculateTotals($normalizedItems, $data);

            $sale->update([
                'customer_id'            => $data['customer_id'] ?? $sale->customer_id,
                'customer_name_snapshot' => $data['customer_name_snapshot'] ?? $sale->customer_name_snapshot,
                'customer_phone_snapshot'=> $data['customer_phone_snapshot'] ?? $sale->customer_phone_snapshot,
                'customer_address'       => $data['customer_address'] ?? $sale->customer_address,
                'billing_address'        => $data['billing_address'] ?? $sale->billing_address,
                'branch_id'              => $data['branch_id'] ?? $sale->branch_id,
                'sale_date'              => $saleDate,
                'due_date'               => $data['due_date'] ?? null,
                'price_type'             => $data['price_type'] ?? $sale->price_type ?? 'regular',
                'status'                 => $data['sale_status'] ?? $sale->status,
                'discount_type'          => $data['discount_type'] ?? null,
                'discount_value'         => $data['discount_value'] ?? 0,
                'discount_amount'        => $totals['discount_amount'],
                'tax_rate'               => $data['tax_rate'] ?? 0,
                'tax_amount'             => $totals['tax_amount'],
                'shipping_charge'        => $data['shipping_charge'] ?? 0,
                'subtotal'               => $totals['subtotal'],
                'grand_total'            => $totals['grand_total'],
                'notes'                  => $data['notes'] ?? $sale->notes,
                'item_description'       => $data['item_description'] ?? $sale->item_description,
                'staff_note'             => $data['staff_note'] ?? $sale->staff_note,
            ]);

            $this->syncCustomerAddressFromSale(
                $data['customer_id'] ?? $originalCustomerId,
                $data,
                $originalBillingAddress,
                $originalCustomerAddress
            );

            // Reverse any previously-deducted stock for the OLD items before
            // replacing them. Guarded on the ledger (not the status name) so it
            // covers every workflow status and never double-reverses. Skipped
            // for return/exchange statuses, which the SaleReturn module owns.
            $newStatus = $data['sale_status'] ?? $data['status'] ?? $sale->status;
            $neutralStatus = in_array($newStatus, self::STOCK_NEUTRAL_STATUSES, true);

            // Skip the reverse + re-deduct cycle entirely when the edit does
            // not change what's physically out: same product/variant/qty
            // composition and the stock is already in the desired state. An
            // edit that only touches prices, discount, notes, status label,
            // etc. must not spam the stock ledger with a reversal pair per
            // line on every save.
            $sale->loadMissing('items');
            $shouldBeOut = ! $neutralStatus && in_array($newStatus, $this->stockOutStatusesFor($sale), true);
            $skipStockChurn = $shouldBeOut
                && $this->isStockDeducted($sale)
                && $this->stockFingerprint($sale->items->all()) === $this->stockFingerprint($items);

            if (! $neutralStatus && ! $skipStockChurn && $this->isStockDeducted($sale)) {
                $this->reverseStockForSale($sale);
            }

            // Replace line items
            $sale->items()->delete();

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                $variantId = $item['variant_id'] ?? null;
                $variant = $variantId ? ProductVariant::with('attributeValues.attribute')->find($variantId) : null;

                $qty = $item['quantity'];
                $unitPrice = $item['price'] ?? $item['unit_price'] ?? 0;
                $discount = $item['discount'] ?? $item['discount_amount'] ?? 0;
                $itemSubtotal = ($qty * $unitPrice) - $discount;

                // Variant-aware name/SKU snapshot (mirrors createSale()).
                $itemName = $product ? $product->name : 'Unknown';
                $itemSku  = $product ? $product->sku : '';
                if ($variant) {
                    $itemName .= ' — ' . $variant->variant_name;
                    $itemSku = $variant->sku;
                }

                SaleItem::create([
                    'sale_id'         => $sale->id,
                    'product_id'      => $item['product_id'],
                    'variant_id'      => $variantId,
                    'variant_label'   => $variant ? $variant->variant_name : ($item['variant_label'] ?? null),
                    'product_name'    => $itemName,
                    'product_sku'     => $itemSku,
                    'quantity'        => $qty,
                    'batch_no'        => $item['batch_no'] ?? null,
                    'expired_date'    => $item['expired_date'] ?? null,
                    'unit_price'      => $unitPrice,
                    'discount_amount' => $discount,
                    'tax_amount'      => $item['tax'] ?? $item['tax_amount'] ?? 0,
                    'subtotal'        => $itemSubtotal,
                    // Combo provenance, preserved across edits.
                    'combo_id'        => $item['combo_id'] ?? null,
                    'combo_group'     => $item['combo_group'] ?? null,
                    'combo_name'      => $item['combo_name'] ?? null,
                    'combo_price'     => $item['combo_price'] ?? null,
                ]);
            }

            // Re-deduct for the NEW items when the sale is in a stock-out
            // status for its channel (counter: confirmed+; online: courier+).
            // Matches changeStatus() so the Edit page moves stock like the
            // status dropdown does.
            if (! $skipStockChurn && $shouldBeOut) {
                $this->deductStockForItems($sale, $items);
            }

            // Sync payments — update existing, create new, delete removed
            $this->syncPayments($sale, $payments);

            $this->updatePaymentStatus($sale);

            // Recompute the customer's total_purchased from delivered sales.
            // This also corrects edits that change a delivered sale's total —
            // which the old increment-on-confirm logic silently missed.
            $this->recomputeCustomerPurchased($sale->customer_id);

            // Replace the ledger entry so an edited total is reflected.
            $this->syncSaleJournal($sale->fresh(), recreate: true);

            return $sale->fresh()->load(['customer', 'items.product']);
        });
    }

    // ── Status Transition ──

    /**
     * Statuses that mean the goods have left inventory (stock is "out") for
     * COUNTER sales (POS / in-store / admin). These hand the product over at
     * the counter, so stock leaves as soon as the sale is confirmed.
     * Entering any of these deducts stock; leaving them for a non-out,
     * non-neutral status (e.g. cancelled) restores it.
     */
    public const STOCK_OUT_STATUSES = ['confirmed', 'packing', 'courier', 'delivered', 'partial_cancelled'];

    /**
     * Stock-out statuses for ONLINE / delivery sales (source = ecommerce).
     * These ship via courier, so stock should only leave inventory once the
     * order is actually handed to the courier — not while it sits confirmed
     * or in packing. See stockOutStatusesFor().
     */
    public const ONLINE_STOCK_OUT_STATUSES = ['courier', 'delivered'];

    /**
     * Sale sources whose fulfilment is delivery/courier-based (online orders).
     * Everything else (pos, store) is treated as a counter sale.
     */
    public const ONLINE_SOURCES = ['ecommerce', 'storefront'];

    /**
     * Statuses whose stock movement is owned by another module (the
     * SaleReturn / exchange flow). changeStatus() leaves stock untouched for
     * these so it never double-counts against the returns module.
     */
    public const STOCK_NEUTRAL_STATUSES = ['returned', 'return_received', 'exchange'];

    /**
     * Statuses that make a sale a real, countable order for the list's KPI
     * cards — placed, and neither cancelled nor an abandoned checkout. Wider
     * than revenue recognition on purpose: a parcel with the courier counts as
     * a sale made and as money owed, even though its revenue is not earned
     * until it is delivered.
     */
    public const COUNTABLE_SALE_STATUSES = ['pending', 'packing', 'courier', 'delivered'];

    /**
     * Statuses at which a courier-fulfilled order's revenue is earned.
     * Matches ONLINE_STOCK_OUT_STATUSES on purpose: revenue is recognized the
     * same moment the goods leave for the courier, not held back for actual
     * delivery confirmation — by instruction, revenue follows stock exactly
     * for these sales, the same as it already does for a POS sale.
     */
    public const ONLINE_REVENUE_STATUSES = self::ONLINE_STOCK_OUT_STATUSES;

    /**
     * Statuses at which a true POS/walk-in sale's revenue is earned. A
     * walk-in sale hands the goods over at the till, so it is earned as soon
     * as it is confirmed — POS writes 'confirmed' and never advances the
     * status, so waiting for 'delivered' would keep POS takings out of the
     * ledger entirely. Mirrors STOCK_OUT_STATUSES: revenue follows the goods.
     */
    public const COUNTER_REVENUE_STATUSES = self::STOCK_OUT_STATUSES;

    /**
     * Revenue-recognition statuses for a sale. Only a true POS terminal sale
     * (source = 'pos') hands goods over immediately at the till — everything
     * else (admin-created 'store' sales included) earns revenue once it's
     * actually shipped to a courier, same timing as stockOutStatusesFor() for
     * that channel. Deliberately NOT keyed off ONLINE_SOURCES/
     * stockOutStatusesFor() directly: a 'store' sale sent to courier is a
     * courier order for revenue purposes even though its stock leaves
     * earlier, at 'confirmed' (COUNTER_STOCK_OUT_STATUSES).
     */
    public static function revenueStatusesFor(Sale $sale): array
    {
        return $sale->source === 'pos'
            ? self::COUNTER_REVENUE_STATUSES
            : self::ONLINE_REVENUE_STATUSES;
    }

    /**
     * The set of stock-out statuses that applies to a given sale, based on its
     * fulfilment channel: online/delivery sales deduct at courier; counter
     * sales (POS/store) deduct at confirmed.
     */
    private function stockOutStatusesFor(Sale $sale): array
    {
        return in_array($sale->source, self::ONLINE_SOURCES, true)
            ? self::ONLINE_STOCK_OUT_STATUSES
            : self::STOCK_OUT_STATUSES;
    }

    /**
     * Bring the sale's general-ledger entry in line with its current status.
     * Every path that can change a sale's status or totals routes through here
     * so there is one rule instead of a condition per call site — the previous
     * arrangement checked for status 'confirmed' in three places and nowhere
     * covered changeStatus(), which is how sales actually reach 'delivered'.
     *
     * Idempotent: posting twice is a no-op unless $recreate is set, which the
     * edit paths use so a changed total replaces the old entry.
     *
     * Failures are logged rather than thrown — a counter sale must not fail
     * because the ledger is misconfigured — but at error level, since a silent
     * warning is what let unposted sales go unnoticed.
     */
    private function syncSaleJournal(Sale $sale, bool $recreate = false): void
    {
        // A return or exchange posts its own offsetting entry, so the sale's
        // original journal has to stay put; voiding it here would reverse the
        // revenue twice.
        if (in_array($sale->status, self::STOCK_NEUTRAL_STATUSES, true)) {
            return;
        }

        $shouldPost = in_array($sale->status, self::revenueStatusesFor($sale), true);
        $isPosted = $this->accountingService->hasPostedJournal('sale', $sale->id);

        if (! $shouldPost && ! $isPosted) {
            return;
        }

        try {
            if ($shouldPost && $isPosted && ! $recreate) {
                return;
            }

            if ($isPosted) {
                $this->accountingService->voidJournalEntry('sale', $sale->id);
            }

            if ($shouldPost) {
                $this->accountingService->recordSale($sale);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to sync sale journal entry', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'status'  => $sale->status,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Maps an admin sale status onto the matching online-order status, so an
     * ecommerce-sourced sale managed from the web admin keeps its mirrored
     * EcommerceOrder in sync. Inverse of EcommerceService::ORDER_TO_SALE_STATUS.
     */
    private const SALE_TO_ORDER_STATUS = [
        'pending'   => 'pending',
        'confirmed' => 'confirmed',
        'packing'   => 'processing',
        'courier'   => 'shipped',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
    ];

    /**
     * Change a sale's status and move stock to match — idempotently.
     *
     * This is the single entry point for status-only changes (admin row
     * dropdown, bulk update, ecommerce order sync). Stock is deducted the
     * first time the sale enters a "stock-out" status and restored the first
     * time it leaves one for cancelled/pending/etc. The stock ledger is the
     * guard, so repeated or forward transitions (packing → courier →
     * delivered) never deduct twice.
     */
    public function changeStatus(Sale $sale, string $newStatus): Sale
    {
        return DB::transaction(function () use ($sale, $newStatus) {
            $sale->update(['status' => $newStatus]);

            // Keep the customer's total_purchased in step with the new status.
            // This is what finally counts ONLINE orders, which reach 'delivered'
            // via this path without ever passing through 'confirmed'; it also
            // drops a sale from the total when it leaves the active set
            // (cancelled / returned). Runs for every transition, including the
            // stock-neutral early return below.
            $this->recomputeCustomerPurchased($sale->customer_id);

            // Returns/exchange own their own stock movements.
            if (in_array($newStatus, self::STOCK_NEUTRAL_STATUSES, true)) {
                return $sale->fresh();
            }

            $this->syncStockState($sale, in_array($newStatus, $this->stockOutStatusesFor($sale), true));

            // Revenue recognition. This is the path sales actually travel to
            // reach 'delivered', so it has to post — and it runs after the
            // stock sync above so the cost of goods is read from a settled
            // ledger. Moving back off a delivered status un-posts it.
            $this->syncSaleJournal($sale);

            // Ecommerce-sourced sales: mirror the status back to the online
            // order (and report the Refund conversion on cancellation).
            if ($sale->source === 'ecommerce') {
                $this->syncEcommerceOrder($sale, $newStatus);
            }

            return $sale->fresh();
        });
    }

    /**
     * Keep an ecommerce order in sync with its mirrored sale. The Purchase
     * conversion is reported at order placement (checkout / AI paths) — status
     * changes here only mirror state and, on cancellation, report the Refund.
     *
     * Resolved lazily via the container (not constructor-injected) because
     * EcommerceService already depends on SaleService — injecting the other
     * way would create a circular dependency. reportRefund is once-guarded
     * (refund_reported_at), so multiple paths never double-report.
     */
    private function syncEcommerceOrder(Sale $sale, string $newStatus): void
    {
        $order = \Modules\Ecommerce\Models\EcommerceOrder::where('sale_id', $sale->id)->first();
        if (! $order) {
            return;
        }

        $orderStatus = self::SALE_TO_ORDER_STATUS[$newStatus] ?? null;
        if ($orderStatus && $order->status !== $orderStatus) {
            $order->update(['status' => $orderStatus]);
        }

        // Reverse: report the Refund conversion when a previously-reported
        // purchase is cancelled/refunded. Mirrors EcommerceService's reverse
        // set. This is the path that actually catches courier RTO/cancellation
        // for the admin workflow, since Steadfast webhooks and admin status
        // changes both route through changeStatus()/cancelSale() above.
        // reportRefund is once-guarded (refund_reported_at), so wiring it here
        // as well as from the mobile API path never double-reports.
        if (in_array($orderStatus, ['cancelled', 'refunded'], true)) {
            try {
                app(\Modules\Ecommerce\Services\TrackingService::class)->reportRefund($order->fresh());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    "Refund tracking failed {$order->order_number}: {$e->getMessage()}"
                );
            }
        }
    }

    /**
     * Bring a sale's stock to the desired state (out or restored) exactly
     * once. Uses the net of 'sale' (-) and 'sale_reversal' (+) ledger entries
     * for this sale as the source of truth: net < 0 means stock is currently
     * deducted. Guarantees stock is never deducted — or reversed — twice.
     */
    private function syncStockState(Sale $sale, bool $shouldBeOut): void
    {
        $currentlyOut = $this->isStockDeducted($sale);

        if ($shouldBeOut && ! $currentlyOut) {
            $sale->loadMissing('items');
            $items = $sale->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'variant_id' => $i->variant_id,
                'quantity'   => $i->quantity,
                'unit_price' => $i->unit_price,
            ])->all();
            $this->deductStockForItems($sale, $items);
        } elseif (! $shouldBeOut && $currentlyOut) {
            $this->reverseStockForSale($sale);
        }
    }

    /**
     * Whether the sale's stock is currently deducted (goods out). Uses the net
     * of 'sale' (-) and 'sale_reversal' (+) ledger entries: net < 0 means out.
     */
    private function isStockDeducted(Sale $sale): bool
    {
        $net = (int) StockLedger::whereIn('source_type', ['sale', 'sale_reversal'])
            ->where('source_id', $sale->id)
            ->sum('quantity_change');

        return $net < 0;
    }

    /**
     * The payment account an advance lands in when the user doesn't pick one:
     * the account flagged default, else the first active Cash account.
     */
    private function defaultPaymentAccountId(): ?int
    {
        return \Modules\Payment\Models\PaymentAccount::where('is_active', true)
            ->where('is_default', true)
            ->value('id')
            ?? \Modules\Payment\Models\PaymentAccount::where('is_active', true)
                ->where('account_type', 'cash')
                ->value('id');
    }

    /**
     * Sync payment records for a sale — update existing, create new, delete removed.
     */
    private function syncPayments(Sale $sale, array $payments): void
    {
        $existingPaymentIds = $sale->allocations()
            ->where('allocatable_type', Sale::class)
            ->pluck('payment_id')
            ->toArray();

        $submittedIds = [];

        foreach ($payments as $paymentData) {
            $amount = (float) ($paymentData['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $existingId = !empty($paymentData['id']) ? (int) $paymentData['id'] : null;

            if ($existingId && in_array($existingId, $existingPaymentIds)) {
                // Update existing payment
                $payment = \Modules\Payment\Models\Payment::find($existingId);
                if ($payment) {
                    // An empty select on the edit form means "unchanged" — keep
                    // the payment's account (it may be soft-deleted and thus
                    // absent from the dropdown) rather than stripping it.
                    $syncAccountId = ($paymentData['payment_account_id'] ?? null)
                        ?: $payment->payment_account_id;
                    $syncMethod = $paymentData['method'] ?? null;
                    if (!$syncMethod && $syncAccountId) {
                        $syncMethod = \Modules\Payment\Models\PaymentAccount::withTrashed()
                            ->where('id', $syncAccountId)->value('account_type') ?? 'cash';
                    }
                    $newDate = $paymentData['date'] ?? $sale->sale_date;

                    // The posted journal was built from the old amount/account/
                    // date — if any of those actually change, it has to be
                    // rebuilt or the GL (and everything reading it: cash flow,
                    // account balances) keeps the stale figures forever.
                    $journalStale = abs((float) $payment->amount - $amount) > 0.001
                        || (int) $payment->payment_account_id !== (int) ($syncAccountId ?: 0)
                        || \Carbon\Carbon::parse($payment->payment_date)->ne(\Carbon\Carbon::parse($newDate));

                    $payment->update([
                        'amount'             => $amount,
                        'payment_method'     => $syncMethod ?: $payment->payment_method,
                        'payment_account_id' => $syncAccountId ?: null,
                        'payment_date'       => $newDate,
                        'reference'          => $paymentData['reference'] ?? $payment->reference,
                        'note'               => $paymentData['note'] ?? null,
                    ]);

                    // Update allocation amount
                    $payment->allocations()
                        ->where('allocatable_type', Sale::class)
                        ->where('allocatable_id', $sale->id)
                        ->update(['amount' => $amount]);

                    if ($journalStale) {
                        $this->paymentService->resyncJournal($payment);
                    }

                    $submittedIds[] = $existingId;
                }
            } else {
                // Create new payment
                $newAccountId = ($paymentData['payment_account_id'] ?? null) ?: $this->defaultPaymentAccountId();
                $newMethod = $paymentData['method'] ?? null;
                if (!$newMethod && $newAccountId) {
                    $newMethod = \Modules\Payment\Models\PaymentAccount::where('id', $newAccountId)->value('account_type') ?? 'cash';
                }
                $newPayment = $this->paymentService->create([
                    'direction'          => 'receive',
                    'party_type'         => $sale->customer_id ? 'customer' : null,
                    'party_id'           => $sale->customer_id,
                    'payment_type'       => 'sale_payment',
                    'amount'             => $amount,
                    'payment_method'     => $newMethod,
                    'payment_account_id' => $newAccountId ?: null,
                    'payment_date'       => $paymentData['date'] ?? $sale->sale_date,
                    'reference'          => $paymentData['reference'] ?? $sale->invoice_number,
                    'note'               => $paymentData['note'] ?? null,
                ], [
                    [
                        'allocatable_type' => Sale::class,
                        'allocatable_id'   => $sale->id,
                        'amount'           => $amount,
                    ],
                ]);

                $submittedIds[] = $newPayment->id;
            }
        }

        // Delete payments that were removed from the form
        $toDelete = array_diff($existingPaymentIds, $submittedIds);
        if (!empty($toDelete)) {
            foreach ($toDelete as $paymentId) {
                $payment = \Modules\Payment\Models\Payment::find($paymentId);
                if ($payment) {
                    // Void before deleting. The payment row is soft-deleted, so
                    // Payment Accounts stops counting it immediately, but the GL
                    // keeps a posted journal unless it is reversed — and the two
                    // then disagree by the payment's amount, permanently.
                    // Deliberately not wrapped in try/catch: a void that fails
                    // must roll the whole sale update back rather than leave an
                    // orphaned entry behind.
                    if ($payment->journal_entry_id) {
                        $this->accountingService->voidJournalEntry('payment', $payment->id);
                    }
                    $payment->allocations()->delete();
                    $payment->delete();
                }
            }
        }

        // Recalculate sale paid/due from allocations. Customer total_paid/due are
        // derived live from sales (no denormalized column), so nothing to write here.
        $this->updatePaymentStatus($sale);
    }

    // ── Update Payment Status ──

    public function updatePaymentStatus(Sale $sale): void
    {
        // A sale is paid from two independent sources: payments recorded in
        // the Payment module (advance/partial — live in allocations) and cash
        // the courier collected on delivery (COD — courier_collected_amount,
        // which by design has no Payment record). Sum both so recalculating
        // from one source never erases the other.
        $paidAmount = (float) $sale->allocations()->sum('amount')
            + (float) ($sale->courier_collected_amount ?? 0);

        $sale->paid_amount = $paidAmount;
        $sale->due_amount = $sale->grand_total - $paidAmount - (float) $sale->due_discount_amount;

        if ($sale->due_amount <= 0) {
            $sale->payment_status = 'paid';
            $sale->due_amount = 0;
        } elseif ($paidAmount > 0) {
            $sale->payment_status = 'partial';
        } else {
            $sale->payment_status = 'unpaid';
        }

        $sale->save();
    }

    /**
     * When the courier settles a delivered COD parcel for less than the sale
     * total (door-step bargain — Steadfast reports it as "Amount has been
     * changed from 1400 to 1130"), the gap is a price concession the business
     * accepted, not money still owed. Record it as a fixed discount so the
     * sale closes at what was actually collected instead of carrying a
     * phantom customer due.
     *
     * Guards: full deliveries only (a partial delivery returns goods and is
     * settled by a SaleReturn), and only when the courier collected something.
     * Recomputing the shortfall from current totals makes webhook retries
     * idempotent — once the discount is applied the shortfall is zero.
     */
    public function settleCodShortfallAsDiscount(Sale $sale): void
    {
        $collected = (float) ($sale->courier_collected_amount ?? 0);
        if ($sale->status !== 'delivered' || $collected <= 0) {
            return;
        }

        $allocated = (float) $sale->allocations()->sum('amount');
        $shortfall = round((float) $sale->grand_total - $allocated - $collected, 2);
        if ($shortfall < 0.01) {
            return;
        }

        // 'fixed' keeps discount_value === discount_amount — the same math
        // calculateTotals() uses, so a later edit won't undo the settlement.
        $sale->discount_type = 'fixed';
        $sale->discount_value = (float) $sale->discount_amount + $shortfall;
        $sale->discount_amount = (float) $sale->discount_amount + $shortfall;
        $sale->grand_total = (float) $sale->grand_total - $shortfall;
        $sale->staff_note = trim(($sale->staff_note ? $sale->staff_note . "\n" : '')
            . 'COD settlement: ' . number_format($shortfall, 2) . ' shortfall auto-recorded as discount.');
        $sale->save();

        $this->updatePaymentStatus($sale);
        $this->recomputeCustomerPurchased($sale->customer_id);

        // Books must reflect the settled price, not the original one.
        $this->syncSaleJournal($sale, recreate: true);
    }

    // ── Cancel Sale ──

    public function cancelSale(Sale $sale): void
    {
        $this->assertSaleCanBeReversed($sale, 'cancel');

        DB::transaction(function () use ($sale) {
            $this->reverseSaleEffects($sale);

            $sale->status = 'cancelled';
            $sale->save();

            // Mirror the cancellation to the linked online order so the
            // customer's panel reflects it (otherwise it stays "pending").
            if ($sale->source === 'ecommerce') {
                $this->syncEcommerceOrder($sale, 'cancelled');
            }
        });
    }

    /**
     * There is no separate "edit customer address" flow from the order
     * screen — staff correct it right on the sale. Write the change back to
     * the customer's profile so it sticks for future orders too. Only fires
     * when this edit actually changed the field: the textarea always
     * resubmits its current value even when the "Edit address" panel was
     * never opened, and that must not clobber an address changed elsewhere
     * since this sale was created.
     */
    private function syncCustomerAddressFromSale(?int $customerId, array $data, ?string $originalBillingAddress, ?string $originalCustomerAddress): void
    {
        if (! $customerId) {
            return;
        }

        $update = [];

        if (array_key_exists('billing_address', $data) && $data['billing_address'] !== $originalBillingAddress) {
            $update['address'] = $data['billing_address'];
        }

        if (array_key_exists('customer_address', $data) && $data['customer_address'] !== $originalCustomerAddress) {
            $update['shipping_address'] = $data['customer_address'];
        }

        if ($update) {
            Customer::whereKey($customerId)->update($update);
        }
    }

    /**
     * Soft-delete a sale: reverse its stock and financial effects (the same as
     * a cancellation) then soft-delete the record so it leaves the list
     * entirely while remaining recoverable. Use this for the "Delete" action;
     * use cancelSale() to keep the record with a 'cancelled' status.
     */
    public function deleteSale(Sale $sale): void
    {
        $this->assertSaleCanBeReversed($sale, 'delete');

        DB::transaction(function () use ($sale) {
            $this->reverseSaleEffects($sale);

            // Mirror the delete to the linked online order so it stops showing
            // (as "pending") in the customer's My Orders panel, which reads the
            // EcommerceOrder directly. Soft delete keeps it recoverable and is a
            // no-op when the sale has no online order.
            $sale->ecommerceOrder()->delete();

            $sale->delete(); // soft delete (SoftDeletes trait)
        });
    }

    /**
     * A sale cannot be cancelled or deleted while it still has active returns.
     */
    private function assertSaleCanBeReversed(Sale $sale, string $action): void
    {
        $activeReturns = \Modules\SaleReturn\Models\SaleReturn::where('sale_id', $sale->id)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        if ($activeReturns > 0) {
            throw new \RuntimeException("Cannot {$action} this sale — {$activeReturns} active return(s) exist. Cancel the return(s) first.");
        }
    }

    /**
     * Recompute and store a customer's total_purchased from the authoritative
     * live data: the sum of their delivered sales' grand_total (the recognised-
     * purchase rule lives in Customer::PURCHASED_STATUSES). Because grand_total
     * is kept net of returns (SaleReturn reduces it in place without changing
     * status), this single figure stays correct through status changes, edits,
     * returns, cancellations and deletions. It replaces the old scattered,
     * asymmetric increment/decrement logic and self-heals any prior drift, and
     * keeps the stored column in step with the Customer accessor. (total_paid /
     * due are derived live by the accessor, so they are not touched here.)
     *
     * Pass $excludeSaleId when reversing a sale that is still in a counted
     * status at call time (cancel/delete reverse effects before the status flip
     * / soft-delete lands).
     */
    private function recomputeCustomerPurchased(?int $customerId, ?int $excludeSaleId = null): void
    {
        if (! $customerId) {
            return;
        }

        $total = Sale::where('customer_id', $customerId)
            ->whereIn('status', \Modules\Customer\Models\Customer::PURCHASED_STATUSES)
            ->when($excludeSaleId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->sum('grand_total');

        DB::table('customers')
            ->where('id', $customerId)
            ->update(['total_purchased' => $total]);
    }

    /**
     * Reverse a sale's side-effects: restore stock, roll back the customer's
     * running total, remove its payments/allocations, and void its journal
     * entry. Shared by cancelSale() and deleteSale(); must run in a transaction.
     */
    private function reverseSaleEffects(Sale $sale): void
    {
        // Reverse any stock this sale currently has out. reverseStockForSale
        // self-guards on the ledger, so this is a no-op when nothing was
        // deducted (e.g. an online order cancelled before it shipped) and
        // correctly restores stock for any stock-out status (confirmed for
        // counter sales, courier/delivered for online), not just 'confirmed'.
        $sale->load('items');
        $this->reverseStockForSale($sale);

        // Roll back the customer's running total. Exclude this sale, which is
        // still in an active status here — the caller flips it to 'cancelled'
        // or soft-deletes it immediately after.
        $this->recomputeCustomerPurchased($sale->customer_id, $sale->id);

        // Drop this sale's payment allocations, then remove any payment left with
        // no allocations — voiding its own receipt journal entry (DR Cash / CR AR)
        // so the ledger stays balanced. A payment split across several documents
        // keeps its other links (and its journal entry) intact.
        $paymentIds = $sale->allocations()->pluck('payment_id')->unique();
        $sale->allocations()->delete();

        foreach ($paymentIds as $paymentId) {
            $payment = \Modules\Payment\Models\Payment::find($paymentId);
            if ($payment && $payment->allocations()->count() === 0) {
                if ($payment->journal_entry_id) {
                    try {
                        $this->accountingService->voidJournalEntry('payment', $payment->id);
                    } catch (\Throwable $e) {
                        \Log::warning("Failed to void journal entry for payment on sale reversal: {$e->getMessage()}");
                    }
                }
                $payment->delete();
            }
        }

        // Void the accounting journal entry if exists
        try {
            $this->accountingService->voidJournalEntry('sale', $sale->id);
        } catch (\Throwable $e) {
            \Log::warning("Failed to void journal entry for sale {$sale->invoice_number}: {$e->getMessage()}");
        }
    }

    // ── Stats ──

    /**
     * Fixed KPI cards for the sales list — always today/this-month scoped,
     * never affected by the list's search/status/date filters. The filtered
     * view gets its own numbers via getListTotals() (the calculation row).
     */
    public function getStats(): array
    {
        // Count the whole active pipeline, not just delivered. Recognising only
        // 'delivered' here made these cards disagree with the due card beside
        // them: an order out with the courier was money owed but not a sale.
        // This is deliberately NOT the revenue-recognition rule — a dispatched
        // order is a sale the shop has made even before the revenue is earned.
        $base = Sale::query()->whereIn('status', self::COUNTABLE_SALE_STATUSES);

        // Money is owed as soon as goods are committed (stock out) — a COD
        // parcel with the courier is a receivable, not only after delivery.
        $dueBase = Sale::query()->whereIn('status', self::STOCK_OUT_STATUSES)
            ->where('payment_status', '!=', 'paid');

        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];

        return [
            'today_sales'       => (clone $base)->whereDate('sale_date', today())->sum('grand_total'),
            'month_sales'       => (clone $base)->whereBetween('sale_date', $thisMonth)->sum('grand_total'),
            // Due and count are month-scoped like the This Month card — the
            // list labels them "This Month Total Due" / "This Month Total Sales".
            'total_due'         => (clone $dueBase)->whereBetween('sale_date', $thisMonth)->sum('due_amount'),
            'total_sales_count' => (clone $base)->whereBetween('sale_date', $thisMonth)->count(),
        ];
    }

    /**
     * Get sale counts per status for the filter tabs.
     */
    public function getStatusCounts(): array
    {
        $statuses = ['pending', 'packing', 'courier', 'delivered', 'partial_cancelled', 'cancelled', 'return_received', 'returned', 'draft', 'on_hold', 'exchange', 'incompleted'];

        $counts = Sale::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $result = [];
        foreach ($statuses as $status) {
            $result[$status] = $counts[$status] ?? 0;
        }
        $result['all'] = array_sum($counts);

        return $result;
    }

    // ── Helpers ──

    // ── Inventory Helpers ──

    /**
     * The stock-relevant composition of a set of sale items: quantity per
     * product+variant, order-independent. Two item sets with equal
     * fingerprints move exactly the same physical stock, so an edit between
     * them needs no ledger churn. Accepts SaleItem models or form arrays.
     */
    private function stockFingerprint(array $items): array
    {
        $agg = [];
        foreach ($items as $item) {
            $get = fn ($key) => is_array($item) ? ($item[$key] ?? null) : ($item->{$key} ?? null);
            $qty = (int) $get('quantity');
            if ($qty <= 0) {
                continue;
            }
            $key = (int) $get('product_id') . ':' . (int) ($get('variant_id') ?: 0);
            $agg[$key] = ($agg[$key] ?? 0) + $qty;
        }
        ksort($agg);

        return $agg;
    }

    /**
     * Deduct stock for each sale item via InventoryService.
     *
     * Idempotent: never deducts twice for the same sale. The stock ledger (net
     * of 'sale' / 'sale_reversal' entries) is the source of truth — if this
     * sale's stock is already out, this is a no-op. A row lock on the sale
     * serialises concurrent callers (double form submit, retried request, a
     * racing status change) so two of them cannot both pass the guard and
     * double-deduct.
     */
    private function deductStockForItems(Sale $sale, array $items): void
    {
        Sale::whereKey($sale->id)->lockForUpdate()->first();

        if ($this->isStockDeducted($sale)) {
            return;
        }

        foreach ($items as $item) {
            $qty = (int) $item['quantity'];
            if ($qty <= 0) {
                continue;
            }

            // Skip stock deduction for service-type products
            $product = \Modules\Product\Models\Product::find($item['product_id']);
            if ($product && $product->product_type === 'service') {
                continue;
            }

            try {
                $this->inventoryService->adjustStock(
                    (int) $item['product_id'],
                    isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                    -$qty,
                    'sale',
                    $sale->id,
                    // Items arrive as 'unit_price' (create/status paths) or
                    // 'price' (raw edit-form rows via updateSale) — accept both
                    // so edit-triggered re-deductions don't log a 0 cost.
                    (float) ($item['unit_price'] ?? $item['price'] ?? 0),
                    "Sale: {$sale->invoice_number}"
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to deduct stock for sale item', [
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity'   => $qty,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reverse (add back) stock for all items of a sale.
     *
     * Idempotent: only reverses stock that is currently out (per the ledger),
     * so a double cancel / repeated call can't add phantom stock back. Same
     * row lock as deduction serialises concurrent reversals.
     */
    private function reverseStockForSale(Sale $sale): void
    {
        Sale::whereKey($sale->id)->lockForUpdate()->first();

        if (! $this->isStockDeducted($sale)) {
            return;
        }

        $sale->loadMissing('items');

        foreach ($sale->items as $item) {
            $qty = (int) $item->quantity;
            if ($qty <= 0) {
                continue;
            }

            // Skip stock reversal for service-type products
            $product = \Modules\Product\Models\Product::find($item->product_id);
            if ($product && $product->product_type === 'service') {
                continue;
            }

            try {
                $this->inventoryService->adjustStock(
                    $item->product_id,
                    $item->variant_id,
                    $qty,
                    'sale_reversal',
                    $sale->id,
                    (float) $item->unit_price,
                    "Sale reversal: {$sale->invoice_number}"
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to reverse stock for sale item', [
                    'sale_id'    => $sale->id,
                    'product_id' => $item->product_id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    // ── Helpers ──

    private function generateInvoiceNumber(string $saleDate): string
    {
        $date = \Carbon\Carbon::parse($saleDate)->format('Ymd');
        $prefix = 'S' . $date;

        $last = Sale::withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextSeq = 1;
        if ($last) {
            $nextSeq = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
    }
}
