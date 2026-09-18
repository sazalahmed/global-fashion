# SEO Plan 4 — On-page & Core Web Vitals (Implementation Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** One `<h1>` per page with the right content, lazy/dimensioned images with real alts, and the safe Core-Web-Vitals head wins (preconnect, hero preload, CLS reservations) — without touching the risky JS/CSS pipeline (that's Plan 4.5).

**Architecture:** Markup/CSS-only changes to storefront Blade views + the storefront stylesheet, plus head additions. No controller/logic changes. Verified via rendered-HTML feature tests.

**Tech Stack:** Laravel 12 (modular), Blade, PHPUnit. Spec: `docs/superpowers/specs/2026-06-14-seo-onpage-cwv-design.md`.

**Conventions:** Branch `feat/storefront-seo-foundation` (do NOT switch). `php artisan test <path>`. RefreshDatabase + BCRYPT_ROUNDS=4; modular factories direct (`\Modules\Category\Database\Factories\CategoryFactory::new()`, `\Modules\Product\Database\Factories\ProductFactory::new()`). Plan-2 env-noindex guard renders noindex off-prod (irrelevant here). Unrelated WIP exists — NEVER `git add -A`; stage only the explicit paths. NO inline CSS — use classes in `public/website/assets/css/style.css` with dark-mode parity. Preserve existing CSS classes when changing a heading's tag so the visual design is unchanged.

---

## Task 1: One `<h1>` per page

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/partials/breadcrumb.blade.php` (demote h1)
- Modify: `storefront/pages/shop/show.blade.php`, `blog/show.blade.php`, `category/show.blade.php` (promote title → h1); `home/index.blade.php`, `shop/index.blade.php`, `category/index.blade.php`, `flash-deals/index.blade.php` (add an h1 where missing)
- Modify: `public/website/assets/css/style.css` (style `.bp-breadcrumb-title` to match the old h1; add `.bp-visually-hidden`)
- Test: `Modules/Ecommerce/tests/Feature/OnPageSeoTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class OnPageSeoTest extends TestCase
{
    private function product(): \Modules\Product\Models\Product
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        return \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'name' => 'Blue Mug', 'slug' => 'blue-mug', 'status' => 'active', 'sell_price' => 100,
        ]);
    }

    public function test_product_page_has_exactly_one_h1_with_name(): void
    {
        $this->product();
        $html = $this->get('/shop/blue-mug')->getContent();
        $this->assertSame(1, substr_count($html, '<h1'), 'exactly one <h1>');
        $this->assertMatchesRegularExpression('/<h1[^>]*>.*Blue Mug.*<\/h1>/s', $html);
    }

    public function test_home_and_shop_have_one_h1(): void
    {
        foreach (['/', '/shop'] as $url) {
            $html = $this->get($url)->getContent();
            $this->assertSame(1, substr_count($html, '<h1'), "one <h1> on {$url}");
        }
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/OnPageSeoTest.php`
Expected: FAIL (breadcrumb h1 + product h2 → either 0 or 2 h1 depending on page; product name in h2 not h1).

- [ ] **Step 3: Demote the breadcrumb heading** — in `breadcrumb.blade.php`, change `<h1>@yield('breadcrumb_title')</h1>` to:

```blade
<h2 class="bp-breadcrumb-title">@yield('breadcrumb_title')</h2>
```

- [ ] **Step 4: Promote detail titles to `<h1>`** — READ each detail view; change the product-name `<h2 class="details_title">` in `shop/show.blade.php` to `<h1 class="details_title">` (keep the class); same for the blog title `<h2>` in `blog/show.blade.php` → `<h1>` (keep classes); and the category name in `category/show.blade.php` → ensure a single `<h1>` carries `$category->name`.

- [ ] **Step 5: Add an `<h1>` to index/listing pages** — `home/index.blade.php`, `shop/index.blade.php`, `category/index.blade.php`, `flash-deals/index.blade.php`. If the page has a visible title element, make it the `<h1>`; otherwise add a visually-hidden one near the top of the main content:

```blade
<h1 class="bp-visually-hidden">{{ $seo->title ?? 'Shop' }}</h1>
```

Use a sensible per-page label (Home → site name or "Online Shopping"; Shop → "Shop"; Categories → "Categories"; Blog handled by its own; Flash Deals → "Flash Deals"). Ensure each page ends with exactly ONE `<h1>` (the breadcrumb is now `<h2>`).

- [ ] **Step 6: Add the CSS** — append to `public/website/assets/css/style.css`:

```css
/* SEO: breadcrumb title (demoted from h1) keeps the old h1 styling */
.bp-breadcrumb-title { font-size: 2rem; font-weight: 700; margin: 0; color: inherit; }
/* SEO: visually-hidden h1 for listing pages */
.bp-visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
```

(Match the breadcrumb title's previous size if it differs — read the existing CSS for the old `<h1>` rule inside the breadcrumb/page-banner and mirror it.)

- [ ] **Step 7: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/OnPageSeoTest.php`
Expected: PASS. Also `php artisan test Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php` (no regression).

- [ ] **Step 8: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/partials/breadcrumb.blade.php Modules/Ecommerce/resources/views/storefront/pages/shop/show.blade.php Modules/Ecommerce/resources/views/storefront/pages/blog/show.blade.php Modules/Ecommerce/resources/views/storefront/pages/category/show.blade.php Modules/Ecommerce/resources/views/storefront/pages/home/index.blade.php Modules/Ecommerce/resources/views/storefront/pages/shop/index.blade.php Modules/Ecommerce/resources/views/storefront/pages/category/index.blade.php Modules/Ecommerce/resources/views/storefront/pages/flash-deals/index.blade.php public/website/assets/css/style.css Modules/Ecommerce/tests/Feature/OnPageSeoTest.php
git commit -m "feat(seo): single semantic h1 per storefront page"
```

---

## Task 2: Image lazy-loading, dimensions, and alt text

**Files:**
- Modify: `storefront/partials/product-card.blade.php`, `storefront/partials/buy-now-modal.blade.php`, `storefront/pages/shop/show.blade.php` (gallery), `storefront/pages/category/show.blade.php`, `storefront/pages/blog/show.blade.php`
- Modify: `public/website/assets/js/cart.js` (buy-now sets img alt)
- Test: append to `OnPageSeoTest`

- [ ] **Step 1: Append failing test**

```php
    public function test_product_cards_lazyload_images(): void
    {
        $this->product();
        $html = $this->get('/shop')->getContent();
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    public function test_buy_now_modal_image_has_nonempty_alt(): void
    {
        $html = $this->get('/')->getContent();
        // buy-now modal is included in the layout; its <img> must not have an empty alt
        $this->assertStringNotContainsString('alt=""', $html);
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/OnPageSeoTest.php --filter "lazyload|nonempty_alt"`
Expected: FAIL.

- [ ] **Step 3: Product card image** — in `product-card.blade.php`, add `loading="lazy"` + dimensions to the `<img>` (it has `alt` already). Example: `<img src="..." alt="{{ $product->name }}" class="img-fluid w-100" loading="lazy" width="300" height="300">`. If a fixed pixel size is wrong for the design, instead add a CSS `aspect-ratio` to the card image box (`.product_item img { aspect-ratio: 1 / 1; object-fit: cover; }` in style.css) and keep `loading="lazy"`. Do NOT lazy-load if this card is the LCP (product cards are below the fold — safe).

- [ ] **Step 4: Buy-now modal** — in `buy-now-modal.blade.php`, change the empty-alt `<img ... alt="">` to `alt="{{ __('Product') }}"` (it's JS-populated). Then in `cart.js`, where the buy-now modal image `src` is set from variant-data (search for the modal img assignment in `BuyNow`), also set its `alt` to the product name from the fetched data (e.g. `$('#buyNowImg').attr('alt', data.name || 'Product')`). Confirm the modal img selector/id by reading the partial + cart.js.

- [ ] **Step 5: Detail/category/blog images** — add `loading="lazy"` to below-the-fold images: the product gallery thumbnails in `shop/show.blade.php` (NOT the main/first gallery image — that's the LCP, leave it eager), category sidebar/listing images, and the blog featured image if below the fold (the blog list thumbnails; the single post's featured image at top is LCP — leave eager). Ensure each has a meaningful `alt` (entity name).

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/OnPageSeoTest.php`
Expected: PASS. `node --check public/website/assets/js/cart.js` valid.

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/partials/product-card.blade.php Modules/Ecommerce/resources/views/storefront/partials/buy-now-modal.blade.php Modules/Ecommerce/resources/views/storefront/pages/shop/show.blade.php Modules/Ecommerce/resources/views/storefront/pages/category/show.blade.php Modules/Ecommerce/resources/views/storefront/pages/blog/show.blade.php public/website/assets/js/cart.js public/website/assets/css/style.css Modules/Ecommerce/tests/Feature/OnPageSeoTest.php
git commit -m "feat(seo): lazy-load + dimensions + alt on storefront images"
```

---

## Task 3: Preconnect + hero preload + CLS reservations

**Files:**
- Modify: `storefront/layouts/master.blade.php` (preconnect; hero preload)
- Modify: `public/website/assets/css/style.css` (CLS reservations)
- Modify: hero slider partial (only if needed to expose the first image URL for preload)
- Test: append to `OnPageSeoTest`

- [ ] **Step 1: Append failing test**

```php
    public function test_head_has_analytics_preconnect(): void
    {
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('rel="preconnect" href="https://www.googletagmanager.com"', $html);
        $this->assertStringContainsString('rel="preconnect" href="https://connect.facebook.net"', $html);
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/OnPageSeoTest.php --filter test_head_has_analytics_preconnect`
Expected: FAIL.

- [ ] **Step 3: Add preconnect** — in `master.blade.php` `<head>` (high up, before the CSS `<link>`s), add:

```blade
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <link rel="preconnect" href="https://connect.facebook.net" crossorigin>
```

- [ ] **Step 4: Hero preload (best-effort)** — if the home hero's first slide image URL is available to the layout/home view, add (in the home page `@push` or the hero partial head-push) a preload. If the hero is a `@push('styles')`-able section, add in `home/index.blade.php`:

```blade
@push('styles')
    @php($firstHero = ($heroBanners->first() ?? null))
    @if($firstHero && ($firstHero->image ?? null))
        <link rel="preload" as="image" href="{{ \Illuminate\Support\Str::startsWith($firstHero->image, 'http') ? $firstHero->image : asset($firstHero->image) }}">
    @endif
@endpush
```

Confirm the hero banner variable name + image field by reading `home/index.blade.php` + the hero partial; adapt. If the structure makes this unreliable, skip the preload and note it (preconnect + CLS are the guaranteed wins).

- [ ] **Step 5: CLS reservations** — append to `public/website/assets/css/style.css` (reserve space so async images/content don't shift; match real class names found by reading the partials):

```css
/* SEO/CLS: reserve space for async media to prevent layout shift */
.product_item img, .product-card-img img { aspect-ratio: 1 / 1; object-fit: cover; }
.banner_slider_2, .banner_2_add { aspect-ratio: 16 / 7; }
.page_banner { min-height: 180px; }
#offcanvasRightBody { min-height: 200px; }
```

Read the storefront partials to confirm these selectors exist; adjust to the real class names (hero slider, breadcrumb banner `.page_banner`, mini-cart body). Add a `[data-theme="dark"]` parity rule only if these introduce theme-sensitive properties (these don't — sizing only — so none needed).

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/OnPageSeoTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php Modules/Ecommerce/resources/views/storefront/pages/home/index.blade.php public/website/assets/css/style.css Modules/Ecommerce/tests/Feature/OnPageSeoTest.php
git commit -m "feat(seo): analytics preconnect + hero preload + CLS reservations"
```

(Adjust the staged hero-partial path if you edited the partial instead of `home/index.blade.php`.)

---

## Task 4: Remove grep-confirmed-unused assets

**Files:**
- Modify: `storefront/layouts/master.blade.php` (only if unused libs are actually loaded there)

- [ ] **Step 1: Confirm usage** — for each candidate, grep the WHOLE repo for references (excluding the asset file itself and the layout's own `<script>`/`<link>` line):

```bash
for lib in youtube-background animated_barfiller multiple-image-video Font-Awesome.js; do echo "=== $lib ==="; grep -rln "$lib" Modules public/website/assets/js public/website/assets/css resources --include="*.blade.php" --include="*.js" | grep -v "assets/js/$lib\|assets/css/$lib"; done
```

- [ ] **Step 2: Remove only the confirmed-unused `<script>`/`<link>` tags** from `master.blade.php`. If a candidate is NOT actually loaded in the layout (the audit noted some files exist but may not be `<script>`-included), there's nothing to remove — skip it. Do NOT delete the asset files themselves (out of scope; just stop loading them). If a lib has ANY reference, keep it.

- [ ] **Step 3: Verify** — `php artisan test Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php Modules/Ecommerce/tests/Feature/OnPageSeoTest.php` still pass; load the storefront home (if a dev server is available) and confirm no JS console errors from the removal. If unsure a removal is safe, leave the tag and report it as DONE_WITH_CONCERNS.

- [ ] **Step 4: Commit** (only if something was removed)

```bash
git add Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php
git commit -m "perf(seo): stop loading unused storefront JS libraries"
```

If nothing was safely removable, skip the commit and report which candidates were kept and why.

---

## Task 5: Full regression

- [ ] **Step 1: Run the SEO + tracking suites**

Run:
```bash
php artisan test Modules/Ecommerce/tests/Feature/OnPageSeoTest.php Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php Modules/Ecommerce/tests/Feature/CanonicalTest.php Modules/Ecommerce/tests/Feature/SlugRedirectTest.php Modules/Ecommerce/tests/Feature/SitemapGenerateTest.php Modules/Ecommerce/tests/Unit/SeoTest.php Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php Modules/Ecommerce/tests/Unit/CleanCanonicalTest.php Modules/Ecommerce/tests/Unit/TrackingServiceTest.php
```
Expected: all pass.

- [ ] **Step 2: Manual QA (document — can't be headless-tested):**
- [ ] Each page type renders with exactly one visible/logical `<h1>` and the design looks unchanged (breadcrumb title still styled).
- [ ] Lighthouse: SEO ≥95, no "image elements do not have explicit width/height" / CLS-from-images warnings; hero preload present; LCP not lazy-loaded.
- [ ] No JS console errors after any unused-lib removal.

---

## Self-Review notes (addressed)
- **Spec coverage:** H1 (T1), images lazy/dims/alt + buy-now alt (T2), preconnect + hero preload + CLS (T3), unused-lib removal (T4), regression (T5). Risky JS-defer/CSS-consolidation/conditional-range_slider explicitly deferred to Plan 4.5 per spec.
- **Correctness guards:** LCP/hero images stay eager (only below-the-fold gets `loading="lazy"`); heading tag swaps preserve CSS classes so design is unchanged; CLS rules are sizing-only (no dark-mode divergence); unused-lib removal is grep-gated and conservative.
- **Type/selector consistency:** `.bp-breadcrumb-title` / `.bp-visually-hidden` defined in T1 CSS and used in T1 markup; CLS selectors flagged to confirm against real partials before relying on them.
