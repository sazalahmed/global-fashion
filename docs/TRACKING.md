# Storefront Analytics — Operations Guide (GTM + Meta Pixel + CAPI)

Conversion tracking for the eCommerce storefront. Browser events fire via GTM `dataLayer` + gtag + Meta Pixel; the **Purchase** fires in the browser on the order success page (once, fraud-gated) with a deduplicated Meta CAPI backup sent server-side at order placement. GA4 purchase is browser-only; the GA4 **Refund** correction stays server-side.

Design: `docs/superpowers/specs/2026-06-13-storefront-gtm-pixel-capi-design.md`, `docs/superpowers/specs/2026-07-06-purchase-event-success-page-design.md`.

## 1. Configure (Admin → Settings → Tracking)
- **GTM:** enable + Container ID (`GTM-XXXX`).
- **Facebook Pixel:** enable + Pixel ID; **Conversions API Access Token** (for server-side Purchase); optional **Meta Test Event Code** for QA.
- **Google Analytics 4:** enable + Measurement ID (`G-XXXX`) — loads gtag.js directly, so ALL browser
  events reach GA4 with **no GTM container configuration**. API Secret powers the server-side
  Purchase/Refund (GA4 Admin → Data Streams → Measurement Protocol API secrets).
- **Suppress Purchase for risk levels:** default `high,critical` — orders whose BD-Courier fraud risk is in this list are NOT reported as conversions.

## 2. Operational requirements (REQUIRED, or events won't land)
1. **Run a queue worker in production** (`php artisan queue:work`). The CAPI Purchase and GA4 Refund jobs (`SendFbCapiEvent`, `SendGa4McEvent`) are queued; without a worker, the server-side backup/correction events are never sent.
2. **GTM container tags are only needed for non-GA4 tags.** With GA4 enabled (gtag.js), browser events
   reach GA4 directly. ⚠ **Never add GA4 tags inside the GTM container while GA4 is enabled here — every
   event would be counted twice.**
3. **⚠ Campaign optimization:** Meta ad campaigns MUST optimize for the **Purchase** event. The fraud gate only protects budget if Purchase is the optimization event — optimizing for AddToCart/InitiateCheckout bypasses it (those fire for everyone, including fraud-risk visitors).

## 3. How Purchase works
Purchase fires in the browser when the shopper lands on the checkout success page straight from checkout (one-time session flash — reloads and shared links never re-fire). The same `event_id` (`purchase.{order_number}`) is sent to Meta CAPI at order placement (deferred until after the fraud check), so Meta dedups browser + server. Both are suppressed for blocked fraud-risk levels; the `ecommerce_orders.purchase_reported_at` guard makes the server send once-only. New customers (no courier history) are reported. AI-assistant orders send CAPI only (no success-page flash). The `_fbp`/`_fbc`/`_ga`/IP/UA captured at placement (`ecommerce_orders.tracking_data`) feed CAPI `user_data` matching. Because Purchase now counts at placement (pending), the GA4 Refund on cancellation is the correction mechanism.

The browser purchase also carries **customer info** (`user_data`: email, E.164 phone, name, country) in Google's enhanced-conversions shape — set on gtag (hashed by Google before sending) and included on the dataLayer push so GTM tags can map it (e.g. for a GTM-managed Meta CAPI tag). `tracking.js` is cache-busted via `?v={filemtime}` — without it, browsers keep an old copy that silently drops newer event params.

A **Refund** is sent to GA4 (same `transaction_id`) when a purchase-reported order is later cancelled or
refunded — once per order (`refund_reported_at` guard), and never for orders whose purchase was suppressed.

## 4. Manual QA checklist (requires real IDs + a browser)
- [ ] GTM Tag Assistant (Preview): `page_view`, `view_item`, `view_item_list`, `search`, `add_to_cart`, `view_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info` with correct `ecommerce.value`/`items`.
- [ ] Meta Pixel Helper: `PageView`, `ViewContent`, `AddToCart`, `InitiateCheckout`, `AddPaymentInfo`, `Lead`, `CompleteRegistration` with `content_ids` + `value` + `currency:BDT`.
- [ ] With a Test Event Code set, place a test order in the browser → `Purchase` appears in Meta **Test Events** twice (browser + server) but **deduplicated** to one conversion, and `purchase` appears **once** in **GA4 DebugView** (browser only).
- [ ] Reload the success page (or open its URL in another tab) → nothing re-fires.
- [ ] Place an order with a **high-risk** phone (success_ratio < 60) → **no** Purchase reaches Meta/GA4, but `purchase_reported_at` is set.
- [ ] Confirm/ship the order from admin afterwards → Purchase does **not** fire again.
- [ ] All event values are raw numbers (e.g. `1499`), never `"BDT 1,499"`.
- [ ] GA4 DebugView (with GA4 enabled): `page_view`, `view_item`, `view_item_list`, `select_item`, `search`, `view_promotion`, `select_promotion`, `add_to_cart`, `remove_from_wishlist` custom event.
- [ ] Cancel a confirmed (reported) order → `refund` appears in GA4 with the original transaction_id.

## 5. Future (out of scope)
Meta Custom Audience exclusion of high-risk customers; landing-page & POS conversions; product catalog feed for dynamic ads.
