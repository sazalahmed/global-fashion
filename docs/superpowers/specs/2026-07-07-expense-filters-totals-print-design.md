# Expense — Filters, Totals Row & Print (Group B)

**Date:** 2026-07-07
**Modules:** `Modules/Expense`, `App\Exports`, `App\Http\Controllers\ExportController`, `Modules/Core` (export-dropdown component)
**Status:** Approved

## Problems being fixed
1. Exports ignore the category filter — `ExpensesExport::query()` reads `category_id` but the URL sends `category`; it also ignores `status` and `payment_account_id`.
2. The payment-method filter on the list is dead — controller `only([...])` and `ExpenseService::list()`/`getStats()` don't handle `payment_account_id`.
3. No totals row in the table or exports.
4. Shared export dropdown has no Print option.
5. PDF export reported "0 pages" — verify genuine vs `file`-tool quirk.

## Changes

### `ExpenseController@index`
- Accept `payment_account_id` in the filters passed to the service (`$request->only([... , 'payment_account_id'])`).
- Compute `$filteredTotal = $this->service->filteredTotal($filters)` and pass to the view.

### `ExpenseService`
- `list()` and `getStats()`: add `->when($filters['payment_account_id'] ?? null, fn ($q, $v) => $q->where('payment_account_id', $v))`.
- Add `filteredTotal(array $filters): float` — same filter chain as `list()`, `->sum('total_amount')`.

### Index view (`expense::index`)
- Add a `<tfoot>` totals row after `</tbody>` showing `money($filteredTotal)` under the Amount column, spanning the earlier columns with a "Total" label. Distinct class `bp-table-total-row` (add to `style.css` with `[data-theme="dark"]` override if not present).

### `App\Exports\ExpensesExport`
- Convert from `FromQuery` to `FromCollection, WithHeadings`.
- `collection()`: build the filtered query (mirroring `list()` filters with correct keys — `search` via `Expense::search()` scope, `category`, `status`, `payment_account_id`, `date_from`, `date_to`), map each row to the array shape, then append a final **TOTAL** row (`['', '', '', 'TOTAL', <sum of total_amount>, '', '', '']`).
- Amount column uses `total_amount` (matches the list), not `amount`.
- Keep `headings()`.

### `ExportController`
- Extract `resolveTableData($export): array` returning `[$headings, $rows]`:
  - If `$export instanceof \Maatwebsite\Excel\Concerns\FromCollection`: `$rows = $export->collection()` (already arrays incl. totals), `$headings = $export->headings()`.
  - Else: `$rows = $export->query()->limit(500)->get()->map(fn ($i) => $export->map($i))`, `$headings = $export->headings()`.
- `exportPdf()` uses `resolveTableData()`.
- Add `format === 'print'` branch → `printTable($export, $module)` renders `exports.table-print` (normal HTML response, not download).

### `resources/views/exports/table-print.blade.php` (new)
- Same table markup/styles as `table-pdf`, plus a small `'use strict';` script calling `window.print()` on load. Title = "<Module> Report".

### `export-dropdown.blade.php`
- Add a **Print** `<li>` linking to `route('export', array_merge(['module' => $module, 'format' => 'print'], $exportQuery))` with `target="_blank"`. Appears for all modules.

## Out of scope
- Totals rows for other modules' exports (only Expense requested). Their `FromQuery` exports are untouched and keep working; Print works for them without a totals row.

## Verification
- `/admin/expenses?category=X` → list filters (already worked) + stats reflect filter.
- `/export/expenses?format=xlsx|csv&category=X` → only category X rows + TOTAL row.
- `/export/expenses?format=pdf` → renders rows + TOTAL, non-empty pages.
- `/export/expenses?format=print` → HTML table opens, print dialog fires.
- Payment-method filter on list now narrows results.
- Regression: `/export/sales?format=pdf` and `?format=print` still work (FromQuery path).
