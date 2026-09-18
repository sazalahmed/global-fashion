# Storefront Analytics — GTM + Facebook Pixel + Conversions API (fraud-gated)

**Status:** Approved design — ready for implementation plan
**Date:** 2026-06-13
**Scope:** Full conversion tracking for the **eCommerce storefront only** (`Modules/Ecommerce`). Browser events via Google Tag Manager (GA4-shaped `dataLayer`) + Meta (Facebook) Pixel. The **Purchase** conversion is deferred and fraud-gated, sent server-side to both Meta (Conversions API) and GA4 (Measurement Protocol).

> Supersedes `docs/STOREFRONT_TRACKING_PLAN.md`, which described an `EcommerceSetting` + `TrackingService`-only design that was never built. That stale doc is to be deleted as part of this work.

---

## 1. Confirmed decisions

| Decision | Choice |
|---|---|
| Surfaces tracked | eCommerce storefront only (not landing pages or POS in this pass) |
| Meta depth | Browser Pixel for upper-funnel + **server-side CAPI** for Purchase |
| GA4 wiring | App pushes GA4-shaped `dataLayer`; GA4 tags configured in the GTM UI. (No direct `gtag.js`.) |
| Purchase trigger | **On order confirmation, fraud-gated** — not at checkout |
| New customers (no courier history) | Treated as trusted → counted |
| Fraud suppression scope | Applies to **both** Meta and GA4 |
| Consent gate | None (Bangladesh market); tracking fires on load |

### The core rationale (COD fraud)
Meta optimizes ad delivery toward whoever you report as a `Purchase`, and seeds lookalike audiences from them. In a COD market, reporting every *placed* order — including customers who refuse the parcel (RTO) — trains Meta to find more bad customers, wasting ad budget and increasing return shipping cost. Therefore the `Purchase` conversion is reported **only for orders the business actually trusts** (confirmed + not fraud-flagged), so Meta optimizes toward customers who accept parcels.

---

## 2. Current state (the "partial implementation")

**Already in place (scaffolding):**
- **Settings** — `Setting` group `tracking`: `gtm_enabled`, `gtm_container_id`, `fbpixel_enabled`, `fbpixel_id`, `fbpixel_access_token`. UI in `Modules/Setting/resources/views/index.blade.php` (~L1382–1438); validated in `UpdateSettingsRequest` (L39–40, 78–79); saved via `SettingService::updateGroupWithBooleans('tracking', …)` (L249).
- **Base loaders** — `Modules/Core/resources/views/components/tracking-head.blade.php` (GTM container + Pixel base + `PageView` + Pixel `<noscript>`) and `tracking-body.blade.php` (GTM `<noscript>`). Exposed as `<x-core::tracking-head>` / `<x-core::tracking-body>`.
- **JS dispatcher** — `public/js/tracking.js` → `window.BizPOS.track(name, params, options)` pushes to `dataLayer` (FB→GA4 name map) and `fbq()`.

**Missing / broken:**
1. The components + `tracking.js` are wired into the **LandingPage** layout only. The storefront master layout (`Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php`) includes **none** of it → storefront has zero tracking today.
2. **No commerce events fire anywhere** — no `BizPOS.track()` calls in any real view. Only the base `PageView` fires, only on landing pages.
3. **No server-side CAPI** — `fbpixel_access_token` is saved but never used.
4. **No staff/admin exclusion.**

**Relevant existing infrastructure we build on:**
- `FraudCheckService::check($phone)` runs **synchronously at checkout** (`CheckoutController@process` L274) and stores `fraud_report` + `fraud_score` (= `success_ratio`, 0–100) on the order. Risk buckets: `new` (no history), `low` (≥80), `medium` (≥60), `high` (≥40), `critical` (<40).
- `EcommerceService::updateOrderStatus()` (L60) is the order status-transition hook. It already fires revenue-recognition **once** via an idempotency guard (`$hasJournal`) when the order enters `confirmed`/`processing`/`shipped`/`delivered`. The deferred Purchase attaches to this exact transition with the same once-only pattern.
- `EcommerceOrder` has `customer_email`, `customer_phone`, `grand_total`, `subtotal`, `discount_amount`, `shipping_charge`, `tax_amount`, `coupon_code`, and an `items()` relation (`product_name`, `quantity`, `unit_price`, `product_id`, `variant_id`).
- `Product::displayPrice()` returns `->effective` as a **raw numeric float** (the ceiled effective/discounted price) — this is the value all events use, never a `bd_price()`-formatted string.

---

## 3. Architecture

### 3.1 Settings (extend the existing `tracking` group)
Add fields to the Tracking settings card + `UpdateSettingsRequest`:
- `fbpixel_test_event_code` — string, optional (Meta Test Events QA).
- `ga4_measurement_id` — string, optional, pattern `G-XXXXXXX` (server-side GA4 purchase).
- `ga4_api_secret` — password, optional (GA4 Measurement Protocol secret).
- `purchase_block_risk_levels` — string, default `high,critical` (risk levels for which Purchase is suppressed).

Booleans continue to be handled by `SettingService::updateGroupWithBooleans`.

### 3.2 `TrackingService` (new — `Modules/Ecommerce/app/Services/TrackingService.php`)
Single responsibility: read tracking settings (cached) and normalize domain objects into platform-ready payloads. DRY core shared by Blade server-rendered events and the server-side jobs.
- `enabled(): bool`, `gtmEnabled()`, `pixelEnabled()`, `gtmId()`, `pixelId()`, `capiToken()`, `testEventCode()`, `ga4MeasurementId()`, `ga4ApiSecret()`.
- `blockedRiskLevels(): array` — parsed from `purchase_block_risk_levels`.
- `shouldReportPurchase(EcommerceOrder $order): bool` — true unless the order's risk level (via `FraudCheckService::getRiskLevel($order->fraud_report)`) is in `blockedRiskLevels()`. `new`/`low`/`medium` report by default.
- `itemsFor($products|$cartItems|$orderItems): array` — GA4 `items[]` (`item_id`, `item_name`, `price` (numeric effective), `quantity`, `item_category`, `item_brand`, `item_variant`).
- `contentsFor(...): array` — Meta `content_ids` + `contents:[{id, quantity, item_price}]`.
- `eventId(string $seed): string` — stable, e.g. `purchase.<order_number>` (shared across CAPI/GA4 dedup).

### 3.3 Staff-exclusion gate
The Core `tracking-head` / `tracking-body` components (and the `track-event` partial) render only when:
`Setting tracking enabled for the channel` **AND** `! auth()->guard('web')->check()` (i.e. not a logged-in admin/staff). Storefront customers (guard `customer`) are still tracked.

### 3.4 Browser dispatcher (`public/js/tracking.js`, enhanced)
- Push proper GA4 ecommerce shape: `dataLayer.push({ ecommerce: null })` to clear, then `dataLayer.push({ event, ecommerce: { currency, value, items } , ...})`.
- Accept an `eventID` option (Pixel) for any future dedup use.
- Advanced matching: when a logged-in customer's hashed email/phone are provided to the layout, call `fbq('init', PIXEL, { em, ph })` so browser events match well.
- Keep the existing FB→GA4 name map.

### 3.5 Server-rendered events (`track-event.blade.php`, new)
`@include('ecommerce::storefront.partials.track-event', ['event' => …, 'ga' => [...], 'fb' => ['name' => …, 'data' => [...]], 'eventId' => null])` emits a `<script>` that calls `BizPOS.track()` on DOM-ready, with the payload built server-side from `TrackingService`. Used for `view_item`, `view_item_list`, `search`, `view_cart`, `begin_checkout`.

### 3.6 Deferred, fraud-gated Purchase (server-side, both platforms)
**No `Purchase` fires in the browser.** Instead:

**Capture at checkout** (`CheckoutController@process`): read `_fbp`, `_fbc` (derive from `fbclid` query param if cookie absent), `_ga` (GA4 client_id), `client_ip`, `user_agent`; store on the order in a new `tracking_data` JSON column.

**Fire on confirmation** (`EcommerceService::updateOrderStatus()`): the first time the order transitions into a recognized state (`confirmed`/`processing`/`shipped`/`delivered`) — mirroring the existing `$recognizeStatuses` block — AND `TrackingService::shouldReportPurchase($order)` is true AND `purchase_reported_at` is null:
- Dispatch `SendFbCapiEvent` (Meta CAPI `Purchase`).
- Dispatch `SendGa4McEvent` (GA4 Measurement Protocol `purchase`).
- Set `purchase_reported_at = now()` (once-guard).

Wrapped in `try/catch` + `Log::warning` so a tracking failure never breaks the order workflow (same defensive style as the existing journal-entry recognition).

**`SendFbCapiEvent`** (new queued job) POSTs to `https://graph.facebook.com/v19.0/{pixel}/events`:
- `event_name: 'Purchase'`, `event_id` (= `eventId('purchase.'.$order->order_number)`), `event_time`, `action_source: 'website'`, `event_source_url` (storefront success URL).
- `custom_data`: `value` (grand_total numeric), `currency: 'BDT'`, `contents`, `content_type: 'product'`, `num_items`, `order_id`.
- `user_data`: `em`/`ph` SHA-256-hashed (normalized lower/trim, phone digits only); `fbp`/`fbc`/`client_ip_address`/`client_user_agent` un-hashed (from `tracking_data`).
- `test_event_code` when set. Never logs raw PII.

**`SendGa4McEvent`** (new queued job) POSTs to `https://www.google-analytics.com/mp/collect?measurement_id={id}&api_secret={secret}`:
- `client_id` (from `tracking_data.ga_client_id`; fallback to a synthesized id), `events: [{ name: 'purchase', params: { transaction_id, value, currency: 'BDT', shipping, tax, coupon, items } }]`.

### 3.7 Browser events that still fire for everyone (not gated)
`view_item`, `view_item_list`, `search`, `add_to_cart`, `add_to_wishlist`, `add_to_compare`, `view_cart`, `remove_from_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info`, `sign_up`, `login`, `generate_lead` (newsletter). These are upper-funnel signals, not the optimization conversion.

> **Campaign requirement (document for the operator):** Meta campaigns must optimize for the **Purchase** event (the gated one) for budget protection to take effect. Optimizing for AddToCart/InitiateCheckout would bypass the gate.

---

## 4. Event-by-event map

| Event | GA4 (dataLayer) | Meta Pixel | Where / how |
|---|---|---|---|
| PageView | `page_view` (GTM auto) | `PageView` (base) | layout |
| view_item | `view_item` | `ViewContent` | `ShopController@show` → server `track-event` |
| view_item_list | `view_item_list` | — | shop index + `StorefrontCategoryController@show` → server `track-event` over paginator |
| search | `search` (`search_term`) | `Search` (`search_string`) | shop index when `request('q')` present |
| select_item | `select_item` | — | product card click (data-attrs) |
| add_to_cart | `add_to_cart` | `AddToCart` | `cart.js` `Cart.add` success |
| order-now | `add_to_cart` + `begin_checkout` | `AddToCart` + `InitiateCheckout` | `cart.js` BuyNow flow |
| add_to_wishlist | `add_to_wishlist` | `AddToWishlist` | `cart.js` `Wishlist.add` success |
| add_to_compare | `add_to_compare` (custom) | — | `cart.js` `Compare.add` success |
| view_cart | `view_cart` | — | `cart/index` → server `track-event` |
| remove_from_cart | `remove_from_cart` | — | `cart.js` remove |
| begin_checkout | `begin_checkout` | `InitiateCheckout` | `checkout/index` → server `track-event` |
| add_shipping_info | `add_shipping_info` | — | `checkout/index` JS on `#zoneSelect` change |
| add_payment_info | `add_payment_info` | `AddPaymentInfo` | `checkout/index` JS on payment select |
| **purchase** | `purchase` (GA4 MP, server) | `Purchase` (CAPI, server) | `EcommerceService@updateOrderStatus` on confirmation — fraud-gated, once-guarded |
| sign_up | `sign_up` | `CompleteRegistration` | flash from `CustomerAuthController@register` → fire next page |
| login | `login` (`method`) | — | flash from `CustomerAuthController@login` |
| newsletter | `generate_lead` | `Lead` | `NewsletterController@subscribe` success |

Product cards/buttons gain `data-*` attributes: `data-id`, `data-sku`, `data-name`, `data-price` (numeric effective), `data-category`, `data-brand`, `data-variant`.

---

## 5. Database changes

New migration on `ecommerce_orders`:
- `tracking_data` JSON nullable — `{ fbp, fbc, ga_client_id, ip, ua }` captured at checkout.
- `purchase_reported_at` timestamp nullable — once-guard for the deferred Purchase.

Add both to `EcommerceOrder::$fillable` and cast `tracking_data => 'array'`, `purchase_reported_at => 'datetime'`.

---

## 6. Files

**New**
- `Modules/Ecommerce/app/Services/TrackingService.php`
- `Modules/Ecommerce/app/Jobs/SendFbCapiEvent.php`
- `Modules/Ecommerce/app/Jobs/SendGa4McEvent.php`
- `Modules/Ecommerce/resources/views/storefront/partials/track-event.blade.php`
- migration: `..._add_tracking_columns_to_ecommerce_orders_table.php`

**Modified**
- `Modules/Ecommerce/.../storefront/layouts/master.blade.php` — include `<x-core::tracking-head>` (head), `<x-core::tracking-body>` (after `<body>`), load `tracking.js`.
- `Modules/Core/.../components/tracking-head.blade.php` + `tracking-body.blade.php` — add staff-exclusion gate.
- `public/js/tracking.js` — GA4 ecommerce shape, `eventID`, advanced matching.
- `Modules/Setting/resources/views/index.blade.php` — add test-event-code, GA4 MP, block-risk-levels fields.
- `Modules/Setting/app/Http/Requests/UpdateSettingsRequest.php` — validate new fields.
- `Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php` — capture fbp/fbc/ga_client_id/ip/ua on the order at `process()`.
- `Modules/Ecommerce/app/Services/EcommerceService.php` — dispatch deferred Purchase jobs in `updateOrderStatus()`.
- `Modules/Ecommerce/app/Models/EcommerceOrder.php` — fillable + casts.
- Storefront views: `shop/show`, `shop/index`, `category/show`, `cart/index`, `checkout/index` — server `track-event` includes + checkout shipping/payment JS + product `data-*` attrs (product card partial + buy-now modal).
- `public/website/assets/js/cart.js` — add_to_cart / order-now / wishlist / compare hooks.
- `CustomerAuthController` (login/sign_up flash), `NewsletterController` (lead event).
- **Delete** `docs/STOREFRONT_TRACKING_PLAN.md` (superseded).

---

## 7. Correctness / privacy / performance
- **Numeric values only** — raw floats from `Product::displayPrice()->effective` / order numeric columns; never `bd_price()` strings. `currency: 'BDT'`.
- Tracking never loads in the admin panel and is skipped for logged-in staff (guard `web`).
- CAPI/GA4 jobs run on the **queue** (non-blocking); GTM/Pixel scripts load async.
- CAPI `em`/`ph` SHA-256-hashed; `fbp`/`fbc`/IP/UA un-hashed; raw PII never logged. CAPI token & GA4 secret stored in settings (note: consider env in production).
- Purchase fires **exactly once** per order via `purchase_reported_at`; status churn / re-confirmation cannot double-report.
- High/critical-risk orders are reported to **neither** Meta nor GA4, keeping reported revenue/ROAS consistent with fulfilment.
- Deferred Purchase relies on the order having captured `tracking_data` at checkout; if absent (e.g. direct admin order), CAPI/GA4 still send with whatever match data exists (em/ph), degrading gracefully.

---

## 8. Phasing
1. **Foundation** — wire storefront layout (head/body/`tracking.js`) + staff gate + new settings fields + `tracking.js` enhancements + `TrackingService`. Verify `PageView` on storefront, not in admin.
2. **Browser events** — `view_item`, `view_item_list`, `search`, `view_cart`, `begin_checkout` (server `track-event`) + `add_to_cart`/order-now/wishlist/compare/remove (`cart.js`) + `add_shipping_info`/`add_payment_info` (checkout JS) + `sign_up`/`login`/newsletter + product `data-*` attrs.
3. **Checkout capture** — migration (`tracking_data`, `purchase_reported_at`) + capture identifiers in `CheckoutController@process` + model fillable/casts.
4. **Deferred Purchase** — `SendFbCapiEvent` + `SendGa4McEvent`, dispatched fraud-gated & once-guarded from `EcommerceService@updateOrderStatus()`.
5. **QA** — GTM Preview / Tag Assistant; Meta Pixel Helper + Meta Test Events; GA4 DebugView. Confirm: every browser event fires; Purchase fires only on confirmation, never for high/critical risk, never twice; nothing fires in admin/for staff; campaigns optimize for Purchase.

---

## 9. Out of scope (future)
- Meta **Custom Audience exclusion**: push hashed phone/email of high-risk customers as an audience used to *exclude* them from ad delivery (stops Meta showing them ads, not just reporting). Heavier (Custom Audience API). The conversion-signal gating above is the primary automatic lever.
- Landing-page and POS conversion tracking.
- Product catalog feed for dynamic product ads (prerequisite for catalog retargeting).
- Consent Mode v2 / cookie banner (not needed for the Bangladesh market now).
