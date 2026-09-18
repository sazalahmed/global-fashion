# BizPOS Pro — Implementation Plan

**Date:** 2026-03-29
**Project:** BizPOS Pro — POS + Accounting + eCommerce + Inventory System
**Stack:** Laravel 12 (Modular via nwidart/laravel-modules), Blade, Bootstrap 5, jQuery, MySQL
**Modules:** 35 modules, 81 models, 60 services, 288 Blade views, 86+ migrations

---

## TABLE OF CONTENTS

1. [Codebase Analysis Summary](#1-codebase-analysis-summary)
2. [Module-by-Module Audit](#2-module-by-module-audit)
3. [Critical Bugs & Integration Gaps](#3-critical-bugs--integration-gaps)
4. [Non-Functional UI Elements](#4-non-functional-ui-elements)
5. [Missing Modules & Features](#5-missing-modules--features)
6. [Route & Controller Audit](#6-route--controller-audit)
7. [Implementation Roadmap](#7-implementation-roadmap)
8. [Priority Matrix](#8-priority-matrix)

---

## 1. Codebase Analysis Summary

### Architecture Overview

| Layer | Implementation | Status |
|-------|---------------|--------|
| Routing | 35 module route files (web + api) + root api.php | Complete |
| Controllers | 55+ controllers across modules | Complete |
| Services | 60 service classes (business logic) | Complete |
| Models | 81 Eloquent models with relationships | Complete |
| FormRequests | 51 validation classes | Complete |
| Views | 288 Blade templates | Complete |
| Migrations | 86+ migration files | Complete |
| API | 3 API controllers (Auth, POS, Base) + API Resources | Partial |
| Middleware | 0 custom middleware | Missing |
| Policies | 0 policy classes (uses Spatie Permission instead) | Adequate |
| Interfaces | 2 contracts (SmsGateway, CourierService) | Minimal |
| PWA | manifest.json, sw.js, sw-pos.js | Implemented |

### Module List (35 Modules)

**Core Business:** Sale, Purchase, PurchaseReturn, SaleReturn, Quotation, Delivery, Installment
**Inventory:** Product, Inventory, Warehouse, Variant, Unit, Brand, Category, Barcode
**People:** Customer, Supplier, Employee
**Finance:** Payment, Expense, Asset, Accounting
**HR:** Attendance, Payroll
**Online:** Ecommerce (storefront + admin)
**Analytics:** Dashboard, Report
**Marketing:** Marketing (email + SMS campaigns)
**System:** Auth, Branch, Core, Setting, Security, Activity

### What's Working Well

- Modular architecture with clean separation of concerns
- Service layer encapsulating business logic with DB::transaction() safety
- Accounting integration (journal entries) auto-created for sales/purchases
- GRN (Goods Received Note) correctly updates warehouse stock
- Dashboard KPIs and charts use real aggregated data
- POS validates stock availability before sale
- Settings module covers 8 configuration sections
- Reports generate real data (not stubs)
- Dark mode fully implemented with CSS variables
- Responsive design across all 288 views
- Consistent UI component system (bp-* classes)
- Blade table component system reduces duplication
- PWA with offline POS capability (IndexedDB queue)
- Spatie Permission with 19 groups and 60+ granular permissions
- 5 courier integrations (Steadfast, Pathao, Paperfly, Ecourier, Redx)
- Fraud detection system for ecommerce orders

---

## 2. Module-by-Module Audit

### 2.1 SALE MODULE

**Pages:** index, create, edit, show, invoice-print, invoice-share (8 views)
**Routes:** CRUD + pdf, email, sms, share (11 endpoints)
**Service:** SaleService (450 lines) — creates sales, items, payments, accounting entries

| Button/Action | Route | Handler | Status |
|--------------|-------|---------|--------|
| Create Sale | sales.create | Link | Working |
| Save Sale | sales.store | Form POST | Working |
| Edit Sale | sales.edit | Link | Working |
| Update Sale | sales.update | Form PUT | Working |
| Delete Sale | sales.destroy | Delete confirm | Working |
| View Sale | sales.show | Link | Working |
| Print Invoice | sales.print | Link | Working |
| Download PDF | sales.pdf | Controller | STUB — renders print view, no actual PDF generation |
| Email Invoice | sales.email | Controller | STUB — `// TODO: Send invoice via Mail` |
| SMS Invoice | sales.sms | Controller | STUB — `// TODO: Send SMS with invoice link` |
| Share Invoice | sales.share | Link | Working (public URL) |
| Export PDF/Excel/CSV | data-export buttons | JS toast | STUB — no backend export endpoint |

**Issues Found:**
1. **CRITICAL: Sales do NOT deduct inventory** — `SaleService::createSale()` creates sale items but never calls `InventoryService::adjustStock()`. Stock quantities remain unchanged after sales.
2. **HIGH: PDF export is stub** — `pdf()` method has TODO comment, no dompdf/snappy installed.
3. **HIGH: Email/SMS invoice not implemented** — Both methods are TODO stubs.
4. **MEDIUM: Export buttons non-functional** — data-export triggers a toast but no backend endpoint exists.

---

### 2.2 POS MODULE

**Pages:** index (POS terminal), receipt, settlement (4 views)
**Routes:** POS terminal, process sale, settlement CRUD
**Services:** POSService, SettlementService

| Button/Action | Route | Handler | Status |
|--------------|-------|---------|--------|
| POS Terminal | pos.index | Link | Working |
| Process Sale | AJAX | POSService | Working (creates sale) |
| Print Receipt | receipt view | window.print() | Working |
| Settlement | settlement routes | CRUD | Working |
| Barcode Scan | JS handler | Input focus | Working |
| Offline Sale | IndexedDB queue | sw-pos.js | Working (syncs on reconnect) |

**Issues Found:**
1. **CRITICAL: POS sales don't deduct inventory** — POS uses `SaleService::createSale()` which has the same inventory gap.
2. **MEDIUM: POS discount at line-item level** — Stock availability is validated but discount handling could be more robust.

---

### 2.3 PURCHASE MODULE

**Pages:** index, create, edit, show, print, receive/GRN (7 views)
**Routes:** CRUD + print + GRN routes
**Services:** PurchaseService, GrnService

| Button/Action | Route | Handler | Status |
|--------------|-------|---------|--------|
| Create PO | purchases.create | Link | Working |
| Save PO | purchases.store | Form POST | Working |
| Edit PO | purchases.edit | Link | Working |
| Receive (GRN) | grn.create | Link | Working |
| Save GRN | grn.store | Form POST | Working — updates warehouse stock |
| Print PO | purchases.print | Link | Working |
| Delete PO | purchases.destroy | Delete confirm | Working |
| Export buttons | data-export | JS toast | STUB |

**Issues Found:**
1. **GRN correctly updates inventory** — Stock incremented on goods receipt.
2. **MEDIUM: Export buttons non-functional** — Same issue as Sales.

---

### 2.4 PURCHASE RETURN MODULE

**Pages:** index, create, edit, show (5 views)
**Routes:** Standard CRUD
**Service:** PurchaseReturnService

**Issues Found:**
1. **HIGH: Purchase returns may not adjust inventory** — Need to verify if stock is decremented when items are returned to supplier.
2. **MEDIUM: No integration with supplier credit/debit notes.**

---

### 2.5 SALE RETURN MODULE

**Pages:** index, create, edit, show (5 views)
**Routes:** Standard CRUD
**Service:** SaleReturnService

**Issues Found:**
1. **HIGH: Sale returns may not adjust inventory** — Need to verify if stock is incremented when items are returned by customer.
2. **Accounting integration exists** — `AccountingIntegrationService::recordSaleReturn()` is implemented.

---

### 2.6 INVENTORY MODULE

**Pages:** index, adjustments (create/show/list), alerts, ledger, stock-ledger, transfers (create/show/list) (8 views)
**Service:** InventoryService with custom exceptions (InsufficientStockException, InvalidTransferException)
**Models:** WarehouseStock, StockAdjustment, StockLedger, StockTransfer (6 models)

| Feature | Status |
|---------|--------|
| Stock overview | Working |
| Manual stock adjustments | Working |
| Stock transfers between warehouses | Working |
| Stock ledger/history | Working |
| Low stock alerts | Working |
| Auto-deduction on sale | NOT IMPLEMENTED |
| Auto-increment on sale return | NOT VERIFIED |

**Issues Found:**
1. **CRITICAL: No auto-deduction on sale** — InventoryService has `adjustStock()` method but it's never called from SaleService.
2. **MEDIUM: Stock alerts may not trigger notifications** — Alert threshold exists but no notification system to send alerts.

---

### 2.7 PRODUCT MODULE

**Pages:** index, create, edit, show (5 views)
**Service:** ProductService (340 lines) — SKU generation, image management, tag syncing
**Models:** Product, ProductImage, Tag, ProductTag

| Button/Action | Status |
|--------------|--------|
| Create Product | Working |
| Edit Product | Working |
| Delete Product | Working |
| View Product | Working |
| Image Upload | Working |
| SKU Auto-generate | Working |
| Bulk Actions | UI present, needs verification |
| Export | STUB |

**Issues Found:**
1. **LOW: Bulk delete/export** — Bulk action bar appears when checkboxes selected, but backend endpoint needs verification.

---

### 2.8 ACCOUNTING MODULE

**Pages:** 26 views — Chart of Accounts, Journal Entries, General Ledger, Trial Balance, P&L, Balance Sheet, Cash Flow, Bank Reconciliation, Credit Notes, Debit Notes, Receipts
**Services:** 8 services — comprehensive accounting suite
**Models:** Account, JournalEntry, JournalEntryLine, CreditNote, DebitNote, PaymentReceipt, BankReconciliation, etc.

| Feature | Status |
|---------|--------|
| Chart of Accounts (CRUD) | Working |
| Journal Entries (CRUD) | Working |
| Auto journal entries on sale/purchase | Working |
| General Ledger report | Working |
| Trial Balance | Working |
| Profit & Loss | Working |
| Balance Sheet | Working |
| Cash Flow Statement | Working |
| Bank Reconciliation | Working |
| Credit Notes (CRUD + print) | Working |
| Debit Notes (CRUD + print) | Working |
| Payment Receipts | Working |

**Issues Found:**
1. **LOW: Print views** — Print functionality uses browser print, not PDF generation.
2. **MEDIUM: No fiscal year management** — Reports don't have fiscal year selection.

---

### 2.9 ECOMMERCE MODULE

**Pages:** 37 views — largest module with admin + storefront
**Admin:** Orders, Product Sync, Shipping, Coupons, Banners, Homepage Builder, Collections, Flash Deals, Blog, Settings
**Storefront:** Home, Shop, Category, Cart, Checkout, Blog, Flash Deals
**Services:** EcommerceService, StorefrontService, ContentManagementService, FraudCheckService + 5 courier services

| Feature | Status |
|---------|--------|
| Product catalog/shop | Working |
| Shopping cart | Working |
| Checkout flow | Partial — order creation works |
| Payment gateway (bKash/SSLCommerz) | CONFIGURED BUT NOT INTEGRATED |
| COD orders | Working |
| Order management | Working |
| Coupon/discount system | Working |
| Shipping zones | Working |
| 5 courier integrations | Working |
| Fraud detection | Working |
| Banner management | Working |
| Homepage builder | Working |
| Flash deals | Working |
| Blog | Working |
| Collections | Working |
| Customer account/login | NOT IMPLEMENTED |
| Order tracking (customer-facing) | NOT IMPLEMENTED |
| Product reviews/ratings | NOT IMPLEMENTED |
| Wishlist | Controller exists, needs verification |

**Issues Found:**
1. **HIGH: Payment gateway not processing** — bKash and SSLCommerz settings are captured in admin but `CheckoutController::process()` only validates payment method without actual gateway API calls.
2. **HIGH: No customer-facing account** — Storefront has no login/register for customers to track orders.
3. **MEDIUM: Order status workflow** — Orders created as 'unpaid', but status transition workflow unclear.

---

### 2.10 CUSTOMER MODULE

**Pages:** index, create, edit, show (with tabs: Sales History, Payments, Ledger, Advance, Loyalty), advances, due-receive, ledger (8 views)
**Service:** CustomerService

| Feature | Status |
|---------|--------|
| Customer CRUD | Working |
| Customer ledger | Working |
| Due collection | Working |
| Advance management | Working |
| Loyalty program | UI present, needs verification |
| Customer groups | Working |
| Credit limit/terms | Working |

---

### 2.11 SUPPLIER MODULE

**Pages:** index, create, show, ledger, print (5 views)
**Services:** SupplierService, SupplierPaymentService, SupplierLedgerService

| Feature | Status |
|---------|--------|
| Supplier CRUD | Working |
| Supplier payments | Working |
| Supplier ledger | Working |
| Print supplier statement | Working |

**Issues Found:**
1. **LOW: No supplier edit page** — Only create and show views found. Edit may be missing.

---

### 2.12 PAYMENT MODULE

**Pages:** index, create, edit, show + Payment Accounts (banks, create, edit, transfers) (8 views)
**Service:** PaymentService — allocation with journal entry integration

| Feature | Status |
|---------|--------|
| Payment CRUD | Working |
| Payment accounts | Working |
| Bank transfers | Working |
| Payment allocation to invoices | Working |
| Journal entry on payment | Working |

---

### 2.13 EXPENSE MODULE

**Pages:** index, create, edit, show, ledger (6 views)
**Service:** ExpenseService

| Feature | Status |
|---------|--------|
| Expense CRUD | Working |
| Expense categories | Working |
| Expense ledger | Working |
| Recurring expenses | NOT IMPLEMENTED |

---

### 2.14 REPORT MODULE

**Pages:** 9 views — index, sales, purchase, inventory, financial, tax, staff, customer, custom builder
**Services:** 7 report services (Sales, Purchase, Inventory, Financial, Tax, Staff, Customer)

| Report | Status |
|--------|--------|
| Sales report | Working (real data) |
| Purchase report | Working (real data) |
| Inventory report | Working (real data) |
| Financial report | Working (real data) |
| Tax report | Working (real data) |
| Staff report | Working (real data) |
| Customer report | Working (real data) |
| Custom report builder | UI present, needs verification |
| PDF/Excel export | NOT IMPLEMENTED (no library) |

---

### 2.15 MARKETING MODULE

**Pages:** 6 views — SMS campaigns, email campaigns, loyalty, index
**Services:** MarketingService
**Jobs:** SendEmailCampaignJob, SendSmsCampaignJob
**Gateways:** BulkSmsBdGateway, SslWirelessGateway (implements SmsGatewayInterface)

| Feature | Status |
|---------|--------|
| SMS campaigns | Working (queued) |
| Email campaigns | Working (queued) |
| Loyalty program | UI present, needs verification |
| Campaign analytics | Needs verification |

---

### 2.16 HR MODULES (Employee, Attendance, Payroll)

| Feature | Status |
|---------|--------|
| Employee CRUD | Working |
| Attendance tracking | Working |
| Leave management | Working |
| Salary structures | Working |
| Payroll generation | Working |
| Payslip printing | Needs verification |

---

### 2.17 SECURITY MODULE

**Pages:** 9 views — Users, Roles, API Keys, Backup, index
**Service:** SecurityService

| Feature | Status |
|---------|--------|
| User CRUD | Working |
| Role management | Working |
| Permission assignment | Working |
| API key management | Working |
| Backup | Needs verification |
| Audit log | Separate Activity module |

---

### 2.18 OTHER MODULES

| Module | Pages | Status |
|--------|-------|--------|
| Branch | 4 views | Working |
| Brand | 4 views | Working |
| Category | 4 views | Working |
| Unit | 4 views | Working |
| Variant | 5 views | Working |
| Warehouse | 5 views | Working |
| Asset | 4 views | Working |
| Barcode | 3 views | Working |
| Quotation | 5 views | Working |
| Delivery | 5 views | Working |
| Installment | 4 views | Working |
| Activity | 3 views | Working |
| Dashboard | 2 views | Working (real data) |
| Setting | 2 views | Working (8 sections) |

---

## 3. Critical Bugs & Integration Gaps

### CRITICAL (Must Fix — Data Integrity)

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| C1 | **Sales do NOT deduct inventory** | `SaleService::createSale()` | Stock quantities never decrease on sale — inventory accuracy completely broken |
| C2 | **POS sales do NOT deduct inventory** | `POSService::processSale()` → `SaleService` | Same root cause as C1 — POS inherits the gap |
| C3 | **Sale returns may not increment inventory** | `SaleReturnService` | Returned items may not be added back to stock |
| C4 | **Purchase returns may not decrement inventory** | `PurchaseReturnService` | Returned-to-supplier items may not be removed from stock |

### HIGH (Important — Broken Features)

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| H1 | **PDF export not implemented** | `SaleController::pdf()` + all modules | Export buttons show toast but generate nothing — no dompdf/snappy installed |
| H2 | **Excel/CSV export not implemented** | All index pages with data-export | No maatwebsite/excel or similar library |
| H3 | **Payment gateway not processing** | `CheckoutController::process()` | bKash/SSLCommerz settings saved but no API calls — only COD works |
| H4 | **Email invoice not implemented** | `SaleController::email()` | TODO stub — customers can't receive invoices by email |
| H5 | **SMS invoice not implemented** | `SaleController::sms()` | TODO stub |
| H6 | **Notification system has no backend** | Header dropdown + no model/table | Bell icon shows "0" with empty container — no notification logic exists |

### MEDIUM (Should Fix — Incomplete Features)

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| M1 | **No customer-facing storefront account** | Ecommerce storefront | Customers can't log in, view orders, or track deliveries |
| M2 | **No customer order tracking** | Ecommerce storefront | No way for customers to check order status |
| M3 | **Low stock alerts don't trigger notifications** | Inventory module | Alert thresholds exist but no system to send alerts |
| M4 | **No fiscal year management** | Accounting reports | Reports can't be filtered by fiscal year |
| M5 | **Supplier edit page missing** | Supplier module | May only have create + show |
| M6 | **FormRequest authorize() always returns true** | All 51 FormRequest classes | Permission checking happens elsewhere but FormRequests don't enforce it |
| M7 | **No custom middleware** | App-wide | SecurityHeaders middleware recommended in CLAUDE.md not implemented |
| M8 | **Bulk actions unverified** | Product, Sale, Customer index pages | Bulk action bar appears but backend endpoints need verification |

---

## 4. Non-Functional UI Elements

### Buttons That Don't Work

| Element | Location | Issue |
|---------|----------|-------|
| Export PDF button | Sale, Purchase, Report index pages | `data-export="pdf"` triggers JS toast, no backend |
| Export Excel button | Same | `data-export="excel"` — same issue |
| Export CSV button | Same | `data-export="csv"` — same issue |
| Email Invoice button | Sale show page | Routes to TODO stub |
| SMS Invoice button | Sale show page | Routes to TODO stub |
| "Mark all read" link | Header notification dropdown | Has ID but no JS handler |
| "View All Notifications" link | Header notification dropdown | `href="#"` — no notification page exists |
| "Track on Pathao" link | Delivery show page | `href="#"` — no tracking integration |

### Partially Functional

| Element | Location | Issue |
|---------|----------|-------|
| Notification bell | Header | Shows "0" count, dropdown is empty template |
| Global search (Ctrl+K) | Header | Keyboard shortcut wired but search functionality unclear |
| Loyalty program tab | Customer show page | Tab exists but loyalty logic needs verification |
| Custom report builder | Report module | UI present but builder logic needs verification |

---

## 5. Missing Modules & Features

### Missing for a Complete ECommerce + POS + Inventory + Accounting System

#### HIGH PRIORITY — Expected in Any Production System

| # | Feature | Description | Estimated Effort |
|---|---------|-------------|-----------------|
| F1 | **Inventory auto-adjustment on sale** | Hook SaleService to InventoryService | 1-2 days |
| F2 | **PDF generation library** | Install dompdf, create PDF templates for invoices, reports, receipts | 3-5 days |
| F3 | **Excel/CSV export** | Install maatwebsite/excel, add export for all list pages | 3-5 days |
| F4 | **Payment gateway integration** | Implement bKash, SSLCommerz, Nagad API calls in checkout | 5-7 days |
| F5 | **Notification system** | Model, migration, event-driven notifications, real-time bell updates | 3-5 days |
| F6 | **SecurityHeaders middleware** | X-Frame-Options, CSP, X-Content-Type-Options, etc. | 0.5 days |
| F7 | **Email invoice/receipt** | Mail class, template, queue for sending | 1-2 days |
| F8 | **Customer storefront account** | Login, register, order history, profile for ecommerce customers | 5-7 days |

#### MEDIUM PRIORITY — Expected in a Mature System

| # | Feature | Description | Estimated Effort |
|---|---------|-------------|-----------------|
| F9 | **Product reviews & ratings** | Model, migration, storefront display, admin moderation | 3-5 days |
| F10 | **Order tracking (customer-facing)** | Tracking page, courier API integration for live status | 3-5 days |
| F11 | **Multi-currency support** | Currency model, exchange rates, conversion in transactions | 5-7 days |
| F12 | **Discount/Coupon engine (POS)** | Apply coupons at POS terminal (currently ecommerce only) | 2-3 days |
| F13 | **Recurring expenses** | Schedule, auto-create expense entries | 2-3 days |
| F14 | **Fiscal year management** | Model, accounting period close, report filtering | 2-3 days |
| F15 | **Barcode printing (thermal)** | Print barcode labels to thermal printer | 1-2 days |
| F16 | **Audit trail enhancement** | Log all CRUD operations with before/after data | 2-3 days |
| F17 | **Webhook system** | Outgoing webhooks for order, sale, inventory events | 3-5 days |
| F18 | **Batch/expiry tracking** | Track product batches and expiry dates (perishables) | 3-5 days |

#### LOW PRIORITY — Nice to Have

| # | Feature | Description | Estimated Effort |
|---|---------|-------------|-----------------|
| F19 | **Multi-language (i18n)** | Laravel lang files, language switcher | 5-7 days |
| F20 | **Customer loyalty points redemption** | Points earn/burn at POS and checkout | 3-5 days |
| F21 | **Advanced POS features** | Split payment, hold/recall, kitchen display | 5-7 days |
| F22 | **BOM (Bill of Materials)** | Manufacturing/assembly support | 5-7 days |
| F23 | **Scheduled reports** | Auto-generate and email reports on schedule | 2-3 days |
| F24 | **Import data (CSV/Excel)** | Bulk import products, customers, suppliers | 3-5 days |
| F25 | **GDPR compliance** | Data export, right to delete, consent management | 3-5 days |
| F26 | **GraphQL API** | Alternative to REST for mobile app | 5-7 days |

---

## 6. Route & Controller Audit

### Route Summary by Module

| Module | Web Routes | API Routes | Controllers | Issues |
|--------|-----------|------------|-------------|--------|
| Sale | 11 | 1 (via POS) | SaleController | pdf/email/sms are stubs |
| Purchase | 8+ GRN | 0 | PurchaseController, GrnController | None |
| PurchaseReturn | 5 | 0 | PurchaseReturnController | None |
| SaleReturn | 5 | 0 | SaleReturnController | None |
| Quotation | 5 | 0 | QuotationController | None |
| Product | 7 | 0 | ProductController | None |
| Inventory | 10+ | 0 | InventoryController | None |
| Customer | 8+ | 0 | CustomerController | None |
| Supplier | 6+ | 0 | SupplierController, SupplierPaymentController | Edit route may be missing |
| Payment | 8+ | 0 | PaymentController, PaymentAccountController | None |
| Expense | 6 | 0 | ExpenseController | None |
| Accounting | 20+ | 0 | 7 controllers | None |
| POS | 4+ | 8 | POSController, SettlementController, Api\PosController | None |
| Ecommerce | 15+ admin, 10+ storefront | 0 | EcommerceController, ContentController, 7 Storefront controllers | Payment gateway stub |
| Dashboard | 2 | 1 | DashboardController | None |
| Report | 9 | 0 | ReportController | Export not working |
| Marketing | 6 | 0 | MarketingController | None |
| Security | 9 | 0 | SecurityController, UserController | None |
| Setting | 2 | 0 | SettingController | None |
| Branch | 4 | 0 | BranchController | None |
| Employee | 5 | 0 | EmployeeController | None |
| Attendance | 6 | 0 | AttendanceController | None |
| Payroll | 5 | 0 | PayrollController | None |
| Auth | 5 | 5 (api/v1/auth) | AuthController (web + api) | None |

### API Routes (routes/api.php)

| Endpoint | Method | Controller | Status |
|----------|--------|-----------|--------|
| `/api/v1/auth/login` | POST | Api\V1\AuthController | Working |
| `/api/v1/auth/profile` | GET | Api\V1\AuthController | Working |
| `/api/v1/auth/profile` | PUT | Api\V1\AuthController | Working |
| `/api/v1/auth/change-password` | POST | Api\V1\AuthController | Working |
| `/api/v1/auth/logout` | POST | Api\V1\AuthController | Working |
| `/api/v1/pos/init` | GET | Api\V1\PosController | Working |
| `/api/v1/pos/search-products` | GET | Api\V1\PosController | Working |
| `/api/v1/pos/barcode` | GET | Api\V1\PosController | Working |
| `/api/v1/pos/process-sale` | POST | Api\V1\PosController | Working |
| `/api/v1/pos/search-customers` | GET | Api\V1\PosController | Working |
| `/api/v1/pos/customer-advance` | GET | Api\V1\PosController | Working |
| `/api/v1/pos/quick-add-customer` | POST | Api\V1\PosController | Working |
| `/api/v1/pos/receipt/{sale}` | GET | Api\V1\PosController | Working |

### Routes Without UI

No orphan routes found — all routes map to views.

### UI Without Routes

| Element | Expected Route | Status |
|---------|---------------|--------|
| Export buttons | export endpoint per module | Missing — no export controller methods |
| Notification "View All" | notifications.index | Missing — no notification module |
| Mark all read | notifications.markAllRead | Missing |

---

## 7. Implementation Roadmap

### Phase 1 — Critical Fixes (Week 1-2)

**Goal:** Fix data integrity issues and core broken functionality.

#### Sprint 1.1: Inventory Integration (Days 1-3)

- [ ] **C1/C2: Wire SaleService to InventoryService for stock deduction**
  - In `SaleService::createSale()`, after creating sale items, call `InventoryService::adjustStock()` for each item
  - Type: 'sale_deduction', reference: sale ID
  - Wrap in existing DB::transaction()
  - Handle `InsufficientStockException` gracefully

- [ ] **C3: Wire SaleReturnService to InventoryService for stock increment**
  - On sale return creation, call `InventoryService::adjustStock()` with positive quantity
  - Type: 'sale_return'

- [ ] **C4: Wire PurchaseReturnService to InventoryService for stock decrement**
  - On purchase return creation, call `InventoryService::adjustStock()` with negative quantity
  - Type: 'purchase_return'

- [ ] **Test all inventory flows end-to-end**
  - Sale → stock decreases
  - Sale return → stock increases
  - Purchase GRN → stock increases (already working)
  - Purchase return → stock decreases

#### Sprint 1.2: SecurityHeaders Middleware (Day 4)

- [ ] **F6: Create `App\Http\Middleware\SecurityHeaders`**
  - X-Content-Type-Options: nosniff
  - X-Frame-Options: DENY
  - X-XSS-Protection: 1; mode=block
  - Referrer-Policy: strict-origin-when-cross-origin
  - Permissions-Policy: camera=(), microphone=(), geolocation=()
  - Content-Security-Policy (appropriate for the app)
  - Register in `bootstrap/app.php`

---

### Phase 2 — Export & Communication (Week 2-3)

**Goal:** Make export buttons and invoice communication functional.

#### Sprint 2.1: PDF Generation (Days 5-8)

- [ ] **F2: Install dompdf** — `composer require barryvdh/laravel-dompdf`
- [ ] **Create PDF templates** for:
  - Sale invoice (from invoice-print view)
  - Purchase order
  - Quotation
  - Credit note / Debit note
  - Payment receipt
  - Delivery note
- [ ] **Update SaleController::pdf()** to generate real PDF using dompdf
- [ ] **Add pdf() method** to PurchaseController, QuotationController, etc.

#### Sprint 2.2: Excel/CSV Export (Days 9-11)

- [ ] **F3: Install maatwebsite/excel** — `composer require maatwebsite/excel`
- [ ] **Create Export classes** for:
  - SalesExport (index page)
  - PurchasesExport
  - ProductsExport
  - CustomersExport
  - SuppliersExport
  - ExpensesExport
  - PaymentsExport
  - Report exports
- [ ] **Add export routes** to each module
- [ ] **Wire data-export buttons** to actual endpoints (update JS handler in app.js)

#### Sprint 2.3: Email/SMS Invoice (Days 12-13)

- [ ] **F7: Create InvoiceMail class** with Blade template
- [ ] **Implement SaleController::email()** — queue email with PDF attachment
- [ ] **Implement SaleController::sms()** — send via configured SMS gateway
- [ ] **Add similar functionality** for quotations and credit notes

---

### Phase 3 — Notification System (Week 3-4)

**Goal:** Build a working notification system.

#### Sprint 3.1: Notification Backend (Days 14-17)

- [ ] **F5: Create Notification module** or use Laravel's built-in notification system
  - Migration: `notifications` table (or use Laravel's default)
  - Events: `LowStockAlert`, `NewOrder`, `PaymentReceived`, `SaleCreated`
  - Listeners: Create notification records
  - API endpoint: `GET /notifications` (paginated)
  - API endpoint: `POST /notifications/mark-read`
  - API endpoint: `POST /notifications/mark-all-read`
- [ ] **Wire header notification dropdown** to real data
  - AJAX call to fetch unread count
  - AJAX call to fetch recent notifications
  - Wire "Mark all read" button
  - Wire "View All Notifications" link to notifications index page

#### Sprint 3.2: Low Stock Alert Notifications (Day 18)

- [ ] **M3: Trigger low stock notifications** when stock falls below threshold after sale
- [ ] **Add notification on new ecommerce order**
- [ ] **Add notification on payment received**

---

### Phase 4 — Ecommerce Completion (Week 4-6)

**Goal:** Complete the ecommerce storefront.

#### Sprint 4.1: Payment Gateway Integration (Days 19-23)

- [ ] **F4: Implement bKash payment gateway**
  - API integration in CheckoutController
  - Callback/webhook handler for payment confirmation
  - Order status update on payment success/failure

- [ ] **Implement SSLCommerz payment gateway**
  - API integration
  - IPN (Instant Payment Notification) handler
  - Session/transaction management

- [ ] **Implement Nagad payment gateway** (if needed)

#### Sprint 4.2: Customer Storefront Account (Days 24-28)

- [ ] **F8: Customer authentication system**
  - Separate `customer` guard in auth.php
  - Login/Register pages for storefront
  - Customer dashboard (order history, profile)
  - Password reset flow

- [ ] **F10: Order tracking page**
  - Public tracking by order number
  - Authenticated tracking with full history
  - Courier API integration for live tracking status

#### Sprint 4.3: Product Reviews (Days 29-31)

- [ ] **F9: Reviews & ratings system**
  - Migration: `product_reviews` table
  - Model with relationships to Product and Customer
  - Storefront display on product page
  - Admin moderation view
  - Average rating calculation

---

### Phase 5 — Accounting & Reporting Enhancement (Week 6-7)

**Goal:** Enhance accounting and reporting capabilities.

#### Sprint 5.1: Fiscal Year Management (Days 32-34)

- [ ] **F14: Fiscal year model and management**
  - Migration: `fiscal_years` table (start_date, end_date, status, is_closed)
  - Accounting reports filtered by fiscal year
  - Period close functionality
  - Opening balance entries

#### Sprint 5.2: Report Export (Days 35-37)

- [ ] **Export all reports to PDF and Excel**
  - Sales report PDF/Excel
  - Financial report PDF/Excel
  - Inventory report PDF/Excel
  - Tax report PDF/Excel
  - Use dompdf + maatwebsite/excel from Phase 2

---

### Phase 6 — API Expansion for Mobile (Week 7-8)

**Goal:** Expand REST API for mobile app support beyond POS.

#### Sprint 6.1: Core API Endpoints (Days 38-42)

- [ ] **Expand API to cover all modules**
  - `apiResource` routes for: products, customers, suppliers, sales, purchases, inventory
  - API Resources for all models
  - Shared validation via FormRequests
  - Pagination and filtering

#### Sprint 6.2: Mobile-Specific Endpoints (Days 43-45)

- [ ] **Dashboard API** — KPIs, charts data
- [ ] **Report API** — summary reports for mobile
- [ ] **Notification API** — push notification integration

---

### Phase 7 — Polish & Enhancement (Week 8-10)

**Goal:** Quality-of-life improvements.

- [ ] F12: Discount/coupon at POS
- [ ] F13: Recurring expenses
- [ ] F15: Barcode label printing
- [ ] F16: Audit trail enhancement
- [ ] F20: Loyalty points redemption
- [ ] F24: CSV/Excel data import
- [ ] M5: Supplier edit page
- [ ] M6: Add permission checks in FormRequest authorize()
- [ ] M8: Verify and fix bulk actions
- [ ] Global search implementation (Ctrl+K)

---

## 8. Priority Matrix

```
                    IMPACT
              HIGH          LOW
         ┌──────────┬──────────┐
    HIGH │ C1,C2,C3 │ M7,M8    │
         │ C4,H1,H2 │          │
URGENCY  │ H3,F6    │          │
         ├──────────┼──────────┤
    LOW  │ F5,F7,F8 │ F9,F12   │
         │ F10,F14  │ F15,F19  │
         │ H4,H5,H6 │ F20,F21  │
         └──────────┴──────────┘
```

### Do First (Critical Path)
1. **C1/C2** — Sales inventory deduction (breaks all stock accuracy)
2. **C3/C4** — Return inventory adjustments
3. **F6** — SecurityHeaders middleware (security baseline)
4. **H1/H2** — PDF + Excel export (most requested feature)

### Do Next (High Value)
5. **H3** — Payment gateway (ecommerce revenue enabler)
6. **F5/H6** — Notification system (UX completeness)
7. **F7/H4/H5** — Email/SMS invoice
8. **F8** — Customer storefront account

### Do Later (Important but Not Urgent)
9. **F10** — Order tracking
10. **F14** — Fiscal year management
11. **F9** — Product reviews
12. **API expansion** for mobile

### Backlog
- F12, F13, F15, F16, F19, F20, F21, F22, F23, F24, F25, F26

---

## Appendix A: Technology Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| PDF Library | barryvdh/laravel-dompdf | Laravel integration, no external binary needed |
| Excel Library | maatwebsite/excel | Industry standard for Laravel, supports export + import |
| Payment: bKash | karim007/laravel-bkash or custom | BD-specific, well-documented API |
| Payment: SSLCommerz | sslcommerz/laravel | Official package available |
| Notifications | Laravel built-in | `php artisan notifications:table` — no extra package needed |

## Appendix B: Files to Modify for Critical Fix C1/C2

```
Modules/Sale/app/Services/SaleService.php
  → Add: use Modules\Inventory\app\Services\InventoryService;
  → In createSale(): after creating sale_items, loop and call adjustStock()
  → In updateSale(): reverse old adjustments, apply new ones

Modules/SaleReturn/app/Services/SaleReturnService.php
  → Add inventory increment on return creation

Modules/PurchaseReturn/app/Services/PurchaseReturnService.php
  → Add inventory decrement on return creation
```

## Appendix C: Database Tables Count by Module

| Module | Tables |
|--------|--------|
| Core (User, settings) | 5 |
| Product | 4 (products, product_images, tags, product_tag) |
| Inventory | 6 (warehouse_stocks, stock_adjustments, stock_ledger, stock_transfers, +items) |
| Sale | 2 (sales, sale_items) |
| Purchase | 4 (purchases, purchase_items, grns, grn_items) |
| Returns | 4 (sale_returns, sale_return_items, purchase_returns, purchase_return_items) |
| Accounting | 10+ (accounts, journal_entries, journal_entry_lines, credit_notes, debit_notes, etc.) |
| Ecommerce | 13 (orders, order_items, coupons, shipping_zones, banners, blog_posts, etc.) |
| Payment | 4 (payments, payment_accounts, payment_allocations, bank_transfers) |
| Payroll | 4 (salary_structures, salary_components, payrolls, payroll_items) |
| Marketing | 3 (email_campaigns, sms_campaigns, marketing_settings) |
| Others | ~20 (branches, categories, brands, units, variants, customers, suppliers, etc.) |
| **TOTAL** | **86+** |
