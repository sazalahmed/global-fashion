# Product Variant Management on Edit — Design

**Date:** 2026-06-15
**Status:** Approved (pending spec review)
**Area:** `Modules/Product`, `Modules/Variant`

## Problem

On the product edit page (`/admin/products/{id}/edit`) for variable products:

1. **Variant changes never persist.** `ProductController::update()` processes images,
   categories, and simple-product stock, then **drops `$data['variants']`**. Only
   `store()` (create) handles variants. So removing an attribute/value, editing a
   SKU/price, or toggling **Active** has no effect on save.
2. **Stale variants accumulate.** `VariantService::generateVariants()` is additive-only —
   it adds new cartesian combos but never removes deselected ones. A product that started
   with Color (6 variants) then added Size (6×4=24) ends up with 6 orphaned color-only rows
   = **30 variants but "24 combinations"**.
3. **No way to remove a value** (e.g. drop "Gold") or **add an existing value** (e.g.
   "Sky Blue") and have it reflected in the variant matrix on save.
4. **No global enable/disable** of an attribute value.

### How variants are created (reference)
Variants are the **cartesian product** of the selected attribute values across attributes
(Color × Size). `generateVariants()` builds combos, skips existing ones (idempotent), and
creates a `product_variants` row per combo with attached `variant_attribute_values`.
Attributes (Color, Size) and values (Red, Gold…) are **global**, shared across all products;
a product "uses" values by attaching them to its variants.

## Decisions (from brainstorming)

| Topic | Decision |
|---|---|
| Removing a value/attribute | **Hard-delete** the now-invalid variants that have **no sales** (and their `warehouse_stock`); **block the entire save** with a clear error if any to-be-removed variant has `sale_items`. |
| Adding a value | **Pick from existing global values only** (active ones). New global values are created in the Variants admin first. |
| Enable/disable | Per-variant **Active** (persisted) + per-product include/exclude of values (via selection) + **global on/off flag on each value** (`variant_attribute_values.status`) that hides it everywhere. |
| Save logic | **Explicit-only.** Variants change only from explicit UI actions (add/remove value or attribute, per-row delete, edit fields). **No silent auto-reconcile.** |
| Existing stale rows | **Left as-is.** Admin deletes them manually via per-row delete (blocked if sold). No automatic cleanup. |

## Design

### 1. Data model
- **New migration:** add boolean `is_active` to `variant_attribute_values` (default `true`,
  indexed). Drives the **global value on/off**. (Boolean to match `product_variants.is_active`;
  the sibling `variant_attributes.status` is left as-is.)
  - Disabled values are hidden from the product edit attribute picker and from new combo
    generation. **Existing variants are not touched** (explicit-only).
- `product_variants` already has `is_active` (bool), `is_default` (bool), and `SoftDeletes`.
  No schema change. (Unused removed variants are **force-deleted**; the soft-delete trait
  remains for other flows.)

### Architecture note — variants are AJAX-managed, not form-managed
Variants do **not** go through the product form / `ProductController::update()`. They are managed
by dedicated AJAX endpoints already wired into the edit page:
`generate` (additive create), `bulk-update` (SKU/price/active/default/stock — works),
`{v}/active` (instant toggle — works), `{v}` DELETE (`destroyVariant` — soft-delete, no sales guard).
The bug is that **`generateVariants` is additive-only** — deselecting a value never removes its
variants. The fix adds a **surgical removal** path and a **sales guard on deletes**; we do NOT
touch `update()`.

### 2. `VariantService` — removal + sales guard (transactional)

**Sales guard helper** — `variantHasSales(int $variantId): bool`
→ `DB::table('sale_items')->where('variant_id', $id)->exists()`.

**Surgical value removal** — `removeProductValues(Product $product, array $valueIds): array`
1. Find this product's variants whose attached values intersect `$valueIds` (via
   `product_variant_values`).
2. If **any** matched variant has sales → throw `VariantHasSalesException` listing the SKUs
   (abort; nothing deleted).
3. Otherwise **force-delete** the matched variants + their `warehouse_stock` rows (and pivot rows).
4. If the deleted set included the default variant, reassign default to the first remaining
   active variant.
5. Return `['removed' => int]`.

Surgical by-value (not full cartesian diff) means unrelated rows — including the 6 stale
color-only variants — are **left untouched** unless they actually contain a removed value.

**Hardened single delete** — `deleteVariant(ProductVariant $variant)`
→ if `variantHasSales($variant->id)` throw `VariantHasSalesException`; else force-delete +
remove its `warehouse_stock`; reassign default if needed. (Currently soft-deletes with no guard.)

**`generateVariants`** — unchanged (additive create is correct for *adding* values).

### 3. `ProductController` — new/updated endpoints

- **New** `removeVariantValues(Product $product, Request $request): JsonResponse`
  - Route: `DELETE products/{product}/variants/by-values`, name `products.remove-variant-values`.
  - Validates `value_ids: required|array`, `value_ids.*: integer|exists:variant_attribute_values,id`.
  - Calls `removeProductValues`; on `VariantHasSalesException` → `response()->json(['message' => ...], 422)`.
- **Update** `destroyVariant` — let `VariantHasSalesException` map to a 422 JSON
  (`['message' => $e->getMessage()]`) instead of soft-deleting unconditionally.
- **Update** `getVariantAttributes` — load only `is_active = true` values
  (`->with(['values' => fn($q) => $q->where('is_active', true)])`).

### 4. Edit UI — `Modules/Product/resources/views/edit.blade.php`
- **Add value** (active global values only) → existing `generateAndRenderVariants()` (additive). Keep.
- **Remove a value** (value chip ×, or unchecking it in the picker) → call
  `products.remove-variant-values` with that `value_id`. On success, re-render the matrix from the
  returned/refetched variants. On **422** (sold) → show the message and **revert** the UI
  (re-select the value), so nothing is lost.
- **Remove an attribute (group ×)** → call the same endpoint with all of that attribute's selected
  `value_ids`.
- **Per-row trash** (`.var-del-btn`) → existing `destroyVariant` call; on **422** show the
  block-if-sold message instead of removing the row.
- **Active** toggle + **Default** + SKU/price/stock already persist via `bulk-update` / `{v}/active`.
- The attribute picker lists only active values (backend already filters in `getVariantAttributes`).
- Blocked-by-sales error surfaces inline, e.g.:
  *"Can't remove GF2025001-GOLD — it has sales. Disable it (uncheck Active) instead."*

### 5. Variants admin — `Modules/Variant`
- Add an enable/disable (`is_active`) toggle on attribute values in the values-management UI
  (`VariantService` value methods + view). Default active.
- Product edit picker (`getVariantAttributes`) lists active values only.

### 6. Retire-vs-delete
Sold variants cannot be deleted by design. The per-variant **Active** toggle (already persisted)
is the supported way to retire a sold variant without losing its sales history.

## Edge cases
- Removing the variant that is the **default** → reassign default to the first remaining active variant.
- Removing an entire attribute (group ×) → removal call with all its values' ids.
- A value removal that matches **no** variants → no-op success.
- Global-disabling a value already used by live variants → value disappears from pickers but
  existing variants remain (explicit-only); admin removes/retires them manually.
- `removeProductValues` is all-or-nothing: if any matched variant is sold, **none** are deleted.

## Testing
- `removeProductValues` on an **unused** value deletes its variants + `warehouse_stock`; unrelated
  variants (incl. stale color-only rows) remain.
- `removeProductValues` when a matched variant **has sales** throws `VariantHasSalesException` and
  deletes nothing (all-or-nothing).
- `deleteVariant` on a sold variant throws; on an unused variant force-deletes + clears stock.
- Removing the default variant reassigns default to the first remaining active variant.
- Adding an existing active value still generates the missing combos (additive `generateVariants`).
- `getVariantAttributes` returns only `is_active = true` values.
- Global-disabling a value hides it from the picker without deleting existing variants.
- Endpoints reject cross-product ids (variant/value not belonging to the product) and return 422 with a message on sales-block.

## Out of scope
- Inline creation of brand-new global values from the product page (admin creates them in Variants).
- Automatic cleanup/reconcile of existing stale variants (manual per-row delete only).
- Bulk variant editing tools beyond the existing matrix.
