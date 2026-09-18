# Design: Combo Package System (Ecommerce)

**Date:** 2026-06-24
**Module:** Ecommerce (+ touches Product/Variant read-only, homepage sections, cart/checkout)
**Status:** Approved design — pending implementation plan

## Problem / Goal

Let admins build **combo packages** — a curated bundle of existing products
(at fixed variants) sold together at a bundle price. A combo has its own
thumbnail, a gallery composed from the component products' images, a combo
price plus optional discount, and its own storefront detail page. Combos
appear on a dedicated storefront listing and can be selected into homepage
sections. Buying a combo decrements **each component product/variant's**
stock (at fulfillment), reusing the existing order → sales → stock machinery.

## Locked Decisions

- **Variants:** the admin fixes the exact variant per component product; the
  customer buys the combo as-is.
- **Pricing:** admin sets a `combo_price` (the bundle total) plus an optional
  discount (fixed or percentage) off that price. The summed component price is
  shown as a strikethrough/savings reference.
- **Stock:** derived from components — no standalone combo stock. Availability
  = `min` over components of `floor(componentAvailable / qtyInCombo)`. Stock
  deducts per component product/variant at fulfillment.
- **Detail page:** combos get their own slugged storefront page.
- **Cart/order:** a combo is one cart line; at order creation it **expands**
  into one `EcommerceOrderItem` per component so existing stock/sales logic
  applies unchanged.
- **Shop placement:** a dedicated combos listing (separate from the
  product grid and its product-specific filters).
- **Gallery:** references/snapshots of existing **product images** (no new
  uploads); only the combo `thumbnail` is an uploaded file.
- **Strikethrough:** uses the sum of component `sell_price` (not their
  individually-discounted prices).

## Current-State Facts (integration points)

- Products have variants (`Modules\Variant\Models\ProductVariant`), variant
  attribute values, and images (`product_images`, `image_path`, `is_primary`).
  Stock rules: `track_stock`, `allow_negative_stock`, `is_in_stock`, with
  quantities in `warehouse_stock`.
- Cart is session-based (`session('cart')`), items keyed by a cart key with
  `product_id, variant_id, variant_name, variant_attributes, name, price,
  sell_price, image, quantity, sku`. `CartController@add` builds these;
  `StorefrontService::calculateCartSubtotal` sums them.
- `StorefrontService::createOrder()` writes `EcommerceOrder` +
  `EcommerceOrderItem` rows and **mirrors a `sales` record** (status pending,
  so stock deducts only at fulfillment, not checkout).
- Homepage: `HomepageSection` + `HomepageSectionSchema` (schema-driven section
  types); product-backed sections use a `homepage_section_products` pivot
  managed by `ContentController::homepageSectionProducts/Update`.
- Admin lives under the `/ecommerce` route group (`ecommerce.*`), surfaced in
  the sidebar "Online" group and `config/navigation.php`.
- Existing `ProductCollection` is a separate concept (named product groups) —
  combos are NOT collections (different pricing/stock/checkout semantics).

## Data Model (new)

- **`combos`**: `id, name, slug (unique), thumbnail (string, nullable),
  description (text, nullable), combo_price decimal(15,2),
  discount_type enum('none','fixed','percentage') default 'none',
  discount_value decimal(15,2) default 0, is_active boolean default true,
  sort_order int default 0, timestamps, softDeletes`.
- **`combo_items`**: `id, combo_id (fk cascade), product_id (fk),
  variant_id (nullable fk to product_variants), quantity int default 1,
  sort_order int default 0, timestamps`.
- **`combo_images`**: `id, combo_id (fk cascade), image_path string,
  product_image_id (nullable, traceability), sort_order int, timestamps`.
- **`homepage_section_combos`**: `id, homepage_section_id (fk cascade),
  combo_id (fk cascade), sort_order int`.
- **`ecommerce_order_items`** (alter): add nullable `combo_id` (unconstrained
  int, survives combo deletion) and `combo_name` (string) to label/group the
  expanded component lines.

Money columns are `decimal(15,2)`. All FKs indexed.

## Components & Responsibilities

### Models (`Modules/Ecommerce/app/Models/`)
- `Combo` — `items()`, `galleryImages()`, `homepageSections()`; accessors
  `summedPrice`, `effectivePrice`, `discountAmount`, `availableStock`,
  `isInStock`; `scopeActive`; slug auto-generation (mirror `ProductCollection`).
- `ComboItem` — `belongsTo` Combo, Product, ProductVariant.
- `ComboImage` — `belongsTo` Combo.

### `ComboService` (`Modules/Ecommerce/app/Services/`)
- `summedPrice(Combo)` = Σ(`item.product`/variant `sell_price` × `item.quantity`).
- `discountAmount(Combo)` from `discount_type`/`discount_value` against
  `combo_price` (percentage clamped 0–100; fixed clamped ≤ combo_price).
- `effectivePrice(Combo)` = `combo_price − discountAmount`.
- `availableStock(Combo)` = `min` over items of
  `floor(componentAvailable(product, variant) / item.quantity)`; a component
  that tracks stock, disallows negatives, and is depleted forces 0.
  `componentAvailable` reuses the same warehouse-stock logic the checkout
  stock-check uses.
- `allocatePrices(Combo, comboQty)` → per-component `unit_price` such that
  `Σ(unit_price × lineQty)` equals `effectivePrice × comboQty`; allocate
  proportionally to each component's `sell_price`, last component absorbs the
  rounding remainder.
- `createOrUpdate(...)` for admin persistence (combo + items + gallery in a
  transaction).

### Admin (`/ecommerce` group)
- `ComboController` (thin) → `index/create/store/edit/update/destroy/toggleStatus`.
- `StoreComboRequest` / `UpdateComboRequest` — validate name, thumbnail
  (`image|mimes:jpg,jpeg,png,webp|max:2048`), `combo_price` (numeric ≥ 0),
  `discount_type` (in list), `discount_value`, `items` (≥1; each
  `product_id` exists, `variant_id` nullable/belongs to product, `quantity` ≥1),
  `gallery` (image paths belonging to the chosen products), `is_active`.
- Views: `combos/index`, `combos/create`, `combos/edit` with a product+variant+qty
  row picker, a gallery picker (images of the chosen products), and a live
  summed-price/savings preview (jQuery, `'use strict'`).
- Sidebar entry under "Online"; `config/navigation.php` entry.

### Storefront
- Routes: `storefront.combos.index` (`/combos`), `storefront.combos.show`
  (`/combos/{slug}`).
- `ComboController` (storefront) → listing (active combos, paginated) + detail
  (gallery, price/discount, "what's included" with component links, stock,
  qty + add-to-cart). Reusable `combo-card` partial used by listing + homepage.
- Out-of-stock combos are shown but not purchasable (mirror product behavior).

### Cart → Order → Stock
- **Add to cart:** `CartController` accepts a combo (e.g. `type=combo` +
  `combo_id`). Cart line: `type=combo, combo_id, name, slug, thumbnail,
  price=effectivePrice, quantity, components=[{product_id, variant_id,
  quantity, name, variant_name}]`. Cart key derived from `combo_id`.
- **Subtotal:** `calculateCartSubtotal` counts combo lines by their line price.
- **Cart/checkout UI:** combo line shows its component list.
- **Stock check (checkout):** for each combo line, every component must have
  `available ≥ comboQty × componentQty` (same rules as the product check),
  else block with a clear message.
- **`createOrder`:** combo lines expand into one `EcommerceOrderItem` per
  component (`product_id`, `variant_id`, `quantity = comboQty × componentQty`,
  `unit_price` from `allocatePrices`, `combo_id`/`combo_name` set). The mirrored
  `sales` items follow suit, so component stock deducts per product/variant at
  fulfillment. Non-combo lines are unchanged.
- **Price integrity:** server recomputes `effectivePrice` from the DB at
  add-to-cart and re-validates at checkout — never trusts a client price.

### Homepage
- Add `combos` section type to `HomepageSectionSchema` (heading + highlight,
  view-all → `storefront.combos.index`, items count).
- `homepage_section_combos` pivot + a combo picker in `ContentController`
  (mirroring `homepageSectionProducts`/`Update`) so admins select which combos
  appear. Storefront homepage partial renders combo cards.

## Edge Cases
- Component product/variant deleted after combo creation → combo treated as
  unavailable; admin edit surfaces the broken item. `combo_items` uses the
  product FK; deletion handling validated in `ComboService`/display.
- Discount ≥ combo price → effective price floored at 0.
- Combo with a component that doesn't track stock → that component never
  limits availability.
- Empty combo (no items) → cannot be saved (validation) and never renders.
- Rounding: allocation guarantees component unit prices sum to the combo total.

## Out of Scope (YAGNI)
- Customer-chosen variants, combo-specific coupon codes, nested combos,
  per-combo standalone stock, combos inside the product grid/filters.
- Existing cart coupons still apply to the overall subtotal (incl. combos).

## Testing
- **Unit (`ComboService`):** summed price; discount (fixed/percentage/clamp);
  effective price; price allocation sums exactly to the combo total (incl.
  rounding); stock derivation = min-over-components respecting
  `track_stock`/`allow_negative_stock`.
- **Feature:** admin CRUD (create combo with items+gallery, edit, toggle,
  delete); storefront listing + detail render; add combo to cart (server
  prices it); checkout expands to per-component order items with correct
  qty/price and `combo_id`; stock check blocks when a component is short;
  homepage combo section renders only the selected active combos.
- **Manual/QA:** thumbnail upload, gallery picker limited to chosen products'
  images, savings preview, dark-mode/responsive admin form, storefront cards.
