# Unified Catalog (Products + Combos) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Manage combos inside the admin Products list, show products and combos interleaved per-category with drag-reorder, and render the storefront in that same per-category order.

**Architecture:** A single polymorphic `catalog_positions` table stores an item's `position` per *viewing category* (with a `NULL` = global bucket). A new `CatalogService` merges products + combos into one filtered, ordered, paginated list for admin, and a matching query drives the storefront. Combo admin URLs relocate under `/admin/products`; the existing combo controller/views are reused.

**Tech Stack:** Laravel 12 (modular under `Modules/`), Eloquent, MySQL 8, Blade, jQuery + native HTML5 drag-and-drop, PHPUnit feature tests.

## Global Constraints

- Named routes only — never hardcode URLs (`route('products.combos.create')`, etc.). (CLAUDE.md §2.11)
- No inline CSS; new styles go in `public/css/style.css` as `bp-` classes with a `[data-theme="dark"]` override. (CLAUDE.md §2.2/2.3)
- Every `<script>` block and JS file starts with `'use strict';`. (CLAUDE.md §2.9)
- SOLID: thin controllers delegate to services; validation in Form Requests. (CLAUDE.md §11)
- Money columns `decimal(15,2)`; use `$fillable` (never `$guarded=[]`). (CLAUDE.md §13/14)
- Currency via existing `currency_symbol()`/formatting helpers; dates `DD MMM YYYY`.
- Migrations: define `down()`; FKs via `constrained()`; index hot columns. (CLAUDE.md §14)
- morph alias strings: `'product'` → `Modules\Product\Models\Product`, `'combo'` → `Modules\Ecommerce\Models\Combo`.
- Reorder positions are 1-based; unpositioned items sort after positioned ones, then by `created_at desc`.
- Dev server / admin login for manual QA: `php artisan serve`; `admin@gmail.com` / `1234` at `/admin/login`.
- Run a single test: `php artisan test --filter=TestClassOrMethod`.

---

## File Structure

**Create:**
- `Modules/Product/database/migrations/2026_07_01_000001_create_catalog_positions_table.php` — the ordering table.
- `Modules/Product/app/Models/CatalogPosition.php` — morphTo model.
- `Modules/Product/app/Services/CatalogService.php` — merged list + context-aware reorder.
- `Modules/Product/tests/Feature/CatalogPositionTest.php`
- `Modules/Product/tests/Feature/CatalogServiceListTest.php`
- `Modules/Product/tests/Feature/CatalogReorderTest.php`
- `Modules/Product/tests/Feature/AdminComboRelocationTest.php`
- `Modules/Product/tests/Feature/CatalogListViewTest.php`
- `Modules/Ecommerce/tests/Feature/StorefrontCatalogOrderTest.php`

**Modify:**
- `Modules/Product/app/Models/Product.php` — add `catalogPositions()` morphMany + delete cleanup.
- `Modules/Ecommerce/app/Models/Combo.php` — add `catalogPositions()` morphMany + delete cleanup.
- A service provider (`Modules/Product/app/Providers/ProductServiceProvider.php` or `App\Providers\AppServiceProvider`) — register the morphMap.
- `Modules/Product/routes/web.php` — add `products.combos.*` and `products.catalog.reorder` routes.
- `Modules/Ecommerce/routes/web.php` — convert old admin combo GET routes to 301 redirects.
- `Modules/Ecommerce/app/Http/Controllers/ComboController.php` — redirect targets + any `route()` calls.
- `Modules/Ecommerce/resources/views/combos/create.blade.php` + `edit.blade.php` — form action/cancel `route()` names.
- `Modules/Product/app/Http/Controllers/ProductController.php` — `index()` delegates to `CatalogService`; add `reorderCatalog()`.
- `Modules/Product/resources/views/index.blade.php` — split add-button, type filter, combo rows, reorder wiring.
- `Modules/Core/resources/views/partials/sidebar.blade.php` — drop Combo Packages link; add `products.combos.*` to active check.
- `public/js/app.js` — extend reorder `persist()` to send `{type,id}` + `category_id` to the new endpoint.
- `Modules/Ecommerce/app/Http/Controllers/Storefront/ShopController.php` + `Modules/Ecommerce/app/Services/StorefrontService.php` — interleaved per-category ordering.
- `public/css/style.css` — combo-row badge / split-button styles (+ dark overrides), if needed.

---

### Task 1: `catalog_positions` table, model, and morphMap

**Files:**
- Create: `Modules/Product/database/migrations/2026_07_01_000001_create_catalog_positions_table.php`
- Create: `Modules/Product/app/Models/CatalogPosition.php`
- Modify: `Modules/Product/app/Models/Product.php` (add morphMany + delete cleanup)
- Modify: `Modules/Ecommerce/app/Models/Combo.php` (add morphMany + delete cleanup)
- Modify: service provider for morphMap (`Modules/Product/app/Providers/ProductServiceProvider.php` `boot()`)
- Test: `Modules/Product/tests/Feature/CatalogPositionTest.php`

**Interfaces:**
- Produces: `catalog_positions(id, category_id nullable, positionable_type, positionable_id, position, timestamps)`; morph aliases `'product'`/`'combo'`; `CatalogPosition` with `positionable(): MorphTo`; `Product::catalogPositions()` and `Combo::catalogPositions()` morphMany.

- [ ] **Step 1: Write the failing test**

```php
<?php
// Modules/Product/tests/Feature/CatalogPositionTest.php
namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Product\Models\Product;
use Modules\Product\Models\CatalogPosition;
use Tests\TestCase;

class CatalogPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_morph_map_registers_product_and_combo_aliases(): void
    {
        $map = Relation::morphMap();
        $this->assertSame(\Modules\Product\Models\Product::class, $map['product'] ?? null);
        $this->assertSame(\Modules\Ecommerce\Models\Combo::class, $map['combo'] ?? null);
    }

    public function test_position_row_resolves_back_to_its_product(): void
    {
        $product = Product::factory()->create();
        CatalogPosition::create([
            'category_id' => null,
            'positionable_type' => 'product',
            'positionable_id' => $product->id,
            'position' => 1,
        ]);

        $row = CatalogPosition::first();
        $this->assertTrue($row->positionable->is($product));
        $this->assertEquals('product', $row->positionable_type);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CatalogPositionTest`
Expected: FAIL (class `CatalogPosition` / table not found).

- [ ] **Step 3: Write the migration**

```php
<?php
// Modules/Product/database/migrations/2026_07_01_000001_create_catalog_positions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_positions', function (Blueprint $table) {
            $table->id();
            // NULL category_id = the global (All Categories / main shop) bucket.
            $table->foreignId('category_id')->nullable()
                ->constrained('categories')->cascadeOnDelete();
            $table->string('positionable_type', 32); // 'product' | 'combo'
            $table->unsignedBigInteger('positionable_id');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // Protects non-null-category rows. Global (NULL) uniqueness is
            // enforced in app code via updateOrCreate (MySQL treats NULLs as distinct).
            $table->unique(['category_id', 'positionable_type', 'positionable_id'], 'catalog_positions_unique');
            $table->index(['category_id', 'position']);
            $table->index(['positionable_type', 'positionable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_positions');
    }
};
```

- [ ] **Step 4: Write the `CatalogPosition` model**

```php
<?php
// Modules/Product/app/Models/CatalogPosition.php
namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CatalogPosition extends Model
{
    protected $fillable = [
        'category_id', 'positionable_type', 'positionable_id', 'position',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'positionable_id' => 'integer',
        'position' => 'integer',
    ];

    public function positionable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

- [ ] **Step 5: Register the morphMap and add morphMany relations**

In `Modules/Product/app/Providers/ProductServiceProvider.php` `boot()` (add `use Illuminate\Database\Eloquent\Relations\Relation;`):

```php
Relation::enforceMorphMap([
    'product' => \Modules\Product\Models\Product::class,
    'combo'   => \Modules\Ecommerce\Models\Combo::class,
]);
```

In `Modules/Product/app/Models/Product.php` add:

```php
public function catalogPositions(): \Illuminate\Database\Eloquent\Relations\MorphMany
{
    return $this->morphMany(\Modules\Product\Models\CatalogPosition::class, 'positionable');
}
```

In `Modules/Ecommerce/app/Models/Combo.php` add:

```php
public function catalogPositions(): \Illuminate\Database\Eloquent\Relations\MorphMany
{
    return $this->morphMany(\Modules\Product\Models\CatalogPosition::class, 'positionable');
}
```

Add delete cleanup to BOTH models' `booted()` (create `booted()` on Product if absent; Combo already has one):

```php
static::deleting(function ($model) {
    $model->catalogPositions()->delete();
});
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=CatalogPositionTest`
Expected: PASS (both tests).

- [ ] **Step 7: Commit**

```bash
git add Modules/Product/database/migrations Modules/Product/app/Models/CatalogPosition.php \
  Modules/Product/app/Models/Product.php Modules/Ecommerce/app/Models/Combo.php \
  Modules/Product/app/Providers/ProductServiceProvider.php Modules/Product/tests/Feature/CatalogPositionTest.php
git commit -m "feat(catalog): add polymorphic catalog_positions ordering table + morphMap"
```

---

### Task 2: `CatalogService::reorder` — context-aware position upsert

**Files:**
- Create: `Modules/Product/app/Services/CatalogService.php`
- Test: `Modules/Product/tests/Feature/CatalogReorderTest.php`

**Interfaces:**
- Consumes: `CatalogPosition`, morph aliases from Task 1.
- Produces: `CatalogService::reorder(?int $categoryId, array $items): void` where `$items = [['type'=>'product'|'combo','id'=>int], ...]`. Writes 1-based `position` per `(categoryId, type, id)` via `updateOrCreate` inside a transaction.

- [ ] **Step 1: Write the failing test**

```php
<?php
// Modules/Product/tests/Feature/CatalogReorderTest.php
namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Product\Models\CatalogPosition;
use Modules\Product\Models\Product;
use Modules\Product\Services\CatalogService;
use Tests\TestCase;

class CatalogReorderTest extends TestCase
{
    use RefreshDatabase;

    private function combo(string $name): Combo
    {
        return Combo::create(['name' => $name, 'combo_price' => 100, 'is_active' => true]);
    }

    public function test_reorder_writes_1_based_positions_for_a_category_context(): void
    {
        $cat = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'status' => 'active']);
        $p = Product::factory()->create();
        $c = $this->combo('Bundle A');

        app(CatalogService::class)->reorder($cat->id, [
            ['type' => 'product', 'id' => $p->id],
            ['type' => 'combo', 'id' => $c->id],
        ]);

        $this->assertDatabaseHas('catalog_positions', [
            'category_id' => $cat->id, 'positionable_type' => 'product',
            'positionable_id' => $p->id, 'position' => 1,
        ]);
        $this->assertDatabaseHas('catalog_positions', [
            'category_id' => $cat->id, 'positionable_type' => 'combo',
            'positionable_id' => $c->id, 'position' => 2,
        ]);
    }

    public function test_same_combo_can_hold_different_positions_per_category_and_global(): void
    {
        $a = Category::create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $b = Category::create(['name' => 'B', 'slug' => 'b', 'status' => 'active']);
        $c = $this->combo('Bundle');
        $svc = app(CatalogService::class);

        // Category A: combo at position 1. Category B: combo at position 3. Global: position 2.
        $svc->reorder($a->id, [['type' => 'combo', 'id' => $c->id]]);
        $svc->reorder($b->id, [
            ['type' => 'combo', 'id' => 0], // placeholder skipped below
        ]);
        // Reset B with real ordering putting combo third.
        $p1 = Product::factory()->create(); $p2 = Product::factory()->create();
        $svc->reorder($b->id, [
            ['type' => 'product', 'id' => $p1->id],
            ['type' => 'product', 'id' => $p2->id],
            ['type' => 'combo', 'id' => $c->id],
        ]);
        $svc->reorder(null, [
            ['type' => 'product', 'id' => $p1->id],
            ['type' => 'combo', 'id' => $c->id],
        ]);

        $this->assertEquals(1, CatalogPosition::where('category_id', $a->id)->where('positionable_type','combo')->where('positionable_id',$c->id)->value('position'));
        $this->assertEquals(3, CatalogPosition::where('category_id', $b->id)->where('positionable_type','combo')->where('positionable_id',$c->id)->value('position'));
        $this->assertEquals(2, CatalogPosition::whereNull('category_id')->where('positionable_type','combo')->where('positionable_id',$c->id)->value('position'));
    }

    public function test_reorder_is_idempotent_and_updates_existing_rows(): void
    {
        $c1 = $this->combo('One'); $c2 = $this->combo('Two');
        $svc = app(CatalogService::class);
        $svc->reorder(null, [['type'=>'combo','id'=>$c1->id], ['type'=>'combo','id'=>$c2->id]]);
        $svc->reorder(null, [['type'=>'combo','id'=>$c2->id], ['type'=>'combo','id'=>$c1->id]]);

        $this->assertEquals(1, CatalogPosition::whereNull('category_id')->where('positionable_id',$c2->id)->value('position'));
        $this->assertEquals(2, CatalogPosition::whereNull('category_id')->where('positionable_id',$c1->id)->value('position'));
        $this->assertEquals(2, CatalogPosition::whereNull('category_id')->count());
    }
}
```

Note: ignore the `id => 0` placeholder line — the second `reorder($b->id, …)` call replaces B's ordering. If a `type/id` of 0 is undesirable, delete that first B call; it exists only to prove re-running overwrites cleanly.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CatalogReorderTest`
Expected: FAIL (`CatalogService` not found).

- [ ] **Step 3: Write the minimal `CatalogService::reorder`**

```php
<?php
// Modules/Product/app/Services/CatalogService.php
namespace Modules\Product\Services;

use Illuminate\Support\Facades\DB;
use Modules\Product\Models\CatalogPosition;

class CatalogService
{
    /** Allowed morph aliases for items in the unified catalog. */
    private const TYPES = ['product', 'combo'];

    /**
     * Persist a new interleaved order for the given context.
     *
     * @param  int|null  $categoryId  viewing category id, or null for the global bucket
     * @param  array<int, array{type: string, id: int}>  $items  in display order
     */
    public function reorder(?int $categoryId, array $items): void
    {
        DB::transaction(function () use ($categoryId, $items) {
            $position = 0;
            foreach ($items as $item) {
                $type = $item['type'] ?? null;
                $id = (int) ($item['id'] ?? 0);
                if (! in_array($type, self::TYPES, true) || $id <= 0) {
                    continue;
                }
                $position++;
                CatalogPosition::updateOrCreate(
                    [
                        'category_id' => $categoryId,
                        'positionable_type' => $type,
                        'positionable_id' => $id,
                    ],
                    ['position' => $position],
                );
            }
        });
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CatalogReorderTest`
Expected: PASS (all three tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Product/app/Services/CatalogService.php Modules/Product/tests/Feature/CatalogReorderTest.php
git commit -m "feat(catalog): context-aware CatalogService::reorder"
```

---

### Task 3: `CatalogService::list` — merged, filtered, ordered, paginated

**Files:**
- Modify: `Modules/Product/app/Services/CatalogService.php`
- Test: `Modules/Product/tests/Feature/CatalogServiceListTest.php`

**Interfaces:**
- Consumes: `Product`, `Combo`, `Category`, `CatalogPosition`; `CategoryService::collectSubtreeIds` (or reimplement locally via `recursiveChildren`).
- Produces: `CatalogService::list(array $filters, int $perPage = 15): LengthAwarePaginator`. Filters keys: `search, category (id), brand (id), status, stock, type ('all'|'product'|'combo')`. Each returned item is a `Product` or `Combo` model with a transient `catalog_type` attribute (`'product'`/`'combo'`) and `catalog_position` (int|null) set for the active context.

- [ ] **Step 1: Write the failing test**

```php
<?php
// Modules/Product/tests/Feature/CatalogServiceListTest.php
namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Product\Models\Product;
use Modules\Product\Services\CatalogService;
use Tests\TestCase;

class CatalogServiceListTest extends TestCase
{
    use RefreshDatabase;

    private function combo(string $name, array $categoryIds = []): Combo
    {
        $c = Combo::create(['name' => $name, 'combo_price' => 100, 'is_active' => true]);
        if ($categoryIds) { $c->categories()->sync($categoryIds); }
        return $c;
    }

    public function test_unfiltered_list_includes_products_and_combos(): void
    {
        Product::factory()->create(['name' => 'Prod X']);
        $this->combo('Combo Y');

        $items = app(CatalogService::class)->list([], 50);
        $names = collect($items->items())->map(fn ($i) => $i->name)->all();

        $this->assertContains('Prod X', $names);
        $this->assertContains('Combo Y', $names);
    }

    public function test_type_filter_limits_to_combos(): void
    {
        Product::factory()->create();
        $this->combo('Only Combo');

        $items = app(CatalogService::class)->list(['type' => 'combo'], 50);
        $this->assertCount(1, $items->items());
        $this->assertEquals('combo', $items->items()[0]->catalog_type);
    }

    public function test_category_filter_includes_subcategory_members(): void
    {
        $parent = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'status' => 'active']);
        $child  = Category::create(['name' => 'Formal', 'slug' => 'formal', 'status' => 'active', 'parent_id' => $parent->id]);

        // Product only in the CHILD category (via category_id).
        $p = Product::factory()->create(['category_id' => $child->id]);
        // Combo assigned to the PARENT category.
        $c = $this->combo('Parent Combo', [$parent->id]);

        $items = app(CatalogService::class)->list(['category' => $parent->id], 50);
        $ids = collect($items->items())->map(fn ($i) => $i->catalog_type.':'.$i->id)->all();

        $this->assertContains('product:'.$p->id, $ids);
        $this->assertContains('combo:'.$c->id, $ids);
    }

    public function test_positioned_items_sort_before_unpositioned(): void
    {
        $cat = Category::create(['name' => 'C', 'slug' => 'c', 'status' => 'active']);
        $a = Product::factory()->create(['category_id' => $cat->id, 'name' => 'A']);
        $b = Product::factory()->create(['category_id' => $cat->id, 'name' => 'B']);
        $combo = $this->combo('Z Combo', [$cat->id]);

        // Put the combo first, product B second; product A left unpositioned.
        app(CatalogService::class)->reorder($cat->id, [
            ['type' => 'combo', 'id' => $combo->id],
            ['type' => 'product', 'id' => $b->id],
        ]);

        $order = collect(app(CatalogService::class)->list(['category' => $cat->id], 50)->items())
            ->map(fn ($i) => $i->catalog_type.':'.$i->id)->all();

        $this->assertEquals('combo:'.$combo->id, $order[0]);
        $this->assertEquals('product:'.$b->id, $order[1]);
        $this->assertEquals('product:'.$a->id, $order[2]); // unpositioned last
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CatalogServiceListTest`
Expected: FAIL (`list()` missing).

- [ ] **Step 3: Implement `list()` and helpers on `CatalogService`**

Add to `Modules/Product/app/Services/CatalogService.php` (add the imports at top):

```php
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Product\Models\Product;
```

```php
public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
{
    $type = $filters['type'] ?? 'all';
    $categoryId = ! empty($filters['category']) ? (int) $filters['category'] : null;
    $subtreeIds = $categoryId ? $this->subtreeIds($categoryId) : null;

    $products = $type === 'combo' ? collect() : $this->products($filters, $subtreeIds);
    $combos   = $type === 'product' ? collect() : $this->combos($filters, $subtreeIds);

    // Attach the active context's position to each item.
    $positions = $this->positionsFor($categoryId, $products, $combos);
    $merged = $products->merge($combos)->map(function ($item) use ($positions) {
        $key = $item->catalog_type.':'.$item->id;
        $item->catalog_position = $positions[$key] ?? null;
        return $item;
    });

    // Positioned first (asc), then unpositioned by created_at desc.
    $sorted = $merged->sort(function ($x, $y) {
        $px = $x->catalog_position; $py = $y->catalog_position;
        if ($px !== null && $py !== null) return $px <=> $py;
        if ($px !== null) return -1;
        if ($py !== null) return 1;
        return $y->created_at <=> $x->created_at;
    })->values();

    $page = LengthAwarePaginator::resolveCurrentPage();
    $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

    return new LengthAwarePaginator(
        $slice, $sorted->count(), $perPage, $page,
        ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
    );
}

/** @return array<int,int> category id + all active descendant ids */
private function subtreeIds(int $categoryId): array
{
    $category = Category::with('recursiveChildren')->find($categoryId);
    if (! $category) return [$categoryId];
    $ids = [$category->id];
    $walk = function ($node) use (&$walk, &$ids) {
        foreach ($node->recursiveChildren ?? [] as $child) {
            $ids[] = $child->id;
            $walk($child);
        }
    };
    $walk($category);
    return array_values(array_unique($ids));
}

private function products(array $filters, ?array $subtreeIds): Collection
{
    $query = Product::with(['category', 'brand', 'unit', 'images'])
        ->withSum('warehouseStock', 'quantity');

    if ($subtreeIds !== null) {
        $query->where(function ($q) use ($subtreeIds) {
            $q->whereIn('category_id', $subtreeIds)
              ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $subtreeIds));
        });
    }
    if (! empty($filters['search'])) $query->search($filters['search']);
    if (! empty($filters['brand']))  $query->byBrand((int) $filters['brand']);
    if (! empty($filters['status'])) $query->where('status', $filters['status']);
    if (! empty($filters['stock'])) {
        match ($filters['stock']) {
            'in_stock' => $query->inStock(),
            'low_stock' => $query->lowStock(),
            'out_of_stock' => $query->outOfStock(),
            default => null,
        };
    }

    return $query->get()->each(fn ($p) => $p->catalog_type = 'product');
}

private function combos(array $filters, ?array $subtreeIds): Collection
{
    // Brand and stock filters do not apply to combos — exclude combos when set.
    if (! empty($filters['brand']) || ! empty($filters['stock'])) {
        return collect();
    }

    $query = Combo::with(['items.product', 'categories']);

    if ($subtreeIds !== null) {
        $query->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $subtreeIds));
    }
    if (! empty($filters['search'])) {
        $term = $filters['search'];
        $query->where('name', 'like', '%'.$term.'%');
    }
    if (! empty($filters['status'])) {
        $query->where('is_active', $filters['status'] === 'active');
    }

    return $query->get()->each(fn ($c) => $c->catalog_type = 'combo');
}

/** @return array<string,int> "type:id" => position for the active context */
private function positionsFor(?int $categoryId, Collection $products, Collection $combos): array
{
    $rows = \Modules\Product\Models\CatalogPosition::query()
        ->when($categoryId === null, fn ($q) => $q->whereNull('category_id'))
        ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
        ->get(['positionable_type', 'positionable_id', 'position']);

    $out = [];
    foreach ($rows as $r) {
        $out[$r->positionable_type.':'.$r->positionable_id] = (int) $r->position;
    }
    return $out;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CatalogServiceListTest`
Expected: PASS (all four tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Product/app/Services/CatalogService.php Modules/Product/tests/Feature/CatalogServiceListTest.php
git commit -m "feat(catalog): merged products+combos list with per-category ordering"
```

---

### Task 4: Relocate admin combo URLs under `/admin/products` + redirect old

**Files:**
- Modify: `Modules/Product/routes/web.php` (add `products.combos.*`)
- Modify: `Modules/Ecommerce/routes/web.php` (301-redirect old combo GET routes; drop admin reorder)
- Modify: `Modules/Ecommerce/app/Http/Controllers/ComboController.php` (redirect targets)
- Modify: `Modules/Ecommerce/resources/views/combos/create.blade.php`, `edit.blade.php` (form `action`, cancel link `route()`)
- Test: `Modules/Product/tests/Feature/AdminComboRelocationTest.php`

**Interfaces:**
- Produces: routes `products.combos.create|store|edit|update|destroy|toggle-status`; old `ecommerce.combos.index|create|edit` return 301 to the new URLs.

- [ ] **Step 1: Write the failing test**

```php
<?php
// Modules/Product/tests/Feature/AdminComboRelocationTest.php
namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Combo;
use Tests\TestCase;

class AdminComboRelocationTest extends TestCase
{
    use RefreshDatabase;

    /** Reuse the project's admin-auth helper. See ComboAdminTest for the exact trait/login used. */
    protected function loginAdmin(): void
    {
        // Follow the existing pattern in Modules/Ecommerce/tests/Feature/ComboAdminTest.php
        // (acting as a super-admin user / permission bypass).
        $this->actingAs($this->superAdminUser());
    }

    public function test_new_combo_create_route_exists(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('products.combos.create'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('products.combos.store'));
    }

    public function test_old_admin_combos_index_redirects_to_products(): void
    {
        $this->loginAdmin();
        $res = $this->get(route('ecommerce.combos.index'));
        $res->assertStatus(301);
        $res->assertRedirect(route('products.index'));
    }
}
```

If `superAdminUser()` is not available in `Tests\TestCase`, copy the auth setup used by `Modules/Ecommerce/tests/Feature/ComboAdminTest.php` verbatim into this test.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminComboRelocationTest`
Expected: FAIL (`products.combos.create` not defined).

- [ ] **Step 3: Add the new routes**

In `Modules/Product/routes/web.php`, inside the same admin `products` group (mirror the existing prefix/name/middleware the product routes use):

```php
use Modules\Ecommerce\Http\Controllers\ComboController as AdminComboController;

Route::get('products/combos/create', [AdminComboController::class, 'create'])->name('products.combos.create');
Route::post('products/combos', [AdminComboController::class, 'store'])->name('products.combos.store');
Route::get('products/combos/{combo}/edit', [AdminComboController::class, 'edit'])->name('products.combos.edit');
Route::put('products/combos/{combo}', [AdminComboController::class, 'update'])->name('products.combos.update');
Route::delete('products/combos/{combo}', [AdminComboController::class, 'destroy'])->name('products.combos.destroy');
Route::patch('products/combos/{combo}/toggle-status', [AdminComboController::class, 'toggleStatus'])->name('products.combos.toggle-status');
```

- [ ] **Step 4: Convert old admin combo GET routes to 301 redirects**

In `Modules/Ecommerce/routes/web.php`, replace the combo index/create/edit GET route definitions (lines ~154-162) with redirects, and remove the admin `combos.reorder`/`combos.store`/`combos.update`/`combos.destroy` admin routes (now served under products). Keep the route NAMES for index/create/edit so existing `route()` calls resolve:

```php
Route::get('/combos', fn () => redirect()->route('products.index', [], 301))->name('combos.index');
Route::get('/combos/create', fn () => redirect()->route('products.combos.create', [], 301))->name('combos.create');
Route::get('/combos/{combo}/edit', fn ($combo) => redirect()->route('products.combos.edit', $combo, 301))->name('combos.edit');
```

- [ ] **Step 5: Update `ComboController` redirect targets and view routes**

In `Modules/Ecommerce/app/Http/Controllers/ComboController.php`, change every `redirect()->route('ecommerce.combos.index')` (in `store`, `update`, `destroy`) to `redirect()->route('products.index')`, and any `route('ecommerce.combos.*')` used for form building to the `products.combos.*` equivalents.

In `Modules/Ecommerce/resources/views/combos/create.blade.php`:
- Form action `route('ecommerce.combos.store')` → `route('products.combos.store')`.
- Cancel link / back link `route('ecommerce.combos.index')` → `route('products.index')`.

In `Modules/Ecommerce/resources/views/combos/edit.blade.php`:
- Form action `route('ecommerce.combos.update', $combo)` → `route('products.combos.update', $combo)`.
- Cancel link → `route('products.index')`.

(Search both files for `ecommerce.combos.` and replace each occurrence with the correct `products.combos.*` / `products.index` target.)

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=AdminComboRelocationTest`
Expected: PASS. Then manually: `php artisan route:list --name=products.combos` shows the six routes.

- [ ] **Step 7: Commit**

```bash
git add Modules/Product/routes/web.php Modules/Ecommerce/routes/web.php \
  Modules/Ecommerce/app/Http/Controllers/ComboController.php \
  Modules/Ecommerce/resources/views/combos/create.blade.php \
  Modules/Ecommerce/resources/views/combos/edit.blade.php \
  Modules/Product/tests/Feature/AdminComboRelocationTest.php
git commit -m "feat(catalog): relocate admin combo URLs under /admin/products with 301 redirects"
```

---

### Task 5: Unified admin reorder endpoint

**Files:**
- Modify: `Modules/Product/routes/web.php` (add `products.catalog.reorder`)
- Modify: `Modules/Product/app/Http/Controllers/ProductController.php` (add `reorderCatalog`)
- Test: extend `Modules/Product/tests/Feature/CatalogReorderTest.php` with an HTTP test

**Interfaces:**
- Consumes: `CatalogService::reorder` (Task 2).
- Produces: `POST products/catalog/reorder` named `products.catalog.reorder`; JSON body `{category_id: int|null, items: [{type,id}]}`; returns `{success:true}`.

- [ ] **Step 1: Write the failing test** (append to `CatalogReorderTest`)

```php
public function test_reorder_endpoint_persists_positions(): void
{
    $this->actingAs($this->superAdminUser()); // reuse project admin-auth helper
    $c1 = $this->combo('One'); $c2 = $this->combo('Two');

    $res = $this->postJson(route('products.catalog.reorder'), [
        'category_id' => null,
        'items' => [
            ['type' => 'combo', 'id' => $c2->id],
            ['type' => 'combo', 'id' => $c1->id],
        ],
    ]);

    $res->assertOk()->assertJson(['success' => true]);
    $this->assertEquals(1, \Modules\Product\Models\CatalogPosition::whereNull('category_id')->where('positionable_id',$c2->id)->value('position'));
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CatalogReorderTest::test_reorder_endpoint_persists_positions`
Expected: FAIL (route missing).

- [ ] **Step 3: Add the route**

In `Modules/Product/routes/web.php` (same admin group):

```php
Route::post('products/catalog/reorder', [\Modules\Product\Http\Controllers\ProductController::class, 'reorderCatalog'])
    ->name('products.catalog.reorder');
```

- [ ] **Step 4: Add the controller method**

In `Modules/Product/app/Http/Controllers/ProductController.php` (inject or resolve `CatalogService`; add `use Illuminate\Http\JsonResponse;`):

```php
public function reorderCatalog(\Illuminate\Http\Request $request, \Modules\Product\Services\CatalogService $catalog): JsonResponse
{
    bpAuthorize('products.edit');
    $validated = $request->validate([
        'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
        'items'         => ['required', 'array'],
        'items.*.type'  => ['required', 'in:product,combo'],
        'items.*.id'    => ['required', 'integer', 'min:1'],
    ]);

    $catalog->reorder($validated['category_id'] ?? null, $validated['items']);

    return response()->json(['success' => true]);
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=CatalogReorderTest`
Expected: PASS (all reorder tests, including the endpoint test).

- [ ] **Step 6: Commit**

```bash
git add Modules/Product/routes/web.php Modules/Product/app/Http/Controllers/ProductController.php \
  Modules/Product/tests/Feature/CatalogReorderTest.php
git commit -m "feat(catalog): unified per-context reorder endpoint"
```

---

### Task 6: Admin list — controller delegation + type filter (backend)

**Files:**
- Modify: `Modules/Product/app/Http/Controllers/ProductController.php` (`index`)
- Test: `Modules/Product/tests/Feature/CatalogListViewTest.php`

**Interfaces:**
- Consumes: `CatalogService::list` (Task 3).
- Produces: `products.index` view receives a `$items` paginator (products + combos) plus existing `$categories`, `$brands`, `$stats`; reads `type` from the request.

- [ ] **Step 1: Write the failing test**

```php
<?php
// Modules/Product/tests/Feature/CatalogListViewTest.php
namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Combo;
use Modules\Product\Models\Product;
use Tests\TestCase;

class CatalogListViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_lists_a_combo_row(): void
    {
        $this->actingAs($this->superAdminUser()); // reuse project admin-auth helper
        Product::factory()->create(['name' => 'Visible Product']);
        Combo::create(['name' => 'Visible Combo', 'combo_price' => 100, 'is_active' => true]);

        $res = $this->get(route('products.index'));
        $res->assertOk();
        $res->assertSee('Visible Product');
        $res->assertSee('Visible Combo');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CatalogListViewTest`
Expected: FAIL (`Visible Combo` not present — index only shows products).

- [ ] **Step 3: Update `ProductController::index`**

Replace the `$this->service->list(...)` call so it uses `CatalogService`, keeping stats product-based. Example (adapt to the existing method signature/vars):

```php
public function index(\Illuminate\Http\Request $request, \Modules\Product\Services\CatalogService $catalog): \Illuminate\View\View
{
    bpAuthorize('products.view');

    $filters = $request->only(['search', 'category', 'brand', 'status', 'stock', 'type']);
    $items = $catalog->list($filters, 15);

    $categories = \Modules\Category\Models\Category::active()->ordered()->get();
    $brands = \Modules\Brand\Models\Brand::query()->orderBy('name')->get();
    $stats = $this->service->stats(); // keep existing product stats call

    return view('product::index', compact('items', 'categories', 'brands', 'stats', 'filters'));
}
```

If the existing view variable was `$products`, either rename to `$items` here AND in the view (Task 7), or pass `compact('items')` and update the view loop in Task 7. Keep the exact `Brand`/stats calls the current controller already uses.

- [ ] **Step 4: Run test to verify it passes** (after Task 7 renders combo rows)

The assertion `assertSee('Visible Combo')` fully passes only once the view (Task 7) renders combo rows. If splitting execution, mark this step complete when the controller passes `$items`; the see-combo assertion is re-verified at the end of Task 7.

Run: `php artisan test --filter=CatalogListViewTest`
Expected: PASS after Task 7 (combo row rendered).

- [ ] **Step 5: Commit**

```bash
git add Modules/Product/app/Http/Controllers/ProductController.php Modules/Product/tests/Feature/CatalogListViewTest.php
git commit -m "feat(catalog): products index delegates to CatalogService (products+combos)"
```

---

### Task 7: Admin list — Blade rendering, add-dropdown, type filter UI, sidebar, reorder JS

**Files:**
- Modify: `Modules/Product/resources/views/index.blade.php`
- Modify: `Modules/Core/resources/views/partials/sidebar.blade.php`
- Modify: `public/js/app.js`
- Modify: `public/css/style.css` (badge/split-button styles + dark overrides, if new)

**Interfaces:**
- Consumes: `$items` paginator from Task 6; `products.combos.create`, `products.catalog.reorder`, combo action routes.

- [ ] **Step 1: Split the "Add Product" button into a dropdown**

In `index.blade.php` page-actions, replace the single add link with (Bootstrap dropdown; no modal):

```blade
<div class="dropdown">
  <button class="bp-btn bp-btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fa-solid fa-plus me-1"></i> Add New
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <li><a class="dropdown-item" href="{{ route('products.create') }}"><i class="fa-solid fa-box me-2"></i>Create Product</a></li>
    <li><a class="dropdown-item" href="{{ route('products.combos.create') }}"><i class="fa-solid fa-layer-group me-2"></i>Create Combo</a></li>
  </ul>
</div>
```

- [ ] **Step 2: Add the Type filter to the filter bar**

Add next to the existing selects (name it `type`, preserving current value):

```blade
<select name="type" class="bp-form-select">
  <option value="">All Types</option>
  <option value="product" @selected(($filters['type'] ?? '')==='product')>Products</option>
  <option value="combo" @selected(($filters['type'] ?? '')==='combo')>Combos</option>
</select>
```

- [ ] **Step 3: Render product vs combo rows**

Change the table loop to iterate `$items` and branch on `catalog_type`. Add `data-type` to every row and use it for the reorder payload. Combo row example (keep the existing product row markup for the product branch):

```blade
@foreach ($items as $item)
  @if ($item->catalog_type === 'combo')
    <tr data-id="{{ $item->id }}" data-type="combo">
      <td><input type="checkbox" class="form-check-input row-checkbox"></td>
      <td class="bp-drag-handle"><i class="fa-solid fa-grip-vertical"></i></td>
      <td>
        @php $thumb = $item->thumbnail ? upload_url($item->thumbnail) : asset('website/assets/images/product_placeholder.png'); @endphp
        <img src="{{ $thumb }}" alt="{{ $item->name }}" class="bp-thumb">
      </td>
      <td>
        <a href="{{ route('products.combos.edit', $item) }}">{{ $item->name }}</a>
        <span class="bp-badge bp-badge-secondary ms-1">Combo</span>
      </td>
      <td>{{ $item->items->count() }} items</td>
      <td>@foreach ($item->categories as $c)<span class="bp-badge bp-badge-primary">{{ $c->name }}</span>@endforeach</td>
      <td>—</td>
      <td>—</td>
      <td>{{ currency_symbol() }} {{ number_format((float) $item->combo_price, 2) }}</td>
      <td>{{ app(\Modules\Ecommerce\Services\ComboService::class)->availableStock($item) ?? '∞' }}</td>
      <td>
        <label class="form-check form-switch mb-0">
          <input class="form-check-input bp-status-toggle" type="checkbox" data-url="{{ route('products.combos.toggle-status', $item) }}" @checked($item->is_active)>
        </label>
      </td>
      <td>
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i> Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('products.combos.edit', $item) }}"><i class="fa-solid fa-pen me-2"></i>Edit</a></li>
            <li>
              <form action="{{ route('products.combos.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this combo?');">
                @csrf @method('DELETE')
                <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash me-2"></i>Delete</button>
              </form>
            </li>
          </ul>
        </div>
      </td>
    </tr>
  @else
    {{-- existing product row markup, with data-type="product" added to the <tr> --}}
  @endif
@endforeach
```

Ensure `<tbody class="bp-reorderable" data-reorder-url="{{ route('products.catalog.reorder') }}">` (swap the old `products.reorder` url). Add `data-category-select` to the category filter `<select>` so JS can read the active category.

- [ ] **Step 4: Update sidebar**

In `Modules/Core/resources/views/partials/sidebar.blade.php`:
- Remove the "Combo Packages" `<li>` (the `ecommerce.combos.index` link).
- Add `products.combos.*` to the Products submenu `request()->routeIs(...)` active check; drop `ecommerce.combos.*` from it.

- [ ] **Step 5: Extend reorder JS to send `{type,id}` + category**

In `public/js/app.js` reorder `persist()` (the flat reorder path), build the payload from `data-type`/`data-id` and include the active category:

```javascript
'use strict';
// inside persist(): replace the ordered_ids collection
var items = [];
$tbody.find('tr[data-id]').each(function () {
  items.push({ type: $(this).data('type') || 'product', id: $(this).data('id') });
});
var categoryId = $('[data-category-select]').val() || null;
$.ajax({
  url: $tbody.data('reorder-url'),
  method: 'POST',
  data: { category_id: categoryId, items: items },
  // CSRF header already set globally via $.ajaxSetup in the layout
});
```

(Adapt variable names to the existing handler; keep the legacy `ordered_ids` path only if another table still uses it.)

- [ ] **Step 6: Add CSS if introduced**

If the split button or combo badge needs styling, append `bp-` rules to `public/css/style.css` with a `[data-theme="dark"]` override. Reuse existing `bp-badge-secondary`/`bp-thumb` if present (no new CSS needed in that case).

- [ ] **Step 7: Run the view test**

Run: `php artisan test --filter=CatalogListViewTest`
Expected: PASS (`assertSee('Visible Combo')`). Then manual QA: `php artisan serve`, log in, open `/admin/products`, confirm combos appear with a badge, the Add dropdown shows both options, pick a category and drag a combo above a product, reload — order persists.

- [ ] **Step 8: Commit**

```bash
git add Modules/Product/resources/views/index.blade.php Modules/Core/resources/views/partials/sidebar.blade.php public/js/app.js public/css/style.css
git commit -m "feat(catalog): render combos in products list, add-dropdown, type filter, interleaved reorder"
```

---

### Task 8: Storefront — interleaved per-category ordering

**Files:**
- Modify: `Modules/Ecommerce/app/Services/StorefrontService.php`
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/ShopController.php`
- Modify: `Modules/Ecommerce/resources/views/storefront/pages/shop/index.blade.php` (render interleaved)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontCatalogOrderTest.php`

**Interfaces:**
- Consumes: `catalog_positions` for the viewing category (or `NULL` global); `Product::storefrontVisible()`, `Combo::active()`.
- Produces: `StorefrontService::getCatalogListing(array $filters, int $perPage): LengthAwarePaginator` returning interleaved products + combos ordered by the context's positions (unpositioned appended by `created_at`).

- [ ] **Step 1: Write the failing test**

```php
<?php
// Modules/Ecommerce/tests/Feature/StorefrontCatalogOrderTest.php
namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Product\Models\Product;
use Modules\Product\Services\CatalogService;
use Modules\Ecommerce\Services\StorefrontService;
use Tests\TestCase;

class StorefrontCatalogOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_listing_matches_admin_defined_order(): void
    {
        $cat = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'status' => 'active']);
        $p = Product::factory()->create(['category_id' => $cat->id, 'status' => 'active']);
        $c = Combo::create(['name' => 'Combo', 'combo_price' => 100, 'is_active' => true]);
        $c->categories()->sync([$cat->id]);

        // Admin orders the combo FIRST, then the product.
        app(CatalogService::class)->reorder($cat->id, [
            ['type' => 'combo', 'id' => $c->id],
            ['type' => 'product', 'id' => $p->id],
        ]);

        $items = app(StorefrontService::class)->getCatalogListing(['category_slug' => 'shirts'], 50);
        $order = collect($items->items())->map(fn ($i) => ($i instanceof Combo ? 'combo:' : 'product:').$i->id)->all();

        $this->assertEquals('combo:'.$c->id, $order[0]);
        $this->assertEquals('product:'.$p->id, $order[1]);
    }
}
```

Adjust `Product::factory()` visibility fields so `storefrontVisible()` returns it (set whatever `status`/visibility flags the scope requires — check the `storefrontVisible` scope).

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=StorefrontCatalogOrderTest`
Expected: FAIL (`getCatalogListing` missing).

- [ ] **Step 3: Implement `getCatalogListing` on `StorefrontService`**

```php
public function getCatalogListing(array $filters, int $perPage = 12): \Illuminate\Pagination\LengthAwarePaginator
{
    $categoryId = null;
    $subtreeIds = null;
    if (! empty($filters['category_slug'])) {
        $category = \Modules\Category\Models\Category::where('slug', $filters['category_slug'])->first();
        if ($category) {
            $categoryId = $category->id;
            $subtreeIds = $this->getCategoryAndChildrenIds($category);
        }
    }

    $products = Product::storefrontVisible()->with(['images', 'category', 'brand', 'approvedReviews'])
        ->when($subtreeIds, fn ($q) => $q->whereIn('category_id', $subtreeIds))
        ->get()->each(fn ($p) => $p->catalog_type = 'product');

    $combos = \Modules\Ecommerce\Models\Combo::active()->with(['items.product.images', 'items.variant'])
        ->when($subtreeIds, fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $subtreeIds)))
        ->get()->each(fn ($c) => $c->catalog_type = 'combo');

    $rows = \Modules\Product\Models\CatalogPosition::query()
        ->when($categoryId === null, fn ($q) => $q->whereNull('category_id'))
        ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
        ->get(['positionable_type', 'positionable_id', 'position']);
    $pos = [];
    foreach ($rows as $r) { $pos[$r->positionable_type.':'.$r->positionable_id] = (int) $r->position; }

    $merged = $products->merge($combos)->sort(function ($x, $y) use ($pos) {
        $px = $pos[$x->catalog_type.':'.$x->id] ?? null;
        $py = $pos[$y->catalog_type.':'.$y->id] ?? null;
        if ($px !== null && $py !== null) return $px <=> $py;
        if ($px !== null) return -1;
        if ($py !== null) return 1;
        return $y->created_at <=> $x->created_at;
    })->values();

    $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
    $slice = $merged->slice(($page - 1) * $perPage, $perPage)->values();
    return new \Illuminate\Pagination\LengthAwarePaginator(
        $slice, $merged->count(), $perPage, $page,
        ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
    );
}
```

- [ ] **Step 4: Wire `ShopController::index` and the view**

In `ShopController::index`, replace the separate `getShopProducts` + `$categoryCombos` logic with `$items = $this->service->getCatalogListing($filters, $perPage);` and pass `$items` (plus `$comboService`) to the view. Remove the now-dead `$categoryCombos` block.

In `storefront/pages/shop/index.blade.php`, iterate `$items` and branch on type, rendering `product-card` or `combo-card` (drop the separate "combos appended" block at lines ~215-226):

```blade
@foreach ($items as $item)
  <div class="col-xxl-3 col-6 col-md-4 col-lg-6 col-xl-4">
    @if ($item->catalog_type === 'combo')
      @include('ecommerce::storefront.partials.combo-card', ['combo' => $item, 'service' => $comboService])
    @else
      @include('ecommerce::storefront.partials.product-card', ['product' => $item])
    @endif
  </div>
@endforeach
```

Keep pagination links using `$items->links()` / the storefront's existing pagination partial.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=StorefrontCatalogOrderTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/app/Services/StorefrontService.php \
  Modules/Ecommerce/app/Http/Controllers/Storefront/ShopController.php \
  Modules/Ecommerce/resources/views/storefront/pages/shop/index.blade.php \
  Modules/Ecommerce/tests/Feature/StorefrontCatalogOrderTest.php
git commit -m "feat(catalog): storefront renders products+combos in admin-defined per-category order"
```

---

### Task 9: Full-suite verification and manual QA sweep

**Files:** none (verification only).

- [ ] **Step 1: Run the full affected test suites**

Run: `php artisan test --filter=Catalog` then `php artisan test Modules/Product/tests Modules/Ecommerce/tests`
Expected: all PASS. Fix any regression before proceeding (especially existing combo tests referencing old routes).

- [ ] **Step 2: Route + smoke QA (per CLAUDE.md §20)**

```bash
php artisan serve --host=127.0.0.1 --port=8000 &
# login, then:
curl -s -b /tmp/bz.txt "http://127.0.0.1:8000/admin/products" -o /dev/null -w "%{http_code}\n"          # 200, shows combos
curl -s -b /tmp/bz.txt "http://127.0.0.1:8000/admin/products/combos/create" -o /dev/null -w "%{http_code}\n" # 200
curl -s -b /tmp/bz.txt "http://127.0.0.1:8000/admin/ecommerce/combos" -o /dev/null -w "%{http_code}\n"     # 301
```

- [ ] **Step 3: Manual interleave check**

Log in, open `/admin/products`, filter by a category that has both products and combos, drag a combo between products, reload → order persists. Open the same category on the storefront → identical order. Switch to a second category the same combo belongs to and give it a different order → both persist independently.

- [ ] **Step 4: Final commit (if any fixes)**

```bash
git add -A
git commit -m "test(catalog): verification fixes for unified products+combos catalog"
```

---

## Self-Review Notes

- **Spec coverage:** table+morphMap (T1), reorder service (T2) + endpoint (T5), merged list (T3) + controller (T6) + view/UI/sidebar/JS (T7), admin URL relocation + redirects (T4), storefront interleaving (T8), tests throughout + sweep (T9). All spec sections mapped.
- **Global bucket uniqueness:** enforced via `updateOrCreate` (T2), matching the spec caveat.
- **Type/name consistency:** `catalog_type`/`catalog_position` transient attrs, `reorder(?int,$items[])`, `list($filters,$perPage)`, `getCatalogListing($filters,$perPage)`, routes `products.combos.*` / `products.catalog.reorder` used consistently across tasks.
- **Known adaptation points (not placeholders):** the project's admin-auth test helper (`superAdminUser()`), the exact existing product-row markup in `index.blade.php`, the `storefrontVisible` scope's required fields, and the existing reorder JS variable names — each task says to reuse the established pattern rather than inventing one.
