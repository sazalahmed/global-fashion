# Roles & Permissions Enforcement — Design Spec

**Date:** 2026-06-25
**Status:** Approved (design)
**Scope:** Enforce RBAC across the BizPOS Pro admin app — hide UI by permission and block direct-URL access via controller-body checks. Clean up the permission catalog.

---

## 1. Problem

The roles/permissions data model is fully built (Spatie, `PermissionsTrait`, role/user management UI, seeders), but **nothing is enforced**:

- Module route groups use only `->middleware('auth')` — no permission gating (verified across all `Modules/*/routes/web.php`).
- No controller performs any authorization (0 `authorize()`/`->can()` calls in controllers).
- `sidebar.blade.php` uses **zero** `@can`/`@bpCan` directives — every user sees the full menu.
- Permissions currently only affect global-search result filtering (`NavigationRegistry`, `SearchableRegistry`).

Net effect: any authenticated user can reach any page/action by URL regardless of role.

## 2. Goals

1. **Hide UI by permission** — sidebar groups/items and create/edit/delete action buttons.
2. **Block direct-URL access** — a guard call at the top of each gated controller method (controller-body approach, matching the reference project `quickshifter-inventory`).
3. **Clean up the catalog** — remove dead permissions, add missing ones, reconcile naming gaps.
4. **No accidental lockouts; no silently-unprotected routes** — enforced by a CI test.

## 3. Chosen approach

**Controller-body checks** (not route middleware, not a central resolver), mirroring the reference project so the two codebases stay consistent.

- Global helper `bpAuthorize('group.action')` throws `PermissionDeniedException` when the current user lacks the permission. It is the **first line of every gated controller method**.
- A custom `PermissionDeniedException::render()` returns `redirect()->back()` with a flash error for web requests and a JSON 403 for `expectsJson()`/API requests. Registered once in `bootstrap/app.php` (Laravel 12 exception handling).
- The 3 shared controllers (`ExportController`, `ImportController`, `BulkActionController`) read the `{module}` route param and call `bpAuthorize($module.'.export'|'.create'|'.delete')` accordingly.

### Why not the alternatives
- **Route middleware**: would require per-route declarations for view/create/edit/delete granularity (same line count, but split across route files) and cannot gate the `{module}`-parameterised shared controllers.
- **Central resolver**: DRY but only as granular/explicit as its map; less greppable; diverges from the reference project the team wants to mirror.

## 4. Permission catalog (final)

Defined in `app/Traits/PermissionsTrait.php`, format `group => ['group.action', ...]`, guard `web`.

**Removed:** `pos` (module returns 404), `grn` (equivalent to `purchases.create`), `purchase_returns` (folded into `purchases`), `branches` (not surfaced in sidebar/nav).

**Added:** `units`, `variants`, `barcode`, `locations`, `activities`, `manufacturing`, `payments`.

| Group | Permissions |
|---|---|
| dashboard | view |
| products | view, create, edit, delete, export |
| categories | view, create, edit, delete |
| brands | view, create, edit, delete |
| units | view, create, edit, delete |
| variants | view, create, edit, delete |
| barcode | view, generate |
| inventory | view, create, edit, delete, export |
| purchases | view, create, edit, delete, approve, export |
| sales | view, create, edit, delete, export |
| quotations | view, create, edit, delete |
| customers | view, create, edit, delete, export |
| suppliers | view, create, edit, delete, export |
| payments | view, create, delete |
| finance | view, create, edit, delete, export |
| accounting | view, create, edit, delete, export |
| reports | view, export |
| ecommerce | view, create, edit, delete |
| marketing | view, create, edit, delete |
| hr | view, create, edit, delete, export |
| manufacturing | view, create, edit, delete |
| locations | view, create, edit, delete |
| activities | view, delete |
| users | view, create, edit, delete |
| roles | view, create, edit, delete |
| settings | view, edit |

**Umbrella mapping** (sub-features fold into a parent group — no dedicated permissions):
- `finance` ← expenses, expense-categories, assets, asset-categories, loans, lenders, money (cashflow/income/summary)
- `hr` ← employee, attendance (+ leave, leave-types, config), payroll
- `accounting` ← chart-of-accounts, journal-entries, ledgers, trial-balance, P&L, balance-sheet, credit/debit notes, receipts, investment, capital-transactions
- `marketing` ← adspend (+ platforms, meta-ads), sms-campaigns, email, loyalty

## 5. Route → permission mapping rules

Naming convention from the route-name suffix:
- `index`, `show`, `list`, `search`, `ledger`, `print`, `pdf`, `quick-view`, `*-search` → `.view`
- `create`, `store`, `quick-store`, `duplicate`, `generate*` → `.create`
- `edit`, `update`, `toggle-status`, `reorder`, `bulk-status`, `assign`, `*-active` → `.edit`
- `destroy`, `bulk-delete`, `clear`, `cancel` → `.delete` (module-dependent; see overrides)
- `export` → `.export`; `approve` → `.approve` (purchases)

**Group aliases** (prefix/name mismatches found in research):
- `supplier.*` → `suppliers`
- `adspend.*`, `marketing.*` → `marketing`
- `employee.*`, `attendance.*`, `payroll.*` → `hr`
- `expenses.*`, `expense-categories.*`, `assets.*`, `asset-categories.*`, `loans.*`, `lenders.*`, `money.*` → `finance`
- `investment.*`, `capital-transactions.*`, `accounting.*` → `accounting`
- `security.users.*` → `users`; `security.roles*` → `roles`; `security.backup`, `security.api-keys` → `users` (or `settings` — see plan)
- `sale-returns.*` → `sales`; `purchase-returns.*`, `purchase-return-types.*`, `purchases.receive.*` → `purchases`
- `customer-groups.*`, `customer-areas.*` → `customers`; `supplier-groups.*` → `suppliers`

**Not gated** (auth-only / public / self-service):
- All `Modules/Auth` routes (login/forgot/reset are guest; logout is auth-only)
- Storefront routes (`Modules/Ecommerce/routes/storefront.php`, `Modules/AiAssistant/routes/storefront.php`)
- `dashboard`, `notifications.*`, `search*`
- `security.profile*`, `security.change-password*` (self-service)
- `settings.index#anchor` deep-link tabs (the `settings.index` page itself → `settings.view`)
- Dead/disabled: all `pos.*` (abort 404), `landing-pages.*` (disabled module)

## 6. UI hiding

- Reuse existing `@bpCan('perm') ... @endbpCan` directive (registered in `AppServiceProvider::registerBladeDirectives`).
- Add `bpCanAny(...$perms): bool` global helper + matching usage `@if(bpCanAny('a.view','a.create')) ... @endif` to hide a whole submenu/group when the user has none of its children.
- Gate `Modules/Core/resources/views/partials/sidebar.blade.php` — every menu header group wrapped with `bpCanAny`, every leaf item with `@bpCan`.
- Gate create/edit/delete/export action buttons across index/show/`@yield('page-actions')` blocks.

## 7. Super Admin & seeding

- Add `Gate::before` in `AppServiceProvider::boot()`: return `true` if user `hasRole('Super Admin')`. New permissions auto-apply with no re-seed. Existing Super-Admin record-protection guards (`guardSuperAdmin`, `excludingSuperAdmin`, `isSuperAdmin`) are unchanged.
- Update `RolePermissionSeeder` for the new catalog: Super Admin gets all (kept), and the Manager/Cashier/Accountant/Inventory Staff/Sales Rep role grants are adjusted for added/removed groups. Idempotent (`firstOrCreate` + `syncPermissions`). Prune permissions removed from the catalog so stale rows don't linger.

## 8. Components & files touched

| Concern | File(s) |
|---|---|
| Catalog | `app/Traits/PermissionsTrait.php` |
| Guard helper | `app/Helpers/PermissionHelper.php` + global functions file (`bpAuthorize`, `bpCanAny`) autoloaded via `composer.json` |
| Exception | `app/Exceptions/PermissionDeniedException.php`, `bootstrap/app.php` (render registration) |
| Super admin bypass + directives | `app/Providers/AppServiceProvider.php` |
| Backend checks | every resource controller in `Modules/*/app/Http/Controllers/*` + `app/Http/Controllers/{Export,Import,BulkAction}Controller.php` |
| UI | `Modules/Core/resources/views/partials/sidebar.blade.php` + index/show views with action buttons |
| Seeder | `Modules/Security/database/seeders/RolePermissionSeeder.php` |
| Tests | new `tests/Feature/PermissionEnforcementTest.php` (route coverage + per-role smoke) |

## 9. Verification

1. **Route-coverage test** — enumerate all admin route names; assert each resolves to a defined permission or is on an explicit public allowlist. Fails if a controller method is added without a guard or a route is unmapped.
2. **Per-role smoke test** — for representative roles (Cashier, Manager), assert allowed routes return 200 and disallowed routes redirect/403.
3. **Manual sweep** — log in as each seeded role; confirm the sidebar hides correctly and direct URLs are blocked.
4. Run after every batch: `php artisan route:list` parity + the QA curl sweep from `CLAUDE.md` §20.

## 10. Out of scope

- Storefront/customer permissions (separate guard/audience).
- Branch-scoped data filtering (row-level) — permissions here are feature-level only.
- POS module (disabled).
