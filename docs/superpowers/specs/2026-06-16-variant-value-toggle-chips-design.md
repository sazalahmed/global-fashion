# Variant Value Toggle Chips — Product Edit Page

**Date:** 2026-06-16
**Surface:** `Modules/Product/resources/views/edit.blade.php` (JS) + `public/css/style.css`

## Problem

On the product edit page, each "Variant Attributes" card only shows the values
already used by the product's variants (e.g. Color: Red, Blue, …). To add a new
value the admin has to reopen the "Add Attribute" picker. There is no at-a-glance
view of which values for an already-added attribute are *in use* vs *available*.

## Goal

Within each already-added attribute card, render **all** of that attribute's
values as toggle chips, visually marking which are taken (in variants) vs
available (not yet added), and let the admin add/remove values by clicking — with
changes applied immediately.

## Scope

- Frontend only. Backend already supports both operations:
  - Add: `ProductController::generateVariants` → `VariantService::generateVariants`
    (reconciles — creates missing combos, prunes stale ones).
  - Remove: `ProductController::removeVariantValues` → `VariantService::removeProductValues`
    (removes a value's combos; 422-blocks if any have sales/purchase history).
- Adding a brand-new attribute *dimension* stays in the existing "Add Attribute"
  picker. This change only expands value selection within attributes already on
  the product.

## Behavior

### Rendering (`renderSelectedAttrs`)
For each added attribute, render every `attr.values` entry as a chip:
- **Taken** (id ∈ `selectedAttributes[attrId]`): filled primary chip + `✓`.
- **Available** (value exists but not selected): muted dashed-outline chip + `+`.
- Color attributes show their color dot in both states (jQuery `.css()` for the dot).
- The attribute-level `×` (remove whole dimension) button stays.

### Interaction (apply immediately)
- **Available → click:** add id to `selectedAttributes[attrId]`, call
  `generateAndRenderVariants()` (sends full selection; server reconciles → new
  combos appear).
- **Taken → click:** confirm (`Remove "<value>" and its N variant(s)?`), then
  `removeVariantValues([id], cb)`. On 422, show the server message and leave the
  chip taken. On success, drop id from `selectedAttributes[attrId]`, then
  re-render chips + matrix.
- **Guard:** clicking the *last* remaining taken value of an attribute is a no-op
  with a hint to use the `×` button to remove the whole attribute (a dimension
  cannot have zero values).

### CSS
New classes in `style.css`: `bp-variant-toggle`, `bp-variant-toggle-on`,
`bp-variant-toggle-off`, with `[data-theme="dark"]` overrides. No inline styles
except the dynamic color dot via jQuery `.css()` (existing allowed exception).

## Testing
- Manual: add an available value → its combos appear; remove a taken value →
  its combos disappear; removing a sold value is blocked with the server message;
  last-value guard prevents emptying a dimension.
- Existing automated coverage for the backend reconcile/remove paths
  (`VariantRegenerationTest`, `VariantRemovalTest`) remains green.

## Out of scope
- Staged/batched apply (rejected — immediate matches existing Add flow).
- Adding new attribute dimensions by click (stays in the picker).
