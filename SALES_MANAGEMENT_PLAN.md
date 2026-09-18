# Sales Management Implementation Plan

## Context
BizPOS Pro needs a complete Sales Management system covering Customer Management, Sales Orders, POS System, Sales Invoices, and Sales Returns. The existing modules (Sale, Customer, POS, SaleReturn, Quotation, Delivery, Installment, Payment) have partial views and stub controllers but zero migrations, models, or business logic. This plan builds out the full backend and updates existing views.

---

## Phase 1: Customer Module
**Goal:** Full customer CRUD with ledger/transaction history.

### Migration: `Modules/Customer/database/migrations/..._create_customers_table.php`
```
customers: id, name, phone (unique, indexed), email, company_name, tax_number,
  shipping_address, billing_address, city, district, division,
  customer_group (enum: regular/wholesale/vip/corporate),
  credit_limit decimal(15,2) default 0, current_balance decimal(15,2) default 0,
  total_purchased decimal(15,2) default 0, total_paid decimal(15,2) default 0,
  loyalty_points int default 0, notes, is_active boolean default true,
  branch_id (FK), created_by (FK users), timestamps, softDeletes
```

### Model: `Modules/Customer/app/Models/Customer.php`
- Relationships: `sales()`, `payments()`, `branch()`, `creator()`
- Scopes: `scopeActive()`, `scopeByGroup()`, `scopeByBranch()`, `scopeSearch($term)`
- Accessor: `getFormattedBalanceAttribute()`

### Service: `Modules/Customer/app/Services/CustomerService.php`
- `getFilteredCustomers(array $filters)` — search, group filter, status, paginated
- `store(array $data)`, `update(Customer, array $data)`, `destroy(Customer)`
- `getLedger(Customer, array $filters)` — combined sales + payments history
- `updateBalance(Customer)` — recalculates from sales/payments

### Form Requests: `StoreCustomerRequest`, `UpdateCustomerRequest`

### Controller: Rewrite `Modules/Customer/app/Http/Controllers/CustomerController.php`
- Thin methods delegating to CustomerService
- `ledger($id)` — customer transaction history page

### Views: Update existing `index.blade.php`, `create.blade.php`; create `edit.blade.php`, `show.blade.php`

---

## Phase 2: Payment Module
**Goal:** Polymorphic payment system supporting Sales, Purchases, Returns.

### Migration: `Modules/Payment/database/migrations/..._create_payments_table.php`
```
payments: id, payment_number (unique, indexed), payable_type, payable_id (polymorphic),
  customer_id (nullable FK), supplier_id (nullable FK),
  payment_type enum(received, sent),
  payment_method enum(cash, bkash, nagad, rocket, card, bank_transfer),
  amount decimal(15,2), transaction_id (nullable),
  payment_date date, notes, branch_id (FK),
  created_by (FK users), timestamps, softDeletes
Index: (payable_type, payable_id), payment_date, payment_method
```

### Model: `Modules/Payment/app/Models/Payment.php`
- Relationships: `payable()` (morphTo), `customer()`, `supplier()`, `branch()`, `creator()`
- Scopes: `scopeReceived()`, `scopeSent()`, `scopeByMethod()`, `scopeByDateRange()`

### Service: `Modules/Payment/app/Services/PaymentService.php`
- `recordPayment(array $data)` — creates payment, updates parent status
- `getFilteredPayments(array $filters)` — with polymorphic eager loading

### Controller: Rewrite `Modules/Payment/app/Http/Controllers/PaymentController.php`

### Views: Update existing `index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`

---

## Phase 3: Sale + POS Modules
**Goal:** Complete sales workflow and functional POS terminal.

### Shared Trait: `app/Traits/HasReferenceNumber.php`
```php
trait HasReferenceNumber {
    public static function generateReference(string $prefix): string {
        $date = now()->format('Ymd');
        $last = static::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . $date . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
```

### Migration: `Modules/Sale/database/migrations/..._create_sales_table.php`
```
sales: id, invoice_number (unique, indexed), reference_number,
  customer_id (nullable FK), branch_id (FK),
  sale_date date, due_date date nullable,
  source enum(pos, store, ecommerce) default 'store',
  status enum(draft, confirmed, delivered, cancelled) default 'confirmed',
  payment_status enum(paid, partial, unpaid) default 'unpaid',
  subtotal decimal(15,2), discount_type enum(fixed, percentage) nullable,
  discount_value decimal(15,2) default 0, discount_amount decimal(15,2) default 0,
  tax_rate decimal(5,2) default 0, tax_amount decimal(15,2) default 0,
  shipping_charge decimal(15,2) default 0,
  grand_total decimal(15,2), paid_amount decimal(15,2) default 0,
  due_amount decimal(15,2) default 0,
  notes, created_by (FK users), timestamps, softDeletes
Index: sale_date, status, payment_status, customer_id
```

### Migration: `..._create_sale_items_table.php`
```
sale_items: id, sale_id (FK cascade), product_id (FK),
  product_name, product_sku,
  quantity int, unit_price decimal(15,2),
  discount_amount decimal(15,2) default 0,
  tax_amount decimal(15,2) default 0,
  subtotal decimal(15,2), timestamps
```

### Models
- `Modules/Sale/app/Models/Sale.php` — relationships: items(), customer(), payments() (morphMany), branch(), creator(), returns()
- `Modules/Sale/app/Models/SaleItem.php` — relationships: sale(), product()

### Service: `Modules/Sale/app/Services/SaleService.php`
- `createSale(array $data)` — DB::transaction: create sale + items, deduct stock, record payment if any, update customer balance
- `getFilteredSales(array $filters)` — search by invoice#, customer, date range, status
- `calculateTotals(array $items, ?discount, ?tax, ?shipping)` — shared calculation logic
- `updatePaymentStatus(Sale)` — recalculate from payments
- `cancelSale(Sale)` — restore stock, update status

### Service: `Modules/POS/app/Services/POSService.php`
- `processPOSSale(array $cartData)` — delegates to SaleService with source='pos'
- `searchProducts(string $term)` — AJAX product search for POS
- `getProductByBarcode(string $barcode)` — barcode scanner support

### Form Requests: `StoreSaleRequest`, `UpdateSaleRequest`

### Controllers
- Rewrite `Modules/Sale/app/Http/Controllers/SaleController.php` — full CRUD + invoice print/share
- Rewrite `Modules/POS/app/Http/Controllers/POSController.php` — index (terminal), processSale (AJAX), searchProducts (AJAX), getByBarcode (AJAX)

### POS Routes (AJAX endpoints):
```php
Route::post('pos/process-sale', [POSController::class, 'processSale'])->name('pos.process');
Route::get('pos/search-products', [POSController::class, 'searchProducts'])->name('pos.search');
Route::get('pos/barcode/{code}', [POSController::class, 'getByBarcode'])->name('pos.barcode');
```

### Views
- Update all 6 existing Sale views (index, create, edit, show, invoice-print, invoice-share)
- Update POS `index.blade.php` — wire up existing cart JS to new AJAX endpoints
- Enhance `public/js/app.js` POS section — connect to backend routes, handle responses

---

## Phase 4: Sale Return + Quotation Modules
**Goal:** Process returns against sales, manage quotations convertible to sales.

### Migration: `..._create_sale_returns_table.php`
```
sale_returns: id, return_number (unique), sale_id (FK),
  customer_id (nullable FK), branch_id (FK),
  return_date date, reason text,
  status enum(pending, approved, completed, rejected) default 'pending',
  subtotal decimal(15,2), tax_amount decimal(15,2) default 0,
  grand_total decimal(15,2),
  refund_method enum(cash, bkash, nagad, rocket, card, bank_transfer, store_credit) nullable,
  refund_status enum(pending, refunded, credited) default 'pending',
  notes, created_by (FK users), timestamps, softDeletes
```

### Migration: `..._create_sale_return_items_table.php`
```
sale_return_items: id, sale_return_id (FK cascade), sale_item_id (FK),
  product_id (FK), quantity int, unit_price decimal(15,2),
  subtotal decimal(15,2), reason nullable, timestamps
```

### Migration: `..._create_quotations_table.php`
```
quotations: id, quotation_number (unique), customer_id (nullable FK),
  branch_id (FK), quotation_date date, valid_until date,
  status enum(draft, sent, accepted, rejected, expired, converted) default 'draft',
  subtotal, discount_type, discount_value, discount_amount,
  tax_rate, tax_amount, grand_total decimal(15,2),
  converted_sale_id (nullable FK sales),
  notes, created_by (FK users), timestamps, softDeletes
```

### Migration: `..._create_quotation_items_table.php`
```
quotation_items: id, quotation_id (FK cascade), product_id (FK),
  product_name, product_sku, quantity int,
  unit_price decimal(15,2), subtotal decimal(15,2), timestamps
```

### Models: SaleReturn, SaleReturnItem, Quotation, QuotationItem

### Services
- `SaleReturnService` — `createReturn(array $data)` (restore stock, create refund payment), `approveReturn()`, `getFilteredReturns()`
- `QuotationService` — `createQuotation()`, `convertToSale(Quotation)` (creates Sale from quotation data), `markExpired()` (scheduled)

### Controllers: Rewrite SaleReturnController, QuotationController

### Views: Update existing SaleReturn views (index, create) + add show/edit; Update Quotation index + add create/show

---

## Phase 5: Delivery + Installment Modules
**Goal:** Track deliveries for sales, manage installment payment plans.

### Migration: `..._create_deliveries_table.php`
```
deliveries: id, delivery_number (unique), sale_id (FK),
  customer_id (FK), branch_id (FK),
  courier_name enum(pathao, steadfast, ecourier, redx, paperfly, sundarban, sa_paribahan, self),
  tracking_number, shipping_address, shipping_city, shipping_district,
  shipping_cost decimal(15,2) default 0,
  status enum(pending, picked_up, in_transit, delivered, returned, cancelled) default 'pending',
  estimated_date date nullable, delivered_date date nullable,
  notes, created_by (FK users), timestamps, softDeletes
```

### Migration: `..._create_installment_plans_table.php`
```
installment_plans: id, plan_number (unique), sale_id (FK),
  customer_id (FK), total_amount decimal(15,2),
  down_payment decimal(15,2) default 0,
  remaining_amount decimal(15,2), number_of_installments int,
  installment_amount decimal(15,2), frequency enum(weekly, biweekly, monthly),
  start_date date, status enum(active, completed, defaulted, cancelled) default 'active',
  notes, created_by (FK users), timestamps, softDeletes
```

### Migration: `..._create_installment_payments_table.php`
```
installment_payments: id, installment_plan_id (FK cascade),
  payment_id (nullable FK payments), installment_number int,
  due_date date, paid_date date nullable, amount decimal(15,2),
  status enum(pending, paid, overdue, waived) default 'pending',
  timestamps
```

### Models: Delivery, InstallmentPlan, InstallmentPayment
### Services: DeliveryService, InstallmentService
### Controllers: Rewrite DeliveryController, InstallmentController
### Views: Update Delivery index + add create/show; Update Installment index + add create/show/pay

---

## Shared Infrastructure

### Trait: `app/Traits/HasReferenceNumber.php`
- Generates references like `SAL-20260313-0001`, `RET-20260313-0001`, etc.

### New Permissions (add to `app/Traits/PermissionsTrait.php`):
- `customers` group: view, create, edit, delete, view-ledger
- `payments` group: view, create, edit, delete
- `quotations` group: view, create, edit, delete, convert-to-sale
- `deliveries` group: view, create, edit, delete, update-status
- `installments` group: view, create, edit, delete, record-payment

### Sidebar Updates (`Modules/Core/resources/views/partials/sidebar.blade.php`):
- Add Customers under People section
- Add Quotations under Transactions
- Add Deliveries under Transactions
- Add Installments under Finance

### CSS Additions (`public/css/style.css`):
- POS receipt print styles
- Invoice print/share layout styles
- Installment timeline styles
- All with dark mode overrides

---

## File Count Summary
| Category | Count |
|---|---|
| Migrations | 11 |
| Models | 11 |
| Services | 8 |
| Form Requests | ~10 |
| Controllers (rewrite) | 8 |
| Views (update/create) | ~25 |
| Traits | 1 |
| **Total new/modified files** | **~64** |

---

## Implementation Order
1. **Phase 1** — Customer (foundation, no dependencies)
2. **Phase 2** — Payment (polymorphic, needed by Sale)
3. **Phase 3** — Sale + POS (depends on Customer, Payment, Product)
4. **Phase 4** — SaleReturn + Quotation (depends on Sale)
5. **Phase 5** — Delivery + Installment (depends on Sale, Payment)

Each phase is independently testable after completion.

---

## Verification Plan
After each phase:
1. Run `php artisan migrate` — verify tables created correctly
2. Run `php artisan db:seed --class=...` where applicable
3. Test CRUD operations via browser (create, list, view, edit, delete)
4. Verify search/filter functionality
5. Check dark mode rendering
6. Test permission gates (unauthorized user cannot access)

End-to-end after all phases:
1. Create a customer → Create a sale for that customer via store form
2. Process a POS sale (add items, apply discount, pay) → verify stock deducted
3. Print/share invoice
4. Create a return against the sale → verify stock restored, refund recorded
5. Create a quotation → convert to sale → verify sale created correctly
6. Create delivery for a sale → update tracking status
7. Create installment plan → record payments → verify completion
8. Check customer ledger shows all transactions
