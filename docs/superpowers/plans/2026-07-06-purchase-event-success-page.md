# Purchase Event on Storefront Success Page — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fire the Purchase conversion in the browser on the checkout success page (GA4 gtag + GTM dataLayer + Meta Pixel), with Meta CAPI as a deduplicated server-side backup sent at order placement instead of on admin status change.

**Architecture:** `TrackingService` gains two methods — `browserPurchasePayload()` (fraud-gated payload for the success page) and `reportPurchaseAtPlacement()` (captures client identifiers + defers the CAPI send so the deferred fraud check runs first). The success page renders a `BizPOS.track('Purchase', …)` script only when a one-time session flash from the checkout redirect matches the order. `reportPurchase()` loses its GA4 Measurement Protocol dispatch (GA4 purchase becomes browser-only; refund stays server-side). Status-change call sites in `EcommerceService`/`SaleService` are removed.

**Tech Stack:** Laravel 12 (Modules), Blade, jQuery-era vanilla JS (`public/js/tracking.js`), PHPUnit (MySQL `bizpos_test` DB).

**Spec:** `docs/superpowers/specs/2026-07-06-purchase-event-success-page-design.md`

## Global Constraints

- Every `<script>` block starts with `'use strict';` (project rule #9).
- Named routes only — never hardcoded URL paths (project rule #11).
- Blade output escaped with `{{ }}` / `@json` — never `{!! !!}` for user data.
- Tests run with `php artisan test <path>` (MySQL `bizpos_test`; `QUEUE_CONNECTION=sync`, so **every test touching purchase reporting must `Bus::fake()`** or the queued job would fire a real HTTP call).
- The shared event id is `purchase.{order_number}` (from `TrackingService::eventId()`, which returns its seed verbatim) — the browser `eventID` and CAPI `event_id` MUST be identical or Meta dedup breaks.
- The fraud check in checkout is **deferred** (runs after the response is sent, `CheckoutController::process()` ~line 382). Any placement-time CAPI send must also be deferred and registered AFTER it, so the fraud gate sees `fraud_report`.

---

### Task 1: TrackingService — browser payload, placement-time reporting, drop GA4 MP purchase

**Files:**
- Modify: `Modules/Ecommerce/app/Services/TrackingService.php`
- Test: `Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`

**Interfaces:**
- Consumes: existing `shouldReportPurchase()`, `itemsFromOrder()`, `contentsFromItems()`, `customerType()`, `captureClientIdentifiers()`, `reportPurchase()`, `eventId()`.
- Produces (later tasks rely on these exact signatures):
  - `browserPurchasePayload(EcommerceOrder $order): ?array` — returns `['ga' => array, 'fb' => array, 'event_id' => string]`, or `null` when the fraud gate blocks the order.
  - `reportPurchaseAtPlacement(EcommerceOrder $order, Request $request): void` — captures `tracking_data`, then `defer()`s `reportPurchase($order->fresh())`.
  - `reportPurchase()` now dispatches **only** `SendFbCapiEvent` (never `SendGa4McEvent`).

- [ ] **Step 1: Write the failing tests**

In `Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`:

**(a)** Replace `test_report_purchase_dispatches_both_jobs_for_trusted_order()` (line 168) with:

```php
public function test_report_purchase_dispatches_only_capi_for_trusted_order(): void
{
    Bus::fake();
    Setting::set('tracking', 'fbpixel_id', '1234567890');
    Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');
    Setting::set('tracking', 'ga4_measurement_id', 'G-ABC123');
    Setting::set('tracking', 'ga4_api_secret', 'SECRET');
    Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

    $order = EcommerceOrder::create([
        'order_number' => 'ORD-T1', 'customer_name' => 'A', 'customer_phone' => '01712345678',
        'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'confirmed',
        'subtotal' => 1500, 'grand_total' => 1500,
        'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]],
    ]);

    app(TrackingService::class)->reportPurchase($order);

    Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class, function ($job) {
        return $job->eventId === 'purchase.ORD-T1' && $job->eventName === 'Purchase';
    });
    // GA4 purchase is browser-only now — MP must NOT be dispatched.
    Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class);
    $this->assertNotNull($order->fresh()->purchase_reported_at);
}
```

**(b)** Append these new tests before the closing brace:

```php
public function test_browser_purchase_payload_contains_ga4_and_meta_shapes(): void
{
    Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

    // ecommerce_order_items.product_id is FK-constrained — persist a real product.
    $category = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
    $product = \Modules\Product\Database\Factories\ProductFactory::new()->create([
        'category_id' => $category->id, 'sell_price' => 499.00,
    ]);

    $order = EcommerceOrder::create([
        'order_number' => 'ORD-BP1', 'customer_name' => 'A', 'customer_phone' => '01712345678',
        'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'pending',
        'subtotal' => 998, 'grand_total' => 1058, 'shipping_charge' => 60,
        'coupon_code' => 'SAVE10',
        'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]],
    ]);
    $order->items()->create([
        'product_id' => $product->id, 'product_name' => 'Tee', 'quantity' => 2,
        'unit_price' => 499.00, 'subtotal' => 998.00,
    ]);

    $payload = $this->service()->browserPurchasePayload($order);

    $this->assertSame('ORD-BP1', $payload['ga']['transaction_id']);
    $this->assertSame(1058.0, $payload['ga']['value']);
    $this->assertSame('BDT', $payload['ga']['currency']);
    $this->assertSame(60.0, $payload['ga']['shipping']);
    $this->assertSame('SAVE10', $payload['ga']['coupon']);
    $this->assertSame('new', $payload['ga']['customer_type']);
    $this->assertSame((string) $product->id, $payload['ga']['items'][0]['item_id']);

    $this->assertSame('purchase.ORD-BP1', $payload['event_id']);
    $this->assertSame('product', $payload['fb']['content_type']);
    $this->assertSame(2, $payload['fb']['num_items']);
    $this->assertSame('ORD-BP1', $payload['fb']['order_id']);
    $this->assertSame([['id' => (string) $product->id, 'quantity' => 2, 'item_price' => 499.0]], $payload['fb']['contents']);
}

public function test_browser_purchase_payload_null_for_blocked_risk(): void
{
    Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

    $order = EcommerceOrder::create([
        'order_number' => 'ORD-BP2', 'customer_name' => 'A', 'customer_phone' => '01712345678',
        'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'pending',
        'subtotal' => 1500, 'grand_total' => 1500,
        'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 20]],
    ]);

    $this->assertNull($this->service()->browserPurchasePayload($order));
}

public function test_report_purchase_at_placement_captures_identifiers_and_defers_capi(): void
{
    Bus::fake();
    Setting::set('tracking', 'fbpixel_id', '1234567890');
    Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');
    Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

    $order = EcommerceOrder::create([
        'order_number' => 'ORD-PL1', 'customer_name' => 'A', 'customer_phone' => '01712345678',
        'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'pending',
        'subtotal' => 1500, 'grand_total' => 1500,
    ]);

    $request = Request::create('/checkout/process?fbclid=abc123', 'POST');
    $request->headers->set('User-Agent', 'PHPUnit-UA');

    $this->service()->reportPurchaseAtPlacement($order, $request);

    // Not dispatched yet — the send is deferred until after the response.
    Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
    $this->assertSame('PHPUnit-UA', $order->fresh()->tracking_data['ua']);

    // Simulate end-of-request: run the deferred callbacks.
    app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();

    Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class, function ($job) {
        return $job->eventId === 'purchase.ORD-PL1';
    });
    $this->assertNotNull($order->fresh()->purchase_reported_at);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`
Expected: FAIL — `test_report_purchase_dispatches_only_capi_for_trusted_order` fails on `assertNotDispatched(SendGa4McEvent)`; the two new-method tests error with "Call to undefined method … browserPurchasePayload / reportPurchaseAtPlacement".

- [ ] **Step 3: Implement in `TrackingService.php`**

**(a)** In `reportPurchase()` (line 154), delete the GA4 Measurement Protocol block — everything from the comment `// GA4 Measurement Protocol` (line 191) through the closing `);` of the `SendGa4McEvent::dispatch(...)` call (line 208), including the `$clientId = …` line (line 192). The method now ends after the Meta CAPI `if` block. Update the method docblock:

```php
/**
 * Report a Purchase server-side to Meta (CAPI) as a deduplicated backup of
 * the browser event fired on the checkout success page (same event_id).
 * GA4 purchase is browser-only — no Measurement Protocol dispatch here.
 * Fires at most once per order (purchase_reported_at guard) and only for
 * orders that pass the fraud gate. The guard is set even when suppressed so
 * the decision is final and never re-evaluated.
 */
```

**(b)** Add the two new methods after `eventId()` (line 238), before `reportRefund()`:

```php
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
    ];
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
```

(`Illuminate\Http\Request` is already imported; `defer()` is the Laravel 12 global helper.)

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`
Expected: PASS (all tests in the file, including the untouched refund tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/TrackingService.php Modules/Ecommerce/tests/Unit/TrackingServiceTest.php
git commit -m "feat(tracking): browser purchase payload + placement-time CAPI; drop GA4 MP purchase"
```

---

### Task 2: Success page fires the browser Purchase (controller + view + tracking.js)

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php` (`success()`, line 427)
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/checkout/success.blade.php`
- Modify: `public/js/tracking.js`
- Test: `Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`

**Interfaces:**
- Consumes: `TrackingService::browserPurchasePayload(EcommerceOrder $order): ?array` (Task 1).
- Produces: the success view receives `$purchasePayload` (`?array` with keys `ga`, `fb`, `event_id`); the checkout redirect's session flash key is **`purchase_order`** (string order number) — Task 3 sets it.

- [ ] **Step 1: Write the failing feature tests**

Append to `Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php` (imports at top already include `Setting`; add `use Modules\Ecommerce\Models\EcommerceOrder;`):

```php
private function makeSuccessPageOrder(array $attrs = []): EcommerceOrder
{
    $order = EcommerceOrder::create(array_merge([
        'order_number'     => 'ORD-SP-' . uniqid(),
        'customer_name'    => 'Guest',
        'customer_phone'   => '01712345678',
        'shipping_address' => 'Dhaka',
        'billing_address'  => 'Dhaka',
        'status'           => 'pending',
        'subtotal'         => 500,
        'grand_total'      => 560,
        'shipping_charge'  => 60,
    ], $attrs));

    // ecommerce_order_items.product_id is FK-constrained — persist a real product.
    $category = CategoryFactory::new()->create();
    $product = ProductFactory::new()->create([
        'category_id' => $category->id, 'sell_price' => 500.00,
    ]);
    $order->items()->create([
        'product_id' => $product->id, 'product_name' => $product->name,
        'quantity' => 1, 'unit_price' => 500.00, 'subtotal' => 500.00,
    ]);

    return $order;
}

public function test_success_page_fires_purchase_with_flash(): void
{
    $order = $this->makeSuccessPageOrder();

    $res = $this->withSession(['purchase_order' => $order->order_number])
        ->get(route('storefront.checkout.success', $order->order_number));

    $res->assertOk();
    $res->assertSee("BizPOS.track('Purchase'", false);
    $res->assertSee($order->order_number, false);
    $res->assertSee('purchase.' . $order->order_number, false); // shared eventID for Meta dedup
    $res->assertSee('customer_type', false);
}

public function test_success_page_reload_does_not_refire_purchase(): void
{
    $order = $this->makeSuccessPageOrder();

    // No flash in the session — a reload, revisit, or shared link.
    $res = $this->get(route('storefront.checkout.success', $order->order_number));

    $res->assertOk();
    $res->assertDontSee("BizPOS.track('Purchase'", false);
}

public function test_success_page_omits_purchase_for_blocked_risk(): void
{
    Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');
    $order = $this->makeSuccessPageOrder([
        'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 20]],
    ]);

    $res = $this->withSession(['purchase_order' => $order->order_number])
        ->get(route('storefront.checkout.success', $order->order_number));

    $res->assertOk();
    $res->assertDontSee("BizPOS.track('Purchase'", false);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`
Expected: FAIL — `test_success_page_fires_purchase_with_flash` can't find `BizPOS.track('Purchase'`; the other two new tests pass trivially (nothing renders yet) — that's fine, they lock in behavior against regressions.

- [ ] **Step 3: Implement**

**(a)** `CheckoutController::success()` (line 427) becomes:

```php
public function success(string $orderNumber): View
{
    $order = $this->storefrontService->findOrderByNumber($orderNumber);

    if (!$order) {
        abort(404);
    }

    // Browser-side Purchase fires only on the first arrival from checkout
    // (one-time session flash) — reloads, revisits, and shared links never
    // re-fire it. Fraud-gated inside browserPurchasePayload (null = no script).
    $purchasePayload = null;
    if (session('purchase_order') === $orderNumber) {
        $purchasePayload = app(\Modules\Ecommerce\Services\TrackingService::class)
            ->browserPurchasePayload($order);
    }

    return view(
        'ecommerce::storefront.pages.checkout.success',
        compact('order', 'purchasePayload') + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,follow')]
    );
}
```

**(b)** In `success.blade.php`, after the existing `@push('scripts') … @endpush` block at the end of the file, add:

```blade
@if(!empty($purchasePayload))
    @push('scripts')
        <script>
            'use strict';

            // Browser-side Purchase — rendered only on the first arrival from
            // checkout (session flash) and only for fraud-clean orders. The
            // eventID matches the server-side CAPI event so Meta dedups them.
            BizPOS.track('Purchase', @json($purchasePayload['ga']), {
                eventID: @json($purchasePayload['event_id']),
                fbData: @json($purchasePayload['fb'])
            });
        </script>
    @endpush
@endif
```

(The storefront master layout loads `js/tracking.js` before `@stack('scripts')` — `BizPOS.track` is available.)

**(c)** In `public/js/tracking.js`, add the three missing params to both sinks:

In the **GTM dataLayer** section, after the `payment_type` line (line 53):

```js
if (typeof params.shipping !== 'undefined') { ecommerce.shipping = params.shipping; }
if (typeof params.tax !== 'undefined') { ecommerce.tax = params.tax; }
if (params.customer_type) { payload.customer_type = params.customer_type; }
```

In the **gtag** section, after the `payment_type` line (line 77):

```js
if (typeof params.shipping !== 'undefined') { ga4Params.shipping = params.shipping; }
if (typeof params.tax !== 'undefined') { ga4Params.tax = params.tax; }
if (params.customer_type) { ga4Params.customer_type = params.customer_type; }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`
Expected: PASS (all tests in the file).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php Modules/Ecommerce/resources/views/storefront/pages/checkout/success.blade.php public/js/tracking.js Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php
git commit -m "feat(tracking): fire browser purchase on checkout success page (flash-guarded, fraud-gated)"
```

---

### Task 3: Checkout placement — session flash + deferred CAPI

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php` (`process()`, lines 396–422)
- Test: Create `Modules/Ecommerce/tests/Feature/CheckoutPurchaseTrackingTest.php`

**Interfaces:**
- Consumes: `TrackingService::reportPurchaseAtPlacement(EcommerceOrder $order, Request $request): void` (Task 1); flash key `purchase_order` (Task 2 reads it).
- Produces: `POST storefront.checkout.process` → redirect to success carries `purchase_order` flash; `SendFbCapiEvent` dispatched at request termination; `purchase_reported_at` set.

- [ ] **Step 1: Write the failing feature test**

Create `Modules/Ecommerce/tests/Feature/CheckoutPurchaseTrackingTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\Bus;
use Modules\Category\Database\Factories\CategoryFactory;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Models\ShippingZone;
use Modules\Product\Database\Factories\ProductFactory;
use Modules\Setting\Models\Setting;
use Tests\TestCase;

class CheckoutPurchaseTrackingTest extends TestCase
{
    public function test_placing_an_order_sends_capi_at_placement_and_flashes_purchase_order(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');
        Setting::set('tracking', 'ga4_measurement_id', 'G-ABC123');
        Setting::set('tracking', 'ga4_api_secret', 'SECRET');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $category = CategoryFactory::new()->create();
        $product = ProductFactory::new()->create([
            'status' => 'active', 'category_id' => $category->id, 'sell_price' => 500.00,
        ]);
        $zone = ShippingZone::create(['name' => 'Dhaka', 'flat_rate' => 60, 'is_active' => true]);

        $cart = [
            (string) $product->id => [
                'product_id'         => $product->id,
                'variant_id'         => null,
                'variant_name'       => null,
                'variant_attributes' => [],
                'name'               => $product->name,
                'slug'               => $product->slug,
                'price'              => 500.00,
                'sell_price'         => 500.00,
                'image'              => null,
                'quantity'           => 1,
                'sku'                => $product->sku,
            ],
        ];

        $res = $this->withSession(['cart' => $cart])->post(route('storefront.checkout.process'), [
            'customer_name'    => 'Test Guest',
            'customer_phone'   => '01712345678',
            'address'          => 'House 1, Road 2, Dhanmondi',
            'shipping_zone_id' => $zone->id,
            'payment_method'   => 'cod',
        ]);

        $order = EcommerceOrder::latest('id')->first();
        $this->assertNotNull($order, 'Checkout did not create an order.');

        $res->assertRedirect(route('storefront.checkout.success', $order->order_number));
        $res->assertSessionHas('purchase_order', $order->order_number);

        // CAPI dispatched at placement (deferred callbacks run at kernel
        // terminate, which the HTTP test harness invokes); GA4 MP must not be.
        Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class, function ($job) use ($order) {
            return $job->eventId === 'purchase.' . $order->order_number
                && $job->eventName === 'Purchase';
        });
        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class);
        $this->assertNotNull($order->fresh()->purchase_reported_at);
    }
}
```

(The fraud check inside `process()` is deferred but harmless in tests: `BdCourierApiService` is unconfigured, so `FraudCheckService::check()` returns null without any HTTP call.)

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/CheckoutPurchaseTrackingTest.php`
Expected: FAIL — `assertSessionHas('purchase_order')` fails and/or `SendFbCapiEvent` not dispatched (nothing reports at placement yet).

- [ ] **Step 3: Implement in `CheckoutController::process()`**

**(a)** Replace the tracking-capture block (lines 396–405):

```php
// Capture ad-click identifiers now so the deferred (confirmation-time)
// Purchase event can still match the original click. Best-effort only.
try {
    $order->update([
        'tracking_data' => app(\Modules\Ecommerce\Services\TrackingService::class)
            ->captureClientIdentifiers($request),
    ]);
} catch (\Throwable $e) {
    \Log::warning('Tracking capture failed on order ' . $order->order_number, ['error' => $e->getMessage()]);
}
```

with:

```php
// Capture ad-click identifiers and send the server-side (CAPI) Purchase
// after the response. Registered AFTER the fraud-check defer above, so the
// deferred callbacks run in order and the fraud gate sees fraud_report.
// The browser Purchase fires on the success page; both share an event_id
// so Meta dedups them. Best-effort only — never blocks the order.
app(\Modules\Ecommerce\Services\TrackingService::class)
    ->reportPurchaseAtPlacement($order, $request);
```

**(b)** Change the final redirect (line 420) to carry the one-time flash the success page checks:

```php
return redirect()->route('storefront.checkout.success', $order->order_number)
    ->with('success', __('Order placed successfully!'))
    ->with('purchase_order', $order->order_number);
```

(Do NOT add the flash to the idempotency-replay redirect at line 334 — a replayed token is a revisit, not a new purchase.)

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test Modules/Ecommerce/tests/Feature/CheckoutPurchaseTrackingTest.php Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php Modules/Ecommerce/tests/Feature/CheckoutPurchaseTrackingTest.php
git commit -m "feat(tracking): CAPI purchase at order placement + success-page flash"
```

---

### Task 4: AI-assistant order paths report at placement too

**Files:**
- Modify: `Modules/AiAssistant/app/Http/Controllers/Storefront/ChatController.php` (`placeOrder()`, after `createOrder` succeeds ~line 283)
- Modify: `Modules/AiAssistant/app/Ai/Tools/QuickCheckout.php` (after `createOrder` succeeds ~line 167)

**Interfaces:**
- Consumes: `TrackingService::reportPurchaseAtPlacement(EcommerceOrder $order, Request $request): void` (Task 1; unit-tested there — these call sites are one-liners on the shared path).
- Produces: AI-placed orders get `tracking_data` + a placement-time CAPI Purchase. (Their `success_url` page renders no browser event — no flash — which is the accepted gap; CAPI covers Meta.)

- [ ] **Step 1: Add the call in `ChatController::placeOrder()`**

Directly after `session()->forget(['cart', 'coupon']);` (line 283), add:

```php
// Server-side (CAPI) Purchase at placement — the AI flow never lands on the
// success page with the checkout flash, so CAPI is the only Purchase signal
// for these orders. Deferred + once-guarded inside the service.
app(\Modules\Ecommerce\Services\TrackingService::class)
    ->reportPurchaseAtPlacement($order, $request);
```

(`placeOrder(PlaceOrderRequest $request, …)` — `$request` is in scope; `PlaceOrderRequest` extends `FormRequest` which is a `Request`.)

- [ ] **Step 2: Add the call in `QuickCheckout` tool**

Directly after `session()->forget(['cart', 'coupon']);` (line 167), add:

```php
// Server-side (CAPI) Purchase at placement — same rationale as the chat
// controller path: no success-page flash, CAPI is the only Purchase signal.
app(\Modules\Ecommerce\Services\TrackingService::class)
    ->reportPurchaseAtPlacement($order, request());
```

(`QuickCheckout` runs inside the chat HTTP request, so the `request()` helper resolves the live request. Note its `$request` parameter is the AI tool-argument array, NOT an HTTP request — do not pass it.)

- [ ] **Step 3: Run the AI-assistant and ecommerce test suites to verify nothing broke**

Run: `php artisan test Modules/AiAssistant/tests Modules/Ecommerce/tests`
Expected: PASS (no behavioral tests cover these AI paths' tracking; the shared method is unit-tested in Task 1).

- [ ] **Step 4: Commit**

```bash
git add Modules/AiAssistant/app/Http/Controllers/Storefront/ChatController.php Modules/AiAssistant/app/Ai/Tools/QuickCheckout.php
git commit -m "feat(tracking): placement-time CAPI purchase on AI-assistant order paths"
```

---

### Task 5: Remove status-change purchase reporting

**Files:**
- Modify: `Modules/Ecommerce/app/Services/EcommerceService.php` (lines 98–106)
- Modify: `Modules/Sale/app/Services/SaleService.php` (`syncEcommerceOrder()`, lines 548–580)
- Test: Create `Modules/Ecommerce/tests/Unit/EcommerceServiceTrackingTest.php`
- Test: Modify `Modules/Sale/tests/Unit/SaleServiceTest.php`

**Interfaces:**
- Consumes: nothing new. `reportRefund()` call sites in both services are KEPT untouched.
- Produces: order/sale status changes never dispatch `SendFbCapiEvent` and never set `purchase_reported_at`.

- [ ] **Step 1: Write the failing tests**

**(a)** Create `Modules/Ecommerce/tests/Unit/EcommerceServiceTrackingTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Support\Facades\Bus;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Services\EcommerceService;
use Modules\Setting\Models\Setting;
use Tests\TestCase;

class EcommerceServiceTrackingTest extends TestCase
{
    public function test_confirming_an_order_no_longer_reports_purchase(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-ST-' . uniqid(), 'customer_name' => 'A',
            'customer_phone' => '01712345678', 'shipping_address' => 'x',
            'billing_address' => 'x', 'status' => 'pending',
            'subtotal' => 500, 'grand_total' => 500,
        ]);

        app(EcommerceService::class)->updateOrderStatus($order, 'confirmed');

        // Purchase is reported at placement (checkout) — a status change must
        // neither dispatch CAPI nor claim the once-guard.
        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
        $this->assertNull($order->fresh()->purchase_reported_at);
    }
}
```

**(b)** Add to `Modules/Sale/tests/Unit/SaleServiceTest.php` (near the existing refund test, after line 267):

```php
public function test_confirming_ecommerce_sale_no_longer_reports_purchase(): void
{
    \Illuminate\Support\Facades\Bus::fake();
    \Modules\Setting\Models\Setting::set('tracking', 'fbpixel_id', '1234567890');
    \Modules\Setting\Models\Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');

    [$sale, $order] = $this->makeEcommerceSaleWithOrder('ORD-NP-0001');

    $this->service->changeStatus($sale, 'confirmed');

    // Purchase reports at placement now — admin fulfilment must not re-report.
    \Illuminate\Support\Facades\Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
    $this->assertNull($order->fresh()->purchase_reported_at);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test Modules/Ecommerce/tests/Unit/EcommerceServiceTrackingTest.php Modules/Sale/tests/Unit/SaleServiceTest.php`
Expected: FAIL — both new tests find `SendFbCapiEvent` dispatched / `purchase_reported_at` set by the status-change paths.

- [ ] **Step 3: Remove the two call sites**

**(a)** `EcommerceService::updateOrderStatus()` — delete lines 98–106:

```php
        // Report the Purchase conversion once, when the order first reaches a
        // confirmed/fulfilled state. Fraud-gated + once-guarded inside the service.
        if (in_array($status, $recognizeStatuses, true) && (float) $order->grand_total > 0) {
            try {
                app(\Modules\Ecommerce\Services\TrackingService::class)->reportPurchase($order);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Purchase tracking failed {$order->order_number}: {$e->getMessage()}");
            }
        }
```

Keep the `reportRefund` block below it exactly as is.

**(b)** `SaleService::syncEcommerceOrder()` — delete the purchase block (lines 570–580):

```php
        // Recognised fulfilment statuses mirror EcommerceService's set.
        $recognised = ['confirmed', 'processing', 'shipped', 'delivered'];
        if (in_array($orderStatus, $recognised, true) && (float) $order->grand_total > 0) {
            try {
                app(\Modules\Ecommerce\Services\TrackingService::class)->reportPurchase($order->fresh());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    "Purchase tracking failed {$order->order_number}: {$e->getMessage()}"
                );
            }
        }
```

Keep its `reportRefund` block. Update the two stale docblocks in the same file:
- `syncEcommerceOrder()` docblock (lines 548–557): replace with

```php
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
```

- the `changeStatus()` inline comment (lines 536–539): replace with

```php
            // Ecommerce-sourced sales: mirror the status back to the online
            // order (and report the Refund conversion on cancellation).
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test Modules/Ecommerce/tests/Unit/EcommerceServiceTrackingTest.php Modules/Sale/tests/Unit/SaleServiceTest.php`
Expected: PASS — including the existing `test_cancel_sale_reports_ga4_refund_for_previously_reported_order` (refund path untouched).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/EcommerceService.php Modules/Sale/app/Services/SaleService.php Modules/Ecommerce/tests/Unit/EcommerceServiceTrackingTest.php Modules/Sale/tests/Unit/SaleServiceTest.php
git commit -m "refactor(tracking): stop reporting purchase on status change (placement-time now)"
```

---

### Task 6: Documentation + full regression run

**Files:**
- Modify: `docs/TRACKING.md`

- [ ] **Step 1: Update `docs/TRACKING.md`**

Rewrite the stale statements (found at lines 3, 16, 23, 31–33) to describe the new flow:

- Line 3 summary → "Browser events fire via GTM `dataLayer` + gtag + Meta Pixel; the **Purchase** fires in the browser on the order success page (once, fraud-gated) with a deduplicated Meta CAPI backup sent server-side at order placement. GA4 purchase is browser-only; the GA4 **Refund** correction stays server-side."
- "How Purchase works" paragraph (line 23) → "Purchase fires when the shopper lands on the checkout success page straight from checkout (one-time session flash — reloads and shared links never re-fire). The same `event_id` (`purchase.{order_number}`) is sent to Meta CAPI at order placement (deferred until after the fraud check), so Meta dedups browser + server. Both are suppressed for blocked fraud-risk levels; the `purchase_reported_at` guard makes the server send once-only. AI-assistant orders send CAPI only (no success-page flash). Because Purchase now counts at placement (pending), the GA4 Refund on cancellation is the correction mechanism."
- QA checklist (lines 31–33) → place a test order in the browser: `Purchase` appears in Meta Test Events **twice-deduped** (browser + server, one counted) and `purchase` in GA4 DebugView **once**; reload the success page → nothing re-fires; high-risk phone → no Purchase anywhere but `purchase_reported_at` set; cancel a placed order → GA4 `refund`.
- Keep the queue-worker and campaign-optimization warnings (lines 16, 20) — still true for CAPI/refund.

- [ ] **Step 2: Full regression run over the touched modules**

Run: `php artisan test Modules/Ecommerce/tests Modules/Sale/tests Modules/AiAssistant/tests`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add docs/TRACKING.md
git commit -m "docs(tracking): purchase now fires on success page with CAPI dedup at placement"
```
