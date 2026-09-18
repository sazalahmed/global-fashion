# Global Search Navigation SSoT Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make every navigable sidebar destination (including "Income") and all 10 Settings tabs findable in global search via real named routes, and prevent future drift with a sidebar↔config guard test.

**Architecture:** Introduce `config/navigation.php` as a structured list of navigable pages (label, route, icon, keywords, permission, mode, group, settings-anchor). Point the search `NavigationRegistry` at it (adding simple/full mode filtering and a dead-route guard), build settings-tab URLs with a `#anchor`, and add JS so a hashed Settings URL opens the right tab. A drift-guard test asserts the sidebar and config stay in sync and that no config route is dead. The sidebar blade itself is **not** changed in this phase (Phase 2, deferred).

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Bootstrap 5 tabs, jQuery, PHPUnit 11 (`php artisan test`, MySQL `bizpos_test`).

**Reference spec:** `docs/superpowers/specs/2026-06-16-global-search-navigation-ssot-design.md`

---

## File Structure

- **Create** `config/navigation.php` — the single structured navigation list consumed by search (and, in Phase 2, the sidebar).
- **Modify** `app/Services/Search/NavigationRegistry.php` — read from config; add mode filter + `Route::has()` guard.
- **Modify** `app/Services/Search/SearchResultBuilder.php` — append `#anchor` to settings-tab URLs.
- **Modify** `Modules/Setting/resources/views/index.blade.php` — add a `@push('scripts')` block that activates the tab named by `location.hash`.
- **Create** `tests/Feature/NavigationSearchSyncTest.php` — drift guard (sidebar→config coverage + config→route existence).
- **Create** `tests/Feature/NavigationRegistrySearchTest.php` — behavior (income searchable, full-mode pages hidden in simple mode, settings anchor URL).

Current relevant code (for reference while implementing):

`app/Services/Search/NavigationRegistry.php` `search()` today iterates a hardcoded `items()` array, lower-cases the term, filters by `permission` via `$user->can()`, and matches `label` or any `keywords`. `items()` currently returns a hardcoded array. We replace `items()` with `config('navigation', [])` and extend `search()`.

`app/Services/Search/SearchResultBuilder.php` `buildNavigation()` today returns `'url' => route($item['route'])` with no params/anchor.

`Modules/Setting/resources/views/index.blade.php` `@extends('core::layouts.master')`, has tab triggers `<a class="bp-settings-nav-item" href="#taxSettings" data-bs-toggle="tab">` and panes `<div class="tab-pane fade" id="taxSettings">`. It already contains one `@push('scripts') … @endpush` block (lines ~1677–2075). The master layout renders `@stack('scripts')`.

---

## Task 1: Navigation config + drift-guard test

**Files:**
- Create: `tests/Feature/NavigationSearchSyncTest.php`
- Create: `config/navigation.php`

- [ ] **Step 1: Write the failing drift-guard test**

Create `tests/Feature/NavigationSearchSyncTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Anti-drift guard for global page search. The sidebar
 * (Modules/Core/resources/views/partials/sidebar.blade.php) is the single
 * source of truth for navigation; every destination it links via route()
 * must also be registered in config/navigation.php so it is searchable.
 * And every config entry must point at a route that actually exists.
 */
class NavigationSearchSyncTest extends TestCase
{
    private const SIDEBAR = 'Modules/Core/resources/views/partials/sidebar.blade.php';

    /**
     * Sidebar route() destinations that are intentionally NOT searchable pages.
     * Keep this list tiny and documented — it is the only sanctioned drift.
     */
    private array $excludedSidebarRoutes = [
        // (none today — every sidebar link is a navigable page)
    ];

    /**
     * Config routes that may legitimately be absent in some installs
     * (optional modules). They are skipped by the route-existence check.
     */
    private array $optionalConfigRoutes = [
        'admin.ai-assistant.settings',
    ];

    private function sidebarRouteNames(): array
    {
        $source = file_get_contents(base_path(self::SIDEBAR));
        // Match route('name') generator calls only — NOT request()->routeIs('name')
        // (which has "routeIs(" not "route(") nor Route::has('name') (capital R).
        preg_match_all("/[^a-zA-Z]route\\(\\s*'([^']+)'/", $source, $m);

        return array_values(array_unique($m[1]));
    }

    private function configRouteNames(): array
    {
        return array_values(array_unique(array_map(
            fn ($item) => $item['route'],
            config('navigation', [])
        )));
    }

    public function test_every_sidebar_destination_is_registered_for_search(): void
    {
        $configRoutes = $this->configRouteNames();
        $missing = [];

        foreach ($this->sidebarRouteNames() as $route) {
            if (in_array($route, $this->excludedSidebarRoutes, true)) {
                continue;
            }
            if (!in_array($route, $configRoutes, true)) {
                $missing[] = $route;
            }
        }

        $this->assertSame(
            [],
            $missing,
            'Sidebar routes missing from config/navigation.php (add them so they are searchable): '
                . implode(', ', $missing)
        );
    }

    public function test_no_config_navigation_route_is_dead(): void
    {
        $dead = [];

        foreach (config('navigation', []) as $item) {
            $route = $item['route'];
            if (in_array($route, $this->optionalConfigRoutes, true)) {
                continue;
            }
            if (!Route::has($route)) {
                $dead[] = $route;
            }
        }

        $this->assertSame(
            [],
            $dead,
            'config/navigation.php references routes that do not exist: ' . implode(', ', $dead)
        );
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Feature/NavigationSearchSyncTest.php`
Expected: FAIL — `config('navigation')` is null/empty, so `test_every_sidebar_destination_is_registered_for_search` reports many missing routes.

- [ ] **Step 3: Create `config/navigation.php` with the full navigation list**

Create `config/navigation.php`:

```php
<?php

/*
|--------------------------------------------------------------------------
| Navigation (Single Source of Truth for page/navigation search)
|--------------------------------------------------------------------------
| Each item: label, route (named, must exist), params, icon (FA solid),
| keywords[], permission (?string; null = no restriction), mode
| ('both'|'simple'|'full' — mirrors SettingService::isSimpleMode()),
| group (section label), anchor (settings tab id, else null).
|
| Kept in sync with Modules/Core/resources/views/partials/sidebar.blade.php
| by tests/Feature/NavigationSearchSyncTest.php.
*/

return [

    // ── Workspace ──
    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'keywords' => ['home', 'overview', 'main'], 'permission' => null, 'mode' => 'both', 'group' => 'Workspace', 'anchor' => null],

    // ── Sales ──
    ['label' => 'Sales List', 'route' => 'sales.index', 'icon' => 'fa-chart-line', 'keywords' => ['sale list', 'invoices', 'sell'], 'permission' => 'sales.view', 'mode' => 'both', 'group' => 'Sales', 'anchor' => null],
    ['label' => 'Create Order', 'route' => 'sales.create', 'icon' => 'fa-plus', 'keywords' => ['new sale', 'create order', 'new order'], 'permission' => 'sales.view', 'mode' => 'both', 'group' => 'Sales', 'anchor' => null],
    ['label' => 'Quotations', 'route' => 'quotations.index', 'icon' => 'fa-file-lines', 'keywords' => ['quote', 'estimate', 'proforma'], 'permission' => 'quotations.view', 'mode' => 'both', 'group' => 'Sales', 'anchor' => null],
    ['label' => 'Sales Returns', 'route' => 'sale-returns.index', 'icon' => 'fa-rotate-left', 'keywords' => ['return sale', 'refund'], 'permission' => 'sales.view', 'mode' => 'both', 'group' => 'Sales', 'anchor' => null],

    // ── Customers ──
    ['label' => 'Customers List', 'route' => 'customers.index', 'icon' => 'fa-users', 'keywords' => ['buyer', 'client'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],
    ['label' => 'Customer Ledger', 'route' => 'customers.ledger', 'icon' => 'fa-book-open', 'keywords' => ['statement', 'balance'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],
    ['label' => 'Customer Advances', 'route' => 'customers.advances', 'icon' => 'fa-hand-holding-dollar', 'keywords' => ['advance', 'prepaid'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],
    ['label' => 'Customer Groups', 'route' => 'customer-groups.index', 'icon' => 'fa-users-rectangle', 'keywords' => ['segment'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],
    ['label' => 'Areas', 'route' => 'customer-areas.index', 'icon' => 'fa-map-marker-alt', 'keywords' => ['zone', 'region', 'location'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],

    // ── Catalog ──
    ['label' => 'Products List', 'route' => 'products.index', 'icon' => 'fa-boxes-stacked', 'keywords' => ['items', 'inventory'], 'permission' => 'products.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Add Product', 'route' => 'products.create', 'icon' => 'fa-plus', 'keywords' => ['new product', 'create product'], 'permission' => 'products.create', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Categories', 'route' => 'categories.index', 'icon' => 'fa-tags', 'keywords' => ['product categories'], 'permission' => 'categories.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Brands', 'route' => 'brands.index', 'icon' => 'fa-copyright', 'keywords' => ['manufacturers'], 'permission' => 'brands.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Units', 'route' => 'units.index', 'icon' => 'fa-ruler', 'keywords' => ['uom', 'measurement'], 'permission' => null, 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Variants', 'route' => 'variants.index', 'icon' => 'fa-swatchbook', 'keywords' => ['size', 'color', 'attributes'], 'permission' => null, 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Print Barcode', 'route' => 'barcode.index', 'icon' => 'fa-barcode', 'keywords' => ['label', 'sticker'], 'permission' => null, 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Stock Overview', 'route' => 'inventory.index', 'icon' => 'fa-cubes-stacked', 'keywords' => ['stock', 'quantity', 'overview'], 'permission' => 'inventory.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Stock Alerts', 'route' => 'inventory.alerts', 'icon' => 'fa-bell', 'keywords' => ['low stock', 'reorder'], 'permission' => 'inventory.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Stock Adjustments', 'route' => 'inventory.adjustments', 'icon' => 'fa-sliders', 'keywords' => ['adjust stock', 'correction'], 'permission' => 'inventory.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],

    // ── Purchasing ──
    ['label' => 'Purchase Orders', 'route' => 'purchases.index', 'icon' => 'fa-cart-plus', 'keywords' => ['po', 'buy', 'purchase order'], 'permission' => 'purchases.view', 'mode' => 'both', 'group' => 'Purchasing', 'anchor' => null],
    ['label' => 'Purchase Returns', 'route' => 'purchase-returns.index', 'icon' => 'fa-rotate-left', 'keywords' => ['return purchase'], 'permission' => 'purchases.view', 'mode' => 'both', 'group' => 'Purchasing', 'anchor' => null],
    ['label' => 'Suppliers List', 'route' => 'supplier.index', 'icon' => 'fa-truck-field', 'keywords' => ['vendor'], 'permission' => 'suppliers.view', 'mode' => 'both', 'group' => 'Purchasing', 'anchor' => null],
    ['label' => 'Supplier Groups', 'route' => 'supplier-groups.index', 'icon' => 'fa-layer-group', 'keywords' => ['supplier group'], 'permission' => 'suppliers.view', 'mode' => 'both', 'group' => 'Purchasing', 'anchor' => null],

    // ── Finance ──
    ['label' => "Today's Summary", 'route' => 'money.summary', 'icon' => 'fa-chart-pie', 'keywords' => ['today summary', 'daily money', 'money summary'], 'permission' => null, 'mode' => 'simple', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Income', 'route' => 'money.income', 'icon' => 'fa-arrow-trend-up', 'keywords' => ['revenue', 'earnings', 'money in'], 'permission' => null, 'mode' => 'simple', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Cash Flow', 'route' => 'money.cashflow', 'icon' => 'fa-right-left', 'keywords' => ['cash flow', 'cash movement'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Payment Accounts', 'route' => 'payment-accounts.index', 'icon' => 'fa-wallet', 'keywords' => ['cash', 'bank', 'bkash', 'nagad', 'account'], 'permission' => 'payments.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Investment', 'route' => 'investment.dashboard', 'icon' => 'fa-hand-holding-dollar', 'keywords' => ['investor', 'shareholder', 'capital', 'dividend'], 'permission' => 'accounting.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Expenses List', 'route' => 'expenses.index', 'icon' => 'fa-money-bill-wave', 'keywords' => ['cost', 'spend'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Expense Categories', 'route' => 'expense-categories.index', 'icon' => 'fa-tags', 'keywords' => ['expense category'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Assets', 'route' => 'assets.index', 'icon' => 'fa-building', 'keywords' => ['fixed asset', 'equipment'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Asset Categories', 'route' => 'asset-categories.index', 'icon' => 'fa-tags', 'keywords' => ['asset category'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Loans', 'route' => 'loans.index', 'icon' => 'fa-hand-holding-dollar', 'keywords' => ['borrow', 'lending'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Lenders', 'route' => 'lenders.index', 'icon' => 'fa-landmark', 'keywords' => ['lender', 'creditor'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],

    // ── Accounting (full mode only) ──
    ['label' => 'Journal Entries', 'route' => 'accounting.journal-entries', 'icon' => 'fa-book', 'keywords' => ['journal', 'debit credit', 'double entry'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'General Ledger', 'route' => 'accounting.general-ledger', 'icon' => 'fa-book-open', 'keywords' => ['gl', 'ledger'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Trial Balance', 'route' => 'accounting.trial-balance', 'icon' => 'fa-scale-balanced', 'keywords' => ['tb'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Profit & Loss', 'route' => 'accounting.profit-loss', 'icon' => 'fa-chart-pie', 'keywords' => ['pnl', 'income statement'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Balance Sheet', 'route' => 'accounting.balance-sheet', 'icon' => 'fa-scale-balanced', 'keywords' => ['bs', 'financial position'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Cash Flow Statement', 'route' => 'accounting.cash-flow', 'icon' => 'fa-water', 'keywords' => ['cash flow statement'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Credit Notes', 'route' => 'accounting.credit-notes.index', 'icon' => 'fa-file-circle-minus', 'keywords' => ['credit note', 'cn'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Debit Notes', 'route' => 'accounting.debit-notes.index', 'icon' => 'fa-file-circle-plus', 'keywords' => ['debit note', 'dn'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Receipts', 'route' => 'accounting.receipts.index', 'icon' => 'fa-file-invoice-dollar', 'keywords' => ['receipt', 'voucher'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],

    // ── Online Store ──
    ['label' => 'Campaigns', 'route' => 'ecommerce.campaigns.index', 'icon' => 'fa-bullhorn', 'keywords' => ['campaign'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Coupons', 'route' => 'ecommerce.coupons', 'icon' => 'fa-ticket', 'keywords' => ['discount code', 'promo'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Flash Deals', 'route' => 'ecommerce.flash-deals', 'icon' => 'fa-bolt', 'keywords' => ['flash sale', 'limited offer'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Collections', 'route' => 'ecommerce.collections', 'icon' => 'fa-layer-group', 'keywords' => ['product collection'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Banners', 'route' => 'ecommerce.banners', 'icon' => 'fa-image', 'keywords' => ['slider', 'hero'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Homepage Builder', 'route' => 'ecommerce.homepage-sections', 'icon' => 'fa-puzzle-piece', 'keywords' => ['homepage', 'storefront builder', 'sections'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Menus', 'route' => 'ecommerce.menus.index', 'icon' => 'fa-bars', 'keywords' => ['menu', 'navigation'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Blog', 'route' => 'ecommerce.blog', 'icon' => 'fa-pen-nib', 'keywords' => ['article', 'content'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Blog Categories', 'route' => 'ecommerce.blog-categories', 'icon' => 'fa-tags', 'keywords' => ['blog category'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Blog Comments', 'route' => 'ecommerce.blog-comments', 'icon' => 'fa-comments', 'keywords' => ['blog comment'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Shipping Zones', 'route' => 'ecommerce.shipping', 'icon' => 'fa-truck-fast', 'keywords' => ['shipping', 'delivery zone', 'courier'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Store Settings', 'route' => 'ecommerce.settings', 'icon' => 'fa-gear', 'keywords' => ['store config', 'ecommerce settings'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],

    // ── Reports ──
    ['label' => 'Daily Summary (DTS)', 'route' => 'reports.dts', 'icon' => 'fa-file-lines', 'keywords' => ['dts', 'daily trading', 'daily report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Monthly Summary', 'route' => 'reports.monthly-summary', 'icon' => 'fa-file-lines', 'keywords' => ['monthly report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Sales Reports', 'route' => 'reports.sales', 'icon' => 'fa-file-lines', 'keywords' => ['sales report', 'revenue report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Detail Sales Report', 'route' => 'reports.detail-sales', 'icon' => 'fa-file-lines', 'keywords' => ['detail sales'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Category-wise Report', 'route' => 'reports.category-wise', 'icon' => 'fa-file-lines', 'keywords' => ['category sales'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Inventory Reports', 'route' => 'reports.inventory', 'icon' => 'fa-file-lines', 'keywords' => ['stock report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Purchase Reports', 'route' => 'reports.purchase', 'icon' => 'fa-file-lines', 'keywords' => ['purchase report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Receivables Aging', 'route' => 'reports.receivables-aging', 'icon' => 'fa-file-lines', 'keywords' => ['receivable', 'aging', 'due report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Profit & Loss Report', 'route' => 'reports.profit-loss', 'icon' => 'fa-file-lines', 'keywords' => ['pnl report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Cash Movement', 'route' => 'reports.cash-movement', 'icon' => 'fa-file-lines', 'keywords' => ['cash movement'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Supplier Payments', 'route' => 'reports.supplier-payments', 'icon' => 'fa-file-lines', 'keywords' => ['supplier payment report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Customer Reports', 'route' => 'reports.customer', 'icon' => 'fa-file-lines', 'keywords' => ['customer report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'VAT / Tax Reports', 'route' => 'reports.tax', 'icon' => 'fa-file-lines', 'keywords' => ['vat', 'tax report', 'mushak'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Financial Reports', 'route' => 'reports.financial', 'icon' => 'fa-file-lines', 'keywords' => ['financial report'], 'permission' => 'reports.view', 'mode' => 'full', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Staff Reports', 'route' => 'reports.staff', 'icon' => 'fa-file-lines', 'keywords' => ['staff report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Custom Report Builder', 'route' => 'reports.custom', 'icon' => 'fa-file-lines', 'keywords' => ['custom builder', 'report builder'], 'permission' => 'reports.view', 'mode' => 'full', 'group' => 'Reports', 'anchor' => null],

    // ── Marketing ──
    ['label' => 'Ad Spend', 'route' => 'adspend.index', 'icon' => 'fa-bullhorn', 'keywords' => ['ad spend', 'advertising'], 'permission' => 'marketing.view', 'mode' => 'both', 'group' => 'Marketing', 'anchor' => null],
    ['label' => 'SMS Campaigns', 'route' => 'marketing.sms-campaigns', 'icon' => 'fa-comment-sms', 'keywords' => ['sms', 'bulk sms'], 'permission' => 'marketing.view', 'mode' => 'both', 'group' => 'Marketing', 'anchor' => null],
    ['label' => 'Email Marketing', 'route' => 'marketing.email', 'icon' => 'fa-envelope', 'keywords' => ['email campaign', 'newsletter'], 'permission' => 'marketing.view', 'mode' => 'both', 'group' => 'Marketing', 'anchor' => null],
    ['label' => 'Loyalty Program', 'route' => 'marketing.loyalty', 'icon' => 'fa-medal', 'keywords' => ['loyalty', 'points', 'rewards'], 'permission' => 'marketing.view', 'mode' => 'both', 'group' => 'Marketing', 'anchor' => null],

    // ── HR ──
    ['label' => 'Employees', 'route' => 'employee.index', 'icon' => 'fa-id-badge', 'keywords' => ['staff', 'worker'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Attendance', 'route' => 'attendance.index', 'icon' => 'fa-fingerprint', 'keywords' => ['check in', 'check out'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Leave Management', 'route' => 'attendance.leave', 'icon' => 'fa-calendar-xmark', 'keywords' => ['leave', 'absence', 'vacation'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Leave Types', 'route' => 'attendance.leave-types.index', 'icon' => 'fa-list', 'keywords' => ['leave type'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Weekends & Holidays', 'route' => 'attendance.config', 'icon' => 'fa-calendar-days', 'keywords' => ['weekend', 'holiday'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Payroll', 'route' => 'payroll.index', 'icon' => 'fa-money-check-dollar', 'keywords' => ['salary', 'wage', 'payslip'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],

    // ── Manufacturing ──
    ['label' => 'Manufacturing Dashboard', 'route' => 'manufacturing.dashboard', 'icon' => 'fa-gauge-high', 'keywords' => ['mfg dashboard', 'production dashboard'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Production Orders', 'route' => 'manufacturing.production-orders.index', 'icon' => 'fa-clipboard-list', 'keywords' => ['production', 'work order'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Purchase Orders (RM)', 'route' => 'manufacturing.rm-purchases.index', 'icon' => 'fa-cart-plus', 'keywords' => ['rm purchase', 'raw material purchase'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Raw Materials', 'route' => 'manufacturing.raw-materials.index', 'icon' => 'fa-scissors', 'keywords' => ['raw material', 'fabric'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Colors', 'route' => 'manufacturing.colors.index', 'icon' => 'fa-palette', 'keywords' => ['color'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Sizes', 'route' => 'manufacturing.sizes.index', 'icon' => 'fa-ruler-combined', 'keywords' => ['size'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Suppliers (RM)', 'route' => 'manufacturing.suppliers.index', 'icon' => 'fa-truck-field', 'keywords' => ['rm supplier'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Factories', 'route' => 'manufacturing.factories.index', 'icon' => 'fa-industry', 'keywords' => ['factory'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Catalogs', 'route' => 'manufacturing.catalogs.index', 'icon' => 'fa-book', 'keywords' => ['catalog', 'bom'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Damages', 'route' => 'manufacturing.damages.index', 'icon' => 'fa-triangle-exclamation', 'keywords' => ['damage'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'RM Wastes', 'route' => 'manufacturing.wastes.rm.index', 'icon' => 'fa-trash', 'keywords' => ['rm waste'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Product Wastes', 'route' => 'manufacturing.wastes.products.index', 'icon' => 'fa-trash', 'keywords' => ['product waste'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'RM Stock Report', 'route' => 'manufacturing.reports.rm-stock', 'icon' => 'fa-file-lines', 'keywords' => ['rm stock report'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Production Report', 'route' => 'manufacturing.reports.production', 'icon' => 'fa-file-lines', 'keywords' => ['production report'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Damage Report', 'route' => 'manufacturing.reports.damage', 'icon' => 'fa-file-lines', 'keywords' => ['damage report'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Waste Report', 'route' => 'manufacturing.reports.waste', 'icon' => 'fa-file-lines', 'keywords' => ['waste report'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Cost Analysis', 'route' => 'manufacturing.reports.cost-analysis', 'icon' => 'fa-file-lines', 'keywords' => ['cost analysis'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],

    // ── System ──
    ['label' => 'Locations', 'route' => 'locations.index', 'icon' => 'fa-location-dot', 'keywords' => ['location', 'warehouse', 'outlet'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Printers', 'route' => 'settings.printers.index', 'icon' => 'fa-print', 'keywords' => ['printer'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'General Settings', 'route' => 'settings.index', 'icon' => 'fa-gears', 'keywords' => ['settings', 'configuration', 'setup', 'general'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Email Config', 'route' => 'settings.email', 'icon' => 'fa-envelope', 'keywords' => ['email config', 'smtp'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Sidebar Menu', 'route' => 'settings.sidebar', 'icon' => 'fa-bars', 'keywords' => ['sidebar menu', 'menu config'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'AI Assistant', 'route' => 'admin.ai-assistant.settings', 'icon' => 'fa-robot', 'keywords' => ['ai assistant', 'ai'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'User Management', 'route' => 'security.users.index', 'icon' => 'fa-user-gear', 'keywords' => ['user management', 'admin', 'users'], 'permission' => 'users.view', 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Roles & Permissions', 'route' => 'security.roles', 'icon' => 'fa-shield-halved', 'keywords' => ['roles', 'permissions', 'access control'], 'permission' => 'users.view', 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Backup & Restore', 'route' => 'security.backup', 'icon' => 'fa-database', 'keywords' => ['backup', 'restore'], 'permission' => 'users.view', 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'API Management', 'route' => 'security.api-keys', 'icon' => 'fa-key', 'keywords' => ['api', 'api keys', 'token'], 'permission' => 'users.view', 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Activity Log', 'route' => 'activities.index', 'icon' => 'fa-clock-rotate-left', 'keywords' => ['activity', 'audit log', 'history'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'System Logs', 'route' => 'settings.system-logs.index', 'icon' => 'fa-terminal', 'keywords' => ['system log', 'logs'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],

    // ── Settings tabs (deep-link to settings.index#anchor) ──
    ['label' => 'Business Profile', 'route' => 'settings.index', 'icon' => 'fa-building', 'keywords' => ['company', 'profile', 'business'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'businessSettings'],
    ['label' => 'Tax / VAT', 'route' => 'settings.index', 'icon' => 'fa-percent', 'keywords' => ['vat', 'tax', 'mushak', 'nbr'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'taxSettings'],
    ['label' => 'Courier & Delivery', 'route' => 'settings.index', 'icon' => 'fa-truck-fast', 'keywords' => ['courier', 'delivery', 'pathao', 'steadfast', 'redx'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'courierSettings'],
    ['label' => 'Invoice & Receipt', 'route' => 'settings.index', 'icon' => 'fa-file-invoice', 'keywords' => ['invoice', 'receipt', 'print format'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'invoiceSettings'],
    ['label' => 'Notifications', 'route' => 'settings.index', 'icon' => 'fa-bell', 'keywords' => ['notification', 'alert'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'notificationSettings'],
    ['label' => 'Localization', 'route' => 'settings.index', 'icon' => 'fa-language', 'keywords' => ['locale', 'language', 'timezone', 'currency'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'localeSettings'],
    ['label' => 'SMS Gateway', 'route' => 'settings.index', 'icon' => 'fa-comment-sms', 'keywords' => ['sms gateway', 'ssl wireless', 'bulksmsbd'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'smsSettings'],
    ['label' => 'Tracking & Analytics', 'route' => 'settings.index', 'icon' => 'fa-chart-line', 'keywords' => ['tracking', 'analytics', 'pixel', 'gtag'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'trackingSettings'],
    ['label' => 'Webhooks', 'route' => 'settings.index', 'icon' => 'fa-link', 'keywords' => ['webhook', 'integration'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'webhookSettings'],
    ['label' => 'Landing Page Settings', 'route' => 'settings.index', 'icon' => 'fa-image', 'keywords' => ['landing page'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'landingPageSettings'],

];
```

- [ ] **Step 4: Clear config cache and run the test to verify it passes**

Run: `php artisan config:clear && php artisan test tests/Feature/NavigationSearchSyncTest.php`
Expected: PASS (2 tests). If `test_every_sidebar_destination_is_registered_for_search` lists a missing route, add that exact route to `config/navigation.php` with appropriate metadata and re-run. If `test_no_config_navigation_route_is_dead` lists a route, fix the route name in the config (it must match the sidebar exactly).

- [ ] **Step 5: Commit**

```bash
git add config/navigation.php tests/Feature/NavigationSearchSyncTest.php
git commit -m "feat(search): add navigation config SSoT + sidebar drift-guard test

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: Point NavigationRegistry at the config (mode + dead-route filters)

**Files:**
- Create: `tests/Feature/NavigationRegistrySearchTest.php`
- Modify: `app/Services/Search/NavigationRegistry.php`

- [ ] **Step 1: Write the failing behavior test**

Create `tests/Feature/NavigationRegistrySearchTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Search\NavigationRegistry;
use Tests\TestCase;

/**
 * Behavior of the config-driven navigation search: coverage of the Income
 * page, simple/full mode filtering, and that results carry the config item
 * shape (route + anchor) for the result builder.
 */
class NavigationRegistrySearchTest extends TestCase
{
    private function search(string $term): array
    {
        $this->actingAs(User::factory()->create());

        return app(NavigationRegistry::class)->search($term, 20);
    }

    public function test_income_page_is_searchable(): void
    {
        // SettingService::isSimpleMode() is true by default, so the simple-mode
        // "Income" page must surface.
        $routes = array_column($this->search('income'), 'route');

        $this->assertContains('money.income', $routes);
    }

    public function test_full_mode_pages_are_hidden_in_simple_mode(): void
    {
        // 'Journal Entries' is full-mode only; in simple mode it must not appear.
        $routes = array_column($this->search('journal'), 'route');

        $this->assertNotContains('accounting.journal-entries', $routes);
    }

    public function test_settings_tab_items_carry_their_anchor(): void
    {
        $tax = collect($this->search('vat'))
            ->firstWhere('route', 'settings.index');

        $this->assertNotNull($tax, 'Expected a settings.index result for "vat"');
        $this->assertSame('taxSettings', $tax['anchor'] ?? null);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Feature/NavigationRegistrySearchTest.php`
Expected: FAIL — `NavigationRegistry` still reads its old hardcoded `items()` which has no "Income" entry and no `anchor` keys, so `test_income_page_is_searchable` and `test_settings_tab_items_carry_their_anchor` fail.

- [ ] **Step 3: Refactor `NavigationRegistry`**

Replace the entire contents of `app/Services/Search/NavigationRegistry.php` with:

```php
<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Setting\Services\SettingService;

class NavigationRegistry
{
    /**
     * Search navigation items by term, respecting simple/full mode and
     * permissions. Items pointing at a non-existent route are skipped so a
     * stale entry can never 500 the search endpoint.
     *
     * @return array<int, array>
     */
    public function search(string $term, int $limit = 5): array
    {
        $term = mb_strtolower($term);
        $user = Auth::user();
        $simpleMode = SettingService::isSimpleMode();
        $results = [];

        foreach (static::items() as $item) {
            // Mode filter — mirror the sidebar's simple/full visibility.
            $mode = $item['mode'] ?? 'both';
            if ($mode === 'simple' && !$simpleMode) {
                continue;
            }
            if ($mode === 'full' && $simpleMode) {
                continue;
            }

            // Permission filter.
            $permission = $item['permission'] ?? null;
            if ($permission !== null && $user && !$user->can($permission)) {
                continue;
            }

            // Dead-route guard — skip optional/missing routes gracefully.
            if (!Route::has($item['route'])) {
                continue;
            }

            // Match label or any keyword.
            $matches = str_contains(mb_strtolower($item['label']), $term);
            if (!$matches) {
                foreach (($item['keywords'] ?? []) as $keyword) {
                    if (str_contains(mb_strtolower($keyword), $term)) {
                        $matches = true;
                        break;
                    }
                }
            }

            if ($matches) {
                $results[] = $item;
                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * All navigable pages — the single source of truth lives in config.
     *
     * @return array<int, array>
     */
    public static function items(): array
    {
        return config('navigation', []);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test tests/Feature/NavigationRegistrySearchTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Search/NavigationRegistry.php tests/Feature/NavigationRegistrySearchTest.php
git commit -m "refactor(search): drive navigation search from config + mode/dead-route filters

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Build settings-tab URLs with their anchor

**Files:**
- Modify: `app/Services/Search/SearchResultBuilder.php`
- Test: `tests/Feature/NavigationRegistrySearchTest.php` (add one method)

- [ ] **Step 1: Write the failing test**

Add this method to `tests/Feature/NavigationRegistrySearchTest.php` (inside the class):

```php
    public function test_builder_appends_anchor_to_settings_tab_url(): void
    {
        $builder = app(\App\Services\Search\SearchResultBuilder::class);

        $result = $builder->buildNavigation([
            'label'  => 'Tax / VAT',
            'route'  => 'settings.index',
            'icon'   => 'fa-percent',
            'group'  => 'Settings',
            'anchor' => 'taxSettings',
        ], 'vat');

        $this->assertStringEndsWith('#taxSettings', $result['url']);
    }

    public function test_builder_omits_anchor_when_absent(): void
    {
        $builder = app(\App\Services\Search\SearchResultBuilder::class);

        $result = $builder->buildNavigation([
            'label'  => 'Income',
            'route'  => 'money.income',
            'icon'   => 'fa-arrow-trend-up',
            'group'  => 'Finance',
            'anchor' => null,
        ], 'income');

        $this->assertStringNotContainsString('#', $result['url']);
    }
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Feature/NavigationRegistrySearchTest.php --filter test_builder_appends_anchor_to_settings_tab_url`
Expected: FAIL — current `buildNavigation()` returns `route('settings.index')` with no `#taxSettings`.

- [ ] **Step 3: Update `buildNavigation()`**

In `app/Services/Search/SearchResultBuilder.php`, replace the `buildNavigation()` method with:

```php
    /**
     * Build a navigation search result. Settings-tab items carry an `anchor`
     * (a tab pane id); the URL gets a matching `#fragment` so the page opens
     * on the right tab.
     */
    public function buildNavigation(array $item, string $term): array
    {
        $url = route($item['route'], $item['params'] ?? []);

        if (!empty($item['anchor'])) {
            $url .= '#' . $item['anchor'];
        }

        return [
            'title'    => $this->highlight($item['label'], $term),
            'subtitle' => $item['group'] ?? '',
            'url'      => $url,
            'icon'     => $item['icon'],
            'type'     => 'Page',
        ];
    }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test tests/Feature/NavigationRegistrySearchTest.php`
Expected: PASS (5 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Search/SearchResultBuilder.php tests/Feature/NavigationRegistrySearchTest.php
git commit -m "feat(search): deep-link settings-tab results to their #anchor

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 4: Activate the Settings tab named by the URL hash

**Files:**
- Modify: `Modules/Setting/resources/views/index.blade.php`

- [ ] **Step 1: Append a hash-activation script block**

At the very END of `Modules/Setting/resources/views/index.blade.php` (after the existing `@endpush` on line ~2075), append a new push block:

```blade

@push('scripts')
<script>
'use strict';
(function () {
    // Open the settings tab referenced by the URL hash (e.g. /admin/settings#taxSettings),
    // so deep links from global search land directly on the right section.
    function activateFromHash() {
        var hash = window.location.hash;
        if (!hash || hash.length < 2) { return; }

        var trigger = document.querySelector('.bp-settings-nav-item[href="' + hash + '"]');
        if (trigger && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(trigger).show();
            var pane = document.querySelector(hash);
            if (pane) { pane.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        }
    }
    document.addEventListener('DOMContentLoaded', activateFromHash);
})();
</script>
@endpush
```

- [ ] **Step 2: Verify the Blade still compiles**

Run: `php artisan view:clear && php artisan view:cache`
Expected: `view:cache` completes with `INFO  Blade templates cached successfully.` and no compile error for the settings view. (Then optionally `php artisan view:clear` again to drop the cache in dev.)

- [ ] **Step 3: Commit**

```bash
git add Modules/Setting/resources/views/index.blade.php
git commit -m "feat(settings): open the tab named by the URL hash (search deep links)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 5: Full verification (suite + live smoke)

**Files:** none (verification only)

- [ ] **Step 1: Run the full search-related test suite**

Run: `php artisan config:clear && php artisan test tests/Feature/NavigationSearchSyncTest.php tests/Feature/NavigationRegistrySearchTest.php tests/Feature/GlobalSearchTest.php`
Expected: ALL PASS (Task 1: 2, Task 2/3: 5, plus the existing GlobalSearchTest: 5).

- [ ] **Step 2: Live smoke — Income surfaces and Settings deep-link resolves**

Start the server and log in, then check the quick-search JSON and the settings deep link:

```bash
php artisan serve --host=127.0.0.1 --port=8123 >/tmp/srv.log 2>&1 &
sleep 4
B=http://127.0.0.1:8123
curl -s -c /tmp/bz.txt $B/admin/login -o /tmp/lp.html
CSRF=$(grep -oE 'name="_token"[^>]*value="[^"]+"' /tmp/lp.html | head -1 | grep -oE 'value="[^"]+"' | sed 's/value="//;s/"//')
curl -s -c /tmp/bz.txt -b /tmp/bz.txt -X POST $B/admin/login -d "_token=${CSRF}&email=admin@gmail.com&password=1234" -o /dev/null
echo "--- search 'income' (expect money/income page) ---"
curl -s -b /tmp/bz.txt "$B/search?q=income" | grep -o '"url":"[^"]*money[^"]*income[^"]*"' | head -1
echo "--- search 'vat' (expect settings#taxSettings) ---"
curl -s -b /tmp/bz.txt "$B/search?q=vat" | grep -o '"url":"[^"]*settings#taxSettings"' | head -1
pkill -f "artisan serve"
```
Expected: the first grep prints a URL containing `money/income`; the second prints a URL ending `settings#taxSettings`.

- [ ] **Step 3: Manual browser check (optional but recommended)**

Open `http://127.0.0.1:8000/admin/settings#taxSettings` in a browser; the Tax / VAT tab should be active and scrolled into view.

- [ ] **Step 4: Final commit (if any tracked changes remain)**

```bash
git status --short
# If nothing is staged, this task produced no code changes — skip the commit.
```

---

## Self-Review Notes (already applied)

- **Spec coverage:** config SSoT (Task 1), `NavigationRegistry` refactor + mode/dead-route filters (Task 2), settings-tab anchor URLs (Task 3), settings hash-activation JS (Task 4), drift-guard test (Task 1), coverage of Income + all sidebar destinations + 10 settings tabs (Task 1 config), cleanup of dead entries (enforced by the dead-route test in Task 1), verification (Task 5). Phase-2 sidebar render refactor is explicitly out of scope.
- **Mode note:** `SettingService::getAccountingMode()` currently returns `'simple'` unconditionally, so `isSimpleMode()` is always true today. The mode filter is therefore validated in the simple direction (full-only pages hidden); it is forward-compatible if full mode is enabled later.
- **Regex note:** the drift test matches `route('…')` generator calls but not `request()->routeIs('…')` (substring `routeIs(`, no `route(`) nor `Route::has('…')` (capital `R`). The leading `[^a-zA-Z]` prevents matching inside identifiers.
- **Type consistency:** config item keys (`label, route, params, icon, keywords, permission, mode, group, anchor`) are read consistently by `NavigationRegistry::search()` and `SearchResultBuilder::buildNavigation()`.
