# Combo — Customer-Selected Size — Design Spec

**Date:** 2026-06-30
**Module:** Ecommerce (combos), Variant, storefront checkout/cart
**Status:** Approved design, pending implementation plan

---

## 1. Problem

Combos bundle several products and sell them at one `combo_price`. In the current
data model:

- Each **colour is a separate product** (e.g. "Remi Cotton Premium Short Sleeve
  Shirt - (Sky blue)" and "(Off white)" are two distinct products).
- Each product carries **Size variants** (M / L / XL / XXL) via the Variant module
  (`ProductVariant` + `attributeValues` + the `Size` `VariantAttribute`).
- `combo_items.variant_id` is **NULL** for every combo item today, so the size a
  customer wants is **never captured**. Combos add to the cart with no size.

We need the customer to choose a size when buying a combo, the admin to enable
that per combo, and the chosen size to flow correctly into the cart and order
(stock deduction against the right variant).

## 2. Decisions (agreed)

| # | Decision | Choice |
|---|---|---|
| 1 | Size granularity | **One size for the whole combo** — the chosen size applies to every component. |
| 2 | Admin configuration | **Toggle + auto-derive.** Admin flips one switch; the system offers the sizes that ALL components share (intersection). |
| 3 | Where the size is picked | **Combo detail page** (inline selector). The combo-card quick "add to cart" icon, for size-required combos, links to the detail page instead of instant-adding. |
| 4 | Out-of-stock sizes | **Disabled in the selector.** Combo availability is computed per size; sizes that can't be fulfilled for every component are greyed out. |

## 3. Assumptions (flag before implementation if wrong)

- **A1 — Price is size-independent.** `combo_price` is the price regardless of the
  chosen size. Per-variant price differences are ignored for combos.
- **A2 — Variant match is by size value.** A component product's variant for a
  chosen size is the active variant whose attribute values include that size
  value. If a product has additional variant attributes beyond Size, pick the
  in-stock match; document if a real combo needs more than Size.
- **A3 — Mini-cart shows the chosen size.** This revisits the earlier "omit size
  for combos" decision (made when combos had no single size). A size-required
  combo line now shows `Size: L`. Non-size combos still show name / price / qty.

## 4. Data model

Single additive column — no new tables, no stored size list (sizes are derived).

```
ALTER TABLE combos ADD COLUMN size_required TINYINT(1) NOT NULL DEFAULT 0;
```

- Migration: `Modules/Ecommerce/database/migrations/*_add_size_required_to_combos_table.php`
  with a reversible `down()`.
- `Combo` model: add `size_required` to `$fillable`, cast to `boolean`.

`combo_items.variant_id` stays NULL — the combo defines *which products* are
bundled; the *size* (hence the variant) is resolved at purchase time from the
customer's choice. No schema change to `combo_items`.

## 5. Service layer — `ComboService`

Two new methods, plus reuse of existing stock helpers.

### 5.1 `sizeAttribute(): ?VariantAttribute`
Resolve the Size attribute (the `VariantAttribute` named `Size`, or the
size-like attribute shared by the combo's components). Returns null when none.

### 5.2 `availableSizes(Combo $combo): array`
Returns the ordered list of selectable sizes for the combo:

```php
[
  ['value' => 'M',  'value_id' => 46, 'in_stock' => true],
  ['value' => 'L',  'value_id' => 47, 'in_stock' => true],
  ['value' => 'XL', 'value_id' => 48, 'in_stock' => false], // disabled
  ...
]
```

Algorithm:
1. Get the Size attribute's values (ordered by `sort_order`).
2. Keep only the values for which **every** component product has an active
   variant (the intersection — guarantees the combo can be fulfilled at that size).
3. For each kept value, compute combo availability =
   `min` over components of `intdiv(variantStock(product, sizeVariant), item.quantity)`.
   `in_stock = (availability > 0)` when stock is tracked; otherwise `true`.
   Reuse the existing `componentAvailable()` logic, but resolve the per-size
   variant instead of the (null) `combo_item.variant_id`.

### 5.3 `resolveComponentVariant(ComboItem $item, int $sizeValueId): ?ProductVariant`
Helper used by add-to-cart and availability: returns the component product's
active variant whose attribute values include `$sizeValueId` (per A2).

### 5.4 Existing methods
`isInStock()` / `availableStock()` gain an optional selected-size parameter so the
detail page and `addCombo` can check stock for the chosen size.

## 6. Storefront — combo detail page

`Modules/Ecommerce/app/Http/Controllers/Storefront/ComboController@show`:
- Pass `$sizeOptions = $service->availableSizes($combo)` and `$combo->size_required`.

`storefront/pages/combos/show.blade.php`:
- When `size_required` and `$sizeOptions` is non-empty, render a **Size selector**
  above the quantity box, reusing the product page's `details_variant_size`
  chip style for visual consistency. Out-of-stock sizes get the disabled style.
- Hidden input `combo_size` holds the selected size **value_id**.
- JS: require a size when `size_required` before Buy Now / Add to cart; include
  `combo_size` in the add-combo AJAX payload; show an inline error if unselected.
- If `size_required` is true but `$sizeOptions` is empty (no common size), hide
  the selector and behave as today (no size) — defensive fallback.

## 7. Storefront — combo cards

`storefront/partials/combo-card.blade.php`:
- For `size_required` combos, the quick cart icon renders as a link to
  `route('storefront.combos.show', $combo->slug)` (pick size on the detail page)
  instead of the instant-add `combo-add-cart-trigger`.
- Non-size combos keep the instant-add icon (unchanged).

## 8. Cart — `CartController@addCombo`

- Accept optional `combo_size` (size `value_id`).
- If `combo->size_required`:
  - Validate `combo_size` is present, is one of `availableSizes`, and is in stock
    for the requested quantity. Reject with a clear message otherwise.
  - Resolve each component's `variant_id` via `resolveComponentVariant()`.
- Build the cart line components with the **resolved** `variant_id` and the size as
  `variant_name` (e.g. "L").
- Store the chosen size on the line: `'size' => 'L'` (label) for display.
- **Cart key:** `combo:{id}:{size}` where `{size}` is the size `value_id`, so two
  sizes of the same combo are separate lines (today it is `combo:{id}`). Non-size
  combos keep `combo:{id}`. (`{size}` is used as shorthand for this key throughout.)
- Re-run the stock check against the resolved variants before adding.

## 9. Order placement

No new logic. Order expansion already deducts stock and writes `ecommerce_order_items`
from `components[].variant_id`; with variants now resolved to the chosen size, the
correct variant is decremented and recorded. The size is visible via the variant
name on the order.

## 10. Mini-cart & checkout display

- `storefront/partials/mini-cart-items.blade.php` and the checkout Order Summary:
  for a combo line with a chosen size, show a single `Size: {size}` line
  (per A3). Combos without a size keep name / price / qty only.

## 11. Admin — combo create/edit

- Add a switch **"Let customer choose size"** bound to `size_required` on
  `combos/create.blade.php` and `combos/edit.blade.php`.
- `ComboService::persist()` saves `size_required` from the request; `StoreComboRequest`
  / `UpdateComboRequest` validate it as boolean.
- Nicety (optional, can be deferred): show the auto-derived common sizes as a hint
  under the toggle, and warn when components share no common size.

## 12. Edge cases & guard rails

- **No common size** while `size_required` → selector hidden, detail page falls
  back to current (no-size) add; admin hint flags it.
- **Variant ambiguity** (product with extra variant attributes) → match by size
  value, prefer the in-stock variant (A2).
- **Size goes out of stock between page load and submit** → server re-validates in
  `addCombo` and rejects with a message.
- **Mixed cart** (same combo, two sizes) → distinct `combo:{id}:{size}` keys.

## 13. Scope / files touched

- **Migration:** add `combos.size_required`.
- **Models:** `Combo` (fillable + cast).
- **Service:** `ComboService` (`sizeAttribute`, `availableSizes`,
  `resolveComponentVariant`, size-aware stock).
- **Controllers:** `Storefront/ComboController@show`, `Storefront/CartController@addCombo`,
  admin `ComboController` (persist via service), form requests.
- **Views:** `combos/show.blade.php`, `partials/combo-card.blade.php`,
  `partials/mini-cart-items.blade.php`, checkout Order Summary,
  admin `combos/create.blade.php` + `combos/edit.blade.php`.

Out of scope: order/checkout internals, pricing-by-size, per-component different
sizes, the Buy Now variant modal.

## 14. Testing

- **Unit (`ComboServicePricingTest` companion):** `availableSizes` returns the
  intersection of component sizes; marks out-of-stock sizes; `resolveComponentVariant`
  returns the right variant per size.
- **Feature (`ComboCartTest` companion):**
  - size-required combo rejects add-to-cart without a size;
  - valid size resolves each component to the correct variant and stores
    `combo:{id}:{size}`;
  - out-of-stock size is rejected;
  - two sizes of one combo create two cart lines.
- **Feature (storefront):** detail page shows the size selector with disabled
  out-of-stock sizes only when `size_required`; combo card links to detail page
  for size-required combos.
- **Admin:** toggling "Let customer choose size" persists `size_required`.
- **Regression:** non-size combos behave exactly as today (instant add, `combo:{id}`).
