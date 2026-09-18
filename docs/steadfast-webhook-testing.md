# Steadfast Webhook — Postman Testing Guide

Real payload formats captured from Steadfast's live webhook (`laravel (1).log`,
2026‑05‑24/25) plus ready‑to‑fire requests against our seeded test sales.

---

## Endpoint

| | |
|---|---|
| **Method** | `POST` |
| **URL (local)** | `http://127.0.0.1:8000/api/webhooks/steadfast` |
| **URL (prod)** | `{APP_URL}/api/webhooks/steadfast` |

### Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer steadfast-test-token
```
> `steadfast-test-token` is the temporary token currently set on the Steadfast
> CourierProvider row for local testing. In production this must equal the
> **Auth Token** configured on the Steadfast dashboard. A wrong/missing token
> returns **401**.

---

## Notification types

Steadfast sends **three** `notification_type` values (all confirmed in the log):

| `notification_type` | Has `status`? | Has `cod_amount`? | Our handling |
|---|---|---|---|
| `delivery_status` | ✅ yes | ✅ yes | Updates sale status + moves stock (see mapping) |
| `tracking_update` | ❌ no | ❌ no | Records `courier_status` from `tracking_message`; no stock change |
| `return_status` | ❌ no | ❌ no | Treated as a tracking update (message recorded); no stock change |

### Common fields (all types)
`notification_type`, `consignment_id` (integer), `invoice` (string, **can be `null`**),
`tracking_id`, `tracking_message`, `updated_at` (`YYYY-MM-DD HH:MM:SS`).
`delivery_status` adds `status`, `cod_amount`, `delivery_charge`.

---

## `delivery_status` — values & effect

Statuses seen in the log: `delivered` (29×), `pending` (45×), `cancelled` (11×), `partial_delivered` (5×).

| Steadfast `status` | Sale becomes | Stock effect | Payment effect |
|---|---|---|---|
| `delivered` | `delivered` | **deduct** (once; repeat = no double) | `paid_amount` = `courier_collected_amount` = `cod_amount`; due/status recomputed |
| `partial_delivered` | `delivered` | **deduct** | `paid_amount` = `cod_amount` initially, then **corrected by the "Amount has been changed" tracking message** (the actually-collected amount) |
| `pending` | `courier` | **deduct** (in transit = goods out) | none — `paid_amount` stays 0 (COD not collected yet) |
| `cancelled` | `cancelled` | **restore** if previously deducted | reset — `paid_amount` & `courier_collected_amount` → 0 |
| `delivered_approval_pending`, `partial_delivered_approval_pending`, `cancelled_approval_pending`, `unknown_approval_pending`, `hold`, `in_review`, `unknown` | unchanged | none — only `courier_status` recorded | none |

The sale is matched by `invoice` → our `invoice_number`/order `order_number`/`reference_number`, then by `consignment_id`. Unknown → **404**.

> **COD is only money in hand once delivered.** `cod_amount` is the parcel's COD
> *value* (present on pending/cancelled events too), so it is booked as
> `paid_amount` only on `delivered`/`partial_delivered`. For **partial
> deliveries** Steadfast revises the real collected amount via a
> `tracking_update` — `"Amount has been changed from 1770 to 50"` — and the
> handler applies that `to`-value (50) as the collected/paid amount on the
> already-delivered sale; the rest is returned (a `return_status` follows). The
> returned items' **stock is not auto-restored** (the webhook has no line items)
> — file a SaleReturn to restore them.

---

## Real payload samples (from the log)

### delivery_status — delivered
```json
{
  "notification_type": "delivery_status",
  "consignment_id": 253393446,
  "invoice": "789",
  "tracking_id": "SFR260521ST0263CE2BD",
  "status": "delivered",
  "cod_amount": 910,
  "delivery_charge": 115,
  "tracking_message": "Consignment status has been updated as Delivered",
  "updated_at": "2026-05-24 13:51:09"
}
```

### delivery_status — cancelled
```json
{
  "notification_type": "delivery_status",
  "consignment_id": 252311938,
  "invoice": "774",
  "tracking_id": "SFR260519STB628F9DBD",
  "status": "cancelled",
  "cod_amount": 850,
  "delivery_charge": 115,
  "tracking_message": "Consignment status has been updated as Cancelled",
  "updated_at": "2026-05-25 19:30:05"
}
```

### delivery_status — partial_delivered
```json
{
  "notification_type": "delivery_status",
  "consignment_id": 254192002,
  "invoice": "820",
  "tracking_id": "SFR260523ST0000000BD",
  "status": "partial_delivered",
  "cod_amount": 1770,
  "delivery_charge": 115,
  "tracking_message": "Consignment status has been updated as Partial Delivered",
  "updated_at": "2026-05-24 12:00:00"
}
```

### tracking_update
```json
{
  "notification_type": "tracking_update",
  "consignment_id": 253391345,
  "invoice": "790",
  "tracking_id": "SFR260521ST7FC6567BD",
  "tracking_message": "Consignment sent to MIRPUR WAREHOUSE.  Dispatch ID: 14340101",
  "updated_at": "2026-05-24 00:25:56"
}
```

### return_status
```json
{
  "notification_type": "return_status",
  "consignment_id": 253391345,
  "invoice": "790",
  "tracking_id": "SFR260521ST7FC6567BD",
  "tracking_message": "Consignment Return status has been updated to Processing",
  "updated_at": "2026-05-24 15:38:50"
}
```

---

## Ready‑to‑fire against our seeded test sales

Five pending test sales already carry consignment numbers — **one status assigned per sale** so every delivery status is covered:

| Sale ID | invoice | consignment_id | qty | Test status | Expected stock effect |
|---|---|---|---|---|---|
| 2 | `S20260610001` | `1500001` | 2 | `delivered` | **−2** (deduct) |
| 3 | `S20260610002` | `1500002` | 2 | `pending` | **−2** (→ `courier`) |
| 4 | `S20260610003` | `1500003` | 2 | `partial_delivered` | **−2** (deduct) |
| 5 | `S20260610004` | `1500004` | 2 | `cancelled` → then `delivered`+`cancelled` to see restore | see note |
| 6 | `S20260610005` | `1500005` | 2 | `tracking_update` / `return_status` | none |

### 1) sale #2 — `delivered` (match by `invoice`) → deducts 2
```json
{
  "notification_type": "delivery_status",
  "consignment_id": 1500001,
  "invoice": "S20260610001",
  "tracking_id": "SFR-TEST-0001",
  "status": "delivered",
  "cod_amount": 3600,
  "delivery_charge": 120,
  "tracking_message": "Consignment status has been updated as Delivered",
  "updated_at": "2026-06-10 10:00:00"
}
```

### 2) sale #3 — `pending` → becomes `courier`, deducts 2
```json
{
  "notification_type": "delivery_status",
  "consignment_id": 1500002,
  "invoice": "S20260610002",
  "tracking_id": "SFR-TEST-0002",
  "status": "pending",
  "cod_amount": 3600,
  "delivery_charge": 120,
  "tracking_message": "Consignment has been created",
  "updated_at": "2026-06-10 10:05:00"
}
```

### 3) sale #4 — `partial_delivered` → becomes `delivered`, deducts 2
```json
{
  "notification_type": "delivery_status",
  "consignment_id": 1500003,
  "invoice": "S20260610003",
  "tracking_id": "SFR-TEST-0003",
  "status": "partial_delivered",
  "cod_amount": 3400,
  "delivery_charge": 120,
  "tracking_message": "Consignment status has been updated as Partial Delivered",
  "updated_at": "2026-06-10 10:10:00"
}
```

### 4) sale #5 — `cancelled` (match by `consignment_id`, `invoice` null)
```json
{
  "notification_type": "delivery_status",
  "consignment_id": 1500004,
  "invoice": null,
  "tracking_id": "SFR-TEST-0004",
  "status": "cancelled",
  "cod_amount": 0,
  "delivery_charge": 0,
  "tracking_message": "Consignment status has been updated as Cancelled",
  "updated_at": "2026-06-10 10:15:00"
}
```
> Sale #5 starts `pending` (not deducted), so this `cancelled` only flips the
> status — stock stays the same (nothing to restore). **To see the restore**,
> first send a `delivered` for `1500004`, confirm stock −2, then send this
> `cancelled` and confirm stock +2 back.

### 5) sale #6 — `tracking_update` then `return_status` (no stock change)
```json
{
  "notification_type": "tracking_update",
  "consignment_id": 1500005,
  "invoice": "S20260610005",
  "tracking_id": "SFR-TEST-0005",
  "tracking_message": "Consignment sent to KHULNA WAREHOUSE.  Dispatch ID: 99999",
  "updated_at": "2026-06-10 10:20:00"
}
```
```json
{
  "notification_type": "return_status",
  "consignment_id": 1500005,
  "invoice": "S20260610005",
  "tracking_id": "SFR-TEST-0005",
  "tracking_message": "Consignment Return status has been updated to Processing",
  "updated_at": "2026-06-10 10:25:00"
}
```

> **Idempotency / repeat test:** re-send request #1 (`delivered` for `1500001`)
> a second time — stock must stay at the same value (no double deduction).

---

## Expected responses

| Case | HTTP | Body |
|---|---|---|
| Valid event | `200` | `{"status":"success","message":"Webhook received successfully."}` |
| Wrong/missing `Authorization` | `401` | `{"status":"error","message":"Invalid auth token."}` |
| Unknown invoice & consignment | `404` | `{"status":"error","message":"Order not found for the given invoice/consignment."}` |

## Verify results (run in terminal)
```bash
php artisan tinker --execute="foreach(\Modules\Sale\Models\Sale::whereIn('id',[2,3,4,5,6])->get() as \$s){\$pid=\$s->items()->first()->product_id;echo \$s->invoice_number.' status='.\$s->status.' courier='.\$s->courier_status.' cod='.(float)\$s->courier_collected_amount.' stock='.(int)\Modules\Inventory\Models\WarehouseStock::where('product_id',\$pid)->whereNull('variant_id')->sum('quantity').PHP_EOL;}"
```

---

## Complete order lifecycle (from real log data)

Every event Steadfast actually sent, in order. Only **`delivery_status`** events
carry a `status` and move our sale/stock; `tracking_update` and `return_status`
are informational (stored on `courier_status` + a `CourierTrackingEvent`).

```
   ┌─ We create the parcel  → Steadfast returns consignment_id  (sale: pending/courier)
   │
   ▼
1. delivery_status  status=pending        "...updated as Pending"            → sale=courier   ★ stock −qty
2. tracking_update                         "received at <HUB> WAREHOUSE"      → info only
3. tracking_update                         "sent to <HUB>. Dispatch ID: …"    → info only (repeats per hub change)
4. tracking_update                         "processing for delivery"          → info only
5. tracking_update                         "Assigned to rider for delivery"   → info only
6. tracking_update                         "Parcel#… <rider note>" (optional) → info only (call/reschedule notes)
   │
   ├──► 7a. delivery_status status=delivered          "...updated as Delivered"        → sale=delivered ★ (already out → no double)
   │
   ├──► 7b. delivery_status status=partial_delivered  "...updated as Partial_delivered"→ sale=delivered ★
   │
   └──► 7c. delivery_status status=cancelled          "...cancelled by system" /
                                                       "...updated as Cancelled"        → sale=cancelled ★ stock +qty (restore)
            │
            ▼
        8. tracking_update      "sent to FULFILLMENT WAREHOUSE"   (return leg) → info only
        9. return_status        "Return status updated to Processing"          → info only
```

### Every event message seen, mapped

| Stage | `notification_type` | `status` | Example message | Our effect |
|---|---|---|---|---|
| Accepted / pending | `delivery_status` | `pending` | "…updated as Pending" | sale → `courier`, **stock −qty** |
| Received at hub | `tracking_update` | — | "Consignment has been received at MIRPUR WAREHOUSE." | record only |
| **Hub change** | `tracking_update` | — | "Consignment sent to RUPGANJ. Dispatch ID: 14337574" | record only |
| Processing | `tracking_update` | — | "Consignment has been processing for delivery." | record only |
| **Assigned to rider** | `tracking_update` | — | "Assigned to rider for delivery." | record only |
| Rider note | `tracking_update` | — | "Parcel#… Customer parcel kalke nibe…" | record only |
| COD changed (partial collect) | `tracking_update` | — | "Amount has been changed from 1770 to 50" | **updates collected/paid** to the new amount if the sale is already `delivered` |
| Address fix | `tracking_update` | — | "Policestation: 'X' to 'Y'." | record only |
| **Delivered** | `delivery_status` | `delivered` | "…updated as Delivered" | sale → `delivered`, **stock −qty** (idempotent) |
| **Partial delivered** | `delivery_status` | `partial_delivered` | "…updated as Partial_delivered" | sale → `delivered`, **stock −qty** |
| **Cancelled** | `delivery_status` | `cancelled` | "…cancelled by system" / "…updated as Cancelled" | sale → `cancelled`, **stock +qty restore** |
| Return processing | `return_status` | — | "Return status updated to Processing" | record only |

### Real traced timelines

**Delivered (consignment 253767964, invoice 805):**
```
04:32  received at UKHIYA (COX'S BAZAR)        tracking_update
06:29  Assigned to rider for delivery          tracking_update
15:22  Parcel#… ৮০০ টাকা delivery merchant note tracking_update
15:23  status = delivered                       delivery_status   ★
```

**Cancelled → return (consignment 252311938, invoice 774):**
```
05-25 19:30  cancelled by system / Cancelled   delivery_status   ★ restore
05-27 11:43  sent to FULFILLMENT WAREHOUSE     tracking_update  (return leg)
06-04 23:01  sent to MIRPUR. Dispatch ID …     tracking_update
06-05 15:42  Return status → Processing        return_status
```

> Key point: **hub changes and rider assignment never move stock or the sale
> status** — they're transit breadcrumbs. The sale status (and stock) only
> changes on the 4 `delivery_status` values: `pending`, `delivered`,
> `partial_delivered`, `cancelled`.

---

## Full journey replay (step‑by‑step, every request)

Fire these **in order** against sale #2 (`invoice S20260610001`, `consignment 1500001`)
to replay a complete delivered journey. Same endpoint + headers each time.

**Step 1 — pending (accepted)** → sale `courier`, stock −2
```json
{ "notification_type": "delivery_status", "consignment_id": 1500001, "invoice": "S20260610001", "tracking_id": "SFR-TEST-0001", "status": "pending", "cod_amount": 3600, "delivery_charge": 120, "tracking_message": "Consignment status has been updated as Pending", "updated_at": "2026-06-10 09:00:00" }
```
**Step 2 — received at hub** (info only)
```json
{ "notification_type": "tracking_update", "consignment_id": 1500001, "invoice": "S20260610001", "tracking_id": "SFR-TEST-0001", "tracking_message": "Consignment has been received at MIRPUR WAREHOUSE.", "updated_at": "2026-06-10 09:10:00" }
```
**Step 3 — hub change / dispatch** (info only)
```json
{ "notification_type": "tracking_update", "consignment_id": 1500001, "invoice": "S20260610001", "tracking_id": "SFR-TEST-0001", "tracking_message": "Consignment sent to MIRPUR.  Dispatch ID: 14337351", "updated_at": "2026-06-10 09:20:00" }
```
**Step 4 — processing for delivery** (info only)
```json
{ "notification_type": "tracking_update", "consignment_id": 1500001, "invoice": "S20260610001", "tracking_id": "SFR-TEST-0001", "tracking_message": "Consignment has been processing for delivery.", "updated_at": "2026-06-10 09:30:00" }
```
**Step 5 — assigned to rider** (info only)
```json
{ "notification_type": "tracking_update", "consignment_id": 1500001, "invoice": "S20260610001", "tracking_id": "SFR-TEST-0001", "tracking_message": "Assigned to rider for delivery.", "updated_at": "2026-06-10 09:40:00" }
```
**Step 6 — rider note** (info only, optional)
```json
{ "notification_type": "tracking_update", "consignment_id": 1500001, "invoice": "S20260610001", "tracking_id": "SFR-TEST-0001", "tracking_message": "Parcel#1500001 - Customer parcel nibe Bolche", "updated_at": "2026-06-10 09:50:00" }
```
**Step 7 — delivered** → sale `delivered`, stock unchanged (already −2 at step 1; no double)
```json
{ "notification_type": "delivery_status", "consignment_id": 1500001, "invoice": "S20260610001", "tracking_id": "SFR-TEST-0001", "status": "delivered", "cod_amount": 3600, "delivery_charge": 120, "tracking_message": "Consignment status has been updated as Delivered", "updated_at": "2026-06-10 10:00:00" }
```

### Cancel → return variant (sale #5, `consignment 1500004`)
Send a `delivered` first if you want to see the restore; otherwise start here.

**Step A — cancelled** → sale `cancelled`, stock +2 (restore, if it was deducted)
```json
{ "notification_type": "delivery_status", "consignment_id": 1500004, "invoice": "S20260610004", "tracking_id": "SFR-TEST-0004", "status": "cancelled", "cod_amount": 0, "delivery_charge": 0, "tracking_message": "Consignment has been cancelled by system.", "updated_at": "2026-06-10 11:00:00" }
```
**Step B — return leg** (info only)
```json
{ "notification_type": "tracking_update", "consignment_id": 1500004, "invoice": "S20260610004", "tracking_id": "SFR-TEST-0004", "tracking_message": "Consignment sent to FULFILLMENT WAREHOUSE.  Dispatch ID: 14413931", "updated_at": "2026-06-10 11:30:00" }
```
**Step C — return processing** (info only)
```json
{ "notification_type": "return_status", "consignment_id": 1500004, "invoice": "S20260610004", "tracking_id": "SFR-TEST-0004", "tracking_message": "Consignment Return status has been updated to Processing", "updated_at": "2026-06-10 12:00:00" }
```

### Other tracking events (info only — no stock/status change)
**COD amount changed**
```json
{ "notification_type": "tracking_update", "consignment_id": 1500001, "invoice": "S20260610001", "tracking_id": "SFR-TEST-0001", "tracking_message": "Amount has been changed from 3600 to 3400.", "updated_at": "2026-06-10 09:15:00" }
```
**Address / policestation correction**
```json
{ "notification_type": "tracking_update", "consignment_id": 1500001, "invoice": "S20260610001", "tracking_id": "SFR-TEST-0001", "tracking_message": "Policestation: 'khagrachari sadar' to 'Kamrangirchar'.", "updated_at": "2026-06-10 09:16:00" }
```

---

## Notes
- `cod_amount` arrives as an integer (e.g. `850`) and is stored on `sale.courier_collected_amount`.
- `delivery_charge` and `tracking_id` are received but not currently persisted (safe to ignore).
- `return_status` is currently recorded as a tracking update (message only, no stock movement). If you want returns to drive stock, that should go through the SaleReturn module, not the webhook.
- Duplicate `delivered`/`cancelled` events are safe — stock movement is idempotent (guarded by the stock ledger).
