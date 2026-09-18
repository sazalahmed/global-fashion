# Combo "Choose Variant" — Generalize from Size-only

**Date:** 2026-07-01
**Module:** Ecommerce (Combos)
**Status:** Approved — ready for implementation plan

## Problem

The combo package feature lets a customer pick one shared **Size** for the whole
combo (`size_required` toggle). The matching logic (`ComboService::isSizeLikeAttribute`)
only recognizes variant attributes literally named `Size` / `Size (...)`. Combos whose
component products vary by another attribute — Color, Weight, etc. — show **no options**
even when every component shares that attribute, so the toggle is effectively useless
for them.

## Goal

Make the customer-choice feature work for **whichever single variant attribute the
components actually share**, not only Size. Auto-detect that attribute; no new admin
input beyond the existing toggle.

## Decisions (from brainstorming)

- **Generalize to any variant** (single attribute), not multiple simultaneous attributes.
- **Auto-detect the single shared attribute.** When more than one attribute is shared by
  every component, pick by priority: **Size-like first, then lowest `sort_order`/name**.
- **Keep the `size_required` DB column** (no migration); its meaning is generalized and
  documented in code.
- **Keep the internal request/stored field `combo_size`** (opaque chosen value-label) to
  avoid rippling through cart/checkout/order/sale expansion.
- **Rename the size-specific service methods** to attribute-agnostic names; update the
  combo tests that call them.

## Design

### 1. Attribute detection — `ComboService`

Replace size-name matching with attribute-agnostic detection.

- **`chooseableAttribute(Combo): ?VariantAttribute`**
  For each variant attribute used by the components, compute the intersection of active
  variant **value labels** across *all* component products. Candidate attributes are those
  with a non-empty intersection. Choose one by priority:
  1. Size-like attribute (`name === 'Size'` or starts with `Size (` / `Size(`)
  2. else lowest `sort_order`, then name
  Returns `null` when no attribute is shared by every component (feature inactive).
- Value→variant resolution keeps existing **"first active variant for a label wins"**
  semantics (only lossy when a product varies by 2+ attributes; unchanged from today).

### 2. Method generalization — `ComboService`

| Old (size-only) | New (attribute-agnostic) |
|---|---|
| `isSizeLikeAttribute($attr)` | kept as private helper, used only for the tiebreaker priority |
| `productSizeVariants($product)` | `productVariantMap($product, $attribute)` → `[valueLabel => variant]` |
| `resolveComponentVariant($item, $sizeLabel)` | `resolveComponentVariant($item, $attribute, $valueLabel)` |
| `availableStockForSize($combo, $label)` | `availableStockForOption($combo, $label)` |
| `isInStockForSize($combo, $label, $qty)` | `isInStockForOption($combo, $label, $qty)` |
| `availableSizes($combo)` | `availableOptions($combo)` — shared value-labels of the chosen attribute, ordered by that attribute's value `sort_order`, each flagged `in_stock` |

`availableOptions` returns the same shape as today: `array<int, array{value: string, in_stock: bool}>`.

### 3. DB column

`combos.size_required` unchanged. Add a comment on the model/cast documenting it now
means "let the customer choose the shared variant." `persist()` continues to read/write it.

### 4. Admin form — `combos/create.blade.php`, `combos/edit.blade.php`

- Toggle label: **"Let customer choose size"** → **"Let customer choose variant"**.
- Helper text → *"Offers the variant (e.g. size/color) shared by all selected products;
  customer picks one on the combo page."*
- Create keeps the toggle **checked by default** (`old('size_required', '1')`, already applied).
- Edit reflects the saved value (unchanged binding).

### 5. Storefront combo show — `Storefront\ComboController` + `storefront/pages/combos/show.blade.php`

- Controller: `$sizeOptions = $combo->size_required ? availableOptions($combo) : []`,
  and resolve `$optionAttribute = $combo->size_required ? chooseableAttribute($combo) : null`.
  Pass `options` and `optionAttribute` (keep `sizeCharts` as-is).
- View: the variant title `"Size :"` becomes the chosen attribute's `base_name`
  (fallback `"Option"`). The option list, hidden input (`combo_size`), error message, and
  JS (`comboSizeRequired`, `comboChosenSize`) keep working — only the visible label is dynamic.
- **Size chart** stays size-specific (renders only when size-like attributes exist).
  A Color-based combo simply shows no chart — acceptable.

### 6. Cart / checkout — `Storefront\CartController`, `StorefrontService`

- Request field `combo_size` and the stored `size` remain, treated as the chosen value
  label of the (now general) attribute.
- Component resolution switches to `chooseableAttribute` + the 3-arg `resolveComponentVariant`.
- Order/sale expansion continues to store the chosen label in `variant_label` / notes.

### 7. Tests

- Update existing combo tests to the new method names.
- Add a case: a combo whose components share a **non-Size** attribute (e.g. Color) yields
  selectable `availableOptions`, resolves each component to the correct variant, and
  computes per-option stock.
- Keep a Size-based case to prove no regression, including the Size-first tiebreaker when
  both Size and Color are shared.

## Out of scope

- Choosing values for multiple attributes at once (explicitly declined).
- Admin manually selecting which attribute (auto-detect chosen instead).
- Renaming the `size_required` column or the `combo_size` field.
- Size-chart support for non-size attributes.
