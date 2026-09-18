# GA4 Ecommerce via Direct gtag.js — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Full GA4 ecommerce event coverage on the storefront via a direct gtag.js loader (zero GTM container config), plus the missing events (view_item, view_item_list, select_item, search, promotions, remove_from_wishlist) and a server-side refund event.

**Architecture:** Every browser event flows through `window.BizPOS.track()` (`public/js/tracking.js`), which fans out to GTM dataLayer + Facebook Pixel (existing) + gtag.js (new). Server-side events (purchase, new refund) go through `Modules\Ecommerce\Services\TrackingService` + the queued `SendGa4McEvent` job (Measurement Protocol).

**Tech Stack:** Laravel 12 modules (nwidart), Blade, jQuery 3.7.1, PHPUnit via `php artisan test`.

**Spec:** `docs/superpowers/specs/2026-07-05-ga4-gtag-ecommerce-design.md`

## Global Constraints

- All JS starts with `'use strict';` (already present in the files being modified — do not remove).
- No inline `style=` attributes; no CDN links except the Google-hosted gtag.js/GTM loaders (they cannot be self-hosted; CSP already whitelists them).
- Blade: `{{ }}` escaping everywhere; named routes only.
- Currency literal is always `'BDT'`; event values are raw numbers, never formatted strings.
- Tests run with `php artisan test <path>` from the repo root. NOTE: this suite runs against the dev database; each test file shown here follows the existing patterns in the same directories.
- Two tests in `StorefrontTrackingTest` are ALREADY FAILING before this work (`test_product_detail_fires_view_item`, `test_shop_list_fires_view_item_list`). They must pass by the end (Tasks 4–5). Don't chase them in earlier tasks.
- Commit after every task with the message given in the task.

---

### Task 1: `ga4_enabled` setting + Google Analytics 4 settings card

**Files:**
- Modify: `Modules/Setting/app/Services/SettingService.php:324` (updateTracking boolean list)
- Modify: `Modules/Setting/resources/views/index.blade.php:1777-1794` (move GA4 fields into own card), `:1686-1688` (section desc), `~:2615` (toggle-sync JS)
- Test: `Modules/Setting/tests/Feature/TrackingSettingsTest.php`

**Interfaces:**
- Produces: tracking settings key `ga4_enabled` (boolean) saved via `Setting::get('tracking', 'ga4_enabled', false)`. Task 2 reads it.

- [ ] **Step 1: Write the failing test** — append to `TrackingSettingsTest`:

```php
    public function test_saves_ga4_enabled_toggle(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('settings.update', 'tracking'), [
            'ga4_enabled'        => '1',
            'ga4_measurement_id' => 'G-ABCDE12345',
        ])->assertSessionHasNoErrors();

        $this->assertTrue((bool) Setting::get('tracking', 'ga4_enabled', false));

        // Unchecked checkbox (absent from payload) must round-trip to false.
        $this->put(route('settings.update', 'tracking'), [
            'ga4_measurement_id' => 'G-ABCDE12345',
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) Setting::get('tracking', 'ga4_enabled', false));
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Setting/tests/Feature/TrackingSettingsTest.php --filter test_saves_ga4_enabled_toggle`
Expected: FAIL — `Setting::get('tracking','ga4_enabled')` stays false because `updateTracking` doesn't include the key.

- [ ] **Step 3: Add the boolean to `SettingService::updateTracking()`** — change line 324:

```php
        $this->updateGroupWithBooleans('tracking', $data, ['gtm_enabled', 'fbpixel_enabled', 'ga4_enabled']);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test Modules/Setting/tests/Feature/TrackingSettingsTest.php`
Expected: all tests PASS.

- [ ] **Step 5: Restructure the settings UI.** In `Modules/Setting/resources/views/index.blade.php`:

(a) Update the section description (line 1687-1688):

```blade
                            <p class="bp-settings-section-desc">Configure Google Tag Manager, Facebook Pixel and
                                Google Analytics 4 for conversion tracking across your storefront and landing pages.</p>
```

(b) DELETE the two GA4 fields from the Facebook Pixel card (lines 1777-1794 — the two `col-md-6` divs for "GA4 Measurement ID" and "GA4 API Secret").

(c) INSERT a new card between the Facebook Pixel card's closing `</div>` (after the card-footer at line ~1801, but the save button footer must move to the new last card — see (d)) and `</form>`:

```blade
                        <!-- Google Analytics 4 -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-chart-line me-2"></i>Google Analytics 4</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Enable GA4</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="ga4_enabled"
                                                id="ga4Enabled"
                                                {{ $settings['tracking']['ga4_enabled'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ga4Enabled">Active</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="bp-form-label">GA4 Measurement ID</label>
                                        <input type="text"
                                            class="bp-form-control @error('ga4_measurement_id') is-invalid @enderror"
                                            name="ga4_measurement_id"
                                            value="{{ old('ga4_measurement_id', $settings['tracking']['ga4_measurement_id'] ?? '') }}"
                                            placeholder="G-XXXXXXX">
                                        @error('ga4_measurement_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">GA4 API Secret <span
                                                class="text-muted">(server-side purchase & refund)</span></label>
                                        <input type="password" class="bp-form-control" name="ga4_api_secret"
                                            value="{{ $settings['tracking']['ga4_api_secret'] ?? '' }}"
                                            placeholder="••••••••">
                                    </div>
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save Tracking Settings</button>
                            </div>
                        </div>
```

(d) REMOVE the old `bp-card-footer` (Save button, lines ~1797-1800) from the Facebook Pixel card — the save button now lives on the GA4 card (the last card in the form).

(e) In the toggle-sync JS near line 2615, add:

```js
            sync('ga4_enabled', ['ga4_measurement_id', 'ga4_api_secret']);
```

- [ ] **Step 6: Verify the page renders**

Run: `php artisan test Modules/Setting/tests/Feature/TrackingSettingsTest.php` (still green), then start the dev server if not running and check the tracking settings tab returns 200:
`curl -s -b /tmp/bz.txt http://127.0.0.1:8000/admin/settings -o /dev/null -w "%{http_code}"` → `200`
(Login first per CLAUDE.md §20 if the cookie jar is stale.)

- [ ] **Step 7: Commit**

```bash
git add Modules/Setting/app/Services/SettingService.php Modules/Setting/resources/views/index.blade.php Modules/Setting/tests/Feature/TrackingSettingsTest.php
git commit -m "feat(tracking): ga4_enabled toggle + dedicated GA4 settings card"
```

---

### Task 2: gtag.js loader in tracking-head + `TrackingService::ga4Enabled()`

**Files:**
- Modify: `Modules/Core/resources/views/components/tracking-head.blade.php`
- Modify: `Modules/Ecommerce/app/Services/TrackingService.php` (add `ga4Enabled()` after `pixelEnabled()`)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`

**Interfaces:**
- Consumes: `Setting::get('tracking', 'ga4_enabled')` from Task 1.
- Produces: `window.gtag` exists on storefront pages when GA4 enabled. `TrackingService::ga4Enabled(): bool`. Task 3's JS relies on `window.gtag`.

- [ ] **Step 1: Write the failing tests** — in `StorefrontTrackingTest::setUp()` add after the existing `Setting::set` lines:

```php
        Setting::set('tracking', 'ga4_enabled', true, 'boolean');
        Setting::set('tracking', 'ga4_measurement_id', 'G-TEST12345');
```

and append two tests:

```php
    public function test_storefront_loads_gtag_when_ga4_enabled(): void
    {
        $res = $this->get(route('storefront.home'));
        $res->assertSee('googletagmanager.com/gtag/js?id=G-TEST12345', false);
        $res->assertSee("gtag('config', 'G-TEST12345')", false);
    }

    public function test_storefront_omits_gtag_when_ga4_disabled(): void
    {
        Setting::set('tracking', 'ga4_enabled', false, 'boolean');
        $res = $this->get(route('storefront.home'));
        $res->assertDontSee('gtag/js?id=', false);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter "gtag"`
Expected: `test_storefront_loads_gtag_when_ga4_enabled` FAILS (no gtag markup); the omits-test passes trivially.

- [ ] **Step 3: Add the loader** — in `Modules/Core/resources/views/components/tracking-head.blade.php`, extend the `@php` block:

```php
    $ga4Enabled = \Modules\Setting\Models\Setting::get('tracking', 'ga4_enabled', false);
    $ga4Id = \Modules\Setting\Models\Setting::get('tracking', 'ga4_measurement_id');
```

and append after the Facebook Pixel `@endif`:

```blade
@if($ga4Enabled && $ga4Id)
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{{ $ga4Id }}');
</script>
@endif
```

- [ ] **Step 4: Add the service helper** — in `TrackingService` after `pixelEnabled()`:

```php
    public function ga4Enabled(): bool
    {
        return (bool) Setting::get('tracking', 'ga4_enabled', false) && $this->ga4MeasurementId();
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter "gtag"`
Expected: both PASS.

- [ ] **Step 6: Commit**

```bash
git add Modules/Core/resources/views/components/tracking-head.blade.php Modules/Ecommerce/app/Services/TrackingService.php Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php
git commit -m "feat(tracking): direct gtag.js loader gated by ga4_enabled"
```

---

### Task 3: gtag branch + new event names in `BizPOS.track()`

**Files:**
- Modify: `public/js/tracking.js`

**Interfaces:**
- Consumes: `window.gtag` from Task 2 (optional — guarded).
- Produces: `BizPOS.track(eventName, params, options)` now forwards to gtag. New FB-style event names usable by later tasks: `RemoveFromWishlist`, `ViewPromotion`, `SelectPromotion`. `params` may now carry `item_list_name`, `promotion_id`, `promotion_name`, `creative_slot`, `method`.

- [ ] **Step 1: Extend the event map** — in `gtmEventMap` add:

```js
        'RemoveFromWishlist': 'remove_from_wishlist',
        'ViewPromotion': 'view_promotion',
        'SelectPromotion': 'select_promotion',
```

- [ ] **Step 2: Extend the dataLayer payload** — after the existing `if (params.search_term)` line add:

```js
        if (params.item_list_name) { ecommerce.item_list_name = params.item_list_name; }
        if (params.promotion_id) { ecommerce.promotion_id = params.promotion_id; }
        if (params.promotion_name) { ecommerce.promotion_name = params.promotion_name; }
        if (params.creative_slot) { ecommerce.creative_slot = params.creative_slot; }
        if (params.method) { payload.method = params.method; }
```

- [ ] **Step 3: Add the gtag branch** — insert between the dataLayer block and the Facebook Pixel block:

```js
    // ── Direct GA4 (gtag.js) ──
    if (typeof window.gtag === 'function') {
        var ga4Event = options.gtmEvent || gtmEventMap[eventName] || eventName;
        var ga4Params = {};
        if (params.currency) { ga4Params.currency = params.currency; }
        if (typeof params.value !== 'undefined') { ga4Params.value = params.value; }
        if (params.items) { ga4Params.items = params.items; }
        if (params.transaction_id) { ga4Params.transaction_id = params.transaction_id; }
        if (params.search_term) { ga4Params.search_term = params.search_term; }
        if (params.item_list_name) { ga4Params.item_list_name = params.item_list_name; }
        if (params.promotion_id) { ga4Params.promotion_id = params.promotion_id; }
        if (params.promotion_name) { ga4Params.promotion_name = params.promotion_name; }
        if (params.creative_slot) { ga4Params.creative_slot = params.creative_slot; }
        if (params.method) { ga4Params.method = params.method; }
        window.gtag('event', ga4Event, ga4Params);
    }
```

- [ ] **Step 4: Extend the FB custom-event list** — change:

```js
        var customEvents = ['RemoveFromCart', 'ViewCart', 'AddToCompare', 'Login', 'SelectItem',
            'RemoveFromWishlist', 'ViewPromotion', 'SelectPromotion', 'ViewCategory'];
```

(`ViewCategory` is also custom for FB — it was in the map but never listed; Task 5 starts using it.)

- [ ] **Step 5: Syntax check + tests still green**

Run: `node --check public/js/tracking.js`
Expected: no output (exit 0). If `node` is unavailable: `php -r "exit(0);"` and rely on the storefront smoke test below.
Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter "guests"`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add public/js/tracking.js
git commit -m "feat(tracking): gtag fan-out + promotion/wishlist event names in BizPOS.track"
```

---

### Task 4: `view_item` on the product detail page

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/shop/show.blade.php` (append at end of file)
- Test: existing `StorefrontTrackingTest::test_product_detail_fires_view_item` (currently failing)

**Interfaces:**
- Consumes: `track-event` partial (`ecommerce::storefront.partials.track-event`, params `event`/`ga`/`fb`); page-level `$product` and `$effectivePrice` (defined in the page's top `@php` block).

- [ ] **Step 1: Run the existing failing test to see the baseline**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter test_product_detail_fires_view_item`
Expected: FAIL — "ViewContent" not found in response.

- [ ] **Step 2: Append to the very end of `shop/show.blade.php`:**

```blade

@push('scripts')
    @php
        $bpViewItem = [
            'item_id'       => (string) $product->id,
            'item_name'     => $product->name,
            'price'         => (float) $effectivePrice,
            'quantity'      => 1,
            'item_brand'    => optional($product->brand)->name,
            'item_category' => optional($product->category)->name,
        ];
    @endphp
    @include('ecommerce::storefront.partials.track-event', [
        'event' => 'ViewContent',
        'ga' => ['currency' => 'BDT', 'value' => (float) $effectivePrice, 'items' => [$bpViewItem]],
        'fb' => ['data' => [
            'content_type' => 'product',
            'content_ids'  => [(string) $product->id],
            'contents'     => [['id' => (string) $product->id, 'quantity' => 1, 'item_price' => (float) $effectivePrice]],
            'content_name' => $product->name,
            'value'        => (float) $effectivePrice,
            'currency'     => 'BDT',
        ]],
    ])
@endpush
```

Note: `$effectivePrice` is set in the page's opening `@php` block (line ~23). If the page already ends with a `@push('scripts')` block, add the include INSIDE that existing block instead of opening a second one (both work — `@push` appends — but one block reads better).

- [ ] **Step 3: Run the test to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter test_product_detail_fires_view_item`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/pages/shop/show.blade.php
git commit -m "feat(tracking): fire view_item/ViewContent on product detail page"
```

---

### Task 5: `view_item_list` + `search` on the shop page

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/shop/index.blade.php` (append at end of file)
- Test: existing `StorefrontTrackingTest::test_shop_list_fires_view_item_list` (currently failing) + `test_shop_search_fires_search`; add one assertion-strengthening test

**Interfaces:**
- Consumes: page paginator `$products` (mixed Product/Combo models, combos flagged by `catalog_type === 'combo'`; Product has `displayPrice()->effective`, Combo has `combo_price`); `track-event` partial.

- [ ] **Step 1: Run the failing test**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter test_shop_list_fires_view_item_list`
Expected: FAIL — "ViewCategory" not found.

- [ ] **Step 2: Append to the very end of `shop/index.blade.php`:**

```blade

@push('scripts')
    @php
        $bpListName = request()->filled('q')
            ? 'Search Results'
            : (request('category') ? \Illuminate\Support\Str::headline((string) request('category')) : 'Shop');
        $bpListItems = collect($products instanceof \Illuminate\Contracts\Pagination\Paginator ? $products->items() : ($products ?? []))
            ->values()
            ->map(function ($p, $i) {
                $isCombo = ($p->catalog_type ?? 'product') === 'combo';
                return [
                    'item_id'   => $isCombo ? 'combo:' . $p->id : (string) $p->id,
                    'item_name' => $p->name,
                    'price'     => (float) ($isCombo ? $p->combo_price : $p->displayPrice()->effective),
                    'index'     => $i,
                    'quantity'  => 1,
                ];
            })->all();
    @endphp
    @if(count($bpListItems))
        @include('ecommerce::storefront.partials.track-event', [
            'event' => 'ViewCategory',
            'ga' => ['item_list_name' => $bpListName, 'items' => $bpListItems],
            'fb' => ['data' => [
                'content_type' => 'product',
                'content_ids'  => array_column($bpListItems, 'item_id'),
                'content_category' => $bpListName,
            ]],
        ])
    @endif
    @if(request()->filled('q'))
        @include('ecommerce::storefront.partials.track-event', [
            'event' => 'Search',
            'ga' => ['search_term' => (string) request('q')],
            'fb' => ['data' => ['search_string' => (string) request('q')]],
        ])
    @endif
@endpush
```

- [ ] **Step 3: Strengthen the search test** — in `StorefrontTrackingTest`, replace `test_shop_search_fires_search` body (its old assertions could pass on incidental page text):

```php
    public function test_shop_search_fires_search(): void
    {
        $res = $this->get(route('storefront.shop.index', ['q' => 'shirt']));
        $res->assertOk();
        $res->assertSee('search_term', false);
        $res->assertSee('shirt', false);
    }
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`
Expected: ALL tests in the file PASS — including the two previously-failing ones.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/pages/shop/index.blade.php Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php
git commit -m "feat(tracking): view_item_list + search events on shop page"
```

---

### Task 6: `select_item` on product/combo cards

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/partials/product-card.blade.php` (root div, line ~25)
- Modify: `Modules/Ecommerce/resources/views/storefront/partials/combo-card.blade.php` (root div)
- Modify: `public/website/assets/js/cart.js` (delegated click handler)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`

**Interfaces:**
- Consumes: `BizPOS.track('SelectItem', ...)` (map entry `SelectItem → select_item` already exists; FB custom list already includes it).
- Produces: `.js-bp-item` cards with `data-item-id`, `data-item-name`, `data-price` attributes.

- [ ] **Step 1: Write the failing test** — append to `StorefrontTrackingTest`:

```php
    public function test_shop_cards_carry_select_item_data_attributes(): void
    {
        $category = CategoryFactory::new()->create();
        ProductFactory::new()->create([
            'status'      => 'active',
            'category_id' => $category->id,
            'sell_price'  => 750.00,
        ]);

        $res = $this->get(route('storefront.shop.index'));
        $res->assertOk();
        $res->assertSee('js-bp-item', false);
        $res->assertSee('data-item-id', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter test_shop_cards_carry_select_item_data_attributes`
Expected: FAIL — "js-bp-item" not found.

- [ ] **Step 3: Add data attributes to the product card root** — in `product-card.blade.php` change the root div (line ~25):

```blade
<div class="product_item_2 product_item js-bp-item"
    data-item-id="{{ $product->id }}"
    data-item-name="{{ $product->name }}"
    data-price="{{ (float) $effectivePrice }}"
    data-category="{{ optional($product->category)->name }}">
```

- [ ] **Step 4: Same for the combo card** — in `combo-card.blade.php`, find the root `<div class="product_item...">` element and extend it the same way (adjust to whatever the existing class list is — only ADD the class and data attributes):

```blade
<div class="{{ /* existing classes */ }} js-bp-item"
    data-item-id="combo:{{ $combo->id }}"
    data-item-name="{{ $combo->name }}"
    data-price="{{ (float) $combo->combo_price }}">
```

Read the file first; keep every existing class/attribute intact.

- [ ] **Step 5: Add the delegated handler** — in `public/website/assets/js/cart.js`, inside the outer IIFE/document-ready scope where the other `$(document).on(...)` handlers live, add:

```js
    // ── select_item: any navigation link inside a catalog card ──
    $(document).on('click', '.js-bp-item a[href]', function () {
        if (!window.BizPOS) { return; }
        // Action buttons (cart/wishlist/compare) have their own events.
        if ($(this).is('.add-to-cart, .add-to-wishlist, .add-to-compare, .quick_view')) { return; }
        var $card = $(this).closest('.js-bp-item');
        var item = {
            item_id: String($card.data('item-id') || ''),
            item_name: $card.data('item-name') || '',
            price: Number($card.data('price')) || 0,
            item_category: $card.data('category') || undefined,
            quantity: 1
        };
        if (!item.item_id) { return; }
        BizPOS.track('SelectItem', { items: [item] }, {});
    });
```

Check the actual class names of the card action buttons in `product-card.blade.php` while editing (the `.quick_view` guard must match whatever class the quick-view trigger uses; adjust the selector list to the real classes found in the card markup).

- [ ] **Step 6: Run the test + syntax check**

Run: `node --check public/website/assets/js/cart.js && php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter test_shop_cards_carry_select_item_data_attributes`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/partials/product-card.blade.php Modules/Ecommerce/resources/views/storefront/partials/combo-card.blade.php public/website/assets/js/cart.js Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php
git commit -m "feat(tracking): select_item on product/combo card clicks"
```

---

### Task 7: `view_promotion` / `select_promotion` on homepage banners

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/home/partials/hero_slider.blade.php` (hero slides ~line 49, promo banner ~line 107)
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/home/partials/flash_deals.blade.php` (section wrapper)
- Modify: `public/website/assets/js/cart.js`
- Test: `Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php`

**Interfaces:**
- Consumes: `BizPOS.track('ViewPromotion'|'SelectPromotion', {promotion_id, promotion_name, creative_slot})` from Task 3.
- Produces: `.js-bp-promo` elements with `data-promotion-id`, `data-promotion-name`, `data-creative-slot`.

- [ ] **Step 1: Write the failing test** — append to `StorefrontTrackingTest`:

```php
    public function test_homepage_banner_carries_promotion_attributes(): void
    {
        \Illuminate\Support\Facades\DB::table('banners')->insert([
            'title'      => 'Mega Sale',
            'image'      => 'uploads/banners/test.jpg',
            'position'   => 'hero',
            'sort_order' => 1,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->get(route('storefront.home'));
        $res->assertOk();
        $res->assertSee('js-bp-promo', false);
        $res->assertSee('data-promotion-name="Mega Sale"', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter test_homepage_banner_carries_promotion_attributes`
Expected: FAIL — "js-bp-promo" not found. (If the insert fails on a missing column, run `php artisan tinker --execute="print_r(Schema::getColumnListing('banners'));"` and match the columns — schema is `id, title, subtitle, button_text, button_url, image, position, sort_order, is_active, starts_at, ends_at, created_at, updated_at`.)

- [ ] **Step 3: Tag the hero slides** — in `hero_slider.blade.php` (~line 49), change the slide wrapper inside `@foreach($banners as $banner)`:

```blade
                                    <div class="banner_slider_2 wow fadeInUp js-bp-promo"
                                        data-promotion-id="banner:{{ $banner->id }}"
                                        data-promotion-name="{{ $banner->title ?: 'Banner ' . $banner->id }}"
                                        data-creative-slot="hero_slider"
                                        style="{!! bg_image_set($banner->image) !!}">
```

(The `style` attribute is pre-existing dynamic background markup — keep it as is.)

- [ ] **Step 4: Tag the promo side banner** — same file (~line 107), inside `@if($promos->count() > 0)`:

```blade
                            <div class="banner_2_add wow fadeInUp js-bp-promo"
                                data-promotion-id="banner:{{ $promo->id }}"
                                data-promotion-name="{{ $promo->title ?: 'Banner ' . $promo->id }}"
                                data-creative-slot="promo_large"
                                style="{!! bg_image_set($promo->image) !!}">
```

Fallback (hard-coded) slides get NO promo attributes — they aren't managed promotions.

- [ ] **Step 5: Tag the flash-deals section** — in `flash_deals.blade.php`, find the outermost `<section ...>` element and add:

```blade
class="... js-bp-promo" data-promotion-id="section:flash_deals" data-promotion-name="{{ $secHeading }}" data-creative-slot="homepage_section"
```

(append the class to the existing class list; only when `$products` is non-empty — wrap in the existing emptiness conditional if the section early-returns on empty).

- [ ] **Step 6: Emit the events** — in `public/website/assets/js/cart.js` add near the select_item handler:

```js
    // ── Promotions: one view_promotion per rendered block, select on click ──
    function bpPromoParams(el) {
        var $el = $(el);
        return {
            promotion_id: String($el.data('promotion-id') || ''),
            promotion_name: $el.data('promotion-name') || '',
            creative_slot: $el.data('creative-slot') || ''
        };
    }

    $(function () {
        if (!window.BizPOS) { return; }
        $('.js-bp-promo').each(function () {
            BizPOS.track('ViewPromotion', bpPromoParams(this), {});
        });
    });

    $(document).on('click', '.js-bp-promo a[href]', function () {
        if (!window.BizPOS) { return; }
        var promo = $(this).closest('.js-bp-promo')[0];
        if (!promo) { return; }
        BizPOS.track('SelectPromotion', bpPromoParams(promo), {});
    });
```

- [ ] **Step 7: Run test + syntax check**

Run: `node --check public/website/assets/js/cart.js && php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php --filter test_homepage_banner_carries_promotion_attributes`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/pages/home/partials/hero_slider.blade.php Modules/Ecommerce/resources/views/storefront/pages/home/partials/flash_deals.blade.php public/website/assets/js/cart.js Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php
git commit -m "feat(tracking): view_promotion/select_promotion on homepage banners"
```

---

### Task 8: `remove_from_wishlist` events

**Files:**
- Modify: `public/website/assets/js/cart.js` — `Wishlist.remove` (~line 287) and `Wishlist.removeCombo` (~line 357)

**Interfaces:**
- Consumes: `BizPOS.track('RemoveFromWishlist', ...)` (map entry from Task 3), existing `bpItemFromEl(el)` helper (cart.js line 10).

- [ ] **Step 1: In `Wishlist.remove` success branch**, after `btns.removeClass('active');` and the count update, add (mirror of the `add` branch's tracking block):

```js
                        if (window.BizPOS) {
                            var w = bpItemFromEl(btns.first());
                            w.item_id = w.item_id || String(productId);
                            BizPOS.track('RemoveFromWishlist',
                                { currency: 'BDT', value: w.price, items: [w] },
                                { fbData: { content_ids: [w.item_id], content_type: 'product', value: w.price, currency: 'BDT' } });
                        }
```

- [ ] **Step 2: In `Wishlist.removeCombo` success branch**, same position:

```js
                        if (window.BizPOS) {
                            var wc = bpItemFromEl(btns.first());
                            wc.item_id = 'combo:' + comboId;
                            BizPOS.track('RemoveFromWishlist',
                                { currency: 'BDT', value: wc.price, items: [wc] },
                                { fbData: { content_ids: [wc.item_id], content_type: 'product', value: wc.price, currency: 'BDT' } });
                        }
```

- [ ] **Step 3: Syntax check**

Run: `node --check public/website/assets/js/cart.js`
Expected: exit 0.

- [ ] **Step 4: Commit**

```bash
git add public/website/assets/js/cart.js
git commit -m "feat(tracking): remove_from_wishlist events (product + combo)"
```

---

### Task 9: Server-side `refund` on cancel/refund after reported purchase

**Files:**
- Create: `Modules/Ecommerce/database/migrations/2026_07_05_000001_add_refund_reported_at_to_ecommerce_orders_table.php`
- Modify: `Modules/Ecommerce/app/Jobs/SendGa4McEvent.php` (eventName param)
- Modify: `Modules/Ecommerce/app/Models/EcommerceOrder.php` (cast + fillable if pattern requires)
- Modify: `Modules/Ecommerce/app/Services/TrackingService.php` (`reportRefund()`)
- Modify: `Modules/Ecommerce/app/Services/EcommerceService.php:90-96` (call site)
- Test: `Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`

**Interfaces:**
- Consumes: `SendGa4McEvent` job, `EcommerceOrder.purchase_reported_at`, `shouldReportPurchase()`.
- Produces: `TrackingService::reportRefund(EcommerceOrder $order): void`; `SendGa4McEvent` gains optional `public string $eventName = 'purchase'` constructor param (backward compatible — purchase call sites unchanged); `ecommerce_orders.refund_reported_at` column.

- [ ] **Step 1: Write the failing tests** — append to `TrackingServiceTest`:

```php
    public function test_report_refund_dispatches_ga4_refund_for_reported_order(): void
    {
        Bus::fake();
        Setting::set('tracking', 'ga4_measurement_id', 'G-ABC123');
        Setting::set('tracking', 'ga4_api_secret', 'SECRET');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-R1', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'cancelled',
            'subtotal' => 1500, 'grand_total' => 1500, 'purchase_reported_at' => now(),
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]],
        ]);

        app(TrackingService::class)->reportRefund($order);

        Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class, function ($job) {
            return $job->eventName === 'refund'
                && $job->params['transaction_id'] === 'ORD-R1';
        });
        $this->assertNotNull($order->fresh()->refund_reported_at);

        // Second call must not dispatch again.
        Bus::fake();
        app(TrackingService::class)->reportRefund($order->fresh());
        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class);
    }

    public function test_report_refund_skips_unreported_or_suppressed_orders(): void
    {
        Bus::fake();
        Setting::set('tracking', 'ga4_measurement_id', 'G-ABC123');
        Setting::set('tracking', 'ga4_api_secret', 'SECRET');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        // Never purchase-reported → no refund.
        $unreported = EcommerceOrder::create([
            'order_number' => 'ORD-R2', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'cancelled',
            'subtotal' => 1500, 'grand_total' => 1500,
        ]);
        app(TrackingService::class)->reportRefund($unreported);

        // Purchase was suppressed by the fraud gate → no refund either.
        $suppressed = EcommerceOrder::create([
            'order_number' => 'ORD-R3', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'cancelled',
            'subtotal' => 1500, 'grand_total' => 1500, 'purchase_reported_at' => now(),
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 20]],
        ]);
        app(TrackingService::class)->reportRefund($suppressed);

        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class);
        // Guard still set on the suppressed order — decision is final.
        $this->assertNotNull($suppressed->fresh()->refund_reported_at);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php --filter refund`
Expected: FAIL — `reportRefund` undefined / column missing.

- [ ] **Step 3: Create the migration:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->timestamp('refund_reported_at')->nullable()->after('purchase_reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn('refund_reported_at');
        });
    }
};
```

Run: `php artisan migrate`

- [ ] **Step 4: Model bookkeeping** — in `EcommerceOrder`, wherever `purchase_reported_at` appears in `$casts` (see `getCasts` — it's a datetime cast) and `$fillable`, add `refund_reported_at` alongside it with the same treatment. Read the model first and mirror exactly.

- [ ] **Step 5: Job event name** — in `SendGa4McEvent`, add the constructor param and use it:

```php
    public function __construct(
        public string $measurementId,
        public string $apiSecret,
        public string $clientId,
        public array $params,
        public string $eventName = 'purchase',
    ) {}
```

and in `handle()` change the events line:

```php
                'events'    => [[ 'name' => $this->eventName, 'params' => $this->params ]],
```

- [ ] **Step 6: `reportRefund()`** — append to `TrackingService` after `reportPurchase()`:

```php
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
```

- [ ] **Step 7: Call site** — in `EcommerceService::updateOrderStatus()`, after the existing purchase-report block (line ~106), add:

```php
        // Report the Refund conversion when a reported purchase is cancelled or
        // refunded. Once-guarded + fraud-symmetric inside the service.
        if (in_array($status, $reverseStatuses, true)) {
            try {
                app(\Modules\Ecommerce\Services\TrackingService::class)->reportRefund($order);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Refund tracking failed {$order->order_number}: {$e->getMessage()}");
            }
        }
```

- [ ] **Step 8: Run the tests**

Run: `php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php`
Expected: ALL PASS (old purchase tests confirm the job change is backward compatible).

- [ ] **Step 9: Commit**

```bash
git add Modules/Ecommerce/database/migrations/2026_07_05_000001_add_refund_reported_at_to_ecommerce_orders_table.php Modules/Ecommerce/app/Jobs/SendGa4McEvent.php Modules/Ecommerce/app/Models/EcommerceOrder.php Modules/Ecommerce/app/Services/TrackingService.php Modules/Ecommerce/app/Services/EcommerceService.php Modules/Ecommerce/tests/Unit/TrackingServiceTest.php
git commit -m "feat(tracking): server-side GA4 refund on cancel after reported purchase"
```

---

### Task 10: Docs, full test sweep, storefront smoke test

**Files:**
- Modify: `docs/TRACKING.md`

- [ ] **Step 1: Update `docs/TRACKING.md`:**

(a) In section 1 (Configure), replace the GA4 bullet:

```markdown
- **Google Analytics 4:** enable + Measurement ID (`G-XXXX`) — loads gtag.js directly, so ALL browser
  events reach GA4 with **no GTM container configuration**. API Secret powers the server-side
  Purchase/Refund (GA4 Admin → Data Streams → Measurement Protocol API secrets).
```

(b) In section 2 (Operational requirements), replace requirement 2 with:

```markdown
2. **GTM container tags are only needed for non-GA4 tags.** With GA4 enabled (gtag.js), browser events
   reach GA4 directly. ⚠ **Never add GA4 tags inside the GTM container while GA4 is enabled here — every
   event would be counted twice.**
```

(c) In section 3 (fraud gate), append:

```markdown
A **Refund** is sent to GA4 (same `transaction_id`) when a purchase-reported order is later cancelled or
refunded — once per order (`refund_reported_at` guard), and never for orders whose purchase was suppressed.
```

(d) In section 4 (QA checklist), add:

```markdown
- [ ] GA4 DebugView (with GA4 enabled): `page_view`, `view_item`, `view_item_list`, `select_item`, `search`, `view_promotion`, `select_promotion`, `add_to_cart`, `remove_from_wishlist` custom event.
- [ ] Cancel a confirmed (reported) order → `refund` appears in GA4 with the original transaction_id.
```

(e) In section 5 (Future), remove the now-implemented "GA4 `refund` on post-confirmation RTO/cancellation" clause.

- [ ] **Step 2: Full affected-suite run**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontTrackingTest.php Modules/Ecommerce/tests/Unit/TrackingServiceTest.php Modules/Setting/tests/Feature/TrackingSettingsTest.php`
Expected: ALL PASS (including the two formerly-failing tests).

- [ ] **Step 3: Storefront smoke test** — with tracking enabled in the dev DB (Settings → Tracking, or via tinker `Setting::set('tracking','ga4_enabled',true,'boolean')`):

```bash
curl -s http://127.0.0.1:8000/ | grep -c "gtag/js?id="        # expect 1
curl -s http://127.0.0.1:8000/ | grep -c "js/tracking.js"     # expect 1
```

- [ ] **Step 4: Commit**

```bash
git add docs/TRACKING.md
git commit -m "docs(tracking): GA4 direct gtag setup, double-count warning, refund"
```
