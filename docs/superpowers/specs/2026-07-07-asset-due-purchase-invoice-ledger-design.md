# Asset — Due Purchase, Invoice & Ledger (Group C)

**Date:** 2026-07-07
**Modules:** `Modules/Asset`, `Modules/Accounting` (integration service)
**Status:** Approved

## Decisions
- Vendor = free-text name on the asset (not linked to Supplier module).
- Partial payments over time via a Record Payment action; each posts a JE.
- Ledger = per-asset transaction ledger with two running balances (Due, Book Value).
- Invoice = printable purchase invoice + a printable receipt per payment.

## Schema (one migration)
Alter `assets`:
- `vendor_name` string nullable
- `vendor_invoice_no` string nullable
- `payment_account_id` foreignId nullable (form already posts this; column was missing)
- `paid_amount` decimal(15,2) default 0
- `due_amount` decimal(15,2) default 0
- `payment_status` string(20) default 'paid'  (paid | partial | unpaid)
Backfill existing rows: `paid_amount = purchase_price`, `due_amount = 0`, `payment_status = 'paid'`.

New `asset_payments`:
- id, `asset_id` FK cascade, `payment_number` string, `amount` decimal(15,2),
  `payment_account_id` nullable, `payment_date` date, `reference` nullable,
  `note` nullable, `journal_entry_id` nullable, `created_by`, timestamps, softDeletes.

## Models
- `AssetPayment` — `$fillable`, casts (amount decimal, payment_date date); `asset()`, `paymentAccount()`, `creator()`.
- `Asset` — add new columns to `$fillable`; casts for money + payment_status; `payments()` hasMany(AssetPayment); `paymentAccount()` belongsTo PaymentAccount.

## Create flow
- Form (`create.blade.php`): add Vendor Name, Vendor Invoice No (optional), Paid Amount (default = purchase price), Payment Account. JS: keep paid ≤ price; show due = price − paid.
- `StoreAssetRequest`: `vendor_name` nullable string; `vendor_invoice_no` nullable string; `paid_amount` nullable numeric min:0 (≤ purchase_price); `payment_account_id` nullable exists.
- `AssetService::create`: paid = min(paid_amount ?? purchase_price, purchase_price); due = purchase_price − paid; status = due<=0 ? paid : (paid>0 ? partial : unpaid). Store fields. Call revised acquisition JE with (paid, dueAccountCode, paymentAccountId).

## Accounting (`AccountingIntegrationService`)
- Revise `recordAssetAcquisition($asset, float $paidAmount, ?int $paymentAccountId)`:
  - DR Fixed Asset (asset_account_code ?? 1500) = purchase_price
  - CR cash/bank (resolve code from payment account type; default 1001) = paidAmount (if > 0)
  - CR Accounts Payable 2001 = due (if > 0)
- Add `recordAssetPayment($assetPayment)`:
  - DR Accounts Payable 2001 = amount
  - CR cash/bank (from payment_account_id) = amount
  - source_type `asset_payment`, source_id = asset_id.
- Reuse the existing payment-account-type → account-code map (cash 1001, mobile_banking 1002, bank/card 1004).

## Service
- `AssetService::recordPayment(Asset $asset, array $data): AssetPayment` — validate amount ≤ due (+epsilon); DB::transaction: create AssetPayment (payment_number via generator), post JE, update asset paid/due/status; return payment.
- `generatePaymentNumber()` → `ASP-YYYY-####` using withTrashed count.
- Ledger builder `assetLedger(Asset $asset): array` — rows from: purchase (asset), payments (asset_payments asc by date), depreciation (journal_entries where source_type='asset_depreciation' and source_id=asset->id), disposal (asset). Each row: date, particulars, cost, paid, depreciation, plus running `due_balance` and `book_value`.

## Controller + routes
- `POST assets/{asset}/payment` → `recordPayment` (name `assets.payment`)
- `GET  assets/{asset}/invoice` → `invoice` (name `assets.invoice`)
- `GET  assets/{asset}/ledger` → `ledger` (name `assets.ledger`)
- `GET  asset-payments/{payment}/receipt` → `paymentReceipt` (name `assets.payment.receipt`)
- Permission: `finance.view` for reads, `finance.edit`/`finance.create` for payment.

## Views
- `show.blade.php`: payment summary (paid/due/status badge), Record-Payment modal button, Invoice + Ledger links, payments-history table with receipt links.
- `invoice.blade.php`: printable purchase invoice (auto-print script, `'use strict';`).
- `payment-receipt.blade.php`: printable payment receipt.
- `ledger.blade.php`: per-asset ledger table with Due + Book-Value running columns and a totals footer.
- All follow bp- classes, dark-mode, named routes, money()/date formatting.

## Verification
- Create asset with paid < price → due recorded; acquisition JE balances (DR 1500 = CR cash + CR 2001).
- Record payment → due decreases, JE balances (DR 2001 / CR cash), AssetPayment row created.
- Overpayment rejected.
- Invoice, ledger, receipt pages render 200 + print.
- Full-payment create (paid = price) → status paid, no AP line.
- Regression: existing asset (backfilled) shows paid/0 due; depreciation still works.
