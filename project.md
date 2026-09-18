# BizPOS Pro - Project Details

## Overview

**BizPOS Pro** is a comprehensive POS (Point of Sale) + Accounting + eCommerce system built for the Bangladesh market. It targets retail businesses including super shops, mobile shops, fashion outlets, electronics stores, and general SMBs.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL 8.0+ |
| Frontend | Blade Templates + Bootstrap 5.3.3 + jQuery 3.7.1 |
| Icons | FontAwesome 6.5.1 |
| Charts | Chart.js 4.4.0 |
| Font | Nunito Sans (local) |
| Mobile App | React Native 0.84 + TypeScript (`../bizpos-mobile/`) |
| API Auth | Laravel Sanctum (token-based) |
| Architecture | Modular (nwidart/laravel-modules v12) |
| PWA | Service Workers for offline POS |

---

## Architecture

### Modular Design

The project uses `nwidart/laravel-modules` to organize code into 35 self-contained modules inside the `Modules/` directory. Each module has its own:

- Controllers, Models, Services
- Form Requests (validation)
- Routes (web + API)
- Blade Views
- Migrations, Seeders, Factories
- Tests

### SOLID + DRY

- **Controllers** are thin - they delegate to Service classes
- **Services** hold all business logic
- **Form Requests** handle validation
- **Models** define relationships, scopes, and accessors only
- No code duplication - shared behavior uses Traits, Helpers, and Blade Components

### Request Flow

```
Route -> Controller -> FormRequest (validate) -> Service (logic) -> Model (DB) -> Blade (render)
```

For API:
```
API Route -> ApiController -> FormRequest -> Service -> Model -> API Resource -> JSON
```

---

## Modules (35 Total)

| # | Module | Purpose |
|---|---|---|
| 1 | **Accounting** | Chart of accounts, journal entries, financial reports, bank reconciliation, credit/debit notes |
| 2 | **Activity** | Activity logging for audit trail |
| 3 | **Asset** | Fixed asset management with categories and maintenance tracking |
| 4 | **Attendance** | Employee attendance, leaves, holidays, weekend configuration |
| 5 | **Auth** | Authentication - login, registration, password reset |
| 6 | **Barcode** | Barcode generation and printing |
| 7 | **Branch** | Multi-branch management |
| 8 | **Brand** | Product brand management |
| 9 | **Category** | Product category hierarchy |
| 10 | **Core** | Shared layouts, partials, components, base infrastructure |
| 11 | **Customer** | Customer CRUD, groups, areas, ledger, due management, advances |
| 12 | **Dashboard** | Main dashboard with stats, charts, and analytics |
| 13 | **Delivery** | Delivery challans with status tracking and printing |
| 14 | **Ecommerce** | Online store - orders, coupons, shipping, banners, blog, flash deals |
| 15 | **Employee** | Employee management |
| 16 | **Expense** | Expenses, categories, vendors, recurring expenses, approvals |
| 17 | **Installment** | Installment plans and payment schedules |
| 18 | **Inventory** | Stock overview, adjustments, transfers, reconciliation, alerts |
| 19 | **Marketing** | Email campaigns, SMS campaigns, loyalty programs |
| 20 | **Payment** | Payment processing, accounts, bank management, balance transfers |
| 21 | **Payroll** | Salary structures, payroll processing |
| 22 | **POS** | Point of Sale terminal - product search, barcode scan, cart, checkout, receipt |
| 23 | **Product** | Product CRUD, SKU/barcode generation, images, tags, bulk operations |
| 24 | **Purchase** | Purchase orders, goods receive notes (GRN), approvals |
| 25 | **PurchaseReturn** | Purchase returns with return types |
| 26 | **Quotation** | Sales quotations with line items |
| 27 | **Report** | Sales, purchase, inventory, financial, tax, staff, customer, DTS reports |
| 28 | **Sale** | Sales management - create, print, PDF, email, SMS share |
| 29 | **SaleReturn** | Sales returns processing |
| 30 | **Security** | User management, API keys, backups |
| 31 | **Setting** | System settings, email config, printers, webhooks, sidebar config, logs |
| 32 | **Supplier** | Supplier CRUD, groups, payments |
| 33 | **Unit** | Product measurement units |
| 34 | **Variant** | Product variants with attributes and values |
| 35 | **Warehouse** | Warehouse and location management |

---

## Key Features

### POS Terminal
- Real-time product search and barcode scanning
- Customer selection with advance balance support
- Multiple payment methods (Cash, bKash, Nagad, Rocket, Card, Bank Transfer)
- Receipt generation and printing
- Offline support via PWA service worker

### Accounting
- Double-entry bookkeeping with chart of accounts
- Journal entries with posting and voiding
- Financial reports: General Ledger, Trial Balance, Profit & Loss, Balance Sheet, Cash Flow
- Bank reconciliation
- Credit and debit notes

### eCommerce
- Online storefront with banners, collections, and flash deals
- Order management with status tracking
- Coupon system
- Shipping zones and courier provider integration (Pathao, Steadfast, eCourier, Redx, Paperfly)
- Blog/content management
- Fraud checking for courier orders

### Inventory
- Multi-warehouse stock management
- Stock adjustments with approval workflow
- Inter-warehouse transfers
- Stock reconciliation
- Low stock alerts and notifications

### Reports
- DTS (Daily Transaction Summary)
- Sales reports (by product, category, customer, date range)
- Purchase reports
- Inventory reports
- Financial reports
- Tax/VAT reports (NBR Bangladesh compliant)
- Staff performance reports
- Receivables aging

---

## Bangladesh-Specific Features

| Feature | Detail |
|---|---|
| Currency | BDT (Bangladeshi Taka) - format: `BDT 1,23,456` (lakh system) |
| Date format | DD MMM YYYY |
| VAT | 15% standard (NBR) |
| Mushak forms | 6.3 (Invoice), 6.5 (Credit Note), 9.1 (Monthly Return) |
| Payment methods | Cash, bKash, Nagad, Rocket, Card, Bank Transfer |
| Couriers | Pathao, Steadfast, eCourier, Redx, Paperfly, Sundarban, SA Paribahan |
| SMS gateways | SSL Wireless, BulkSMSBD |
| Banks | DBBL, BRAC Bank, Islami Bank, City Bank |

---

## API

The REST API serves the React Native mobile app at `../bizpos-mobile/`.

- **Base path:** `/api/v1/`
- **Auth:** Laravel Sanctum (Bearer token)
- **Endpoints:** Auth, POS, Products, Customers, Sales, Dashboard, Notifications
- **Response format:** `{ success, message, data, meta }`

---

## PWA Support

BizPOS Pro is a Progressive Web App:

- Installable on mobile devices via browser
- Offline POS via service worker (`sw-pos.js`) with IndexedDB queue
- Push notifications for low-stock alerts and order updates
- App shortcuts for POS Terminal and Dashboard

---

## Design System

- **Color scheme:** Custom CSS variables with `--bp-` prefix
- **Primary:** `#1B4F72` | **Secondary:** `#117A65` | **Accent:** `#D4AC0D`
- **CSS classes:** `bp-` prefixed (bp-card, bp-btn, bp-table, bp-badge, etc.)
- **Dark mode:** Full support via `[data-theme="dark"]`
- **Responsive:** 768px, 992px, 1200px breakpoints
- **No CDN:** All vendor libraries served locally from `public/vendor/`
