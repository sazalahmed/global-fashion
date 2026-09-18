<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Http\Request;
use Modules\Ecommerce\Jobs\SendFbCapiEvent;
use Modules\Ecommerce\Jobs\SendGa4McEvent;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Models\EcommerceOrderItem;
use Modules\Product\Models\Product;
use Modules\Setting\Models\Setting;

class TrackingService
{
    public function __construct(private readonly FraudCheckService $fraud) {}

    // ── Settings ──
    public function gtmId(): ?string { return Setting::get('tracking', 'gtm_container_id'); }
    public function pixelId(): ?string { return Setting::get('tracking', 'fbpixel_id'); }
    public function capiToken(): ?string { return Setting::get('tracking', 'fbpixel_access_token'); }
    public function testEventCode(): ?string { return Setting::get('tracking', 'fbpixel_test_event_code'); }
    public function ga4MeasurementId(): ?string { return Setting::get('tracking', 'ga4_measurement_id'); }
    public function ga4ApiSecret(): ?string { return Setting::get('tracking', 'ga4_api_secret'); }

    public function gtmEnabled(): bool
    {
        return (bool) Setting::get('tracking', 'gtm_enabled', false) && $this->gtmId();
    }

    public function pixelEnabled(): bool
    {
        return (bool) Setting::get('tracking', 'fbpixel_enabled', false) && $this->pixelId();
    }

    public function ga4Enabled(): bool
    {
        return (bool) Setting::get('tracking', 'ga4_enabled', false) && $this->ga4MeasurementId();
    }

    // ── Fraud gate ──
    public function blockedRiskLevels(): array
    {
        $raw = Setting::get('tracking', 'purchase_block_risk_levels', 'high,critical');
        return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
    }

    public function shouldReportPurchase(EcommerceOrder $order): bool
    {
        $risk = $this->fraud->getRiskLevel($order->fraud_report);
        return ! in_array($risk, $this->blockedRiskLevels(), true);
    }

    // ── Normalization ──
    public function itemFromProduct(Product $product, int $quantity = 1): array
    {
        $price = $product->displayPrice();

        $item = [
            'item_id'       => (string) $product->id,
            'item_name'     => $product->name,
            'price'         => (float) $price->effective,
            'quantity'      => $quantity,
            'item_brand'    => optional($product->brand)->name,
            'item_category' => optional($product->category)->name,
        ];

        if ($price->has_discount) {
            $item['discount'] = round((float) $price->sell - (float) $price->effective, 2);
        }

        return $item;
    }

    public function itemsFromOrder(EcommerceOrder $order): array
    {
        return $order->items->map(function (EcommerceOrderItem $i) {
            $item = [
                'item_id'   => (string) $i->product_id,
                'item_name' => $i->product_name,
                'price'     => (float) $i->unit_price,
                'quantity'  => (int) $i->quantity,
            ];

            if ($i->variant_name) {
                $item['item_variant'] = $i->variant_name;
            }

            return $item;
        })->all();
    }

    public function contentsFromItems(array $items): array
    {
        return array_map(fn (array $i) => [
            'id'         => $i['item_id'],
            'quantity'   => $i['quantity'],
            'item_price' => $i['price'],
        ], $items);
    }

    // ── Client identifiers (captured at checkout) ──
    public function captureClientIdentifiers(Request $request): array
    {
        $fbc = $request->cookie('_fbc');
        if (! $fbc && $request->query('fbclid')) {
            $fbc = 'fb.1.' . time() . '.' . $request->query('fbclid');
        }

        return [
            'fbp'          => $request->cookie('_fbp'),
            'fbc'          => $fbc,
            'ga_client_id' => $this->parseGaCookie($request->cookie('_ga')),
            'ip'           => $request->ip(),
            'ua'           => (string) $request->userAgent(),
        ];
    }

    private function parseGaCookie(?string $ga): ?string
    {
        if (! $ga) {
            return null;
        }
        // _ga = "GA1.1.1234567890.1680000000" → client_id = "1234567890.1680000000"
        $parts = explode('.', $ga);
        return count($parts) >= 4 ? $parts[2] . '.' . $parts[3] : null;
    }

    // ── CAPI user_data (hashed where required) ──
    public function hashedUserData(EcommerceOrder $order): array
    {
        $t  = (array) ($order->tracking_data ?? []);
        $ud = [];

        if ($order->customer_email) {
            $ud['em'] = hash('sha256', strtolower(trim($order->customer_email)));
        }
        if ($order->customer_phone) {
            $ud['ph'] = hash('sha256', preg_replace('/\D/', '', $order->customer_phone));
        }
        if (! empty($t['fbp'])) { $ud['fbp'] = $t['fbp']; }
        if (! empty($t['fbc'])) { $ud['fbc'] = $t['fbc']; }
        if (! empty($t['ip']))  { $ud['client_ip_address'] = $t['ip']; }
        if (! empty($t['ua']))  { $ud['client_user_agent'] = $t['ua']; }

        return $ud;
    }

    /**
     * Report a Purchase server-side to Meta (CAPI) as a deduplicated backup of
     * the browser event fired on the checkout success page (same event_id).
     * GA4 purchase is browser-only — no Measurement Protocol dispatch here.
     * Fires at most once per order (purchase_reported_at guard) and only for
     * orders that pass the fraud gate. The guard is set even when suppressed so
     * the decision is final and never re-evaluated.
     */
    public function reportPurchase(EcommerceOrder $order): void
    {
        if ($order->purchase_reported_at) {
            return;
        }
        $order->forceFill(['purchase_reported_at' => now()])->save();

        if (! $this->shouldReportPurchase($order)) {
            return;
        }

        $order->loadMissing('items');
        $items   = $this->itemsFromOrder($order);
        $eventId = $this->eventId('purchase.' . $order->order_number);
        $url     = route('storefront.checkout.success', $order->order_number);

        // Meta Conversions API
        if ($this->pixelId() && $this->capiToken()) {
            SendFbCapiEvent::dispatch(
                pixelId: $this->pixelId(),
                token: $this->capiToken(),
                eventName: 'Purchase',
                eventId: $eventId,
                customData: [
                    'value'        => (float) $order->grand_total,
                    'currency'     => 'BDT',
                    'content_type' => 'product',
                    'contents'     => $this->contentsFromItems($items),
                    'num_items'    => array_sum(array_column($items, 'quantity')),
                    'order_id'     => $order->order_number,
                ],
                userData: $this->hashedUserData($order),
                eventSourceUrl: $url,
                testEventCode: $this->testEventCode(),
            );
        }
    }

    /**
     * "new" for a customer's first fulfilled order, "returning" afterwards.
     * Matched by customer_id when the order has one, else by phone number.
     */
    public function customerType(EcommerceOrder $order): string
    {
        $query = EcommerceOrder::where('id', '<', $order->id)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered']);

        if ($order->customer_id) {
            $query->where(function ($q) use ($order) {
                $q->where('customer_id', $order->customer_id)
                  ->orWhere('customer_phone', $order->customer_phone);
            });
        } elseif ($order->customer_phone) {
            $query->where('customer_phone', $order->customer_phone);
        } else {
            return 'new';
        }

        return $query->exists() ? 'returning' : 'new';
    }

    public function eventId(string $seed): string
    {
        return $seed;
    }

    /**
     * Payload for the browser-side Purchase fired on the checkout success page
     * (GTM dataLayer + gtag + fbq via BizPOS.track). Null when the fraud gate
     * blocks the order — the page then renders no tracking script at all.
     * event_id matches the CAPI event so Meta dedups browser + server.
     */
    public function browserPurchasePayload(EcommerceOrder $order): ?array
    {
        if (! $this->shouldReportPurchase($order)) {
            return null;
        }

        $order->loadMissing('items');
        $items = $this->itemsFromOrder($order);

        return [
            'ga' => [
                'transaction_id' => $order->order_number,
                'value'          => (float) $order->grand_total,
                'currency'       => 'BDT',
                'shipping'       => (float) $order->shipping_charge,
                'tax'            => (float) $order->tax_amount,
                'coupon'         => $order->coupon_code,
                'payment_type'   => $order->payment_method,
                'customer_type'  => $this->customerType($order),
                'items'          => $items,
            ],
            'fb' => [
                'value'        => (float) $order->grand_total,
                'currency'     => 'BDT',
                'content_type' => 'product',
                'contents'     => $this->contentsFromItems($items),
                'num_items'    => array_sum(array_column($items, 'quantity')),
                'order_id'     => $order->order_number,
            ],
            'event_id' => $this->eventId('purchase.' . $order->order_number),
            'user'     => $this->browserUserData($order),
            'customer' => $this->browserCustomerData($order),
        ];
    }

    /**
     * Customer info for the browser purchase, in Google's enhanced-conversions
     * user_data shape (email_address / phone_number E.164 / address — Google's
     * spec places first/last name INSIDE address). GTM tags can map the same
     * object for Meta CAPI matching. Google hashes these before sending, so
     * plain values are the documented format.
     */
    public function browserUserData(EcommerceOrder $order): array
    {
        $ud = [];

        if ($order->customer_email) {
            $ud['email_address'] = strtolower(trim($order->customer_email));
        }
        if ($phone = $this->e164Phone($order->customer_phone)) {
            $ud['phone_number'] = $phone;
        }

        $address = [];
        $name = trim((string) $order->customer_name);
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name);
            $address['first_name'] = array_shift($parts);
            if ($parts) {
                $address['last_name'] = implode(' ', $parts);
            }
        }
        if ($order->shipping_address) {
            $address['street'] = $order->shipping_address;
        }
        if ($city = $this->cityFromAddress($order->shipping_address)) {
            $address['city'] = $city;
        }
        if ($address) {
            $address['country'] = 'BD';
            $ud['address'] = $address;
        }

        return $ud;
    }

    /**
     * Plain (non-spec) customer block pushed to the GTM dataLayer alongside
     * user_data, so container tags can map checkout info without parsing the
     * enhanced-conversions shape. billing_address/city/country are deliberately
     * omitted (billing mirrors shipping; city/country live in user_data.address).
     * Only present keys are included.
     */
    public function browserCustomerData(EcommerceOrder $order): array
    {
        // alt_phone (also embedded in notes) is deliberately NOT sent — ad
        // platforms only match on the primary phone/email, so it would be
        // extra PII in the dataLayer with no consumer.
        [, $note] = $this->splitOrderNotes($order->notes);

        $data = [
            'name'             => trim((string) $order->customer_name) ?: null,
            'email'            => $order->customer_email ? strtolower(trim($order->customer_email)) : null,
            'phone'            => $this->e164Phone($order->customer_phone),
            'shipping_address' => $order->shipping_address,
            'delivery_zone'    => $order->shipping_zone_id ? $order->shippingZone?->name : null,
            'payment_method'   => $order->payment_method,
            'note'             => $note,
        ];

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Checkout stores the alternate phone inside notes as "Alt: <phone>",
     * joined to the customer's own note with " | ". Split them back apart:
     * returns [altPhone, note], either may be null.
     */
    private function splitOrderNotes(?string $notes): array
    {
        $altPhone = null;
        $parts = array_values(array_filter(array_map('trim', explode('|', (string) $notes))));

        foreach ($parts as $i => $part) {
            if (preg_match('/^Alt:\s*([\d\-\+ ]+)$/', $part, $m)) {
                $altPhone = $m[1];
                unset($parts[$i]);
                break;
            }
        }

        $note = trim(implode(' | ', $parts));

        return [$altPhone, $note !== '' ? $note : null];
    }

    /** Normalize a BD phone number to E.164 (+8801XXXXXXXXX). Null when empty. */
    public function e164Phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '88' . $digits; // 01XXXXXXXXX → 8801XXXXXXXXX
        } elseif (str_starts_with($digits, '1')) {
            $digits = '880' . $digits; // 1XXXXXXXXX (no leading zero)
        }

        return '+' . $digits;
    }

    /**
     * Best-effort city: checkout appends the chosen district to the address
     * ("House 5, Road 2, Dhanmondi, Dhaka"), so the last comma segment is a
     * city candidate — used only when it matches a known district.
     */
    public function cityFromAddress(?string $address): ?string
    {
        $last = trim((string) last(explode(',', (string) $address)));
        if ($last === '') {
            return null;
        }

        return \Modules\Location\Models\District::where('district_name', $last)->exists()
            ? $last
            : null;
    }

    /**
     * Placement-time server-side reporting, shared by every order-creation path
     * (storefront checkout, AI chat, AI quick-checkout). Captures the client
     * identifiers from the live request, then defers the CAPI send to after the
     * response — the checkout fraud check is itself deferred and registered
     * earlier, so by the time this runs the fraud gate sees fraud_report.
     */
    public function reportPurchaseAtPlacement(EcommerceOrder $order, Request $request): void
    {
        try {
            $order->update(['tracking_data' => $this->captureClientIdentifiers($request)]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Tracking capture failed on order ' . $order->order_number,
                ['error' => $e->getMessage()]
            );
        }

        defer(function () use ($order) {
            try {
                $this->reportPurchase($order->fresh());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    'Purchase tracking failed ' . $order->order_number,
                    ['error' => $e->getMessage()]
                );
            }
        });
    }

    /**
     * Report a Refund to GA4 when a purchase-reported order is cancelled or
     * refunded. Fires at most once (refund_reported_at guard). Symmetric with
     * the purchase fraud gate: a suppressed purchase was never sent, so its
     * refund is suppressed too (guard still set — the decision is final).
     */
    public function reportRefund(EcommerceOrder $order): void
    {
        if (! $order->purchase_reported_at || $order->refund_reported_at) {
            return;
        }
        $order->forceFill(['refund_reported_at' => now()])->save();

        if (! $this->shouldReportPurchase($order)) {
            return;
        }
        if (! $this->ga4MeasurementId() || ! $this->ga4ApiSecret()) {
            return;
        }

        $order->loadMissing('items');
        $clientId = ((array) ($order->tracking_data ?? []))['ga_client_id'] ?? null;

        SendGa4McEvent::dispatch(
            measurementId: $this->ga4MeasurementId(),
            apiSecret: $this->ga4ApiSecret(),
            clientId: $clientId ?: ('srv.' . $order->id),
            params: [
                'transaction_id' => $order->order_number,
                'value'          => (float) $order->grand_total,
                'currency'       => 'BDT',
                'items'          => $this->itemsFromOrder($order),
            ],
            eventName: 'refund',
        );
    }
}
