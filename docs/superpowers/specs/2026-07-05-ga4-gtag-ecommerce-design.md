# GA4 Ecommerce via Direct gtag.js — Design

**Date:** 2026-07-05
**Status:** Approved
**Builds on:** `2026-06-13-storefront-gtm-pixel-capi-design.md` (GTM + Pixel + CAPI foundation)

## Problem

Browser events currently reach GA4 only if the merchant configures GA4 tags inside
their GTM container — which nobody does, so GA4 reports stay empty (only the
server-side Purchase arrives via Measurement Protocol). Several standard GA4
ecommerce events are also missing entirely: `view_item`, `view_item_list`,
`select_item`, `search`, `view_promotion`, `select_promotion`, `refund` — two of
which have pre-existing failing tests (`test_product_detail_fires_view_item`,
`test_shop_list_fires_view_item_list`).

Reference: https://developers.google.com/analytics/devguides/collection/ga4/ecommerce?client_type=gtag

## Goal

Full GA4 ecommerce coverage that works with **zero GTM container configuration**,
by loading gtag.js directly with the merchant's Measurement ID, while keeping the
existing GTM dataLayer + Facebook Pixel pipeline untouched.

## Architecture

Single-funnel principle stays: every browser event goes through
`window.BizPOS.track(eventName, gaParams, options)` (`public/js/tracking.js`),
which fans out to three sinks:

1. **GTM dataLayer** (existing) — GA4-shaped pushes for GTM-managed tags
2. **Facebook Pixel** (existing) — `fbq('track'|'trackCustom', ...)`
3. **gtag.js** (NEW) — `gtag('event', <ga4_name>, {currency, value, items, ...})`
   exactly per the gtag client-type guide; fires only when `window.gtag` exists

Server-side (existing `TrackingService` + queued jobs): Purchase via GA4
Measurement Protocol + Meta CAPI, fraud-gated. This design adds Refund on the
same pattern.

## Components

### 1. Settings (Setting module)
- New tracking keys: `ga4_enabled` (boolean). Reuses existing
  `ga4_measurement_id` + `ga4_api_secret`.
- Settings → Tracking UI: new "Google Analytics 4" card with Enable toggle +
  Measurement ID + API Secret (fields move out of the Facebook Pixel card).
- `SettingService::updateTracking()` boolean list gains `ga4_enabled`;
  `UpdateSettingsRequest` validates `ga4_measurement_id` format (`G-[A-Z0-9]+`).
- `TrackingService::ga4Enabled(): bool` helper (enabled && id present).

### 2. gtag loader (`Modules/Core/resources/views/components/tracking-head.blade.php`)
When GA4 enabled + Measurement ID set, render per the guide:
```html
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXX');
</script>
```
This gives `page_view` and session tracking for free. CSP already whitelists
`www.googletagmanager.com` (script) and `*.google-analytics.com` (connect) —
verified, no CSP change needed.

**Double-count warning (docs):** merchants must NOT also add GA4 tags inside
their GTM container while GA4 is enabled here, or events count twice.

### 3. `BizPOS.track()` gtag branch (`public/js/tracking.js`)
- Maps the FB-style event name through the existing `gtmEventMap` to the GA4
  name (e.g. `ViewContent` → `view_item`).
- Calls `gtag('event', name, params)` with the GA4 params it already receives
  (`currency`, `value`, `items`, `transaction_id`, `search_term`, plus
  promotion fields).
- Custom events without a GA4 standard equivalent go through as-is:
  `remove_from_wishlist`, `add_to_compare`, `view_cart` is standard, `login`
  and `sign_up` are standard.
- New map entries: `RemoveFromWishlist` → `remove_from_wishlist`,
  `ViewPromotion` → `view_promotion`, `SelectPromotion` → `select_promotion`.

### 4. New browser events (Ecommerce module views + storefront JS)

| Event | Where | Payload |
|---|---|---|
| `view_item` | `shop/show.blade.php` via `track-event` partial (FB name `ViewContent`) | currency, value (sell price), items[1]; FB: content_ids, contents, value |
| `view_item_list` | `shop/index.blade.php` (FB name `ViewCategory`, custom for FB) | item_list_name (category name or "Shop"), items (rendered page of products) |
| `search` | `shop/index.blade.php` when `q` present (FB standard `Search`) | search_term; FB: search_string |
| `select_item` | delegated click handler on `.product-card` links, added to cart.js (where the other storefront tracking handlers live) | items[1] from data-attributes on the card |
| `view_promotion` | homepage: one per rendered banner / campaign section | promotion_id, promotion_name, creative_slot |
| `select_promotion` | click on banner / campaign block | same fields |
| `remove_from_wishlist` | cart.js wishlist remove handler (product + combo variants) | items[1] (custom event for both GA4 and FB) |

`product-card.blade.php` gains `data-item-id`, `data-item-name`, `data-price`
attributes so `select_item` needs no extra queries. Combo cards likewise.

### 5. Server-side `refund` (Ecommerce module)
- Migration: `ecommerce_orders.refund_reported_at` (nullable timestamp).
- In `EcommerceService::updateOrderStatus()`: when status transitions to
  `cancelled`/`refunded` AND `purchase_reported_at` is set AND
  `refund_reported_at` is null → dispatch `SendGa4McEvent` with a new optional
  `eventName` constructor param (default `'purchase'`, here `'refund'` — DRY,
  the job differs only by the event name): GA4 MP `refund` event with the original `transaction_id`
  (order number), full-order refund (no items required per GA4 spec for full
  refunds; we send items anyway from order items). Set `refund_reported_at`.
- Orders whose purchase was fraud-suppressed have `purchase_reported_at` set
  but were never sent — decision: send refund only if the purchase was
  actually reported. Reuse the same suppression check: if
  `shouldReportPurchase()` was false at purchase time nothing was sent, so
  refund must also be skipped. Since `purchase_reported_at` is set in both
  cases, add the fraud-risk re-check in the refund path (same
  `blockedRiskLevels` logic) to keep symmetry.
- No Meta CAPI refund (Meta has no standard refund event) — GA4 only.

### 6. Tests
- Fix/keep green: `test_product_detail_fires_view_item`,
  `test_shop_list_fires_view_item_list` (existing, currently failing).
- New: gtag loader renders when enabled (and not when disabled); search event
  on `?q=`; select_item data-attributes present on product cards; promotion
  markup on homepage; refund job dispatched exactly once on
  cancel-after-purchase; refund not dispatched when purchase was suppressed
  or order never reported.
- Settings test: `ga4_enabled` round-trip.

### 7. Docs
`docs/TRACKING.md`: GA4 direct setup section (enable toggle, Measurement ID),
double-count warning, refund behavior, updated QA checklist (GA4 DebugView
for browser events now, not just Purchase).

## Out of scope
- Browser-side `purchase` (stays server-side, fraud-gated — deliberate)
- Meta refund/return events
- POS / landing-page tracking
- Product catalog feed / dynamic ads

## Decisions log
- Direct gtag.js chosen over GTM-only (zero container config; matches guide)
- All guide events included, incl. promotions + refund (user request)
- `remove_from_wishlist` added as custom event (no GA4 standard name)
- GTM dataLayer pushes kept as-is for FB/other tags; docs warn against GA4
  tags in GTM to avoid double counting
