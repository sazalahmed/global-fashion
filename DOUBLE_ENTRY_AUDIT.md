# Double-Entry Accounting Coverage Audit

**Date:** 2026-05-03
**Scope:** All 40 modules — verifying every financial transaction creates a balanced journal entry (DR = CR).

---

## 1. Architecture (working as designed)

- `Modules\Accounting\Services\JournalEntryService` — low-level entry/line creator. Auto-posts entries from source modules and validates `isBalanced()` before posting. Void = reverse-entry pattern (good).
- `Modules\Accounting\Services\AccountingIntegrationService` — high-level wrappers: `recordSale`, `recordPurchase`, `recordSaleReturn`, `recordPurchaseReturn`, `voidJournalEntry`.
- `Modules\Manufacturing\Services\ManufacturingAccountingService` — manufacturing-specific wrappers (11 methods). **Most are dead code — see §3.**

The two services together produce balanced JEs by construction (debits and credits hard-coded on the same `total_amount`), so individual call sites are mathematically safe.

---

## 2. Modules WITH proper double-entry (verified)

| Module | Trigger | DR / CR | Reversal on void/cancel |
|---|---|---|---|
| Sale | `create()` / `confirm()` | DR AR (1010) / CR Revenue (4001) + CR VAT (2010) | ✓ `voidJournalEntry('sale', ...)` |
| POS | `processSale()` → `SaleService::createSale()` | (delegates to Sale) | ✓ via Sale |
| SaleReturn | `create()` | DR Sales Returns (4010) / CR AR (1010) | ✓ |
| Purchase | `create()` / `receive()` | DR Inventory (1020) + DR Input VAT (1025) / CR AP (2001) | ✓ |
| PurchaseReturn | `create()` | DR AP (2001) / CR Inventory (1020) | ✓ |
| Payment | `create()` (customer + supplier) | DR Cash / CR AR  *or*  DR AP / CR Cash | ✓ `journalService->void()` |
| Expense | `create()` & `recordPayment()` | DR Expense / CR AP or Cash | ✓ on cancel |
| Payroll | `post()` payroll run | DR Salary Expense / CR Salary Payable + Cash | ✓ on void |
| Loan | `disburse()` / `recordRepayment()` | DR Cash / CR Loan Payable  *and*  DR Loan / CR Cash + Interest Exp | ✓ |
| AdSpend | `create()` | DR Marketing Expense / CR Cash | ✓ on delete |
| CreditNote | `create()` | DR Sales Returns / CR AR | n/a |
| DebitNote | `create()` | DR AP / CR Purchase Returns | n/a |
| Manufacturing — Factory Payment | `FactoryPaymentService::create()` | DR Factory Payable / CR Cash | – |
| Manufacturing — RM Supplier Payment | `RmSupplierPaymentService::create()` | DR AP / CR Cash | – |
| Manufacturing — Cost Adjustment | `ProductionCostService` | DR/CR WIP adjustments | – |

---

## 3. Modules MISSING journal entries (gaps)

### A. Critical — silent financial movement, no GL impact

| # | Module | File | Issue |
|---|---|---|---|
| 1 | **Installment** | `Modules\Installment\app\Services\InstallmentService.php:92` | `recordPayment()` updates schedule + sale `paid_amount` but does **not** call `PaymentService` or post a JE. Cash received from customer never hits the ledger. |
| 2 | **Ecommerce — Storefront checkout** | `Modules\Ecommerce\app\Services\StorefrontService.php` | Online order checkout creates an `ecommerce_order` row but does not call `SaleService` or `recordSale`. Online revenue invisible to GL. |
| 3 | **Asset** | `Modules\Asset\app\Services\AssetService.php` | No JE on acquisition (DR Asset / CR Cash/AP), depreciation (DR Depreciation Exp / CR Accumulated Depreciation), or disposal (DR Cash + Accum.Dep. / CR Asset + Gain/Loss). Entire asset lifecycle off-ledger. |
| 4 | **Inventory adjustment** | `Modules\Inventory\app\Services\InventoryService.php::adjustStock()` | Stock write-up/down has no JE. Should post DR/CR Inventory vs. Inventory Adjustment expense/income. |
| 5 | **Inventory reconciliation** | `Modules\Inventory\app\Services\ReconciliationService.php` | Physical-count variance has no JE. Same fix pattern as #4. |

### B. Manufacturing operational flows — dead code (methods exist, never invoked)

`ManufacturingAccountingService` has these methods but they are not called anywhere in operational services:

| Method | Should be called by | Currently called? |
|---|---|---|
| `recordRmPurchase()` | `RmPurchaseService::create()` | ✗ |
| `recordFabricIssuance()` | `FabricIssuanceService::create()` | ✗ |
| `recordFabricReturn()` | `FabricReturnService::create()` | ✗ |
| `recordLotReceived()` | `ProductionLotService::receive()` | ✗ |
| `recordDamage()` | `ProductionDamageService::create()` | ✗ |
| `recordRmWaste()` | `RmWasteService::create()` | ✗ |
| `recordProductWaste()` | `ProductWasteService::create()` | ✗ |
| `recordCompensation()` | `ProductionDamageService::recordCompensation()` | ✗ |

Verified by `grep -rn "ManufacturingAccountingService\|accountingService->" Modules/Manufacturing/app/Services/` — only `FactoryPaymentService`, `RmSupplierPaymentService`, and `ProductionCostService` inject and call it.

### C. Reversal gaps (entry on create, but no reverse on cancel/void)

None found in the verified-working modules — all of them call `voidJournalEntry()` or `journalService->void()` on cancel/delete.

### D. Out of scope (no financial impact — confirmed)

Quotation, Delivery (charge captured by Sale), Activity, Attendance, Customer/Supplier master CRUD (advance payments correctly route through PaymentService), Branch, Brand, Category, Unit, Variant, Barcode, Marketing, Security, Setting, Recruitment, Auth.

---

## 4. Balance integrity (math)

Every call site I read constructs lines with `debit_amount` and `credit_amount` derived from the same source total (e.g. `$sale->grand_total` split across AR / Revenue / VAT). The pattern guarantees DR = CR by construction. `JournalEntryService::post()` additionally guards with `isBalanced()`.

**Static verdict:** every JE that *is* created is balanced. The risk is not unbalanced entries — it is **missing entries** (§3.A and §3.B).

I could not run the runtime DB check (`Unknown database 'bizpos'` — local DB not provisioned). On a populated environment, run:

```sql
SELECT je.id, je.entry_number, je.source_type,
       SUM(jel.debit_amount) AS d, SUM(jel.credit_amount) AS c
FROM journal_entries je
JOIN journal_entry_lines jel ON jel.journal_entry_id = je.id
WHERE je.status = 'posted'
GROUP BY je.id, je.entry_number, je.source_type
HAVING ABS(d - c) > 0.01;
```

Expected: 0 rows.

---

## 5. Recommended fixes (priority order)

1. **Wire ManufacturingAccountingService into the 8 operational services.** Lowest-risk fix — the JE code is already written and balanced; just inject + call. ~1 line per service.
2. **Installment::recordPayment()** — route through `PaymentService::create()` instead of directly mutating the sale, so existing JE wiring kicks in.
3. **Ecommerce checkout** — call `SaleService::createSale()` from the order-confirmation path (or add `recordEcommerceOrder()` wrapper that mirrors `recordSale`).
4. **Inventory adjustments / reconciliation** — add a small `recordInventoryAdjustment(direction, amount)` to `AccountingIntegrationService` and call it from `adjustStock()` / reconciliation post.
5. **Asset lifecycle** — three new wrappers (`recordAssetAcquisition`, `recordDepreciation`, `recordAssetDisposal`) in `AccountingIntegrationService`, called from `AssetService` (and a daily/monthly depreciation job).

None of these require schema changes.
