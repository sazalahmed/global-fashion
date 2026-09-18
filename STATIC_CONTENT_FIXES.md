# Static/Hardcoded Content Fixes Tracker

## Status Legend
- [ ] Not started
- [x] Completed

---

## HIGH Priority — Core Business Pages

### 1. Sale/invoice-print.blade.php
- [x] Company info from Settings model
- [x] Customer data from $sale->customer
- [x] Invoice number, dates, salesperson, branch from $sale model
- [x] Line items via @foreach($sale->items)
- [x] Payment data, totals from $sale model

### 2. Sale/invoice-share.blade.php
- [x] All data wired to $sale model and Settings

### 3. Sale/edit.blade.php
- [x] Customer dropdown from $customers
- [x] Product options from $products
- [x] Dates, notes, amounts from $sale model
- [x] Branch dropdown from $branches

### 4. Purchase/create.blade.php
- [x] Supplier dropdown from $suppliers
- [x] Branch options from $branches
- [x] Product options from $products

### 5. Purchase/index.blade.php
- [x] All 4 stat cards from $stats
- [x] Supplier filter from $suppliers
- [x] PO rows via @forelse($purchases)
- [x] Date filter defaults dynamic

### 6. Supplier/index.blade.php
- [x] All 4 stat cards from $stats
- [x] Supplier table rows via @forelse($suppliers)
- [x] Action links with proper routes

### 7. Customer/ledger.blade.php
- [x] Stat cards from controller data (no hardcoded fallbacks)
- [x] Ledger rows properly formatted in @forelse
- [x] Empty state replaces sample data in @empty block

### 8. Quotation/index.blade.php
- [x] All 4 stat cards from $stats
- [x] Customer filter from $customers
- [x] Quotation rows via @forelse($quotations)
- [x] Date filters dynamic

### 9. Quotation/create.blade.php
- [x] Customer dropdown from $customers
- [x] Product options from $products
- [x] Branch from $branches
- [x] JS addItem uses server-side product data

### 10. Quotation/show.blade.php
- [x] All data from $quotation model with relationships
- [x] Items via @foreach($quotation->items)
- [x] Status, dates, totals, customer info all dynamic

### 11. Quotation/edit.blade.php
- [x] All dropdowns from server data
- [x] Line items from $quotation->items
- [x] Dates, amounts, notes from model

---

## MEDIUM Priority — Financial/Operational Pages

### 12. Accounting/bank-reconciliation.blade.php
- [x] Bank account dropdown from $bankAccounts (Account model)
- [x] All stat cards from $summary (book_balance, statement_balance, difference, unreconciled_count)
- [x] Book transactions via @forelse($bookItems)
- [x] Bank statement rows via @forelse($statementItems)
- [x] Reconciliation summary computed dynamically

### 13. Expense/ledger.blade.php
- [x] Stat cards from $ledgerStats (total, top_categories, monthly_average)
- [x] Period label from $ledgerStats['period_label']
- [x] Sample rows removed, proper empty state added
- [x] Category filter dropdown from $categories

### 14. Delivery/index.blade.php
- [x] All stat cards from $stats (total, in_transit, delivered, pending)
- [x] Courier filter dropdown from DeliveryChallan::COURIERS
- [x] Delivery rows via @forelse($challans) with model accessors
- [x] Status dropdown from DeliveryChallan::STATUS_LABELS

### 15. Report/custom.blade.php
- [x] JS sampleData replaced with AJAX POST to reports.custom.generate
- [x] Sales, purchase, inventory, financial reports query real data
- [x] BDT formatting helper added (lakh system)
- [x] Loading states and error handling added

---

## LOW Priority — Settings/Config Pages

### 16. Marketing/loyalty.blade.php
- [x] Already mostly dynamic (stats, transactions use real data)
- [x] Tier settings and points config are acceptable as configuration defaults

---

## Already Fixed (Prior Sessions)
- Payment/create.blade.php — date field, AJAX party search, AJAX invoices
- Payment/edit.blade.php — date field, wired to $payment model
- Payment/advance.blade.php — real party names, dynamic balances
- Supplier/show.blade.php — all tabs wired to real data
- Customer/show.blade.php — all tabs wired to real data
