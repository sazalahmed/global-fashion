# BizPOS Pro — Bulk Actions Implementation Plan

**Created:** 05 Apr 2026
**Status:** Pending Implementation

---

## Current Infrastructure

| Component | Status | Location |
|-----------|--------|----------|
| JS bulk handler | Ready | `public/js/app.js:665-709` — auto-derives module from URL, submits to `/bulk/{module}/{action}` |
| Bulk routes | Ready | `routes/web.php:22-25` — `POST /bulk/{module}/delete` and `POST /bulk/{module}/status` |
| BulkActionController | Exists (needs fixes) | `app/Http/Controllers/BulkActionController.php` — raw delete/status without business logic |
| `<x-core::table.bulk-actions>` | Ready | View component for rendering action buttons |
| Checkbox JS | Ready | `app.js:183-202` — check-all, row-checkbox, bulk count display |

### Current Problems with BulkActionController
1. `bulkDelete()` does raw `->delete()` — no dependency checks, no stock reversal, no payment cleanup
2. `bulkStatusUpdate()` does raw `->update(['status' => $status])` — no business logic (stock, journal, etc.)
3. Only 6 modules in `modelMap` (products, customers, sales, purchases, expenses, suppliers)
4. No module-specific action support (e.g., bulk approve, bulk cancel, bulk mark-paid)

---

## Phase 1 — Fix BulkActionController to Use Service Layer

**Goal:** Rewrite `BulkActionController` to delegate to each module's service for proper business logic.

### 1.1 Rewrite `bulkDelete()` Method

Instead of raw `->delete()`, loop through IDs and call the service's `delete()` method:

```php
public function bulkDelete(Request $request, string $module)
{
    $ids = $request->input('ids', []);
    $config = $this->getModuleConfig($module);
    
    $deleted = 0;
    $errors = [];
    
    foreach ($ids as $id) {
        try {
            $record = $config['model']::findOrFail($id);
            $config['service']->delete($record); // Uses guards + cleanup
            $deleted++;
        } catch (\Throwable $e) {
            $errors[] = "#{$id}: {$e->getMessage()}";
        }
    }
    
    // Return summary with successes and failures
}
```

### 1.2 Rewrite `bulkStatusUpdate()` Method

Delegate to service methods based on target status:

```php
public function bulkStatusUpdate(Request $request, string $module)
{
    $ids = $request->input('ids', []);
    $status = $request->input('status');
    $config = $this->getModuleConfig($module);
    
    foreach ($ids as $id) {
        $record = $config['model']::findOrFail($id);
        match ($status) {
            'cancelled' => $config['service']->cancel($record),
            'approved'  => $config['service']->approve($record),
            // etc.
        };
    }
}
```

### 1.3 New Module Config Map

Replace flat `modelMap` with full config:

```php
private function getModuleConfig(string $module): array
{
    return match ($module) {
        'sales'             => ['model' => Sale::class, 'service' => app(SaleService::class)],
        'purchases'         => ['model' => Purchase::class, 'service' => app(PurchaseService::class)],
        'sale-returns'      => ['model' => SaleReturn::class, 'service' => app(SaleReturnService::class)],
        'purchase-returns'  => ['model' => PurchaseReturn::class, 'service' => app(PurchaseReturnService::class)],
        'products'          => ['model' => Product::class, 'service' => app(ProductService::class)],
        'customers'         => ['model' => Customer::class, 'service' => app(CustomerService::class)],
        'suppliers'         => ['model' => Supplier::class, 'service' => app(SupplierService::class)],
        'employees'         => ['model' => Employee::class, 'service' => app(EmployeeService::class)],
        'expenses'          => ['model' => Expense::class, 'service' => app(ExpenseService::class)],
        'payments'          => ['model' => Payment::class, 'service' => app(PaymentService::class)],
        'payroll'           => ['model' => Payroll::class, 'service' => app(PayrollService::class)],
        'installments'      => ['model' => InstallmentPlan::class, 'service' => app(InstallmentService::class)],
        'quotations'        => ['model' => Quotation::class, 'service' => app(QuotationService::class)],
        'delivery'          => ['model' => DeliveryChallan::class, 'service' => app(DeliveryService::class)],
        // Inventory uses InventoryService for both
        'stock-adjustments' => ['model' => StockAdjustment::class, 'service' => app(InventoryService::class)],
        'stock-transfers'   => ['model' => StockTransfer::class, 'service' => app(InventoryService::class)],
    };
}
```

---

## Phase 2 — Add Bulk Action Buttons to Views Missing Them

**Goal:** Every index table should have bulk action checkboxes and action buttons.

### Views That Already Have Bulk Actions
These use `<x-core::table :selectable="true">` and `<x-core::table.bulk-actions>`:

| Module | Actions Available |
|--------|-----------------|
| Sale | Print, Export (has bulk but needs action buttons) |
| Purchase | Approve, Cancel (has bulk but needs action buttons) |
| Customer | Set Active, Set Inactive, Delete |
| Supplier | Set Active, Set Inactive, Delete |
| Product | Set Active, Set Inactive, Delete |
| Employee | Set Active, Set Inactive, Delete (has bulk bar) |
| Expense | Approve, Reject (has bulk bar) |
| Installment | Has bulk bar |
| Quotation | Has bulk actions |
| Delivery | Has bulk bar |

### Views That Need Bulk Actions Added

| Module | View | Actions to Add |
|--------|------|---------------|
| **SaleReturn** | `index.blade.php` | Bulk Cancel, Bulk Delete (draft only) |
| **PurchaseReturn** | `index.blade.php` | Bulk Cancel, Bulk Delete (draft only) |
| **Payment** | `index.blade.php` | Bulk Delete |
| **Payroll** | `index.blade.php` | Bulk Approve, Bulk Cancel, Bulk Delete |
| **Inventory (stock levels)** | `index.blade.php` | Bulk Export (no actions needed) |
| **Inventory (adjustments)** | `adjustments.blade.php` | Bulk Approve, Bulk Cancel, Bulk Delete |
| **Inventory (transfers)** | `transfers.blade.php` | Bulk Ship, Bulk Cancel, Bulk Delete |
| **Accounting (journal entries)** | `journal-entries.blade.php` | Bulk Post, Bulk Void |
| **Accounting (credit notes)** | `credit-notes/index.blade.php` | Bulk Issue, Bulk Cancel |
| **Accounting (debit notes)** | `debit-notes/index.blade.php` | Bulk Issue, Bulk Cancel |
| **Accounting (receipts)** | `receipts/index.blade.php` | No bulk actions needed (read-only) |

### For Each View, Add:

1. `:selectable="true"` on `<x-core::table>` (adds check-all in header)
2. `<input type="checkbox" class="form-check-input row-checkbox" value="{{ $record->id }}">` in each row
3. `<x-core::table.bulk-actions>` with appropriate action buttons

### Standard Bulk Action Buttons Template

```blade
<x-core::table.bulk-actions>
  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bulk-action="status" data-bulk-status="approved">
    <i class="fa-solid fa-check me-1"></i> Approve
  </button>
  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bulk-action="status" data-bulk-status="cancelled">
    <i class="fa-solid fa-ban me-1"></i> Cancel
  </button>
  <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="delete">
    <i class="fa-solid fa-trash me-1"></i> Delete
  </button>
</x-core::table.bulk-actions>
```

---

## Phase 3 — Module-Specific Bulk Actions

**Goal:** Add specialized bulk actions that require business logic beyond simple status changes.

### 3.1 Sale Module

| Action | Button | Backend Logic |
|--------|--------|--------------|
| Bulk Cancel | `data-bulk-status="cancelled"` | Calls `SaleService::cancelSale()` per record — reverses stock, payments, journal |
| Bulk Print | `data-bulk-action="print"` | Opens multi-print view with all selected invoices |

### 3.2 Purchase Module

| Action | Button | Backend Logic |
|--------|--------|--------------|
| Bulk Approve | `data-bulk-status="approved"` | Calls `PurchaseService::approve()` per record |
| Bulk Cancel | `data-bulk-status="cancelled"` | Calls `PurchaseService::cancel()` per record |

### 3.3 Expense Module

| Action | Button | Backend Logic |
|--------|--------|--------------|
| Bulk Approve | `data-bulk-status="approved"` | Calls `ExpenseService::approve()` per record |
| Bulk Reject | `data-bulk-status="rejected"` | Calls `ExpenseService::reject()` per record (needs reason) |
| Bulk Mark Paid | `data-bulk-status="paid"` | Calls `ExpenseService::markPaid()` per record |

### 3.4 Payroll Module

| Action | Button | Backend Logic |
|--------|--------|--------------|
| Bulk Approve | `data-bulk-status="approved"` | Calls `PayrollService::approvePayroll()` per record |
| Bulk Cancel | `data-bulk-status="cancelled"` | Calls `PayrollService::cancelPayroll()` per record |

### 3.5 Inventory — Stock Adjustments

| Action | Button | Backend Logic |
|--------|--------|--------------|
| Bulk Approve | `data-bulk-status="approved"` | Calls `InventoryService::approveAdjustment()` per record |
| Bulk Cancel | `data-bulk-status="cancelled"` | Calls `InventoryService::cancelAdjustment()` per record |

### 3.6 Inventory — Stock Transfers

| Action | Button | Backend Logic |
|--------|--------|--------------|
| Bulk Ship | `data-bulk-status="in_transit"` | Calls `InventoryService::shipTransfer()` per record |
| Bulk Cancel | `data-bulk-status="cancelled"` | Calls `InventoryService::cancelTransfer()` per record |

### 3.7 Master Modules (Customer, Supplier, Product, Employee)

| Action | Button | Backend Logic |
|--------|--------|--------------|
| Set Active | `data-bulk-status="active"` | Simple status update (OK for master data) |
| Set Inactive | `data-bulk-status="inactive"` | Simple status update |
| Bulk Delete | `data-bulk-action="delete"` | Calls service `delete()` per record (with guards) |

---

## Phase 4 — Enhance Bulk Action UX

### 4.1 Progress Feedback

Show a progress indicator when processing many records:
```javascript
function submitBulkAction(url, data) {
    showToast('Processing ' + data.ids.length + ' items...', 'info');
    // AJAX instead of form submit for better UX
}
```

### 4.2 Result Summary

After bulk action, show detailed summary:
```
3 of 5 items processed successfully.
2 failed:
  - #PR-2026-0004: Cannot cancel — stock already reversed
  - #PR-2026-0005: Cannot delete — not in draft status
```

### 4.3 Selective Actions Based on Status

Disable/hide bulk action buttons based on selected items' statuses:
- If all selected are draft → show Approve, Delete
- If all selected are approved → show Cancel
- If mixed → show only universally applicable actions

---

## Implementation Priority

| Phase | Scope | Est. Files | Priority |
|-------|-------|-----------|----------|
| **Phase 1** | Rewrite BulkActionController with service delegation | 1 file | Highest |
| **Phase 2** | Add bulk action buttons to 11 views | ~11 files | High |
| **Phase 3** | Wire module-specific actions in controller | 1 file | High |
| **Phase 4** | UX enhancements (progress, summary, smart buttons) | 2 files | Medium |

---

## Technical Notes

### How Bulk Actions Flow

```
1. User selects checkboxes (row-checkbox with value=record_id)
2. Clicks bulk action button [data-bulk-action="delete|status"]
3. JS collects IDs, derives module from URL
4. Submits form POST to /bulk/{module}/delete or /bulk/{module}/status
5. BulkActionController resolves module → service
6. Loops through IDs, calls service method per record
7. Catches per-record errors, builds summary
8. Redirects back with success/error message
```

### Safe Bulk Operations

Bulk actions MUST use service methods (not raw queries) to ensure:
- Stock adjustments are reversed
- Payments are cleaned up
- Journal entries are voided
- Supplier/customer balances are updated
- Activity logs are created
- Deletion guards are enforced
