# Roles & Permissions Enforcement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enforce RBAC across the BizPOS admin app — hide UI by permission and block direct-URL access via a guard call at the top of every gated controller method — and clean up the permission catalog.

**Architecture:** Controller-body checks (matching the reference project `quickshifter-inventory`). A global `bpAuthorize('group.action')` helper throws `PermissionDeniedException` (rendered as redirect-back for web / JSON 403 for API). UI hiding reuses the existing `@bpCan` Blade directive plus a new `bpCanAny()` helper. Super Admin bypasses everything via `Gate::before`.

**Tech Stack:** Laravel 12, PHP 8.2, spatie/laravel-permission ^7.2 (already installed + middleware aliased), Blade, nwidart/laravel-modules.

## Global Constraints

- Permission names use dot notation `group.action`, guard `web` — copied verbatim from the catalog in §4 of the design spec.
- Spatie middleware aliases (`role`, `permission`) already exist in `bootstrap/app.php` — do NOT re-add. We are NOT using route middleware for enforcement; checks live in controller method bodies.
- `app/Helpers/PermissionHelper.php` is already autoloaded via `composer.json` `autoload.files` and already defines `checkUserHasPermission()`, `checkUserHasRole()`, `isSuperAdmin()`. Add new helpers to THIS file (do not create a new autoloaded file).
- The `@bpCan` and `@superAdmin` Blade directives are already registered in `app/Providers/AppServiceProvider.php::registerBladeDirectives()`.
- The Spatie `permissions` table already has a `group_name` column (see `Modules/Security/database/migrations/2026_03_13_000001_create_permission_tables.php`). No migration needed.
- Never gate: auth/login/logout, `dashboard`, self-service `security.profile*` + `security.change-password*`, `notifications.*`, `search*`, storefront routes, settings deep-link tab anchors.
- Test/login creds: `admin@gmail.com` / `1234` (Super Admin). Dev server: `php artisan serve --host=127.0.0.1 --port=8000`. Admin login URL: `/admin/login`.
- Follow CLAUDE.md: thin controllers, named routes only, `'use strict';` in JS, no inline CSS.
- Commit after every task.

## Permission → method mapping convention

Used throughout the backend tasks. When a method isn't in a task's override table, apply this convention by route/method name:

| Method name pattern | Permission action |
|---|---|
| `index`, `show`, `list`, `search*`, `*Index`, `report*`, `ledger`, `advances`, `print`, `pdf`, `analytics`, `dashboard`, `*Show`, `summary`, `balance*`, `*ByDistrict` | `.view` |
| `create`, `store`, `quickStore`, `*Create`, `*Store`, `duplicate`, `generate*`, `import`, `start` | `.create` |
| `edit`, `update`, `*Edit`, `*Update`, `toggleStatus`, `toggle*`, `reorder*`, `*active`, `assign`, `post`, `issue`, `void`, `match`, `unmatch`, `save*`, `reschedule`, `approve`*, `complete`, `cancel`, `reject`, `markPaid`, `recordPayment`, `send`, `sync`, `*Approve`, `*Reject` | `.edit` |
| `destroy`, `*Destroy`, `delete`, `clear`, `bulkDelete` | `.delete` |
| `export` | `.export` |

*`approve` → `.approve` ONLY for `purchases` (the only group with an `approve` permission). Everywhere else `approve`/`complete`/`cancel` → `.edit`.

---

## Phase A — Foundation

### Task 1: Clean up the permission catalog

**Files:**
- Modify: `app/Traits/PermissionsTrait.php` (replace the `permissionGroups()` array body, lines 11-153)
- Test: `tests/Unit/PermissionCatalogTest.php` (create)

**Interfaces:**
- Produces: `PermissionsTrait::permissionGroups(): array`, `getAllDefinedPermissions(): array`, `getPermissionGroups(): array`, `getPermissionsByGroup(string): array` (signatures unchanged).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Unit/PermissionCatalogTest.php
namespace Tests\Unit;

use App\Traits\PermissionsTrait;
use PHPUnit\Framework\TestCase;

class PermissionCatalogTest extends TestCase
{
    use PermissionsTrait;

    public function test_catalog_has_expected_groups_and_drops_dead_ones(): void
    {
        $groups = self::getPermissionGroups();

        // Newly added
        $this->assertContains('units', $groups);
        $this->assertContains('variants', $groups);
        $this->assertContains('barcode', $groups);
        $this->assertContains('payments', $groups);
        $this->assertContains('manufacturing', $groups);
        $this->assertContains('locations', $groups);
        $this->assertContains('activities', $groups);

        // Removed
        $this->assertNotContains('pos', $groups);
        $this->assertNotContains('grn', $groups);
        $this->assertNotContains('purchase_returns', $groups);
        $this->assertNotContains('branches', $groups);

        // Spot-check a permission name format
        $this->assertContains('barcode.generate', self::getAllDefinedPermissions());
        $this->assertContains('activities.delete', self::getAllDefinedPermissions());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PermissionCatalogTest`
Expected: FAIL (pos/grn still present, new groups missing).

- [ ] **Step 3: Replace the `permissionGroups()` array**

Replace the entire array returned in `permissionGroups()` (the `return [ ... ];` block) with:

```php
return [
    'dashboard' => ['dashboard.view'],
    'products' => ['products.view', 'products.create', 'products.edit', 'products.delete', 'products.export'],
    'categories' => ['categories.view', 'categories.create', 'categories.edit', 'categories.delete'],
    'brands' => ['brands.view', 'brands.create', 'brands.edit', 'brands.delete'],
    'units' => ['units.view', 'units.create', 'units.edit', 'units.delete'],
    'variants' => ['variants.view', 'variants.create', 'variants.edit', 'variants.delete'],
    'barcode' => ['barcode.view', 'barcode.generate'],
    'inventory' => ['inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete', 'inventory.export'],
    'purchases' => ['purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete', 'purchases.approve', 'purchases.export'],
    'sales' => ['sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.export'],
    'quotations' => ['quotations.view', 'quotations.create', 'quotations.edit', 'quotations.delete'],
    'customers' => ['customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.export'],
    'suppliers' => ['suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete', 'suppliers.export'],
    'payments' => ['payments.view', 'payments.create', 'payments.delete'],
    'finance' => ['finance.view', 'finance.create', 'finance.edit', 'finance.delete', 'finance.export'],
    'accounting' => ['accounting.view', 'accounting.create', 'accounting.edit', 'accounting.delete', 'accounting.export'],
    'reports' => ['reports.view', 'reports.export'],
    'ecommerce' => ['ecommerce.view', 'ecommerce.create', 'ecommerce.edit', 'ecommerce.delete'],
    'marketing' => ['marketing.view', 'marketing.create', 'marketing.edit', 'marketing.delete'],
    'hr' => ['hr.view', 'hr.create', 'hr.edit', 'hr.delete', 'hr.export'],
    'manufacturing' => ['manufacturing.view', 'manufacturing.create', 'manufacturing.edit', 'manufacturing.delete'],
    'locations' => ['locations.view', 'locations.create', 'locations.edit', 'locations.delete'],
    'activities' => ['activities.view', 'activities.delete'],
    'users' => ['users.view', 'users.create', 'users.edit', 'users.delete'],
    'roles' => ['roles.view', 'roles.create', 'roles.edit', 'roles.delete'],
    'settings' => ['settings.view', 'settings.edit'],
];
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PermissionCatalogTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Traits/PermissionsTrait.php tests/Unit/PermissionCatalogTest.php
git commit -m "feat(security): clean up permission catalog (drop pos/grn, add units/variants/etc.)"
```

---

### Task 2: Add `bpAuthorize()` and `bpCanAny()` helpers

**Files:**
- Modify: `app/Helpers/PermissionHelper.php` (append two functions)
- Test: `tests/Feature/PermissionHelperTest.php` (create)

**Interfaces:**
- Produces:
  - `bpCanAny(string ...$permissions): bool` — true if user has ANY of the given permissions.
  - `bpAuthorize(string $permission): void` — throws `\App\Exceptions\PermissionDeniedException` if the current user lacks `$permission`. (Depends on Task 3's exception — create Task 3 first or in the same branch; this task's test asserts the throw.)

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/PermissionHelperTest.php
namespace Tests\Feature;

use App\Exceptions\PermissionDeniedException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_bp_authorize_throws_when_user_lacks_permission(): void
    {
        Permission::findOrCreate('products.view', 'web');
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->expectException(PermissionDeniedException::class);
        bpAuthorize('products.view');
    }

    public function test_bp_can_any_true_when_user_has_one(): void
    {
        Permission::findOrCreate('products.view', 'web');
        Permission::findOrCreate('products.create', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('products.create');
        $this->actingAs($user);

        $this->assertTrue(bpCanAny('products.view', 'products.create'));
        $this->assertFalse(bpCanAny('sales.view', 'sales.create'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PermissionHelperTest`
Expected: FAIL ("Call to undefined function bpAuthorize" / class not found).

- [ ] **Step 3: Append helpers to `app/Helpers/PermissionHelper.php`**

Add at the end of the file (after the `isSuperAdmin` block):

```php
if (!function_exists('bpCanAny')) {
    /**
     * True if the authenticated user has ANY of the given permissions.
     * Used in Blade to decide whether to render a whole menu group.
     */
    function bpCanAny(string ...$permissions): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bpAuthorize')) {
    /**
     * Throw a PermissionDeniedException unless the authenticated user has the
     * given permission. Call as the first line of a gated controller method.
     */
    function bpAuthorize(string $permission): void
    {
        if (!checkUserHasPermission($permission)) {
            throw new \App\Exceptions\PermissionDeniedException();
        }
    }
}
```

- [ ] **Step 4: Run test (will still fail until Task 3 exists)**

Run: `php artisan test --filter=PermissionHelperTest`
Expected: still FAIL (PermissionDeniedException class missing) — proceed to Task 3, then re-run.

- [ ] **Step 5: Commit**

```bash
git add app/Helpers/PermissionHelper.php tests/Feature/PermissionHelperTest.php
git commit -m "feat(security): add bpAuthorize and bpCanAny helpers"
```

---

### Task 3: Create `PermissionDeniedException`

**Files:**
- Create: `app/Exceptions/PermissionDeniedException.php`

**Interfaces:**
- Produces: `\App\Exceptions\PermissionDeniedException` with a `render($request)` method (Laravel auto-invokes it; no `bootstrap/app.php` change required).

- [ ] **Step 1: Create the exception**

```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PermissionDeniedException extends Exception
{
    /**
     * Render the exception: JSON 403 for API/AJAX, redirect-back with a flash
     * error for normal web requests.
     */
    public function render($request): JsonResponse|RedirectResponse
    {
        $message = __('Permission denied — you are not allowed to perform this action.');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}
```

- [ ] **Step 2: Run the Task 2 helper test (now passes)**

Run: `php artisan test --filter=PermissionHelperTest`
Expected: PASS (both tests).

- [ ] **Step 3: Commit**

```bash
git add app/Exceptions/PermissionDeniedException.php
git commit -m "feat(security): add PermissionDeniedException (redirect-back / JSON 403)"
```

---

### Task 4: Super Admin `Gate::before` + `@bpCanAny` Blade directive

**Files:**
- Modify: `app/Providers/AppServiceProvider.php` (add `Gate::before` in `boot()`; register `@bpCanAny` in `registerBladeDirectives()`)
- Test: `tests/Feature/SuperAdminBypassTest.php` (create)

**Interfaces:**
- Consumes: `bpCanAny()` (Task 2), `permissionGroups()` (Task 1).
- Produces: `@bpCanAny('a','b') ... @endbpCanAny` Blade directive; Super-Admin-grants-all gate.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/SuperAdminBypassTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminBypassTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_passes_any_permission_without_explicit_grant(): void
    {
        Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        // No permissions exist at all, but Gate::before short-circuits to true.
        $this->assertTrue($user->can('anything.at.all'));
        $this->assertTrue(checkUserHasPermission('sales.delete'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SuperAdminBypassTest`
Expected: FAIL (returns false — no Gate::before yet).

- [ ] **Step 3: Add `Gate::before` to `boot()`**

In `app/Providers/AppServiceProvider.php`, add `use Illuminate\Support\Facades\Gate;` at the top, then inside `boot()` (after `$this->configureRateLimiting();`):

```php
// Super Admin bypasses all permission checks. Returning null (not false)
// for non-super-admins lets Spatie resolve the permission normally.
Gate::before(function ($user, $ability) {
    return $user->hasRole('Super Admin') ? true : null;
});
```

- [ ] **Step 4: Register the `@bpCanAny` directive**

In `registerBladeDirectives()`, after the existing `Blade::if('bpCan', ...)` block, add:

```php
// @bpCanAny('a','b') - render block if user has ANY of the given permissions.
Blade::if('bpCanAny', function (string ...$permissions) {
    return bpCanAny(...$permissions);
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=SuperAdminBypassTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Providers/AppServiceProvider.php tests/Feature/SuperAdminBypassTest.php
git commit -m "feat(security): super admin Gate::before bypass + @bpCanAny directive"
```

---

### Task 5: Update `RolePermissionSeeder` for the new catalog

**Files:**
- Modify: `Modules/Security/database/seeders/RolePermissionSeeder.php`
- Test: `tests/Feature/RolePermissionSeederTest.php` (create)

**Interfaces:**
- Consumes: `permissionGroups()` (Task 1).
- Produces: seeded roles (`Super Admin`, `Manager`, `Cashier`, `Accountant`, `Inventory Staff`, `Sales Rep`) with refreshed permission sets; prunes permissions no longer in the catalog.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/RolePermissionSeederTest.php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Security\Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_catalog_and_prunes_dead_permissions(): void
    {
        // Stale permission that must be pruned.
        Permission::findOrCreate('pos.access', 'web');

        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'manufacturing.view', 'guard_name' => 'web']);
        $this->assertDatabaseMissing('permissions', ['name' => 'pos.access']);
        $this->assertTrue(Role::where('name', 'Super Admin')->where('guard_name', 'web')->exists());

        $manager = Role::where('name', 'Manager')->first();
        $this->assertTrue($manager->hasPermissionTo('products.view'));
        $this->assertFalse($manager->hasPermissionTo('users.delete'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RolePermissionSeederTest`
Expected: FAIL (pos.access not pruned / manufacturing missing).

- [ ] **Step 3: Update the seeder**

Replace the body of `run()` with the version below. It (a) creates all catalog permissions, (b) prunes any DB permission not in the catalog, (c) re-syncs each role.

```php
public function run(): void
{
    // 1. Create all catalog permissions, grouped.
    foreach (static::permissionGroups() as $group => $permissions) {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['group_name' => $group]
            );
        }
    }

    // 2. Prune permissions no longer in the catalog (e.g. pos.*, grn.*).
    $defined = static::getAllDefinedPermissions();
    Permission::where('guard_name', 'web')->whereNotIn('name', $defined)->delete();

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    // 3. Super Admin — all permissions (Gate::before also covers it).
    $superAdmin = Role::firstOrCreate(
        ['name' => 'Super Admin', 'guard_name' => 'web'],
        ['description' => 'Full system access with all permissions', 'is_default' => false]
    );
    $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());

    // 4. Manager — operations + reports, no system/users/roles/settings.
    $manager = Role::firstOrCreate(
        ['name' => 'Manager', 'guard_name' => 'web'],
        ['description' => 'Branch management, reports, and staff oversight', 'is_default' => false]
    );
    $manager->syncPermissions(Permission::where('guard_name', 'web')->whereIn('group_name', [
        'dashboard', 'products', 'categories', 'brands', 'units', 'variants', 'barcode',
        'inventory', 'purchases', 'sales', 'quotations', 'customers', 'suppliers',
        'payments', 'finance', 'hr', 'reports', 'manufacturing',
    ])->get());

    // 5. Cashier — POS-less sales floor: sell, view products/customers.
    $cashier = Role::firstOrCreate(
        ['name' => 'Cashier', 'guard_name' => 'web'],
        ['description' => 'Sales, basic customer and product view', 'is_default' => true]
    );
    $cashier->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', [
        'dashboard.view', 'products.view', 'sales.view', 'sales.create',
        'customers.view', 'customers.create',
    ])->get());

    // 6. Accountant — money + reports.
    $accountant = Role::firstOrCreate(
        ['name' => 'Accountant', 'guard_name' => 'web'],
        ['description' => 'Finance, accounting, payments, payroll, reports', 'is_default' => false]
    );
    $accountant->syncPermissions(Permission::where('guard_name', 'web')->whereIn('group_name', [
        'dashboard', 'payments', 'finance', 'accounting', 'reports', 'hr',
    ])->get());

    // 7. Inventory Staff — catalog + stock + purchasing.
    $inventoryStaff = Role::firstOrCreate(
        ['name' => 'Inventory Staff', 'guard_name' => 'web'],
        ['description' => 'Inventory, stock adjustments, receiving', 'is_default' => false]
    );
    $inventoryStaff->syncPermissions(Permission::where('guard_name', 'web')->whereIn('group_name', [
        'dashboard', 'products', 'categories', 'brands', 'units', 'variants', 'barcode',
        'inventory', 'purchases',
    ])->get());

    // 8. Sales Rep — customer + quotation + order processing.
    $salesRep = Role::firstOrCreate(
        ['name' => 'Sales Rep', 'guard_name' => 'web'],
        ['description' => 'Customers, quotations, order processing', 'is_default' => false]
    );
    $salesRep->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', [
        'dashboard.view', 'products.view',
        'sales.view', 'sales.create', 'sales.edit',
        'quotations.view', 'quotations.create', 'quotations.edit',
        'customers.view', 'customers.create', 'customers.edit',
    ])->get());
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RolePermissionSeederTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Security/database/seeders/RolePermissionSeeder.php tests/Feature/RolePermissionSeederTest.php
git commit -m "feat(security): re-seed roles for new catalog + prune dead permissions"
```

---

## Phase B — Backend gating (controller-body checks)

**For every task below:** add `bpAuthorize('<perm>');` as the **first executable line** of each listed method (before any other logic). Use the per-controller tables. Methods not listed (e.g. `__construct`, AJAX helpers like `partySearch`, `thanasByDistrict`, `apiList`, `banks`, route-model helpers) get `.view` of their group unless the table says otherwise. Self-service and ungated methods are called out explicitly.

**Verification pattern for each task** (run once at the end of the task): start the dev server, log in as a user whose role lacks the module's permission, and confirm a gated GET route redirects instead of returning 200. Concretely, the centralized automated check is Task 23 (`PermissionEnforcementTest`); for per-task local confirmation use:

```bash
# As super admin (should pass) — replace <path> with a gated GET route:
curl -s -b /tmp/bz.txt "http://127.0.0.1:8000/admin/<path>" -o /dev/null -w "%{http_code}\n"   # 200
```

### Task 6: Product, Variant, Barcode controllers

**Files:**
- Modify: `Modules/Product/app/Http/Controllers/ProductController.php`
- Modify: `Modules/Variant/app/Http/Controllers/VariantController.php`, `VariantChartController.php`
- Modify: `Modules/Barcode/app/Http/Controllers/BarcodeController.php`

**ProductController:**

| Method(s) | Permission |
|---|---|
| index, show | products.view |
| create, store, duplicate, generateSku, generateBarcode, generateVariants, storeVariantAttributeValue | products.create |
| edit, update, toggleStatus, reorder, bulkUpdateVariants | products.edit |
| destroy, destroyVariant | products.delete |

**VariantController:** index/show → `variants.view`; create/store → `variants.create`; edit/update/toggleStatus/setValueActive → `variants.edit`; destroy → `variants.delete`.
**VariantChartController:** storeRow → `variants.create`; updateRow/reorderRows/upsertValue → `variants.edit`; destroyRow → `variants.delete`.
**BarcodeController:** index/search → `barcode.view`; generate/print → `barcode.generate`.

- [ ] **Step 1: Apply guards** — Example (ProductController):

```php
public function index(Request $request)
{
    bpAuthorize('products.view');
    // ... existing body unchanged ...
}

public function store(StoreProductRequest $request)
{
    bpAuthorize('products.create');
    // ...
}

public function destroy(Product $product)
{
    bpAuthorize('products.delete');
    // ...
}
```

Apply the same one-line pattern to every method per the tables above, in all four controllers.

- [ ] **Step 2: Smoke-check compile**

Run: `php -l Modules/Product/app/Http/Controllers/ProductController.php`
Expected: `No syntax errors detected`. Repeat for the other three files.

- [ ] **Step 3: Commit**

```bash
git add Modules/Product Modules/Variant Modules/Barcode
git commit -m "feat(security): gate Product/Variant/Barcode controllers"
```

---

### Task 7: Category, Brand, Unit controllers

**Files:** `Modules/Category/.../CategoryController.php`, `Modules/Brand/.../BrandController.php`, `Modules/Unit/.../UnitController.php`

Each controller, identical shape (group = `categories` / `brands` / `units`):

| Method(s) | Permission |
|---|---|
| index | `<group>.view` |
| create, store, quickStore | `<group>.create` |
| edit, update, toggleStatus, reorder | `<group>.edit` |
| destroy | `<group>.delete` |

- [ ] **Step 1: Apply guards** per table to all three controllers (first line of each method). Example:

```php
public function quickStore(Request $request)
{
    bpAuthorize('categories.create');
    // ...
}
```

- [ ] **Step 2: Lint** — `php -l` each of the three files → no syntax errors.
- [ ] **Step 3: Commit**

```bash
git add Modules/Category Modules/Brand Modules/Unit
git commit -m "feat(security): gate Category/Brand/Unit controllers"
```

---

### Task 8: Inventory controller

**Files:** `Modules/Inventory/app/Http/Controllers/InventoryController.php`

| Method(s) | Permission |
|---|---|
| index, ledger, showAdjustment, printAdjustment, alerts, stockLedger, reconciliation | inventory.view |
| createAdjustment, storeAdjustment | inventory.create |
| editAdjustment, updateAdjustment, approveAdjustment, cancelAdjustment | inventory.edit |
| destroyAdjustment | inventory.delete |

- [ ] **Step 1:** Apply guards. **Step 2:** `php -l`. **Step 3:** Commit `feat(security): gate Inventory controller`.

---

### Task 9: Sale, SaleReturn, Quotation controllers

**Files:** `Modules/Sale/.../SaleController.php`, `Modules/SaleReturn/.../SaleReturnController.php`, `Modules/Quotation/.../QuotationController.php`

**SaleController:**

| Method(s) | Permission |
|---|---|
| index, show, print, pdf, bulkPrint, bulkLabel, fraudCheck, quickView, share | sales.view |
| create, store | sales.create |
| edit, update, bulkStatus, bulkAssign, sendToCourier, updateStatus, assignStaff, bulkSendToCourier, refreshCourierStatus, email, sms | sales.edit |
| destroy | sales.delete |

**SaleReturnController:** index/show/print/pdf/saleItems → `sales.view`; create/store → `sales.create`; edit/update/approve/complete/cancel → `sales.edit`; destroy → `sales.delete`.
**QuotationController:** index/show/print/pdf/send/searchProducts → `quotations.view`; create/store/duplicate/convertToSale → `quotations.create`; edit/update/bulkStatus → `quotations.edit`; destroy/bulkDelete → `quotations.delete`.

- [ ] **Step 1:** Apply guards. **Step 2:** `php -l` all three. **Step 3:** Commit `feat(security): gate Sale/SaleReturn/Quotation controllers`.

---

### Task 10: Purchase, GRN, PurchaseReturn controllers

**Files:** `Modules/Purchase/.../PurchaseController.php`, `Modules/Purchase/.../GrnController.php`, `Modules/PurchaseReturn/.../PurchaseReturnController.php`, `.../PurchaseReturnTypeController.php`

**PurchaseController:** index/show/print/pdf/quickAddSupplier → `purchases.view`; create/store → `purchases.create`; edit/update/cancel → `purchases.edit`; **approve → `purchases.approve`**; destroy → `purchases.delete`.
**GrnController:** create → `purchases.view`; store → `purchases.create`.
**PurchaseReturnController:** index/show/print/pdf/purchaseItems → `purchases.view`; create/store → `purchases.create`; edit/update/complete/cancel → `purchases.edit`; destroy → `purchases.delete`.
**PurchaseReturnTypeController:** index → `purchases.view`; store → `purchases.create`; update/toggleStatus → `purchases.edit`; destroy → `purchases.delete`.

- [ ] **Step 1:** Apply guards. **Step 2:** `php -l` all four. **Step 3:** Commit `feat(security): gate Purchase/GRN/PurchaseReturn controllers`.

---

### Task 11: Customer (+Group, +Area) controllers

**Files:** `Modules/Customer/.../CustomerController.php`, `CustomerGroupController.php`, `AreaController.php`

**CustomerController:** index/show/search/advances/ledger/dueReceive/offsetDue/thanasByDistrict → `customers.view`; create/store/quickStore → `customers.create`; edit/update/toggleStatus → `customers.edit`; destroy → `customers.delete`.
**CustomerGroupController & AreaController:** index → `customers.view`; store → `customers.create`; update/toggleStatus → `customers.edit`; destroy → `customers.delete`.

- [ ] **Step 1:** Apply guards. **Step 2:** `php -l` all three. **Step 3:** Commit `feat(security): gate Customer controllers`.

---

### Task 12: Supplier (+Group, +Payment) controllers

**Files:** `Modules/Supplier/.../SupplierController.php`, `SupplierGroupController.php`, `SupplierPaymentController.php`

**SupplierController:** index/show/ledger/print → `suppliers.view`; **export → `suppliers.export`**; create/store → `suppliers.create`; edit/update/toggleStatus → `suppliers.edit`; destroy → `suppliers.delete`.
**SupplierGroupController:** index → `suppliers.view`; store → `suppliers.create`; update/toggleStatus → `suppliers.edit`; destroy → `suppliers.delete`.
**SupplierPaymentController:** store → `suppliers.edit`.

- [ ] **Step 1:** Apply guards. **Step 2:** `php -l` all three. **Step 3:** Commit `feat(security): gate Supplier controllers`.

---

### Task 13: Payment + PaymentAccount controllers

**Files:** `Modules/Payment/.../PaymentController.php`, `PaymentAccountController.php`

**PaymentController:** index/show/advances/partySearch/outstandingInvoices → `payments.view`; create/store → `payments.create`; destroy → `payments.delete`.
**PaymentAccountController:** index/ledger/apiList/banks/mobileBanks/transfers → `payments.view`; create/store/storeTransfer/storeBank/storeMobileBank → `payments.create`; edit/update/toggleStatus → `payments.edit` — **note `payments` has no `edit`; use `payments.create` for edit/update/toggleStatus** (catalog defines view/create/delete only); destroyBank/destroyMobileBank/destroy → `payments.delete`.

> Catalog note: `payments` = {view, create, delete}. Map edit-like actions to `payments.create`.

- [ ] **Step 1:** Apply guards. **Step 2:** `php -l` both. **Step 3:** Commit `feat(security): gate Payment controllers`.

---

### Task 14: Finance umbrella — Expense, Asset, Loan, SimpleMoney, CapitalTransaction

**Files:**
- `Modules/Expense/.../ExpenseController.php`, `ExpenseCategoryController.php`
- `Modules/Asset/.../AssetController.php`, `AssetCategoryController.php`
- `Modules/Loan/.../LoanController.php`, `LenderController.php`
- `Modules/Accounting/.../SimpleMoneyController.php`, `CapitalTransactionController.php`

All map to the **`finance`** group.

| Action kind | Permission |
|---|---|
| index, show, ledger, print, summary, cashFlow, income, expense | finance.view |
| create, store | finance.create |
| edit, update, toggleStatus, approve, reject, markPaid, recordPayment, cancel, maintenance, depreciation, repayment, reschedule | finance.edit |
| destroy | finance.delete |

(SimpleMoneyController: summary/cashFlow/income/expense → `finance.view`. CapitalTransactionController: index → `finance.view`, store → `finance.create`, destroy → `finance.delete`. Asset maintenance/depreciation → `finance.edit`. Loan repayment/reschedule → `finance.edit`, cancel → `finance.edit`.)

- [ ] **Step 1:** Apply guards across all 8 files. **Step 2:** `php -l` each. **Step 3:** Commit `feat(security): gate Finance umbrella (expense/asset/loan/money) controllers`.

---

### Task 15: Accounting umbrella controllers

**Files (Modules/Accounting/app/Http/Controllers):** `ChartOfAccountsController.php`, `JournalEntryController.php`, `AccountingReportController.php`, `BankReconciliationController.php`, `CreditNoteController.php`, `DebitNoteController.php`, `PaymentReceiptController.php`, `InvestmentController.php`

All map to the **`accounting`** group.

| Action kind | Permission |
|---|---|
| index, show, print, pdf, generalLedger, trialBalance, profitLoss, balanceSheet, cashFlow, dashboard, investorsIndex, investorsShow, distributionsIndex, *Index, *Show | accounting.view |
| create, store, *Create, *Store, importStatement, start, capitalCreate, capitalStore, distributionsCreate, distributionsStore | accounting.create |
| edit, update, toggleStatus, post, void, issue, cancel, match, unmatch, complete, *Edit, *Update, *ToggleStatus | accounting.edit |
| destroy, *Destroy, capitalDestroy, distributionsDestroy | accounting.delete |

- [ ] **Step 1:** Apply guards across all 8 files (InvestmentController has the most methods — gate every public `investors*`, `capital*`, `distributions*`, `dashboard`). **Step 2:** `php -l` each. **Step 3:** Commit `feat(security): gate Accounting controllers`.

---

### Task 16: Reports controller

**Files:** `Modules/Report/app/Http/Controllers/ReportController.php`

All view methods (index, dts, categoryWise, monthlySummary, detailSales, receivablesAging, cashMovement, supplierPayments, sales, purchase, inventory, financial, profitLoss, staff, customer, custom, customGenerate) → `reports.view`. `exportPdf` → `reports.export`.

- [ ] **Step 1:** Apply guards. **Step 2:** `php -l`. **Step 3:** Commit `feat(security): gate Reports controller`.

---

### Task 17: HR umbrella — Employee, Attendance, Payroll

**Files:**
- `Modules/Employee/.../EmployeeController.php`
- `Modules/Attendance/.../AttendanceController.php`, `AttendanceConfigController.php`, `LeaveTypeController.php`
- `Modules/Payroll/.../PayrollController.php`

All map to the **`hr`** group.

| Action kind | Permission |
|---|---|
| index, show, report, leave, leaveBalance, salaryStructures, salaryStructureShow, print, *Show | hr.view |
| create, store, leaveCreate, leaveStore, salaryIncrementStore, salaryStructureCreate, salaryStructureStore, generate, generateStore, storeHoliday, *Create, *Store | hr.create |
| edit, update, toggleStatus, saveShift, saveWeekends, updateHoliday, leaveApprove, leaveReject, approve, markPaid, cancel, salaryIncrementUpdate, salaryStructureEdit, salaryStructureUpdate, salaryStructureToggleStatus, *Edit, *Update | hr.edit |
| destroy, destroyHoliday, salaryIncrementDestroy, salaryStructureDestroy, *Destroy | hr.delete |

- [ ] **Step 1:** Apply guards across all 5 files. **Step 2:** `php -l` each. **Step 3:** Commit `feat(security): gate HR umbrella (employee/attendance/payroll) controllers`.

---

### Task 18: Marketing + AdSpend controllers

**Files:**
- `Modules/Marketing/.../MarketingController.php`
- `Modules/AdSpend/.../AdSpendController.php`, `AdPlatformController.php`, `MetaAdsController.php`

All map to the **`marketing`** group.

| Action kind | Permission |
|---|---|
| index, smsCampaigns, smsCampaignShow, email, loyalty, show, analytics, settings | marketing.view |
| create, store, *Create, *Store, payment, duplicate, smsCampaignSend, smsCampaignDuplicate, sync | marketing.create |
| edit, update, toggleStatus, *Edit, *Update, saveSettings, testConnection | marketing.edit |
| destroy, *Destroy | marketing.delete |

(`smsCampaignSend`/`sync` are actions — map to `marketing.create` is acceptable; alternatively `marketing.edit`. Use `marketing.edit` for send/sync/saveSettings to keep "view-only" users from triggering sends. Final: smsCampaignSend → marketing.edit, sync → marketing.edit.)

- [ ] **Step 1:** Apply guards across all 4 files. **Step 2:** `php -l` each. **Step 3:** Commit `feat(security): gate Marketing/AdSpend controllers`.

---

### Task 19: Manufacturing controllers

**Files (Modules/Manufacturing/app/Http/Controllers — all):** `ManufacturingDashboardController.php`, `ManufacturingReportController.php`, `ProductionOrderController.php`, `ProductionLotController.php`, `ProductionDamageController.php`, `RawMaterialController.php`, `RawMaterialSupplierController.php`, `RmPurchaseController.php`, `RmReceiveController.php`, `RmSupplierPaymentController.php`, `RmWasteController.php`, `FabricIssuanceController.php`, `FabricReturnController.php`, `FactoryController.php`, `FactoryPaymentController.php`, `CatalogController.php`, `MfgColorController.php`, `MfgSizeController.php`, `ProductWasteController.php`

All map to the **`manufacturing`** group.

| Action kind | Permission |
|---|---|
| index, show, dashboard, rmStock, production, damage, waste, costAnalysis, *Show | manufacturing.view |
| create, store, *Create, *Store, recordCompensation | manufacturing.create |
| edit, update, toggleStatus, approve, cancel, complete, *Edit, *Update | manufacturing.edit |
| destroy, *Destroy | manufacturing.delete |

- [ ] **Step 1:** Apply guards across all 19 files (first line of each public action method). **Step 2:** `php -l` each. **Step 3:** Commit `feat(security): gate Manufacturing controllers`.

---

### Task 20: Ecommerce controllers

**Files (Modules/Ecommerce/app/Http/Controllers):** `EcommerceController.php`, `CampaignController.php`, `ContentController.php`, `MenuController.php`, `SteadfastOperationsController.php`

All map to the **`ecommerce`** group.

| Action kind | Permission |
|---|---|
| index, products, coupons, shipping, settings, banners, homepageSections, collections, flashDeals, blogPosts, blogCategories, blogComments, courierProviders, searchProducts, generateSitemap, paymentDetail, *Show, edit-form GETs (`*Create`, `*Edit`) | ecommerce.view* |
| store, *Store, couponStore, shippingStore, bannerStore, collectionStore, flashDealStore, blogPostStore, blogCategoryStore, storeItem | ecommerce.create |
| update, *Update, toggle*, reorder, settingsUpdate, appearanceUpdate, blogCommentApprove, blogCommentUnapprove, updateCourierProvider, homepageSection* (reorder/toggle/settings/products), updateItem, clear, reset | ecommerce.edit |
| destroy, *Destroy, destroyItem | ecommerce.delete |

> *For `*Create`/`*Edit` GET form methods, use `ecommerce.create` / `ecommerce.edit` respectively (they precede a write). e.g. `couponCreate` → `ecommerce.create`, `couponEdit` → `ecommerce.edit`.

- [ ] **Step 1:** Apply guards across all 5 files. ContentController & EcommerceController have many methods — gate each per the action-kind table. **Step 2:** `php -l` each. **Step 3:** Commit `feat(security): gate Ecommerce admin controllers`.

---

### Task 21: Security + System (Settings, Location, Activity)

> **Out of scope (left ungated by decision):** `Modules/Branch` (BranchController) and `Modules/AiAssistant` (AiSettingsController) remain auth-only — do not add guards or permissions for them.

**Files:**
- `Modules/Security/.../SecurityController.php`, `UserController.php`
- `Modules/Setting/.../SettingController.php`, `EmailSettingController.php`, `PrinterController.php`, `SystemLogController.php`, `TaxRateController.php`
- `Modules/Location/.../LocationController.php`
- `Modules/Activity/.../ActivityController.php`

**SecurityController:** index → `roles.view`; roles → `roles.view`; roleCreate/roleStore → `roles.create`; roleEdit/roleUpdate → `roles.edit`; roleDestroy → `roles.delete`; backup/apiKeys → `users.view`; backupCreate/backupRestore/apiKeyCreate/apiKeyStore → `users.create`; apiKeyDestroy → `users.delete`.
**UserController:** index/show → `users.view`; create/store → `users.create`; edit/update/toggleStatus → `users.edit`; destroy → `users.delete`. **Do NOT gate** editProfile, updateProfile, changePassword, updatePassword (self-service).
**SettingController:** index/sidebarConfig → `settings.view`; everything else (update, webhookStore/Toggle/Destroy/Test, saveSidebarConfig, toggleMaintenance, clearCache, testCourierConnection) → `settings.edit`. **Do NOT gate** webhookReceive (external inbound webhook — has no auth user).
**EmailSettingController:** config/templateEdit → `settings.view`; saveConfig/testEmail/templateUpdate → `settings.edit`.
**PrinterController:** index → `settings.view`; create/store → `settings.edit`; edit/update/destroy/testConnection/toggleStatus → `settings.edit` (printers fold into settings; no separate group).
**SystemLogController:** index/show/download → `settings.view`; clear/delete → `settings.edit`.
**TaxRateController:** store/update → `settings.edit`; destroy → `settings.edit`.
**LocationController:** index/thanasByDistrict → `locations.view`; storeDistrict/storeThana → `locations.create`; updateDistrict/updateThana → `locations.edit`; destroyDistrict/destroyThana → `locations.delete`.
**ActivityController:** index/show → `activities.view`; clear → `activities.delete`.

- [ ] **Step 1:** Apply guards across all 9 files, honoring the self-service / webhookReceive exclusions. **Step 2:** `php -l` each. **Step 3:** Commit `feat(security): gate Security/Settings/Location/Activity controllers`.

---

### Task 22: Shared cross-cutting controllers (Export/Import/Bulk)

**Files (app/Http/Controllers):** `ExportController.php`, `ImportController.php`, `BulkActionController.php`

These resolve the target via the `{module}` route param. Map module → permission action and authorize inside the method.

- [ ] **Step 1: Read each controller** to find the `{module}` param name and how it's used.

Run: `php artisan route:list --path=export` and `--path=import` and `--path=bulk` to confirm param names.

- [ ] **Step 2: Add a module→group resolver + guard**

Add this private helper to each controller (or a shared trait `app/Http/Controllers/Concerns/ResolvesModulePermission.php` — preferred, DRY):

```php
<?php
// app/Http/Controllers/Concerns/ResolvesModulePermission.php
namespace App\Http\Controllers\Concerns;

trait ResolvesModulePermission
{
    /**
     * Map a {module} route segment to its permission group.
     * Folds umbrella sub-modules into their parent group.
     */
    protected function permissionGroupForModule(string $module): string
    {
        $map = [
            'sale' => 'sales', 'sales' => 'sales', 'sale-returns' => 'sales',
            'purchase' => 'purchases', 'purchases' => 'purchases', 'purchase-returns' => 'purchases',
            'customer' => 'customers', 'customers' => 'customers',
            'supplier' => 'suppliers', 'suppliers' => 'suppliers',
            'product' => 'products', 'products' => 'products',
            'expense' => 'finance', 'expenses' => 'finance', 'asset' => 'finance', 'assets' => 'finance',
            'loan' => 'finance', 'loans' => 'finance',
            'employee' => 'hr', 'employees' => 'hr', 'attendance' => 'hr', 'payroll' => 'hr',
            'inventory' => 'inventory', 'quotation' => 'quotations', 'quotations' => 'quotations',
            'payment' => 'payments', 'payments' => 'payments',
        ];

        return $map[$module] ?? str_replace('-', '_', $module);
    }
}
```

Then in `ExportController@export`:

```php
public function export(Request $request, string $module)
{
    bpAuthorize($this->permissionGroupForModule($module) . '.export');
    // ... existing body ...
}
```

In `ImportController@import`: authorize `...'.create'`. In `BulkActionController`: the `delete` method → `...'.delete'`, the `status` method → `...'.edit'`.

> Note: only groups that DEFINE `.export` (products, inventory, purchases, sales, customers, suppliers, finance, accounting, hr, reports) can be exported. If a module without `.export` is requested, `bpAuthorize` will throw because the permission doesn't exist and no role has it — which is the correct fail-closed behavior for export of a non-exportable module.

- [ ] **Step 3:** `use ResolvesModulePermission;` in all three controllers; `php -l` each.
- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Concerns/ResolvesModulePermission.php app/Http/Controllers/ExportController.php app/Http/Controllers/ImportController.php app/Http/Controllers/BulkActionController.php
git commit -m "feat(security): gate shared export/import/bulk controllers by module"
```

---

## Phase C — UI hiding

### Task 23: Automated enforcement test (coverage + per-role smoke)

> Placed before the UI task so the backend is proven locked down first.

**Files:** `tests/Feature/PermissionEnforcementTest.php` (create)

**Interfaces:** Consumes the seeded roles (Task 5) and `bpAuthorize` guards (Phase B).

- [ ] **Step 1: Write the test**

```php
<?php
// tests/Feature/PermissionEnforcementTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Security\Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /** A no-permission authenticated user is blocked from gated GET pages. */
    public function test_unprivileged_user_is_blocked_from_gated_pages(): void
    {
        $role = Role::create(['name' => 'NoAccess', 'guard_name' => 'web']); // zero permissions
        $user = User::factory()->create();
        $user->assignRole($role);

        foreach ([
            '/admin/products', '/admin/sales', '/admin/customers',
            '/admin/reports', '/admin/security/users',
        ] as $path) {
            $resp = $this->actingAs($user)->get($path);
            $this->assertContains($resp->status(), [302, 403], "Expected block for {$path}, got {$resp->status()}");
        }
    }

    /** Super admin reaches everything. */
    public function test_super_admin_reaches_gated_pages(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        foreach (['/admin/products', '/admin/sales', '/admin/security/users'] as $path) {
            $this->actingAs($user)->get($path)->assertOk();
        }
    }

    /** Cashier can sell but cannot manage users. */
    public function test_cashier_scope(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Cashier');

        $this->actingAs($user)->get('/admin/sales')->assertOk();
        $resp = $this->actingAs($user)->get('/admin/security/users');
        $this->assertContains($resp->status(), [302, 403]);
    }
}
```

> Adjust the exact paths to the real admin URL prefix — confirm with `php artisan route:list --name=products.index` (the design notes routes are under `/admin`). If the prefix differs, fix the literals here.

- [ ] **Step 2: Run**

Run: `php artisan test --filter=PermissionEnforcementTest`
Expected: PASS. If a gated page returns 200 for the no-access user, a controller method is missing its `bpAuthorize()` — go back and add it.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/PermissionEnforcementTest.php
git commit -m "test(security): per-role enforcement smoke tests"
```

---

### Task 24: Gate the sidebar

**Files:** `Modules/Core/resources/views/partials/sidebar.blade.php`

Wrap each leaf menu item with `@bpCan('<perm>')...@endbpCan` and each group/submenu with `@bpCanAny(...)...@endbpCanAny` (so an empty group disappears entirely). Use the route→permission mapping from the design spec.

- [ ] **Step 1: Gate leaf items** — Example (Sales submenu):

```blade
@bpCanAny('sales.view','sales.create','quotations.view')
<li class="menu-item {{ request()->routeIs('sales.*', 'quotations.*', 'sale-returns.*') ? 'open' : '' }}">
  <a href="#" class="menu-link ..." data-toggle="submenu" title="Sales">
    <i class="fa-solid fa-chart-line"></i>
    <span class="menu-text">Sales</span>
    <i class="fa-solid fa-chevron-right menu-arrow"></i>
  </a>
  <ul class="submenu">
    @bpCan('sales.view')
      <li><a href="{{ route('sales.index') }}" class="menu-link ...">Sales List</a></li>
    @endbpCan
    @bpCan('sales.create')
      <li><a href="{{ route('sales.create') }}" class="menu-link ...">Create Order</a></li>
    @endbpCan
    @bpCan('quotations.view')
      <li><a href="{{ route('quotations.index') }}" class="menu-link ...">Quotations</a></li>
    @endbpCan
    @bpCan('sales.view')
      <li><a href="{{ route('sale-returns.index') }}" class="menu-link ...">Sales Returns</a></li>
    @endbpCan
  </ul>
</li>
@endbpCanAny
```

Apply the same shape to every group. Group → wrapper permissions:

| Sidebar group | `@bpCanAny(...)` wrapper |
|---|---|
| Workspace › Sales | sales.view, sales.create, quotations.view |
| Workspace › Customers | customers.view |
| Catalog › Products | products.view, categories.view, brands.view, units.view, variants.view, barcode.view |
| Catalog › Stock | inventory.view |
| Purchasing › Purchases | purchases.view |
| Purchasing › Suppliers | suppliers.view |
| Finance (all) | finance.view, payments.view, accounting.view |
| Online Store › eCommerce | ecommerce.view |
| Reports | reports.view |
| Marketing | marketing.view |
| HR › Staff | hr.view |
| Manufacturing | manufacturing.view, manufacturing.create |
| System › Locations | locations.view |
| System › Settings | settings.view |
| System › Security | users.view, roles.view |
| System › Activity Log | activities.view |

Leaf-item permissions follow the obvious group action (`.index` → `.view`, `.create` page → `.create`). Dashboard stays ungated (everyone with a login sees it).

- [ ] **Step 2: Verify render as Super Admin** — start server, log in as `admin@gmail.com`, confirm the full sidebar still shows (Gate::before grants all).
- [ ] **Step 3: Verify render as Cashier** — create/login a Cashier; confirm only Sales/Customers/Products(view)/Dashboard appear, and Security/Settings/Reports are hidden.
- [ ] **Step 4: Commit**

```bash
git add Modules/Core/resources/views/partials/sidebar.blade.php
git commit -m "feat(security): gate sidebar menu by permission"
```

---

### Task 25: Gate action buttons across ALL module views

**Scope — exhaustive.** 278 Blade files contain action controls (Add/Edit/Delete/Export/page-actions). Gate every one. This task is best executed **per module, folded into that module's Phase B task** (gate the controller and its views together), but is listed here as one task for completeness. Per-module file counts (authoritative, from the codebase):

| Module | Views to gate | Module | Views to gate |
|---|---|---|---|
| Module | Views | Module | Views |
|---|---|---|---|
| Accounting | 32 | Marketing | 8 |
| Activity | 2 | Payment | 10 |
| AdSpend | 5 | Payroll | 7 |
| Asset | 7 | Product | 4 |
| Attendance | 7 | Purchase | 5 |
| Barcode | 1 | PurchaseReturn | 5 |
| Brand | 3 | Quotation | 4 |
| Category | 3 | Report | 15 |
| Customer | 9 | Sale | 4 |
| Ecommerce | 36 | SaleReturn | 4 |
| Employee | 4 | Security | 11 |
| Expense | 8 | Setting | 6 |
| Inventory | 8 | Supplier | 7 |
| Loan | 7 | Unit | 3 |
| Location | 1 | Variant | 4 |
| Manufacturing | 37 | | |

**Excluded:** Branch (3) and AiAssistant (1) — left ungated by decision. POS (1) and LandingPage (2) — disabled modules. Dashboard (1) and Core (1) — ungated surfaces (verify the single match isn't a gated control before skipping).

- [ ] **Step 1: Enumerate the exact files for the module you're gating**

Run (replace `<Module>`):

```bash
grep -rlE "section\('page-actions'\)|\.create'\)|\.export'\)|route\('export'|data-delete|\.destroy'|fa-trash|fa-pen" \
  Modules/<Module>/resources/views --include="*.blade.php" | grep -vE "partials/(sidebar|header|footer)"
```

This prints the authoritative file list for that module. Gate every file it returns.

- [ ] **Step 2: Wrap each control** — Example (Products page-actions / row actions):

```blade
@section('page-actions')
  @bpCan('products.create')
    <a href="{{ route('products.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i>Add Product</a>
  @endbpCan
  @bpCan('products.export')
    <a href="{{ route('export', 'products') }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-download me-1"></i>Export</a>
  @endbpCan
@endsection
```

```blade
{{-- row actions --}}
@bpCan('products.edit')
  <a href="{{ route('products.edit', $product) }}" class="bp-btn bp-btn-icon bp-btn-sm"><i class="fa-solid fa-pen"></i></a>
@endbpCan
@bpCan('products.delete')
  <button class="bp-btn bp-btn-icon bp-btn-sm bp-btn-danger" data-delete="{{ route('products.destroy', $product) }}"><i class="fa-solid fa-trash"></i></button>
@endbpCan
```

Map per module group (products/sales/customers/suppliers/purchases/inventory/reports/users/roles/etc.). Use the same `<group>.create|edit|delete|export` keys.

- [ ] **Step 3: Verify** — as a `view`-only role, confirm Add/Edit/Delete buttons are absent on a couple of pages (e.g. Products, Sales) and present as Super Admin.
- [ ] **Step 4: Commit**

```bash
git add Modules/*/resources/views
git commit -m "feat(security): gate create/edit/delete/export action buttons by permission"
```

---

## Phase D — Finalize & verify

### Task 26: Re-seed, full regression sweep, dark-mode/responsive sanity

**Files:** none (operational)

- [ ] **Step 1: Re-seed permissions on the dev DB**

```bash
php artisan db:seed --class="Modules\\Security\\Database\\Seeders\\RolePermissionSeeder" --force
```
Expected: completes with no error; `php artisan tinker --execute="echo \Spatie\Permission\Models\Permission::count();"` shows the new catalog count (~95 permissions, pos/grn pruned).

- [ ] **Step 2: Run the full test suite**

Run: `php artisan test`
Expected: PASS (all new tests + existing `NavigationSearchSyncTest`, `SecurityTest`).

> If `tests/Feature/NavigationSearchSyncTest.php` fails because `config/navigation.php` references `payments.view` (now defined) or removed perms, reconcile `config/navigation.php` permission keys to the new catalog in this step and re-run.

- [ ] **Step 3: Manual role sweep** — log in as each seeded role (create one user per role via Security › Users) and verify: sidebar hides correctly, direct-URL to a forbidden page redirects with the flash error, allowed pages work.

- [ ] **Step 4: Route sweep** — confirm no 500s introduced:

```bash
php artisan route:list --json > /tmp/routes.json
# Spot-check gated GET routes return 200 as super admin, 302/403 as no-access user.
```

- [ ] **Step 5: Commit any reconciliation**

```bash
git add config/navigation.php
git commit -m "fix(security): reconcile navigation permission keys with new catalog"
```

---

## Self-review notes (coverage map)

- Spec §4 catalog → Task 1. §6 UI → Tasks 24-25. §2 backend → Tasks 6-22. §3 exception → Task 3. §7 super admin + seeder → Tasks 4-5. §9 verification → Tasks 23, 26. §5 route→permission rules → embedded per-module tables + Task 22 resolver.
- Every module from the research inventory has a backend task: Product/Variant/Barcode (6), Category/Brand/Unit (7), Inventory (8), Sale/SaleReturn/Quotation (9), Purchase/GRN/PurchaseReturn (10), Customer (11), Supplier (12), Payment (13), Finance umbrella (14), Accounting (15), Reports (16), HR umbrella (17), Marketing/AdSpend (18), Manufacturing (19), Ecommerce (20), Security/Settings/Location/Activity (21), shared Export/Import/Bulk (22).
- Self-service (profile/password), webhookReceive, storefront, dashboard, notifications, search, and disabled modules (POS/LandingPage) are explicitly excluded.
```
