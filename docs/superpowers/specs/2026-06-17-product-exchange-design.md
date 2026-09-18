# Product / Variant Exchange — Design

**Date:** 2026-06-17
**Module:** SaleReturn (extended), Sale, Inventory, Accounting, Payment
**Status:** Approved design — pending implementation plan

## 1. Problem

A customer wants to swap a purchased product (or variant) for a different one. Today
"Exchange" exists only as a `Sale` status that is deliberately **stock-neutral**
(`SaleService::STOCK_NEUTRAL_STATUSES` includes `exchange`), and the `SaleReturn`
module has no exchange logic — its `refund_method` enum lists `exchange` but
`complete()` only ever creates a credit note. There is no flow that issues a
replacement item or moves stock for it.

An exchange must:
1. Take back the original item(s) → **restock** (`+qty`).
2. Issue replacement product(s)/variant(s) → **deduct stock** (`−qty`).
3. Settle the price difference in either direction (collect more, or refund / store credit).
4. Recalculate stock (immutable ledger + warehouse balances), sale totals, customer balance, and accounting.

## 2. Decisions (locked)

| Decision | Choice |
|---|---|
| Architecture | **Extend the SaleReturn module** — an exchange is a `SaleReturn` row with `type = exchange` carrying both return and replacement line items. Reuses the existing approve → complete → cancel lifecycle, stock, accounting, and credit-note plumbing. |
| Exchange scope | Replacement may be **any product or variant** (not limited to same product). |
| Price difference | **Both directions** — replacement dearer ⇒ collect a payment; cheaper ⇒ refund (cash) or store credit (credit note). |
| Stock timing | On **`complete`**, matching the existing return lifecycle (reversible via `cancel`). |
| Out-of-stock replacement | **Admin selects which product/variant to send.** System respects each product's negative-stock flag (same flag used for checkout in commit `acd0078`): block only when the product disallows negative stock. |
| Trigger & status | **"Exchange" action in the sale row** dropdown (beside "Sale Return"); on `complete` the original sale's status becomes `exchange`. |
| Replacement vs return quantity | **May differ freely** — reconciled by the price difference. |
| Listing | **Dedicated "Exchanges" page** (own index + sidebar item), backed by the SaleReturn table filtered to `type = exchange`. The Sale Returns index is filtered to `type = return` so the two never overlap. |

## 3. Data Model

### 3.1 `sale_returns` — new columns (one additive migration)

| Column | Type | Default | Purpose |
|---|---|---|---|
| `type` | `enum('return','exchange')` | `return` | Distinguishes a plain return from an exchange. Chosen over overloading `refund_method` (which is consumed as a `payment_account_id` and must not double as a type). |
| `replacement_subtotal` | `decimal(15,2)` | `0` | Total value of outgoing replacement items. |
| `price_difference` | `decimal(15,2)` | `0` | `replacement_subtotal − subtotal` (signed: `+` customer owes, `−` customer is refunded). |
| `difference_settlement` | `enum('collect','refund','credit_note','none')` | `none` | How the gap is settled at completion. |
| `payment_id` | `nullable FK → payments` (`nullOnDelete`) | — | Payment created when the difference is collected or refunded as cash. |

Existing columns reused: `subtotal` (= return subtotal), `tax_amount`, `total_amount`,
`status`, `credit_note_id`, `journal_entry_id`, `customer_id`, `branch_id`, `sale_id`.

### 3.2 `sale_return_items` — new column

| Column | Type | Default | Purpose |
|---|---|---|---|
| `line_type` | `enum('return','replacement')` | `return` | Marks each line as an incoming returned item or an outgoing replacement. |

Existing return data is unaffected (defaults to `return`).

### 3.3 Sale status

The original `Sale` moves to status `exchange` on completion. `exchange` is already in
`Sale::STATUSES` and is already stock-neutral in `SaleService` (correct — the SaleReturn
module owns all stock movement for the exchange).

## 4. Service Layer — `SaleReturnService`

### 4.1 `create(array $data, array $items)` / `update(...)`
- The controller maps the validated request into a single `items` array, tagging each
  line's `line_type`: `items` → `return`, `replacement_items` → `replacement`.
- **Return lines:** `unit_price` re-validated from the linked original `SaleItem` (existing behavior).
- **Replacement lines:** `unit_price` re-fetched from DB — `Product.sell_price` (or the variant's price). **Never trust the client price** (price-security rule).
- Compute and persist: `subtotal` (return), `tax_amount`, `replacement_subtotal`,
  `total_amount`, `price_difference = replacement_subtotal − subtotal`.

### 4.2 `complete(SaleReturn $return)` — exchange branch (in the existing DB transaction)
1. **Restock** every `return` line in good condition: `adjustStock(+qty, 'sale_return', ...)`.
2. **Deduct** every `replacement` line: `adjustStock(−qty, 'sale_exchange', ...)`.
   - Catch `InsufficientStockException`; rethrow only when the product **disallows**
     negative stock. When the flag allows it, let stock go negative (admin chose the item).
3. **Settle `price_difference`** per `difference_settlement`:
   - `collect` (`+` diff): create an incoming `Payment` (via `PaymentService`) allocated to the sale; store `payment_id`.
   - `refund` (`−` diff): create an outgoing cash `Payment`; store `payment_id`.
   - `credit_note` (`−` diff): create a `CreditNote` for the absolute difference; store `credit_note_id`.
   - `none`: even swap, no money movement.
4. **Reconcile the sale** by the net difference: update `grand_total`, `due_amount`,
   `payment_status`; update the customer's `total_purchased` accordingly.
5. **Accounting:** new `AccountingIntegrationService::recordSaleExchange($return)` posting a
   balanced entry = return-reversal legs (for returned goods/revenue) + new-sale legs (for
   replacement goods/revenue/COGS). Wrapped in try/catch + `Log::warning` like the return path.
6. Set the original **sale status → `exchange`**; persist `journal_entry_id`.

### 4.3 `cancel(SaleReturn $return)` — exchange branch
Reverse everything completed: re-deduct restocked returns, re-add deducted replacements,
void/delete the `Payment`, cancel the `CreditNote`, void the journal entry, restore the
sale's totals/customer balance, and revert the sale status away from `exchange`.

## 5. Validation, Controller, Routes

### 5.1 Request
`StoreExchangeRequest` (new) — return-item rules (as today) **plus**:
```
replacement_items                 required|array|min:1
replacement_items.*.product_id    required|exists:products,id
replacement_items.*.variant_id    nullable|exists:product_variants,id
replacement_items.*.quantity      required|integer|min:1
difference_settlement             required|in:collect,refund,credit_note,none
```
Replacement prices are NOT accepted from the client — resolved server-side.

### 5.2 Routes (in `Modules/SaleReturn/routes/web.php`)
Dedicated exchange routes backed by the same controller/service:
```
exchanges.index    GET  exchanges
exchanges.create   GET  exchanges/create        (?sale_id=)
exchanges.store    POST exchanges
exchanges.show     GET  exchanges/{saleReturn}
exchanges.cancel   ...
sale-returns.search-products  GET  (AJAX product/variant picker)
```
`exchanges.index` lists `SaleReturn::where('type','exchange')`; the existing
`sale-returns.index` is filtered to `type = return`.

### 5.3 Controller
Reuse `SaleReturnController` (or a thin `ExchangeController` delegating to the same service).
Add `searchProducts()` mirroring `QuotationController::searchProducts`.

## 6. UI

- **Sales index** (`Modules/Sale/resources/views/index.blade.php`): add an **"Exchange"**
  item to the row action dropdown beside "Sale Return", linking to
  `route('exchanges.create', ['sale_id' => $sale->id])`.
- **Sidebar:** add an **"Exchanges"** entry near Sale Returns.
- **Exchange create view** (`Modules/SaleReturn/resources/views/exchange-create.blade.php`):
  - **Returned Items** card — load original sale items (existing pattern), pick qty to return.
  - **Replacement Items** card — product/variant search picker, qty; line prices shown read-only from DB.
  - **Settlement summary** — return value, replacement value, signed difference, and the
    `difference_settlement` selector (collect / refund / credit note / none) with the
    payment-account picker when cash is involved.
  - Reuse `formatBDT`, the table styles, and the AJAX item-loading approach from
    `create.blade.php`.
- **Exchanges index / show / print** views, modeled on the existing return views, with a
  Return-vs-Replacement breakdown and the settlement line.
- All styling via existing `bp-` classes; dark-mode overrides for any new class; no inline CSS.

## 7. Testing

Feature tests (`Modules/SaleReturn/tests/Feature`):
- Completing an exchange restocks returned items **and** deducts replacement items —
  assert both `WarehouseStock.quantity` and the `StockLedger` rows (`sale_return` & `sale_exchange`).
- Price difference: `collect` creates an incoming payment and raises due correctly;
  `refund` creates an outgoing payment; `credit_note` issues a credit note; `none` for even swaps.
- Replacement of a product that **allows** negative stock succeeds when stock is insufficient;
  one that **disallows** it is blocked with `InsufficientStockException`.
- Differing return vs replacement quantities reconcile purely via the price difference.
- `cancel` fully reverses stock, payment/credit note, sale totals, and sale status.
- The original sale lands in `exchange` status; the journal entry balances (debits = credits).
- `exchanges.index` shows only `type=exchange`; `sale-returns.index` shows only `type=return`.

## 8. Out of Scope (YAGNI)

- POS-counter instant exchange (no approval gate) — exchanges follow draft → approve → complete.
- Multi-warehouse stock routing for replacements (uses existing single-stock-row behavior).
- Partial-shipment / courier return-pickup automation specific to exchanges (return path's
  Steadfast hook is untouched).
