# Unified Catalog — Products + Combos with Per-Category Ordering

**Date:** 2026-07-01
**Modules:** Product, Ecommerce, Category, Core (sidebar)
**Status:** Approved — ready for implementation plan

## Problem / Goal

Combos ("Combo Packages") are managed on a separate admin page (`/admin/ecommerce/combos`)
and appear on the storefront only as an afterthought (appended after products). We want a
single unified catalog:

1. Manage combos from inside the admin **Products** list (`/admin/products`), not a separate page.
2. Turn the "+ Add Product" button into a split dropdown: **Create Product** / **Create Combo**,
   each opening the existing full create page.
3. Show combos in the products list alongside products.
4. When filtered by a category, products and combos are shown **interleaved** and can be
   **drag-reordered together** (product, combo, product, combo, …).
5. Ordering is **per-category**: the same combo/product can sit at position 4 in Category A and
   position 8 in Category B. An item can appear in multiple categories.
6. The storefront renders each category in exactly the admin-defined interleaved order.

## Decisions (from brainstorming)

- **Unfiltered ordering = global reorderable bucket.** The "All Categories" admin list and the
  main `/shop` / home use a global ordering context (a `NULL`-category row in the ordering
  table). It is fully drag-reorderable and interleaves products + combos.
- **Category scope = category + subcategories.** Filtering by a category shows items from it and
  its descendants; positions are stored against the **viewing category** id.
- **Storefront combos kept; admin moved.** The customer-facing `/combos` list and combo detail
  pages are unchanged. Only admin combo management relocates; old `/admin/ecommerce/combos`
  GET URLs 301-redirect. The "Combo Packages" sidebar link is removed.
- **Reuse existing combo create/edit views and `Ecommerce\ComboController`** — relocate URLs and
  entry points only; do not move controller logic between modules.

## Established codebase facts (from exploration)

- Products: `products.category_id` (belongsTo) **and** `category_product` pivot (`is_primary`).
  Global reorder via `products.position` (`ProductService::reorder`, `list()` orders by
  `CASE WHEN position>0 …`, then `position`, then `created_at desc`).
- Combos: `category_combo` pivot (composite PK, no extra columns). Global `combos.sort_order`
  (`ComboService::reorder`). Admin routes in `Modules/Ecommerce/routes/web.php`
  (`ecommerce.combos.*`), controller `Modules\Ecommerce\Http\Controllers\ComboController`.
- Category: `parent_id` tree; `recursiveChildren()`; `CategoryService::collectSubtreeIds()`.
- Storefront shop: `ShopController::index` → `StorefrontService::getShopProducts` (filters by
  `category_id` + descendants via `getCategoryAndChildrenIds`); combos appended separately via
  `$categoryCombos` (only when a category is selected), ordered by `sort_order`.
- Precedent: pivot-with-`sort_order` (`collection_product`, `homepage_section_products`,
  `homepage_section_combos`, `flash_deal_product`); polymorphic `morphTo`
  (`ActivityLog`, `PaymentAllocation`).
- Sidebar: `Modules/Core/resources/views/partials/sidebar.blade.php` — Products submenu
  `routeIs('products.*', 'categories.*', …, 'ecommerce.combos.*')`; combo link at the
  `ecommerce.view`-gated `<li>`.

## Design

### 1. Data model — polymorphic per-category ordering table

New migration `catalog_positions`:

```
catalog_positions
  id
  category_id        unsignedBigInteger NULLABLE, FK → categories, cascadeOnDelete   (NULL = global bucket)
  positionable_type  string   (morph alias: 'product' | 'combo')
  positionable_id    unsignedBigInteger
  position           unsignedInteger  default 0
  timestamps
  unique (category_id, positionable_type, positionable_id)   // treat NULL category as a real bucket
  index  (category_id, position)
```

- **morphMap** registered in `AppServiceProvider` (or Product/Ecommerce provider):
  `Relation::enforceMorphMap(['product' => Product::class, 'combo' => Combo::class])`.
- Model `App\Models\CatalogPosition` (or `Modules\Product\Models\CatalogPosition`) with a
  `positionable()` morphTo. Products/Combos get a `catalogPositions()` morphMany for cleanup.
- `position` stored against the **viewing category** (the selected filter), or `NULL` for global.
- Rows created **lazily on first reorder**; missing rows fall back to `created_at` (nulls last).
  No mass backfill.
- **Global-bucket uniqueness caveat**: MySQL's `UNIQUE` index treats each `NULL` as distinct, so
  the unique index does **not** prevent duplicate global rows `(NULL, 'product', 5)`. It only
  protects non-null-category rows. Global-bucket uniqueness is therefore enforced at the
  application layer: all writes use `updateOrCreate`/upsert keyed on the
  `(category_id, positionable_type, positionable_id)` triple, so duplicates are never created.
  The nullable FK is kept for `cascadeOnDelete` cleanup when a real category is deleted; global
  rows are cleaned via the item's `catalogPositions()` morphMany on product/combo delete.

### 2. Admin — relocate combo management under `/admin/products`

- Add routes (in `Modules/Product/routes/web.php`, `products` prefix) pointing at the existing
  `\Modules\Ecommerce\Http\Controllers\ComboController`:
  - `GET  products/combos/create`         → `products.combos.create`
  - `POST products/combos`                → `products.combos.store`
  - `GET  products/combos/{combo}/edit`   → `products.combos.edit`
  - `PUT  products/combos/{combo}`        → `products.combos.update`
  - `DELETE products/combos/{combo}`      → `products.combos.destroy`
  - `PATCH products/combos/{combo}/toggle-status` → `products.combos.toggle-status`
- Existing combo create/edit Blade views keep working; update their `route()` calls
  (form action, cancel link, redirects in controller) from `ecommerce.combos.*` →
  `products.combos.*`. The `ComboController` redirect targets change to the products list.
- Old admin combo routes: keep `ecommerce.combos.index` (+ create/edit) as **301 redirects** to
  the new URLs / products list, so bookmarks and any hardcoded links survive. Remove the
  `ecommerce.combos.reorder` admin usage (superseded by the unified reorder) — but retain a
  redirect for the index.
- Sidebar: remove the "Combo Packages" `<li>`; add `products.combos.*` to the Products submenu
  `routeIs(...)` active check.

### 3. Admin — "Add Product" split dropdown

In `Modules/Product/resources/views/index.blade.php` page-actions, replace the single
"+ Add Product" link with a `bp-` split/dropdown button:
- **Create Product** → `route('products.create')`
- **Create Combo**  → `route('products.combos.create')`

Uses existing `bp-btn`/dropdown classes; no modal. Add a `[data-theme="dark"]` override if new
CSS is introduced.

### 4. Admin list — merged products + combos

Introduce a `CatalogService` (Product module) with `list(array $filters, int $perPage)`:

- **Membership**:
  - No category filter → all products (all statuses) + all combos.
  - Category `C` selected → subtree ids `S = collectSubtreeIds(C)`.
    - Products: `category_id ∈ S` **OR** `category_product.category_id ∈ S`.
    - Combos: `category_combo.category_id ∈ S`.
- **Type filter** (new): `all` (default) / `product` / `combo`.
- **Other filters**: search, brand, status, stock apply to products; combos are matched by
  search (name) and status (`is_active`) only — brand/stock filters, when set, exclude combos.
- **Ordering**: context = `C` (or `NULL`). Attach each item's `position` from `catalog_positions`
  for that context; sort by `position` (nulls last) then `created_at desc`.
- **Pagination**: merge the two model collections in memory, sort, then wrap in
  `Illuminate\Pagination\LengthAwarePaginator` with `withQueryString()`.
  Scale caveat documented: fine for tens–low-hundreds of items; revisit with a DB `UNION`
  or materialized ordering if the catalog grows large.

`ProductController::index` delegates to `CatalogService`. Stats cards stay product-based
(Total/Active/Low/Out) — combos are not counted in those four stats (documented).

**Row rendering** (`index.blade.php`): iterate the merged paginator. Detect item type
(`$item instanceof Combo`) and render:
- **Product row**: unchanged.
- **Combo row**: same `<tr data-id>` structure with `data-type="combo"`; a **"Combo" badge**
  by the name; columns adapt — Image=thumbnail, SKU cell=item count, Category=combo categories,
  Brand="—", Cost/Sell=summed (strike) / combo price, Stock=min buildable (`availableStock`),
  Status=toggle (`products.combos.toggle-status`), Actions=Edit/Delete/Toggle → `products.combos.*`.
- Product rows carry `data-type="product"`. Drag handle shared → interleaved reorder.

### 5. Admin reorder endpoint (context-aware)

- New route `POST products/catalog/reorder` → `products.catalog.reorder`
  (`ProductController::reorderCatalog` or `CatalogService::reorder`).
- Payload: `{ category_id: int|null, items: [{ type: 'product'|'combo', id: int }, …] }`.
  Validated: `type` in whitelist, `id` exists in the respective table, `category_id` nullable
  `exists:categories,id`.
- Writes `catalog_positions` for that context in a transaction: upsert `(category_id, type, id)`
  with `position = index + 1`.
- Drag JS (`public/js/app.js` reorder handler): extend `persist()` to emit `{type,id}` pairs from
  `data-type`/`data-id`, include the active `category_id` (read from the category filter select),
  and POST to the new endpoint. The legacy `products.reorder` (global product-only) can remain for
  back-compat but the list uses the new one.

### 6. Storefront — interleaved per-category order

- `ShopController::index` / `StorefrontService`: when a category is active, build the grid from
  `catalog_positions` for that category — products **and** combos interleaved by `position`,
  items without a row appended by `created_at`. Replaces the "products first, `$categoryCombos`
  appended" approach.
- Main `/shop` (no category) and home listings use the **global** (`NULL`) context order.
- Membership mirrors admin (category + descendants). Storefront visibility rules for products
  (`storefrontVisible`) and `Combo::active()` still apply.
- The shop grid view already renders `product-card` vs `combo-card`; keep that, driven by item type.
- Dedicated `/combos` storefront page: unchanged.

### 7. Tests

- `catalog_positions`: uniqueness per (category, type, id); morphMap resolves both types.
- `CatalogService::list`: category+subcategory membership (product via `category_id` and via
  pivot; combo via `category_combo`); type filter; interleaved order honors positions; lazy
  fallback for unpositioned items; pagination counts.
- `reorder`: writes per-context positions; the same combo gets different positions under
  category A vs B vs global; product+combo interleaving persists.
- Admin URL move: `products.combos.create` opens the existing form; old
  `ecommerce.combos.index` 301-redirects.
- Storefront: category page renders the exact admin-defined interleaved order; global order used
  when no category.

## Out of scope

- Moving `ComboController`/combo services/views between modules (URLs relocate only).
- Changing combo pricing, the choose-variant feature, or combo detail pages.
- Counting combos in the four product stat cards.
- A DB `UNION`/materialized ordering optimization (documented as a future step if scale demands).
- Reworking product single-`category_id` vs pivot duality (used as-is).

## Rollout / migration notes

- New table only; no destructive schema change. `down()` drops `catalog_positions`.
- No data backfill required (lazy positions). Existing `products.position` and
  `combos.sort_order` remain for back-compat; they are no longer the source of truth for the
  unified list but are left intact.
