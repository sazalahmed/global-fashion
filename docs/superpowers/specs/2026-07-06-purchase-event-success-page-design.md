# Purchase Event on Storefront Success Page — Design

**Date:** 2026-07-06
**Status:** Approved
**Builds on:** `2026-07-05-ga4-gtag-ecommerce-design.md` (gtag.js pipeline),
`2026-06-13-storefront-gtm-pixel-capi-design.md` (GTM + Pixel + CAPI foundation)

## Problem

The Purchase conversion currently fires **server-side only** (Meta CAPI + GA4
Measurement Protocol), triggered when an admin (or courier webhook) moves the
order to `confirmed`/`processing`/`shipped`/`delivered`. Consequences:

- No browser-side purchase at all: no Pixel `Purchase` for Meta's browser
  match signals, no gtag `purchase` for GA4 session/attribution stitching.
- The conversion lands hours or days after the ad click, degrading attribution.
- GA4 purchases arrive via Measurement Protocol with a synthetic client id
  (`srv.{id}`) when the `_ga` cookie wasn't captured, so they don't join the
  browser session that produced them.

## Goal

Fire the Purchase conversion from the **order success page** in the browser
(GA4 gtag + GTM dataLayer + Meta Pixel), with Meta CAPI kept as a
**deduplicated server-side backup** fired at order placement. GA4 becomes
client-only for purchase (no MP purchase → no double-count). The GA4 `refund`
correction on cancellation stays server-side.

## Decisions (approved)

1. **Hybrid with dedup** — browser purchase on success page + Meta CAPI backup
   sharing `event_id` = `purchase.{order_number}` (already deterministic via
   `TrackingService::eventId()`). GA4 MP purchase is removed.
2. **CAPI fires at order placement** — same moment as the browser event, so
   Meta's ~48h dedup window always holds. Status changes no longer report
   purchase.
3. **Fire-once guard = session flash** — the checkout redirect carries a
   one-time flash; the success page renders the tracking script only when the
   flash matches the order. Reload/revisit/shared link → no re-fire.
   **Accepted gap:** external entries (payment-gateway `success_url`,
   AI-assistant checkout links) arrive without the flash → no client-side
   GA4/Pixel purchase for those orders; Meta remains covered by CAPI.
4. **Fraud gate applies to the client event too** — the success page only
   embeds the tracking payload when `shouldReportPurchase($order)` passes
   (same `purchase_block_risk_levels` setting), symmetric with CAPI.

## Components

### 1. Success page tracking (`Modules/Ecommerce/.../storefront/pages/checkout/success.blade.php`)

When the flash is present AND the fraud gate passes, render a script that calls:

```js
BizPOS.track('Purchase', {
    transaction_id: '{order_number}',
    value: grand_total, currency: 'BDT',
    shipping: shipping_charge, tax: tax_amount,
    coupon: coupon_code, customer_type: 'new'|'returning',
    items: [...]                       // TrackingService::itemsFromOrder()
}, {
    eventID: 'purchase.{order_number}', // matches CAPI event_id → Meta dedup
    fbData: { value, currency: 'BDT', content_type: 'product',
              contents: [...], num_items, order_id }  // mirrors CAPI payload
});
```

Payload is built server-side in `CheckoutController::success()` (reusing
`TrackingService::itemsFromOrder()`, `contentsFromItems()`, `customerType()`)
and passed to the view as a JSON-encoded variable — no ad-hoc payload shaping
in Blade.

### 2. Fire-once flash

- `CheckoutController::store()` adds `->with('purchase_order',
  $order->order_number)` to the success redirect.
- `CheckoutController::success()` builds the tracking payload only when
  `session('purchase_order') === $orderNumber`.
- Same flash added to any other internal redirect we control that lands on
  the success page (e.g. duplicate-order redirect at
  `CheckoutController.php:334` does NOT get it — that's a revisit, not a new
  purchase).

### 3. `public/js/tracking.js`

Add `shipping`, `tax`, and `customer_type` to the param pass-through for both
the dataLayer and gtag sinks (currently not whitelisted).

### 4. `TrackingService::reportPurchase()`

- **Keep:** once-guard (`purchase_reported_at`), fraud gate, Meta CAPI
  dispatch (`SendFbCapiEvent`) with unchanged `event_id`.
- **Remove:** the GA4 Measurement Protocol purchase dispatch
  (`SendGa4McEvent`) — GA4 purchase is browser-only now.
- `reportRefund()` unchanged: still guards on `purchase_reported_at` (now set
  at placement) and still sends the GA4 MP refund + logic as today.

### 5. Call-site moves

- **Add:** `reportPurchase($order)` called at order placement. All three
  creation paths go through `StorefrontService::createOrder()`
  (`CheckoutController::store()`, `AiAssistant\QuickCheckout`,
  `AiAssistant\ChatController::placeOrder`), but `CheckoutController` captures
  `tracking_data` (fbp/fbc/ga_client_id/ip/ua) *after* `createOrder()` returns
  — and CAPI `user_data` needs it. So the call lands in each caller after
  order creation + identifier capture (in `CheckoutController::store()` after
  the `tracking_data` update; in the two AI paths after their
  `createOrder()` calls, capturing identifiers there too if not already).
- **Remove:** `reportPurchase()` calls from
  `EcommerceService::updateOrderStatus()` (line ~100) and
  `SaleService::syncEcommerceOrder()` (line ~572).
- **Keep:** both `reportRefund()` call sites (status → cancelled/refunded).

## Behavior change (accepted trade-off)

Purchases are now counted at **order placement** (pending), before admin
confirmation. Fake/cancelled orders that pass the fraud gate will initially
count; the server-side GA4 `refund` (and Meta's post-hoc signals) are the
correction mechanism. This is inherent to success-page firing and was
explicitly accepted.

## Testing

- `TrackingServiceTest`: purchase dispatches `SendFbCapiEvent` but **not**
  `SendGa4McEvent`; once-guard and fraud gate unchanged; refund path
  unchanged.
- New feature test for the success page:
  - with flash + clean order → tracking script present exactly once, payload
    contains `transaction_id`, `eventID`, items;
  - without flash (reload) → no tracking script;
  - fraud-blocked order → no tracking script even with flash.
- Feature test: placing an order via checkout dispatches the CAPI job at
  placement; changing order status afterwards does not dispatch another.
