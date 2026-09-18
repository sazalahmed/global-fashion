# SEO Plan 3 — Crawl & Indexing (Implementation Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Consolidate duplicate URLs (clean canonicals), feed Google a sitemap + correct robots.txt, and preserve link equity with 301s on changed slugs.

**Architecture:** Extends the Plan-1 `Seo` pipeline (`BuildsSeo::cleanCanonical`), adds an artisan `sitemap:generate` command (writes a gitignored `public/sitemap.xml`, scheduled daily), a committed `public/robots.txt`, and a `slug_histories` table + `HasSlugHistory` trait that the storefront show controllers use to 301 old slugs.

**Tech Stack:** Laravel 12 (modular), Blade, MySQL, PHPUnit. Spec: `docs/superpowers/specs/2026-06-14-seo-crawl-indexing-design.md`.

**Conventions:** Branch `feat/storefront-seo-foundation` (do NOT switch). `php artisan test <path>`. Base `Tests\TestCase` = RefreshDatabase + BCRYPT_ROUNDS=4, no `$this->admin`, modular factories called directly (`\Modules\X\Database\Factories\YFactory::new()`). The Plan-2 env-noindex guard renders `noindex,nofollow` off-production, so per-page robots tests must `config(['app.env' => 'production'])`. Unrelated WIP exists (Purchase/Quotation/Sale/`app.js`) — NEVER `git add -A`; stage only the explicit paths. Transient dirty-test-DB → `php artisan migrate:fresh --env=testing` once.

---

## File Structure
- **New:** `Modules/Ecommerce/app/Console/GenerateSitemap.php`; `Modules/Ecommerce/app/Models/SlugHistory.php`; migration `..._create_slug_histories_table.php`; `Modules/Ecommerce/app/Support/HasSlugHistory.php`; tests `SitemapGenerateTest.php`, `SlugRedirectTest.php`.
- **Modify:** `Modules/Ecommerce/app/Support/BuildsSeo.php`; `ShopController@index/@show`, `StorefrontCategoryController@show`, `BlogController@show`; `Product`, `Category`, `BlogPost` models; `public/robots.txt`; `.gitignore`; `routes/console.php`.

---

## Task 1: `cleanCanonical` helper

**Files:**
- Modify: `Modules/Ecommerce/app/Support/BuildsSeo.php`
- Test: `Modules/Ecommerce/tests/Unit/CleanCanonicalTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Http\Request;
use Modules\Ecommerce\Support\BuildsSeo;
use Tests\TestCase;

class CleanCanonicalTest extends TestCase
{
    private function helper(): object
    {
        return new class { use BuildsSeo; public function call($r, $b, $k) { return $this->cleanCanonical($r, $b, $k); } };
    }

    public function test_strips_sort_price_and_tracking_keeps_category(): void
    {
        $req = Request::create('/shop?category=mugs&sort=price&min_price=10&utm_source=fb&fbclid=x', 'GET');
        $out = $this->helper()->call($req, 'https://x.test/shop', ['category', 'page']);
        $this->assertSame('https://x.test/shop?category=mugs', $out);
    }

    public function test_keeps_page_only_when_gt_1(): void
    {
        $h = $this->helper();
        $this->assertSame('https://x.test/shop', $h->call(Request::create('/shop?page=1', 'GET'), 'https://x.test/shop', ['page']));
        $this->assertSame('https://x.test/shop?page=3', $h->call(Request::create('/shop?page=3', 'GET'), 'https://x.test/shop', ['page']));
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/CleanCanonicalTest.php`
Expected: FAIL (undefined method `cleanCanonical`).

- [ ] **Step 3: Add the method** to the `BuildsSeo` trait (add `use Illuminate\Http\Request;` import):

```php
    /**
     * Build a clean canonical URL: $baseUrl + only the $keep query params present.
     * `page` is kept only when > 1; all other params (sort/price/utm_*/fbclid/...) are dropped.
     */
    protected function cleanCanonical(Request $request, string $baseUrl, array $keep = []): string
    {
        $params = [];
        foreach ($keep as $k) {
            $v = $request->query($k);
            if ($k === 'page') {
                if ((int) $v > 1) { $params['page'] = (int) $v; }
            } elseif (filled($v)) {
                $params[$k] = $v;
            }
        }
        return $params ? $baseUrl . '?' . http_build_query($params) : $baseUrl;
    }
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/CleanCanonicalTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Support/BuildsSeo.php Modules/Ecommerce/tests/Unit/CleanCanonicalTest.php
git commit -m "feat(seo): cleanCanonical helper (strip filter/sort/tracking params)"
```

---

## Task 2: Wire clean canonical + search noindex

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/ShopController.php` (`index`, `show`), `StorefrontCategoryController.php` (`show`)
- Test: `Modules/Ecommerce/tests/Feature/CanonicalTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class CanonicalTest extends TestCase
{
    public function test_shop_filters_canonicalize_to_clean_base(): void
    {
        config(['app.env' => 'production']);
        $res = $this->get('/shop?sort=price&min_price=10&utm_source=fb');
        $res->assertSee('rel="canonical" href="' . url('/shop') . '"', false);
    }

    public function test_search_results_are_noindex(): void
    {
        config(['app.env' => 'production']);
        $res = $this->get('/shop?q=anything');
        $res->assertSee('content="noindex,follow"', false);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/CanonicalTest.php`
Expected: FAIL (canonical includes query; not noindex).

- [ ] **Step 3: Wire ShopController@index** — after `$seo` is built (Plan-1/Plan-2 code), add:

```php
        $seo->canonical($this->cleanCanonical($request, route('storefront.shop.index'), ['category', 'page']));
        if (filled($request->query('q'))) {
            $seo->robots('noindex,follow')
                ->canonical(route('storefront.shop.index'));
        }
```

(`ShopController` must `use Modules\Ecommerce\Support\BuildsSeo;` — it already does from Plan 1. Confirm the request variable is `$request`.)

- [ ] **Step 4: Wire StorefrontCategoryController@show** — after `$seo` is built, add:

```php
        $seo->canonical($this->cleanCanonical($request, route('storefront.category.show', $category->slug), ['page']));
```

(Ensure the method has the `Request $request` parameter — `show(Request $request, string $slug)`; it does per the audit. Add `use BuildsSeo;` if missing.)

- [ ] **Step 5: ShopController@show** — set an explicit self canonical (no query):

```php
        $seo->canonical(route('storefront.shop.show', $product->slug));
```

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/CanonicalTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/ShopController.php Modules/Ecommerce/app/Http/Controllers/Storefront/StorefrontCategoryController.php Modules/Ecommerce/tests/Feature/CanonicalTest.php
git commit -m "feat(seo): clean canonical on shop/category + noindex internal search"
```

---

## Task 3: robots.txt

**Files:**
- Modify: `public/robots.txt`

- [ ] **Step 1: Replace `public/robots.txt`** contents with:

```
User-agent: *
Disallow: /admin
Disallow: /cart
Disallow: /checkout
Disallow: /customer
Disallow: /login
Disallow: /register
Disallow: /wishlist
Disallow: /compare
Allow: /

Sitemap: {{APP_URL}}/sitemap.xml
```

Replace `{{APP_URL}}` with the literal value of `config('app.url')` at edit time IF known; otherwise leave the token and note that Task 4's command rewrites the `Sitemap:` line. (Simplest: write the actual `APP_URL` from `.env` — read it — e.g. `http://127.0.0.1:8000/sitemap.xml` for dev; the sitemap command will overwrite it with the correct absolute URL on generation.)

- [ ] **Step 2: Verify** — `cat public/robots.txt` shows the disallows + a `Sitemap:` line.

- [ ] **Step 3: Commit**

```bash
git add public/robots.txt
git commit -m "feat(seo): robots.txt disallows + sitemap directive"
```

---

## Task 4: `sitemap:generate` command + gitignore + schedule

**Files:**
- Create: `Modules/Ecommerce/app/Console/GenerateSitemap.php`
- Modify: `.gitignore`, `routes/console.php`
- Test: `Modules/Ecommerce/tests/Feature/SitemapGenerateTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SitemapGenerateTest extends TestCase
{
    public function test_generates_sitemap_with_product_url(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'slug' => 'sm-prod', 'status' => 'active', 'sell_price' => 100,
        ]);

        $path = public_path('sitemap.xml');
        @unlink($path);
        Artisan::call('sitemap:generate');

        $this->assertFileExists($path);
        $xml = file_get_contents($path);
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString(url('/shop/sm-prod'), $xml);
        $this->assertStringContainsString(url('/'), $xml);
        $this->assertStringNotContainsString('/checkout', $xml);

        @unlink($path); // cleanup test artifact
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SitemapGenerateTest.php`
Expected: FAIL (command not found).

- [ ] **Step 3: Create the command** `Modules/Ecommerce/app/Console/GenerateSitemap.php`:

```php
<?php

namespace Modules\Ecommerce\Console;

use Illuminate\Console\Command;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\BlogPost;
use Modules\Product\Models\Product;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate public/sitemap.xml for storefront SEO';

    public function handle(): int
    {
        $urls = [];
        $add = function (string $loc, $lastmod = null) use (&$urls) {
            $urls[] = ['loc' => $loc, 'lastmod' => $lastmod];
        };

        $add(route('storefront.home'));
        $add(route('storefront.shop.index'));
        $add(route('storefront.category.index'));
        $add(route('storefront.blog.index'));
        $add(route('storefront.flash-deals'));

        Product::query()->where('status', 'active')->get()->each(function ($p) use ($add) {
            $add(route('storefront.shop.show', $p->slug), optional($p->updated_at)->toAtomString());
        });
        Category::query()->where('is_active', true)->get()->each(function ($c) use ($add) {
            $add(route('storefront.category.show', $c->slug), optional($c->updated_at)->toAtomString());
        });
        BlogPost::query()->where('is_published', true)->get()->each(function ($b) use ($add) {
            $add(route('storefront.blog.show', $b->slug), optional($b->updated_at)->toAtomString());
        });

        if (count($urls) > 50000) {
            $this->warn('Sitemap exceeds 50k URLs; consider splitting into a sitemap index.');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
                  . ($u['lastmod'] ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '')
                  . '</url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        file_put_contents(public_path('sitemap.xml'), $xml);

        // Keep robots.txt's Sitemap: line pointed at the configured app URL.
        $robots = public_path('robots.txt');
        if (is_file($robots)) {
            $content = preg_replace('/^Sitemap:.*$/m', 'Sitemap: ' . url('/sitemap.xml'), file_get_contents($robots));
            file_put_contents($robots, $content);
        }

        $this->info('Sitemap generated: ' . count($urls) . ' URLs.');
        return self::SUCCESS;
    }
}
```

> Confirm: `Category` has an `is_active` column and `BlogPost` an `is_published` column (per audit, yes). If a column name differs, adapt the `where(...)`. Confirm the command is auto-discovered (nwidart modules usually auto-register `app/Console`); if not, register it in the module's service provider `commands([...])`.

- [ ] **Step 4: Gitignore the artifact** — append to `.gitignore`:

```
/public/sitemap.xml
/public/sitemap*.xml
```

- [ ] **Step 5: Schedule daily** — in `routes/console.php`, add:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('sitemap:generate')->daily();
```

(If `routes/console.php` already imports `Schedule`, don't duplicate the import.)

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SitemapGenerateTest.php`
Expected: PASS. Also run `php artisan sitemap:generate` once manually and confirm `public/sitemap.xml` is valid XML.

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/app/Console/GenerateSitemap.php .gitignore routes/console.php Modules/Ecommerce/tests/Feature/SitemapGenerateTest.php
git commit -m "feat(seo): sitemap:generate command + daily schedule + gitignore artifact"
```

---

## Task 5: SlugHistory model + migration + trait

**Files:**
- Create: migration `Modules/Ecommerce/database/migrations/2026_06_14_000003_create_slug_histories_table.php`, `Modules/Ecommerce/app/Models/SlugHistory.php`, `Modules/Ecommerce/app/Support/HasSlugHistory.php`
- Test: `Modules/Ecommerce/tests/Feature/SlugRedirectTest.php` (records part)

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class SlugRedirectTest extends TestCase
{
    public function test_changing_slug_records_history(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $p = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'slug' => 'old-slug', 'status' => 'active', 'sell_price' => 100,
        ]);
        $p->update(['slug' => 'new-slug']);

        $this->assertDatabaseHas('slug_histories', [
            'old_slug' => 'old-slug', 'model_id' => $p->id,
        ]);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SlugRedirectTest.php`
Expected: FAIL (no table / trait).

- [ ] **Step 3: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('slug_histories', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('old_slug');
            $table->timestamps();
            $table->unique(['model_type', 'old_slug']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_histories');
    }
};
```

- [ ] **Step 4: Model** `SlugHistory.php`

```php
<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class SlugHistory extends Model
{
    protected $fillable = ['model_type', 'model_id', 'old_slug'];
}
```

- [ ] **Step 5: Trait** `HasSlugHistory.php`

```php
<?php

namespace Modules\Ecommerce\Support;

use Modules\Ecommerce\Models\SlugHistory;

trait HasSlugHistory
{
    public static function bootHasSlugHistory(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty('slug') && filled($model->getOriginal('slug'))) {
                SlugHistory::updateOrCreate(
                    ['model_type' => $model->getMorphClass(), 'old_slug' => $model->getOriginal('slug')],
                    ['model_id' => $model->getKey()]
                );
            }
        });
    }

    /** Return the CURRENT slug for a historical slug, or null. */
    public static function currentSlugFor(string $oldSlug): ?string
    {
        $row = SlugHistory::where('model_type', (new static)->getMorphClass())
            ->where('old_slug', $oldSlug)->first();
        if (! $row) { return null; }
        return optional(static::find($row->model_id))->slug;
    }
}
```

- [ ] **Step 6: Use the trait** — add `use \Modules\Ecommerce\Support\HasSlugHistory;` inside the `Product`, `Category`, and `BlogPost` model classes (as a `use HasSlugHistory;` statement in the class body).

- [ ] **Step 7: Migrate + run test**

Run: `php artisan migrate` then `php artisan test Modules/Ecommerce/tests/Feature/SlugRedirectTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add Modules/Ecommerce/database/migrations/2026_06_14_000003_create_slug_histories_table.php Modules/Ecommerce/app/Models/SlugHistory.php Modules/Ecommerce/app/Support/HasSlugHistory.php Modules/Product/app/Models/Product.php Modules/Category/app/Models/Category.php Modules/Ecommerce/app/Models/BlogPost.php Modules/Ecommerce/tests/Feature/SlugRedirectTest.php
git commit -m "feat(seo): slug history recording on product/category/blog slug change"
```

---

## Task 6: 301 redirect old slugs in show controllers

**Files:**
- Modify: `ShopController@show`, `StorefrontCategoryController@show`, `BlogController@show`
- Test: append to `SlugRedirectTest`

- [ ] **Step 1: Append failing test**

```php
    public function test_old_product_slug_301s_to_new(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $p = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'slug' => 'old-p', 'status' => 'active', 'sell_price' => 100,
        ]);
        $p->update(['slug' => 'new-p']);

        $res = $this->get('/shop/old-p');
        $res->assertStatus(301);
        $res->assertRedirect(route('storefront.shop.show', 'new-p'));
    }

    public function test_unknown_slug_404s(): void
    {
        $this->get('/shop/totally-unknown')->assertStatus(404);
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SlugRedirectTest.php --filter "301s_to_new|unknown_slug"`
Expected: the 301 test fails (currently 404).

- [ ] **Step 3: Add the redirect** in `ShopController@show` — at the start, where the product is looked up (currently `findOrFail`/`first()` then `abort(404)`). Replace the not-found path:

```php
        $product = \Modules\Product\Models\Product::where('slug', $slug)->first();
        if (! $product) {
            $current = \Modules\Product\Models\Product::currentSlugFor($slug);
            if ($current && $current !== $slug) {
                return redirect()->route('storefront.shop.show', $current, 301);
            }
            abort(404);
        }
```

(Adapt to the controller's existing lookup — keep its eager-loads. If it currently uses `findOrFail` via a service, fetch with `first()` and branch as above.)

- [ ] **Step 4: Same pattern for `StorefrontCategoryController@show`** (`Category::currentSlugFor`, redirect to `storefront.category.show`) and **`BlogController@show`** (`BlogPost::currentSlugFor`, redirect to `storefront.blog.show`).

- [ ] **Step 5: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SlugRedirectTest.php`
Expected: PASS (all 3).

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/ShopController.php Modules/Ecommerce/app/Http/Controllers/Storefront/StorefrontCategoryController.php Modules/Ecommerce/app/Http/Controllers/Storefront/BlogController.php Modules/Ecommerce/tests/Feature/SlugRedirectTest.php
git commit -m "feat(seo): 301 redirect changed product/category/blog slugs"
```

---

## Task 7: Full regression

- [ ] **Step 1: Run the SEO + tracking suites**

Run:
```bash
php artisan test Modules/Ecommerce/tests/Unit/CleanCanonicalTest.php Modules/Ecommerce/tests/Feature/CanonicalTest.php Modules/Ecommerce/tests/Feature/SitemapGenerateTest.php Modules/Ecommerce/tests/Feature/SlugRedirectTest.php Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php Modules/Ecommerce/tests/Unit/SeoTest.php Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php
```
Expected: all pass.

- [ ] **Step 2: Manual QA (document):**
- [ ] `/shop?sort=price&utm_source=x` → `<link rel="canonical" href=".../shop">`; `/shop?q=foo` is `noindex,follow`.
- [ ] `php artisan sitemap:generate` writes valid `public/sitemap.xml` (open it); robots.txt `Sitemap:` line points at the right host.
- [ ] Rename a product slug in admin → old URL 301s to new; a never-existed slug → 404.
- [ ] Confirm `public/sitemap.xml` is gitignored (`git status` clean after generation).

---

## Self-Review notes (addressed)
- **Spec coverage:** clean canonical + search noindex (T1-2); robots.txt (T3); sitemap command + schedule + gitignore (T4); slug history (T5) + 301 (T6); regression (T7). Host/HTTPS normalization explicitly deferred (Plan 3.5) per spec.
- **Type consistency:** `cleanCanonical(Request,$baseUrl,$keep)` defined T1, used T2; `HasSlugHistory::currentSlugFor()` defined T5, used T6 on all three models; sitemap uses `status='active'`/`is_active`/`is_published` (flagged to confirm against real columns).
- **Test/env:** per-page robots/canonical tests set `config(['app.env'=>'production'])` to bypass the Plan-2 env guard; sitemap test cleans up the generated file artifact.
