# Storefront Data-Layer Caching Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the storefront home, shop, product-detail, blog, and flash-deals pages load fast by caching their expensive, filter-independent query results, and automatically invalidate that cache whenever catalog or blog content changes.

**Architecture:** Data-layer caching only (no full-page HTML caching — per-user cart/login/wishlist keeps rendering live per request). Cache the read-only query assembly inside `StorefrontService`, `ContentManagementService`, and `BlogService`. Because the cache driver is `database` (no `Cache::tags()` support), invalidation uses **version counters**: each cache key embeds an integer version for its namespace (`catalog` or `blog`); bumping the counter makes all old keys unreachable and they expire by TTL. Time-bound flash-deal/campaign price **decoration always runs after the cache fetch, never inside it**, so prices stay correct as deals start and end.

**Tech Stack:** Laravel 12, `Illuminate\Support\Facades\Cache`, Eloquent model observers, PHPUnit feature tests (`Tests\TestCase`), nwidart/laravel-modules.

---

## Background Facts (verified against the codebase)

- Cache driver: `config/cache.php` → `env('CACHE_STORE', 'database')`. **No tag support.**
- Storefront data assembly: `Modules/Ecommerce/app/Services/StorefrontService.php`.
- CMS lookups: `Modules/Ecommerce/app/Services/ContentManagementService.php`
  - `getActiveBannersByPosition(string $position): Collection` (line 50)
  - `getActiveFlashDeal(): ?FlashDeal` (line 264)
  - `getPublishedPosts(int $limit = 6): Collection` (line 381)
  - `getHomepagePosts(int $limit = 4): Collection` (line 395)
- Blog sidebar: `Modules/Ecommerce/app/Services/BlogService.php`
  - `categoriesWithCounts(): Collection` (line 17)
  - `popularTags(int $limit = 12): Collection` (line 31)
  - `popularPosts(int $limit = 3, ?int $excludeId = null): Collection` (line 49)
- Controllers (no changes needed except where noted): `HomeController`, `ShopController` (`index`, `flashDeals`, `show`), `BlogController`.
- Model FQCNs for observers:
  - `Modules\Product\Models\Product`
  - `Modules\Product\Models\ProductImage`
  - `Modules\Product\Models\Tag`
  - `Modules\Variant\Models\ProductVariant`
  - `Modules\Variant\Models\VariantAttribute`
  - `Modules\Variant\Models\VariantAttributeValue`
  - `Modules\Category\Models\Category`
  - `Modules\Brand\Models\Brand`
  - `Modules\Ecommerce\Models\FlashDeal`
  - `Modules\Ecommerce\Models\HomepageSection`
  - `Modules\Ecommerce\Models\Banner`
  - `Modules\Ecommerce\Models\Campaign`
  - `Modules\Ecommerce\Models\ProductReview`
  - `Modules\Ecommerce\Models\BlogPost` (blog namespace)
  - `Modules\Ecommerce\Models\BlogComment` (blog namespace)
- Pivot-sync sites that change storefront output WITHOUT saving a watched parent row (need explicit bumps):
  - `ContentManagementService.php:140` — homepage section `products()->sync()`
  - `ContentManagementService.php:177` — collection `products()->sync()`
  - `ContentManagementService.php:261` — flash deal `products()->sync()`
  - `CampaignController.php:60` and `:93` — campaign `products()->sync()`
- Service provider boot: `Modules/Ecommerce/app/Providers/EcommerceServiceProvider.php::boot()`.
- Existing manual cache clear: `Modules/Setting/app/Http/Controllers/SettingController.php::clearCache()` + Settings → System Maintenance view section (already added) at `Modules/Setting/resources/views/index.blade.php`.
- Test convention: `namespace Modules\Ecommerce\Tests\Feature;`, `extends Tests\TestCase`, helper `actingAsAdmin()` exists. Tests run with the array cache driver by default, so `Cache::increment` and version persistence work in-test without the DB cache table.

---

## File Structure

**Create:**
- `Modules/Ecommerce/app/Support/StorefrontCache.php` — the version-counter cache helper (the one place that knows how keys/versions are built).
- `Modules/Ecommerce/app/Observers/CatalogCacheObserver.php` — bumps the `catalog` version on any watched catalog model change.
- `Modules/Ecommerce/app/Observers/BlogCacheObserver.php` — bumps the `blog` version on blog model changes.
- `Modules/Ecommerce/tests/Feature/StorefrontCacheTest.php` — unit-style tests for the helper.
- `Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php` — observer + service caching behaviour tests.

**Modify:**
- `Modules/Ecommerce/app/Services/StorefrontService.php` — wrap shared/expensive read methods; split homepage assembly so decoration runs post-cache.
- `Modules/Ecommerce/app/Services/ContentManagementService.php` — bump `catalog` after the three pivot syncs.
- `Modules/Ecommerce/app/Http/Controllers/CampaignController.php` — bump `catalog` after campaign product sync.
- `Modules/Ecommerce/app/Providers/EcommerceServiceProvider.php` — register observers in `boot()`.
- `Modules/Setting/app/Http/Controllers/SettingController.php` — flush storefront versions in `clearCache()` and add a dedicated `clearStorefrontCache()` action.
- `Modules/Setting/routes/web.php` — route for `clearStorefrontCache`.
- `Modules/Setting/resources/views/index.blade.php` — "Clear Storefront Cache" button in the System Maintenance section.

---

## Task 1: The `StorefrontCache` helper

**Files:**
- Create: `Modules/Ecommerce/app/Support/StorefrontCache.php`
- Test: `Modules/Ecommerce/tests/Feature/StorefrontCacheTest.php`

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Feature/StorefrontCacheTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Support\StorefrontCache;
use Tests\TestCase;

class StorefrontCacheTest extends TestCase
{
    public function test_version_starts_at_one(): void
    {
        $this->assertSame(1, StorefrontCache::version('catalog'));
    }

    public function test_bump_increments_version(): void
    {
        $this->assertSame(1, StorefrontCache::version('catalog'));
        StorefrontCache::bump('catalog');
        $this->assertSame(2, StorefrontCache::version('catalog'));
    }

    public function test_remember_caches_callback_result(): void
    {
        $calls = 0;
        $make = fn () => StorefrontCache::remember('catalog', 'thing', 3600, function () use (&$calls) {
            $calls++;
            return 'value-' . $calls;
        });

        $this->assertSame('value-1', $make());
        $this->assertSame('value-1', $make()); // served from cache, callback not re-run
        $this->assertSame(1, $calls);
    }

    public function test_bump_invalidates_remembered_value(): void
    {
        $calls = 0;
        $make = fn () => StorefrontCache::remember('catalog', 'thing', 3600, function () use (&$calls) {
            $calls++;
            return 'value-' . $calls;
        });

        $this->assertSame('value-1', $make());
        StorefrontCache::bump('catalog');           // new version -> new key
        $this->assertSame('value-2', $make());
        $this->assertSame(2, $calls);
    }

    public function test_composite_key_changes_when_either_namespace_bumps(): void
    {
        $calls = 0;
        $make = fn () => StorefrontCache::remember(['catalog', 'blog'], 'home', 3600, function () use (&$calls) {
            $calls++;
            return $calls;
        });

        $this->assertSame(1, $make());
        StorefrontCache::bump('blog');
        $this->assertSame(2, $make());
        StorefrontCache::bump('catalog');
        $this->assertSame(3, $make());
    }

    public function test_flush_all_bumps_both_namespaces(): void
    {
        $this->assertSame(1, StorefrontCache::version('catalog'));
        $this->assertSame(1, StorefrontCache::version('blog'));
        StorefrontCache::flushAll();
        $this->assertSame(2, StorefrontCache::version('catalog'));
        $this->assertSame(2, StorefrontCache::version('blog'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=StorefrontCacheTest`
Expected: FAIL — `Class "Modules\Ecommerce\Support\StorefrontCache" not found`.

- [ ] **Step 3: Write minimal implementation**

Create `Modules/Ecommerce/app/Support/StorefrontCache.php`:

```php
<?php

namespace Modules\Ecommerce\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Version-counter cache for the storefront. The DB cache driver has no tag
 * support, so instead of tagging keys we embed an integer version per
 * namespace into every key. Bumping the version makes all previously-stored
 * keys unreachable (they expire on their own TTL), giving O(1) invalidation
 * without tracking individual keys.
 *
 * Namespaces:
 *   - 'catalog' : products, variants, images, categories, brands, flash deals,
 *                 campaigns, homepage sections, banners, reviews, tags, variant
 *                 attributes/values.
 *   - 'blog'    : blog posts and comments.
 */
class StorefrontCache
{
    /** Cache key prefix for the per-namespace version counters. */
    private const VERSION_PREFIX = 'sf:ver:';

    /** All namespaces this helper manages (used by flushAll). */
    private const NAMESPACES = ['catalog', 'blog'];

    /**
     * Current integer version for a namespace. Initialised to 1 on first read.
     */
    public static function version(string $namespace): int
    {
        $key = self::VERSION_PREFIX . $namespace;
        $value = Cache::get($key);

        if ($value === null) {
            Cache::forever($key, 1);
            return 1;
        }

        return (int) $value;
    }

    /**
     * Invalidate everything in a namespace by advancing its version counter.
     */
    public static function bump(string $namespace): void
    {
        $key = self::VERSION_PREFIX . $namespace;

        if (Cache::get($key) === null) {
            // Never read before: jump straight to 2 so any value that was
            // remembered under the implicit v1 is invalidated.
            Cache::forever($key, 2);
            return;
        }

        Cache::increment($key);
    }

    /**
     * Remember a value under one or more namespaces. The cache key embeds the
     * current version of every namespace supplied, so a bump to ANY of them
     * produces a fresh key. Pass an array when a value depends on more than one
     * namespace (e.g. the homepage uses both 'catalog' and 'blog').
     *
     * @param  string|array<int, string>  $namespaces
     */
    public static function remember(string|array $namespaces, string $key, int $ttl, Closure $callback): mixed
    {
        $list = (array) $namespaces;

        $versionPart = implode('.', array_map(
            fn (string $ns) => $ns[0] . self::version($ns), // e.g. c12.b3
            $list
        ));

        $cacheKey = 'sf:' . implode('-', $list) . ':' . $versionPart . ':' . $key;

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Bump every managed namespace — used by the admin "Clear Storefront
     * Cache" action and the global cache clear.
     */
    public static function flushAll(): void
    {
        foreach (self::NAMESPACES as $namespace) {
            self::bump($namespace);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=StorefrontCacheTest`
Expected: PASS (6 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Support/StorefrontCache.php Modules/Ecommerce/tests/Feature/StorefrontCacheTest.php
git commit -m "feat(ecommerce): add StorefrontCache version-counter helper"
```

---

## Task 2: Cache the homepage assembly (split decoration out)

The homepage is the highest-traffic page and its data is filter-independent, so we cache the whole raw assembly. Currently `getHomepageData()` mixes raw queries with time-bound decoration (lines 81–92). We split it: cache the raw assembly, decorate after retrieval.

**Files:**
- Modify: `Modules/Ecommerce/app/Services/StorefrontService.php` (the `getHomepageData()` method, lines 31–95)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php`

- [ ] **Step 1: Write the failing test**

Create `Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php`:

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Ecommerce\Support\StorefrontCache;
use Modules\Product\Models\Product;
use Tests\TestCase;

class StorefrontCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_data_is_served_from_cache_on_second_call(): void
    {
        $service = app(StorefrontService::class);

        $service->getHomepageData(); // warm

        DB::enableQueryLog();
        $service->getHomepageData(); // should hit cache
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Only the version-counter lookups (if any) should run, not the
        // catalogue assembly queries. Allow a small ceiling for the version
        // reads; assert it is dramatically lower than a cold assembly.
        $this->assertLessThan(5, count($queries));
    }

    public function test_bumping_catalog_rebuilds_homepage_data(): void
    {
        $service = app(StorefrontService::class);
        $service->getHomepageData(); // warm

        StorefrontCache::bump('catalog');

        DB::enableQueryLog();
        $service->getHomepageData();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertGreaterThan(5, count($queries)); // re-assembled from DB
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=StorefrontCacheInvalidationTest`
Expected: FAIL — second call still runs the full assembly (query count not reduced).

- [ ] **Step 3: Refactor `getHomepageData()` to cache the raw assembly**

In `Modules/Ecommerce/app/Services/StorefrontService.php`, add the `StorefrontCache` import at the top with the other `use` statements:

```php
use Modules\Ecommerce\Support\StorefrontCache;
```

Replace the entire `getHomepageData()` method (currently lines 31–95) with:

```php
    public function getHomepageData(): array
    {
        // Raw, filter-independent assembly is cached. Time-bound campaign and
        // flash-deal decoration runs AFTER retrieval so prices stay correct as
        // deals start/end without invalidating the cache.
        $data = StorefrontCache::remember(['catalog', 'blog'], 'home', 3600, function () {
            return $this->assembleHomepageData();
        });

        // Decorate every product collection we just loaded so views can render
        // campaign + flash-deal pricing. Order matters: campaigns first so
        // their decoration is in place, then decorateFlashDeals can compare and
        // beat them where cheaper.
        foreach (['newArrivals', 'bestSelling', 'trendingProducts', 'specialProducts', 'favouriteProducts'] as $key) {
            if (!empty($data[$key])) {
                $this->campaigns->decorate($data[$key]);
                $this->decorateFlashDeals($data[$key]);
            }
        }

        // The home flash-deals partial also receives the deal's products
        // collection — decorate those too so the cards show pivot prices.
        if (! empty($data['flashDeal']?->products)) {
            $this->decorateFlashDeals($data['flashDeal']->products);
        }

        return $data;
    }

    /**
     * Build the raw homepage data (no time-bound decoration). Cached by
     * getHomepageData(); invalidated via the 'catalog'/'blog' version counters.
     *
     * NOTE: trending/special-brand use inRandomOrder(); caching freezes the
     * random pick for the cache lifetime (a perf win, acceptable for the home
     * page).
     */
    private function assembleHomepageData(): array
    {
        $cms = app(ContentManagementService::class);

        $sections = HomepageSection::active()->ordered()->get();
        $data = ['sections' => $sections];

        foreach ($sections as $section) {
            $limit = $section->getSetting('items_count', 12);

            match ($section->section_type) {
                'hero_slider'    => $data['heroBanners'] = $cms->getActiveBannersByPosition('hero'),
                'features'       => null,
                'flash_deals'    => $data['flashDeal'] = $cms->getActiveFlashDeal(),
                'categories'     => $data['topCategories'] = $this->getTopCategories($limit),
                'promo_banners'  => $data['promoBanners'] = $cms->getActiveBannersByPosition('promo_large'),
                'new_arrivals'   => $data['newArrivals'] = $this->curatedSectionProducts($section) ?? $this->getNewArrivals($limit),
                'best_selling'   => $data['bestSelling'] = $this->curatedSectionProducts($section) ?? $this->getBestSellingProducts($limit),
                'brands'         => $data['brands'] = $this->getActiveBrands($limit),
                'blog'           => $data['blogPosts'] = $cms->getHomepagePosts($limit),
                'trending'       => $data['trendingProducts'] = $this->curatedSectionProducts($section) ?? $this->getTrendingProducts($limit),
                'special_brand'  => $data['specialProducts'] = $this->curatedSectionProducts($section) ?? $this->getSpecialBrandProducts($limit),
                'favourite'      => $data['favouriteProducts'] = $this->curatedSectionProducts($section) ?? $this->getFavouriteProducts($limit),
                'newsletter'     => null,
                default          => null,
            };
        }

        // Fallback data when no sections are configured. Mirrors every partial
        // the homepage view @includes in its fallback branch.
        if ($sections->isEmpty()) {
            $data['heroBanners'] = $cms->getActiveBannersByPosition('hero');
            $data['flashDeal'] = $cms->getActiveFlashDeal();
            $data['topCategories'] = $this->getTopCategories(10);
            $data['newArrivals'] = $this->getNewArrivals(10);
            $data['bestSelling'] = $this->getBestSellingProducts(8);
            $data['brands'] = $this->getActiveBrands(12);
            $data['trendingProducts'] = $this->getTrendingProducts(10);
            $data['specialProducts'] = $this->getSpecialBrandProducts(8);
            $data['favouriteProducts'] = $this->getFavouriteProducts(6);
            $data['blogPosts'] = $cms->getPublishedPosts(6);
        }

        return $data;
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=StorefrontCacheInvalidationTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/StorefrontService.php Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php
git commit -m "feat(ecommerce): cache homepage assembly with post-cache decoration"
```

---

## Task 3: Cache the shop sidebar building blocks

The shop page's product list varies by filters/pagination (not cached), but the sidebar pieces are shared across every shop request and are the expensive recursive/aggregate queries. Cache those four.

**Files:**
- Modify: `Modules/Ecommerce/app/Services/StorefrontService.php`
  - `getSidebarCategories()` (lines 518–528)
  - `getSidebarBrands()` (lines 607–610)
  - `getActivePriceBounds()` (lines 537–547)
  - `getShopVariantFilters()` (lines 556–592)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php` (add a method)

- [ ] **Step 1: Write the failing test**

Append this method to `StorefrontCacheInvalidationTest`:

```php
    public function test_shop_sidebar_pieces_are_cached(): void
    {
        $service = app(StorefrontService::class);

        $service->getSidebarCategories();
        $service->getSidebarBrands();
        $service->getActivePriceBounds();
        $service->getShopVariantFilters();

        DB::enableQueryLog();
        $service->getSidebarCategories();
        $service->getSidebarBrands();
        $service->getActivePriceBounds();
        $service->getShopVariantFilters();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThan(5, count($queries));
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_shop_sidebar_pieces_are_cached`
Expected: FAIL — sidebar queries re-run on the second call.

- [ ] **Step 3: Wrap each method body in `StorefrontCache::remember('catalog', ...)`**

In `Modules/Ecommerce/app/Services/StorefrontService.php`, replace the four method bodies.

`getSidebarCategories()`:

```php
    public function getSidebarCategories(): Collection
    {
        return StorefrontCache::remember('catalog', 'shop_sidebar_categories', 3600, function () {
            // Recursive eager-load — the shop sidebar renders the full tree
            // (parent → sub → child…) so deeper levels aren't N+1 queried.
            return Category::active()
                ->root()
                ->ordered()
                ->with('recursiveChildren')
                ->get();
        });
    }
```

`getActivePriceBounds()`:

```php
    public function getActivePriceBounds(): array
    {
        return StorefrontCache::remember('catalog', 'shop_price_bounds', 3600, function () {
            $bounds = Product::active()
                ->selectRaw('MIN(sell_price) as min_price, MAX(sell_price) as max_price')
                ->first();

            return [
                'min' => (float) ($bounds->min_price ?? 0),
                'max' => (float) ($bounds->max_price ?? 0),
            ];
        });
    }
```

`getShopVariantFilters()`:

```php
    public function getShopVariantFilters(): \Illuminate\Support\Collection
    {
        return StorefrontCache::remember('catalog', 'shop_variant_filters', 3600, function () {
            // Value IDs in use by an active variant of an active product.
            $usedValueIds = DB::table('product_variant_values as pvv')
                ->join('product_variants as pv', 'pv.id', '=', 'pvv.product_variant_id')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->where('pv.is_active', true)
                ->whereNull('pv.deleted_at')
                ->where('p.status', 'active')
                ->whereNull('p.deleted_at')
                ->distinct()
                ->pluck('pvv.variant_attribute_value_id')
                ->all();

            if (empty($usedValueIds)) {
                return collect();
            }

            return \Modules\Variant\Models\VariantAttribute::active()
                ->ordered()
                ->with(['values' => function ($q) use ($usedValueIds) {
                    $q->whereIn('id', $usedValueIds);
                }])
                ->get()
                ->filter(fn ($attr) => $attr->values->isNotEmpty())
                ->map(fn ($attr) => [
                    'id'           => (int) $attr->id,
                    'name'         => $attr->display_name ?: $attr->name,
                    'display_type' => $attr->display_type,
                    'values'       => $attr->values->map(fn ($v) => [
                        'id'         => (int) $v->id,
                        'value'      => $v->value,
                        'color_code' => $v->color_code,
                    ])->values()->all(),
                ])
                ->values();
        });
    }
```

`getSidebarBrands()`:

```php
    public function getSidebarBrands(): Collection
    {
        return StorefrontCache::remember('catalog', 'shop_sidebar_brands', 3600, function () {
            return Brand::active()->ordered()->get();
        });
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=StorefrontCacheInvalidationTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/StorefrontService.php Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php
git commit -m "feat(ecommerce): cache shop sidebar categories, brands, price bounds, variant filters"
```

---

## Task 4: Cache the product-detail query (per slug)

Cache only the read query (`findProductBySlug`). All time-bound decoration stays in the controller and runs live. Per-slug key under the `catalog` namespace; a catalog bump invalidates every product page at once.

**Files:**
- Modify: `Modules/Ecommerce/app/Services/StorefrontService.php` — `findProductBySlug()` (lines 304–313)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php` (add a method)

- [ ] **Step 1: Write the failing test**

Append to `StorefrontCacheInvalidationTest`:

```php
    public function test_find_product_by_slug_is_cached_per_slug(): void
    {
        $product = Product::factory()->create(['status' => 'active']);
        $service = app(StorefrontService::class);

        $first = $service->findProductBySlug($product->slug);
        $this->assertNotNull($first);

        DB::enableQueryLog();
        $service->findProductBySlug($product->slug);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThan(3, count($queries)); // served from cache

        StorefrontCache::bump('catalog');

        DB::enableQueryLog();
        $service->findProductBySlug($product->slug);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertGreaterThan(2, count($queries)); // re-fetched after bump
    }
```

> If `Product::factory()` is unavailable, replace with a direct `Product::create([...])` using the table's required columns; check `Schema::getColumnListing('products')` for the minimal set (`name`, `slug`, `sell_price`, `status`).

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_find_product_by_slug_is_cached_per_slug`
Expected: FAIL — second call re-queries.

- [ ] **Step 3: Wrap `findProductBySlug()`**

Replace the method body in `StorefrontService.php`:

```php
    public function findProductBySlug(string $slug): ?Product
    {
        return StorefrontCache::remember('catalog', 'product:' . $slug, 3600, function () use ($slug) {
            return Product::active()
                ->where('slug', $slug)
                ->with([
                    'images', 'category', 'brand', 'variants', 'tags', 'approvedReviews',
                    'sizeChartOverrides',
                ])
                ->first();
        });
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=StorefrontCacheInvalidationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/StorefrontService.php Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php
git commit -m "feat(ecommerce): cache product-detail query per slug"
```

---

## Task 5: Cache the blog sidebar pieces

The paginated blog list varies by filters, so it stays uncached. The sidebar (popular posts, categories with counts, popular tags) is shared and cached under the `blog` namespace.

**Files:**
- Modify: `Modules/Ecommerce/app/Services/BlogService.php`
  - `categoriesWithCounts()` (line 17)
  - `popularTags(int $limit = 12)` (line 31)
  - `popularPosts(int $limit = 3, ?int $excludeId = null)` (line 49)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php` (add a method)

- [ ] **Step 1: Write the failing test**

Append to `StorefrontCacheInvalidationTest`:

```php
    public function test_blog_sidebar_pieces_are_cached(): void
    {
        $blog = app(\Modules\Ecommerce\Services\BlogService::class);

        $blog->categoriesWithCounts();
        $blog->popularTags();
        $blog->popularPosts(3);

        DB::enableQueryLog();
        $blog->categoriesWithCounts();
        $blog->popularTags();
        $blog->popularPosts(3);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThan(4, count($queries));
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_blog_sidebar_pieces_are_cached`
Expected: FAIL — sidebar queries re-run.

- [ ] **Step 3: Wrap the three methods**

In `Modules/Ecommerce/app/Services/BlogService.php`, add the import near the top:

```php
use Modules\Ecommerce\Support\StorefrontCache;
```

Wrap each method's existing query body inside a `StorefrontCache::remember('blog', <key>, 3600, fn () => <original body>)`. Use these keys, preserving the existing query logic exactly:
- `categoriesWithCounts()` → key `blog_categories_counts`
- `popularTags($limit)` → key `blog_popular_tags:' . $limit`
- `popularPosts($limit, $excludeId)` → key `blog_popular_posts:' . $limit . ':' . ($excludeId ?? 0)`

Example for `popularPosts` (apply the same wrapping pattern to the other two, keeping their original query code intact inside the closure):

```php
    public function popularPosts(int $limit = 3, ?int $excludeId = null): Collection
    {
        return StorefrontCache::remember('blog', 'blog_popular_posts:' . $limit . ':' . ($excludeId ?? 0), 3600, function () use ($limit, $excludeId) {
            // ... original query body, unchanged ...
        });
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=StorefrontCacheInvalidationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/BlogService.php Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php
git commit -m "feat(ecommerce): cache blog sidebar (popular posts, categories, tags)"
```

---

## Task 6: Cache observers + provider registration (auto-invalidation)

**Files:**
- Create: `Modules/Ecommerce/app/Observers/CatalogCacheObserver.php`
- Create: `Modules/Ecommerce/app/Observers/BlogCacheObserver.php`
- Modify: `Modules/Ecommerce/app/Providers/EcommerceServiceProvider.php`
- Test: `Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php` (add a method)

- [ ] **Step 1: Write the failing test**

Append to `StorefrontCacheInvalidationTest`:

```php
    public function test_saving_a_product_bumps_catalog_version(): void
    {
        $before = StorefrontCache::version('catalog');
        Product::factory()->create(['status' => 'active']);
        $this->assertGreaterThan($before, StorefrontCache::version('catalog'));
    }

    public function test_saving_a_brand_bumps_catalog_version(): void
    {
        $before = StorefrontCache::version('catalog');
        \Modules\Brand\Models\Brand::factory()->create();
        $this->assertGreaterThan($before, StorefrontCache::version('catalog'));
    }

    public function test_saving_a_blog_post_bumps_blog_version(): void
    {
        $before = StorefrontCache::version('blog');
        \Modules\Ecommerce\Models\BlogPost::factory()->create();
        $this->assertGreaterThan($before, StorefrontCache::version('blog'));
    }
```

> If any factory does not exist, substitute a `::create([...])` with the model's required columns (inspect via `Schema::getColumnListing(<table>)`). The assertion — "version increases after a write" — is what matters.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_saving_a_product_bumps_catalog_version`
Expected: FAIL — version unchanged (no observer registered yet).

- [ ] **Step 3: Create the observers**

Create `Modules/Ecommerce/app/Observers/CatalogCacheObserver.php`:

```php
<?php

namespace Modules\Ecommerce\Observers;

use Modules\Ecommerce\Support\StorefrontCache;

/**
 * Bumps the 'catalog' cache version on any create/update/delete/restore of a
 * watched catalog model, so storefront pages immediately reflect the change.
 * One observer is attached to every catalog model (see EcommerceServiceProvider).
 */
class CatalogCacheObserver
{
    public function saved($model): void
    {
        StorefrontCache::bump('catalog');
    }

    public function deleted($model): void
    {
        StorefrontCache::bump('catalog');
    }

    public function restored($model): void
    {
        StorefrontCache::bump('catalog');
    }
}
```

Create `Modules/Ecommerce/app/Observers/BlogCacheObserver.php`:

```php
<?php

namespace Modules\Ecommerce\Observers;

use Modules\Ecommerce\Support\StorefrontCache;

/**
 * Bumps the 'blog' cache version on any create/update/delete/restore of a blog
 * model (posts, comments).
 */
class BlogCacheObserver
{
    public function saved($model): void
    {
        StorefrontCache::bump('blog');
    }

    public function deleted($model): void
    {
        StorefrontCache::bump('blog');
    }

    public function restored($model): void
    {
        StorefrontCache::bump('blog');
    }
}
```

> Note: the Eloquent `saved` event fires for both `created` and `updated`, so the two cases are covered by one method.

- [ ] **Step 4: Register observers in the provider**

In `Modules/Ecommerce/app/Providers/EcommerceServiceProvider.php`, add a call at the end of `boot()`:

```php
        $this->registerCacheObservers();
```

Then add this method to the class (place after `registerPasswordResetUrl()`):

```php
    /**
     * Attach cache-invalidation observers. Any change to a watched catalog
     * model bumps the 'catalog' storefront cache version; blog models bump the
     * 'blog' version. Pivot-only changes (e.g. flash-deal ⇄ products) are
     * bumped explicitly at their sync sites — see ContentManagementService and
     * CampaignController.
     */
    protected function registerCacheObservers(): void
    {
        $catalogModels = [
            \Modules\Product\Models\Product::class,
            \Modules\Product\Models\ProductImage::class,
            \Modules\Product\Models\Tag::class,
            \Modules\Variant\Models\ProductVariant::class,
            \Modules\Variant\Models\VariantAttribute::class,
            \Modules\Variant\Models\VariantAttributeValue::class,
            \Modules\Category\Models\Category::class,
            \Modules\Brand\Models\Brand::class,
            \Modules\Ecommerce\Models\FlashDeal::class,
            \Modules\Ecommerce\Models\HomepageSection::class,
            \Modules\Ecommerce\Models\Banner::class,
            \Modules\Ecommerce\Models\Campaign::class,
            \Modules\Ecommerce\Models\ProductReview::class,
        ];

        foreach ($catalogModels as $model) {
            $model::observe(\Modules\Ecommerce\Observers\CatalogCacheObserver::class);
        }

        $blogModels = [
            \Modules\Ecommerce\Models\BlogPost::class,
            \Modules\Ecommerce\Models\BlogComment::class,
        ];

        foreach ($blogModels as $model) {
            $model::observe(\Modules\Ecommerce\Observers\BlogCacheObserver::class);
        }
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=StorefrontCacheInvalidationTest`
Expected: PASS (all methods).

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/app/Observers Modules/Ecommerce/app/Providers/EcommerceServiceProvider.php Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php
git commit -m "feat(ecommerce): auto-invalidate storefront cache via model observers"
```

---

## Task 7: Explicit bumps at pivot-sync sites

Pivot `sync()`/`attach()` calls do not fire `saved` on the watched parent, so storefront-visible pivot changes need explicit bumps.

**Files:**
- Modify: `Modules/Ecommerce/app/Services/ContentManagementService.php` (lines 140, 177, 261)
- Modify: `Modules/Ecommerce/app/Http/Controllers/CampaignController.php` (lines 60, 93)

- [ ] **Step 1: Write the failing test**

Append to `StorefrontCacheInvalidationTest`:

```php
    public function test_syncing_flash_deal_products_bumps_catalog_version(): void
    {
        $deal    = \Modules\Ecommerce\Models\FlashDeal::factory()->create();
        $product = Product::factory()->create(['status' => 'active']);

        $before = StorefrontCache::version('catalog');

        app(\Modules\Ecommerce\Services\ContentManagementService::class)
            ->syncFlashDealProducts($deal, [$product->id]); // adjust to the real method name/signature

        $this->assertGreaterThan($before, StorefrontCache::version('catalog'));
    }
```

> Before writing this test, open `ContentManagementService.php` around line 261 to read the actual public method that performs `$flashDeal->products()->sync($syncData)` and match its name/signature. If the sync happens inside a larger save method, call that method instead. The assertion is the contract: a pivot sync must bump `catalog`.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_syncing_flash_deal_products_bumps_catalog_version`
Expected: FAIL — version unchanged.

- [ ] **Step 3: Add bumps after each sync**

In `Modules/Ecommerce/app/Services/ContentManagementService.php`, add the import:

```php
use Modules\Ecommerce\Support\StorefrontCache;
```

Immediately after each of these existing lines, add `StorefrontCache::bump('catalog');`:
- After line 140 — `$section->products()->sync($sync);`
- After line 177 — `$collection->products()->sync($syncData);`
- After line 261 — `$flashDeal->products()->sync($syncData);`

Example (the flash-deal site):

```php
        $flashDeal->products()->sync($syncData);
        StorefrontCache::bump('catalog');
```

In `Modules/Ecommerce/app/Http/Controllers/CampaignController.php`, add the import:

```php
use Modules\Ecommerce\Support\StorefrontCache;
```

After each `$campaign->products()->sync($request->productIds());` (lines 60 and 93), add:

```php
        StorefrontCache::bump('catalog');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=StorefrontCacheInvalidationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/ContentManagementService.php Modules/Ecommerce/app/Http/Controllers/CampaignController.php Modules/Ecommerce/tests/Feature/StorefrontCacheInvalidationTest.php
git commit -m "feat(ecommerce): bump storefront cache on flash-deal/section/campaign pivot syncs"
```

---

## Task 8: Manual "Clear Storefront Cache" control in Settings

**Files:**
- Modify: `Modules/Setting/app/Http/Controllers/SettingController.php` (`clearCache()` at line 256; add `clearStorefrontCache()`)
- Modify: `Modules/Setting/routes/web.php` (cache routes block, lines 34–36)
- Modify: `Modules/Setting/resources/views/index.blade.php` (System Maintenance section, the Clear Cache card)

- [ ] **Step 1: Write the failing test**

Create `Modules/Setting/tests/Feature/StorefrontCacheClearTest.php`:

```php
<?php

namespace Modules\Setting\Tests\Feature;

use Modules\Ecommerce\Support\StorefrontCache;
use Tests\TestCase;

class StorefrontCacheClearTest extends TestCase
{
    public function test_clear_storefront_cache_route_bumps_versions(): void
    {
        $catalogBefore = StorefrontCache::version('catalog');
        $blogBefore = StorefrontCache::version('blog');

        $this->actingAsAdmin()
            ->post(route('settings.clear-storefront-cache'))
            ->assertRedirect();

        $this->assertGreaterThan($catalogBefore, StorefrontCache::version('catalog'));
        $this->assertGreaterThan($blogBefore, StorefrontCache::version('blog'));
    }
}
```

> Confirm the admin login helper name used elsewhere in Setting tests (`SettingControllerTest` uses `actingAsAdmin()` per `Tests\TestCase`). The route name `settings.clear-storefront-cache` is defined in Step 3.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=StorefrontCacheClearTest`
Expected: FAIL — route `settings.clear-storefront-cache` not defined.

- [ ] **Step 3: Add the route**

In `Modules/Setting/routes/web.php`, inside the `System Maintenance & Cache` block (after line 36, `Route::post('/clear-cache', ...)`), add:

```php
    Route::post('/clear-storefront-cache', [SettingController::class, 'clearStorefrontCache'])->name('clear-storefront-cache');
```

- [ ] **Step 4: Add the controller method + flush on global clear**

In `Modules/Setting/app/Http/Controllers/SettingController.php`, add the import near the top with the other `use` statements:

```php
use Modules\Ecommerce\Support\StorefrontCache;
```

Add the new method directly after the existing `clearCache()` method (after line 264):

```php
    /**
     * Bump the storefront cache version counters so all cached storefront
     * page data (home, shop, product detail, blog, flash deals) is rebuilt
     * on the next request.
     */
    public function clearStorefrontCache()
    {
        StorefrontCache::flushAll();

        return back()->with('success', __('Storefront cache cleared. Pages will rebuild on next visit.'));
    }
```

Also extend the existing `clearCache()` so the global clear flushes storefront versions too. Inside `clearCache()`, after the four `Artisan::call(...)` lines and before the `return`, add:

```php
        StorefrontCache::flushAll();
```

- [ ] **Step 5: Add the button to the view**

In `Modules/Setting/resources/views/index.blade.php`, inside the **Clear Cache** card body of the System Maintenance section (the `<div class="bp-card-body">` that holds the `settings.clear-cache` form), add a second form below the existing button:

```blade
              <hr class="my-3">
              <p class="fs-13 text-muted mb-3">Rebuild cached storefront pages (home, shop, product details, blog, flash deals). Use after bulk catalog or content changes.</p>
              <form action="{{ route('settings.clear-storefront-cache') }}" method="POST">
                @csrf
                <button type="submit" class="bp-btn bp-btn-outline"><i class="fa-solid fa-store me-1"></i> Clear Storefront Cache</button>
              </form>
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=StorefrontCacheClearTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add Modules/Setting/app/Http/Controllers/SettingController.php Modules/Setting/routes/web.php Modules/Setting/resources/views/index.blade.php Modules/Setting/tests/Feature/StorefrontCacheClearTest.php
git commit -m "feat(settings): add Clear Storefront Cache control"
```

---

## Task 9: Full regression sweep

**Files:** none (verification only)

- [ ] **Step 1: Run the full storefront + setting test suites**

Run: `php artisan test --filter=Storefront`
Then: `php artisan test Modules/Ecommerce/tests Modules/Setting/tests`
Expected: All PASS.

- [ ] **Step 2: Manual smoke test with the dev server**

Run: `php artisan serve --host=127.0.0.1 --port=8000` (in a separate terminal), then exercise:
- `GET /` (home), `/shop`, `/shop/<a-product-slug>`, `/blog`, `/flash-deals` — all return 200 and render product/cart correctly.
- Confirm cart/login/wishlist still reflect the current session (open in two browsers / incognito; carts must NOT bleed across sessions).
- Edit a product price in admin → reload its detail and shop pages → updated price shows (catalog bump worked).
- Edit a flash deal's window so it is currently active → product shows the deal price; after `ends_at` passes, the deal price disappears without any manual clear (live decoration).
- Settings → System Maintenance → "Clear Storefront Cache" → success flash; pages rebuild.

- [ ] **Step 3: Commit any fixes, then finish the branch**

If smoke testing surfaced fixes, commit them. Then follow `superpowers:finishing-a-development-branch` to merge/PR.

---

## Self-Review Notes (addressed)

- **Spec coverage:** home (Task 2), shop sidebar (Task 3), product detail (Task 4), blog (Task 5), flash-deals — flash-deal *list* is intentionally NOT cached because it is paginated and time-bound; its correctness comes from live decoration (documented in Task 2 and verified in Task 9 Step 2). Invalidation on "variants changed, category, brands, product CRUD, all possible changes" → Tasks 6 (observers across all catalog models incl. variants/category/brand/product/review/banner/section/campaign) + 7 (pivot syncs). Manual destroy → Task 8.
- **Why flash-deal list is uncached:** caching paginated, query-string-varying results yields low hit-rate and key explosion; the expensive shared pieces (sidebar/home assembly) are cached instead, and per-deal prices are applied live so they never go stale.
- **Type/name consistency:** helper API is `version()/bump()/remember()/flushAll()` used identically across all tasks; namespaces are exactly `'catalog'` and `'blog'`; route name `settings.clear-storefront-cache` matches between Task 8 Steps 1/3/5.
- **No placeholders:** every code step contains complete code. The two spots requiring a quick signature confirmation (Task 7 flash-deal sync method name; factory availability in Tasks 4/6/7) are flagged inline with the exact lines to read and a fallback.
