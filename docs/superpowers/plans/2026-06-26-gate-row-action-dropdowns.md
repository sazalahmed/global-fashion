# Gate Row Action Dropdowns Implementation Plan

> Extends the RBAC enforcement work. Backend is already enforced; this is UI hiding for the per-row **Actions** dropdowns on list pages.

**Goal:** In every list page's row Actions menu (the three-dot dropdown), gate each item by its own permission, and hide the entire dropdown when the user can perform none of its items — so a user never sees an action that 403s on click (per screenshot: a Manager seeing "Barcode"/"Stock History" they can't use).

**Key rule — items can cross permission groups.** A dropdown item maps to the permission of the *action it performs*, NOT the list's group. E.g. on the Products list, `Barcode` → `barcode.view`, `Stock History` → `inventory.view`, even though the page is `products`.

## The pattern

1. Wrap **each** `dropdown-item` (or its `<li>`) in `@bpCan('<permission>')...@endbpCan`.
2. Wrap the **whole** Actions control (the three-dot toggle button **and** its `<ul class="dropdown-menu">`) in `@bpCanAny(<every permission used by the items>)...@endbpCanAny`, so the three-dot button disappears entirely when the user has none.

```blade
@bpCanAny('products.view','products.edit','products.create','products.delete','barcode.view','inventory.view')
<div class="dropdown">
  <button class="..." data-bs-toggle="dropdown">⋮</button>
  <ul class="dropdown-menu dropdown-menu-end">
    @bpCan('products.view')
    <li><a class="dropdown-item" href="{{ route('products.show',$product->id) }}"><i class="fa-solid fa-eye"></i> View</a></li>
    @endbpCan
    @bpCan('products.view')
    <li><a class="dropdown-item" href="{{ route('storefront.shop.show',$product->slug) }}" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Public View</a></li>
    @endbpCan
    @bpCan('products.edit')
    <li><a class="dropdown-item" href="{{ route('products.edit',$product->id) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
    @endbpCan
    @bpCan('products.create')
    <li><form action="{{ route('products.duplicate',$product->id) }}" method="POST">@csrf<button class="dropdown-item"><i class="fa-solid fa-copy"></i> Duplicate</button></form></li>
    @endbpCan
    @bpCan('barcode.view')
    <li><a class="dropdown-item" href="{{ route('barcode.index',['product_id'=>$product->id]) }}"><i class="fa-solid fa-barcode"></i> Barcode</a></li>
    @endbpCan
    @bpCan('inventory.view')
    <li><a class="dropdown-item" href="{{ route('inventory.stock-ledger',['product_id'=>$product->id]) }}"><i class="fa-solid fa-clock-rotate-left"></i> Stock History</a></li>
    @endbpCan
    @bpCan('products.delete')
    <li><form action="{{ route('products.destroy',$product->id) }}" method="POST" class="delete-form">@csrf @method('DELETE')<button class="dropdown-item text-danger delete-confirm" data-name="{{ $product->name }}"><i class="fa-solid fa-trash"></i> Delete</button></form></li>
    @endbpCan
  </ul>
</div>
@endbpCanAny
```

## Item → permission mapping (apply by what the item DOES)

| Dropdown item | Permission |
|---|---|
| View / Details / Show | `<group>.view` |
| Public View (storefront link) | `<group>.view` |
| Edit | `<group>.edit` |
| Duplicate | `<group>.create` |
| Delete | `<group>.delete` |
| Print / PDF / Label | `<group>.view` |
| **Barcode** | `barcode.view` |
| **Stock History / Stock Ledger** | `inventory.view` |
| Ledger (customer/supplier) | `<group>.view` |
| Approve (purchases) | `purchases.approve`; (others) `<group>.edit` |
| Cancel / Complete / Status / Send / Convert-state | `<group>.edit` |
| Convert to Sale (quotation) | `quotations.create` |
| **Pay / Record Payment / Collect** | `payments.create` |
| Assign / Send to Courier | `sales.edit` |
| Make payment (AdSpend) | `marketing.create` |

`<group>` = the module's umbrella group used elsewhere (sales, products, customers, suppliers, purchases, finance, accounting, hr, marketing, manufacturing, ecommerce, users, roles, settings, payments, inventory, etc.).

**Wrapper permission set** = the distinct set of all item permissions in that dropdown. If after gating a dropdown would only ever contain items from one permission (e.g. only `<group>.view`), the wrapper is just `@bpCanAny('<group>.view')` (still correct — hides when no view perm).

## Global constraints
- Directives must stay balanced (`@bpCan`/`@endbpCan`, `@bpCanAny`/`@endbpCanAny`).
- Only wrap — never alter markup, routes, classes, JS hooks (`delete-confirm`, `toggle-status`, reorder handles).
- Do NOT gate non-action controls (filter submit, pagination, column sorters, row checkboxes, reorder drag handles, "Back").
- `php artisan view:cache` must compile after each batch.
- Reuse existing `@bpCan`/`@bpCanAny` (already registered). Many dropdowns already gate Edit/Delete from the earlier batch — augment, don't duplicate.
- Skip: `Dashboard` (quick-action shortcuts, not entity actions — verify), `Branch` & `AiAssistant` (ungated modules), `Core/components/export-dropdown.blade.php` & `export-menu.blade.php` (export-format menus, gated at call sites), storefront/website views.

---

## Task 1: Reference implementation — Products list (do first, it's the screenshot case)

**File:** `Modules/Product/resources/views/index.blade.php`

- [ ] Gate the ungated dropdown items: `View`→`products.view`, `Public View`→`products.view`, `Barcode`→`barcode.view`, `Stock History`→`inventory.view` (Edit/Duplicate/Delete already gated).
- [ ] Wrap the row dropdown (toggle button + `<ul class="dropdown-menu">`, around lines 165-203) in `@bpCanAny('products.view','products.create','products.edit','products.delete','barcode.view','inventory.view')`.
- [ ] `php artisan view:cache` → OK; balance check.
- [ ] Commit: `fix(security): gate Products row action dropdown items by permission`.
- [ ] Manual check: as a role without `barcode.view`/`inventory.view`, those items are hidden; as a role with zero product/barcode/inventory perms, the three-dot button is gone.

## Task 2: Catalog lists
**Files:** `Brand/index`, `Category/index`, `Unit/index`, `Variant/index`, `Inventory/index`, `Inventory/adjustments`.
Per file: gate each dropdown item per the mapping (mostly `<group>.view/edit/delete`; Inventory adjustments: approve/cancel→`inventory.edit`), wrap dropdown in `@bpCanAny(<group perms>)`. Commit `fix(security): gate Catalog/Inventory row action dropdowns`.

## Task 3: Sales & Customer lists
**Files:** `Sale/index` (10 items — view/print/pdf/label→sales.view, edit→sales.edit, status/assign/send-courier→sales.edit, delete→sales.delete), `SaleReturn/index`, `Quotation/index` (convert→quotations.create, send→quotations.edit), `Customer/index`.
Wrap each dropdown in `@bpCanAny(...)`. Commit `fix(security): gate Sales/Customer row action dropdowns`.

## Task 4: Purchasing, Supplier, Payment lists
**Files:** `Purchase/index` (approve→purchases.approve), `PurchaseReturn/index`, `Supplier/index` (+ `Supplier/show` Pay→`payments.create`), `Payment/index`, `Payment/accounts/index` (edit-like→`payments.create`, delete→`payments.delete`).
Commit `fix(security): gate Purchasing/Payment row action dropdowns`.

## Task 5: Finance & Accounting lists
**Files:** `Expense/index`, `Expense/categories/index`, `Asset/index`, `Loan/lenders/index` → group `finance`. `Accounting/chart-of-accounts`, `credit-notes/index`, `debit-notes/index`, `journal-entries` → group `accounting` (post/void/issue/cancel→`accounting.edit`).
Commit `fix(security): gate Finance/Accounting row action dropdowns`.

## Task 6: HR & Marketing lists
**Files:** `Employee/index`, `Attendance/leave` (approve/reject→hr.edit), `Payroll/salary-structure` → group `hr`. `AdSpend/index`, `Marketing/email`, `Marketing/sms-campaigns` (send/duplicate→marketing.edit/create) → group `marketing`.
Commit `fix(security): gate HR/Marketing row action dropdowns`.

## Task 7: Manufacturing lists
**Files:** `Manufacturing/{catalogs,factories,production-orders,raw-materials,rm-purchases,suppliers}/index` → group `manufacturing` (approve/cancel/complete→`manufacturing.edit`).
Commit `fix(security): gate Manufacturing row action dropdowns`.

## Task 8: Ecommerce lists
**Files:** `Ecommerce/{banners,blog-categories,blog-comments,blog-posts,collections,coupons,flash-deals}.blade.php` → group `ecommerce` (toggle/approve-comment→ecommerce.edit, delete→ecommerce.delete).
Commit `fix(security): gate Ecommerce row action dropdowns`.

## Task 9: System lists
**Files:** `Security/users/index` (edit→users.edit, delete/toggle→users.edit/delete), `Security/roles` (edit→roles.edit, delete→roles.delete), `Security/api-keys` (delete→users.delete). Skip Branch (ungated module) and Dashboard (verify it's quick-actions, not entity CRUD; if it is CRUD, gate accordingly).
Commit `fix(security): gate System row action dropdowns`.

## Task 10: Verify
- [ ] `php artisan view:cache` compiles; global `@bpCan`/`@bpCanAny` balance across all module views.
- [ ] Coverage scan: no `dropdown-item` with `.destroy'`/`barcode.`/`stock-ledger`/`route('payments.create'` left without an enclosing `@bpCan`.
- [ ] Re-run `php artisan test --filter=PermissionEnforcementTest` (still green — backend unaffected).
- [ ] Manual: log in as Manager/Cashier/Sales Rep; open a few list Actions menus; confirm only permitted items show and the three-dot hides when none apply.

## Execution note
Each task is one commit, balance-verified + `view:cache`-checked. Best run as subagent batches (one per task) — same approach as the earlier view-gating batches. The item→permission mapping table is the single source of truth; when a dropdown item's target route is ambiguous, map by the route name's module + action.
