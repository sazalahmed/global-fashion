# Global Search — Navigation Single Source of Truth (Phased A)

**Date:** 2026-06-16
**Status:** Approved (design)
**Branch:** feat/product-variant-management

## Problem

The top-bar global search has two halves:

1. **Entity-record search** (Products, Sales, Customers, …) — driven by models implementing `SearchableInterface`. This half was expanded in the prior session (variant SKU/barcode, party-name search, Payment/Lender/Customer Group/Supplier Group, full results page, N+1 fix, double-escape fix) and is **out of scope** here.
2. **Page/navigation search** ("go to a page") — driven by `App\Services\Search\NavigationRegistry`, which holds a **hand-maintained static list (~80 entries)** that is a *separate copy* of the sidebar.

The navigation list has **drifted** from the real sidebar (`Modules/Core/resources/views/partials/sidebar.blade.php`, the documented single source of truth, ~113 `route()` links). Concrete symptoms:

- Searching **"Income"** returns nothing, although the sidebar links `money.income` ("Income"). Also missing: `money.summary`, `money.cashflow`, `money.expense`, `customers.advances`, `expense-categories`, `asset-categories`, `investment.investors.*`, most `ecommerce.*` (campaigns, menus, blog-categories, blog-comments), most `manufacturing.*`, `locations`, `settings.email/sidebar/printers/system-logs`, `security.backup/api-keys`, several `reports.*`.
- The **Settings page** is a single route (`settings.index`) with **10 in-page Bootstrap tabs** (Business Profile, Tax/VAT, Courier & Delivery, Invoice & Receipt, Notifications, Localization, SMS Gateway, Tracking & Analytics, Webhooks, Landing Page). Search has only one "General Settings" entry, so no settings section is findable.
- The static list also has **stale/suspect entries** (e.g. "Product Sync" → `ecommerce.products`, "Online Orders" → `sales.index`) that risk pointing at routes that don't match the sidebar — potential dead links.

Root cause: the navigation search list is a manually-duplicated copy of the sidebar, so it drifts every time the sidebar changes.

## Goal

- Make every navigable sidebar destination (including **Income** and all **Settings tabs**) findable in global search, using **real named routes** only.
- Remove/repair stale, unused, or dead navigation entries.
- Prevent future drift structurally, so a new sidebar item can't silently miss search.
- Do all of this with **near-zero risk to the sidebar UI** (the most-used element).

## Approach — Phased A (chosen)

Considered three options:

- **A. Shared nav config (SSoT)** — one config drives both sidebar render and search. Correct end-state, but refactoring the sidebar blade to render from config is risky (mode conditionals, `Route::has()` guards, active-state, submenus).
- **B. Rewrite the static list now** — fast, but re-creates the drift problem.
- **C. Auto-derive from routes** — lowest maintenance, but weak labels/keywords (e.g. "VAT" wouldn't find "Tax Reports"), messy grouping, awkward mode/permission handling.

**Chosen: phased version of A.** Get A's anti-drift guarantee now without the risky sidebar refactor:

- **Phase 1 (this plan):** introduce the structured config as the source for **search**, and add a **drift-guard test** that fails if the sidebar and the config diverge or if any config route is dead. The sidebar keeps rendering from its own blade for now.
- **Phase 2 (deferred, not in this plan):** re-point `sidebar.blade.php` to render *from* the same config — pure cleanup, no behavior change.

## Design

### 1. `config/navigation.php` (new) — the structured navigation list

Ordered array. Each item:

```php
[
    'label'      => 'Income',           // display text
    'route'      => 'money.income',     // named route (required, must exist)
    'params'     => [],                 // optional route params
    'icon'       => 'fa-arrow-trend-up',// FontAwesome solid class
    'keywords'   => ['revenue', 'earnings', 'money in'],
    'permission' => null,               // ?string; null = no restriction
    'mode'       => 'simple',           // 'both' | 'simple' | 'full'
    'group'      => 'Finance',          // section label
    'anchor'     => null,               // settings tab id, e.g. 'taxSettings'; else null
],
```

- **`mode`** mirrors `SettingService::isSimpleMode()`. Simple-only examples: `money.income`, `money.summary`. Full-only examples: `accounting.*`, `reports.financial`, `reports.custom`. Default `'both'`.
- **`anchor`** is used only by settings-tab entries: the route stays `settings.index`, and the built URL becomes `route('settings.index') . '#' . $anchor`.

Coverage is populated from the current sidebar (~113 links) plus the 10 settings tabs. Every entry's `route` must resolve (validated by the drift-guard test).

### 2. `NavigationRegistry` refactor

`App\Services\Search\NavigationRegistry`:

- `items()` returns `config('navigation', [])` instead of the hardcoded array.
- `search($term, $limit)` keeps current label + keyword matching, and additionally:
  - filters out items whose `mode` doesn't match the current mode (`'both'` always passes; otherwise must equal simple/full state),
  - keeps the existing permission filter (`$user->can($permission)`),
  - **skips items where `Route::has($item['route'])` is false** — defensive, so a typo can never 500 the search endpoint.

### 3. Settings tabs — searchable + deep-link

- Add all 10 settings sections as `config/navigation.php` entries: `route => 'settings.index'`, `anchor => '<tabId>'`, `group => 'Settings'`, with useful keywords (e.g. Tax/VAT → `['vat','tax','mushak','nbr']`).
  Anchor ids (from the settings view): `businessSettings`, `taxSettings`, `courierSettings`, `invoiceSettings`, `notificationSettings`, `localeSettings`, `smsSettings`, `trackingSettings`, `webhookSettings`, `landingPageSettings`.
- `SearchResultBuilder::buildNavigation()` builds the URL with the fragment when `anchor` is present.
- Settings view JS: on `DOMContentLoaded`, if `location.hash` matches a tab pane id, activate that Bootstrap tab and scroll it into view. (Add only if the page doesn't already do hash-based tab activation — to be confirmed during implementation.) All script blocks begin with `'use strict';`.

### 4. Cleanup — remove/fix unused

Validate every navigation entry against `php artisan route:list`. Drop or correct stale entries (e.g. "Product Sync", duplicate "Online Orders", any non-existent report routes). The drift-guard test below makes dead entries fail going forward.

### 5. Drift-guard test (anti-drift mechanism)

A test (e.g. `tests/Feature/NavigationSearchSyncTest.php`) that:

- **(a) Sidebar → config coverage:** read `Modules/Core/resources/views/partials/sidebar.blade.php`, extract every `route('name'...)` name, and assert each *navigable* destination exists in `config/navigation.php`. Maintain a small, explicit **allowlist** of intentional exclusions: detail/action routes such as `*.create`, `*.edit`, `*.show`, and pure submenu parents (`href="#"`). The allowlist lives in the test and is documented inline.
- **(b) Config → routes validity:** assert `Route::has($route)` is true for every config entry (no dead links).

Result: adding a sidebar destination without registering it for search, or referencing a non-existent route, fails CI.

## Out of scope

- **Entity-record search** changes (already completed in the prior session).
- **Phase 2**: re-pointing the sidebar to render from `config/navigation.php`.
- Branch-scoping of search results and FULLTEXT indexing (separate, deliberate decisions documented earlier).

## Testing strategy

- **Unit:** `NavigationRegistry` filtering — keyword/label match, permission filter, mode filter (simple vs full), and `anchor` URL construction via the builder.
- **Drift-guard:** the sidebar↔config + route-existence test above.
- **Live smoke:** authenticated requests confirming `/search?q=income` returns the Income page entry, and the built Settings/Tax URL is `…/settings#taxSettings` and opens the Tax tab.

## Files touched

- **New:** `config/navigation.php`
- **New:** `tests/Feature/NavigationSearchSyncTest.php` (+ optional `NavigationRegistryTest`)
- **Modified:** `app/Services/Search/NavigationRegistry.php` (read config; mode + `Route::has` filters)
- **Modified:** `app/Services/Search/SearchResultBuilder.php` (anchor URL for settings tabs)
- **Modified:** settings view (`Modules/Setting/resources/views/index.blade.php`) — hash-based tab activation, if not already present
- **Unchanged:** `sidebar.blade.php` (Phase 2)

## Rollout / risk

- Sidebar rendering is untouched → no risk to the primary UI in Phase 1.
- `Route::has()` guard ensures a malformed entry degrades gracefully (entry skipped) rather than erroring the search endpoint.
- The drift-guard test is the long-term safety net.
