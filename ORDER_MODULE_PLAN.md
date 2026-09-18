# Order Module — Implementation Plan (Items 4–20)

**Scope:** everything from the gap analysis except the three biggest dependencies (#1 payment methods, #2 SMS pipeline, #3 district address selector — those are foundation pieces that deserve their own plan once you choose providers).

**Audience:** Bangladeshi SMB shopkeeper. Plan optimizes for the COD-dominant, phone-first, low-volume reality of these shops, not enterprise complexity.

**Total estimated effort:** ~9–11 working days, broken into 5 phases. Each phase ships independently so the shop is never broken between phases.

---

## Phase 0 — Foundation tables (½ day, no UI)

These three tables back several later items. Cheap to add now, hard to retrofit.

### 0.1 `ecommerce_order_status_logs`
```
id, ecommerce_order_id, from_status, to_status, changed_by (FK users), note (nullable), created_at
```
Index on `ecommerce_order_id`.

### 0.2 `ecommerce_order_returns`
```
id, ecommerce_order_id, return_number, status (requested|approved|rejected|received|refunded),
reason, customer_note, admin_note, refund_method, refund_amount, refund_trx_id,
requested_by (customer or admin), processed_by (FK users, nullable),
created_at, updated_at, deleted_at
```

### 0.3 `ecommerce_order_returns_items` (line items being returned)
```
id, ecommerce_order_return_id, ecommerce_order_item_id, quantity, line_amount, condition (good|damaged), restocked (bool)
```

### 0.4 Columns added to `ecommerce_orders` (single migration)
```
- confirmation_called_at  TIMESTAMP NULL
- confirmation_attempts   TINYINT DEFAULT 0
- confirmation_notes      TEXT NULL
- cancellation_reason     VARCHAR(50) NULL    (enum: oos, customer_change, fraud, address_invalid, duplicate, other)
- cancellation_note       TEXT NULL
- cod_attempts            TINYINT DEFAULT 0
- last_attempt_at         TIMESTAMP NULL
- last_attempt_note       TEXT NULL
- vip_priority            BOOLEAN DEFAULT 0
```

### 0.5 Columns added to `warehouse_stock`
```
- (existing) reserved_quantity already exists
```
✓ No new columns needed for stock reservation — table already has `reserved_quantity`.

---

## Phase 1 — Operational essentials (2 days)

### #4 Order status timeline & history
**Backend**
- New `OrderStatusLogService::log($order, $from, $to, $note = null)` writes to `ecommerce_order_status_logs`.
- Hook into `EcommerceService::updateOrderStatus()` — log every transition.
- Backfill seeder for existing orders (insert one log per order at current status).

**Admin UI**
- On order show page: vertical timeline component above the items table — dot per status with date/time, who changed it, optional note.

**Customer UI**
- Same component on `My Orders → Order Detail` (already exists in storefront customer pages).

**Effort:** 4 hours.

### #5 Customer confirmation call log
**Backend**
- Add `confirmCall($order, $note, $reached_customer = true)` on `EcommerceService` — sets `confirmation_called_at`, increments `confirmation_attempts`, optionally moves status pending→confirmed.

**Admin UI**
- Order show page: prominent button "Mark Customer Confirmed" — opens small modal with note field + checkbox "Reached customer (else mark as attempt only)".
- Sidebar shows attempt count + last call timestamp.
- Filter on order list: "Pending — needs confirmation call"; auto-prioritize by created_at desc.

**Effort:** 4 hours.

### #7 Cancellation reason
**Backend**
- `cancelOrder($order, $reason, $note)` validates reason against the enum, writes both columns, then proceeds with the existing cancel path (which already voids the JE and reverses stock).

**Admin UI**
- "Cancel Order" button opens a small dialog: dropdown (Out of Stock, Customer Change, Fraud, Invalid Address, Duplicate, Other) + free-text note.
- Order list filter by cancellation reason for analysis.

**Reports add-on**
- New stat card on Ecommerce dashboard: "Cancelled this month" + breakdown by reason.

**Effort:** 3 hours.

### #8 COD delivery attempt tracking
**Backend**
- `recordDeliveryAttempt($order, $note, $is_final = false)` — increments `cod_attempts`, sets `last_attempt_at` and `last_attempt_note`. If `is_final`, transitions status to `delivery_failed` (new status).
- New status: `delivery_failed` added to the order status enum.

**Admin UI**
- On in-transit / shipped orders, "Log Attempt" button → modal with note + "Final attempt — return to sender" checkbox.
- Filter on order list: "Failed delivery — pending merchandise return".

**Auto-pull from courier**
- (Optional, depends on courier API capability) Each courier service in `Couriers/` exposes `getStatus($consignmentId)` — schedule a daily job to refresh `courier_status` and infer attempt count where the courier reports it.

**Effort:** 5 hours (without courier auto-pull) / +6 hours with auto-pull.

### #9 Stock reservation on order placement
**Backend**
- On order placed (`StorefrontService::createOrder`): increment `warehouse_stock.reserved_quantity` for each item.
- On order cancelled / delivery_failed: decrement reserved.
- On order shipped: decrement reserved (the actual stock came out via the existing JE / GRN flow).
- New scope on `WarehouseStock` model: `available()` returns `quantity - reserved_quantity`.
- Update product cards / cart / checkout to use `available()` not `quantity`.

**Risk control**
- DB transaction with row-level lock when incrementing reserved.
- Cron job: orders stuck in `pending` longer than 48 hours auto-cancel + release reservation.

**Effort:** 6 hours including the 48-hour cleanup job.

---

## Phase 2 — Print & customer-facing ops (2 days)

### #10 Print views (Invoice + Packing slip + Address label)

**Three new routes / views:**
1. `GET /admin/ecommerce/orders/{order}/invoice` — A4 invoice with company logo, order number, dates, customer name/phone/address, line items, subtotal/tax/shipping/total, BDT lakh format, "Mushak 6.3" header for VAT-registered shops.
2. `GET /admin/ecommerce/orders/{order}/packing-slip` — A6/A5 size, items + quantity for warehouse staff. No prices.
3. `GET /admin/ecommerce/orders/{order}/address-label` — courier-format address label, ~4×6 inch, big customer name + phone + full address + COD amount + courier consignment.

**Tech**
- Use existing dompdf if installed, otherwise plain HTML with print-friendly CSS (`@media print { ... }`). For label, use thermal-printer-friendly 80mm CSS.
- All three reuse a base layout `print/base.blade.php` with company config from Settings → Business.

**Bulk print**
- "Selected → Print Address Labels" on order list (bulk action) generates a single PDF with N labels for batch courier handoff.

**Effort:** 1 day (design + 3 templates + bulk PDF).

### #11 Phone-only Quick Buy
**Storefront**
- "Quick Order" button on product detail page → opens slide-up form with: name, phone, address (uses #3 if available, else textarea), qty.
- Server-side: if no email, generate `phone+timestamp@guest.local`.
- Skip account creation entirely; this stays as a guest order.
- Form sets `source = 'quick_buy'`.

**Effort:** 4 hours.

### #12 Public order tracking page
**Routes**
- `GET /track-order` — form: order number + phone.
- `POST /track-order` — validates the pair, redirects to `/track-order/{order_number}` with phone hash in session.
- `GET /track-order/{order_number}` — shows the timeline (#4 component), courier tracking number with deep link to courier's site.

**Anti-enumeration**
- Rate limit by IP (10 lookups/min) so attackers can't sweep order numbers.
- Phone+order_number must match — else generic "Order not found" (no oracle leak).

**Effort:** 4 hours.

### #6 Returns / refunds workflow

**Customer side**
- On `My Orders → Order Detail` for delivered orders: "Request Return" button (only if delivered_at within last 14 days — config setting).
- Form: select line items + quantities, reason dropdown, note, optional photos.
- Creates `ecommerce_order_returns` row + items, status=`requested`.

**Admin side**
- New menu under Ecommerce → Returns. List with filters (status, date, customer).
- Detail page: approve / reject (with reason) / mark received / refund.
- On `received`: optionally restock (per-line `restocked` checkbox).
- On `refunded`: pick refund method (cash / bKash / Nagad / bank), enter Trx ID for digital refunds, log amount.
- All transitions logged to status logs table.

**JE wiring**
- On approved + received + restocked: call existing `recordSaleReturn` / `recordEcommerceOrderReturn` (new wrapper) to reverse the original revenue + tax.
- On refund: call `PaymentService::create(['direction' => 'refund', ...])` (new direction).

**Effort:** 1 day.

---

## Phase 3 — Channel & flexibility (1.5 days)

### #13 WhatsApp ordering button
**Setting**: `ecommerce.whatsapp_number` in Business Settings.
**Storefront**: each product detail page gets a green "Order via WhatsApp" button that opens `https://wa.me/<number>?text=<encoded message>` with product name + slug URL prefilled.
**Tracking**: when admin manually creates an order from a WhatsApp inquiry, set `source='whatsapp'`.

**Effort:** 1 hour.

### #14 Multi-channel source tagging
**Backend**: existing `source` column. Populate it everywhere an order is created:
- Storefront checkout → `'storefront'`
- Quick Buy → `'quick_buy'`
- Admin manual create → dropdown (`whatsapp`, `phone`, `facebook`, `instagram`, `walk_in`, `referral`)
- POS → `'pos'`

**Reports**: new section on Ecommerce dashboard — orders / revenue split by source. Used for marketing attribution.

**Effort:** 3 hours including dashboard widget.

### #15 COD amount cap
**Setting**: `ecommerce.cod_max_amount` (default null = no cap).
**Checkout validation**: if order grand_total > cod_max_amount AND payment_method = COD → reject with message "COD limited to BDT X. Please use bKash/Nagad for higher amounts."
**Or alternative**: prompt for partial advance via bKash + remainder COD (advanced — defer to Phase 5 if at all).

**Effort:** 2 hours.

### #16 Partial fulfillment / split shipment
**Schema**
- New table `ecommerce_order_shipments` (order_id, shipment_number, courier_provider_id, consignment_id, tracking_number, shipped_at, delivered_at, status).
- New table `ecommerce_order_shipment_items` (shipment_id, order_item_id, quantity).
- Existing tracking columns on `ecommerce_orders` become deprecated (kept for backward compatibility, populated from primary shipment).

**Admin UI**
- Order detail: new "Create Shipment" panel — checkboxes per item with qty input, then dispatch.
- Order can have status `partially_shipped`.

**Effort:** 1 day. *Assess actual customer demand before building — many BD shops never need this.*

---

## Phase 4 — Quality of life (1.5 days)

### #17 Bangla + English templates
**Setting**: per-status templates for SMS + Email, both in EN and BN. Stored in `email_templates` table (already exists).
**UI**: Settings → Notifications → Templates. Live preview with sample data.
**Engine**: simple `{{customer_name}}`, `{{order_number}}`, `{{tracking_url}}` placeholders.
**Send**: requires Phase 1 SMS pipeline (item #2). Without #2, this only works for email.

**Effort:** 4 hours (assuming #2 exists).

### #18 VIP / repeat customer flagging
**Auto-rule** (cron, daily): customers with `total_purchased >= X` OR `order_count >= Y` get `is_vip = true`.
**Manual override** on customer detail page.
**Storefront**: order placement of a VIP customer triggers a priority badge + optional auto-confirm (skip the call step from #5).
**Admin**: order list shows a star next to VIP orders; default sort floats VIP to top.

**Effort:** 3 hours.

### #19 Reverse VAT on online order returns
Already mostly handled by `recordSaleReturn` JE wrapper. The gap is for online orders specifically (source_type `ecommerce_order`).

**Add**: `recordEcommerceOrderReturn($return)` to `AccountingIntegrationService` mirroring `recordSaleReturn` but with `source_type='ecommerce_return'`.
**Wire**: into the returns flow (item #6) at the `received + restocked` transition.
**Mushak**: include reversed entry on monthly Mushak 9.1 export.

**Effort:** 2 hours.

### #20 Failed-delivery merchandise reception
**Status**: order in `delivery_failed`.
**Admin action**: "Receive Returned Goods" button on order detail.
**Modal**: per-line condition picker (good — restock, damaged — write off).
**Effects**:
- Per-line `restocked=1`: increment warehouse_stock for the line item.
- Per-line `damaged=1`: post `recordInventoryAdjustment(negative, 'failed_delivery_damage')`.
- Order status moves to `closed_failed`.
- If order was prepaid: trigger refund flow from #6.

**Effort:** 4 hours.

---

## Phase 5 — (Deferred) Advanced

These are listed for completeness but recommended *not* to build until you have actual customer feedback they're needed:

- **bKash Tokenized API** — for direct charge instead of TrxID verification. Requires KYC + production keys from bKash. Build only after volume justifies it.
- **Partial advance + COD remainder** — for high-value items.
- **Auto-fraud-block on N suspicious orders from same number/IP** — extension of existing fraud_score column.
- **Customer review request automation** — N days post-delivery, auto-send SMS asking for product review with link.

---

## Order-of-work summary

| Phase | Items | Days | Ship-blocking? |
|---|---|---|---|
| 0 | Foundation tables | 0.5 | Yes — required by 1, 4, 6, 8 |
| 1 | #4 timeline, #5 call log, #7 cancel reason, #8 COD attempts, #9 stock reservation | 2 | Yes — biggest operational value |
| 2 | #10 prints, #11 quick buy, #12 public tracking, #6 returns | 2 | No — can ship in slices |
| 3 | #13 WhatsApp, #14 source, #15 COD cap, #16 partial ship | 1.5 | No |
| 4 | #17 templates, #18 VIP, #19 reverse VAT, #20 failed delivery | 1.5 | No |
| **Total** | | **~7.5–8 days** | |

## Dependencies on items NOT in this plan

The plan assumes the following are EITHER already done OR will be done before depending phases:

- **#1 Payment methods** — required before #15 (COD cap meaningful only when alternative exists), #6 (refund methods).
- **#2 SMS pipeline** — required before #17 (templates) and meaningfully required by #4, #5, #8, #20 (every status change ideally fires an SMS).
- **#3 Address selector** — required by #11 (Quick Buy form needs district picker).

If you build this plan WITHOUT #1/#2/#3, several items will land but with their notification arms stubbed (UI exists, SMS doesn't fire). That's still OK — wire SMS later as a single drop-in to the existing event hooks.

## Recommended kick-off

Build Phase 0 + Phase 1 in a single ~2.5-day sprint. That gets you:
- Confirmation call log (eliminates fake-COD wastage)
- Status timeline (customer + staff visibility)
- Cancellation reasons (clean dashboards)
- COD attempts (operational truth)
- Stock reservation (no more overselling)

Those five items alone make the system measurably more usable for a BD shopkeeper, with no big external dependencies. Phase 2 can follow whenever — it's all customer-facing polish on top of the operational foundation.
