# Storefront SEO Plan 1 — Pipeline + Admin SEO + Structured Data (Implementation Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give every storefront page a complete, unique, admin-controlled `<head>` (title/description/canonical/robots/OG/Twitter) plus schema.org JSON-LD, driven by one central `Seo` pipeline.

**Architecture:** A `Seo` value object (built per request by each storefront controller using `admin field → derived → site default`) is shared to the view; a `seo-head` Blade partial renders the full head + JSON-LD from it; a `SeoSchemaService` builds the JSON-LD graphs. Admin-managed SEO fields live on products/categories/blog + a `seo_pages` table + `EcommerceSetting` site defaults. Coexists with the already-shipped GTM/Pixel tracking components (no analytics here).

**Tech Stack:** Laravel 12 (modular, nwidart), Blade, MySQL, PHPUnit. Spec: `docs/superpowers/specs/2026-06-14-storefront-seo-foundation-design.md`. Exhaustive page-by-page reference: `docs/STOREFRONT_SEO_PLAN.md` §D.

**Conventions:** Run a test file with `php artisan test <path>`. Base `Tests\TestCase` uses `RefreshDatabase` (empty DB per test, no seeders, does NOT provide `$this->admin` — create users/records via factories; the modular `HasFactory` auto-resolver is broken, so call `\Modules\X\Database\Factories\YFactory::new()` directly). `BCRYPT_ROUNDS=4` is set for tests. There is unrelated uncommitted WIP in the tree (Purchase/Quotation/Sale/`app.js`) — never `git add -A`; stage only the explicit paths each task lists.

---

## File Structure

**New**
- `Modules/Ecommerce/app/Support/Seo.php` — value object + resolution + render helpers.
- `Modules/Ecommerce/app/Services/SeoSchemaService.php` — JSON-LD graph builders.
- `Modules/Ecommerce/app/Support/BuildsSeo.php` — trait controllers use to build/share `$seo` (DRY).
- `Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php` — renders head + JSON-LD.
- `Modules/Ecommerce/app/Models/SeoPage.php` + migration `..._create_seo_pages_table.php`.
- migrations: blog `seo_title/seo_description/seo_image`; products `seo_image`; categories `meta_image`.
- `Modules/Ecommerce/database/seeders/SeoPageSeeder.php`.
- `public/website/assets/js/seo-snippet.js` — snippet preview + char counters.
- Tests: `Modules/Ecommerce/tests/Unit/SeoTest.php`, `Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php`, `Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php`.

**Modified**
- `storefront/layouts/master.blade.php` (head → `seo-head`).
- 7 storefront controllers + the new trait.
- product/category/blog admin form views + FormRequests + models (fillable).
- `ecommerce::settings` view (SEO section).

---

# PHASE 1 — Pipeline

## Task 1: `Seo` value object

**Files:**
- Create: `Modules/Ecommerce/app/Support/Seo.php`
- Test: `Modules/Ecommerce/tests/Unit/SeoTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Unit;

use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Support\Seo;
use Tests\TestCase;

class SeoTest extends TestCase
{
    public function test_resolve_prefers_admin_then_derived_then_default(): void
    {
        EcommerceSetting::set('seo_default_title', 'Default');
        $seo = Seo::make();
        $this->assertSame('Admin', $seo->resolve('Admin', 'Derived', 'seo_default_title'));
        $this->assertSame('Derived', $seo->resolve(null, 'Derived', 'seo_default_title'));
        $this->assertSame('Default', $seo->resolve(null, null, 'seo_default_title'));
    }

    public function test_rendered_title_appends_site_with_separator_and_caps_length(): void
    {
        EcommerceSetting::set('seo_site_name', 'BizShop');
        EcommerceSetting::set('seo_title_separator', '|');
        $seo = Seo::make()->title('Red Shirt');
        $this->assertSame('Red Shirt | BizShop', $seo->renderedTitle());

        $long = str_repeat('a', 100);
        $this->assertLessThanOrEqual(80, strlen(Seo::make()->title($long)->renderedTitle()));
    }

    public function test_rendered_description_strips_tags_and_caps_160(): void
    {
        $seo = Seo::make()->description('<p>' . str_repeat('x', 300) . '</p>');
        $out = $seo->renderedDescription();
        $this->assertStringNotContainsString('<p>', $out);
        $this->assertLessThanOrEqual(160, strlen($out));
    }

    public function test_rendered_image_makes_absolute(): void
    {
        $seo = Seo::make()->image('storage/og.jpg');
        $this->assertStringStartsWith('http', $seo->renderedImage());
        $this->assertSame('https://cdn.test/x.jpg', Seo::make()->image('https://cdn.test/x.jpg')->renderedImage());
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/SeoTest.php`
Expected: FAIL (class not found).

- [ ] **Step 3: Create the value object**

```php
<?php

namespace Modules\Ecommerce\Support;

use Illuminate\Support\Str;
use Modules\Ecommerce\Models\EcommerceSetting;

class Seo
{
    public ?string $title = null;
    public ?string $description = null;
    public ?string $canonical = null;
    public string $robots = 'index,follow';
    public ?string $image = null;
    public string $type = 'website';
    public array $schema = [];
    public array $breadcrumbs = [];

    public static function make(): self
    {
        $s = new self();
        $s->canonical = url()->current();
        return $s;
    }

    public function title(?string $v): self { if (filled($v)) { $this->title = $v; } return $this; }
    public function description(?string $v): self { if (filled($v)) { $this->description = $v; } return $this; }
    public function canonical(?string $v): self { if (filled($v)) { $this->canonical = $v; } return $this; }
    public function robots(string $v): self { $this->robots = $v; return $this; }
    public function image(?string $v): self { if (filled($v)) { $this->image = $v; } return $this; }
    public function type(string $v): self { $this->type = $v; return $this; }
    public function addSchema(array $node): self { if ($node) { $this->schema[] = $node; } return $this; }
    public function breadcrumbs(array $items): self { $this->breadcrumbs = $items; return $this; }

    /** First non-empty of admin → derived → site-default setting. */
    public function resolve(?string $admin, ?string $derived, string $defaultKey): ?string
    {
        foreach ([$admin, $derived, EcommerceSetting::get($defaultKey)] as $v) {
            if (filled($v)) { return $v; }
        }
        return null;
    }

    public function siteName(): string
    {
        return EcommerceSetting::get('seo_site_name') ?: config('app.name', 'BizPOS Pro');
    }

    public function renderedTitle(): string
    {
        $site  = $this->siteName();
        $sep   = EcommerceSetting::get('seo_title_separator') ?: '|';
        $title = $this->title ?: (EcommerceSetting::get('seo_default_title') ?: $site);
        $title = trim(Str::limit($title, 60, ''));
        return $title === $site ? $title : "{$title} {$sep} {$site}";
    }

    public function renderedDescription(): string
    {
        $d = $this->description ?: (EcommerceSetting::get('seo_default_description') ?: '');
        return trim(Str::limit(strip_tags($d), 160, ''));
    }

    public function renderedImage(): ?string
    {
        $img = $this->image ?: EcommerceSetting::get('seo_default_image');
        if (! $img) { return null; }
        return Str::startsWith($img, ['http://', 'https://']) ? $img : url($img);
    }

    public function canonicalUrl(): string
    {
        return $this->canonical ?: url()->current();
    }
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/SeoTest.php`
Expected: PASS (4 tests). If `EcommerceSetting::set` signature differs, adapt the test setup to the real API (it is `set(string $key, $value)`).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Support/Seo.php Modules/Ecommerce/tests/Unit/SeoTest.php
git commit -m "feat(seo): Seo value object with resolution + render helpers"
```

---

## Task 2: `SeoSchemaService` — global graphs (Organization + WebSite)

**Files:**
- Create: `Modules/Ecommerce/app/Services/SeoSchemaService.php`
- Test: `Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Unit;

use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Services\SeoSchemaService;
use Tests\TestCase;

class SeoSchemaServiceTest extends TestCase
{
    private function svc(): SeoSchemaService { return app(SeoSchemaService::class); }

    public function test_organization_node_uses_settings(): void
    {
        EcommerceSetting::set('seo_org_name', 'BizShop Ltd');
        $node = $this->svc()->organization();
        $this->assertSame('Organization', $node['@type']);
        $this->assertSame('BizShop Ltd', $node['name']);
        $this->assertArrayHasKey('url', $node);
    }

    public function test_website_node_has_search_action(): void
    {
        $node = $this->svc()->website();
        $this->assertSame('WebSite', $node['@type']);
        $this->assertSame('SearchAction', $node['potentialAction']['@type']);
        $this->assertStringContainsString('q={search_term_string}', $node['potentialAction']['target']['urlTemplate']);
    }

    public function test_breadcrumb_list_positions(): void
    {
        $node = $this->svc()->breadcrumbList([
            ['name' => 'Home', 'url' => 'https://x.test/'],
            ['name' => 'Shop', 'url' => 'https://x.test/shop'],
            ['name' => 'Red Shirt', 'url' => null],
        ]);
        $this->assertSame('BreadcrumbList', $node['@type']);
        $this->assertCount(3, $node['itemListElement']);
        $this->assertSame(1, $node['itemListElement'][0]['position']);
        $this->assertSame('Red Shirt', $node['itemListElement'][2]['name']);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php`
Expected: FAIL (class not found).

- [ ] **Step 3: Create the service** (global + breadcrumb builders; entity builders added in Task 9)

```php
<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\EcommerceSetting;

class SeoSchemaService
{
    public function organization(): array
    {
        $name = EcommerceSetting::get('seo_org_name')
            ?: (EcommerceSetting::get('seo_site_name') ?: config('app.name', 'BizPOS Pro'));
        $logo = EcommerceSetting::get('seo_org_logo');

        $sameAs = array_values(array_filter([
            EcommerceSetting::get('facebook_url'),
            EcommerceSetting::get('instagram_url'),
            EcommerceSetting::get('youtube_url'),
            EcommerceSetting::get('twitter_url'),
            EcommerceSetting::get('linkedin_url'),
        ]));

        $node = [
            '@type' => 'Organization',
            '@id'   => url('/') . '/#organization',
            'name'  => $name,
            'url'   => url('/'),
        ];
        if ($logo) { $node['logo'] = str_starts_with($logo, 'http') ? $logo : url($logo); }
        if ($sameAs) { $node['sameAs'] = $sameAs; }

        return $node;
    }

    public function website(): array
    {
        return [
            '@type'     => 'WebSite',
            '@id'       => url('/') . '/#website',
            'url'       => url('/'),
            'name'      => EcommerceSetting::get('seo_site_name') ?: config('app.name', 'BizPOS Pro'),
            'publisher' => ['@id' => url('/') . '/#organization'],
            'potentialAction' => [
                '@type'  => 'SearchAction',
                'target' => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => url('/shop') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function breadcrumbList(array $items): array
    {
        $elements = [];
        foreach (array_values($items) as $i => $item) {
            $el = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $item['name'],
            ];
            if (! empty($item['url'])) { $el['item'] = $item['url']; }
            $elements[] = $el;
        }
        return ['@type' => 'BreadcrumbList', 'itemListElement' => $elements];
    }
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Services/SeoSchemaService.php Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php
git commit -m "feat(seo): SeoSchemaService global Organization/WebSite + breadcrumb builders"
```

---

## Task 3: `seo-head` partial + layout integration + default `$seo` fallback

**Files:**
- Create: `Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php`
- Modify: `Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php:9-25` (replace ad-hoc head block) + share a default `$seo`
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/HomeController.php` (build a minimal `$seo` so home is non-default — full controller wiring is Task 8; here just prove the pipeline renders)
- Test: `Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\EcommerceSetting;
use Tests\TestCase;

class StorefrontSeoTest extends TestCase
{
    public function test_home_emits_complete_head_with_defaults(): void
    {
        EcommerceSetting::set('seo_site_name', 'BizShop');
        EcommerceSetting::set('seo_default_description', 'Best shop in BD');

        $res = $this->get(route('storefront.home'));
        $res->assertSee('<link rel="canonical"', false);
        $res->assertSee('BizShop', false);
        $res->assertSee('Best shop in BD', false);
        $res->assertSee('property="og:title"', false);
        $res->assertSee('name="twitter:card"', false);
        $res->assertSee('application/ld+json', false);
        $res->assertSee('"@type":"Organization"', false);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php`
Expected: FAIL (no canonical/jsonld yet).

- [ ] **Step 3: Create the partial**

```blade
{{-- Renders the full SEO <head> + JSON-LD from $seo (Modules\Ecommerce\Support\Seo). --}}
@php
    $seo = $seo ?? \Modules\Ecommerce\Support\Seo::make();
    $schemaSvc = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
    // Global graph on every page.
    $graph = array_merge([$schemaSvc->organization(), $schemaSvc->website()], $seo->schema);
    $ogImage = $seo->renderedImage();
    $verification = \Modules\Ecommerce\Models\EcommerceSetting::get('google_site_verification');
    $twitterHandle = \Modules\Ecommerce\Models\EcommerceSetting::get('seo_twitter_handle');
@endphp
<title>{{ $seo->renderedTitle() }}</title>
<meta name="description" content="{{ $seo->renderedDescription() }}">
<link rel="canonical" href="{{ $seo->canonicalUrl() }}">
<meta name="robots" content="{{ $seo->robots }}">
@if($verification)<meta name="google-site-verification" content="{{ $verification }}">@endif

<meta property="og:site_name" content="{{ $seo->siteName() }}">
<meta property="og:locale" content="en_US">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->renderedTitle() }}">
<meta property="og:description" content="{{ $seo->renderedDescription() }}">
<meta property="og:url" content="{{ $seo->canonicalUrl() }}">
@if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif

<meta name="twitter:card" content="summary_large_image">
@if($twitterHandle)<meta name="twitter:site" content="{{ $twitterHandle }}">@endif
<meta name="twitter:title" content="{{ $seo->renderedTitle() }}">
<meta name="twitter:description" content="{{ $seo->renderedDescription() }}">
@if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
```

> Note: `json_encode` output in a `ld+json` script is data, not executable JS — safe. Meta values use Blade `{{ }}` auto-escaping.

- [ ] **Step 4: Integrate into the layout**

In `master.blade.php`, REPLACE the current head block (lines ~9–25: the `<title>`, the two `@hasSection('meta_description'/'meta_keywords')` blocks, and the OG block ending at `<meta property="og:type" content="website">`) with:

```blade
    @include('ecommerce::storefront.partials.seo-head')
```

Leave the favicon (line ~27), `@stack('styles')`, and `<x-core::tracking-head />` exactly as they are.

- [ ] **Step 5: Share a default `$seo` so non-wired pages still render**

Add a `View::share` in a service provider so every storefront view has `$seo`. In `Modules/Ecommerce/app/Providers/EcommerceServiceProvider.php` `boot()`, add:

```php
        \Illuminate\Support\Facades\View::composer('ecommerce::storefront.*', function ($view) {
            if (! array_key_exists('seo', $view->getData())) {
                $view->with('seo', \Modules\Ecommerce\Support\Seo::make());
            }
        });
```

(If the provider/namespace differs, find the Ecommerce service provider's `boot()` and add the composer there. Confirm the storefront view namespace prefix is `ecommerce::storefront.`.)

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php`
Expected: PASS. If the home page previously relied on `@section('title','Home')`, that still works because controllers set `$seo` later; for now the composer default renders site name. Confirm no Blade error from the removed `@hasSection` (search the layout for stray references).

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php Modules/Ecommerce/app/Providers/EcommerceServiceProvider.php Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php
git commit -m "feat(seo): seo-head partial + layout integration + default seo composer"
```

---

# PHASE 2 — Admin-managed SEO fields

## Task 4: Migrations + models for SEO columns + `seo_pages`

**Files:**
- Create: `Modules/Ecommerce/database/migrations/2026_06_14_000001_add_seo_columns_to_blog_products_categories.php`
- Create: `Modules/Ecommerce/database/migrations/2026_06_14_000002_create_seo_pages_table.php`
- Create: `Modules/Ecommerce/app/Models/SeoPage.php`
- Modify: `Modules/Ecommerce/app/Models/BlogPost.php` (fillable), `Modules/Product/app/Models/Product.php` (fillable + `seo_image`), `Modules/Category/app/Models/Category.php` (fillable + `meta_image`)
- Test: `Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php` (append a column-existence smoke test) OR a new small migration test

- [ ] **Step 1: Write the failing test** (append to `SeoSchemaServiceTest`)

```php
    public function test_seo_columns_and_seo_pages_exist(): void
    {
        $this->assertTrue(\Schema::hasColumn('blog_posts', 'seo_title'));
        $this->assertTrue(\Schema::hasColumn('blog_posts', 'seo_image'));
        $this->assertTrue(\Schema::hasColumn('products', 'seo_image'));
        $this->assertTrue(\Schema::hasColumn('categories', 'meta_image'));
        $this->assertTrue(\Schema::hasTable('seo_pages'));
    }
```

Add `use Illuminate\Support\Facades\Schema;` if needed (or use `\Schema`).

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php --filter test_seo_columns_and_seo_pages_exist`
Expected: FAIL.

- [ ] **Step 3: Create migration 1** (`...000001_add_seo_columns...`)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('seo_title', 255)->nullable()->after('slug');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->string('seo_image')->nullable()->after('seo_description');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->string('seo_image')->nullable()->after('seo_description');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->string('meta_image')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', fn (Blueprint $t) => $t->dropColumn(['seo_title', 'seo_description', 'seo_image']));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('seo_image'));
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('meta_image'));
    }
};
```

(Anchor `after()` on real columns: `blog_posts.slug`, `products.seo_description`, `categories.meta_description` all exist per the audit. If an anchor is missing, drop the `after()`.)

- [ ] **Step 4: Create migration 2** (`...000002_create_seo_pages_table.php`)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();      // home, shop, categories, blog, flash-deals
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('robots')->default('index,follow');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
```

- [ ] **Step 5: Create the `SeoPage` model**

```php
<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class SeoPage extends Model
{
    protected $fillable = ['key', 'title', 'description', 'image', 'robots'];

    public static function forKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
```

- [ ] **Step 6: Update fillables**

- `BlogPost.php`: add `'seo_title', 'seo_description', 'seo_image'` to `$fillable`.
- `Product.php`: add `'seo_image'` to `$fillable` (`seo_title`/`seo_description` already present).
- `Category.php`: add `'meta_image'` to `$fillable` (`meta_title`/`meta_description` already present).

- [ ] **Step 7: Migrate + run test**

Run: `php artisan migrate` then `php artisan test Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php`
Expected: migration applies; all pass.

- [ ] **Step 8: Commit**

```bash
git add Modules/Ecommerce/database/migrations/2026_06_14_000001_add_seo_columns_to_blog_products_categories.php Modules/Ecommerce/database/migrations/2026_06_14_000002_create_seo_pages_table.php Modules/Ecommerce/app/Models/SeoPage.php Modules/Ecommerce/app/Models/BlogPost.php Modules/Product/app/Models/Product.php Modules/Category/app/Models/Category.php Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php
git commit -m "feat(seo): SEO columns on blog/product/category + seo_pages table & model"
```

---

## Task 5: Admin SEO form fields + FormRequest validation

**Files:**
- Modify: `Modules/Product/resources/views/create.blade.php` + `edit.blade.php` (existing SEO section ~L393/396 — add `seo_image`)
- Modify: `Modules/Category/resources/views/create.blade.php` + `edit.blade.php` (existing SEO Information section — add `meta_image`)
- Modify: `Modules/Ecommerce/resources/views/blog-post-create.blade.php` + `blog-post-edit.blade.php` (add a new SEO section)
- Modify: `Modules/Product/app/Http/Requests/StoreProductRequest.php` + `UpdateProductRequest.php`; the Category + Blog FormRequests/validation
- Test: manual + a feature test on blog save (blog SEO is the new path)

- [ ] **Step 1: Add `seo_image` to the Product SEO section** (in both create & edit, inside the existing "eCommerce & SEO" card). Use the existing image-input pattern in that form (file input + name `seo_image`). Example:

```blade
                  <div class="col-12">
                    <label class="bp-form-label">SEO / OG Image</label>
                    <input type="file" class="bp-form-control @error('seo_image') is-invalid @enderror" name="seo_image" accept="image/*">
                    @error('seo_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
```

If the form stores images as paths via a service, match that pattern; otherwise store the uploaded path on `seo_image`. In edit, show the current value.

- [ ] **Step 2: Add `meta_image` to the Category SEO Information section** (create & edit), same file-input pattern, `name="meta_image"`.

- [ ] **Step 3: Add a SEO section to blog create & edit** (after the Post Details card). Use the project's `bp-card` pattern:

```blade
<div class="bp-card mb-4">
  <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>SEO</h5></div>
  <div class="bp-card-body">
    <div class="row g-3">
      <div class="col-12">
        <label class="bp-form-label">SEO Title</label>
        <input type="text" class="bp-form-control" name="seo_title" value="{{ old('seo_title', $post->seo_title ?? '') }}" placeholder="Defaults to the post title">
      </div>
      <div class="col-12">
        <label class="bp-form-label">Meta Description</label>
        <textarea class="bp-form-control" name="seo_description" rows="2" placeholder="Max ~160 characters">{{ old('seo_description', $post->seo_description ?? '') }}</textarea>
      </div>
      <div class="col-12">
        <label class="bp-form-label">SEO / OG Image</label>
        <input type="file" class="bp-form-control" name="seo_image" accept="image/*">
      </div>
    </div>
  </div>
</div>
```

(In create there is no `$post`; the `?? ''` handles it. Drop the `$post->` part in create if it errors.)

- [ ] **Step 4: Validate the new fields** in each FormRequest / controller validation:
- Product Store/Update requests: add `'seo_title' => ['nullable','string','max:255']`, `'seo_description' => ['nullable','string','max:300']`, `'seo_image' => ['nullable','image','max:2048']` (only add rules that aren't already present).
- Category validation: `'meta_image' => ['nullable','image','max:2048']` (meta_title/description likely already validated).
- Blog controller/request: `'seo_title' => ['nullable','string','max:255']`, `'seo_description' => ['nullable','string','max:300']`, `'seo_image' => ['nullable','image','max:2048']`.
Ensure the controllers persist `seo_image`/`meta_image` (store the uploaded file via the same `Storage`/path approach used for other images in that controller) and the new blog fields.

- [ ] **Step 5: Feature test (blog SEO persists)** — append to `StorefrontSeoTest` (or a new admin test). Create an admin, post to the blog store route with `seo_title`, assert it saved. Confirm the real blog store route name via `php artisan route:list --name=blog`. Example:

```php
    public function test_blog_post_saves_seo_fields(): void
    {
        $admin = \App\Models\User::factory()->create();
        $res = $this->actingAs($admin)->post(route('blog-posts.store'), [
            'title' => 'My Post', 'excerpt' => 'x', 'content' => 'body',
            'seo_title' => 'Custom SEO Title', 'seo_description' => 'Custom desc',
        ]);
        $this->assertDatabaseHas('blog_posts', ['title' => 'My Post', 'seo_title' => 'Custom SEO Title']);
    }
```

Adapt the route name + required fields to the actual blog store request (inspect the blog admin controller/request). If admin routes require permissions/middleware that complicate this, assert via a direct `BlogPost::create([...])` round-trip of the new fillable fields instead, and verify the form renders the inputs with a `$this->get(route('blog-posts.create'))->assertSee('name="seo_title"', false)`.

- [ ] **Step 6: Run + commit**

Run the test file; then:

```bash
git add Modules/Product/resources/views/create.blade.php Modules/Product/resources/views/edit.blade.php Modules/Category/resources/views/create.blade.php Modules/Category/resources/views/edit.blade.php Modules/Ecommerce/resources/views/blog-post-create.blade.php Modules/Ecommerce/resources/views/blog-post-edit.blade.php Modules/Product/app/Http/Requests/StoreProductRequest.php Modules/Product/app/Http/Requests/UpdateProductRequest.php Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php
# plus any Category/Blog request or controller files you edited
git commit -m "feat(seo): admin SEO image fields + blog SEO section + validation"
```

---

## Task 6: SEO Settings admin section + `seo_pages` seeder

**Files:**
- Modify: `Modules/Ecommerce/resources/views/settings.blade.php` (new "SEO" section in the settings form)
- Create: `Modules/Ecommerce/database/seeders/SeoPageSeeder.php`
- Modify: `Modules/Ecommerce/app/Http/Controllers/EcommerceController.php` `settingsUpdate` ONLY IF it doesn't already persist arbitrary keys (verify first — `EcommerceService::updateSettings` likely loops all keys)
- Test: `StorefrontSeoTest` (settings save)

- [ ] **Step 1: Add the SEO settings fields** to `settings.blade.php` inside the existing settings `<form>` (which posts to `route('ecommerce.settings.update')`). Add inputs (names become `EcommerceSetting` keys): `seo_site_name`, `seo_title_separator`, `seo_default_title`, `seo_default_description`, `seo_default_image` (file), `seo_twitter_handle`, `seo_org_name`, `seo_org_logo` (file), `google_site_verification`. Use the existing settings field markup pattern. Pre-fill with `{{ $settings['seo_site_name'] ?? '' }}` (match how the view reads existing settings).

- [ ] **Step 2: Add per-static-page rows** (home/shop/categories/blog/flash-deals) — for each, a title + description + robots select, named e.g. `seo_pages[home][title]`. Persist to the `seo_pages` table. If the existing `settingsUpdate` can't handle the nested `seo_pages[...]` array, add a small block in `EcommerceController@settingsUpdate` to upsert them:

```php
        foreach ((array) $request->input('seo_pages', []) as $key => $vals) {
            \Modules\Ecommerce\Models\SeoPage::updateOrCreate(
                ['key' => $key],
                [
                    'title'       => $vals['title'] ?? null,
                    'description' => $vals['description'] ?? null,
                    'robots'      => $vals['robots'] ?? 'index,follow',
                ]
            );
        }
```

- [ ] **Step 3: Create the seeder** (seeds the 5 known static-page keys so the admin form has rows):

```php
<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\SeoPage;

class SeoPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['home', 'shop', 'categories', 'blog', 'flash-deals'] as $key) {
            SeoPage::firstOrCreate(['key' => $key]);
        }
    }
}
```

- [ ] **Step 4: Feature test (settings persist)** — append to `StorefrontSeoTest`:

```php
    public function test_seo_settings_save_and_render_on_home(): void
    {
        \Modules\Ecommerce\Models\EcommerceSetting::set('seo_site_name', 'MyStore');
        \Modules\Ecommerce\Models\EcommerceSetting::set('seo_default_description', 'desc here');
        $res = $this->get(route('storefront.home'));
        $res->assertSee('MyStore', false);
        $res->assertSee('desc here', false);
    }
```

- [ ] **Step 5: Run + commit**

```bash
git add Modules/Ecommerce/resources/views/settings.blade.php Modules/Ecommerce/database/seeders/SeoPageSeeder.php Modules/Ecommerce/app/Http/Controllers/EcommerceController.php Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php
git commit -m "feat(seo): SEO Settings admin section + seo_pages seeder"
```

---

## Task 7: Snippet preview + char counters (admin UX)

**Files:**
- Create: `public/website/assets/js/seo-snippet.js`
- Modify: product/category/blog admin form views to include the script + markup hooks

- [ ] **Step 1: Create the helper** (vanilla JS, `'use strict';`):

```js
'use strict';
/* SEO snippet preview + char counters.
   Wire by adding data-seo-title / data-seo-desc to the inputs and a
   [data-seo-preview] container with .seo-pv-title/.seo-pv-desc/.seo-pv-url. */
(function () {
    function cap(el, max, counterSel) {
        var counter = document.querySelector(counterSel);
        function update() {
            var len = (el.value || '').length;
            if (counter) { counter.textContent = len + '/' + max; counter.style.color = len > max ? '#C0392B' : ''; }
        }
        el.addEventListener('input', update);
        update();
    }
    document.addEventListener('DOMContentLoaded', function () {
        var t = document.querySelector('[data-seo-title]');
        var d = document.querySelector('[data-seo-desc]');
        var pv = document.querySelector('[data-seo-preview]');
        if (t) { cap(t, 60, '[data-seo-title-count]'); }
        if (d) { cap(d, 160, '[data-seo-desc-count]'); }
        if (pv && (t || d)) {
            function sync() {
                var pt = pv.querySelector('.seo-pv-title');
                var pd = pv.querySelector('.seo-pv-desc');
                if (pt && t) { pt.textContent = t.value || t.getAttribute('placeholder') || ''; }
                if (pd && d) { pd.textContent = d.value || d.getAttribute('placeholder') || ''; }
            }
            if (t) { t.addEventListener('input', sync); }
            if (d) { d.addEventListener('input', sync); }
            sync();
        }
    });
})();
```

- [ ] **Step 2: Verify syntax** — `node --check public/website/assets/js/seo-snippet.js` (expect valid).

- [ ] **Step 3: Wire into the three admin forms** — add `data-seo-title` to the seo/meta title input, `data-seo-desc` to the description, small counter spans (`<small data-seo-title-count></small>`), a preview block:

```blade
<div class="seo-snippet-preview" data-seo-preview>
  <div class="seo-pv-title"></div>
  <div class="seo-pv-url">{{ url('/') }}/…</div>
  <div class="seo-pv-desc"></div>
</div>
@push('scripts')<script src="{{ asset('website/assets/js/seo-snippet.js') }}"></script>@endpush
```

Add minimal styling using existing `bp-` classes (no inline CSS per project rules); if a style is needed, add a `bp-seo-snippet` class to `public/css/style.css`. Apply to product, category, and blog SEO sections.

- [ ] **Step 4: Commit**

```bash
git add public/website/assets/js/seo-snippet.js Modules/Product/resources/views/create.blade.php Modules/Product/resources/views/edit.blade.php Modules/Category/resources/views/create.blade.php Modules/Category/resources/views/edit.blade.php Modules/Ecommerce/resources/views/blog-post-create.blade.php Modules/Ecommerce/resources/views/blog-post-edit.blade.php public/css/style.css
git commit -m "feat(seo): admin snippet preview + char counters"
```

---

# PHASE 3 — Controllers populate `$seo` + JSON-LD

## Task 8: `BuildsSeo` trait + wire all storefront controllers

**Files:**
- Create: `Modules/Ecommerce/app/Support/BuildsSeo.php`
- Modify: `HomeController`, `ShopController@index/@show/@flashDeals`, `StorefrontCategoryController@index/@show`, `BlogController@index/@show`
- Test: `StorefrontSeoTest` (per-page title/description/canonical)

- [ ] **Step 1: Create the trait** (DRY helpers shared by controllers):

```php
<?php

namespace Modules\Ecommerce\Support;

use Modules\Ecommerce\Models\SeoPage;

trait BuildsSeo
{
    protected function staticPageSeo(string $key): Seo
    {
        $page = SeoPage::forKey($key);
        return Seo::make()
            ->title($page?->title)
            ->description($page?->description)
            ->robots($page?->robots ?: 'index,follow')
            ->image($page?->image);
    }
}
```

- [ ] **Step 2: Write per-page tests** — append to `StorefrontSeoTest`. Use factories (direct, per conventions). Example for product:

```php
    public function test_product_page_uses_seo_title_then_name(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $product = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'name' => 'Blue Mug', 'slug' => 'blue-mug',
            'seo_title' => 'Best Blue Mug', 'sell_price' => 300, 'status' => 'active',
        ]);
        $res = $this->get(route('storefront.shop.show', 'blue-mug'));
        $res->assertSee('Best Blue Mug', false);          // seo_title wins
        $res->assertSee('rel="canonical"', false);
    }

    public function test_shop_index_has_shop_title_and_canonical(): void
    {
        $res = $this->get(route('storefront.shop.index'));
        $res->assertSee('rel="canonical"', false);
        $res->assertSee('property="og:title"', false);
    }
```

- [ ] **Step 3: Wire HomeController** — `use Modules\Ecommerce\Support\BuildsSeo;` on the controller, then in `index()` build and pass `$seo`:

```php
        $seo = $this->staticPageSeo('home')->type('website');
        // ...existing data..., add 'seo' => $seo to the view data
        return view('ecommerce::storefront.pages.home.index', [/* existing */, 'seo' => $seo]);
```

- [ ] **Step 4: Wire ShopController**
- `index()`: `$seo = $this->staticPageSeo('shop');` if `request('q')`, `$seo->title('Search: '.request('q'))`. Pass `'seo' => $seo`.
- `show()`: build from product:

```php
        $seo = \Modules\Ecommerce\Support\Seo::make()
            ->title($seo0 = $product->seo_title ?: $product->name)
            ->description($product->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 160))
            ->image($product->seo_image ?: optional($product->images->firstWhere('is_primary', true) ?? $product->images->first())->image_path)
            ->type('product')
            ->breadcrumbs([
                ['name' => 'Home', 'url' => route('storefront.home')],
                ['name' => 'Shop', 'url' => route('storefront.shop.index')],
                ['name' => $product->name, 'url' => null],
            ]);
        // pass 'seo' => $seo to the view
```

(Use the real image accessor on `ProductImage` — confirm the column, e.g. `image_path` or `image`.)
- `flashDeals()`: `$seo = $this->staticPageSeo('flash-deals');`

- [ ] **Step 5: Wire StorefrontCategoryController**
- `index()`: `$seo = $this->staticPageSeo('categories');`
- `show()`: `$seo = Seo::make()->title($category->meta_title ?: $category->name)->description($category->meta_description ?: Str::limit(strip_tags($category->description),160))->image($category->meta_image ?: $category->image)->breadcrumbs([... Home, Categories, {category} ...]);`

- [ ] **Step 6: Wire BlogController**
- `index()`: `$seo = $this->staticPageSeo('blog');`
- `show()`: `$seo = Seo::make()->title($post->seo_title ?: $post->title)->description($post->seo_description ?: Str::limit(strip_tags($post->excerpt ?: $post->content),160))->image($post->seo_image ?: $post->featured_image)->type('article')->breadcrumbs([... Home, Blog, {post->title} ...]);`

Each controller passes `'seo' => $seo` in its `view(...)` data (merge with existing compact/array).

- [ ] **Step 7: Run tests + commit**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php`
Expected: PASS.

```bash
git add Modules/Ecommerce/app/Support/BuildsSeo.php Modules/Ecommerce/app/Http/Controllers/Storefront/HomeController.php Modules/Ecommerce/app/Http/Controllers/Storefront/ShopController.php Modules/Ecommerce/app/Http/Controllers/Storefront/StorefrontCategoryController.php Modules/Ecommerce/app/Http/Controllers/Storefront/BlogController.php Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php
git commit -m "feat(seo): controllers build per-page Seo (admin->derived->default) + breadcrumbs"
```

---

## Task 9: `SeoSchemaService` entity builders

**Files:**
- Modify: `Modules/Ecommerce/app/Services/SeoSchemaService.php` (add product/blogPosting/collectionPage/localBusiness)
- Modify: `Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php`

- [ ] **Step 1: Write failing tests** (append):

```php
    public function test_product_node_has_offer_with_bdt_and_availability(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $product = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'name' => 'Mug', 'slug' => 'mug', 'sell_price' => 300, 'sku' => 'MUG1',
        ]);
        $node = $this->svc()->product($product->fresh(), 'https://x.test/shop/mug');
        $this->assertSame('Product', $node['@type']);
        $this->assertSame('BDT', $node['offers']['priceCurrency']);
        $this->assertStringContainsString('schema.org', $node['offers']['availability']);
    }

    public function test_collection_page_lists_items(): void
    {
        $node = $this->svc()->collectionPage('Shop', 'https://x.test/shop', [
            ['name' => 'A', 'url' => 'https://x.test/shop/a', 'image' => 'https://x.test/a.jpg'],
        ]);
        $this->assertSame('CollectionPage', $node['@type']);
        $this->assertSame('ItemList', $node['mainEntity']['@type']);
        $this->assertSame(1, $node['mainEntity']['numberOfItems']);
    }
```

- [ ] **Step 2: Run to verify it fails.** `php artisan test Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php`

- [ ] **Step 3: Add the builders** to `SeoSchemaService`:

```php
    public function product(\Modules\Product\Models\Product $p, string $canonical): array
    {
        $price = (float) $p->displayPrice()->effective;
        $inStock = (bool) $p->is_in_stock;

        $node = [
            '@type'       => 'Product',
            '@id'         => $canonical . '#product',
            'name'        => $p->name,
            'description' => trim(\Illuminate\Support\Str::limit(strip_tags($p->seo_description ?: $p->description), 300)),
            'sku'         => $p->sku,
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => $canonical,
                'priceCurrency' => 'BDT',
                'price'         => $price,
                'availability'  => 'https://schema.org/' . ($inStock ? 'InStock' : 'OutOfStock'),
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];

        $primary = $p->images->firstWhere('is_primary', true) ?? $p->images->first();
        if ($primary) { $node['image'] = [url($primary->image_path)]; }
        if ($p->brand) { $node['brand'] = ['@type' => 'Brand', 'name' => optional($p->brand)->name]; }

        $count = $p->approvedReviews()->count();
        if ($count > 0) {
            $node['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round((float) $p->approvedReviews()->avg('rating'), 1),
                'reviewCount' => $count,
            ];
        }

        return $node;
    }

    public function blogPosting(\Modules\Ecommerce\Models\BlogPost $post, string $canonical): array
    {
        $node = [
            '@type'            => 'BlogPosting',
            '@id'              => $canonical . '#article',
            'headline'         => $post->title,
            'mainEntityOfPage' => $canonical,
            'description'      => trim(\Illuminate\Support\Str::limit(strip_tags($post->seo_description ?: $post->excerpt), 200)),
            'publisher'        => ['@id' => url('/') . '/#organization'],
        ];
        if ($post->featured_image) { $node['image'] = url($post->featured_image); }
        if ($post->published_at) { $node['datePublished'] = $post->published_at->toIso8601String(); }
        if ($post->updated_at) { $node['dateModified'] = $post->updated_at->toIso8601String(); }
        if ($post->author) { $node['author'] = ['@type' => 'Person', 'name' => optional($post->author)->name]; }
        return $node;
    }

    public function collectionPage(string $name, string $canonical, iterable $items): array
    {
        $elements = [];
        $i = 0;
        foreach ($items as $it) {
            $elements[] = array_filter([
                '@type'    => 'ListItem',
                'position' => ++$i,
                'url'      => $it['url'] ?? null,
                'name'     => $it['name'] ?? null,
                'image'    => $it['image'] ?? null,
            ]);
        }
        return [
            '@type'      => 'CollectionPage',
            '@id'        => $canonical . '#collection',
            'name'       => $name,
            'url'        => $canonical,
            'mainEntity' => ['@type' => 'ItemList', 'numberOfItems' => $i, 'itemListElement' => $elements],
        ];
    }

    public function localBusiness(\Modules\Branch\Models\Branch $b): array
    {
        $node = [
            '@type'     => 'Store',
            'name'      => $b->name,
            'telephone' => $b->phone,
            'address'   => array_filter([
                '@type'           => 'PostalAddress',
                'streetAddress'   => $b->address,
                'addressLocality' => $b->city,
                'addressRegion'   => $b->district,
                'postalCode'      => $b->zip_code ?? null,
                'addressCountry'  => 'BD',
            ]),
        ];
        if ($b->opening_time && $b->closing_time) {
            $node['openingHours'] = $b->opening_time . '-' . $b->closing_time;
        }
        return $node;
    }
```

(Confirm `ProductImage` path column — `image_path` vs `image`; adapt. Confirm `is_in_stock` accessor name `getIsInStockAttribute` → `$p->is_in_stock`.)

- [ ] **Step 4: Run to verify it passes + commit**

```bash
git add Modules/Ecommerce/app/Services/SeoSchemaService.php Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php
git commit -m "feat(seo): SeoSchemaService product/blogPosting/collectionPage/localBusiness builders"
```

---

## Task 10: Wire JSON-LD into each page's `$seo`

**Files:**
- Modify: the 7 storefront controllers (add `$seo->addSchema(...)` + breadcrumb schema)
- Test: `StorefrontSeoTest`

- [ ] **Step 1: Write failing tests** (append):

```php
    public function test_product_page_emits_product_jsonld(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'name' => 'Mug', 'slug' => 'mug2', 'sell_price' => 300, 'sku' => 'M2', 'status' => 'active',
        ]);
        $res = $this->get(route('storefront.shop.show', 'mug2'));
        $res->assertSee('"@type":"Product"', false);
        $res->assertSee('"@type":"BreadcrumbList"', false);
        $res->assertSee('"priceCurrency":"BDT"', false);
    }
```

- [ ] **Step 2: Run to verify it fails.**

- [ ] **Step 3: Add schema to each controller** after building `$seo` (Task 8), using the `SeoSchemaService` (inject or `app(...)`) and the page's data:

- **ShopController@show:**
```php
        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        $canonical = route('storefront.shop.show', $product->slug);
        $seo->addSchema($schema->product($product, $canonical))
            ->addSchema($schema->breadcrumbList($seo->breadcrumbs));
```
- **BlogController@show:**
```php
        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        $canonical = route('storefront.blog.show', $post->slug);
        $seo->addSchema($schema->blogPosting($post, $canonical))
            ->addSchema($schema->breadcrumbList($seo->breadcrumbs));
```
- **ShopController@index, @flashDeals, StorefrontCategoryController@show, @index, BlogController@index:** add `collectionPage(...)` over the paginated items (map each item to `['name','url','image']`) + `breadcrumbList($seo->breadcrumbs)`. Example (shop index):
```php
        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        $items = $products->map(fn ($p) => [
            'name'  => $p->name,
            'url'   => route('storefront.shop.show', $p->slug),
            'image' => optional($p->images->first())->image_path ? url($p->images->first()->image_path) : null,
        ])->all();
        $seo->addSchema($schema->collectionPage('Shop', url()->current(), $items));
```
- **HomeController:** add `localBusiness(...)` for each active ecom-enabled branch:
```php
        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        foreach (\Modules\Branch\Models\Branch::where('is_active', true)->get() as $branch) {
            $seo->addSchema($schema->localBusiness($branch));
        }
```
(Confirm the Branch query scope/columns; `is_ecom_enabled` may be the better filter.)

- [ ] **Step 4: Run tests + commit**

Run: `php artisan test Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php`

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/HomeController.php Modules/Ecommerce/app/Http/Controllers/Storefront/ShopController.php Modules/Ecommerce/app/Http/Controllers/Storefront/StorefrontCategoryController.php Modules/Ecommerce/app/Http/Controllers/Storefront/BlogController.php Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php
git commit -m "feat(seo): emit Product/BlogPosting/CollectionPage/Breadcrumb/LocalBusiness JSON-LD"
```

---

# PHASE 4 — QA

## Task 11: Cross-page SEO regression + cleanup

**Files:**
- Modify: `Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php` (coverage sweep)

- [ ] **Step 1: Add a coverage sweep test** asserting each public page type renders a complete head:

```php
    public function test_all_public_pages_have_head_essentials(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create(['slug' => 'c1']);
        \Modules\Product\Database\Factories\ProductFactory::new()->create(['category_id' => $cat->id, 'slug' => 'p1', 'status' => 'active', 'sell_price' => 100]);

        foreach ([
            route('storefront.home'),
            route('storefront.shop.index'),
            route('storefront.category.index'),
            route('storefront.blog.index'),
            route('storefront.flash-deals'),
        ] as $url) {
            $res = $this->get($url);
            $res->assertOk();
            $res->assertSee('rel="canonical"', false);
            $res->assertSee('application/ld+json', false);
            $res->assertSee('name="twitter:card"', false);
        }
    }
```

- [ ] **Step 2: Run the full SEO suite**

Run:
```bash
php artisan test Modules/Ecommerce/tests/Unit/SeoTest.php Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php
```
Expected: all pass.

- [ ] **Step 3: Manual QA checklist (requires running app)** — document, don't block:
- [ ] View-source each page type: one `<title>`, meta description, self-canonical, robots, OG + Twitter, JSON-LD present.
- [ ] Google Rich Results Test passes for Product, BlogPosting, Breadcrumb, Organization on real URLs.
- [ ] Editing a product `seo_title` / category `meta_title` / blog `seo_title` / SEO Settings default changes the rendered head; empty fields fall back derived → default.
- [ ] `seo-head` coexists with the GTM/Pixel tracking head (both present, no duplication/errors).

- [ ] **Step 4: Commit**

```bash
git add Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php
git commit -m "test(seo): cross-page head/JSON-LD coverage sweep"
```

---

## Self-Review notes (addressed)
- **Spec coverage:** Phase 1 pipeline (Tasks 1-3); Phase 2 admin fields/settings/UX (Tasks 4-7); Phase 3 controllers + JSON-LD (Tasks 8-10); QA (Task 11). Coexistence with tracking preserved (Task 3 keeps `<x-core::tracking-head/>`). Deferred items (canonical logic/sitemap/robots/h1/perf) are explicitly Plan 2/3, not here.
- **Type consistency:** `Seo` API (`title/description/canonical/robots/image/type/addSchema/breadcrumbs/resolve/rendered*`) defined in Task 1 and used unchanged in Tasks 3/8/10. `SeoSchemaService` methods (`organization/website/breadcrumbList/product/blogPosting/collectionPage/localBusiness`) defined in Tasks 2/9 and called in Task 10 with matching signatures.
- **Verify-before-code reminders:** exact `ProductImage` path column, blog store route name, and Branch ecom filter are flagged inline for the implementer to confirm against the codebase.
