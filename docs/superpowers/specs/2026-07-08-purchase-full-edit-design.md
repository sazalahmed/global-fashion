# Purchase — Full Edit incl. Received POs (P2)

**Date:** 2026-07-08
**Module:** `Modules/Purchase`
**Status:** Approved

## Problems
- Index "Edit" link points to `purchases.show` (bug).
- Edit restricted to draft/pending; received/approved POs can't be edited.

## Changes

### Index view
- Fix the Edit dropdown link → `route('purchases.edit', $po)`.

### PurchaseController
- `edit()`: allow any status except cancelled (was draft/pending only).
- `update()`: unchanged signature; service enforces rules.

### PurchaseService::update()
- Block if status === cancelled → throw.
- Block if the PO has non-cancelled purchase returns → throw (handle returns first).
- Transaction:
  1. Reverse received GRN stock (reuse delete()'s safe logic: reverse
     min(accepted, on-hand), log shortfall), delete GRN items + GRNs.
  2. Void the purchase JE (accountingService->voidJournalEntry('purchase', id)).
  3. `$purchase->update($headerData)`; delete old items; createItems(new); calculateTotals.
  4. Re-record purchase JE if status !== draft.
  5. recordPayments(new payments); calculateTotals already sets due from paid_amount.
  6. If the PO had GRNs (was partially/received), set status back to 'approved'
     so it can be received again. Otherwise keep submitted status.
- Extract the GRN stock-reversal into a private helper reused by delete() and update().

### Edit view
- Warning banner when $purchase has GRNs: "saving will reverse received stock;
  you'll need to receive again."

## Verification
- Edit a draft PO (no receipts): items/totals update; JE re-posted; no stock change.
- Edit an approved+received PO: received stock reversed, GRNs removed, status→approved,
  items replaced, JE re-posted, due recalculated. Re-receive works.
- Edit blocked when a purchase return exists.
- Index Edit link opens the edit page.
- Regression: delete() still works (shared reversal helper).
