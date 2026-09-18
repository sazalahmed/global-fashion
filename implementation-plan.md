# BizPOS — Gap Feature Implementation Plan

> Created: 30 Mar 2026
> Updated: 30 Mar 2026 (schema aligned to BizPOS conventions)
> Based on: `missing-features-analysis.md` + verified codebase audit + quickshifter-inventory schema
> Architecture: Laravel 12, Nwidart Modules, SOLID, DRY

---

## How to Read This Plan

Each phase is self-contained and ordered by business impact. Every task specifies:

- **What** — Feature scope
- **Where** — Files/modules to create or modify
- **Schema** — Exact database columns matching BizPOS conventions
- **Routes** — Endpoints needed
- **Dependencies** — What must exist before this task
- **Reference** — Quickshifter table/file for context

Items marked `[EXISTS]` have partial support — only enhancement needed. Items marked `[NEW]` require building from scratch.

### BizPOS Schema Conventions (must follow)

- Money: `decimal(15, 2)` — never `float` or `double`
- Foreign keys: `$table->foreignId('x_id')->constrained()->nullOnDelete()` or `cascadeOnDelete()`
- Booleans: `$table->boolean('is_x')->default(true|false)`
- Status: `$table->string('status', 20)->default('value')` — not enum (BizPOS uses string for status)
- Soft deletes: `$table->softDeletes()` on transactional tables
- Timestamps: `$table->timestamps()` always
- Naming: snake_case, plural table names, singular model names
- Indexes: Add on FKs, status columns, date columns used in filters

---

## Phase 1 — Core Business Gaps (High Priority)

---

### 1.1 Customer Groups `[NEW]`

> Managed customer segmentation replacing the current plain-text `customer_group` string field on `customers` table.

**Reference**: Quickshifter `user_group` table (shared for customers + suppliers with `type` enum). BizPOS will use separate tables for cleaner separation.

**Schema — `customer_groups` table**

```php
Schema::create('customer_groups', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('description', 500)->nullable();
    $table->decimal('discount_percentage', 5, 2)->default(0); // group-level discount
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique('name');
});
```

**Schema — alter `customers` table**

```php
// Drop old string column, add FK
Schema::table('customers', function (Blueprint $table) {
    $table->dropColumn('customer_group'); // was string(30) default 'Retail'
    $table->foreignId('customer_group_id')->nullable()->after('tin')
          ->constrained('customer_groups')->nullOnDelete();
    $table->index('customer_group_id');
});
```

**Where**
- Migration: `Modules/Customer/database/migrations/xxxx_create_customer_groups_table.php`
- Migration: `Modules/Customer/database/migrations/xxxx_replace_customer_group_with_fk.php`
- Model: `Modules/Customer/app/Models/CustomerGroup.php`
- Controller: `Modules/Customer/app/Http/Controllers/CustomerGroupController.php`
- Service: `Modules/Customer/app/Services/CustomerGroupService.php`
- Routes: `Modules/Customer/routes/web.php`
- Views: `Modules/Customer/resources/views/groups/index.blade.php` (table + inline create modal)
- Update: Customer create/edit — replace text input with `<select>` populated from `customer_groups`
- Update: Customer model — add `belongsTo('CustomerGroup')` relationship
- Sidebar: Add "Customer Groups" under Customers submenu
- Seeder: Retail, Wholesale, VIP, Corporate

**Routes**
```
GET    /customer-groups           → index
POST   /customer-groups           → store
PUT    /customer-groups/{group}   → update
DELETE /customer-groups/{group}   → destroy
```

**Dependencies**: None

---

### 1.2 Supplier Groups `[NEW]`

> Categorize suppliers. Quickshifter uses a shared `user_group` table with type column. BizPOS will use a dedicated table.

**Schema — `supplier_groups` table**

```php
Schema::create('supplier_groups', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('description', 500)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique('name');
});
```

**Schema — alter `suppliers` table**

```php
Schema::table('suppliers', function (Blueprint $table) {
    $table->foreignId('supplier_group_id')->nullable()->after('status')
          ->constrained('supplier_groups')->nullOnDelete();
    $table->index('supplier_group_id');
});
```

**Where**
- Migration: `Modules/Supplier/database/migrations/xxxx_create_supplier_groups_table.php`
- Migration: `Modules/Supplier/database/migrations/xxxx_add_supplier_group_id_to_suppliers.php`
- Model: `Modules/Supplier/app/Models/SupplierGroup.php`
- Controller: `Modules/Supplier/app/Http/Controllers/SupplierGroupController.php`
- Routes: `Modules/Supplier/routes/web.php`
- Views: `Modules/Supplier/resources/views/groups/index.blade.php`
- Sidebar: Make Suppliers a submenu parent → add "All Suppliers" + "Supplier Groups"
- Update: Supplier create/edit — add group dropdown
- Update: Supplier model — add `belongsTo('SupplierGroup')`
- Seeder: Local Manufacturer, Importer, Distributor, Wholesaler

**Routes**
```
GET    /supplier-groups           → index
POST   /supplier-groups           → store
PUT    /supplier-groups/{group}   → update
DELETE /supplier-groups/{group}   → destroy
```

**Dependencies**: None

---

### 1.3 Supplier CSV Import `[NEW]`

> Bulk import suppliers from CSV/XLSX. BizPOS already has a global `POST /import/{module}` route and `CustomersImport` class — follow the same pattern.

**Reference**: Quickshifter `app/Imports/SuppliersImport.php`

**Where**
- Import class: `app/Imports/SuppliersImport.php` (implements `ToModel`, `WithHeadingRow`, `WithValidation`, `SkipsOnFailure`)
- Uses existing global route: `POST /import/suppliers`
- Add import button + sample download link to supplier index view

**Column Mapping (CSV headers → `suppliers` columns)**
```
company_name*  → company_name
contact_person*→ contact_person
phone*         → phone
email          → email
division       → division
district       → district
area           → area
address        → address
opening_balance→ opening_balance (decimal, default 0)
credit_limit   → credit_limit (decimal, default 0)
payment_terms  → payment_terms (default 'Net 30')
supplier_group → supplier_group_id (match by name from supplier_groups)
```

**Dependencies**: 1.2 (Supplier Groups) — for group name matching

---

### 1.4 Expense Vendors `[NEW]`

> Separate from inventory suppliers. These are non-inventory vendors: landlord, ISP, utility companies, cleaning service, etc. Each has their own due/advance/ledger tracking.

**Reference**: Quickshifter `expense_suppliers` + `expense_supplier_payments` tables

**Schema — `expense_vendors` table**

```php
Schema::create('expense_vendors', function (Blueprint $table) {
    $table->id();
    $table->string('name', 150);
    $table->string('company_name', 255)->nullable();
    $table->string('phone', 20)->nullable();
    $table->string('email')->nullable();
    $table->string('contact_person', 150)->nullable();
    $table->text('address')->nullable();
    $table->decimal('opening_balance', 15, 2)->default(0);
    $table->decimal('total_expense', 15, 2)->default(0);
    $table->decimal('total_paid', 15, 2)->default(0);
    $table->decimal('advance_balance', 15, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index('is_active');
    $table->index('phone');
});
```

**Schema — alter `expenses` table**

```php
Schema::table('expenses', function (Blueprint $table) {
    $table->foreignId('expense_vendor_id')->nullable()->after('expense_category_id')
          ->constrained('expense_vendors')->nullOnDelete();
    $table->date('due_date')->nullable()->after('expense_date');
    $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
    $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
    $table->string('payment_status', 20)->default('unpaid')->after('status');
    // payment_status: paid, partial, unpaid

    $table->index('expense_vendor_id');
    $table->index('payment_status');
});
```

**Where**
- Migration: `Modules/Expense/database/migrations/xxxx_create_expense_vendors_table.php`
- Migration: `Modules/Expense/database/migrations/xxxx_add_vendor_and_due_fields_to_expenses.php`
- Model: `Modules/Expense/app/Models/ExpenseVendor.php`
- Service: `Modules/Expense/app/Services/ExpenseVendorService.php`
- Controller: `Modules/Expense/app/Http/Controllers/ExpenseVendorController.php`
- Views: `Modules/Expense/resources/views/vendors/` — index, create, edit, show (with ledger)
- Routes: `Modules/Expense/routes/web.php`
- Sidebar: Add "Expense Vendors" under Finance section
- Update: Expense create/edit forms — add vendor dropdown, due_date field
- Update: ExpenseService — track paid_amount/due_amount, update vendor totals on payment

**Routes**
```
GET    /expense-vendors                        → index
GET    /expense-vendors/create                 → create
POST   /expense-vendors                        → store
GET    /expense-vendors/{vendor}               → show (includes ledger)
GET    /expense-vendors/{vendor}/edit           → edit
PUT    /expense-vendors/{vendor}               → update
DELETE /expense-vendors/{vendor}               → destroy
POST   /expense-vendors/{vendor}/pay-due       → payDue
POST   /expense-vendors/{vendor}/add-advance   → addAdvance
```

**Dependencies**: None

---

### 1.5 POS Cart Hold & Resume `[NEW]`

> Save current POS cart and resume later. Quickshifter stores cart contents as JSON.

**Reference**: Quickshifter `cart_holds` table

**Schema — `pos_held_carts` table**

```php
Schema::create('pos_held_carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->string('reference', 100)->nullable(); // label: "Table 3", "John waiting"
    $table->json('items'); // [{product_id, variant_id, name, sku, qty, price, discount}]
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('discount_amount', 15, 2)->default(0);
    $table->text('notes')->nullable();
    $table->foreignId('held_by')->constrained('users');
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->timestamps();

    $table->index('held_by');
    $table->index('branch_id');
});
```

**Where**
- Migration: `Modules/POS/database/migrations/xxxx_create_pos_held_carts_table.php`
- Model: `Modules/POS/app/Models/HeldCart.php`
- Update controller: `POSController.php` — add `holdCart()`, `getHeldCarts()`, `resumeCart()`, `deleteHeldCart()`
- Routes: `Modules/POS/routes/web.php`
- Update POS terminal view — Hold button, held carts sidebar panel

**Routes**
```
POST   /pos/hold-cart           → holdCart (JSON)
GET    /pos/held-carts          → getHeldCarts (JSON)
POST   /pos/resume-cart/{id}    → resumeCart (JSON)
DELETE /pos/held-cart/{id}      → deleteHeldCart (JSON)
```

**Dependencies**: None

---

### 1.6 Daily Transaction Summary (DTS) Report `[NEW]`

> Consolidated daily financial overview. Quickshifter has this as a standalone report with Excel/PDF export.

**Reference**: Quickshifter `ReportController` DTS methods + `DTSExport`

**No new tables needed** — reads from existing: `sales`, `purchases`, `expenses`, `payments`, `payment_allocations`, `sale_returns`, `purchase_returns`

**Where**
- Controller: `Modules/Report/app/Http/Controllers/ReportController.php` — add `dts()` method
- Service: `Modules/Report/app/Services/ReportService.php` — add `getDailyTransactionSummary(string $date)`
- Views: `Modules/Report/resources/views/dts.blade.php`
- Export: `app/Exports/DTSExport.php`
- Routes: `Modules/Report/routes/web.php`
- Sidebar: Add "Daily Summary (DTS)" under Reports

**Report Sections**
```
1. Sales     — count, total, cash/mobile/card breakdown, returns
2. Purchases — count, total, paid, due
3. Expenses  — count, total, by category (top 5)
4. Received  — total from customers (by method: cash, bKash, nagad, card, bank)
5. Paid Out  — to suppliers, expense vendors, salary
6. Returns   — sale return total, purchase return total
7. Net Cash  — (received - paid - expenses + purchase returns - sale returns)
```

**Routes**
```
GET /reports/dts       → dts (accepts ?date=YYYY-MM-DD, defaults to today)
GET /reports/dts/export → dtsExport (format: xlsx, pdf)
```

**Dependencies**: None

---

### 1.7 Stock Reconciliation `[NEW]`

> Compare system stock vs expected stock (calculated from purchases - sales - adjustments - transfers). Flag discrepancies.

**Reference**: Quickshifter `StockReconcile`, `SalesReconcile`, `PurchaseReconcile`, `LedgerReconcile` artisan commands

**No new tables needed** — reads from: `warehouse_stock`, `stock_ledger`, `sale_items`, `purchase_items`, `stock_adjustment_items`, `stock_transfer_items`, `sale_return_items`, `purchase_return_items`

**Where**
- Command: `app/Console/Commands/StockReconcile.php` — `php artisan stock:reconcile {--warehouse=} {--product=} {--fix}`
- Command: `app/Console/Commands/LedgerReconcile.php` — `php artisan ledger:reconcile`
- Service: `Modules/Inventory/app/Services/ReconciliationService.php`
- Controller: `Modules/Inventory/app/Http/Controllers/InventoryController.php` — add `reconciliation()`, `runReconciliation()`
- Views: `Modules/Inventory/resources/views/reconciliation.blade.php`
- Routes: `Modules/Inventory/routes/web.php`
- Sidebar: Add "Reconciliation" under Stock submenu

**Logic**
```
For each product + variant + warehouse:
  ledger_qty   = SUM(stock_ledger.quantity_change) WHERE product_id AND warehouse_id
  stored_qty   = warehouse_stock.quantity
  expected_qty = (total_purchased + total_returned_from_sale + adjustments_in + transfers_in)
               - (total_sold + total_returned_to_supplier + adjustments_out + transfers_out)

  discrepancy_1 = stored_qty - ledger_qty        (stock table vs ledger)
  discrepancy_2 = ledger_qty - expected_qty       (ledger vs source documents)

  → Flag rows where either discrepancy != 0
  → Option to auto-create stock adjustments for discrepancy_1
```

**Routes**
```
GET  /inventory/reconciliation      → reconciliation (view with filters)
POST /inventory/reconciliation/run  → runReconciliation (returns results)
```

**Dependencies**: Existing Inventory module

---

### 1.8 Opening Balance UI (Accounting) `[EXISTS — Enhance]`

> The `accounts` table already has `opening_balance` (decimal 15,2), `opening_balance_type` (enum: debit/credit), and `opening_balance_date` (date). Just needs a dedicated UI page.

**Reference**: Quickshifter `BalanceController` — `getOpeningBalance()`, `openingBalance()`, `openingBalanceUpdate()`

**No new tables needed** — uses existing `accounts` table columns

**Where**
- Controller: `Modules/Accounting/app/Http/Controllers/AccountingController.php` — add `openingBalances()`, `saveOpeningBalances()`
- Views: `Modules/Accounting/resources/views/opening-balances.blade.php`
- Routes: `Modules/Accounting/routes/web.php`
- Sidebar: Add "Opening Balances" under Accounting

**UI Flow**
```
1. Table of all accounts:
   Account Code | Account Name | Type | Opening Balance | Debit/Credit | Date
2. Each row has inline editable inputs
3. Footer: Total Debits | Total Credits | Difference
4. Validation: Total Debits must equal Total Credits
5. Lock editing after first journal entry is posted (add check)
```

**Routes**
```
GET  /accounting/opening-balances  → openingBalances
POST /accounting/opening-balances  → saveOpeningBalances (bulk update)
```

**Dependencies**: Existing Accounting module

---

### 1.9 Payment Account Ledger `[NEW]`

> Per-account transaction ledger. Click on any payment account (Cash, bKash, Nagad, DBBL Bank, etc.) and see every transaction that flowed through it with a running balance. This is the **operational money trail** — not the accounting GL.

**Reference**: Quickshifter `AccountsController::accountLedger()` + `AccountLedgerExport`

**No new tables needed** — reads from existing tables:
- `payments` (has `payment_account_id` FK) — sale payments, purchase payments, expense payments, advance payments
- `balance_transfers` (has `from_account_id` and `to_account_id`) — money moving between accounts
- `payment_accounts` (has `opening_balance`) — starting balance

**Where**
- Controller: `Modules/Payment/app/Http/Controllers/PaymentAccountController.php` — add `ledger(PaymentAccount $account, Request $request)`
- Service: `Modules/Payment/app/Services/PaymentAccountService.php` — add `getAccountLedger(int $accountId, ?string $from, ?string $to)`
- Views: `Modules/Payment/resources/views/accounts/ledger.blade.php`
- Export: `app/Exports/AccountLedgerExport.php`
- Routes: `Modules/Payment/routes/web.php`
- Update: Payment Accounts index — add "View Ledger" action button per account
- Update: Payment Accounts show card — link to ledger

**Ledger Query Logic**
```
Starting Balance = payment_account.opening_balance

Transactions (UNION ALL, ordered by date):
1. Payments received (direction='receive', payment_account_id=X)
   → IN (+) — sale payment, customer due, advance received
2. Payments paid out (direction='pay', payment_account_id=X)
   → OUT (-) — supplier payment, expense payment, advance paid
3. Balance transfers FROM this account (from_account_id=X)
   → OUT (-) — transferred to another account
4. Balance transfers TO this account (to_account_id=X)
   → IN (+) — received from another account

Running Balance = opening_balance + SUM(in) - SUM(out) up to each row
```

**Ledger View Columns**
```
Date | Reference | Description | Type | IN (+) | OUT (-) | Running Balance
```

**Type Labels**
- Sale Payment, Purchase Payment, Expense Payment, Advance Received, Advance Paid
- Due Receive, Due Pay, Transfer In, Transfer Out, Opening Balance

**Filters**: Date range, transaction type

**Routes**
```
GET /payment-accounts/{account}/ledger         → ledger (with ?from=&to= filters)
GET /payment-accounts/{account}/ledger/export   → ledgerExport (xlsx/pdf)
```

**Dependencies**: None

---

## Phase 2 — Operational Enhancements (Medium Priority)

---

### 2.1 Hierarchical Expense Categories `[EXISTS — Enhance]`

> BizPOS `expense_categories` table currently has: id, name, account_id, description, is_active, sort_order. No `parent_id`. Add nesting.

**Reference**: Quickshifter `expense_types` table has `parent_id` FK to itself

**Schema — alter `expense_categories` table**

```php
Schema::table('expense_categories', function (Blueprint $table) {
    $table->foreignId('parent_id')->nullable()->after('id')
          ->constrained('expense_categories')->nullOnDelete();
    $table->unsignedTinyInteger('level')->default(0)->after('parent_id');
});
```

**Where**
- Migration: `Modules/Expense/database/migrations/xxxx_add_parent_id_to_expense_categories.php`
- Update model: `ExpenseCategory` — add `parent()`, `children()` relationships, `scopeRoots()`
- Update views: Show indented tree in category list, nested dropdown in expense forms
- Update service: Category deletion must handle children (reassign to parent or block)

**Dependencies**: None

---

### 2.2 Purchase Return Types `[NEW]`

> Managed return reasons instead of free-text.

**Reference**: Quickshifter `purchase_return_types` table

**Schema — `purchase_return_types` table**

```php
Schema::create('purchase_return_types', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('description', 500)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique('name');
});
```

**Schema — alter `purchase_returns` table**

```php
Schema::table('purchase_returns', function (Blueprint $table) {
    $table->foreignId('return_type_id')->nullable()->after('return_date')
          ->constrained('purchase_return_types')->nullOnDelete();
});
```

**Where**
- Migration: `Modules/Purchase/database/migrations/xxxx_create_purchase_return_types_table.php`
- Migration: `Modules/Purchase/database/migrations/xxxx_add_return_type_id_to_purchase_returns.php`
- Model: `Modules/Purchase/app/Models/PurchaseReturnType.php`
- Controller: `Modules/Purchase/app/Http/Controllers/PurchaseReturnTypeController.php`
- Routes: `Modules/Purchase/routes/web.php`
- Views: Simple CRUD (index with inline add/edit)
- Update: Purchase return create/edit — add type dropdown
- Seeder: Defective, Wrong Item, Expired, Damaged in Transit, Quality Issue, Overstock

**Dependencies**: None

---

### 2.3 Additional Reports `[NEW]`

> 6 new report types. All read from existing tables — no schema changes needed.

**2.3.1 Category-wise Sales Report**
- Query: `sale_items` JOIN `products` JOIN `categories` → GROUP BY category
- Columns: Category, Items Sold, Revenue, Cost, Profit, Margin %
- Filters: date range, branch
- Route: `GET /reports/category-wise`

**2.3.2 Monthly Sales Summary**
- Query: `sales` → GROUP BY `YEAR(sale_date), MONTH(sale_date)`
- Columns: Month, Total Sales, Returns, Net Sales, Cost, Gross Profit
- Chart.js bar chart
- Route: `GET /reports/monthly-summary`

**2.3.3 Item-Level Detail Sale Report**
- Query: `sale_items` JOIN `sales` JOIN `customers`
- Columns: Date, Invoice#, Customer, Product, SKU, Qty, Price, Discount, Total
- Filters: date range, product, category, customer, branch
- Route: `GET /reports/detail-sales`

**2.3.4 Receivables Aging Report**
- Query: `sales` WHERE payment_status IN ('partial', 'unpaid')
- Buckets: Current, 1-30, 31-60, 61-90, 90+ days overdue
- Columns: Customer, Invoice#, Sale Date, Due Date, Total, Paid, Due, Days Overdue
- Route: `GET /reports/receivables-aging`

**2.3.5 Cash Movement Report (Received vs Paid)**
- Query: `payments` grouped by direction (receive/pay) and period
- Sections: Received (by method), Paid Out (by type), Net Position
- Filters: date range, daily/weekly/monthly breakdown
- Route: `GET /reports/cash-movement`

**2.3.6 Supplier Payment Report**
- Query: `payments` WHERE party_type = 'supplier' JOIN `suppliers`
- Columns: Supplier, Total Purchased, Total Paid, Due, Last Payment Date
- Filters: date range, supplier
- Route: `GET /reports/supplier-payments`

**Where** (all reports)
- Controller: Extend `Modules/Report/app/Http/Controllers/ReportController.php`
- Service: Extend `Modules/Report/app/Services/ReportService.php`
- Views: One blade per report in `Modules/Report/resources/views/`
- Routes: `Modules/Report/routes/web.php`
- Exports: One export class per report in `app/Exports/`
- Sidebar: Add under Reports submenu

**Dependencies**: None

---

### 2.4 Weekend & Holiday Setup `[NEW]`

> Configure working days and public holidays for attendance module.

**Reference**: Quickshifter `weekend_setup` + `holiday_setup` tables

**Schema — `weekend_days` table**

```php
Schema::create('weekend_days', function (Blueprint $table) {
    $table->id();
    $table->string('name', 20); // Saturday, Sunday, etc.
    $table->unsignedTinyInteger('day_of_week'); // 0=Sunday, 1=Monday...6=Saturday
    $table->boolean('is_weekend')->default(false);
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->timestamps();

    $table->unique(['day_of_week', 'branch_id']);
});
```

**Schema — `holidays` table**

```php
Schema::create('holidays', function (Blueprint $table) {
    $table->id();
    $table->string('name', 150);
    $table->date('start_date');
    $table->date('end_date')->nullable(); // multi-day holidays
    $table->text('description')->nullable();
    $table->boolean('is_recurring')->default(false); // same date every year
    $table->boolean('is_active')->default(true);
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->timestamps();

    $table->index('start_date');
});
```

**Where**
- Migration: `Modules/Attendance/database/migrations/xxxx_create_weekend_days_table.php`
- Migration: `Modules/Attendance/database/migrations/xxxx_create_holidays_table.php`
- Models: `WeekendDay`, `Holiday` in Attendance module
- Controller: `Modules/Attendance/app/Http/Controllers/AttendanceConfigController.php`
- Routes: `Modules/Attendance/routes/web.php`
- Views: `Modules/Attendance/resources/views/config.blade.php` (tabbed: Weekends | Holidays)
- Update: Attendance marking — auto-fill weekends/holidays with appropriate status
- Seeder: Default BD weekends (Friday + Saturday)

**Routes**
```
GET    /attendance/config                → config
POST   /attendance/config/weekends       → saveWeekends
POST   /attendance/config/holidays       → storeHoliday
PUT    /attendance/config/holidays/{id}  → updateHoliday
DELETE /attendance/config/holidays/{id}  → destroyHoliday
```

**Dependencies**: None

---

### 2.5 Email Configuration & Templates `[NEW]`

> Admin panel SMTP setup and transactional email templates.

**Reference**: Quickshifter `email_templates` table + `EmailSettingController` + `GlobalSettingController`

**Schema — `email_templates` table**

```php
Schema::create('email_templates', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 100)->unique(); // 'sale-invoice', 'quotation-send', etc.
    $table->string('name', 150);
    $table->string('subject', 255);
    $table->longText('body'); // supports {{customer_name}}, {{invoice_number}} etc.
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**SMTP settings** — stored in existing `settings` table with group `email`:
```
email.mail_driver     = smtp
email.mail_host       = smtp.gmail.com
email.mail_port       = 587
email.mail_username   = (encrypted)
email.mail_password   = (encrypted)
email.mail_encryption = tls
email.mail_from_address = noreply@bizpos.com
email.mail_from_name   = BizPOS Pro
```

**Where**
- Migration: `Modules/Setting/database/migrations/xxxx_create_email_templates_table.php`
- Model: `Modules/Setting/app/Models/EmailTemplate.php`
- Controller: `Modules/Setting/app/Http/Controllers/EmailSettingController.php`
- Service: `Modules/Setting/app/Services/EmailService.php` — render template with placeholders, send via configured SMTP
- Routes: `Modules/Setting/routes/web.php`
- Views: `Modules/Setting/resources/views/email/` — config, template-list, template-edit
- Seeder: Default templates — sale-invoice, quotation-send, payment-receipt, low-stock-alert

**Routes**
```
GET  /settings/email                         → emailConfig
POST /settings/email                         → saveEmailConfig
POST /settings/email/test                    → sendTestEmail
GET  /settings/email/templates               → templateIndex
GET  /settings/email/templates/{tpl}/edit    → templateEdit
PUT  /settings/email/templates/{tpl}         → templateUpdate
```

**Dependencies**: None

---

### 2.6 Export Classes `[NEW]`

> Dedicated export classes for modules that currently lack them. BizPOS already has `SalesExport`, `ProductsExport`, `PurchasesExport`, `CustomersExport`, `ExpensesExport`. Need the rest.

**Reference**: Quickshifter has 37 export classes

**New Export Classes**

| Class | Source Tables | Key Columns |
|-------|-------------|-------------|
| `StockExport` | warehouse_stock + products | Product, SKU, Warehouse, Qty, Reserved, Reorder Level, Value |
| `EmployeesExport` | employees | Employee ID, Name, Phone, Department, Designation, Join Date, Salary, Status |
| `PayrollExport` | payrolls + payroll_items | Employee, Month, Basic, Earnings, Deductions, Net, Status |
| `AssetsExport` | assets + asset_categories | Code, Name, Category, Purchase Date, Cost, Depreciation, Current Value, Status |
| `SuppliersExport` | suppliers | Company, Contact, Phone, Total Purchase, Total Paid, Due, Advance |
| `AccountLedgerExport` | journal_entry_lines + journal_entries | Date, Entry#, Account, Description, Debit, Credit |
| `ReceivablesExport` | sales (unpaid/partial) | Customer, Invoice#, Date, Due Date, Total, Paid, Due, Days Overdue |

**Where**
- All in `app/Exports/` — each implements `FromCollection`, `WithHeadings`, `WithMapping`, `WithStyles`
- Wired through existing `GET /export/{module}` route
- Add export buttons to respective index views

**Dependencies**: None

---

### 2.7 POS Settings `[EXISTS — Enhance]`

> Currently `auto_print` and `pos_print_format` in settings table (group: `invoice`). Add a dedicated POS settings page with more options.

**Reference**: Quickshifter `pos_settings` table (show_note, show_barcode, show_discount, show_customer, etc.)

**New settings keys** — stored in existing `settings` table with group `pos`:

```
pos.default_customer_id        — Default walk-in customer (FK)
pos.default_payment_method     — Default method (cash)
pos.sound_enabled              — Beep on scan (boolean)
pos.auto_focus_barcode         — Auto-focus barcode input (boolean)
pos.show_stock_qty             — Show stock on POS items (boolean)
pos.allow_negative_stock       — Allow selling below zero (boolean)
pos.receipt_header             — Custom receipt header text
pos.receipt_footer             — Custom receipt footer text
pos.receipt_show_logo          — Show logo on receipt (boolean)
pos.receipt_show_customer      — Show customer on receipt (boolean)
pos.receipt_show_barcode       — Show barcode on receipt (boolean)
```

**Where**
- Controller: `POSController.php` — add `settings()`, `saveSettings()`
- Routes: `Modules/POS/routes/web.php`
- Views: `Modules/POS/resources/views/settings.blade.php`
- Sidebar: Add "POS Settings" under Main section

**Dependencies**: None

---

## Phase 3 — Advanced Features (Medium-Low Priority)

---

### 3.1 Service Sales Flow `[EXISTS — Enhance]`

> Products with `product_type = 'service'` already exist but stock deduction still runs for them. Need to skip inventory operations for service-type products.

**Reference**: Quickshifter `services` table + `service_categories` + `ServiceController`

**No new tables** — BizPOS already has `product_type` field on `products` table. Service products can be created with type `service`. Just needs:

**Changes**
- Update: `SaleService::deductStockForItems()` — skip items where `product.product_type === 'service'`
- Update: `SaleService::reverseStockForSale()` — same skip
- Update: Sale create view — add filter toggle to show only services or only products
- Update: Reports — add ability to filter/separate service revenue from product revenue

**Dependencies**: None

---

### 3.2 Customer Area Management `[NEW]`

> Geographic hierarchy for delivery areas. Quickshifter has a flat `areas` table. BizPOS needs BD-specific hierarchy.

**Reference**: Quickshifter `areas` table (flat: id, name)

**Schema — `areas` table**

```php
Schema::create('areas', function (Blueprint $table) {
    $table->id();
    $table->string('name', 150);
    $table->foreignId('parent_id')->nullable()->constrained('areas')->nullOnDelete();
    $table->string('level', 20); // division, district, area
    $table->decimal('delivery_charge', 10, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index('parent_id');
    $table->index('level');
});
```

**Schema — alter `customers` table**

```php
Schema::table('customers', function (Blueprint $table) {
    $table->foreignId('area_id')->nullable()->after('upazila')
          ->constrained('areas')->nullOnDelete();
});
```

**Where**
- Migration: `Modules/Customer/database/migrations/xxxx_create_areas_table.php`
- Migration: `Modules/Customer/database/migrations/xxxx_add_area_id_to_customers.php`
- Model: `Modules/Customer/app/Models/Area.php` — `parent()`, `children()`, `scopeByLevel()`
- Controller: `Modules/Customer/app/Http/Controllers/AreaController.php`
- Routes: `Modules/Customer/routes/web.php`
- Views: `Modules/Customer/resources/views/areas/index.blade.php` — tree view with inline CRUD
- Seeder: 8 BD divisions + major districts
- Update: Customer forms — cascading area selector (division → district → area)

**Routes**
```
GET    /customer-areas                       → index
POST   /customer-areas                       → store
PUT    /customer-areas/{area}                → update
DELETE /customer-areas/{area}                → destroy
GET    /customer-areas/{area}/children       → children (JSON, for cascading select)
```

**Dependencies**: None

---

### 3.3 Offset Customer Due with Advance `[NEW]`

> One action to apply customer advance balance against outstanding sales dues.

**No new tables** — uses existing `customers`, `sales`, `payments`, `payment_allocations`

**Where**
- Controller: `Modules/Customer/app/Http/Controllers/CustomerController.php` — add `offsetDue()`
- Service: add offset logic to `CustomerService`
- Route: `POST /customers/{customer}/offset-due`
- Views: Add button on customer show page, confirmation modal showing: advance_balance, total_due, amount_to_apply

**Logic**
```
1. Get customer.advance_balance (available advance)
2. Get unpaid/partial sales ordered by sale_date ASC (FIFO)
3. Loop: apply advance to each sale's due_amount
4. For each allocation:
   - Create Payment record (direction: receive, type: advance_return)
   - Create PaymentAllocation linking to the sale
   - Update sale.paid_amount, sale.due_amount, sale.payment_status
5. Reduce customer.advance_balance by total applied
6. Update customer.total_paid
7. Create journal entry (DR Customer Advance → CR Accounts Receivable)
```

**Dependencies**: None

---

### 3.4 Other Income / Non-Transaction Dues `[NEW]`

> Track miscellaneous income and non-sale customer/supplier dues (penalties, damage charges, goodwill adjustments).

**Reference**: Quickshifter `other_summeries` table

**Schema — `other_transactions` table**

```php
Schema::create('other_transactions', function (Blueprint $table) {
    $table->id();
    $table->string('type', 30); // income, customer_due, supplier_due, customer_credit, supplier_credit
    $table->string('party_type', 30)->nullable(); // customer, supplier
    $table->unsignedBigInteger('party_id')->nullable();
    $table->decimal('amount', 15, 2);
    $table->decimal('paid_amount', 15, 2)->default(0);
    $table->decimal('due_amount', 15, 2)->default(0);
    $table->string('reference', 100)->nullable();
    $table->text('description')->nullable();
    $table->date('transaction_date');
    $table->foreignId('payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
    $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['party_type', 'party_id']);
    $table->index('type');
    $table->index('transaction_date');
});
```

**Where**
- Migration: `Modules/Accounting/database/migrations/xxxx_create_other_transactions_table.php`
- Model: `Modules/Accounting/app/Models/OtherTransaction.php`
- Controller: `Modules/Accounting/app/Http/Controllers/OtherTransactionController.php`
- Service: `Modules/Accounting/app/Services/OtherTransactionService.php`
- Routes: `Modules/Accounting/routes/web.php`
- Views: `Modules/Accounting/resources/views/other-transactions/` — index, create
- Sidebar: Add "Other Income" under Accounting submenu

**Routes**
```
GET    /accounting/other-transactions          → index
GET    /accounting/other-transactions/create   → create
POST   /accounting/other-transactions          → store
GET    /accounting/other-transactions/{id}     → show
DELETE /accounting/other-transactions/{id}     → destroy
```

**Dependencies**: None

---

### 3.5 Activity Logging — Comprehensive Coverage `[EXISTS — Enhance]`

> BizPOS already has `activity_logs` table with the `LogsActivity` trait (Spatie-style). Every model that uses this trait logs created/updated/deleted events with old/new values in JSON `properties` column. Instead of a separate transaction logger, ensure ALL financial models use the existing `LogsActivity` trait comprehensively.

**Existing schema — `activity_logs` table**
```
id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties (json), event, timestamps
```

The `properties` column already stores `{ "old": {...}, "attributes": {...} }` — this IS the before/after state snapshot.

**What needs to happen**
- Verify ALL financial models have `use LogsActivity` trait:
  - Sale, SaleItem, SaleReturn ✓ (verify)
  - Purchase, PurchaseItem, PurchaseReturn ✓ (verify)
  - Payment, PaymentAllocation
  - Expense, RecurringExpense
  - StockAdjustment, StockTransfer
  - JournalEntry
  - Quotation
  - DeliveryChallan
  - Installment
  - CreditNote, DebitNote
  - Asset, AssetMaintenance
  - Payroll
  - ExpenseVendor (new in 1.4)
  - OtherTransaction (new in 3.4)
- Ensure `$activityLogName` is set to a descriptive name on each model (e.g., `'sales'`, `'purchases'`, `'payments'`)
- Ensure `getActivitylogOptions()` captures all important attributes (not just fillable)
- Update Activity Log UI to support filtering by log_name (module filter)
- Add "Financial Activity" filter tab on the Activity Log page

**Where**
- Update: Each model listed above — add/verify `LogsActivity` trait
- Update: `Modules/Activity/` views — add module filter dropdown
- No new tables, no new migration

**Dependencies**: None

---

### 2.8 Sidebar Menu Configuration `[NEW]`

> Let admins toggle sidebar menu items on/off from Settings. Businesses that don't use HR, eCommerce, or Marketing shouldn't see those sections cluttering the sidebar.

**No new tables** — use existing `settings` table with group `sidebar`

**Settings keys** — one key per sidebar section/item, stored as boolean:

```
sidebar.pos_terminal        = true
sidebar.pos_settlement      = true
sidebar.products            = true
sidebar.warehouses          = true
sidebar.stock               = true
sidebar.purchases           = true
sidebar.sales               = true
sidebar.customers           = true
sidebar.suppliers           = true
sidebar.employees_link      = true   (standalone link under People)
sidebar.payments            = true
sidebar.payment_accounts    = true
sidebar.expenses            = true
sidebar.assets              = true
sidebar.accounting          = true
sidebar.ecommerce           = false  (off by default — not all businesses need it)
sidebar.reports             = true
sidebar.staff_hr            = true
sidebar.marketing           = false  (off by default)
sidebar.branches            = true
sidebar.settings            = true
sidebar.activity_log        = true
sidebar.security            = true
```

**Where**
- Controller: `Modules/Setting/app/Http/Controllers/SettingController.php` — add `sidebarConfig()`, `saveSidebarConfig()`
- Routes: `Modules/Setting/routes/web.php`
- Views: `Modules/Setting/resources/views/sidebar-config.blade.php` — list of all menu items with toggle switches, grouped by section (Main, Inventory, Transactions, People, Finance, Online, Analytics, HR, System)
- Update: `Modules/Core/resources/views/partials/sidebar.blade.php` — wrap each section/item in `@if(setting('sidebar.{key}', true))` checks
- Helper: Add `setting()` helper function (or use existing) to read from settings with default fallback — cache-friendly

**Routes**
```
GET  /settings/sidebar      → sidebarConfig
POST /settings/sidebar      → saveSidebarConfig
```

**UI Design**
```
┌─────────────────────────────────────────────┐
│ Sidebar Menu Configuration                  │
├─────────────────────────────────────────────┤
│ MAIN                                        │
│   [✓] POS Terminal                          │
│   [✓] POS Settlement                        │
│                                             │
│ INVENTORY                                   │
│   [✓] Products                              │
│   [✓] Warehouses                            │
│   [✓] Stock                                 │
│                                             │
│ TRANSACTIONS                                │
│   [✓] Purchases                             │
│   [✓] Sales                                 │
│                                             │
│ PEOPLE                                      │
│   [✓] Customers                             │
│   [✓] Suppliers                             │
│   [✓] Employees                             │
│                                             │
│ FINANCE                                     │
│   [✓] Payments                              │
│   [✓] Payment Accounts                      │
│   [✓] Expenses                              │
│   [✓] Assets                                │
│   [✓] Accounting                            │
│                                             │
│ ONLINE                                      │
│   [ ] eCommerce          ← off by default   │
│                                             │
│ ANALYTICS                                   │
│   [✓] Reports                               │
│                                             │
│ HR                                          │
│   [✓] Staff (HR)                            │
│   [ ] Marketing          ← off by default   │
│                                             │
│ SYSTEM                                      │
│   [✓] Branches                              │
│   [✓] Settings           ← always visible   │
│   [✓] Activity Log                          │
│   [✓] Security                              │
│                                             │
│               [Save Changes]                │
└─────────────────────────────────────────────┘
```

**Sidebar Integration**

In `sidebar.blade.php`, wrap each section:
```blade
@if(setting('sidebar.products', true))
<li class="menu-item ...">
  <a href="#" class="menu-link ..." data-toggle="submenu">
    <i class="fa-solid fa-boxes-stacked"></i>
    <span class="menu-text">Products</span>
    ...
  </a>
  <ul class="submenu">...</ul>
</li>
@endif
```

**Notes**
- Settings page itself and Dashboard are always visible (not toggleable)
- Use `setting()` helper with caching to avoid DB queries on every page load
- Settings are global (apply to all users). Per-role sidebar visibility should use the existing Roles & Permissions system instead
- Seeder: Set defaults — most items `true`, eCommerce and Marketing `false`

**Dependencies**: None

---

## Phase 4 — Logging, Printing & System Administration

---

### 4.1 Laravel System Log Viewer `[NEW]`

> View `storage/logs/laravel.log` (and daily log files like `laravel-2026-03-30.log`) from the admin panel. See application errors, exceptions, warnings, debug messages without SSH access. Essential for troubleshooting production issues.

**No new tables** — reads log files directly from `storage/logs/`

**Where**
- Create module: `Modules/SystemLog/` (new module via `php artisan module:make SystemLog`)
- Or add to existing Setting module — `Modules/Setting/app/Http/Controllers/SystemLogController.php`

**Files to Create**

```
Modules/Setting/app/Http/Controllers/SystemLogController.php
Modules/Setting/app/Services/LogViewerService.php
Modules/Setting/resources/views/logs/index.blade.php
Modules/Setting/resources/views/logs/show.blade.php
```

**Service: `LogViewerService.php`**

```
Methods:
├── getLogFiles(): array
│   → Scan storage/logs/ for *.log files
│   → Return: [{filename, size_human, last_modified, path}]
│   → Sort by last_modified DESC
│
├── parseLogFile(string $filename, array $filters = []): array
│   → Read log file (tail — read last N lines first for performance)
│   → Parse Laravel log format:
│     [2026-03-30 14:23:45] production.ERROR: Message {stack trace}
│   → Extract per entry:
│     - timestamp (datetime)
│     - environment (production/local)
│     - level (EMERGENCY, ALERT, CRITICAL, ERROR, WARNING, NOTICE, INFO, DEBUG)
│     - message (first line)
│     - stack_trace (remaining lines until next entry)
│     - context (JSON if present)
│   → Apply filters: level, search term, date range
│   → Return: paginated array of parsed entries
│
├── getLogStats(string $filename): array
│   → Count entries per level
│   → Return: {emergency: 0, alert: 0, critical: 0, error: 12, warning: 45, ...}
│
├── deleteLogFile(string $filename): bool
│   → Validate filename (prevent path traversal — only allow files in storage/logs/)
│   → Delete the file
│
├── downloadLogFile(string $filename): StreamedResponse
│   → Validate filename
│   → Return file download
│
└── clearLogFile(string $filename): bool
    → Truncate file to 0 bytes (keep the file, just empty it)
```

**Controller: `SystemLogController.php`**

```
Methods:
├── index(Request $request)
│   → List all log files with size and last modified
│   → Show aggregate stats (total errors today, total warnings)
│   → View: logs/index.blade.php
│
├── show(Request $request, string $filename)
│   → Parse and display entries from specific log file
│   → Filters: level, search, date range
│   → Paginated (50 entries per page, newest first)
│   → View: logs/show.blade.php
│
├── download(string $filename)
│   → Download raw log file
│
├── clear(string $filename)
│   → Empty the log file contents
│   → Redirect back with success
│
└── delete(string $filename)
    → Delete the log file entirely
    → Redirect back with success
```

**Routes** — add to `Modules/Setting/routes/web.php`
```
GET    /system-logs                        → index
GET    /system-logs/{filename}             → show (with ?level=&search=&date= filters)
GET    /system-logs/{filename}/download    → download
POST   /system-logs/{filename}/clear       → clear
DELETE /system-logs/{filename}             → delete
```

**Sidebar**: Add "System Logs" under System section (below Activity Log)

**View: `logs/index.blade.php` — Log Files List**

```
┌──────────────────────────────────────────────────────────────────┐
│ System Logs                                        [Clear All]  │
├──────────────────────────────────────────────────────────────────┤
│ Stats: 🔴 12 Errors Today  🟡 45 Warnings Today  📄 8 Files   │
├──────────────────────────────────────────────────────────────────┤
│ File Name                    │ Size     │ Last Modified  │ Act  │
│──────────────────────────────│──────────│────────────────│──────│
│ laravel-2026-03-30.log       │ 245 KB   │ 30 Mar, 2:15pm│ 👁📥🗑│
│ laravel-2026-03-29.log       │ 1.2 MB   │ 29 Mar, 11:59p│ 👁📥🗑│
│ laravel-2026-03-28.log       │ 89 KB    │ 28 Mar, 11:59p│ 👁📥🗑│
│ laravel.log                  │ 5.4 MB   │ 30 Mar, 2:15pm│ 👁📥🗑│
└──────────────────────────────────────────────────────────────────┘
```

**View: `logs/show.blade.php` — Single Log File Entries**

```
┌──────────────────────────────────────────────────────────────────┐
│ laravel-2026-03-30.log                     [Download] [Clear]   │
├──────────────────────────────────────────────────────────────────┤
│ Level Filter:                                                    │
│ [All] [Emergency] [Critical] [Error] [Warning] [Info] [Debug]   │
│                                                                  │
│ Search: [____________________]  From: [____]  To: [____]        │
├──────────────────────────────────────────────────────────────────┤
│ Stats: 🔴 Error: 12  🟡 Warning: 45  🔵 Info: 120  ⚪ Debug: 8 │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│ ┌── 🔴 ERROR — 30 Mar 2026, 14:23:45 ──────────────────────┐   │
│ │ SQLSTATE[42S02]: Table 'bizpos.tax_rates' doesn't exist    │   │
│ │                                                             │   │
│ │ ▼ Stack Trace (click to expand)                             │   │
│ │ #0 vendor/laravel/framework/src/Database/Connection.php:795│   │
│ │ #1 vendor/laravel/framework/src/Database/Connection.php:755│   │
│ │ ...                                                         │   │
│ └─────────────────────────────────────────────────────────────┘   │
│                                                                  │
│ ┌── 🟡 WARNING — 30 Mar 2026, 14:20:12 ────────────────────┐   │
│ │ Failed to record sale journal entry: Account not found      │   │
│ │ {"sale_id": 142, "error": "No account with code 4100"}     │   │
│ └─────────────────────────────────────────────────────────────┘   │
│                                                                  │
│ ┌── 🔵 INFO — 30 Mar 2026, 14:15:00 ───────────────────────┐   │
│ │ User admin@bizpos.com logged in from 192.168.1.10          │   │
│ └─────────────────────────────────────────────────────────────┘   │
│                                                                  │
│ Pagination: ← 1 2 3 4 5 →                                       │
└──────────────────────────────────────────────────────────────────┘
```

**Level Color Mapping (CSS classes)**
```
EMERGENCY → bp-badge-danger   (dark red)
ALERT     → bp-badge-danger
CRITICAL  → bp-badge-danger
ERROR     → bp-badge-danger   (red)
WARNING   → bp-badge-warning  (orange)
NOTICE    → bp-badge-info     (blue)
INFO      → bp-badge-info     (light blue)
DEBUG     → bp-badge-dark     (grey)
```

**Security**
- Validate filenames — only allow `[a-zA-Z0-9\-_.]` characters, must end with `.log`, must be inside `storage/logs/`
- Prevent path traversal attacks (`../`, `..\\`, absolute paths)
- Only accessible to admin users (consider restricting to super-admin role)
- Never expose log contents via API — web only

**Log Parsing Regex**
```php
// Laravel log entry pattern:
// [2026-03-30 14:23:45] production.ERROR: Message here {"context":"json"}\n
$pattern = '/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s(\w+)\.(\w+):\s(.*)$/';

// Stack trace lines start with #N or whitespace
// Context JSON is inline after the message
```

**Performance Notes**
- For large files (>10MB), read from the END of file using `SplFileObject::seek()` or `tail` approach
- Parse on-demand, don't load entire file into memory
- Cache stats per file with 5-minute TTL
- Consider log rotation — recommend daily logs (`LOG_CHANNEL=daily` in `.env`)

**Dependencies**: None

---

### 4.2 Activity Log Enhancement `[EXISTS — Enhance]`

> The existing activity log system works but only 5 models use the `LogsActivity` trait. Need comprehensive coverage + better UI.

**Step 1 — Add `LogsActivity` trait to ALL remaining models**

| Module | Model | `$activityLogName` | Status |
|--------|-------|-------------------|--------|
| Sale | Sale | `'sales'` | ✅ Already has it |
| Sale | SaleItem | — | Skip (child — logged via Sale) |
| Sale | SaleReturn | `'sale_returns'` | ❌ Add trait |
| Purchase | Purchase | `'purchases'` | ✅ Already has it |
| Purchase | PurchaseReturn | `'purchase_returns'` | ❌ Add trait |
| Product | Product | `'products'` | ✅ Already has it |
| Customer | Customer | `'customers'` | ✅ Already has it |
| Expense | Expense | `'expenses'` | ✅ Already has it |
| Expense | RecurringExpense | `'recurring_expenses'` | ❌ Add trait |
| Expense | ExpenseVendor | `'expense_vendors'` | ❌ Add trait (new in 1.4) |
| Payment | Payment | `'payments'` | ❌ Add trait |
| Payment | PaymentAccount | `'payment_accounts'` | ❌ Add trait |
| Payment | BalanceTransfer | `'balance_transfers'` | ❌ Add trait |
| Quotation | Quotation | `'quotations'` | ❌ Add trait |
| Inventory | StockAdjustment | `'stock_adjustments'` | ❌ Add trait |
| Inventory | StockTransfer | `'stock_transfers'` | ❌ Add trait |
| Accounting | JournalEntry | `'journal_entries'` | ❌ Add trait |
| Accounting | CreditNote | `'credit_notes'` | ❌ Add trait |
| Accounting | DebitNote | `'debit_notes'` | ❌ Add trait |
| Asset | Asset | `'assets'` | ❌ Add trait |
| Delivery | DeliveryChallan | `'delivery_challans'` | ❌ Add trait |
| Payroll | Payroll | `'payrolls'` | ❌ Add trait |
| Employee | Employee | `'employees'` | ❌ Add trait |
| Supplier | Supplier | `'suppliers'` | ❌ Add trait |
| Branch | Branch | `'branches'` | ❌ Add trait |
| Ecommerce | EcommerceOrder | `'ecommerce_orders'` | ❌ Add trait |
| Installment | InstallmentPlan | `'installments'` | ❌ Add trait |

**Total: 5 existing + 21 to add = 26 models with activity logging**

**Step 2 — Add login/logout logging**

In `Modules/Auth` or `app/Providers/EventServiceProvider.php`:
```php
// Listen to Login/Logout events
Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
    ActivityLog::log(
        description: "User {$event->user->name} logged in",
        subject: $event->user,
        causer: $event->user,
        properties: ['ip' => request()->ip(), 'user_agent' => request()->userAgent()],
        event: 'login',
        logName: 'auth',
    );
});

Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
    ActivityLog::log(
        description: "User {$event->user->name} logged out",
        subject: $event->user,
        causer: $event->user,
        event: 'logout',
        logName: 'auth',
    );
});

Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
    ActivityLog::log(
        description: "Failed login attempt for {$event->credentials['email']}",
        properties: ['ip' => request()->ip(), 'email' => $event->credentials['email']],
        event: 'login_failed',
        logName: 'auth',
    );
});
```

**Step 3 — Enhance Activity Log UI**

Update `Modules/Activity/resources/views/index.blade.php`:

**3a. Module Quick Filter Tabs**
```
[All] [Sales] [Purchases] [Payments] [Expenses] [Products] [Customers] [Suppliers] [Auth] [Stock] [Accounting] [System]
```
Each tab filters by `log_name`. Show count badge on each tab.

**3b. Enhanced Stats Row**
```
┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐
│ Total      │ │ Today      │ │ Creates    │ │ Updates    │ │ Deletes    │
│ 12,456     │ │ 89         │ │ 5,230      │ │ 6,102      │ │ 1,124      │
└────────────┘ └────────────┘ └────────────┘ └────────────┘ └────────────┘
```

**3c. Timeline View Option**
Add a toggle between Table view and Timeline view:
```
Table View:
  Standard table as current

Timeline View:
  ──●── 14:23  admin@bizpos.com created Sale S20260330001 (BDT 5,200)
  ──●── 14:20  admin@bizpos.com updated Product "Samsung A54" (price: 32000 → 29999)
  ──●── 14:15  admin@bizpos.com deleted Expense #EXP-2026-0089
  ──●── 14:10  admin@bizpos.com logged in (IP: 192.168.1.10)
  ──●── 13:55  cashier@bizpos.com created Sale S20260330002 (BDT 1,850)
```

**3d. Export**
- Add Excel/CSV export button — `GET /activity/export`
- Export class: `app/Exports/ActivityLogExport.php`
- Columns: Date, Time, User, Module, Event, Description, Changed Fields

**3e. Show Page Enhancement**
Update `Modules/Activity/resources/views/show.blade.php`:
- Show "Navigate to Subject" link — if the subject still exists, show a link to view it (e.g., link to the sale, product, customer page)
- Show IP address and User Agent if available (from login events)
- Better diff display — side-by-side old vs new with highlighted changes

**Step 4 — Auto-cleanup Scheduler**

In `app/Console/Kernel.php` or `routes/console.php`:
```php
Schedule::call(function () {
    \Modules\Activity\Models\ActivityLog::where('created_at', '<', now()->subDays(180))->delete();
})->daily()->at('03:00')->name('cleanup-activity-logs');
```

Configurable via setting: `system.activity_log_retention_days = 180`

**Where**
- Update: 21 models listed above — add `use LogsActivity` trait + set `$activityLogName`
- Update: `app/Providers/EventServiceProvider.php` — login/logout/failed listeners
- Update: `Modules/Activity/resources/views/index.blade.php` — tabs, stats, timeline toggle, export
- Update: `Modules/Activity/resources/views/show.blade.php` — subject link, better diff
- Create: `app/Exports/ActivityLogExport.php`
- Update: `Modules/Activity/app/Services/ActivityService.php` — add `export()` method, update `getStats()`
- Update: `Modules/Activity/routes/web.php` — add export route
- Update: Scheduler — auto-cleanup

**Routes** (additions to existing)
```
GET /activity/export   → export (with same filters as index)
```

**Dependencies**: None

---

### 4.3 Printer IP Management `[NEW]`

> Configure network printer IP addresses for receipt/invoice printing from POS, mobile devices, or any browser. Supports ESC/POS thermal printers and network printers accessible via IP.

**Schema — `printers` table**

```php
Schema::create('printers', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);              // "Main Counter Printer", "Kitchen Printer"
    $table->string('ip_address', 45);          // 192.168.1.100 or IPv6
    $table->unsignedSmallInteger('port')->default(9100); // ESC/POS default port
    $table->string('printer_type', 30)->default('thermal');
        // thermal (80mm receipt), thermal_58 (58mm), a4 (full page), label
    $table->string('connection_type', 20)->default('network');
        // network (IP), usb, bluetooth
    $table->unsignedTinyInteger('paper_width')->default(80); // mm: 58, 80, 210 (A4)
    $table->string('purpose', 30)->default('receipt');
        // receipt, invoice, barcode, kitchen, report
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->boolean('is_default')->default(false);
    $table->boolean('is_active')->default(true);
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->index('branch_id');
    $table->index('is_active');
    $table->unique(['ip_address', 'port']);
});
```

**Where**
- Migration: `Modules/Setting/database/migrations/xxxx_create_printers_table.php`
- Model: `Modules/Setting/app/Models/Printer.php`
- Service: `Modules/Setting/app/Services/PrinterService.php`
- Controller: `Modules/Setting/app/Http/Controllers/PrinterController.php`
- Routes: `Modules/Setting/routes/web.php`
- Views: `Modules/Setting/resources/views/printers/` — index, create, edit
- Sidebar: Add "Printers" under System section

**Controller Methods**

```
index()         → List all printers with status indicators
create()        → Add new printer form
store()         → Save printer
edit()          → Edit printer form
update()        → Update printer
destroy()       → Delete printer
testConnection()→ AJAX: test if printer is reachable (ping IP:port)
setDefault()    → Set a printer as default for its purpose
printTest()     → Send test print job to verify printer works
```

**Routes**
```
GET    /printers                         → index
GET    /printers/create                  → create
POST   /printers                         → store
GET    /printers/{printer}/edit          → edit
PUT    /printers/{printer}              → update
DELETE /printers/{printer}              → destroy
POST   /printers/{printer}/test         → testConnection (AJAX, returns JSON)
POST   /printers/{printer}/test-print   → printTest (sends test page)
POST   /printers/{printer}/set-default  → setDefault
```

**Service: `PrinterService.php`**

```
Methods:
├── list(array $filters): LengthAwarePaginator
│
├── getDefaultPrinter(string $purpose = 'receipt', ?int $branchId = null): ?Printer
│   → Find default printer for purpose + branch
│   → Fallback: any active printer for that purpose
│
├── testConnection(Printer $printer): array
│   → Open socket to $printer->ip_address:$printer->port with 3s timeout
│   → Return: {success: true/false, latency_ms: 12, error: null}
│
├── getActivePrinters(?int $branchId = null): Collection
│   → Return all active printers, optionally filtered by branch
│
└── getPrintersByPurpose(string $purpose): Collection
    → Return active printers filtered by purpose
```

**View: `printers/index.blade.php`**

```
┌──────────────────────────────────────────────────────────────────────┐
│ Printers                                           [+ Add Printer]  │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│ Name              │ IP:Port           │ Type    │ Purpose │ Branch │ St │
│───────────────────│───────────────────│─────────│─────────│────────│────│
│ Main Counter      │ 192.168.1.100:9100│ Thermal │ Receipt │ Main   │ 🟢 │
│  ⭐ Default       │                   │ 80mm    │         │        │    │
│───────────────────│───────────────────│─────────│─────────│────────│────│
│ Back Office       │ 192.168.1.101:9100│ A4      │ Invoice │ Main   │ 🟢 │
│───────────────────│───────────────────│─────────│─────────│────────│────│
│ Branch 2 Printer  │ 10.0.0.50:9100   │ Thermal │ Receipt │ Mirpur │ 🔴 │
│                   │                   │ 58mm    │         │        │    │
│───────────────────│───────────────────│─────────│─────────│────────│────│
│ Barcode Printer   │ 192.168.1.102:9100│ Label   │ Barcode │ Main   │ 🟢 │
└──────────────────────────────────────────────────────────────────────┘
Actions per row: Test Connection | Test Print | Edit | Set Default | Delete
```

**Integration with POS and Invoices**

After creating the printer management, integrate it into the print flow:

**Step 1: JavaScript Print Helper** — add to `public/js/app.js`
```javascript
// Network print via server-side proxy
function printToNetworkPrinter(printerId, content, type) {
    $.post('/printers/' + printerId + '/print-job', {
        content: content,    // HTML content or receipt data
        type: type,          // 'receipt', 'invoice', 'barcode'
        _token: $('meta[name="csrf-token"]').attr('content')
    }).done(function(res) {
        if (res.success) {
            // Optional: show toast "Sent to printer"
        }
    }).fail(function() {
        // Fallback: browser print
        window.print();
    });
}
```

**Step 2: Server-Side Print Proxy Route**
```
POST /printers/{printer}/print-job → sendPrintJob
```
- For **ESC/POS thermal printers**: Convert receipt HTML to ESC/POS commands, send via raw socket to IP:port
- For **network printers**: Use IPP protocol or raw TCP print
- For **browser fallback**: Return JSON with `{fallback: true}` and let JS do `window.print()`

**Step 3: POS Integration**
- In POS settings (2.7), add: "Default Receipt Printer" dropdown populated from `printers` where purpose='receipt'
- On sale completion: auto-send receipt to default printer via AJAX
- Fallback: browser print dialog if no printer configured or printer offline

**Step 4: Mobile Device Printing**
- Mobile devices on same network can print by hitting the same endpoint
- PWA POS → sale complete → AJAX to `/printers/{id}/print-job` → server sends to thermal printer
- No driver installation needed on the mobile device — server handles the TCP connection

**ESC/POS Receipt Format Helper** — `app/Services/EscPosService.php`
```
Methods:
├── buildReceipt(Sale $sale, Printer $printer): string
│   → Generate ESC/POS byte commands for thermal printer
│   → Handles: text alignment, bold, font size, cut paper, barcode
│   → Adapts to paper width (58mm vs 80mm)
│
├── buildInvoice(Sale $sale, Printer $printer): string
│   → A4 format for network/laser printers (raw HTML or PCL)
│
├── sendToPrinter(Printer $printer, string $data): bool
│   → Open TCP socket to ip:port
│   → Write data
│   → Close socket
│   → Return success/failure
│
└── buildTestPage(Printer $printer): string
    → Generate test print with: printer name, IP, date, alignment test, cut
```

**Dependencies**: None (but integrates with POS settings 2.7)

---

### 4.4 Maintenance Mode Toggle `[NEW]`

**Where**
- Add to `SettingController.php` — `toggleMaintenance()`
- Route: `POST /settings/maintenance`
- View: Add toggle switch to Settings page
- Logic: `Artisan::call('down', ['--secret' => $adminSecret])` / `Artisan::call('up')`

**Dependencies**: None

---

### 4.5 Cache Clear from UI `[NEW]`

**Where**
- Add to `SettingController.php` — `clearCache()`
- Route: `POST /settings/clear-cache`
- View: Add button to Settings page
- Logic: Clear app, config, route, view caches via Artisan

**Dependencies**: None

---

### 4.6 Custom Pagination Settings `[NEW]`

**No new table** — use existing `settings` table with group `pagination`

**Settings keys**
```
pagination.products   = 15
pagination.sales      = 15
pagination.purchases  = 15
pagination.customers  = 15
pagination.suppliers  = 15
pagination.expenses   = 15
pagination.employees  = 15
pagination.reports    = 25
```

**Where**
- Add section to Settings page
- Update all service `list()` methods: read from `setting('pagination.{module}', 15)` instead of hardcoded 15

**Dependencies**: None

---

### 4.7 SEO Settings (eCommerce) `[NEW]`

**No new table** — use existing `settings` table with group `seo`

**Settings keys**
```
seo.site_title       = BizPOS Store
seo.meta_description = ...
seo.meta_keywords    = ...
seo.og_image         = (file path)
seo.google_analytics = UA-XXXXX / G-XXXXX
seo.facebook_pixel   = XXXXX
```

**Where**
- Add as tab in eCommerce Store Settings
- Update frontend layout to read these meta values

**Dependencies**: Ecommerce module

---

## Phase 5 — Internationalization (Deferred)

### 5.1 Multi-Language `[DEFERRED]`

> Quickshifter `languages` table (name, code, icon, direction, status, is_default). Build only if expanding beyond Bengali market.

### 5.2 Multi-Currency `[DEFERRED]`

> Quickshifter `multi_currencies` table (currency_name, country_code, currency_code, currency_icon, is_default, currency_rate, currency_position, status). Build only if going international.

---

## Implementation Timeline

```
Phase 1 (Weeks 1-3) — Core Business Gaps
  1.1  Customer Groups               ~3 hrs
  1.2  Supplier Groups               ~3 hrs
  1.3  Supplier CSV Import           ~2 hrs
  1.4  Expense Vendors               ~8 hrs
  1.5  POS Cart Hold                 ~4 hrs
  1.6  DTS Report                    ~4 hrs
  1.7  Stock Reconciliation          ~6 hrs
  1.8  Opening Balance UI            ~3 hrs
  1.9  Payment Account Ledger        ~5 hrs

Phase 2 (Weeks 4-6) — Operational Enhancements
  2.1  Hierarchical Expense Cats     ~2 hrs
  2.2  Purchase Return Types         ~2 hrs
  2.3  Additional Reports (6)        ~12 hrs
  2.4  Weekend & Holiday Setup       ~4 hrs
  2.5  Email Config & Templates      ~6 hrs
  2.6  Export Classes (7)            ~4 hrs
  2.7  POS Settings                  ~3 hrs
  2.8  Sidebar Menu Config           ~3 hrs

Phase 3 (Weeks 7-9) — Advanced Features
  3.1  Service Sales Flow            ~2 hrs
  3.2  Customer Area Management      ~4 hrs
  3.3  Offset Due with Advance       ~3 hrs
  3.4  Other Income Tracking         ~4 hrs
  3.5  Activity Log Enhancement      ~2 hrs

Phase 4 (Weeks 10-13) — Logging, Printing & System Admin
  4.1  Laravel System Log Viewer     ~6 hrs
  4.2  Activity Log Enhancement      ~5 hrs
  4.3  Printer IP Management         ~8 hrs
  4.4  Maintenance Mode              ~1 hr
  4.5  Cache Clear UI                ~1 hr
  4.6  Custom Pagination             ~2 hrs
  4.7  SEO Settings                  ~2 hrs

Phase 5 (Deferred)
  5.1  Multi-Language                ~16 hrs
  5.2  Multi-Currency                ~12 hrs
```

---

## Schema Reference Quick Table

| Task | Table Action | Table Name |
|------|-------------|------------|
| 1.1 | CREATE | `customer_groups` |
| 1.1 | ALTER | `customers` (drop customer_group string, add customer_group_id FK) |
| 1.2 | CREATE | `supplier_groups` |
| 1.2 | ALTER | `suppliers` (add supplier_group_id FK) |
| 1.4 | CREATE | `expense_vendors` |
| 1.4 | ALTER | `expenses` (add expense_vendor_id, due_date, paid_amount, due_amount, payment_status) |
| 1.5 | CREATE | `pos_held_carts` |
| 2.1 | ALTER | `expense_categories` (add parent_id, level) |
| 2.2 | CREATE | `purchase_return_types` |
| 2.2 | ALTER | `purchase_returns` (add return_type_id FK) |
| 2.4 | CREATE | `weekend_days` |
| 2.4 | CREATE | `holidays` |
| 2.5 | CREATE | `email_templates` |
| 3.2 | CREATE | `areas` |
| 3.2 | ALTER | `customers` (add area_id FK) |
| 3.4 | CREATE | `other_transactions` |
| 3.5 | — | No schema change (enhance existing `activity_logs` coverage) |
| 4.3 | CREATE | `printers` |

**Total new tables: 10** | **Total altered tables: 5**

---

## Notes

- All new modules follow existing Nwidart Modules pattern
- All money columns: `decimal(15, 2)` — never float
- All status columns: `string(20)` — never enum (BizPOS convention)
- All views extend `core::layouts.master` with dark mode support
- All routes use `middleware('auth')` and named routes
- All business logic in Service classes — thin controllers
- All new CSS uses `bp-` prefix classes with `[data-theme="dark"]` overrides
- Quickshifter uses `float`/`double` for money — BizPOS must NOT replicate this (use decimal)
- Quickshifter uses shared `user_group` for customers+suppliers — BizPOS uses separate tables (cleaner)
- Quickshifter uses `admins` table for staff — BizPOS uses `users` table with roles
