# BizPOS Pro - System Flow Documentation

## 1. Authentication Flow

```
User visits any page
  -> Middleware checks auth
    -> Not logged in: Redirect to /login
    -> Logged in: Proceed to requested page

Login Flow:
  User submits email + password
    -> AuthController@login
      -> Validates credentials (throttled: max 5 attempts/15 min)
      -> Auth::attempt() verifies bcrypt hash
      -> Session regenerated on success
      -> Redirect to Dashboard

API Login (Mobile):
  POST /api/v1/auth/login { email, password }
    -> Returns Sanctum Bearer token
    -> Mobile stores token in encrypted storage
    -> All subsequent requests include Authorization header
```

---

## 2. POS Sale Flow

```
Cashier opens POS Terminal (/pos)
  -> POSController@index loads POS interface
  -> POS initializes: loads categories, payment methods, settings

Product Selection:
  Search by name -> AJAX /pos/search-products -> returns matching products
  Scan barcode -> AJAX /pos/barcode/{code} -> returns product
  Browse category -> filtered product list
  -> Product added to cart (client-side)
  -> Quantity, discount adjustable per item

Customer Selection (optional):
  Search customer -> AJAX /pos/search-customers
  Quick add customer -> AJAX POST /pos/quick-add-customer
  -> Customer advance balance shown if applicable

Checkout:
  Cashier clicks "Process Sale"
    -> POST /pos/process-sale
      -> POSService@processSale
        -> DB::transaction begins
          -> Create Sale record
          -> Create SaleItem records (per cart item)
          -> Update stock (decrement product quantities)
          -> Create Payment record(s)
          -> Apply customer advance if used
          -> Update customer balance/due
          -> Generate invoice number
        -> DB::transaction commits
      -> Return sale data + receipt
    -> Receipt displayed for print

Offline POS (PWA):
  No internet detected
    -> sw-pos.js service worker intercepts
    -> Sale queued in IndexedDB
    -> When online: queued sales synced to server
```

---

## 3. Standard Sale Flow (Non-POS)

```
User navigates to Sales > Create Sale
  -> SaleController@create
    -> Loads form with customer select, product search, payment methods

User fills sale form:
  -> Selects customer
  -> Adds products with quantities, prices, discounts
  -> Selects payment method and enters amount
  -> Submits form

  -> POST /sales
    -> StoreSaleRequest validates all input
    -> SaleController@store delegates to SaleService
    -> SaleService@createSale:
      -> DB::transaction
        -> Create Sale (invoice number, totals, tax, discount)
        -> Create SaleItems
        -> Deduct stock per item
        -> Create Payment record
        -> Update customer due balance
        -> Log activity
      -> Commit
    -> Redirect to sale detail with success message

Sale Actions:
  Print -> SaleController@print -> generates printable view
  PDF -> SaleController@pdf -> DomPDF generation
  Email -> SaleController@email -> sends sale invoice via email
  SMS -> SaleController@sms -> sends via SMS gateway
```

---

## 4. Purchase Flow

```
User navigates to Purchases > Create Purchase
  -> PurchaseController@create
    -> Loads form with supplier select, product search

User fills purchase form:
  -> Selects supplier
  -> Adds products with quantities and costs
  -> Submits

  -> POST /purchases
    -> StorePurchaseRequest validates
    -> PurchaseService@createPurchase:
      -> DB::transaction
        -> Create Purchase record
        -> Create PurchaseItem records
        -> Status set to "pending" (awaiting approval)
      -> Commit
    -> Redirect with success

Approval Flow:
  Manager reviews purchase
    -> POST /purchases/{id}/approve
      -> PurchaseService@approve
        -> Status -> "approved"

Goods Receiving (GRN):
  -> POST /purchases/{id}/receive
    -> GrnService@receiveGoods
      -> DB::transaction
        -> Create GoodsReceiveNote
        -> Create GrnItem records
        -> Increment stock per item (WarehouseStock)
        -> Update purchase status -> "received"
        -> Update supplier due balance
        -> Log stock ledger entries
      -> Commit
```

---

## 5. Inventory Management Flow

```
Stock Adjustment:
  User creates stock adjustment (reason: damage, loss, correction)
    -> InventoryController@createAdjustment
      -> Create StockAdjustment (status: pending)
      -> Create StockAdjustmentItem records
    -> Manager approves
      -> InventoryService@approveAdjustment
        -> Update WarehouseStock quantities
        -> Log StockLedger entries

Stock Transfer:
  User creates transfer between warehouses
    -> InventoryController@createTransfer
      -> Create StockTransfer (status: pending)
      -> Create StockTransferItem records
    -> Source warehouse ships
      -> Status -> "shipped", deduct source stock
    -> Destination warehouse receives
      -> Status -> "received", increment destination stock

Stock Reconciliation:
  -> ReconciliationService compares system stock vs physical count
  -> Generates variance report
  -> Creates adjustment entries for discrepancies

Low Stock Alerts:
  -> Scheduled check compares stock vs reorder level
  -> Triggers LowStockAlert notification
  -> Notification appears in header bell icon + push notification (PWA)
```

---

## 6. Payment Flow

```
Payment Collection:
  From sale/invoice -> collect payment
    -> PaymentController@store
      -> PaymentService@processPayment
        -> DB::transaction
          -> Create Payment record (amount, method, reference)
          -> Create PaymentAllocation (link payment to invoice)
          -> Update invoice paid amount / payment status
          -> Update customer due balance
          -> If advance: credit to customer advance account
        -> Commit

Payment Methods Supported:
  Cash | bKash | Nagad | Rocket | Card (Visa/Master) | Bank Transfer

Payment Accounts:
  -> PaymentAccountController manages cash registers, bank accounts, mobile wallets
  -> Balance transfers between accounts tracked via BalanceTransfer model
  -> Account ledger shows all transactions per account

Supplier Payment:
  -> SupplierPaymentController@store
    -> Deducts from payment account
    -> Credits supplier balance
    -> Logs in supplier ledger
```

---

## 7. Customer Management Flow

```
Customer Lifecycle:
  Create -> CustomerController@store
    -> CustomerService creates customer with group, area assignment
    -> Opening balance set if applicable

Customer Ledger:
  -> CustomerController@ledger
    -> Shows all transactions: sales, payments, returns, advances
    -> Running balance calculated

Due Collection:
  -> CustomerController@dueReceive
    -> Collects outstanding amount
    -> Creates Payment + allocates to oldest invoices

Advance Management:
  -> Customer pays advance (pre-payment)
  -> Advance balance shown in POS for auto-deduction
  -> Offset due: advance applied against outstanding invoices

Customer Groups & Areas:
  -> Groups: wholesale, retail, VIP (discount tiers)
  -> Areas: geographic hierarchy for delivery and reporting
```

---

## 8. Expense Flow

```
Create Expense:
  -> ExpenseController@store
    -> ExpenseService creates expense with category, vendor, amount
    -> Status: pending (if approval required) or approved

Approval Workflow:
  -> Manager reviews pending expenses
  -> Approve: ExpenseService@approve -> status: approved, accounting entry created
  -> Reject: ExpenseService@reject -> status: rejected

Recurring Expenses:
  -> RecurringExpense defines template (amount, frequency, next_date)
  -> Scheduled job creates Expense from template on due date
  -> Auto-approved or requires approval based on settings

Expense Vendor Management:
  -> Vendors tracked separately from suppliers
  -> Vendor ledger shows all expense payments
  -> Due tracking and advance payments supported
```

---

## 9. Accounting Flow

```
Chart of Accounts:
  -> Hierarchical account structure (Assets, Liabilities, Equity, Revenue, Expenses)
  -> ChartOfAccountsService manages CRUD

Journal Entries:
  -> Manual entries for non-automated transactions
  -> JournalEntryService@create
    -> Creates JournalEntry + JournalEntryLine items
    -> Debits must equal Credits (validated)
    -> Status: draft -> posted (affects balances) -> voided

Automated Accounting Entries:
  Sale created -> Debit: Accounts Receivable, Credit: Revenue
  Payment received -> Debit: Cash/Bank, Credit: Accounts Receivable
  Purchase received -> Debit: Inventory, Credit: Accounts Payable
  Expense approved -> Debit: Expense Account, Credit: Cash/Bank

Financial Reports:
  General Ledger -> all entries per account with running balance
  Trial Balance -> debit/credit totals per account
  Profit & Loss -> revenue minus expenses for period
  Balance Sheet -> assets, liabilities, equity at a point in time
  Cash Flow -> cash inflows and outflows

Bank Reconciliation:
  -> BankReconciliationService compares system entries vs bank statement
  -> User matches/unmatches entries
  -> Generates reconciliation report with discrepancies
```

---

## 10. eCommerce Order Flow

```
Online Order Received:
  -> EcommerceOrder created (source: website/storefront)
  -> Status: pending

Order Processing:
  Admin reviews order
    -> Confirm: status -> confirmed
      -> Stock reserved
    -> Process: status -> processing
      -> Prepare for shipment
    -> Ship: status -> shipped
      -> Create DeliveryChallan
      -> Assign courier (Pathao/Steadfast/eCourier/etc.)
      -> Tracking number assigned
    -> Deliver: status -> delivered
      -> Stock deducted (if not already)
      -> Payment confirmed
    -> Cancel: status -> cancelled
      -> Stock released

Fraud Check:
  -> FraudCheckService validates order via BD courier fraud database
  -> Flags suspicious orders for manual review

Courier Integration:
  -> CourierProvider model stores API credentials per courier
  -> DeliveryService handles shipment creation, tracking updates
  -> Webhook receives delivery status updates from couriers
```

---

## 11. Reporting Flow

```
User navigates to Reports section
  -> ReportController routes to specific report type
  -> Report Service generates data:
    - SalesReportService: sales by date, product, customer, category
    - PurchaseReportService: purchase summaries, supplier analysis
    - InventoryReportService: stock levels, movement, valuation
    - FinancialReportService: P&L, balance sheet, cash flow
    - TaxReportService: VAT summaries, Mushak form data
    - StaffReportService: employee performance, attendance
    - CustomerReportService: customer analysis, receivables aging
    - DTSReportService: daily transaction summary
    - AdditionalReportService: custom and ad-hoc reports

Export:
  -> ExportController@export($module)
    -> Uses Maatwebsite/Excel
    -> Generates XLSX/CSV with formatted data
    -> Downloads via browser
```

---

## 12. Notification Flow

```
Events Trigger Notifications:
  Low stock -> LowStockAlert notification
  New sale -> NewSaleNotification
  New order -> NewOrderNotification

Notification Delivery:
  -> Database notification (stored in notifications table)
  -> Shown in header bell icon (AJAX polling)
  -> Push notification via service worker (PWA)

User Actions:
  -> View notifications: GET /notifications (AJAX JSON)
  -> Mark as read: POST /notifications/{id}/read
  -> Mark all read: POST /notifications/mark-all-read
```

---

## 13. Import/Export Flow

```
Export:
  User clicks export button on any list page
    -> GET /export/{module}?filters...
      -> ExportController resolves module-specific Export class
      -> Maatwebsite/Excel generates file
      -> Browser downloads XLSX/CSV

Import:
  User uploads file on import page
    -> POST /import/{module} with file
      -> ImportController resolves module-specific Import class
      -> Validates file format and data
      -> Processes rows in batches
      -> Reports success/failure count

Available Exports: Products, Customers, Suppliers, Sales, Purchases, Expenses, Stock, Employees, Assets, Payroll, Receivables, Account Ledger
Available Imports: Products, Customers, Suppliers
```

---

## 14. Settings Flow

```
Settings Page (tabbed):
  1. Business Profile - company name, address, logo, NID, TIN, BIN
  2. Branches - multi-branch configuration
  3. Tax/VAT - VAT rates, Mushak form settings
  4. Payment Methods - enable/disable, configure payment options
  5. Courier & Delivery - courier providers, shipping zones, rates
  6. Invoice & Receipt - invoice format, receipt template, numbering
  7. Notifications - alert thresholds, channels
  8. Localization - currency, date format, timezone
  9. SMS Gateway - SSL Wireless / BulkSMSBD configuration
  10. Email - SMTP settings, email templates
  11. Printers - thermal/receipt printer configuration
  12. Sidebar - menu item visibility and ordering
  13. System Logs - view/download/clear application logs
  14. Webhooks - external webhook endpoints
  15. Maintenance - cache clearing, maintenance mode

All settings stored in Settings model (key-value) and cached.
```

---

## 15. Security Flow

```
Request Pipeline:
  Request
    -> Rate Limiting (throttle middleware)
    -> CSRF Verification (VerifyCsrfToken middleware)
    -> Authentication (auth middleware / Sanctum for API)
    -> Authorization (Policies / Gates via spatie/laravel-permission)
    -> Input Validation (Form Requests)
    -> SecurityHeaders middleware (X-Frame-Options, CSP, etc.)
    -> Controller -> Service -> Response

User & Role Management:
  -> SecurityController manages users
  -> spatie/laravel-permission for roles and permissions
  -> Policies enforce per-model authorization
  -> Activity logging tracks all user actions

API Security:
  -> Sanctum token authentication
  -> Rate limiting per user
  -> API key management (ApiKey model)
```

---

## 16. Payroll Flow

```
Salary Structure:
  -> Define SalaryStructure with SalaryStructureComponent items
  -> Components: basic, house rent, medical, transport, etc.
  -> Assign structures to employees

Payroll Processing:
  -> PayrollController generates monthly payroll
  -> PayrollService calculates:
    -> Base salary from structure
    -> Attendance deductions
    -> Overtime calculations
    -> Tax deductions
  -> Creates Payroll + PayrollItem records
  -> Approval workflow before disbursement
```

---

## 17. Return Flows

```
Sale Return:
  Customer returns products
    -> SaleReturnController@store
      -> SaleReturnService@processReturn
        -> DB::transaction
          -> Create SaleReturn + SaleReturnItem records
          -> Increment stock (returned items)
          -> Adjust customer balance (credit)
          -> Create accounting entry (debit revenue, credit receivable)
        -> Commit

Purchase Return:
  Return products to supplier
    -> PurchaseReturnController@store
      -> PurchaseReturnService@processReturn
        -> DB::transaction
          -> Create PurchaseReturn + PurchaseReturnItem
          -> Deduct stock (returned items)
          -> Adjust supplier balance (debit)
          -> Create accounting entry
        -> Commit
```

---

## 18. Mobile App Sync Flow

```
Mobile App Startup:
  -> Check encrypted storage for auth token
    -> Token exists: validate with GET /api/v1/auth/profile
      -> Valid: proceed to Dashboard
      -> Invalid/expired: redirect to Login
    -> No token: show Login screen

Data Flow:
  Mobile Screen -> API Call (Axios + Bearer token) -> Laravel API Controller
    -> Same Service layer as web -> Model -> API Resource -> JSON Response
    -> Mobile parses response, updates UI

POS Sync:
  -> GET /api/v1/pos/init loads products, categories, settings
  -> Sale processed via POST /api/v1/pos/process-sale
  -> Receipt fetched via GET /api/v1/pos/receipt/{sale}
```
