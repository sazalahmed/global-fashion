# Quotation Variant-Picker Modal + Custom Note — Design

**Date:** 2026-06-06
**Module:** Quotation
**Status:** Approved (design)

## Problem

When quoting a variable product, the admin needs to pick from **all** of that
product's variants and capture a free-text note describing the selection (real
example from a paper challan: `OED-02 (lemon + sky + Biscuit) — 12P @ 380`).
The current Quotation create/edit page only lets the admin drill into one
variant at a time (inline dropdown via `BpProductSearch.showList`), and
`quotation_items` has nowhere to store a per-line custom note.

## Goals

1. Picking a **variable** product opens a **modal** listing every variant, with
   per-variant **quantity** and **price** inputs, plus a **custom-note** field.
2. On confirm, add **one combined quotation line** for the product (not one line
   per variant), matching the challan's single-rate, single-quantity style.
3. **Every** line (simple, variant, combined) has an editable custom-note field
   that appears on the quotation show + print.

## Non-Goals

- One-line-per-variant output (explicitly rejected — combined line chosen).
- Carrying the custom note into a **converted sale** (`sale_items` has no note
  column; quotation-only for now).
- An "add new variant" panel inside the modal (the Purchase modal has one; not
  needed here — YAGNI).
- Changes to Sale / POS / Purchase variant flows.

## Data Model

Migration on `quotation_items`:

```php
$table->string('custom_note', 500)->nullable()->after('product_sku');
```

- Add `custom_note` to `QuotationItem::$fillable`.
- No other column changes. `variant_id` stays nullable and is `null` for a
  combined multi-variant line.

## Backend

- **`StoreQuotationRequest`** (used by store + update):
  - add `items.*.custom_note => ['nullable', 'string', 'max:500']`
  - `items.*.variant_id` remains `['nullable', 'exists:product_variants,id']`
- **`QuotationService::createItems()`**: persist `custom_note` (default `null`).
- **`QuotationService::calculateTotals()`**: unchanged — it already computes
  `subtotal = Σ(quantity × unit_price − discount_amount)`, which a combined line
  satisfies.

## Combined-Line Math

Built client-side from the modal's per-variant `{qty, price}` rows:

- `quantity = Σ qty`
- If all chosen variant prices are equal → `unit_price` = that price (exact;
  matches the challan single-rate case).
- If prices differ → `unit_price = round(Σ(qty × price) / Σ qty, 2)` (weighted
  average). The displayed line subtotal is recomputed by the existing JS as
  `qty × unit_price − discount`; rounding error is sub-paisa and acceptable for
  an estimate document.
- `variant_id = null`; the chosen variants are recorded in `custom_note`,
  auto-prefilled as a comma list (e.g. `Lemon, Sky, Biscuit`) and editable.

## Frontend (create + edit)

New partial `quotation::components.variant-picker-modal`:

- Title shows the product name.
- One row per variant: variant name, SKU, `qty` input (default 0), `price` input
  (default = variant sell price).
- A `custom-note` textarea, auto-filled with the names of variants that have
  qty > 0 (updates as quantities change), editable.
- Confirm is blocked if no variant has qty > 0 (mirrors the Purchase modal).

Search wiring (`create.blade.php`, `edit.blade.php`):

- Keep `BpProductSearch` for search. `onSelect`:
  - variable product → open the modal (replaces the current `showList`
    drill-down).
  - simple product → add a line directly.
- On modal confirm → add **one** combined line via the existing `addLineItem`
  path, extended to accept `{ quantity, unit_price, custom_note, variantSummary }`.

Line-items table:

- Add a **Note** field per row → hidden/visible input `items[<i>][custom_note]`,
  editable on every row.
- Combined lines: Variant column shows `Multiple`; Note prefilled with the
  variant summary.
- Existing recalc (`recalculate()`) is unchanged.

## Show / Print Views

`quotation::show` and `quotation::print`: render `custom_note` under the product
name when present (small muted line), so the variant breakdown is visible on the
quote and the printed copy.

## Edit-Page Behavior

- Existing items repopulate with their stored `custom_note`.
- Historical combined lines display their stored note as-is; we do not
  reverse-engineer them back into per-variant modal rows.

## Edge Cases

| Case | Handling |
|---|---|
| Variable product, no qty entered | Confirm blocked with inline error |
| Mixed variant prices | Weighted-average `unit_price`; subtotal exact |
| Simple product | Direct line add; empty editable note |
| Convert to sale | Note not propagated (no `sale_items` column) — flagged |
| Long note | Capped at 500 chars (validation + column) |

## Testing

1. **Create (combined):** submit a quotation with one product, `variant_id`
   empty, summed `quantity`, a `custom_note`; assert the `quotation_items` row
   has the note, summed qty, and `subtotal = qty × unit_price − discount`.
2. **Validation:** `custom_note` > 500 chars → 422; missing required fields →
   redirect back.
3. **Render:** quotation show + print display the note under the product name.
4. **Edit round-trip:** open the created quotation's edit page; the line shows
   the stored note; re-save preserves it.
5. **Simple product:** add a simple product, type a note, save; note persists.

## Files Touched

- `Modules/Quotation/database/migrations/*_add_custom_note_to_quotation_items.php` (new)
- `Modules/Quotation/app/Models/QuotationItem.php` (`$fillable`)
- `Modules/Quotation/app/Http/Requests/StoreQuotationRequest.php`
- `Modules/Quotation/app/Services/QuotationService.php` (`createItems`)
- `Modules/Quotation/resources/views/components/variant-picker-modal.blade.php` (new)
- `Modules/Quotation/resources/views/create.blade.php`
- `Modules/Quotation/resources/views/edit.blade.php`
- `Modules/Quotation/resources/views/show.blade.php`
- `Modules/Quotation/resources/views/print.blade.php`
