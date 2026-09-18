# Billing & Shipping Addresses Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let each customer store separate free-text Billing and Shipping addresses, and let Order (Sale) and Quotation forms display/edit them per transaction (prefilled from the customer, overridable, saved on the record).

**Architecture:** Customer-level addresses live on `customers.address` (billing) and `customers.shipping_address` (shipping). On the Order form the existing `customer_address` field IS the shipping/delivery address (courier uses it) — it is relabelled "Shipping Address"; a new `sales.billing_address` column adds billing. Quotations get two fresh columns. Customer values prefill the order/quotation fields via `data-*` attributes on the customer `<option>` and are overridable + saved per record.

**Tech Stack:** Laravel 12, Blade, jQuery, MySQL. Modular (nWidart) under `Modules/`.

## Global Constraints

- Currency/format, no-inline-CSS, `'use strict';` in every `<script>`, named routes, dark-mode overrides for any new CSS — per project CLAUDE.md.
- Addresses are **single free-text lines** (no per-address district/upazila).
- Money/schema: `text` nullable columns for addresses; never break the courier flow that reads `sales.customer_address`.
- **Verification style (this codebase has no PHPUnit suite for these modules):** validate with `php -l`, `php artisan tinker --execute=...`, `php artisan view:cache` (blade compile), and HTTP smoke tests using the documented admin login (`admin@gmail.com` / `1234`). Note the login route is rate-limited (`throttle:5,15`) — prefer tinker for repeated checks.

---

## Task 0: Reset the superseded read-only address-panel work

The previous (uncommitted) read-only "customer-address-panel" attempt is superseded by this editable design. Start from a clean tree so nothing half-built lingers.

**Files:**
- Delete: `Modules/Core/resources/views/components/customer-address-panel.blade.php`
- Revert (to `main`): `Modules/Sale/app/Http/Controllers/SaleController.php`, `Modules/Quotation/app/Http/Controllers/QuotationController.php`, `Modules/Sale/resources/views/create.blade.php`, `Modules/Sale/resources/views/edit.blade.php`, `Modules/Quotation/resources/views/create.blade.php`, `Modules/Quotation/resources/views/edit.blade.php`, `public/css/style.css`

- [ ] **Step 1: Confirm the panel work is uncommitted** — `git status --short` shows the above modified/untracked; none are on `main`.
- [ ] **Step 2: Revert tracked files** — `git checkout -- Modules/Sale/... Modules/Quotation/... public/css/style.css` (the 7 tracked files above).
- [ ] **Step 3: Delete the untracked component** — `rm Modules/Core/resources/views/components/customer-address-panel.blade.php`
- [ ] **Step 4: Verify clean** — `git status --short` prints nothing.

---

## Task 1: Customer — make Billing + Shipping editable

**Files:**
- Modify: `Modules/Customer/app/Models/Customer.php` (add `shipping_address` to `$fillable`)
- Modify: `Modules/Customer/app/Http/Requests/StoreCustomerRequest.php`, `UpdateCustomerRequest.php` (add rule)
- Modify: `Modules/Customer/resources/views/create.blade.php`, `edit.blade.php` (relabel `address` → "Billing Address"; add "Shipping Address" field)

**Interfaces:**
- Produces: a `customers.shipping_address` value that is now writable and read as `$customer->shipping_address`.

- [ ] **Step 1: Add to fillable** — in `Customer.php` `$fillable`, add `'shipping_address'` right after `'address'`.
- [ ] **Step 2: Add validation** — in both request classes, after the `'address'` rule add:
```php
'shipping_address'  => 'nullable|string|max:500',
```
- [ ] **Step 3: Relabel billing + add shipping field** — in `create.blade.php`, change the address field label to `{{ __('Billing Address') }}`, then add below it:
```blade
<div class="col-md-6">
    <label class="bp-form-label">{{ __('Shipping Address') }}</label>
    <input type="text" class="bp-form-control" name="shipping_address"
        value="{{ old('shipping_address') }}" placeholder="{{ __('Leave blank if same as billing') }}">
</div>
```
- [ ] **Step 4: Same for edit.blade.php** — relabel to "Billing Address" and add the shipping field with `value="{{ old('shipping_address', $customer->shipping_address) }}"`.
- [ ] **Step 5: Lint** — `php -l` the model + both request files → "No syntax errors".
- [ ] **Step 6: Verify persistence via tinker**:
```
php artisan tinker --execute="\$c=\Modules\Customer\Models\Customer::first();\$c->shipping_address='TEST SHIP';\$c->save();echo \Modules\Customer\Models\Customer::find(\$c->id)->shipping_address;"
```
Expected: prints `TEST SHIP`. Then reset it to null.
- [ ] **Step 7: Commit** — `git commit -m "feat(customer): editable billing + shipping address"`

---

## Task 2: Migrations — add address columns to sales & quotations

**Files:**
- Create: `Modules/Sale/database/migrations/2026_07_22_000001_add_billing_address_to_sales_table.php`
- Create: `Modules/Quotation/database/migrations/2026_07_22_000002_add_addresses_to_quotations_table.php`

**Interfaces:**
- Produces: `sales.billing_address` (text, nullable); `quotations.billing_address` + `quotations.shipping_address` (text, nullable).

- [ ] **Step 1: Sale migration** — `php artisan make:migration add_billing_address_to_sales_table --path=Modules/Sale/database/migrations` then set:
```php
public function up(): void {
    Schema::table('sales', function (Blueprint $table) {
        if (!Schema::hasColumn('sales', 'billing_address')) {
            $table->text('billing_address')->nullable()->after('customer_address');
        }
    });
}
public function down(): void {
    Schema::table('sales', fn (Blueprint $t) => $t->dropColumn('billing_address'));
}
```
- [ ] **Step 2: Quotation migration** — similar; add `billing_address` and `shipping_address` (both `->text()->nullable()`), each guarded by `hasColumn`. `down()` drops both.
- [ ] **Step 3: Run** — `php artisan migrate` → both migrations "DONE".
- [ ] **Step 4: Verify columns** — `SHOW COLUMNS FROM sales LIKE 'billing_address'` and `SHOW COLUMNS FROM quotations LIKE '%_address'` return the new columns.
- [ ] **Step 5: Commit** — `git commit -m "feat(sale,quotation): add billing/shipping address columns"`

---

## Task 3: Sale — billing/shipping fields, prefill, save

**Files:**
- Modify: `Modules/Sale/app/Models/Sale.php` (`$fillable` += `billing_address`)
- Modify: `Modules/Sale/app/Http/Controllers/SaleController.php:80,288` (customer select columns)
- Modify: `Modules/Sale/app/Http/Requests/StoreSaleRequest.php`, `UpdateSaleRequest.php` (validation)
- Modify: `Modules/Sale/resources/views/create.blade.php`, `edit.blade.php` (relabel Full Address → Shipping Address; add Billing field; `data-*` on options; prefill JS)

**Interfaces:**
- Consumes: `sales.billing_address` (Task 2), `customers.shipping_address` (Task 1).
- Produces: saved `sale.billing_address` + `sale.customer_address` (shipping) on store/update.

- [ ] **Step 1: Sale fillable** — add `'billing_address'` to `Sale::$fillable`. (Confirm `customer_address` already fillable — it is, since the form saves it today.)
- [ ] **Step 2: Controller customer columns** — change both `Customer::active()->get([...])` calls to include `'address', 'shipping_address'`:
```php
$customers = Customer::active()->get(['id', 'name', 'phone', 'address', 'shipping_address']);
```
- [ ] **Step 3: Validation** — in Store + Update SaleRequest `rules()` add:
```php
'billing_address'  => 'nullable|string|max:1000',
'customer_address' => 'nullable|string|max:1000',
```
(add `customer_address` only if not already present — check first).
- [ ] **Step 4: Options `data-*`** — on each customer `<option>` in create & edit add:
```blade
data-billing="{{ $customer->address ?? '' }}"
data-shipping="{{ $customer->shipping_address ?? '' }}"
```
- [ ] **Step 5: Relabel + add fields** — find the "Full Address" (`name="customer_address"`) field; relabel its `<label>` to `{{ __('Shipping Address') }}`. Add a Billing Address field near it:
```blade
<label class="bp-form-label">{{ __('Billing Address') }}</label>
<textarea class="bp-form-control" name="billing_address" rows="2"
    placeholder="{{ __('Billing address') }}">{{ old('billing_address', $sale->billing_address ?? '') }}</textarea>
```
(create uses `old('billing_address')`; edit uses `old('billing_address', $sale->billing_address)`.)
- [ ] **Step 6: Prefill JS** — in the existing `#customerSelect` change handler (both files), prefill only when the target is empty (don't clobber an edited value):
```javascript
var billing = $opt.data('billing') || '';
var shipping = $opt.data('shipping') || $opt.data('billing') || '';
if (billing && !$('textarea[name="billing_address"]').val()) $('textarea[name="billing_address"]').val(billing);
if (shipping && !$('#customerAddress').val()) $('#customerAddress').val(shipping);
```
Confirm the shipping field's id/selector matches the existing Full-Address control (`#customerAddress`).
- [ ] **Step 7: Lint + blade compile** — `php -l` model/requests/controller; `php artisan view:cache` succeeds; `php artisan view:clear`.
- [ ] **Step 8: HTTP smoke — save round-trip** — create a sale via curl (documented flow) posting `billing_address=BILL-X` and `customer_address=SHIP-Y`; then tinker: `Sale::latest()->first()->only(['billing_address','customer_address'])` shows `BILL-X` / `SHIP-Y`.
- [ ] **Step 9: Commit** — `git commit -m "feat(sale): billing + shipping address on order form"`

---

## Task 4: Quotation — billing/shipping fields, prefill, save

**Files:**
- Modify: `Modules/Quotation/app/Models/Quotation.php` (`$fillable` += both)
- Modify: `Modules/Quotation/app/Http/Controllers/QuotationController.php` (customer select columns — reuse Task-1 columns)
- Modify: `Modules/Quotation/app/Http/Requests/StoreQuotationRequest.php` (validation)
- Modify: `Modules/Quotation/app/Services/QuotationService.php` (persist billing/shipping in create + update)
- Modify: `Modules/Quotation/resources/views/create.blade.php`, `edit.blade.php` (fields, `data-*`, prefill JS)

**Interfaces:**
- Consumes: `quotations.billing_address` + `shipping_address` (Task 2), `customers.address`/`shipping_address` (Task 1).
- Produces: saved `quotation.billing_address` + `quotation.shipping_address`.

- [ ] **Step 1: Fillable** — add `'billing_address', 'shipping_address'` to `Quotation::$fillable`.
- [ ] **Step 2: Controller columns** — customer `get([...])` includes `'address','shipping_address'` (already done if Task-1 pattern reused; else add).
- [ ] **Step 3: Validation** — in `StoreQuotationRequest::rules()` add both:
```php
'billing_address'  => 'nullable|string|max:1000',
'shipping_address' => 'nullable|string|max:1000',
```
- [ ] **Step 4: Service persistence** — in `QuotationService::create()` and `update()`, ensure `billing_address`/`shipping_address` from `$validated` are written to the model (they flow through `$validated` after `unset($validated['items'])`; confirm the create/update array includes them — since fillable now allows them and they're in `$validated`, a `Quotation::create($validated)` / `$quotation->update($validated)` picks them up automatically. Verify the service passes the full `$validated`).
- [ ] **Step 5: View fields + `data-*` + prefill JS** — mirror Task 3 (Steps 4–6) in both quotation blades: `data-billing`/`data-shipping` on the `<x-core::select2>` options; add Billing + Shipping textareas (`name="billing_address"`, `name="shipping_address"`); prefill in the `#customer_id` change handler when empty. Edit uses `old(..., $quotation->billing_address)` etc.
- [ ] **Step 6: Lint + blade compile** — `php -l`; `php artisan view:cache`; `php artisan view:clear`.
- [ ] **Step 7: HTTP smoke — save round-trip** — create a quotation via curl with `billing_address`/`shipping_address`; tinker confirms they persisted.
- [ ] **Step 8: Commit** — `git commit -m "feat(quotation): billing + shipping address on quotation form"`

---

## Task 5: Final verification sweep

**Files:** none (verification only)

- [ ] **Step 1: Route render sweep** — with a single login, GET `sales/create`, `sales/{id}/edit`, `quotations/create`, `quotations/{id}/edit`, `customers/create`, `customers/{id}/edit` → all HTTP 200.
- [ ] **Step 2: Prefill sanity (tinker)** — pick a customer with distinct billing vs shipping; confirm `data-billing`/`data-shipping` render on that option in `sales/create` HTML.
- [ ] **Step 3: Courier flow intact** — confirm `sales.customer_address` still saves and the send-to-courier path reads it (unchanged column).
- [ ] **Step 4: Commit any doc updates** if needed.

---

## Notes on reconciliation
- **Order Shipping = `sales.customer_address`** (existing delivery field, courier-integrated). We only *relabel* it and add prefill; no new shipping column on sales.
- **Order Billing = new `sales.billing_address`**.
- **Quotation** has no existing address, so it gets both new columns.
- Prefill never overwrites a value the user already typed (empty-check before setting).
