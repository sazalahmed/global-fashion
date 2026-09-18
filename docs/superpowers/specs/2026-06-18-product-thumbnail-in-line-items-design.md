# Use Product Thumbnail for Single-Image Displays — Design Spec

**Date:** 2026-06-18
**Status:** Approved for planning

## 1. Summary

Wherever a single representative product image is shown in transactional line-item flows, use the
product's dedicated **`thumbnail`** column instead of the **gallery** image (`Product::$image`
accessor, which returns the primary/first `ProductImage`). When a product has no thumbnail, fall
back to the gallery image, then to the existing placeholder box.

The change is centralized in **one model accessor** so there is a single source of truth, and the
4 data-source points that currently emit the gallery image are pointed at it.

## 2. Background (current state, verified)

- `Product` has a fillable **`thumbnail`** column (a stored upload path; no accessor today).
- `Product::getImageAttribute()` returns the **gallery** image path: primary `ProductImage`, else
  first — i.e. `$product->image` is the gallery image, and it lazy-loads the `images` relation.
- The line-item view/JS layers consume a generic `image` field, so only the **backend data
  sources** that build that field need to change; the JS that renders `d.image` is untouched.

### Inventory of single-image points

**Already correct (no change):**
- Sale quick-view modal — `partials/quick-view.blade.php` uses `product->thumbnail`.
- POS grid + AJAX search — uses `thumbnail` (gallery fallback).
- Ecommerce storefront/admin — uses `thumbnail` (gallery fallback).

**Needs change (currently emit gallery `->image`):**

| # | File:line | Flow | Notes |
|---|---|---|---|
| 1 | `Modules/Sale/resources/views/create.blade.php:356` | Sale create catalog | Full Product model loaded → `thumbnail` available. Also `:98` prefill (old input) uses `$product->image`. |
| 2 | `Modules/Sale/resources/views/edit.blade.php:385` | Sale edit catalog | Full model loaded. Existing-item rows (`:1191/:1266`) reuse catalog data — no separate change. |
| 3 | `Modules/Quotation/app/Http/Controllers/QuotationController.php:259` (`mapProductForCatalog`) | Quotation create catalog | **Query column-selects WITHOUT `thumbnail` and does not eager-load `images`** — must add `thumbnail` to `select()` and `->with('images')` for the fallback. |
| 4 | `Modules/Quotation/resources/views/edit.blade.php:138` | Quotation edit existing items | `$item->product?->image`. |

**Not applicable:** Purchase create/edit, Sale/Purchase returns (no per-row product image).

## 3. Design

### 3.1 Model accessor (single source of truth)
Add to `Modules/Product/app/Models/Product.php`:

```php
/**
 * The single representative image path for a product, for line-item / row displays:
 * the dedicated thumbnail when set, else the gallery (primary/first) image, else null.
 * Returns a RAW stored path (callers wrap with upload_url()), matching getImageAttribute().
 */
public function getDisplayImageAttribute(): ?string
{
    return $this->thumbnail ?: $this->image;
}
```

- Returns a raw path (not a URL) so existing `upload_url(...)` call sites are unchanged in shape.
- When `thumbnail` is set, the `images` relation is never touched (avoids a gallery query).
- When absent, it defers to the existing `image` accessor (gallery), preserving today's behavior.

### 3.2 Point the 4 data sources at the accessor
Replace the single-image references with `display_image`:

1. `create.blade.php:356` → `'image' => $p->display_image ? upload_url($p->display_image) : null,`
   and `:98` prefill → `$product->display_image`.
2. `edit.blade.php:385` → `'image' => $p->display_image ? upload_url($p->display_image) : null,`
   (and the matching prefill reference if present).
3. `QuotationController.php`:
   - `mapProductForCatalog()`:259 → `'image' => $product->display_image ? upload_url($product->display_image) : null,`
   - In the catalog query (the `select(...)` builder around :241): add `'thumbnail'` to the
     `->select(...)` column list, and add `->with('images')` so the gallery fallback resolves
     without an N+1 (mirrors how Sale loads the full model).
4. `edit.blade.php:138` (Quotation) → `@php $itemImg = $item->product?->display_image; @endphp`
   (verify the `$item->product` load includes the `thumbnail` column / `images` relation; eager-load
   `quotation.items.product.images` in the edit controller if not already).

### 3.3 Optional consistency (low risk, recommended)
Repoint the already-migrated spots that reference `thumbnail` directly (quick-view) at
`display_image` too, so all single-image displays share one definition and gain the gallery
fallback. POS/Ecommerce already do thumbnail-with-fallback; leaving them is fine, but using the
accessor there removes duplicated fallback logic. (Scope this only if it stays a clean swap.)

## 4. Non-Goals
- No change to the gallery/`image` accessor or the gallery feature itself.
- No change to Purchase / returns (they show no per-row product image).
- No new image upload/processing; uses the existing `thumbnail` column and `upload_url()`.

## 5. Files

### Modified
- `Modules/Product/app/Models/Product.php` — add `getDisplayImageAttribute()`.
- `Modules/Sale/resources/views/create.blade.php` — catalog + prefill → `display_image`.
- `Modules/Sale/resources/views/edit.blade.php` — catalog (+ prefill) → `display_image`.
- `Modules/Quotation/app/Http/Controllers/QuotationController.php` — `mapProductForCatalog` →
  `display_image`; add `thumbnail` to `select()` + `->with('images')`.
- `Modules/Quotation/resources/views/edit.blade.php` — existing-item image → `display_image`
  (+ eager-load product `images`/`thumbnail` in the edit controller if needed).
- (Optional) `Modules/Sale/resources/views/partials/quick-view.blade.php` — → `display_image`.

## 6. Testing / acceptance

1. Product **with** a thumbnail: sale create/edit order rows and quotation create/edit rows show
   the **thumbnail** image (not the gallery image), including the lightbox `href`.
2. Product **without** thumbnail but **with** gallery images: rows show the **gallery** image
   (fallback works — no blank image).
3. Product with **neither**: rows show the placeholder box (`fa-box`).
4. Quotation catalog: confirm `thumbnail` is present in the JSON payload (was missing from
   `select()` before) and that no N+1 fires (images eager-loaded) — check the query log / debugbar.
5. Validation-error prefill on sale create still shows the correct (thumbnail) image.
6. No regressions in quick-view / POS / Ecommerce single-image displays.
7. Route sweep on sale + quotation create/edit returns 200; order rows render.

## 7. Open considerations (decided defaults)

- **Fallback policy:** thumbnail → gallery → placeholder (decided; avoids blank images for
  products that only have gallery photos, and matches POS/Ecommerce).
- **N+1:** Sale loads the full model already; Quotation gains `->with('images')` so the fallback
  path doesn't lazy-load per row.
