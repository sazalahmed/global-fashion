# Reseller Price Tier — Design

**Date:** 2026-07-18
**Status:** Approved (pending spec review)

## Summary

Add a third product pricing tier, **Reseller Price** (`resell_price`), positioned after
the existing Wholesale Price. Expose it as a selectable **Price Type** on the Sale and
Quotation create/edit pages so the applied unit price changes with the selected type.

This cleanly mirrors the existing `wholesale_price` pattern already present across the
Product model, product forms, and the Sale/Quotation Price Type selector.

## Decisions

- **UI label:** "Reseller Price" (DB column: `resell_price`, dropdown value: `resell`).
- **Fallback chain** when a product/variant has no resell price set:
  **resell → wholesale → sell** (regular sell price). Applied consistently in the variant
  accessor, the Sale product payload, and both JS `priceFor()` helpers.
- **Out of scope:** POS (has no Price Type selector); per-variant resell inputs in the
  product form (the form does not collect per-variant wholesale today — variants fall back
  to the product-level price via an accessor).

## Changes

### 1. Database & Models

- **Migration** (new, follows Laravel timestamp convention): add
  `decimal('resell_price', 15, 2)->nullable()` to `products` after `wholesale_price`, and
  the same column to `product_variants` after its `wholesale_price`. Provide `down()` that
  drops both columns.
- **`Modules/Product/app/Models/Product.php`:** add `resell_price` to `$fillable`, add
  `'resell_price' => 'decimal:2'` cast, add `getFormattedResellPriceAttribute()` mirroring
  `getFormattedWholesalePriceAttribute()`.
- **`Modules/Variant/app/Models/ProductVariant.php`:** add `resell_price` to fillable and
  cast; add `getEffectiveResellPriceAttribute()`:
  `resell_price ?? product->resell_price ?? effective_wholesale_price ?? effective/sell`
  — i.e. resell → wholesale → sell.

### 2. Product Create / Edit Forms

- **`Modules/Product/resources/views/create.blade.php`** and `edit.blade.php`: add a
  "Reseller Price ({{ currency_symbol() }})" number input immediately **after** the
  Wholesale Price input in the same pricing row. Rebalance the row's column widths so
  Sell + Wholesale + Reseller fit cleanly (no inline CSS — use existing grid classes).
  Bind `value="{{ old('resell_price', $product->resell_price ?? '') }}"` as appropriate.
- **`StoreProductRequest` / `UpdateProductRequest`:** add
  `'resell_price' => ['nullable', 'numeric', 'min:0']` mirroring the wholesale rule.

### 3. Sale Create / Edit (`price_type` is a persisted column)

- **`Modules/Sale/resources/views/create.blade.php`** and `edit.blade.php`:
  - Add `<option value="resell">Reseller Price</option>` to the Price Type `<select>`
    (preserve `old('price_type')`/saved value on edit).
  - In the products payload: add `'resell' => (float) ($p->resell_price ?? $p->wholesale_price ?? $p->sell_price)`
    and the variant equivalent using `effective_resell_price`.
  - Update `priceFor(product, variant)`: when `currentPriceType() === 'resell'`, return
    `src.resell != null ? src.resell : (src.wholesale != null ? src.wholesale : (src.price || 0))`.
  - The existing `price_type` change handler already re-applies prices to simple rows; it
    will pick up the new type with no structural change.
- **`StoreSaleRequest` / `UpdateSaleRequest`:** change the `price_type` rule to
  `['nullable', 'in:regular,wholesale,resell']`.
- `SaleService` already stores `$data['price_type'] ?? 'regular'` — no change needed.

### 4. Quotation Create / Edit (price_type is frontend-only, not persisted)

- **`Modules/Quotation/resources/views/create.blade.php`** and `edit.blade.php`:
  - Add the `resell` dropdown option.
  - Add `resell_price` to the product payload (quotation uses raw `sell_price` /
    `wholesale_price` field names).
  - Update `priceFor(src)`: when type is `resell`, return
    `src.resell_price != null ? src.resell_price : (src.wholesale_price != null ? src.wholesale_price : (src.sell_price || 0))`.

## Testing

- Migration runs and rolls back cleanly.
- Product create/edit saves and displays `resell_price`.
- Sale create with Price Type = Reseller applies resell price to line items; falls back to
  wholesale, then sell, when resell is absent. Saved `price_type` = `resell` persists and
  reloads on edit.
- Quotation create/edit applies resell pricing in the line items.
- Validation rejects an invalid `price_type` and negative `resell_price`.
- Route sweep: product, sale, quotation create/edit pages return 200.
