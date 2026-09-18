# BizPOS Pro — Phase Plan: Exports, Filters & Module Completeness

**Created:** 05 Apr 2026
**Status:** Pending Implementation

---

## Current Infrastructure (Already Built)

| Component | Status | Location |
|-----------|--------|----------|
| ExportController (generic) | Ready | `app/Http/Controllers/ExportController.php` |
| Export route | Ready | `GET /export/{module}?format=xlsx\|csv\|pdf` in `routes/web.php:17` |
| JS export handler | Ready | `public/js/app.js:715` — auto-derives module from URL, passes current filters |
| PDF table template | Ready | `resources/views/exports/table-pdf.blade.php` |
| Maatwebsite/Excel | Installed | Excel & CSV generation |
| Barryvdh/DomPDF | Installed | PDF generation |
| Existing export classes | 11 classes | `app/Exports/` — Sales, Products, Purchases, Customers, Expenses, Stock, Employees, Payroll, Assets, Suppliers, Receivables |
| `<x-core::table.filter-bar>` | Ready | Shared Blade component for search + filters |

**How export works:** Views use `data-export="pdf|excel|csv"` attributes on buttons. JS intercepts clicks, derives the module name from the URL, appends current page filters, and redirects to `/export/{module}?format=...&filters...`. The `ExportController` looks up the export class, runs the query with filters, and returns the file.

---

## Phase 1 — Complete Export Backend for All Modules

**Goal:** Every index/list page that shows tabular data should support PDF, Excel, and CSV export with current filters applied.

**Effort:** ~12 new export classes + ExportController update + ~10 view button upgrades

### 1.1 Create Missing Export Classes

Each class goes in `app/Exports/` and implements `FromQuery, WithHeadings, WithMapping`.

| # | Export Class | Module | Key Columns |
|---|-------------|--------|-------------|
| 1 | `PaymentsExport` | Payment | Date, Ref#, Direction, Party Type, Party, Amount, Method, Account, Creator |
| 2 | `SaleReturnsExport` | SaleReturn | Return#, Date, Invoice#, Customer, Items, Reason, Status, Total |
| 3 | `PurchaseReturnsExport` | PurchaseReturn | Return#, Date, PO#, Supplier, Items, Reason, Status, Total |
| 4 | `InstallmentsExport` | Installment | Plan#, Customer, Sale Invoice, Total, Paid, Remaining, Installments, Status |
| 5 | `QuotationsExport` | Quotation | Quotation#, Date, Customer, Valid Until, Items, Subtotal, Total, Status |
| 6 | `DeliveryExport` | Delivery | Challan#, Date, Customer, Invoice#, Courier, Tracking#, Status |
| 7 | `JournalEntriesExport` | Accounting | Entry#, Date, Reference, Description, Debit Total, Credit Total, Status |
| 8 | `CreditNotesExport` | Accounting | Note#, Date, Customer, Return#, Amount, Applied, Balance, Status |
| 9 | `DebitNotesExport` | Accounting | Note#, Date, Supplier, Purchase#, Amount, Status |
| 10 | `StockAdjustmentsExport` | Inventory | Adj#, Date, Warehouse, Type, Reason, Items Count, Status |
| 11 | `StockTransfersExport` | Inventory | Transfer#, Date, From Warehouse, To Warehouse, Items Count, Status |
| 12 | `StockLedgerExport` | Inventory | Date, Product, Variant, Warehouse, Source Type, Qty Change, Balance, Description |

### 1.2 Wire New Modules in ExportController

Add to the `match` statement in `ExportController::export()`:

```
'payments'          => new PaymentsExport($filters),
'sale-returns'      => new SaleReturnsExport($filters),
'purchase-returns'  => new PurchaseReturnsExport($filters),
'installments'      => new InstallmentsExport($filters),
'quotations'        => new QuotationsExport($filters),
'delivery'          => new DeliveryExport($filters),
'journal-entries'   => new JournalEntriesExport($filters),
'credit-notes'      => new CreditNotesExport($filters),
'debit-notes'       => new DebitNotesExport($filters),
'stock-adjustments' => new StockAdjustmentsExport($filters),
'stock-transfers'   => new StockTransfersExport($filters),
'stock-ledger'      => new StockLedgerExport($filters),
```

### 1.3 Add/Upgrade Export Buttons in Views

**Add full PDF/Excel/CSV dropdown (currently NO export buttons):**

| View | File |
|------|------|
| Employee index | `Modules/Employee/resources/views/index.blade.php` |
| Payroll index | `Modules/Payroll/resources/views/index.blade.php` |
| Stock Adjustments | `Modules/Inventory/resources/views/adjustments.blade.php` |
| Stock Transfers | `Modules/Inventory/resources/views/transfers.blade.php` |
| Stock Ledger | `Modules/Inventory/resources/views/stock-ledger.blade.php` |
| Credit Notes | `Modules/Accounting/resources/views/credit-notes/index.blade.php` |

**Upgrade single "Export Excel" button to full dropdown:**

| View | File |
|------|------|
| Purchase index | `Modules/Purchase/resources/views/index.blade.php` |
| Supplier index | `Modules/Supplier/resources/views/index.blade.php` |
| Payment index | `Modules/Payment/resources/views/index.blade.php` |
| Expense index | `Modules/Expense/resources/views/index.blade.php` |
| Delivery index | `Modules/Delivery/resources/views/index.blade.php` |
| Installment index | `Modules/Installment/resources/views/index.blade.php` |

**Standard export dropdown template to use:**
```blade
<div class="dropdown">
  <button class="bp-btn bp-btn-outline dropdown-toggle" data-bs-toggle="dropdown">
    <i class="fa-solid fa-download me-1"></i> Export
  </button>
  <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="#" data-export="pdf"><i class="fa-solid fa-file-pdf me-2"></i>PDF</a></li>
    <li><a class="dropdown-item" href="#" data-export="excel"><i class="fa-solid fa-file-excel me-2"></i>Excel</a></li>
    <li><a class="dropdown-item" href="#" data-export="csv"><i class="fa-solid fa-file-csv me-2"></i>CSV</a></li>
  </ul>
</div>
```

---

## Phase 2 — Fix Filter & Search Gaps

**Goal:** Every list page has dynamic, server-side-supported filters — no hardcoded values.

**Effort:** ~6 view fixes + ~4 service/controller updates

### 2.1 PurchaseReturn Index — Replace Hardcoded Filters

**File:** `Modules/PurchaseReturn/resources/views/index.blade.php`

| Fix | Current | Target |
|-----|---------|--------|
| Date range | Hardcoded `2026-03-01` / `2026-03-09` | Dynamic `request('date_from')` / `request('date_to')` |
| Supplier dropdown | Hardcoded 3 suppliers | Dynamic `@foreach($suppliers ...)` from controller |
| Status filter | "Pending, Approved, Credited" | Match model: "Draft, Confirmed, Completed, Cancelled" |

**Backend:** Update `PurchaseReturnController::index()` to pass `$suppliers` and update `PurchaseReturnService::list()` to handle `date_from`, `date_to`, `supplier_id` params.

### 2.2 Stock Transfers — Replace Hardcoded Branches

**File:** `Modules/Inventory/resources/views/transfers.blade.php`

| Fix | Current | Target |
|-----|---------|--------|
| From/To warehouse dropdowns | Hardcoded branch names | Dynamic `@foreach($warehouses ...)` |
| Date range | Missing | Add date_from / date_to inputs |

**Backend:** Pass `$warehouses` from controller. Add date filter support in service.

### 2.3 Stock Adjustments — Add Status & Date Filters

**File:** `Modules/Inventory/resources/views/adjustments.blade.php`

| Fix | Current | Target |
|-----|---------|--------|
| Status filter | Missing | Add Draft / Approved dropdown |
| Date range | Missing | Add date_from / date_to inputs |

**Backend:** Add `status`, `date_from`, `date_to` filter handling in service.

### 2.4 Installment Index — Add Customer & Date Filters

**File:** `Modules/Installment/resources/views/index.blade.php`

| Fix | Current | Target |
|-----|---------|--------|
| Customer filter | Missing | Add customer search/dropdown |
| Date range | Missing | Add date_from / date_to inputs |

**Backend:** Add filter params to `InstallmentService::list()`.

### 2.5 Accounting — Credit Notes Index Filters

**File:** `Modules/Accounting/resources/views/credit-notes/index.blade.php`

| Fix | Current | Target |
|-----|---------|--------|
| Search | Missing | Add search by note#, customer |
| Status filter | Missing | Add: Draft, Issued, Applied, Cancelled |
| Date range | Missing | Add date_from / date_to |

### 2.6 Accounting — Debit Notes Index Filters

**File:** `Modules/Accounting/resources/views/debit-notes/index.blade.php`

| Fix | Current | Target |
|-----|---------|--------|
| CSV option | Missing from dropdown | Add CSV to existing PDF/Excel dropdown |
| Verify filters | Check server-side support | Ensure all visible filters are handled in controller |

---

## Phase 3 — Inventory Module CRUD & Cancellation

**Goal:** Stock adjustments and transfers should have full lifecycle: create, edit (draft), cancel (with reversal), delete (draft).

**Effort:** ~8 new methods + ~4 new views + route updates

### 3.1 Stock Adjustments — Cancel Approved

**New method:** `InventoryService::cancelAdjustment(StockAdjustment $adj)`

Logic:
1. Only approved adjustments can be cancelled
2. Loop items: call `adjustStock()` with opposite quantity (addition -> subtract, subtraction -> add)
3. Set status to 'cancelled'
4. Void journal entry if exists

**Files:** Service + Controller method + Route (`POST /inventory/adjustments/{id}/cancel`)

### 3.2 Stock Transfers — Cancel (Any Active Status)

**New method:** `InventoryService::cancelTransfer(StockTransfer $transfer)`

Logic:
- **Draft:** Just set cancelled
- **In Transit:** Reverse source warehouse deduction (add stock back), set cancelled
- **Received:** Reverse both source deduction AND destination addition, set cancelled

**Files:** Service + Controller method + Route (`POST /inventory/transfers/{id}/cancel`)

### 3.3 Stock Adjustments — Edit Draft

**New files:**
- View: `Modules/Inventory/resources/views/adjustments-edit.blade.php`
- Controller methods: `editAdjustment()`, `updateAdjustment()`
- Routes: `GET /inventory/adjustments/{id}/edit`, `PUT /inventory/adjustments/{id}`

Logic: Only draft adjustments. Replace items, recalculate.

### 3.4 Stock Transfers — Edit Draft

**New files:**
- View: `Modules/Inventory/resources/views/transfers-edit.blade.php`
- Controller methods: `editTransfer()`, `updateTransfer()`
- Routes: `GET /inventory/transfers/{id}/edit`, `PUT /inventory/transfers/{id}`

Logic: Only draft transfers. Replace items, recalculate.

### 3.5 Stock Adjustments & Transfers — Delete Draft

**New methods:** `destroyAdjustment()`, `destroyTransfer()`

Logic:
- Only draft status allowed
- Delete items first, then parent record
- Routes: `DELETE /inventory/adjustments/{id}`, `DELETE /inventory/transfers/{id}`

---

## Phase 4 — Print Views for Missing Modules

**Goal:** Add print-friendly views for individual record printing.

**Effort:** ~4 new print views + routes + controller methods

### 4.1 Stock Adjustment Print

**New file:** `Modules/Inventory/resources/views/adjustments-print.blade.php`
- Header: Adjustment#, Date, Warehouse, Type, Reason, Status
- Table: Product, Variant, SKU, Quantity, Unit Cost, Note
- Footer: Total items, Total value

### 4.2 Stock Transfer Print

**New file:** `Modules/Inventory/resources/views/transfers-print.blade.php`
- Header: Transfer#, Date, From Warehouse, To Warehouse, Status
- Table: Product, Variant, SKU, Quantity
- Footer: Total items

### 4.3 Expense Print

**New file:** `Modules/Expense/resources/views/print.blade.php`
- Expense details: Number, Date, Category, Vendor, Amount, Tax, Total
- Payment info: Method, Account, Status
- Description, Receipt reference

### 4.4 Payroll Print

**New file:** `Modules/Payroll/resources/views/print.blade.php`
- Header: Payroll#, Month, Branch, Status
- Table: Employee, Basic, Earnings, Deductions, Advance Deduction, Net Salary, Payment Method
- Footer: Totals row

### 4.5 Add Print Routes

For each new print view, add:
- `GET /{module}/{id}/print` route
- Controller method that loads data with relations and returns print view
- Print button on the show page

---

## Phase 5 — Accounting Module Completeness

**Goal:** Credit notes, debit notes, and other accounting views have full filter, search, and export support.

**Effort:** ~4 view updates + backend filter support

### 5.1 Credit Notes Index

| Task | Details |
|------|---------|
| Add export buttons | PDF/Excel/CSV dropdown |
| Add search input | Search by note#, customer name |
| Add status filter | Draft, Issued, Partially Applied, Fully Applied, Cancelled |
| Add date range | date_from / date_to |
| Backend | Update controller/service to handle all filters |

### 5.2 Debit Notes Index

| Task | Details |
|------|---------|
| Add CSV to dropdown | Currently only PDF/Excel |
| Verify filter backend | Ensure status, date, search filters are server-side |

### 5.3 Receipts/Vouchers Index

| Task | Details |
|------|---------|
| Verify export backend | Ensure `data-export` buttons connect to ExportController |
| Add missing formats | Add CSV if only PDF/Excel available |

### 5.4 Journal Entries Index

| Task | Details |
|------|---------|
| Add export buttons | PDF/Excel/CSV dropdown (if missing) |
| Add source_type filter | Filter by: sale, purchase, payment, expense, payroll, manual, etc. |

---

## Phase 6 — Final Consistency Audit

**Goal:** Verify every module follows the same patterns. No gaps.

### 6.1 View Checklist (Every Index Page)

For each module's index view, verify:
- [ ] Search input with placeholder text
- [ ] Status filter dropdown (where applicable)
- [ ] Date range filter (where applicable)
- [ ] Additional context filters (category, branch, warehouse, etc.)
- [ ] Export dropdown with PDF/Excel/CSV
- [ ] Pagination at bottom
- [ ] All filters are server-side supported (not just UI)

### 6.2 Transactional Module Checklist

For Sale, Purchase, SaleReturn, PurchaseReturn, Expense, Payroll, Stock Adjustment, Stock Transfer:

- [ ] Create with proper validation
- [ ] Edit (draft only where applicable)
- [ ] Delete with guards (draft only / check dependencies)
- [ ] Cancel with full reversal (stock + payments + journal + balances)
- [ ] Export (PDF/Excel/CSV)
- [ ] Print (individual record)
- [ ] Activity logging (LogsActivity trait)
- [ ] Journal entry integration (create on confirm, void on cancel)

### 6.3 Master Module Checklist

For Customer, Supplier, Product, Employee:

- [ ] CRUD with validation
- [ ] Deletion guards (check dependencies before delete)
- [ ] Export (PDF/Excel/CSV)
- [ ] Search + filters on index
- [ ] Activity logging (LogsActivity trait)
- [ ] Show page with full details + related transactions

---

## Summary

| Phase | Scope | Est. Files | Priority |
|-------|-------|-----------|----------|
| **Phase 1** | Export backend for 12 modules + button upgrades | ~15 files | Highest |
| **Phase 2** | Fix hardcoded filters across 6 views | ~10 files | High |
| **Phase 3** | Inventory cancel/edit/delete for adjustments & transfers | ~12 files | High |
| **Phase 4** | Print views for 4 modules | ~8 files | Medium |
| **Phase 5** | Accounting filters & export completion | ~6 files | Medium |
| **Phase 6** | Final audit & gap fixes | Variable | Low |
