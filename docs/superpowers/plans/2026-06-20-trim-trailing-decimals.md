# Plan — Trim trailing `.00` across the admin panel

**Date:** 2026-06-20
**Branch:** `feat/product-variant-management`
**Goal:** Numbers that are whole should render as integers (e.g. `1365.00 → 1365`); fractional values keep their decimals (`1365.50 → 1365.50`). Apply across the **entire** admin panel — interactive UI, formal documents (invoices/receipts/PDF), exports, and JS-driven live displays.

---

## Background / current state

- A `money()` helper already exists in `app/Helpers/CurrencyHelper.php` (globally autoloaded) and **already implements the trim** ("Bug_18"): whole amounts drop decimals, fractional keep two. It prefixes the configured currency symbol.
- `bd_price()` / `window.bdPrice()` already handle the **storefront** — out of scope here.
- The trim has only been adopted in **2 admin blade files** so far (e.g. `Modules/Sale/resources/views/index.blade.php`).

### Scope measured
| Surface | Count | Pattern |
|---|---|---|
| Interactive admin blades | 321 | `number_format($x, 2)` in lists/show/dashboards/stat-cards |
| Formal-document blades | 22 (5 files) | print / pdf / invoice / receipt / mushak |
| Admin JS | 36 | `.toFixed(2)` (POS, live total calculators) |
| Export services | TBD | `number_format`/raw floats in xlsx/csv builders |

Two display shapes in blades:
1. **Symbol-prefixed:** `{{ currency_symbol() }} {{ number_format($x, 2) }}` or `BDT {{ number_format($x, 2) }}` → replace with `{{ money($x) }}`.
2. **Bare number:** `{{ number_format($x, 2) }}` (quantities, unit prices in tables) → replace with a new `{{ num($x) }}`.

---

## Helpers (Phase 0 — foundation)

**1. Refactor `CurrencyHelper.php` to share one trim core (DRY):**
- Extract the "whole → 0 decimals, else 2" decision into a private `_resolve_decimals($amount, $decimals)`.
- Keep `money()` behavior identical (symbol + trim).
- Add `num($amount, ?int $decimals = null)` — bare number, same trim logic, **standard grouping matching `money()`** (no symbol). Used where a currency symbol/"BDT" is NOT present on the line.
- Edge cases in both: cast null→0, handle negatives, never throw.

**2. JS formatter in `public/js/app.js`:**
- Add `window.fmtAmount(value, decimals)` mirroring the PHP trim (whole → integer, else fixed 2). `'use strict';` already in file.
- Does NOT touch intermediate math — only the final display string.

---

## Execution — phased, module by module

Order by occurrence density so the highest-impact modules land first:
`Accounting (27) → Manufacturing (13) → Asset/Payment/Inventory/Expense/AdSpend → Sale/Loan/Ecommerce → Setting/Report/Product → remaining`.

### Phase 1 — Symbol-prefixed money → `money()`
Find and replace, per module, with review:
- `{{ currency_symbol() }} {{ number_format($X, 2) }}` → `{{ money($X) }}`
- `BDT {{ number_format($X, 2) }}` → `BDT {{ num($X) }}` *(keep the literal "BDT" label if the design uses it as text; or switch fully to `money()` where the symbol is dynamic)*.

### Phase 2 — Bare numbers → `num()`
- `{{ number_format($X, 2) }}` (no symbol on the line) → `{{ num($X) }}`.

### Phase 3 — Formal documents (per decision: trim too)
- Apply the same swaps in print/pdf/invoice/receipt/mushak views.
- **Caution:** these are the highest-risk for layout (column widths) — visually verify each rendered PDF/print page.

### Phase 4 — Exports (PHP services)
- Apply trim in xlsx/csv builder services so exported cells match the UI. Keep numbers as **numeric** cell types where the lib supports it (so spreadsheets still sum), formatting only the display/string columns.

### Phase 5 — JS live displays
- Replace the **display** `.toFixed(2)` sites (POS line totals, form grand-total calculators) with `window.fmtAmount(...)`.
- **Do NOT** blind-replace: many `.toFixed(2)` feed further arithmetic — only the final `.text()/.html()/.val()` assignments change. Review each of the 36.

---

## Critical cautions (do NOT trim blindly)

1. **Form `value="..."` inputs** — `num()` adds thousands separators which break `<input type="number">` parsing on submit. For editable money inputs, leave raw (`{{ $x }}`) or trim WITHOUT grouping. Audit every `value="{{ number_format(... ,2) }}"` separately.
2. **Hidden inputs / data-* attributes** consumed by JS math — never format these.
3. **Quantities with real fractions** (e.g. 2.5 kg) — safe: trim only fires on whole numbers, so `2.50 → 2.50`, `2.00 → 2`.
4. **Percentages / tax rates** — `15.00% → 15%` is acceptable per the brief; confirm none are editable inputs (see #1).
5. **Excludes storefront** — already handled by `bd_price()`.

---

## Verification (after each module + final)

1. `php artisan route:list` — no PHP errors from edited blades.
2. `php artisan view:clear` then curl the key pages of the module (logged-in session, expect 200, grep the rendered HTML for stray `.00`).
3. For formal docs: render the PDF/print route, open and eyeball columns.
4. For exports: download xlsx, confirm it opens and totals are intact.
5. For JS: load POS, add items, confirm live totals show integers and still calculate correctly.
6. Per-module grep gate: `grep -rn "number_format([^,]*, *2" <module>/resources/views` returns only intentional/`value=` cases.
7. Commit per phase (or per module for the big ones) so each step is revertible.

---

## Rollout
- One PR on the current feature branch, commits grouped by phase/module.
- Estimated edits: ~343 blade sites + ~36 JS sites + export services + 2 helpers.
- No DB/migration changes. No schema or API contract changes (display-only).
