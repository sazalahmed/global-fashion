# BizPOS Pro — Complete Feature Guide

BizPOS Pro is an all-in-one business management system designed for retail businesses, super shops, fashion stores, electronics dealers, and SMBs in Bangladesh. It combines Point of Sale, Inventory Management, Accounting, eCommerce, Manufacturing, HR, and Marketing into a single platform.

---

## Point of Sale (POS)

The POS terminal is a fast, full-screen sales interface designed for counter-top use. Staff can scan barcodes or search products by name, add them to the cart, select a customer or process as walk-in, and complete payment using cash, bKash, Nagad, cards, or any combination. The system supports split payments — for example, a customer can pay 600 BDT in cash and 400 BDT via bKash for a single 1000 BDT purchase. After each sale, a receipt is generated that can be printed or shared digitally.

The POS also supports quick customer creation (add a new customer without leaving the terminal), real-time stock checking, and end-of-day settlement where managers can reconcile the cash drawer against the day's sales.

---

## Sales Management

Sales invoices can be created manually from the admin panel (for phone orders, wholesale deals, or custom pricing) or automatically through the POS terminal and eCommerce storefront. Every invoice tracks the customer, products, quantities, prices, discounts, tax, and shipping.

Invoices go through a lifecycle: Draft (editable, not counted in reports), Confirmed (finalized, stock deducted), Delivered (shipped to customer), or Cancelled. Each invoice can be printed as a professional PDF, emailed to the customer, sent via SMS, or shared as a web link.

The system supports split payments at the invoice level — a single invoice can be paid using multiple payment methods simultaneously. Payment tracking shows paid amount, due amount, and payment status (Unpaid, Partial, Paid) in real time.

### Quotations

Before committing to a sale, staff can create formal quotations for customers. Quotations include product details, pricing, validity period, and terms. With one click, a quotation converts into a confirmed sale invoice, carrying over all the line items.

### Installment Plans (Kisti)

For large purchases, BizPOS supports installment plans. The system auto-generates a repayment schedule (weekly, bi-weekly, or monthly), tracks each installment payment, and shows the customer's installment progress.

### Sale Returns

When customers return products, a formal sale return is created against the original invoice. The system validates that return quantities don't exceed the sold quantities and that prices match the original sale. Returns go through an approval workflow (Draft, Approved, Completed) and automatically restock good-condition items while tracking damaged items separately.

---

## Purchase Management

Purchase orders are created for suppliers with line items, quantities, unit prices, tax, and shipping costs. POs follow an approval workflow: Draft (editable), Pending (submitted for approval), Approved (ready for receiving). Approved POs can be printed or exported as PDF for the supplier.

### Goods Receiving (GRN)

When goods arrive, staff creates a Goods Receive Note against the PO. They can receive partial quantities, mark rejected items with reasons, and the system automatically updates warehouse stock levels for accepted items.

### Supplier Payments

Payments to suppliers are tracked per purchase order. The system supports partial payments, advance payments, and split payments across multiple payment methods. The supplier's running balance (total purchased, total paid, due amount, advance balance) updates automatically.

### Purchase Returns

Defective or excess goods can be returned to suppliers through the purchase return process. The system validates prices against the original PO and tracks the return through completion.

---

## Inventory Management

### Warehouses

The system supports multiple warehouses. Each warehouse can have named locations (shelves, racks, bins). Products track stock per warehouse, and a default warehouse handles POS sales automatically.

### Stock Overview

A real-time view of all product stock levels across all warehouses. Products below their minimum stock level are flagged with alerts.

### Stock Adjustments

When physical stock doesn't match the system (damage, theft, counting errors), stock adjustments record the difference. Adjustments go through an approval process and create audit-trail entries.

### Stock Transfers

Move inventory between warehouses with a tracked workflow: Create the transfer, Ship it (stock leaves the source), Receive it (stock enters the destination). Partial receives are supported.

### Stock Ledger

A complete history of every stock movement for every product — sales, purchases, adjustments, transfers, returns — with running balance at each point.

---

## Product Management

Products support simple (single SKU) and variable (multiple variants) types. Variable products can have combinations of size, color, material, or any custom attribute. Each variant gets its own SKU, barcode, price, and stock level.

The system auto-generates SKUs and barcodes. Products can be duplicated for quick setup of similar items. Bulk variant generation creates all size/color combinations at once.

### Barcode Printing

Search for products and generate printable barcode labels in bulk. Labels include barcode, product name, price, and SKU.

---

## Customer Management

Each customer has a profile with contact details, purchase history, payment records, and running balance. Customers can be organized into groups (Retail, Wholesale, VIP) and geographic areas.

### Customer Ledger

A complete financial history showing every sale, payment, return, and advance — with a running balance that always matches the stored totals.

### Due Collection

A dedicated view showing all customers with outstanding amounts, sorted by overdue amount. Staff can record payments directly from this screen.

### Advance Payments

Customers can make advance payments that sit as credit on their account. When they make a purchase, the advance can be automatically applied against the invoice through the "Offset Due" feature. Unused advances can be refunded.

---

## Supplier Management

Supplier profiles store contact details, bank information, and financial history. Each supplier has a ledger showing purchases, payments, returns, and running balance.

Suppliers can be organized into groups. Payment terms and credit limits are tracked per supplier.

---

## Expense Management

Every business expense is tracked from creation through approval to payment. Expenses link to expense categories and optionally to expense vendors (landlords, service providers, etc.).

### Approval Workflow

Expenses start as Pending, then get Approved or Rejected by a manager. Approved expenses can be paid immediately or kept as due for later payment.

### Partial and Split Payments

A single expense can be paid in multiple installments. Each payment records which payment account was used. Split payments allow paying with multiple methods at once — for example, 200 BDT cash plus 100 BDT bKash plus 100 BDT card for a 400 BDT expense.

### Recurring Expenses

Monthly rent, utility bills, and other regular expenses can be set up as recurring. The system auto-generates expense entries at the configured frequency.

### Expense Vendors

Track outstanding amounts owed to service providers, landlords, and other vendors separately from product suppliers.

---

## Accounting

BizPOS includes a complete double-entry accounting system that automatically records journal entries for every financial transaction — sales, purchases, payments, returns, expenses, payroll, loans, and ad spend.

### Chart of Accounts

A hierarchical account structure with five types: Assets, Liabilities, Equity, Revenue, and Expenses. System accounts are pre-configured and custom accounts can be added.

### Journal Entries

Every transaction creates balanced journal entries (debits equal credits). Manual journal entries can be created for adjustments. Entries can be voided with automatic reversing entries.

### Financial Reports

- Trial Balance — verifies all accounts balance
- Profit and Loss — revenue minus expenses for any period
- Balance Sheet — assets, liabilities, and equity at a point in time
- Cash Flow — operating, investing, and financing cash movements
- General Ledger — detailed transaction history per account

### Bank Reconciliation

Match bank statement transactions against system records to identify discrepancies. Import bank statements and auto-match transactions.

### Credit and Debit Notes

Issue credit notes for customer refunds and debit notes for supplier adjustments. Both generate proper Mushak forms for Bangladesh tax compliance.

---

## Loan Management

Track business loans from external lenders. Create a loan with the principal amount, set up an installment schedule (weekly, bi-weekly, or monthly), and record repayments as they happen.

When a loan is disbursed (money received), the system records it as a liability in the accounting books. When repayments are made, the liability reduces. Everything reflects in the trial balance and cash flow reports.

Lenders have their own profiles with running balances showing total borrowed, total repaid, and outstanding amount.

---

## Ad Spend Tracking

Track advertising costs across all digital platforms — Facebook, Google Ads, Instagram, TikTok, YouTube, LinkedIn, and more.

For each ad campaign, record the spend amount, platform, date, and performance metrics (impressions, clicks, conversions, reach). The system automatically calculates CPC (Cost Per Click), CPM (Cost Per 1000 Impressions), CTR (Click-Through Rate), and Conversion Rate.

### Analytics Dashboard

Visual charts show the 30-day spend trend, platform breakdown (which platform gets the most budget), period-over-period comparison (this month vs last month), and top-performing campaigns.

Ad spend is recorded as an accounting expense and reflects in the cash flow and profit/loss reports.

---

## eCommerce Storefront

BizPOS includes a complete customer-facing online store.

### Customer Experience

Customers can browse products by category, search by name, filter by price, view product details with images, and add items to their cart. The checkout process supports Cash on Delivery with shipping address and district selection.

Registered customers get a personal dashboard where they can view order history, track order status, manage their profile, change password, and maintain a wishlist.

### Order Management

Admin staff see all online orders with status tracking: Pending, Confirmed, Processing, Shipped, Delivered, Cancelled, Refunded. Each status transition is tracked. Courier providers (Pathao, Steadfast, eCourier, etc.) can be assigned to orders with tracking numbers.

### Content Management

- Homepage Builder — customize homepage sections and layout
- Banners — promotional banners with image uploads
- Collections — curated product groups
- Flash Deals — time-limited discounts
- Blog — content marketing with full post management
- Coupons — discount codes with usage limits and validity periods

### Shipping

Define shipping zones by district/division with flat-rate shipping. Set free shipping thresholds per zone.

### Price Security

Cart prices are re-validated from the database at checkout time. If a product price changes between when the customer adds it to cart and when they checkout, the current price is used. This prevents stale pricing.

---

## Manufacturing

For businesses that produce goods (especially garment manufacturing), BizPOS includes a full manufacturing module.

### Factories

Register contract factories/manufacturers with contact details and track payments to them.

### Raw Materials

Manage raw material inventory separately from finished goods. Each material has its own SKU, unit (yard, meter, kg, piece), cost price, and reorder level.

### Production Orders

Create production orders that specify which products to manufacture, in what quantities, sizes, and colors. Track the order through its lifecycle: Draft, Approved, In Production, Completed.

Production orders support fabric issuance (sending raw materials to the factory), fabric returns (leftover materials coming back), and production lots (batch tracking).

### Quality Control

Record production damages and defects with compensation tracking. Track raw material waste and finished product waste separately.

### Reports

Manufacturing-specific reports include raw material stock levels, production output, damage analysis, waste tracking, and cost analysis.

---

## Human Resources

### Employee Management

Maintain employee records with personal details, department, designation, salary, joining date, and employment status. Employee photos can be uploaded.

### Attendance

Record daily attendance for all employees. The system supports Present, Absent, Late, Half Day, and On Leave statuses with check-in/check-out times.

### Leave Management

Employees can apply for leave (Casual, Sick, Annual, Maternity, Paternity, Unpaid). Managers approve or reject leave requests. The system tracks leave balances.

### Holidays and Weekends

Configure public holidays with date ranges. Set weekend days (e.g., Friday and Saturday for Bangladesh). Both affect attendance calculations and payroll.

### Payroll

Generate monthly payroll automatically based on employee salary, attendance records, and salary structure components. Salary structures define earnings (Basic, House Rent, Transport) and deductions (Provident Fund, Tax). The system prorates salary for absent days and recovers advance payments.

Payroll goes through approval before payment. Paid payroll creates accounting journal entries.

---

## Marketing

### Ad Spend Tracking

Described above in the Ad Spend section.

### SMS Campaigns

Create and send bulk SMS campaigns to customer segments. Configure SMS gateway (SSL Wireless, BulkSMSBD), compose messages, and schedule delivery.

### Email Marketing

Create email campaigns with HTML templates, target customer segments, and schedule sends.

### Loyalty Program

Reward customers with points for purchases. Points can be earned and redeemed. The system tracks point balances per customer.

---

## Reports and Analytics

### Sales Reports

Daily Trading Summary (DTS), category-wise sales breakdown, detailed line-item sales, monthly summaries, and staff performance reports.

### Financial Reports

Income vs expense analysis, profit trends, receivables aging (30/60/90 day buckets), cash movement tracking, and supplier payment history.

### Inventory Reports

Stock level summaries, stock movement history, slow-moving items, and inventory valuation.

### Tax Reports

VAT summary for tax filing, input vs output tax analysis, and Mushak form preparation for Bangladesh NBR compliance.

### Custom Report Builder

Build ad-hoc reports by selecting columns, applying filters, and choosing grouping. Export any report as PDF, Excel, or CSV.

---

## Settings and Configuration

### Business Profile

Company name, logo, address, phone, email, BIN/TIN numbers, financial year start, timezone, and currency settings.

### Back-date Control

Administrators can enable or disable back-dated transactions. When enabled, a maximum number of days in the past can be set (e.g., 30 days). This prevents users from creating sales or expenses with dates too far in the past.

### Invoice Settings

Configure invoice numbering format, footer text, and preview how invoices look.

### Email Configuration

SMTP settings for sending emails, plus customizable email templates for different transaction types.

### Printer Configuration

Set up thermal receipt printers, A4 invoice printers, and barcode label printers with network connection details.

---

## Security

### Users and Roles

Create user accounts with role-based access control. Define custom roles with granular permissions — control which modules, pages, and actions each role can access.

### Activity Log

Every user action is logged with timestamp and details. Administrators can review who did what and when.

### Backup and Restore

Create database backups on demand. Download backup files for offsite storage. Restore from backups when needed.

### API Keys

Generate API keys for the mobile app and third-party integrations. Control API rate limits and environment (live/test).

---

## Multi-Branch Support

BizPOS supports multiple business locations (branches). Each branch can have its own warehouses, employees, and settings while sharing the same product catalog, customer database, and accounting books. Reports can be filtered by branch.

---

## Export and Print

Every list page supports exporting data as PDF, Excel (XLSX), or CSV. Individual records (invoices, POs, quotations, delivery challans, credit notes, etc.) can be printed or downloaded as professional PDF documents.

Print templates are clean and professional with no browser header/footer pollution. The business name, address, and custom footer text appear on all printed documents.

---

## Technical Highlights

- All prices are calculated on the server — the system never trusts prices sent from the browser
- Double-entry accounting ensures every transaction is balanced (debits equal credits)
- Soft deletes preserve audit trails — deleted records are hidden but not destroyed
- Split payment support across all payment forms (sales, purchases, expenses, customer payments, supplier payments)
- Bangladesh-specific: BDT currency in lakh format, VAT at 15%, Mushak forms, local courier integrations, Bengali number support
- Progressive Web App (PWA) — installable on mobile devices from the browser
- All third-party libraries served locally — no CDN dependencies
- Dark mode support across the entire application
