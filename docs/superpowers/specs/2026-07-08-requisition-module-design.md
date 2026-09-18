# Requisition Module (P4)

**Date:** 2026-07-08
**Module:** `Modules/Purchase` (requisitions live inside purchasing)
**Status:** Approved

## Flow
Pending (submitted) → Approved / Rejected → Ordered (converted to a purchase) →
Fulfilled (linked purchase received). Cancelled is also possible from pending.

## Schema (migration)
- `requisitions`: id, requisition_number (unique), requested_by (user id nullable),
  department (nullable), branch_id (nullable), required_date (nullable date),
  priority (low|normal|high, default normal), status (default pending),
  note (nullable), reviewed_by (nullable), reviewed_at (nullable),
  rejection_reason (nullable), purchase_id (nullable), created_by, timestamps, softDeletes.
- `requisition_items`: id, requisition_id FK cascade, product_id, variant_id nullable,
  quantity decimal(15,2), note nullable, timestamps.

## Models
- `Requisition`: fillable/casts; items() hasMany; requester()/reviewer()/creator()/purchase();
  scopes byStatus; statusCounts helper; STATUS_* consts.
- `RequisitionItem`: fillable; requisition(), product(), variant().

## Service — `RequisitionService`
- list(filters), statusCounts(), getStats().
- create(data, items) — number REQ-YYYY-####, status pending, requested_by = auth/user.
- update(req, data, items) — only when pending.
- approve(req) / reject(req, reason) — only from pending → approved/rejected; set reviewed_by/at.
- cancel(req) — from pending/approved.
- linkPurchase(req, purchase) — set purchase_id + status ordered (called from purchase store).
- markFulfilled(req) — only when ordered and the linked purchase is received.
- delete(req) — only draft/pending/rejected/cancelled.

## Controller + routes (requisitions.* under /requisitions)
- resourceful: index, create, store, show, edit, update, destroy
- POST {req}/approve, {req}/reject, {req}/cancel, {req}/mark-fulfilled
- GET  {req}/convert → redirect to purchases.create?requisition_id={id}
- Permissions: purchases.view / create / edit / approve / delete.

## Purchase integration
- `PurchaseController@create`: accept `requisition_id`; if present + approved, pass the
  requisition (with items) to prefill the item rows and a hidden `requisition_id` field.
- `StorePurchaseRequest`: allow nullable `requisition_id` exists:requisitions,id.
- `PurchaseController@store` / `PurchaseService@create`: if requisition_id present, after
  creating the purchase call RequisitionService::linkPurchase (status → ordered).
- create.blade.php: render prefilled rows when $requisition passed; hidden requisition_id.

## Views (Modules/Purchase/resources/views/requisitions/)
- index: stats + status filter + table (number, requester, items count, required date,
  status badge, actions) + export dropdown (module optional — skip export for now).
- create/edit: requester (default current user, editable name/department), required date,
  priority, branch, product picker rows (product + qty + note; no price/supplier), note.
- show: header, items table, status timeline, review actions (approve/reject/cancel),
  Convert-to-Purchase (when approved), linked purchase + Mark-Fulfilled (when ordered).

## Sidebar
- "Requisitions" under the Purchases submenu (route requisitions.index, active requisitions.*).

## Verification
- Create requisition → pending; approve → approved; reject → rejected (reason).
- Convert approved → purchase create prefilled; store → purchase created, requisition ordered + linked.
- Receive the linked purchase → Mark Fulfilled → fulfilled.
- Pending-only edit; delete guards.
- Pages 200; sidebar link active.
