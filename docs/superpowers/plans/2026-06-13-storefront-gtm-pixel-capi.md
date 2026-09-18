# Storefront GTM + Pixel + CAPI (fraud-gated) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire full conversion tracking into the eCommerce storefront — browser events via GTM `dataLayer` + Meta Pixel, and a deferred, fraud-gated `Purchase` sent server-side to both Meta CAPI and GA4 Measurement Protocol on order confirmation.

**Architecture:** A `TrackingService` (Ecommerce module) is the single source of truth for reading tracking settings, normalizing products/orders into GA4 items + Meta contents, deciding whether an order is trustworthy enough to report (fraud gate), and dispatching the two server-side Purchase jobs. Browser upper-funnel events fire via the existing `window.BizPOS.track()` helper + Blade `track-event` partial. The `Purchase` conversion is NOT fired in the browser; it is dispatched once from `EcommerceService::updateOrderStatus()` when an order first reaches a confirmed/fulfilled state and passes the fraud gate.

**Tech Stack:** Laravel 12, Blade, jQuery, MySQL, Laravel queued jobs, `Http` client (Graph API + GA4 MP), PHPUnit. Verified manually with GTM Tag Assistant, Meta Pixel Helper / Test Events, and GA4 DebugView.

**Spec:** `docs/superpowers/specs/2026-06-13-storefront-gtm-pixel-capi-design.md`

**Prerequisites:** The test DB `bizpos_test` must be migrated + seeded (existing module tests rely on it). `Tests\TestCase` provides `$this->admin`. Run tests with the path form, e.g. `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`.

---

## File Structure

**New files**
- `Modules/Ecommerce/app/Services/TrackingService.php` — settings reader, item/content normalizer, fraud gate, identifier capture, hashed user-data builder, Purchase dispatcher.
- `Modules/Ecommerce/app/Jobs/SendFbCapiEvent.php` — queued Meta Conversions API sender.
- `Modules/Ecommerce/app/Jobs/SendGa4McEvent.php` — queued GA4 Measurement Protocol sender.
- `Modules/Ecommerce/resources/views/storefront/partials/track-event.blade.php` — emits a `BizPOS.track()` call for server-rendered events.
- `Modules/Ecommerce/database/migrations/2026_06_13_000001_add_tracking_columns_to_ecommerce_orders_table.php`
- Tests: `Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`, `Modules/Ecommerce/tests/Unit/SendFbCapiEventTest.php`, `Modules/Ecommerce/tests/Unit/SendGa4McEventTest.php`, `Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`, `Modules/Setting/tests/Feature/TrackingSettingsTest.php`.

**Modified files**
- `Modules/Setting/app/Http/Requests/UpdateSettingsRequest.php` — validate new tracking fields.
- `Modules/Setting/resources/views/index.blade.php` — add new settings fields.
- `Modules/Core/resources/views/components/tracking-head.blade.php` + `tracking-body.blade.php` — staff-exclusion gate.
- `public/js/tracking.js` — GA4 ecommerce shape, `eventID`, advanced matching.
- `Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php` — include head/body components + load `tracking.js`.
- `Modules/Ecommerce/app/Models/EcommerceOrder.php` — fillable + casts.
- `Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php` — capture identifiers at `process()`.
- `Modules/Ecommerce/app/Services/EcommerceService.php` — dispatch Purchase in `updateOrderStatus()`.
- Storefront views (`shop/show`, `shop/index`, category `show`, `cart/index`, `checkout/index`) + product card partial + buy-now modal + `public/website/assets/js/cart.js` + `CustomerAuthController` + `NewsletterController` — event firing.
- Delete `docs/STOREFRONT_TRACKING_PLAN.md`.

---

# PHASE 1 — Foundation

## Task 1: Add new tracking settings (validation + UI)

**Files:**
- Modify: `Modules/Setting/app/Http/Requests/UpdateSettingsRequest.php:39-41` and `:78-81`
- Modify: `Modules/Setting/resources/views/index.blade.php:1428-1431` (inside the Facebook Pixel card, after the access-token field)
- Test: `Modules/Setting/tests/Feature/TrackingSettingsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Setting\Tests\Feature;

use Modules\Setting\Models\Setting;
use Tests\TestCase;

class TrackingSettingsTest extends TestCase
{
    public function test_saves_new_tracking_fields(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('settings.update', 'tracking'), [
            'gtm_enabled'                => '1',
            'gtm_container_id'           => 'GTM-ABCD123',
            'fbpixel_enabled'            => '1',
            'fbpixel_id'                 => '1234567890',
            'fbpixel_test_event_code'    => 'TEST12345',
            'ga4_measurement_id'         => 'G-ABCDE12345',
            'ga4_api_secret'             => 'secret_value_xyz',
            'purchase_block_risk_levels' => 'high,critical',
        ])->assertSessionHasNoErrors();

        $this->assertSame('G-ABCDE12345', Setting::get('tracking', 'ga4_measurement_id'));
        $this->assertSame('TEST12345', Setting::get('tracking', 'fbpixel_test_event_code'));
        $this->assertSame('high,critical', Setting::get('tracking', 'purchase_block_risk_levels'));
    }

    public function test_rejects_bad_ga4_measurement_id(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('settings.update', 'tracking'), [
            'ga4_measurement_id' => 'not-a-valid-id',
        ])->assertSessionHasErrors('ga4_measurement_id');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Setting/tests/Feature/TrackingSettingsTest.php`
Expected: FAIL (validation error on ga4 id absent → second test fails; first test passes only after fields validate).

- [ ] **Step 3: Add validation rules**

In `UpdateSettingsRequest::rules()`, immediately after the `fbpixel_access_token` rule (line 41), add:

```php
            'fbpixel_test_event_code'    => ['sometimes', 'nullable', 'string', 'max:64'],
            'ga4_measurement_id'         => ['sometimes', 'nullable', 'string', 'regex:/^G-[A-Z0-9]{4,12}$/i'],
            'ga4_api_secret'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'purchase_block_risk_levels' => ['sometimes', 'nullable', 'string', 'max:64'],
```

In `attributes()`, after the `fbpixel_access_token` line (line 80), add:

```php
            'ga4_measurement_id'  => 'GA4 Measurement ID',
            'ga4_api_secret'      => 'GA4 API secret',
```

- [ ] **Step 4: Add UI fields**

In `Modules/Setting/resources/views/index.blade.php`, replace the access-token `col-12` block (lines 1428-1431) with this expanded block (keeps the token field, adds three more):

```blade
                  <div class="col-12">
                    <label class="bp-form-label">Conversions API Access Token <span class="text-muted">(optional — server-side events)</span></label>
                    <input type="password" class="bp-form-control" name="fbpixel_access_token" value="{{ $settings['tracking']['fbpixel_access_token'] ?? '' }}" placeholder="EAAxxxxxxx...">
                  </div>
                  <div class="col-md-6">
                    <label class="bp-form-label">Meta Test Event Code <span class="text-muted">(QA only)</span></label>
                    <input type="text" class="bp-form-control" name="fbpixel_test_event_code" value="{{ old('fbpixel_test_event_code', $settings['tracking']['fbpixel_test_event_code'] ?? '') }}" placeholder="TEST12345">
                  </div>
                  <div class="col-md-6">
                    <label class="bp-form-label">Suppress Purchase for risk levels</label>
                    <input type="text" class="bp-form-control" name="purchase_block_risk_levels" value="{{ old('purchase_block_risk_levels', $settings['tracking']['purchase_block_risk_levels'] ?? 'high,critical') }}" placeholder="high,critical">
                  </div>
                  <div class="col-md-6">
                    <label class="bp-form-label">GA4 Measurement ID <span class="text-muted">(server-side purchase)</span></label>
                    <input type="text" class="bp-form-control @error('ga4_measurement_id') is-invalid @enderror" name="ga4_measurement_id" value="{{ old('ga4_measurement_id', $settings['tracking']['ga4_measurement_id'] ?? '') }}" placeholder="G-XXXXXXX">
                    @error('ga4_measurement_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
                  <div class="col-md-6">
                    <label class="bp-form-label">GA4 API Secret</label>
                    <input type="password" class="bp-form-control" name="ga4_api_secret" value="{{ $settings['tracking']['ga4_api_secret'] ?? '' }}" placeholder="••••••••">
                  </div>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test Modules/Setting/tests/Feature/TrackingSettingsTest.php`
Expected: PASS (both tests).

- [ ] **Step 6: Commit**

```bash
git add Modules/Setting/app/Http/Requests/UpdateSettingsRequest.php Modules/Setting/resources/views/index.blade.php Modules/Setting/tests/Feature/TrackingSettingsTest.php
git commit -m "feat(tracking): add GA4 MP, test-event-code, fraud-block settings"
```

---

## Task 2: TrackingService — settings getters + fraud gate

**Files:**
- Create: `Modules/Ecommerce/app/Services/TrackingService.php`
- Test: `Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Unit;

use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Services\TrackingService;
use Modules\Setting\Models\Setting;
use Tests\TestCase;

class TrackingServiceTest extends TestCase
{
    private function service(): TrackingService
    {
        return app(TrackingService::class);
    }

    public function test_blocked_risk_levels_defaults_to_high_and_critical(): void
    {
        Setting::where('group', 'tracking')->where('key', 'purchase_block_risk_levels')->delete();
        $this->assertSame(['high', 'critical'], array_values($this->service()->blockedRiskLevels()));
    }

    public function test_should_report_purchase_blocks_critical_risk(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');
        $order = new EcommerceOrder(['fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 30]]]);
        $this->assertFalse($this->service()->shouldReportPurchase($order));
    }

    public function test_should_report_purchase_allows_low_risk(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');
        $order = new EcommerceOrder(['fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]]]);
        $this->assertTrue($this->service()->shouldReportPurchase($order));
    }

    public function test_should_report_purchase_allows_new_customer(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');
        $order = new EcommerceOrder(['fraud_report' => ['aggregate' => ['total_deliveries' => 0]]]);
        $this->assertTrue($this->service()->shouldReportPurchase($order));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`
Expected: FAIL ("Class TrackingService not found").

- [ ] **Step 3: Create the service**

```php
<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Http\Request;
use Modules\Ecommerce\Models\EcommerceOrder;
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
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/TrackingService.php Modules/Ecommerce/tests/Unit/TrackingServiceTest.php
git commit -m "feat(tracking): TrackingService settings getters + fraud gate"
```

---

## Task 3: TrackingService — item/content normalization + identifier capture + hashed user data

**Files:**
- Modify: `Modules/Ecommerce/app/Services/TrackingService.php`
- Modify: `Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`

- [ ] **Step 1: Write the failing tests** (append these methods to `TrackingServiceTest`)

```php
    public function test_items_from_order_use_numeric_unit_price(): void
    {
        $order = new EcommerceOrder();
        $order->setRelation('items', collect([
            new \Modules\Ecommerce\Models\EcommerceOrderItem([
                'product_id' => 7, 'product_name' => 'Tee', 'quantity' => 2, 'unit_price' => 499.00,
            ]),
        ]));

        $items = app(TrackingService::class)->itemsFromOrder($order);

        $this->assertSame('7', $items[0]['item_id']);
        $this->assertSame('Tee', $items[0]['item_name']);
        $this->assertSame(499.0, $items[0]['price']);
        $this->assertSame(2, $items[0]['quantity']);
    }

    public function test_contents_from_items_maps_to_meta_shape(): void
    {
        $contents = app(TrackingService::class)->contentsFromItems([
            ['item_id' => '7', 'item_name' => 'Tee', 'price' => 499.0, 'quantity' => 2],
        ]);

        $this->assertSame([['id' => '7', 'quantity' => 2, 'item_price' => 499.0]], $contents);
    }

    public function test_capture_client_identifiers_derives_fbc_from_fbclid(): void
    {
        $request = Request::create('/checkout?fbclid=abc123', 'POST');
        $request->headers->set('User-Agent', 'PHPUnit-UA');

        $data = app(TrackingService::class)->captureClientIdentifiers($request);

        $this->assertStringStartsWith('fb.1.', $data['fbc']);
        $this->assertStringEndsWith('.abc123', $data['fbc']);
        $this->assertSame('PHPUnit-UA', $data['ua']);
    }

    public function test_hashed_user_data_hashes_email_and_phone(): void
    {
        $order = new EcommerceOrder([
            'customer_email' => 'Test@Example.com',
            'customer_phone' => '01712-345678',
        ]);
        $order->tracking_data = ['fbp' => 'fb.1.1.2', 'ip' => '1.2.3.4'];

        $ud = app(TrackingService::class)->hashedUserData($order);

        $this->assertSame(hash('sha256', 'test@example.com'), $ud['em']);
        $this->assertSame(hash('sha256', '01712345678'), $ud['ph']);
        $this->assertSame('fb.1.1.2', $ud['fbp']);
        $this->assertSame('1.2.3.4', $ud['client_ip_address']);
    }
```

Add the import at the top of the test file: `use Illuminate\Http\Request;`

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`
Expected: FAIL ("Call to undefined method ...itemsFromOrder").

- [ ] **Step 3: Add the methods to `TrackingService`** (add `use Modules\Ecommerce\Models\EcommerceOrderItem;` and `use Modules\Product\Models\Product;` at top)

```php
    // ── Normalization ──
    public function itemFromProduct(Product $product, int $quantity = 1): array
    {
        $price = (float) $product->displayPrice()->effective;

        return [
            'item_id'       => (string) $product->id,
            'item_name'     => $product->name,
            'price'         => $price,
            'quantity'      => $quantity,
            'item_brand'    => $product->brand ?? null,
            'item_category' => optional($product->category)->name,
        ];
    }

    public function itemsFromOrder(EcommerceOrder $order): array
    {
        return $order->items->map(fn (EcommerceOrderItem $i) => [
            'item_id'   => (string) $i->product_id,
            'item_name' => $i->product_name,
            'price'     => (float) $i->unit_price,
            'quantity'  => (int) $i->quantity,
        ])->all();
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
            'fbp'           => $request->cookie('_fbp'),
            'fbc'           => $fbc,
            'ga_client_id'  => $this->parseGaCookie($request->cookie('_ga')),
            'ip'            => $request->ip(),
            'ua'            => (string) $request->userAgent(),
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`
Expected: PASS (8 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/TrackingService.php Modules/Ecommerce/tests/Unit/TrackingServiceTest.php
git commit -m "feat(tracking): item/content normalization, identifier capture, hashed user data"
```

---

## Task 4: Staff-exclusion gate + wire components into storefront layout

**Files:**
- Modify: `Modules/Core/resources/views/components/tracking-head.blade.php:9` and `:19` (wrap blocks)
- Modify: `Modules/Core/resources/views/components/tracking-body.blade.php:7` (wrap block)
- Modify: `Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php` (`<head>` ~L63, after `<body>` L65, before `@stack('scripts')` L181)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Setting\Models\Setting;
use Tests\TestCase;

class StorefrontTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('tracking', 'gtm_enabled', true, 'boolean');
        Setting::set('tracking', 'gtm_container_id', 'GTM-ABCD123');
        Setting::set('tracking', 'fbpixel_enabled', true, 'boolean');
        Setting::set('tracking', 'fbpixel_id', '1234567890');
    }

    public function test_storefront_home_includes_gtm_and_pixel_for_guests(): void
    {
        $res = $this->get(route('storefront.home'));
        $res->assertSee('googletagmanager.com/gtm.js', false);
        $res->assertSee('GTM-ABCD123', false);
        $res->assertSee("fbq('init', '1234567890')", false);
        $res->assertSee('js/tracking.js', false);
    }

    public function test_storefront_does_not_track_logged_in_staff(): void
    {
        $res = $this->actingAs($this->admin)->get(route('storefront.home'));
        $res->assertDontSee('googletagmanager.com/gtm.js', false);
        $res->assertDontSee("fbq('init'", false);
    }
}
```

> If the home route name differs, confirm with `php artisan route:list --name=storefront` and use the actual storefront landing route.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`
Expected: FAIL (snippets not present — layout doesn't include the components yet).

- [ ] **Step 3: Add the staff gate to the Core components**

In `tracking-head.blade.php`, change the opening of each `@if` so both blocks also require a non-staff visitor. Replace line 9 `@if($gtmEnabled && $gtmId)` with:

```blade
@if($gtmEnabled && $gtmId && !auth()->guard('web')->check())
```

Replace line 19 `@if($fbEnabled && $fbId)` with:

```blade
@if($fbEnabled && $fbId && !auth()->guard('web')->check())
```

In `tracking-body.blade.php`, replace line 7 `@if($gtmEnabled && $gtmId)` with:

```blade
@if($gtmEnabled && $gtmId && !auth()->guard('web')->check())
```

- [ ] **Step 4: Wire the components + tracking.js into the storefront layout**

In `master.blade.php`, add inside `<head>` just before `@stack('styles')` (line 63):

```blade
    <x-core::tracking-head />
```

Add immediately after `<body class="default_home">` (line 65):

```blade
    <x-core::tracking-body />
```

Add just before `@stack('scripts')` (line 181):

```blade
    <script src="{{ asset('js/tracking.js') }}"></script>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add Modules/Core/resources/views/components/tracking-head.blade.php Modules/Core/resources/views/components/tracking-body.blade.php Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php
git commit -m "feat(tracking): wire GTM/Pixel into storefront layout with staff exclusion"
```

---

## Task 5: Enhance the `BizPOS.track()` helper (GA4 ecommerce shape + eventID + advanced matching)

**Files:**
- Modify: `public/js/tracking.js` (full rewrite)

- [ ] **Step 1: Replace the file contents**

```js
'use strict';

/**
 * BizPOS Tracking Helper
 * Fires events to both the GTM dataLayer (GA4-shaped ecommerce) and Facebook Pixel.
 *
 * Usage:
 *   BizPOS.track('AddToCart', { value: 1190, currency: 'BDT', items: [...] },
 *                { fbData: { content_ids: ['7'], contents: [...], value: 1190, currency: 'BDT' } });
 */
window.BizPOS = window.BizPOS || {};

window.BizPOS.track = function (eventName, params, options) {
    params = params || {};
    options = options || {};

    var gtmEventMap = {
        'ViewContent': 'view_item',
        'ViewCategory': 'view_item_list',
        'Search': 'search',
        'AddToCart': 'add_to_cart',
        'RemoveFromCart': 'remove_from_cart',
        'ViewCart': 'view_cart',
        'InitiateCheckout': 'begin_checkout',
        'AddShippingInfo': 'add_shipping_info',
        'AddPaymentInfo': 'add_payment_info',
        'Purchase': 'purchase',
        'Lead': 'generate_lead',
        'AddToWishlist': 'add_to_wishlist',
        'AddToCompare': 'add_to_compare',
        'CompleteRegistration': 'sign_up',
        'Login': 'login',
        'SelectItem': 'select_item'
    };

    // ── GTM dataLayer (GA4 ecommerce shape) ──
    if (window.dataLayer) {
        var gtmEvent = options.gtmEvent || gtmEventMap[eventName] || eventName;
        // Clear the previous ecommerce object so events don't bleed into each other.
        window.dataLayer.push({ ecommerce: null });

        var payload = { event: gtmEvent };
        var ecommerce = {};
        if (params.currency) { ecommerce.currency = params.currency; }
        if (typeof params.value !== 'undefined') { ecommerce.value = params.value; }
        if (params.items) { ecommerce.items = params.items; }
        if (params.transaction_id) { ecommerce.transaction_id = params.transaction_id; }
        if (params.search_term) { payload.search_term = params.search_term; }
        if (Object.keys(ecommerce).length) { payload.ecommerce = ecommerce; }

        window.dataLayer.push(payload);
    }

    // ── Facebook Pixel ──
    if (typeof fbq === 'function') {
        var fbEvent = options.fbEvent || eventName;
        var customEvents = ['RemoveFromCart', 'ViewCart', 'AddToCompare', 'Login', 'SelectItem'];
        var isCustom = options.fbCustom || customEvents.indexOf(eventName) !== -1;
        var fbData = options.fbData || {};
        var fbOpts = options.eventID ? { eventID: options.eventID } : undefined;

        if (isCustom) {
            fbq('trackCustom', fbEvent, fbData, fbOpts);
        } else {
            fbq('track', fbEvent, fbData, fbOpts);
        }
    }
};
```

- [ ] **Step 2: Verify syntax**

Run: `node --check public/js/tracking.js`
Expected: no output (valid).

- [ ] **Step 3: Commit**

```bash
git add public/js/tracking.js
git commit -m "feat(tracking): GA4 ecommerce dataLayer shape + eventID + custom-event map"
```

---

# PHASE 2 — Browser events

## Task 6: `track-event` Blade partial

**Files:**
- Create: `Modules/Ecommerce/resources/views/storefront/partials/track-event.blade.php`

- [ ] **Step 1: Create the partial**

```blade
{{-- Emits a single BizPOS.track() call for a server-rendered event.
     Params: $event (string FB-style name), $ga (array GA4 params),
             $fb (array ['data'=>[...]]), $eventId (optional string). --}}
@php
    $ga = $ga ?? [];
    $fb = $fb ?? [];
    $eventId = $eventId ?? null;
@endphp
<script>
'use strict';
document.addEventListener('DOMContentLoaded', function () {
    if (!window.BizPOS || typeof window.BizPOS.track !== 'function') { return; }
    window.BizPOS.track(
        @json($event),
        @json($ga),
        {
            fbData: @json($fb['data'] ?? []),
            @if($eventId) eventID: @json($eventId) @endif
        }
    );
});
</script>
```

- [ ] **Step 2: Verify it renders without error**

Add a temporary include to any storefront page or rely on Task 7's test. No standalone test for the partial; it is exercised by Tasks 7–9 feature tests.

- [ ] **Step 3: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/partials/track-event.blade.php
git commit -m "feat(tracking): add server-rendered track-event partial"
```

---

## Task 7: `view_item` on the product detail page

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/shop/show.blade.php` (append inside `@push('scripts')`; if none exists, add one at the end)
- Modify: `Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`

- [ ] **Step 1: Write the failing test** (append to `StorefrontTrackingTest`)

```php
    public function test_product_detail_fires_view_item(): void
    {
        $product = \Modules\Product\Models\Product::query()->first();
        $this->assertNotNull($product, 'Seed at least one product for this test.');

        $res = $this->get(route('storefront.shop.show', $product->slug));
        $res->assertSee('"ViewContent"', false);
        $res->assertSee('view_item', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`
Expected: FAIL on the new test.

- [ ] **Step 3: Add the include**

In `shop/show.blade.php`, inside the page's `@push('scripts')` section (the view already computes `$price = $product->displayPrice()`), add:

```blade
@include('ecommerce::storefront.partials.track-event', [
    'event' => 'ViewContent',
    'ga' => [
        'currency' => 'BDT',
        'value' => (float) $product->displayPrice()->effective,
        'items' => [ app(\Modules\Ecommerce\Services\TrackingService::class)->itemFromProduct($product) ],
    ],
    'fb' => ['data' => [
        'content_type' => 'product',
        'content_ids' => [ (string) $product->id ],
        'value' => (float) $product->displayPrice()->effective,
        'currency' => 'BDT',
    ]],
])
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/pages/shop/show.blade.php Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php
git commit -m "feat(tracking): view_item / ViewContent on product detail"
```

---

## Task 8: `view_item_list` + `search` on shop & category lists

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/shop/index.blade.php`
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/category/show.blade.php` (confirm exact path with `php artisan route:list --name=storefront.category.show`)

- [ ] **Step 1: Add the list include to `shop/index.blade.php`** (inside `@push('scripts')`)

```blade
@php
    $bpTracking = app(\Modules\Ecommerce\Services\TrackingService::class);
    $bpItems = $products->map(fn ($p) => $bpTracking->itemFromProduct($p))->values()->all();
@endphp
@include('ecommerce::storefront.partials.track-event', [
    'event' => 'ViewCategory',
    'ga' => ['item_list_name' => 'shop', 'items' => $bpItems],
    'fb' => ['data' => []],
])
@if(request('q'))
@include('ecommerce::storefront.partials.track-event', [
    'event' => 'Search',
    'ga' => ['search_term' => request('q')],
    'fb' => ['data' => ['search_string' => request('q')]],
])
@endif
```

- [ ] **Step 2: Add the list include to `category/show.blade.php`** (inside `@push('scripts')`)

```blade
@php
    $bpTracking = app(\Modules\Ecommerce\Services\TrackingService::class);
    $bpItems = $products->map(fn ($p) => $bpTracking->itemFromProduct($p))->values()->all();
@endphp
@include('ecommerce::storefront.partials.track-event', [
    'event' => 'ViewCategory',
    'ga' => ['item_list_name' => $category->name ?? 'category', 'items' => $bpItems],
    'fb' => ['data' => []],
])
```

- [ ] **Step 3: Verify manually**

Run the dev server (`php artisan serve`), open `/shop` and `/shop?q=shirt`, and confirm in the browser console: `dataLayer` contains a `view_item_list` event, and for the search URL a `search` event. (No automated test — list views depend on seeded catalog.)

- [ ] **Step 4: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/pages/shop/index.blade.php Modules/Ecommerce/resources/views/storefront/pages/category/show.blade.php
git commit -m "feat(tracking): view_item_list + search on shop & category lists"
```

---

## Task 9: `view_cart` + `begin_checkout`

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/cart/index.blade.php`
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/checkout/index.blade.php`

- [ ] **Step 1: Add `view_cart` to `cart/index.blade.php`** (inside `@push('scripts')`; `$cartItems`, `$cartTotal` are in scope)

```blade
@php
    $bpItems = collect($cartItems)->map(fn ($i) => [
        'item_id' => (string) $i['product_id'],
        'item_name' => $i['name'],
        'price' => (float) $i['price'],
        'quantity' => (int) $i['quantity'],
    ])->values()->all();
@endphp
@include('ecommerce::storefront.partials.track-event', [
    'event' => 'ViewCart',
    'ga' => ['currency' => 'BDT', 'value' => (float) $cartTotal, 'items' => $bpItems],
    'fb' => ['data' => []],
])
```

- [ ] **Step 2: Add `begin_checkout` to `checkout/index.blade.php`** (inside `@push('scripts')`; `$cartItems`, `$cartTotal` in scope)

```blade
@php
    $bpItems = collect($cartItems)->map(fn ($i) => [
        'item_id' => (string) $i['product_id'],
        'item_name' => $i['name'],
        'price' => (float) $i['price'],
        'quantity' => (int) $i['quantity'],
    ])->values()->all();
    $bpContentIds = array_map(fn ($i) => $i['item_id'], $bpItems);
    $bpContents = array_map(fn ($i) => ['id' => $i['item_id'], 'quantity' => $i['quantity'], 'item_price' => $i['price']], $bpItems);
@endphp
@include('ecommerce::storefront.partials.track-event', [
    'event' => 'InitiateCheckout',
    'ga' => ['currency' => 'BDT', 'value' => (float) $cartTotal, 'items' => $bpItems],
    'fb' => ['data' => [
        'content_type' => 'product',
        'content_ids' => $bpContentIds,
        'contents' => $bpContents,
        'num_items' => count($bpItems),
        'value' => (float) $cartTotal,
        'currency' => 'BDT',
    ]],
])
```

- [ ] **Step 3: Verify manually**

Open `/cart` and `/checkout` with items in cart; confirm `view_cart` and `begin_checkout`/`InitiateCheckout` appear in `dataLayer` and Meta Pixel Helper.

- [ ] **Step 4: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/pages/cart/index.blade.php Modules/Ecommerce/resources/views/storefront/pages/checkout/index.blade.php
git commit -m "feat(tracking): view_cart + begin_checkout server events"
```

---

## Task 10: Product `data-*` attributes for client-side events

**Files:**
- Modify: the storefront product-card partial (find with `grep -rl "add-to-cart" Modules/Ecommerce/resources/views/storefront/partials`)
- Modify: the buy-now modal partial (`Modules/Ecommerce/resources/views/storefront/partials/buy-now-modal.blade.php`)

- [ ] **Step 1: Add data attributes to the add-to-cart / wishlist / compare buttons**

On each product card's action button(s) that already carry `data-product-id`, add the analytics attributes alongside (use the card's `$product`/`$item` variable; for a card loop variable named `$product`):

```blade
data-id="{{ $product->id }}"
data-sku="{{ $product->sku }}"
data-name="{{ $product->name }}"
data-price="{{ (float) $product->displayPrice()->effective }}"
data-category="{{ optional($product->category)->name }}"
data-brand="{{ $product->brand }}"
```

> Use the card's actual loop variable. If the card receives `$item` (array), use `$item['id']`, `$item['name']`, `$item['price']` etc. accordingly.

- [ ] **Step 2: Verify**

Run: `grep -rn 'data-price' Modules/Ecommerce/resources/views/storefront/partials`
Expected: the product-card partial now emits `data-price` (numeric).

- [ ] **Step 3: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/partials
git commit -m "feat(tracking): add analytics data-* attributes to product cards"
```

---

## Task 11: `cart.js` interaction hooks (add_to_cart, order-now, wishlist, compare, remove)

**Files:**
- Modify: `public/website/assets/js/cart.js` (add `BizPOS.track()` calls in the existing AJAX success callbacks)

- [ ] **Step 1: Add a small helper at the top of cart.js** (after `'use strict';`)

```js
// Build an analytics item payload from a button's data-* attributes.
function bpItemFromEl(el) {
    var $el = $(el);
    return {
        item_id: String($el.data('id') || $el.data('product-id') || ''),
        item_name: $el.data('name') || '',
        price: Number($el.data('price')) || 0,
        item_brand: $el.data('brand') || undefined,
        item_category: $el.data('category') || undefined,
        quantity: 1
    };
}
```

- [ ] **Step 2: Fire `AddToCart` in the `Cart.add` success callback**

Inside the `success:` callback of the `/cart/add` AJAX (where `data.success` is handled), after the existing UI update, add:

```js
if (data.success && window.BizPOS) {
    var item = bpItemFromEl(triggerEl); // triggerEl = the button that initiated the add
    BizPOS.track('AddToCart',
        { currency: 'BDT', value: item.price * item.quantity, items: [item] },
        { fbData: { content_type: 'product', content_ids: [item.item_id], contents: [{ id: item.item_id, quantity: item.quantity, item_price: item.price }], value: item.price * item.quantity, currency: 'BDT' } });
}
```

> Ensure the initiating element is in scope as `triggerEl`. If `Cart.add(productId, qty, variantId)` doesn't receive the element, capture it in the click handler and pass it through, or look it up via `$('[data-product-id="' + productId + '"]')`.

- [ ] **Step 3: Fire wishlist / compare / order-now**

In `Wishlist.add` success:

```js
if (data.success && window.BizPOS) {
    var w = bpItemFromEl(triggerEl);
    BizPOS.track('AddToWishlist',
        { currency: 'BDT', value: w.price, items: [w] },
        { fbData: { content_ids: [w.item_id], content_type: 'product', value: w.price, currency: 'BDT' } });
}
```

In `Compare.add` success:

```js
if (data.success && window.BizPOS) {
    BizPOS.track('AddToCompare', { items: [bpItemFromEl(triggerEl)] }, {});
}
```

In `BuyNow.confirmAndCheckout` (order-now), right before redirecting to checkout:

```js
if (window.BizPOS) {
    var b = { item_id: String(productId), price: Number(unitPrice) || 0, quantity: Number(qty) || 1 };
    BizPOS.track('AddToCart', { currency: 'BDT', value: b.price * b.quantity, items: [b] },
        { fbData: { content_type: 'product', content_ids: [b.item_id], value: b.price * b.quantity, currency: 'BDT' } });
    BizPOS.track('InitiateCheckout', { currency: 'BDT', value: b.price * b.quantity, items: [b] },
        { fbData: { content_type: 'product', content_ids: [b.item_id], num_items: b.quantity, value: b.price * b.quantity, currency: 'BDT' } });
}
```

In the cart remove success callback:

```js
if (data.success && window.BizPOS) {
    BizPOS.track('RemoveFromCart', { items: [{ item_id: String(productId), quantity: 1 }] }, {});
}
```

- [ ] **Step 4: Verify syntax + behavior**

Run: `node --check public/website/assets/js/cart.js`
Expected: valid. Then manually add to cart / wishlist / order-now and confirm events in `dataLayer` + Meta Pixel Helper.

- [ ] **Step 5: Commit**

```bash
git add public/website/assets/js/cart.js
git commit -m "feat(tracking): add_to_cart/wishlist/compare/order-now/remove hooks in cart.js"
```

---

## Task 12: Checkout JS — `add_shipping_info` + `add_payment_info`

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/checkout/index.blade.php` (inside `@push('scripts')`)

- [ ] **Step 1: Add change handlers**

```blade
<script>
'use strict';
$(function () {
    var bpCheckoutValue = {{ (float) $cartTotal }};
    $('#zoneSelect').on('change', function () {
        if (window.BizPOS) {
            BizPOS.track('AddShippingInfo', { currency: 'BDT', value: bpCheckoutValue }, { fbData: {} });
        }
    });
    $('input[name="payment_method"]').on('change', function () {
        if (window.BizPOS) {
            BizPOS.track('AddPaymentInfo',
                { currency: 'BDT', value: bpCheckoutValue },
                { fbData: { value: bpCheckoutValue, currency: 'BDT' } });
        }
    });
});
</script>
```

> Confirm the shipping `<select>` id (`#zoneSelect`) and the payment radio name (`payment_method`) match the checkout markup; adjust selectors if different.

- [ ] **Step 2: Verify manually**

On `/checkout`, change the shipping zone and select a payment method; confirm `add_shipping_info` and `add_payment_info`/`AddPaymentInfo` fire.

- [ ] **Step 3: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/pages/checkout/index.blade.php
git commit -m "feat(tracking): add_shipping_info + add_payment_info on checkout"
```

---

## Task 13: `sign_up`, `login`, newsletter `generate_lead`

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CustomerAuthController.php` (`login()` ~L74, `register()`)
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/NewsletterController.php` (`subscribe()` ~L22-30)
- Modify: `Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php` (read the flash once)

- [ ] **Step 1: Flash a tracking event from the controllers**

In `CustomerAuthController::login()`, on success before redirect:

```php
session()->flash('bp_track', ['event' => 'Login', 'ga' => ['method' => 'password'], 'fb' => []]);
```

In `CustomerAuthController::register()`, on success before redirect:

```php
session()->flash('bp_track', ['event' => 'CompleteRegistration', 'ga' => [], 'fb' => ['data' => []]]);
```

In `NewsletterController::subscribe()`, after a successful subscribe before redirect back:

```php
session()->flash('bp_track', ['event' => 'Lead', 'ga' => [], 'fb' => ['data' => ['content_name' => 'newsletter']]]);
```

- [ ] **Step 2: Render the flashed event in the layout**

In `master.blade.php`, just before `<script src="{{ asset('js/tracking.js') }}">` add:

```blade
@if(session('bp_track'))
@php($bpFlash = session('bp_track'))
@include('ecommerce::storefront.partials.track-event', [
    'event' => $bpFlash['event'],
    'ga' => $bpFlash['ga'] ?? [],
    'fb' => $bpFlash['fb'] ?? [],
])
@endif
```

> `track-event.blade.php` runs on DOMContentLoaded and `tracking.js` is loaded right after — the helper is defined before the DOM-ready callback runs, so ordering is safe.

- [ ] **Step 3: Verify manually**

Register a new storefront customer, log in, and subscribe to the newsletter; confirm `sign_up`/`CompleteRegistration`, `login`, and `generate_lead`/`Lead` fire on the page loaded after each redirect.

- [ ] **Step 4: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/CustomerAuthController.php Modules/Ecommerce/app/Http/Controllers/Storefront/NewsletterController.php Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php
git commit -m "feat(tracking): sign_up / login / newsletter lead via session flash"
```

---

# PHASE 3 — Checkout capture

## Task 14: Migration + model for `tracking_data` and `purchase_reported_at`

**Files:**
- Create: `Modules/Ecommerce/database/migrations/2026_06_13_000001_add_tracking_columns_to_ecommerce_orders_table.php`
- Modify: `Modules/Ecommerce/app/Models/EcommerceOrder.php:16-37`
- Test: `Modules/Ecommerce/tests/Unit/TrackingServiceTest.php` (add a model cast assertion)

- [ ] **Step 1: Write the failing test** (append to `TrackingServiceTest`)

```php
    public function test_order_casts_tracking_data_to_array(): void
    {
        $order = new EcommerceOrder();
        $order->tracking_data = ['fbp' => 'x'];
        $this->assertIsArray($order->tracking_data);
        $this->assertArrayHasKey('purchase_reported_at', $order->getCasts());
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php --filter test_order_casts_tracking_data_to_array`
Expected: FAIL (`purchase_reported_at` not in casts).

- [ ] **Step 3: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->json('tracking_data')->nullable()->after('fraud_score');
            $table->timestamp('purchase_reported_at')->nullable()->after('tracking_data');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn(['tracking_data', 'purchase_reported_at']);
        });
    }
};
```

- [ ] **Step 4: Update the model**

In `EcommerceOrder::$fillable`, add `'tracking_data', 'purchase_reported_at'` (e.g. after `'fraud_score',` on line 23). In `$casts`, add:

```php
        'tracking_data' => 'array',
        'purchase_reported_at' => 'datetime',
```

- [ ] **Step 5: Migrate + run test**

Run: `php artisan migrate` (dev DB) and `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php --filter test_order_casts_tracking_data_to_array`
Expected: migration applies; test PASSES. (The test DB picks up the migration on its next refresh; if the suite runs against a persistent DB, run `php artisan migrate --env=testing --database=mysql` against `bizpos_test`.)

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/database/migrations/2026_06_13_000001_add_tracking_columns_to_ecommerce_orders_table.php Modules/Ecommerce/app/Models/EcommerceOrder.php Modules/Ecommerce/tests/Unit/TrackingServiceTest.php
git commit -m "feat(tracking): add tracking_data + purchase_reported_at to ecommerce_orders"
```

---

## Task 15: Capture identifiers at checkout

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php:272-283` (in `process()`, near the fraud-check block)

- [ ] **Step 1: Store captured identifiers on the order**

In `process()`, immediately after the order is created (`$order = $this->storefrontService->createOrder(...)`, ~L264-270) and alongside the existing fraud-check update, add:

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

> `process()` already receives the `Request $request`. Confirm the parameter name.

- [ ] **Step 2: Verify manually**

Place a test order (optionally append `?fbclid=test123` to a storefront URL first so a `_fbc` is derivable), then in tinker:

```bash
php artisan tinker --execute="echo \Modules\Ecommerce\Models\EcommerceOrder::latest('id')->first()->tracking_data ? 'has-data' : 'empty';"
```
Expected: `has-data` with `ip`/`ua` populated.

- [ ] **Step 3: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php
git commit -m "feat(tracking): capture fbp/fbc/ga_client_id/ip/ua at checkout"
```

---

# PHASE 4 — Deferred Purchase (server-side)

## Task 16: `SendFbCapiEvent` job

**Files:**
- Create: `Modules/Ecommerce/app/Jobs/SendFbCapiEvent.php`
- Test: `Modules/Ecommerce/tests/Unit/SendFbCapiEventTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Ecommerce\Jobs\SendFbCapiEvent;
use Tests\TestCase;

class SendFbCapiEventTest extends TestCase
{
    public function test_posts_purchase_payload_to_graph_api(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);

        (new SendFbCapiEvent(
            pixelId: '1234567890',
            token: 'TOKEN',
            eventName: 'Purchase',
            eventId: 'purchase.ORD-1',
            customData: ['value' => 1500.0, 'currency' => 'BDT', 'order_id' => 'ORD-1'],
            userData: ['em' => 'hashed', 'fbp' => 'fb.1.1.2'],
            eventSourceUrl: 'https://shop.test/checkout/success/ORD-1',
            testEventCode: 'TEST123'
        ))->handle();

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($request->url(), '/1234567890/events')
                && $body['data'][0]['event_name'] === 'Purchase'
                && $body['data'][0]['event_id'] === 'purchase.ORD-1'
                && $body['data'][0]['user_data']['em'] === 'hashed'
                && $body['data'][0]['custom_data']['currency'] === 'BDT'
                && $body['test_event_code'] === 'TEST123';
        });
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/SendFbCapiEventTest.php`
Expected: FAIL ("Class SendFbCapiEvent not found").

- [ ] **Step 3: Create the job**

```php
<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFbCapiEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $pixelId,
        public string $token,
        public string $eventName,
        public string $eventId,
        public array $customData,
        public array $userData,
        public ?string $eventSourceUrl = null,
        public ?string $testEventCode = null,
    ) {}

    public function handle(): void
    {
        $payload = [
            'data' => [[
                'event_name'      => $this->eventName,
                'event_time'      => time(),
                'event_id'        => $this->eventId,
                'action_source'   => 'website',
                'event_source_url'=> $this->eventSourceUrl,
                'user_data'       => $this->userData,
                'custom_data'     => $this->customData,
            ]],
        ];
        if ($this->testEventCode) {
            $payload['test_event_code'] = $this->testEventCode;
        }

        $res = Http::asJson()->post(
            "https://graph.facebook.com/v19.0/{$this->pixelId}/events?access_token={$this->token}",
            $payload
        );

        if ($res->failed()) {
            Log::warning('FB CAPI send failed', ['status' => $res->status(), 'event_id' => $this->eventId]);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/SendFbCapiEventTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Jobs/SendFbCapiEvent.php Modules/Ecommerce/tests/Unit/SendFbCapiEventTest.php
git commit -m "feat(tracking): SendFbCapiEvent queued Conversions API job"
```

---

## Task 17: `SendGa4McEvent` job

**Files:**
- Create: `Modules/Ecommerce/app/Jobs/SendGa4McEvent.php`
- Test: `Modules/Ecommerce/tests/Unit/SendGa4McEventTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Ecommerce\Jobs\SendGa4McEvent;
use Tests\TestCase;

class SendGa4McEventTest extends TestCase
{
    public function test_posts_purchase_to_measurement_protocol(): void
    {
        Http::fake(['www.google-analytics.com/*' => Http::response('', 204)]);

        (new SendGa4McEvent(
            measurementId: 'G-ABC123',
            apiSecret: 'SECRET',
            clientId: '111.222',
            params: ['transaction_id' => 'ORD-1', 'value' => 1500.0, 'currency' => 'BDT', 'items' => []],
        ))->handle();

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($request->url(), 'measurement_id=G-ABC123')
                && str_contains($request->url(), 'api_secret=SECRET')
                && $body['client_id'] === '111.222'
                && $body['events'][0]['name'] === 'purchase'
                && $body['events'][0]['params']['transaction_id'] === 'ORD-1';
        });
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/SendGa4McEventTest.php`
Expected: FAIL ("Class SendGa4McEvent not found").

- [ ] **Step 3: Create the job**

```php
<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendGa4McEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $measurementId,
        public string $apiSecret,
        public string $clientId,
        public array $params,
    ) {}

    public function handle(): void
    {
        $res = Http::asJson()->post(
            "https://www.google-analytics.com/mp/collect?measurement_id={$this->measurementId}&api_secret={$this->apiSecret}",
            [
                'client_id' => $this->clientId,
                'events'    => [[ 'name' => 'purchase', 'params' => $this->params ]],
            ]
        );

        if ($res->failed()) {
            Log::warning('GA4 MP send failed', ['status' => $res->status(), 'tid' => $this->params['transaction_id'] ?? null]);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/SendGa4McEventTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Jobs/SendGa4McEvent.php Modules/Ecommerce/tests/Unit/SendGa4McEventTest.php
git commit -m "feat(tracking): SendGa4McEvent queued Measurement Protocol job"
```

---

## Task 18: `reportPurchase()` + dispatch from `updateOrderStatus()` (fraud-gated, once-guarded)

**Files:**
- Modify: `Modules/Ecommerce/app/Services/TrackingService.php` (add `reportPurchase()`)
- Modify: `Modules/Ecommerce/app/Services/EcommerceService.php:96-98` (call it inside `updateOrderStatus()`)
- Test: `Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`

- [ ] **Step 1: Write the failing tests** (append to `TrackingServiceTest`; add imports `use Illuminate\Support\Facades\Bus;` and the job classes)

```php
    public function test_report_purchase_dispatches_both_jobs_for_trusted_order(): void
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

        Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
        Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class);
        $this->assertNotNull($order->fresh()->purchase_reported_at);
    }

    public function test_report_purchase_suppressed_for_high_risk_but_guard_set(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-T2', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'confirmed',
            'subtotal' => 1500, 'grand_total' => 1500,
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 20]],
        ]);

        app(TrackingService::class)->reportPurchase($order);

        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
        $this->assertNotNull($order->fresh()->purchase_reported_at);
    }

    public function test_report_purchase_is_idempotent(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-T3', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'confirmed',
            'subtotal' => 1500, 'grand_total' => 1500, 'purchase_reported_at' => now(),
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]],
        ]);

        app(TrackingService::class)->reportPurchase($order);

        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php --filter test_report_purchase`
Expected: FAIL ("Call to undefined method ...reportPurchase").

- [ ] **Step 3: Add `reportPurchase()` to `TrackingService`** (add `use Modules\Ecommerce\Jobs\SendFbCapiEvent;` and `use Modules\Ecommerce\Jobs\SendGa4McEvent;`)

```php
    /**
     * Report a Purchase server-side to Meta (CAPI) and GA4 (MP).
     * Fires at most once per order (purchase_reported_at guard) and only for
     * orders that pass the fraud gate. The guard is set even when suppressed so
     * the decision is final and never re-evaluated on later status changes.
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

        // GA4 Measurement Protocol
        $clientId = ((array) ($order->tracking_data ?? []))['ga_client_id'] ?? null;
        if ($this->ga4MeasurementId() && $this->ga4ApiSecret()) {
            SendGa4McEvent::dispatch(
                measurementId: $this->ga4MeasurementId(),
                apiSecret: $this->ga4ApiSecret(),
                clientId: $clientId ?: ('srv.' . $order->id),
                params: [
                    'transaction_id' => $order->order_number,
                    'value'          => (float) $order->grand_total,
                    'currency'       => 'BDT',
                    'shipping'       => (float) $order->shipping_charge,
                    'tax'            => (float) $order->tax_amount,
                    'coupon'         => $order->coupon_code,
                    'items'          => $items,
                ],
            );
        }
    }

    public function eventId(string $seed): string
    {
        return $seed;
    }
```

- [ ] **Step 4: Call it from `EcommerceService::updateOrderStatus()`**

In `EcommerceService.php`, immediately before `return $order->fresh();` (line 98), add:

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

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`
Expected: PASS (all tests, including the three `reportPurchase` cases).

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/app/Services/TrackingService.php Modules/Ecommerce/app/Services/EcommerceService.php Modules/Ecommerce/tests/Unit/TrackingServiceTest.php
git commit -m "feat(tracking): deferred fraud-gated Purchase dispatch on order confirmation"
```

---

# PHASE 5 — Cleanup & QA

## Task 19: Remove stale doc + full regression + manual QA

**Files:**
- Delete: `docs/STOREFRONT_TRACKING_PLAN.md`

- [ ] **Step 1: Delete the superseded plan doc**

```bash
git rm docs/STOREFRONT_TRACKING_PLAN.md
```

- [ ] **Step 2: Run the full tracking test suite**

Run:
```bash
php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php Modules/Ecommerce/tests/Unit/SendFbCapiEventTest.php Modules/Ecommerce/tests/Unit/SendGa4McEventTest.php Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php Modules/Setting/tests/Feature/TrackingSettingsTest.php
```
Expected: all PASS.

- [ ] **Step 3: Manual QA checklist** (dev server + real GTM/Pixel test IDs in Settings → Tracking)

- [ ] GTM Tag Assistant (Preview): `page_view`, `view_item`, `view_item_list`, `search`, `add_to_cart`, `view_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info` appear with correct `ecommerce.value`/`items`.
- [ ] Meta Pixel Helper: `PageView`, `ViewContent`, `AddToCart`, `InitiateCheckout`, `AddPaymentInfo`, `Lead`, `CompleteRegistration` fire with `content_ids` + `value` + `currency: BDT`.
- [ ] Confirm **no** tracking scripts load when logged in as admin (view source on a storefront page while authenticated to the admin panel).
- [ ] Set a Meta Test Event Code; mark a **low-risk** order `confirmed` in admin; confirm a `Purchase` appears in Meta **Test Events** and a `purchase` in **GA4 DebugView**, with `value`/`currency`/`contents`.
- [ ] Mark a **high-risk** order (success_ratio < 60) `confirmed`; confirm **no** `Purchase` reaches Meta or GA4, and `purchase_reported_at` is set.
- [ ] Re-save/re-confirm the same order; confirm the `Purchase` does **not** fire twice.
- [ ] Confirm prices in every event are raw numbers (e.g. `1499`), never `"BDT 1,499"`.
- [ ] Confirm queue worker is running in production (`php artisan queue:work`) so CAPI/GA4 jobs send.

- [ ] **Step 4: Document the campaign requirement**

Add a one-line note to the team runbook / README: *"Meta campaigns must optimize for the **Purchase** event for COD fraud gating to protect budget — do not optimize for AddToCart/InitiateCheckout."*

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "chore(tracking): remove superseded plan doc + QA pass"
```

---

## Self-Review notes (addressed)

- **Spec coverage:** settings (Task 1), TrackingService normalizer/gate (Tasks 2-3), staff gate + layout wiring (Task 4), helper enhancements (Task 5), all browser events (Tasks 6-13), capture columns + capture (Tasks 14-15), CAPI + GA4 MP jobs (Tasks 16-17), deferred fraud-gated once-guarded Purchase (Task 18), stale-doc deletion + QA (Task 19). All spec sections map to a task.
- **Type consistency:** `itemFromProduct`/`itemsFromOrder`/`contentsFromItems`/`captureClientIdentifiers`/`hashedUserData`/`shouldReportPurchase`/`blockedRiskLevels`/`reportPurchase`/`eventId` are defined once in `TrackingService` (Tasks 2-3, 18) and referenced consistently. Job constructor argument names match their dispatch call sites (Tasks 16-18).
