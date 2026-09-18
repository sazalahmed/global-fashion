# Dashboard — Order-Count Cards (Group A)

**Date:** 2026-07-07
**Module:** `Modules/Dashboard`
**Status:** Approved

## Goal

Replace the 12 gradient **amount** KPI cards on the admin dashboard with **6 order-count cards** (integers, not currency). Counts respect the dashboard's existing date-range filter.

## Cards & status mapping

Orders = rows in the `sales` table (non-deleted). Scoped to `sale_date` within the selected range (defaults to today).

| Card | Status filter | Icon |
|------|---------------|------|
| Total Orders | all non-deleted sales in range | `fa-layer-group` |
| Pending Orders | `status = pending` | `fa-clock` |
| Processing Orders | `status IN (packing, courier)` | `fa-gears` |
| Delivered Orders | `status = delivered` | `fa-circle-check` |
| Hold Orders | `status = on_hold` | `fa-pause` |
| Cancelled Orders | `status = cancelled` | `fa-ban` |

## Changes

### `DashboardService`
- Add `getOrderCounts(?string $from = null, ?string $to = null): array`.
  - Mirrors the date-defaulting logic of `getKpiCards()` (today when no range given).
  - Single grouped query on `sales` (`whereBetween('sale_date', ...)`, `whereNull('deleted_at')`, `groupBy('status')`), then buckets into the 6 keys above. Processing = packing + courier. Total = sum of all statuses in range.
- Leave `getKpiCards()` untouched (avoid breaking the Dashboard API route). Verify during planning whether any consumer still uses it; if none, note as dead code (do not remove in this change).

### `DashboardController@index`
- Replace `$kpis = $this->service->getKpiCards(...)` with `$orderCounts = $this->service->getOrderCounts($from, $to)`.
- Pass `orderCounts` to the view instead of `kpis`.
- Side effect: dashboard no longer triggers the courier-balance API on load.

### `dashboard/index.blade.php`
- Delete the 3 rows of 12 gradient amount cards (`kpis[...]`).
- Add one row of 6 count cards reusing existing `bp-stat-card bp-stat-fill bp-stat-fill-cN` classes. Values are `number_format($orderCounts['...'], 0)` — no `currency_symbol()`.
- Everything below the cards (charts, top products, recent sales/expenses, low stock, monthly summary) stays as-is.

## Out of scope / constraints
- No new CSS (reuse `bp-` stat-card classes; dark mode already covered).
- No route, migration, or Sale-module changes.
- Follows CLAUDE.md: no inline CSS, no hardcoded routes, `'use strict';` unaffected (no JS changes).

## Verification
- Load `/admin` (dashboard) → 6 count cards render, no gradient amount cards.
- Switch date presets → counts change accordingly.
- `Total = Pending + Processing + Delivered + Hold + Cancelled + (other statuses)` — Total is all statuses, so it is ≥ the sum of the 5 named buckets.
