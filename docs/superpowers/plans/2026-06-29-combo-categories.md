# Combo Package Categorization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let admins assign multiple existing product categories to a combo package, filter combos by category in the admin list, and let shoppers filter the storefront combos page by category.

**Architecture:** Combos reuse the existing `categories` table via a new `category_combo` many-to-many pivot. Category sync happens in `ComboService::persist` (the single write path). Admin form reuses the product form's searchable category-checkbox UI; admin list and storefront combos page gain a category filter.

**Tech Stack:** Laravel 12, Eloquent, Blade, Bootstrap 5, jQuery. Tests: PHPUnit 11 via `php artisan test`.

## Global Constraints

- No hardcoded URLs — always use named routes (`route('...')`) in Blade, PHP, and JS.
- Every `<script>` block and JS file starts with `'use strict';`.
- No inline `style="..."`; styles go in CSS files using `bp-` classes (storefront uses `public/website/assets/css/style.css`).
- Use `$fillable` (never `$guarded = []`); money columns are `decimal`.
- Eager-load to avoid N+1; paginate list queries.
- Foreign-key columns must be cast (`'integer'`) so strict comparisons survive the live MySQL/PDO string-typing (see recent FK-cast fixes).
- Currency display via `currency_symbol()`; BDT lakh formatting already handled by helpers.
- Module namespaces: `Modules\Ecommerce\...`, `Modules\Category\Models\Category`.
- Test base class `Tests\TestCase` exposes `$this->admin` (authenticated admin user). Storefront routes are public (no auth).

---

### Task 1: Pivot table + model relations

**Files:**
- Create: `Modules/Ecommerce/database/migrations/2026_06_29_000001_create_category_combo_table.php`
- Modify: `Modules/Ecommerce/app/Models/Combo.php`
- Modify: `Modules/Category/app/Models/Category.php`
- Test: `Modules/Ecommerce/tests/Feature/ComboCategoryTest.php` (new)

**Interfaces:**
- Produces: `Combo::categories(): BelongsToMany` and `Category::combos(): BelongsToMany`, both over the `category_combo` pivot. Later tasks call `$combo->categories()->sync(array $ids)` and `$combo->categories` / `$category->combos`.

- [ ] **Step 1: Write the migration**

Create `Modules/Ecommerce/database/migrations/2026_06_29_000001_create_category_combo_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_combo', function (Blueprint $table) {
            $table->foreignId('combo_id')->constrained('combos')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['combo_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_combo');
    }
};
```

- [ ] **Step 2: Add the `categories()` relation to the Combo model**

In `Modules/Ecommerce/app/Models/Combo.php`, add this method inside the class (e.g. after `homepageSections()`):

```php
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Category\Models\Category::class, 'category_combo');
    }
```

(The `use ...BelongsToMany;` import already exists in this file.)

- [ ] **Step 3: Add the inverse `combos()` relation to the Category model**

In `Modules/Category/app/Models/Category.php`, add this method inside the class (near the other relations):

```php
    public function combos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\Modules\Ecommerce\Models\Combo::class, 'category_combo');
    }
```

- [ ] **Step 4: Write the failing relation test**

Create `Modules/Ecommerce/tests/Feature/ComboCategoryTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

class ComboCategoryTest extends TestCase
{
    use CreatesComboTestData;

    public function test_combo_and_category_relate_through_pivot(): void
    {
        $combo = Combo::create([
            'name' => 'Relation Combo', 'combo_price' => 100,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true,
        ]);
        $catA = Category::create(['name' => 'Cat A', 'slug' => 'cat-a-'.uniqid()]);
        $catB = Category::create(['name' => 'Cat B', 'slug' => 'cat-b-'.uniqid()]);

        $combo->categories()->sync([$catA->id, $catB->id]);

        $this->assertCount(2, $combo->fresh()->categories);
        $this->assertTrue($catA->fresh()->combos->contains($combo->id));
    }
}
```

- [ ] **Step 5: Run migration + test**

Run: `php artisan migrate && php artisan test --filter=ComboCategoryTest`
Expected: migration runs; `test_combo_and_category_relate_through_pivot` PASSES.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/database/migrations/2026_06_29_000001_create_category_combo_table.php Modules/Ecommerce/app/Models/Combo.php Modules/Category/app/Models/Category.php Modules/Ecommerce/tests/Feature/ComboCategoryTest.php
git commit -m "feat(combo): add category_combo pivot and combo<->category relations"
```

---

### Task 2: Validation + service sync

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Requests/StoreComboRequest.php`
- Modify: `Modules/Ecommerce/app/Http/Requests/UpdateComboRequest.php`
- Modify: `Modules/Ecommerce/app/Services/ComboService.php:154-192` (the `persist` method)
- Test: `Modules/Ecommerce/tests/Feature/ComboAdminTest.php` (add cases)

**Interfaces:**
- Consumes: `Combo::categories()` from Task 1.
- Produces: posting `categories` (array of category ids) to combo store/update persists pivot rows; omitting/empty clears them.

- [ ] **Step 1: Add validation rules to both form requests**

In `Modules/Ecommerce/app/Http/Requests/StoreComboRequest.php` AND `Modules/Ecommerce/app/Http/Requests/UpdateComboRequest.php`, add these two lines to the `rules()` array (e.g. right after the `sort_order` rule):

```php
            'categories'         => ['nullable', 'array'],
            'categories.*'       => ['integer', 'exists:categories,id'],
```

- [ ] **Step 2: Sync categories in `ComboService::persist`**

In `Modules/Ecommerce/app/Services/ComboService.php`, inside the `persist()` transaction, add a sync call after the gallery block and before `return $combo->fresh(...)`. Change the tail of the method to:

```php
            $combo->galleryImages()->delete();
            foreach (array_values($data['gallery'] ?? []) as $i => $path) {
                if (! $path) {
                    continue;
                }
                $combo->galleryImages()->create(['image_path' => $path, 'sort_order' => $i]);
            }

            $combo->categories()->sync(array_map('intval', $data['categories'] ?? []));

            return $combo->fresh(['items', 'galleryImages', 'categories']);
```

(Only the `categories()->sync(...)` line and the added `'categories'` in `fresh()` are new; the gallery block above is unchanged context.)

- [ ] **Step 3: Write the failing tests**

In `Modules/Ecommerce/tests/Feature/ComboAdminTest.php`, add these two methods inside the class. Add `use Modules\Category\Models\Category;` to the imports at the top.

```php
    public function test_admin_can_create_combo_with_categories(): void
    {
        $p1 = $this->makeProduct(['sell_price' => 500]);
        $catA = Category::create(['name' => 'Bundles', 'slug' => 'bundles-'.uniqid()]);
        $catB = Category::create(['name' => 'Gifts', 'slug' => 'gifts-'.uniqid()]);

        $this->actingAs($this->admin)->post(route('ecommerce.combos.store'), [
            'name' => 'Categorized Combo', 'combo_price' => 400,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => 1,
            'items' => [['product_id' => $p1->id, 'variant_id' => null, 'quantity' => 1]],
            'categories' => [$catA->id, $catB->id],
        ])->assertRedirect(route('ecommerce.combos.index'));

        $combo = Combo::firstWhere('name', 'Categorized Combo');
        $this->assertEqualsCanonicalizing([$catA->id, $catB->id], $combo->categories->pluck('id')->all());
    }

    public function test_update_can_clear_categories(): void
    {
        $p1 = $this->makeProduct(['sell_price' => 500]);
        $cat = Category::create(['name' => 'Temp', 'slug' => 'temp-'.uniqid()]);
        $combo = Combo::create([
            'name' => 'Editable', 'combo_price' => 100,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true,
        ]);
        $combo->items()->create(['product_id' => $p1->id, 'quantity' => 1, 'sort_order' => 0]);
        $combo->categories()->sync([$cat->id]);

        $this->actingAs($this->admin)->put(route('ecommerce.combos.update', $combo), [
            'name' => 'Editable', 'combo_price' => 100,
            'discount_type' => 'none', 'discount_value' => 0, 'is_active' => 1,
            'items' => [['product_id' => $p1->id, 'variant_id' => null, 'quantity' => 1]],
            // no 'categories' key => should clear
        ])->assertRedirect(route('ecommerce.combos.index'));

        $this->assertCount(0, $combo->fresh()->categories);
    }

    public function test_invalid_category_id_is_rejected(): void
    {
        $p1 = $this->makeProduct(['sell_price' => 500]);
        $this->actingAs($this->admin)->from(route('ecommerce.combos.create'))
            ->post(route('ecommerce.combos.store'), [
                'name' => 'Bad Cat', 'combo_price' => 100,
                'discount_type' => 'none', 'discount_value' => 0, 'is_active' => 1,
                'items' => [['product_id' => $p1->id, 'variant_id' => null, 'quantity' => 1]],
                'categories' => [999999],
            ])->assertSessionHasErrors('categories.0');
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ComboAdminTest`
Expected: all ComboAdminTest cases PASS (the 3 new + the 3 existing).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Http/Requests/StoreComboRequest.php Modules/Ecommerce/app/Http/Requests/UpdateComboRequest.php Modules/Ecommerce/app/Services/ComboService.php Modules/Ecommerce/tests/Feature/ComboAdminTest.php
git commit -m "feat(combo): validate and persist combo categories in ComboService"
```

---

### Task 3: Admin form category multi-select

**Files:**
- Create: `Modules/Ecommerce/resources/views/combos/_categories-card.blade.php`
- Modify: `Modules/Ecommerce/resources/views/combos/create.blade.php` (right column, after the Visibility card ~line 199)
- Modify: `Modules/Ecommerce/resources/views/combos/edit.blade.php` (matching right-column spot)
- Modify: `Modules/Ecommerce/app/Http/Controllers/ComboController.php:29-34` (`create`) and `:47-57` (`edit`)
- Test: `Modules/Ecommerce/tests/Feature/ComboCategoryTest.php` (add a render test)

**Interfaces:**
- Consumes: `Combo::categories()` (Task 1); controller now passes `$categories` (root category tree) and the partial reads `$selectedCategoryIds`.
- Produces: combo create/edit forms submit `categories[]` checkboxes (consumed by Task 2's validation/sync).

- [ ] **Step 1: Create the shared categories-card partial**

Create `Modules/Ecommerce/resources/views/combos/_categories-card.blade.php`. It reuses the existing `bp-cat-*` classes (already in `public/css/style.css` from the product form — no new CSS):

```blade
@php
    /** @var \Illuminate\Support\Collection $categories root categories with nested children */
    $flattenCategoryTree = function ($items, $depth = 0) use (&$flattenCategoryTree) {
        $out = [];
        foreach ($items as $item) {
            $out[] = ['id' => $item->id, 'name' => $item->name, 'depth' => $depth];
            $children = $item->children ?? collect();
            if (count($children)) {
                $out = array_merge($out, $flattenCategoryTree($children, $depth + 1));
            }
        }
        return $out;
    };
    $flatCategories = $flattenCategoryTree($categories ?? collect());
    $selectedCategoryIds = collect($selectedCategoryIds ?? [])->map(fn ($v) => (int) $v)->all();
@endphp

<div class="bp-card mb-4" id="comboCategoriesCard">
    <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2 text-primary"></i>Categories</h5>
    </div>
    <div class="bp-card-body">
        <div class="bp-cat-search mb-2">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="bp-form-control" id="comboCategorySearch" placeholder="Search categories...">
        </div>
        <div class="bp-cat-list" id="comboCategoryList">
            @forelse ($flatCategories as $cat)
                <label class="bp-cat-item bp-cat-depth-{{ $cat['depth'] }}"
                       data-name="{{ \Illuminate\Support\Str::lower($cat['name']) }}"
                       style="--depth: {{ $cat['depth'] }};">
                    <input type="checkbox" name="categories[]" value="{{ $cat['id'] }}" class="cat-checkbox"
                           {{ in_array($cat['id'], $selectedCategoryIds, true) ? 'checked' : '' }}>
                    <span class="bp-check-box"></span>
                    <span class="bp-cat-label">{{ $cat['name'] }}</span>
                </label>
            @empty
                <div class="text-muted fs-12 py-2">No categories yet. Create categories first.</div>
            @endforelse
            <div class="bp-cat-empty text-center text-muted fs-12 py-3 d-none" id="comboCategoryEmpty">No matching categories.</div>
        </div>
        @error('categories')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="fs-11 text-muted mt-2"><i class="fa-solid fa-circle-info me-1"></i> A combo can belong to multiple categories.</div>
    </div>
</div>

<script>
'use strict';
$(function () {
    if (window.__comboCatSearchBound) { return; }
    window.__comboCatSearchBound = true;
    $(document).on('input', '#comboCategorySearch', function () {
        var q = $(this).val().toString().toLowerCase().trim();
        var anyVisible = false;
        $('#comboCategoryList .bp-cat-item').each(function () {
            var match = $(this).attr('data-name').indexOf(q) !== -1;
            $(this).toggleClass('d-none', q !== '' && !match);
            if (match) { anyVisible = true; }
        });
        $('#comboCategoryEmpty').toggleClass('d-none', anyVisible);
    });
});
</script>
```

> The inline `style="--depth: ..."` sets a CSS custom property consumed by the existing `.bp-cat-depth-*` rules — this is the exact pattern the product form uses, allowed because the indentation depth is data-driven.

- [ ] **Step 2: Include the partial in the create form**

In `Modules/Ecommerce/resources/views/combos/create.blade.php`, immediately after the Visibility `bp-card` (closes at ~line 199) and before the submit-buttons `div` (~line 201), add:

```blade
      @include('ecommerce::combos._categories-card', [
        'categories' => $categories,
        'selectedCategoryIds' => old('categories', []),
      ])
```

- [ ] **Step 3: Include the partial in the edit form**

In `Modules/Ecommerce/resources/views/combos/edit.blade.php`, in the same right-column position (after the Visibility card, before the submit buttons), add:

```blade
      @include('ecommerce::combos._categories-card', [
        'categories' => $categories,
        'selectedCategoryIds' => old('categories', $combo->categories->pluck('id')->all()),
      ])
```

- [ ] **Step 4: Pass the category tree from the controller**

In `Modules/Ecommerce/app/Http/Controllers/ComboController.php`, add the import at the top:

```php
use Modules\Category\Models\Category;
```

Define a private helper and use it in `create()` and `edit()`. Replace the `create()` method body and the `edit()` view payload:

```php
    public function create(): View
    {
        $products = Product::active()->with(['images', 'variants.attributeValues.attribute'])->orderBy('name')->get();

        return view('ecommerce::combos.create', [
            'products'   => $products,
            'categories' => $this->categoryTree(),
        ]);
    }

    public function edit(Combo $combo): View
    {
        $combo->load(['items.product.images', 'items.variant', 'galleryImages', 'categories']);
        $products = Product::active()->with(['images', 'variants.attributeValues.attribute'])->orderBy('name')->get();

        return view('ecommerce::combos.edit', [
            'combo'      => $combo,
            'products'   => $products,
            'categories' => $this->categoryTree(),
            'service'    => $this->combos,
        ]);
    }

    private function categoryTree()
    {
        return Category::active()->ordered()
            ->with('children', 'children.children')
            ->whereNull('parent_id')
            ->get();
    }
```

- [ ] **Step 5: Write the failing render test**

In `Modules/Ecommerce/tests/Feature/ComboCategoryTest.php`, add:

```php
    public function test_create_form_renders_category_checkboxes(): void
    {
        Category::create(['name' => 'Visible Cat', 'slug' => 'visible-cat-'.uniqid(), 'status' => 'active']);

        $this->actingAs($this->admin)->get(route('ecommerce.combos.create'))
            ->assertOk()
            ->assertSee('name="categories[]"', false)
            ->assertSee('Visible Cat');
    }
```

> `status => 'active'` is required so `Category::active()` includes it; check the `scopeActive` definition in `Category.php:115` for the exact column/value if this assertion fails.

- [ ] **Step 6: Run the test**

Run: `php artisan test --filter=ComboCategoryTest`
Expected: all ComboCategoryTest cases PASS.

- [ ] **Step 7: Manually verify the form (smoke)**

Run the dev server, open `route('ecommerce.combos.create')`, confirm the Categories card shows a searchable checkbox list; create a combo with 2 categories ticked; edit it and confirm both stay checked.

- [ ] **Step 8: Commit**

```bash
git add Modules/Ecommerce/resources/views/combos/_categories-card.blade.php Modules/Ecommerce/resources/views/combos/create.blade.php Modules/Ecommerce/resources/views/combos/edit.blade.php Modules/Ecommerce/app/Http/Controllers/ComboController.php Modules/Ecommerce/tests/Feature/ComboCategoryTest.php
git commit -m "feat(combo): category multi-select on combo create/edit forms"
```

---

### Task 4: Admin list — category column + filter

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/ComboController.php:19-27` (`index`)
- Modify: `Modules/Ecommerce/resources/views/combos/index.blade.php`
- Test: `Modules/Ecommerce/tests/Feature/ComboCategoryTest.php` (add filter test)

**Interfaces:**
- Consumes: `Combo::categories()` (Task 1), `Category::active()->ordered()` (exists).
- Produces: admin combo index accepts `?category=<id>` and shows a category column + filter `<select>`.

- [ ] **Step 1: Add filtering + eager-load to `index`**

In `Modules/Ecommerce/app/Http/Controllers/ComboController.php`, change `index()` to accept the request, filter, and pass categories. Add `use Illuminate\Http\Request;` to the imports (the `Category` import was added in Task 3):

```php
    public function index(Request $request): View
    {
        $categoryId = $request->integer('category') ?: null;

        $combos = Combo::with(['items.product', 'categories'])
            ->when($categoryId, fn ($q) => $q->whereHas(
                'categories', fn ($c) => $c->where('categories.id', $categoryId)
            ))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(15)
            ->appends($request->query());

        return view('ecommerce::combos.index', [
            'combos'           => $combos,
            'service'          => $this->combos,
            'filterCategories' => Category::active()->ordered()->get(),
            'activeCategory'   => $categoryId,
        ]);
    }
```

- [ ] **Step 2: Add the filter bar above the table**

In `Modules/Ecommerce/resources/views/combos/index.blade.php`, immediately after `@section('content')`/the `@php` block and before `<div class="bp-card">`, add a filter bar:

```blade
<form method="GET" action="{{ route('ecommerce.combos.index') }}" class="bp-filter-bar mb-3">
  <select name="category" class="bp-form-select" onchange="this.form.submit()">
    <option value="">{{ __('All categories') }}</option>
    @foreach ($filterCategories as $cat)
      <option value="{{ $cat->id }}" {{ (int) $activeCategory === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
    @endforeach
  </select>
  @if ($activeCategory)
    <a href="{{ route('ecommerce.combos.index') }}" class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-rotate"></i></a>
  @endif
</form>
```

- [ ] **Step 3: Add the Category column to the table**

In the same view, add a header cell. Change the `<thead>` row from:

```blade
            <th>Status</th>
            <th width="140">Actions</th>
```
to:
```blade
            <th>Categories</th>
            <th>Status</th>
            <th width="140">Actions</th>
```

Add the matching body cell. Immediately before the `<td>` that contains the status toggle `<form action="{{ route('ecommerce.combos.toggle-status', $combo) }}" ...>`, insert:

```blade
              <td>
                @forelse ($combo->categories as $cat)
                  <span class="bp-badge bp-badge-secondary">{{ $cat->name }}</span>
                @empty
                  <span class="text-muted fs-12">—</span>
                @endforelse
              </td>
```

Then update the empty-state colspan from `colspan="6"` to `colspan="7"`.

- [ ] **Step 4: Write the failing filter test**

In `Modules/Ecommerce/tests/Feature/ComboCategoryTest.php`, add:

```php
    public function test_admin_index_filters_by_category(): void
    {
        $cat = Category::create(['name' => 'Filterable', 'slug' => 'filterable-'.uniqid(), 'status' => 'active']);
        $inCat = Combo::create(['name' => 'In Category', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $other = Combo::create(['name' => 'Not In Category', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $inCat->categories()->sync([$cat->id]);

        $this->actingAs($this->admin)->get(route('ecommerce.combos.index', ['category' => $cat->id]))
            ->assertOk()
            ->assertSee('In Category')
            ->assertDontSee('Not In Category');
    }
```

- [ ] **Step 5: Run the test**

Run: `php artisan test --filter=ComboCategoryTest`
Expected: all cases PASS.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/ComboController.php Modules/Ecommerce/resources/views/combos/index.blade.php Modules/Ecommerce/tests/Feature/ComboCategoryTest.php
git commit -m "feat(combo): category column and filter on admin combo list"
```

---

### Task 5: Storefront combos category filter

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/ComboController.php:15-35` (`index`)
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/combos/index.blade.php`
- Modify: `public/website/assets/css/style.css` (append filter styles)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontComboTest.php` (add cases)

**Interfaces:**
- Consumes: `Combo::active()` + `Combo::categories()` (Task 1), `Category::combos()` (Task 1).
- Produces: storefront `/combos?category=<slug>` filters combos; passes `$filterCategories` and `$activeCategory` to the view.

- [ ] **Step 1: Add filtering to the storefront `index`**

In `Modules/Ecommerce/app/Http/Controllers/Storefront/ComboController.php`, add `use Illuminate\Http\Request;` and `use Modules\Category\Models\Category;` to the imports, then change `index()`:

```php
    public function index(Request $request): View
    {
        $slug = trim((string) $request->query('category', ''));
        $activeCategory = $slug !== '' ? Category::where('slug', $slug)->first() : null;

        $combos = Combo::active()
            ->when($activeCategory, fn ($q) => $q->whereHas(
                'categories', fn ($c) => $c->where('categories.id', $activeCategory->id)
            ))
            ->with(['items.product.images', 'items.variant'])
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(12)
            ->appends($request->query());

        $filterCategories = Category::whereHas('combos', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $title = $activeCategory ? $activeCategory->name.' Combo Packages' : 'Combo Packages';
        $seo = Seo::make()
            ->title($title)
            ->description('Shop curated combo packages — bundled products at a better price.')
            ->breadcrumbs([
                ['name' => 'Home', 'url' => route('storefront.home')],
                ['name' => 'Combo Packages', 'url' => null],
            ])
            ->canonical(route('storefront.combos.index', $activeCategory ? ['category' => $activeCategory->slug] : []));

        return view('ecommerce::storefront.pages.combos.index', [
            'combos'           => $combos,
            'service'          => $this->service,
            'seo'              => $seo,
            'filterCategories' => $filterCategories,
            'activeCategory'   => $activeCategory,
        ]);
    }
```

(An unknown slug yields `$activeCategory = null` → all combos shown, fail-open, no 404.)

- [ ] **Step 2: Render the filter chips in the storefront view**

In `Modules/Ecommerce/resources/views/storefront/pages/combos/index.blade.php`, add the filter row immediately after the closing `</div>` of the `section_heading` row and before `@if ($combos->count() > 0)`:

```blade
            @if ($filterCategories->count())
                <div class="row">
                    <div class="col-12">
                        <div class="combo_cat_filter">
                            <a href="{{ route('storefront.combos.index') }}"
                               class="combo_cat_chip {{ ! $activeCategory ? 'active' : '' }}">All</a>
                            @foreach ($filterCategories as $cat)
                                <a href="{{ route('storefront.combos.index', ['category' => $cat->slug]) }}"
                                   class="combo_cat_chip {{ $activeCategory && $activeCategory->id === $cat->id ? 'active' : '' }}">{{ $cat->name }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
```

- [ ] **Step 3: Append the filter styles**

Append to `public/website/assets/css/style.css` (no `style=""` inline; storefront theme is light-only so no dark override needed):

```css
/* ===== Combo category filter ===== */
.combo_cat_filter { display: flex; flex-wrap: wrap; gap: 8px; margin: 6px 0 24px; }
.combo_cat_chip {
    display: inline-block; padding: 6px 16px; border-radius: 20px;
    border: 1px solid #e0e0e0; background: #fff; color: #2C3E50;
    font-size: 13px; font-weight: 600; transition: all .15s ease; text-decoration: none;
}
.combo_cat_chip:hover { border-color: var(--themeColor, #1B4F72); color: var(--themeColor, #1B4F72); }
.combo_cat_chip.active { background: var(--themeColor, #1B4F72); border-color: var(--themeColor, #1B4F72); color: #fff; }
```

- [ ] **Step 4: Write the failing storefront tests**

In `Modules/Ecommerce/tests/Feature/StorefrontComboTest.php`, add `use Modules\Category\Models\Category;` (if absent) and add:

```php
    public function test_storefront_filters_combos_by_category(): void
    {
        $cat = Category::create(['name' => 'Storefront Cat', 'slug' => 'sf-cat-'.uniqid(), 'status' => 'active']);
        $inCat = Combo::create(['name' => 'Shown Combo', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $other = Combo::create(['name' => 'Hidden Combo', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $inCat->categories()->sync([$cat->id]);

        $this->get(route('storefront.combos.index', ['category' => $cat->slug]))
            ->assertOk()
            ->assertSee('Shown Combo')
            ->assertDontSee('Hidden Combo');
    }

    public function test_unknown_category_slug_shows_all_combos(): void
    {
        Combo::create(['name' => 'Always Shown', 'combo_price' => 100, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);

        $this->get(route('storefront.combos.index', ['category' => 'no-such-slug']))
            ->assertOk()
            ->assertSee('Always Shown');
    }
```

> If `StorefrontComboTest` does not already `use CreatesComboTestData` and import `Combo`, add `use Modules\Ecommerce\Models\Combo;` and the trait. Check the file's existing imports first.

- [ ] **Step 5: Run the tests**

Run: `php artisan test --filter=StorefrontComboTest`
Expected: new cases PASS, existing cases still PASS.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/ComboController.php Modules/Ecommerce/resources/views/storefront/pages/combos/index.blade.php public/website/assets/css/style.css Modules/Ecommerce/tests/Feature/StorefrontComboTest.php
git commit -m "feat(combo): category filter on storefront combos page"
```

---

### Task 6: Full suite + cleanup

**Files:** none (verification only)

- [ ] **Step 1: Run the full combo + category test groups**

Run: `php artisan test --filter='Combo|StorefrontCombo'`
Expected: all PASS.

- [ ] **Step 2: Manual end-to-end smoke**

- Admin: create a combo in 2 categories → list shows both badges → filter by one category shows it.
- Storefront: `/combos` shows category chips; clicking one filters; "All" clears; page-2 keeps the filter.

- [ ] **Step 3: Final commit (if any doc/cleanup)**

```bash
git add -A
git commit -m "chore(combo): finalize combo categorization" --allow-empty
```

---

## Self-Review Notes

- **Spec coverage:** data model (Task 1), admin form multi-select (Task 3), admin list column + filter (Task 4), storefront filter incl. empty-filter exclusion + fail-open unknown slug (Task 5), `ComboService::persist` write path (Task 2), validation (Task 2). All spec sections mapped.
- **FK-cast constraint:** `category_combo` uses `foreignId(...)->constrained()` (correct types); relations use `where('categories.id', $id)` with integer ids from `$request->integer()` / model `->id`, avoiding string/int strict-comparison pitfalls.
- **Naming consistency:** relation `categories()` / `combos()`, pivot `category_combo`, request key `categories[]`, query param `category` (id in admin, slug in storefront — intentional per spec) used consistently across all tasks.
- **Non-goals respected:** no combo-only taxonomy, no combos injected into product category pages, no per-category sort order.
