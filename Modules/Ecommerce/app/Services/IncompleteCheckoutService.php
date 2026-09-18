<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Models\IncompleteCheckout;
use Modules\Sale\Models\Sale;
use Modules\Sale\Services\SaleService;

/**
 * Abandoned-checkout capture. When a shopper fills in their contact details
 * on the checkout page, the info + cart is stored and mirrored as a Sale with
 * status 'incompleted' so staff see it in the sales list's Incompleted tab and
 * can follow up. A customer record is created/linked from the name + phone
 * (idempotent by phone), but no stock moves and no journal/customer totals are
 * touched — 'incompleted' is excluded from all totals. Placing the order
 * converts the capture and removes the placeholder —
 * the real pending sale comes from the normal order flow, so nothing is
 * duplicated. Everything here is best-effort: callers swallow failures so
 * this feature can never break checkout.
 */
class IncompleteCheckoutService
{
    public function __construct(
        protected SaleService $saleService,
        protected StorefrontService $storefrontService,
        protected \Modules\Customer\Services\CustomerService $customerService,
    ) {}

    /**
     * Store/refresh an abandoned-checkout capture, keyed by the checkout
     * page's one-time idempotency token and deduped by phone across page
     * reloads (each render mints a new token).
     */
    public function capture(string $token, array $contact, array $cart): ?IncompleteCheckout
    {
        $phone = trim((string) ($contact['customer_phone'] ?? ''));
        if ($token === '' || $phone === '' || empty($cart)) {
            return null;
        }

        // The order was already placed with this token (stray debounced
        // request arriving after Place Order) — nothing to capture.
        if (DB::table('checkout_idempotency_keys')->where('token', $token)->exists()) {
            return null;
        }

        // A late debounced capture can arrive AFTER the shopper placed the
        // order (and convert() already cleared the placeholder). If an order
        // was just placed for this phone — a converted capture exists — do not
        // resurrect a fresh 'incompleted' placeholder for it. Phone matched
        // digits-only so a reformat between calls still counts as the same
        // shopper.
        $digits = preg_replace('/\D+/', '', $phone);
        $recentlyConverted = IncompleteCheckout::whereNotNull('converted_order_id')
            ->where(function ($q) use ($phone, $digits) {
                $q->where('customer_phone', $phone);
                if ($digits !== '') {
                    $q->orWhereRaw("REGEXP_REPLACE(customer_phone, '[^0-9]+', '') = ?", [$digits]);
                }
            })
            ->where('updated_at', '>=', now()->subMinutes(30))
            ->exists();
        if ($recentlyConverted) {
            return null;
        }

        return DB::transaction(function () use ($token, $contact, $cart, $phone) {
            $capture = IncompleteCheckout::unconverted()
                ->where(fn ($q) => $q->where('token', $token)->orWhere('customer_phone', $phone))
                ->orderByRaw('token = ? desc', [$token])
                ->first();

            $attributes = [
                'token'            => $token,
                'customer_name'    => trim((string) ($contact['customer_name'] ?? '')),
                'customer_phone'   => $phone,
                'customer_email'   => $contact['customer_email'] ?? null,
                'address'          => $contact['address'] ?? null,
                'district_id'      => $contact['district_id'] ?? null,
                'shipping_zone_id' => $contact['shipping_zone_id'] ?? null,
                'cart'             => $cart,
            ];

            if ($capture) {
                $capture->update($attributes);
            } else {
                $capture = IncompleteCheckout::create($attributes);
            }

            $this->mirrorSale($capture);

            return $capture;
        });
    }

    /**
     * The order was placed — stamp the capture and remove its placeholder
     * sale so the shopper's entry moves from Incompleted to Pending without
     * a duplicate. Matched by token first, then phone (token rotates when
     * the checkout page is re-rendered).
     */
    public function convert(string $token, string $phone, EcommerceOrder $order): void
    {
        $phone  = trim($phone);
        $digits = preg_replace('/\D+/', '', $phone);

        // Sweep EVERY unconverted capture for this shopper — matched by token
        // OR phone (digits-only, so "01712-345678" and "01712345678" resolve to
        // the same shopper the way findOrCreateByPhone does). Handles a phone
        // reformatted between capture and placement, and clears any duplicate
        // placeholders left by a re-rendered checkout page.
        $captures = IncompleteCheckout::unconverted()
            ->where(function ($q) use ($token, $phone, $digits) {
                $q->where('token', $token);
                if ($phone !== '') {
                    $q->orWhere('customer_phone', $phone);
                    if ($digits !== '') {
                        $q->orWhereRaw("REGEXP_REPLACE(customer_phone, '[^0-9]+', '') = ?", [$digits]);
                    }
                }
            })
            ->get();

        foreach ($captures as $capture) {
            $capture->update(['converted_order_id' => $order->id]);
            $this->removePlaceholderSale($capture);
        }
    }

    /**
     * Create or refresh the 'incompleted' Sale mirroring this capture. If an
     * admin already moved the placeholder to another status, they own it —
     * stop mirroring so their workflow is never overwritten.
     */
    private function mirrorSale(IncompleteCheckout $capture): void
    {
        $saleItems = $this->storefrontService->cartToSaleItems($capture->cart ?? []);
        if (empty($saleItems)) {
            return;
        }

        // Create/link a customer from the captured name + phone so abandoned
        // checkouts still appear in the customer list. findOrCreateByPhone is
        // idempotent (matches by phone, never duplicates), and the mirrored
        // sale stays 'incompleted' — a status excluded from purchase totals —
        // so the customer's spend is not inflated by an unplaced order.
        $customer = $this->customerService->findOrCreateByPhone([
            'name'    => $capture->customer_name,
            'phone'   => $capture->customer_phone,
            'email'   => $capture->customer_email,
            'address' => $capture->address,
        ]);

        $saleData = [
            'customer_id'             => $customer?->id,
            'customer_name_snapshot'  => $customer ? null : $capture->customer_name,
            'customer_phone_snapshot' => $customer ? null : $capture->customer_phone,
            'customer_address'        => $capture->address,
            'sale_date'               => now()->toDateString(),
            'source'                  => 'ecommerce',
            'sale_status'             => 'incompleted',
            'notes'                   => __('Incomplete checkout — customer did not place the order.'),
        ];

        $sale = $capture->sale_id ? Sale::find($capture->sale_id) : null;

        if ($sale && $sale->status !== 'incompleted') {
            return;
        }

        if ($sale) {
            $this->saleService->updateSale($sale, $saleData, $saleItems);

            return;
        }

        $sale = $this->saleService->createSale($saleData, $saleItems);
        $capture->update(['sale_id' => $sale->id]);
    }

    /**
     * Soft-delete the placeholder sale (only while it is still 'incompleted'
     * and untouched by staff — otherwise leave it for them to reconcile).
     */
    private function removePlaceholderSale(IncompleteCheckout $capture): void
    {
        $sale = $capture->sale_id ? Sale::find($capture->sale_id) : null;

        if (! $sale || $sale->status !== 'incompleted' || $sale->allocations()->exists()) {
            return;
        }

        try {
            $this->saleService->deleteSale($sale);
        } catch (\Throwable $e) {
            Log::warning("Could not remove placeholder sale {$sale->invoice_number} for converted checkout {$capture->token}: {$e->getMessage()}");
        }
    }
}
