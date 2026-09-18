# BizPOS Pro — QA Testing Guide

## Getting Started

Open the application at http://127.0.0.1:8000/admin and login with the admin credentials. The storefront is available at http://127.0.0.1:8000/ for customer-facing testing.

---

## 1. Dashboard

- Open the admin dashboard
- Verify all 8 stat cards show real numbers (not zeros or placeholder text)
- Verify the Sales Trend chart renders with actual data points
- Verify the Payment Method Breakdown pie chart shows correct proportions
- Verify Recent Sales table shows the latest invoices with correct amounts
- Verify Low Stock Alerts section shows products actually below minimum stock
- Click each stat card link — it should navigate to the respective module
- Switch to dark mode and verify all dashboard elements are readable

---

## 2. POS Terminal

### Basic Sale
- Open POS Terminal from the sidebar
- Search for a product by name — verify results appear
- Search by barcode — verify the product loads instantly
- Add 3 different products to the cart
- Change quantity of an item — verify the line total updates
- Remove an item — verify it disappears and totals recalculate
- Verify the subtotal, tax, and grand total are correct (multiply manually)

### Split Payment
- Set the total to a round number (e.g., 1000 BDT)
- Click "Add Split Payment" to add a second payment row
- Enter 600 in Cash and 400 in bKash
- Verify the "Received" amount shows 1000 and "Change" shows 0
- Complete the sale — verify the receipt shows both payment methods
- Check the sale in the Sales list — it should show as "Paid"

### Walk-in vs Customer Sale
- Process a sale WITHOUT selecting a customer — it should work as walk-in
- Process a sale WITH a customer selected — verify customer name appears on invoice
- Verify the customer's total_purchased updates in their profile after the sale

### POS Settings
- Open POS Settings
- Verify all toggles and options save correctly

---

## 3. Sales Module

### Create Invoice
- Go to Sales then New Invoice
- Select a customer, set the date, add line items
- Verify line totals auto-calculate (qty times price minus discount)
- Add a discount (percentage) — verify the discount amount calculates correctly
- Add tax — verify tax calculates on (subtotal minus discount)
- Add shipping — verify grand total equals subtotal minus discount plus tax plus shipping
- Add split payment (Cash plus bKash) — verify both payment rows appear
- Save as Draft — verify status shows "Draft" and customer totals are NOT updated
- Save as Confirmed — verify status shows "Confirmed" and customer total_purchased is updated

### Edit Invoice
- Open a draft invoice and click Edit
- Change the quantity — verify totals recalculate
- Add a new line item — verify it appears and totals update
- Try editing a confirmed invoice — it should be blocked (only drafts editable)

### Print and PDF
- Click Print — verify the print preview has NO action buttons (Print/Back/Download)
- Click Download PDF — verify a valid PDF file downloads
- Open the PDF — verify NO buttons appear inside the PDF
- Verify the invoice header shows the business name from Settings
- Verify the footer shows the configured footer text

### Sale Return
- From a confirmed sale, create a return
- Verify the items from the original sale load automatically
- Verify return quantity defaults to the sold quantity
- Verify return amount calculates correctly (quantity times unit price)
- Verify the unit price matches the original sale price (not editable to a fake price)
- Approve then Complete the return — verify customer total_purchased decreases
- Cancel a completed return — verify the reversal restores the customer balance

---

## 4. Purchase Module

### Create Purchase Order
- Go to Purchases then New Purchase
- Select a supplier, set the PO date, add line items
- Verify line totals calculate correctly including tax
- Save as Draft — verify supplier totals are NOT updated
- Approve the PO — verify supplier total_purchase updates

### Goods Receive (GRN)
- Open an approved PO and click Receive
- Enter received quantities — verify partial receive is allowed
- Complete the GRN — verify stock levels increase in the warehouse

### Supplier Payment
- Go to the supplier profile
- Record a payment against a PO — verify PO paid_amount updates
- Test split payment (Cash plus bKash in one submission)
- Record an advance payment — verify advance_balance increases
- Check supplier ledger — verify all transactions appear in order

### Purchase Return
- Create a return against a received PO
- Verify the unit price matches the original PO price
- Complete the return — verify supplier total_purchase decreases

---

## 5. Customer Management

### Customer Balance Verification
For each customer with transactions, verify these match:
- total_purchased equals the sum of all confirmed/delivered sale grand_totals
- total_paid equals the sum of all payment records for that customer
- outstanding dues equals total_purchased plus opening_balance minus total_paid

### Advance Payment Flow
- Record a 5000 BDT advance payment for a customer
- Create a 3000 BDT unpaid sale
- Go to the customer profile and click "Offset Due"
- Verify the sale becomes paid and advance balance reduces by 3000
- Verify the remaining advance is 2000

### Due Receive
- Go to Customers then Due Receive
- Verify the list shows only customers with outstanding amounts
- Click to receive payment — verify the due amount reduces
- Test partial payment — verify status changes to "Partial"
- Pay the remaining — verify status changes to "Paid"

### Customer Ledger
- Open any customer's ledger
- Verify all sales, payments, returns, and advances appear
- Verify the running balance is correct at each row
- Verify the totals at the bottom match

---

## 6. Expense Module

### Create Expense
- Create a new expense with amount, category, and payment account
- Verify due_amount equals total_amount on creation
- Approve the expense — verify status changes

### Partial Payment
- On an approved expense, record a partial payment (e.g., 500 of 2000)
- Verify paid_amount updates and due_amount reduces
- Verify payment_status changes to "Partial"
- Pay the remaining — verify it becomes "Paid"

### Split Payment on Expense
- Record a payment with 200 Cash plus 100 bKash plus 100 Card
- Verify 3 separate journal entries are created
- Verify each journal entry credits the correct asset account (Cash 1001, Mobile Banking 1002, Bank 1004)

### Vendor Tracking
- Create an expense linked to a vendor
- Verify the vendor's total_expense increases
- Record a vendor payment — verify total_paid increases

---

## 7. Accounting

### Journal Entry Balance
- Go to Accounting then Journal Entries
- Verify every posted entry shows equal debits and credits
- Create a manual journal entry with 2 lines — verify it auto-balances

### Trial Balance
- Open Trial Balance — verify total debits equal total credits
- This is the most critical check — if unbalanced, there is a data integrity issue

### Cash Flow
- Open Cash Flow report
- Verify Operating, Investing, and Financing sections have data
- Change the period — verify numbers update

### Chart of Accounts
- Verify account codes are unique
- Verify account types are correct (Assets, Liabilities, Equity, Revenue, Expense)

---

## 8. eCommerce Storefront

### Customer Registration and Login
- Go to the storefront and click Register
- Fill in name, phone, email, password — submit
- Verify redirect to customer profile
- Logout and login again — verify it works

### Shopping Flow
- Browse the shop page — verify products with images and prices show
- Filter by category — verify filtering works
- Sort by price — verify order changes
- Click a product — verify detail page shows correct price

### Cart and Checkout
- Add 3 products to cart
- Update quantity in cart — verify totals recalculate
- Apply a coupon code — verify discount applies
- Proceed to checkout — verify the cart totals carry over
- Fill in shipping address and place order (COD)
- Verify the success page shows the order number
- Verify the order appears in the customer's order history
- Verify the order appears in the admin ecommerce orders list

### Price Security
- Add a product to cart and note the price
- Change the product price in admin
- Go to checkout — verify the NEW price is used (not the old cart price)

---

## 9. Order Management (Admin)

- Open an ecommerce order in admin
- Change status: Pending then Confirmed then Processing then Shipped then Delivered
- Verify each status change saves correctly
- Add tracking information — verify it saves
- Cancel an order — verify status changes to Cancelled
- Verify the customer can see the updated status on the storefront

---

## 10. Payroll and HR

### Employee
- Create a new employee with name, department, salary
- Edit the employee — change salary and save
- Verify the updated salary appears

### Attendance
- Record attendance for today — mark 2 employees as Present
- Verify attendance appears in the attendance list
- View the attendance report — verify counts match

### Leave
- Submit a leave request for an employee
- Approve the leave — verify status changes
- Submit another leave and Reject it — verify status changes

### Payroll Generation
- Go to Payroll then Generate
- Select a month and generate — verify payroll creates with correct salary amounts
- Verify each employee's gross and net salary match the salary structure
- Approve the payroll
- Mark as Paid — verify status changes

---

## 11. Loan Module

- Create a lender with name and bank details
- Create a loan: 10000 BDT, 5 monthly installments
- Verify 5 schedule entries are generated with correct amounts
- Verify the lender's total_borrowed increases by 10000
- Record a repayment — verify the schedule status changes to Paid
- Verify lender's total_repaid increases and outstanding decreases
- Cancel a loan with no repayments — verify lender totals reverse

---

## 12. Ad Spend

- Go to Marketing then Ad Spend
- Verify seeded platforms appear (Facebook, Google, Instagram, etc.)
- Create an ad spend: Facebook, 5000 BDT, with impressions and clicks
- Verify the dashboard charts update
- Verify platform breakdown shows Facebook with the correct amount
- Check the show page — verify CPC, CPM, CTR calculate correctly
- Go to Analytics — verify period comparison and platform table show data

---

## 13. Export and Print

### Export Testing
- On every list page (Sales, Purchases, Expenses, etc.), click Export
- Click PDF — verify a PDF file downloads
- Click Excel — verify an XLSX file downloads
- Click CSV — verify a CSV file downloads with correct headers

### Print Testing
- On every show page, click Print
- Verify the browser print dialog opens
- Verify NO "Print", "Back", or "Download" buttons appear in the print preview
- Verify the business name and footer text come from Settings

---

## 14. Settings

- Open Settings
- Update the business name — verify it reflects on invoices
- Toggle "Allow Back-dated Transactions" OFF
- Try creating a sale with yesterday's date — it should be blocked
- Toggle it ON with 30 day limit
- Try creating a sale with a date 10 days ago — it should succeed
- Try creating a sale with a date 60 days ago — it should be blocked
- Click "Preview Invoice" — verify it opens the latest invoice in a new tab

---

## 15. Data Integrity Checks

After all testing, verify these critical balances:

- Open Trial Balance — debits must equal credits
- For every customer with sales: total_purchased must equal sum of confirmed sale grand_totals
- For every customer with payments: total_paid must equal sum of payment records
- For every supplier: total_purchase must equal sum of approved purchase grand_totals
- For every lender: outstanding_balance must equal total_borrowed plus opening_balance minus total_repaid
- No negative warehouse stock quantities
- Every expense with payment_status "paid" must have due_amount of 0
- Every sale with payment_status "paid" must have due_amount of 0

---

## 16. Design and UI Checks

- Verify all toggle switches use the theme color (dark blue, not default Bootstrap blue)
- Verify no CDN links in the page source (all assets must be local)
- Verify dark mode works on every page
- Verify all pages are responsive at 768px, 992px, and 1200px breakpoints
- Verify no inline styles (style="...") in the HTML source
- Verify all buttons have the correct bp-btn classes
- Verify all badges use the correct bp-badge color classes
- Verify all stat cards show dynamic data (not hardcoded numbers)
- Verify the sidebar active state highlights the current page

---

## 17. Security Checks

- Try accessing admin pages without logging in — should redirect to login
- Try accessing customer pages without customer login — should redirect to customer login
- Try submitting a POS sale with a fake unit_price via browser dev tools — verify the backend uses the database price instead
- Try submitting a sale return with a manipulated unit_price — verify it uses the original sale item price
- Verify all forms have CSRF tokens
- Verify file uploads only accept allowed types (no PHP or EXE files)
