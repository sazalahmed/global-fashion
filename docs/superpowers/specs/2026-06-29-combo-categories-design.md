# Design: Combo Package Categorization (Ecommerce)

**Date:** 2026-06-29
**Module:** Ecommerce (combos) + Category (read-only reuse)
**Status:** Approved design — pending implementation plan

## Problem / Goal

Combo packages currently have no categorization. Admins need to **categorize
combos** so they can be organized in the admin panel and discovered by shoppers
on the storefront. Combos reuse the **existing product `categories`** taxonomy
(the same hierarchical Category records products use), and a combo can belong to
**multiple categories** at once.

## Locked Decisions

- **Category source:** reuse the existing `categories` table (shared with
  products). No separate combo-only category type, no free-text field.
- **Cardinality:** **many-to-many** — a combo can be in multiple categories
  (e.g. a bundle under "Men", "Shirts", and "Gifts" simultaneously). Backed by
  a `category_combo` pivot table, mirroring the product `category_product`
  pattern.
- **Scope:** Admin **and** storefront.
  - Admin: category multi-select in the combo form; category column + category
    filter on the combo list.
  - Storefront: a category **filter** on the `/combos` page (view one category
    at a time, like a normal filter). A combo still belongs to many categories.
- **Out of scope (now):** injecting combos into existing product *category
  pages* (the shop category listings). Combos stay on their dedicated `/combos`
  page. Can be added later.
- **Write path:** category sync happens in `ComboService::persist` (the single
  place both store and update already route through), not in controllers.

## Current-State Facts (integration points)

- `combos` table: `name, slug, thumbnail, description, combo_price,
  discount_type, discount_value, is_active, sort_order`, soft-deletes. No
  category column.
- `Combo` model (`Modules\Ecommerce\app\Models\Combo.php`): relations `items()`,
  `galleryImages()`, `homepageSections()` (belongsToMany via pivot), scope
  `active()`. Auto-generates `slug` on create.
- `Category` model (`Modules\Category\app\Models\Category.php`): hierarchical
  (`parent_id`, `children()`), soft-deletes, `status`, `show_in_menu`, has a
  `category_product` many-to-many with products. Helper `mostSpecificId()`.
- Products select categories via a **searchable checkbox list** of a flattened
  category tree (`name="categories[]"`) — see
  `Modules/Product/resources/views/create.blade.php` (`$flattenCategoryTree`,
  `#categoryList`, `.bp-cat-list`). This is the UI pattern to reuse.
- Admin combo controller: `Modules\Ecommerce\app\Http\Controllers\ComboController`
  (`index/create/store/edit/update/destroy/toggleStatus`).
- Admin form requests: `StoreComboRequest`, `UpdateComboRequest`.
- `ComboService::persist($data, ?Combo $combo)` centralizes combo writes.
- Storefront combo controller:
  `Modules\Ecommerce\app\Http\Controllers\Storefront\ComboController` —
  `index()` lists `Combo::active()->...->paginate(12)`; `show($slug)`.
- Storefront combos index view:
  `Modules/Ecommerce/resources/views/storefront/pages/combos/index.blade.php`.

## Data Model

New pivot table `category_combo` (alphabetical singular_singular, matching
`category_product`):

```php
Schema::create('category_combo', function (Blueprint $table) {
    $table->foreignId('combo_id')->constrained('combos')->cascadeOnDelete();
    $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
    $table->primary(['combo_id', 'category_id']); // also enforces uniqueness
});
```

- No change to the `combos` table.
- On `down()`: `Schema::dropIfExists('category_combo')`.

### Model relations

`Combo`:
```php
public function categories(): BelongsToMany
{
    return $this->belongsToMany(\Modules\Category\Models\Category::class, 'category_combo');
}
```

`Category` (inverse, for storefront lookups):
```php
public function combos(): BelongsToMany
{
    return $this->belongsToMany(\Modules\Ecommerce\Models\Combo::class, 'category_combo');
}
```

> Note: `category_combo` has no extra pivot columns (no `sort_order`), unlike
> `homepage_section_combos`. Keep it minimal; add later only if needed.

## Admin Panel

1. **Create/Edit form** (`combos/create.blade.php`, `combos/edit.blade.php`):
   add a "Categories" card that reuses the product form pattern — a searchable,
   depth-indented checkbox list of all categories, `name="categories[]"`.
   - Controller passes a `$categories` tree to both views
     (`Category::with('children')->whereNull('parent_id')->orderBy('sort_order')`
     — match how the product controller supplies it).
   - Edit pre-checks the combo's current category ids
     (`old('categories', $combo->categories->pluck('id')->all())`).
2. **Validation** (`StoreComboRequest` + `UpdateComboRequest`):
   ```php
   'categories'   => ['nullable', 'array'],
   'categories.*' => ['integer', 'exists:categories,id'],
   ```
3. **Persist** (`ComboService::persist`): after the combo is saved, call
   `$combo->categories()->sync($data['categories'] ?? [])`. Works for both
   create and update. Wrap the existing save + sync in a DB transaction if not
   already.
4. **Index list** (`combos/index.blade.php` + `ComboController::index`):
   - Eager-load `categories` (`Combo::with(['items.product', 'categories'])`).
   - Add a **Category** column rendering category names as `bp-badge`s.
   - Add a **category filter** to the filter bar: a `<select>` of categories
     bound to `?category=<id>`; controller applies
     `->when($request->integer('category'), fn ($q, $id) =>
     $q->whereHas('categories', fn ($c) => $c->where('categories.id', $id)))`.
   - Preserve the filter across pagination (`->appends(request()->query())`).

## Storefront

1. **Combos index** (`Storefront\ComboController::index` + index view):
   - Build the filter list: only categories that have at least one **active**
     combo — `Category::whereHas('combos', fn ($q) => $q->where('is_active', true))
     ->orderBy('sort_order')->get()`. Avoids empty filter chips.
   - Read `?category=<slug>` (slug, not id — consistent with storefront URLs).
     When present, scope combos:
     `->whereHas('categories', fn ($c) => $c->where('slug', $slug))`.
     Invalid/unknown slug → just show all (fail open, no 404).
   - Render a chip/dropdown row above the grid: an "All" chip plus one per
     category; the active one is highlighted. Each links to
     `route('storefront.combos.index', ['category' => $slug])`.
   - `->paginate(12)->appends(request()->query())` to keep the filter on page 2+.
   - SEO: when filtered, reflect the category in the page title/description
     (e.g. "<Category> Combo Packages"); canonical points to the filtered URL.
2. **No change** to product category pages or the shop grid.

## Error Handling / Edge Cases

- **No categories selected:** `sync([])` clears all — combo is uncategorized,
  still listed on `/combos` under "All". Valid state.
- **Category soft-deleted:** pivot rows for a deleted category simply stop
  resolving (the `categories()` relation excludes trashed). No orphan UI.
  `cascadeOnDelete` handles hard deletes.
- **Combo deleted (soft):** pivot rows remain harmless; on force-delete the FK
  cascade clears them.
- **Unknown `?category` value:** fail open (show all), never 500/404.
- **N+1:** eager-load `categories` on admin index; storefront already eager
  loads combo relations — add `categories` only if rendered on cards (not
  required for the filter itself).

## Testing

Extend the existing combo test suite (`Modules/Ecommerce/tests/Feature/`):

- **ComboAdminTest:** creating/updating a combo with `categories[]` persists the
  pivot rows; clearing them on update syncs to empty; invalid category id fails
  validation; admin index `?category=` filters the list.
- **New ComboCategoryTest (or extend StorefrontComboTest):** storefront
  `/combos?category=<slug>` returns only combos in that category; unknown slug
  shows all; the filter list excludes categories with no active combos.
- **ComboModelTest:** `Combo::categories()` and `Category::combos()` relations
  resolve the pivot correctly.
- Reuse `CreatesComboTestData` (tests/Concerns) and add a category factory/helper.

## Files Touched (summary)

- **New:** migration `..._create_category_combo_table.php`; (maybe) test
  `ComboCategoryTest.php`.
- **Edit:** `Combo.php`, `Category.php`, `ComboController.php`,
  `Storefront/ComboController.php`, `ComboService.php`, `StoreComboRequest.php`,
  `UpdateComboRequest.php`, `combos/create.blade.php`, `combos/edit.blade.php`,
  `combos/index.blade.php`, `storefront/pages/combos/index.blade.php`.
- **Possibly:** `_combo-form-js.blade.php` (category search filter JS, reused
  from product form), `public/css/style.css` (only if new classes needed —
  prefer reusing `.bp-cat-list` etc.).

## Non-Goals

- Combo-only category taxonomy.
- Combos appearing inside product category pages / shop grid.
- Per-category sort order for combos.
- Nested category auto-selection logic (products' "most specific primary"
  behavior) — combos have no single primary category.
