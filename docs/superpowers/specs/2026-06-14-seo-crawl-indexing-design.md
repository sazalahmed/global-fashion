# SEO Plan 3 — Crawl & Indexing (design)

**Status:** Approved (audit-driven) — ready for implementation plan
**Date:** 2026-06-14
**Branch:** continues on `feat/storefront-seo-foundation`
**Source:** `docs/SEO_AUDIT_AND_PLAN.md` (issues 5, 6, 13) + `docs/STOREFRONT_SEO_PLAN.md` Phases 4–5, 9

Make Google crawl the right URLs and consolidate duplicates: clean canonicals, an XML sitemap, a proper robots.txt, and 301s for changed slugs.

## Scope (in)
1. **Clean canonical + empty-search noindex.**
   - Filtered/sorted/tracking URLs must canonicalize to a clean base. On `ShopController@index` and `StorefrontCategoryController@show`, set `$seo->canonical(...)` to the route base **keeping only** `category` (shop) and `page` (when >1); **drop** `sort`, `min_price`, `max_price`, `q`, and all tracking params (`utm_*`, `fbclid`, `gclid`, `ref`).
   - Helper `cleanCanonical(Request $request, string $baseUrl, array $keep = []): string` on the existing `BuildsSeo` trait — rebuilds the URL from `$baseUrl` + only the `$keep` query params present (and `page` only when >1).
   - When `request('q')` is present **and** the result set is empty → `$seo->robots('noindex,follow')` (thin/duplicate). (When `q` is present with results, keep indexable but canonical drops `q`… **decision:** search-result pages canonical to `/shop` base and are `noindex,follow` whenever `q` is present — internal search results shouldn't be indexed. So: any `q` present → `noindex,follow` + canonical `/shop`.)
2. **robots.txt** (`public/robots.txt`, committed/static, correct content):
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
   Sitemap: {APP_URL}/sitemap.xml
   ```
   The `{APP_URL}` is rendered at build/generation time (the sitemap command rewrites the `Sitemap:` line to the configured `app.url`), or a sane absolute default is committed. (Non-prod indexing is already prevented by the Plan-2 meta-noindex env guard, so robots.txt stays index-friendly.)
3. **XML sitemap** — `php artisan sitemap:generate` (`Modules\Ecommerce\app\Console\GenerateSitemap`) writes `public/sitemap.xml` with absolute `<loc>` + `<lastmod>` (updated_at, W3C) for: home, shop, categories index, blog index, flash-deals, each **active** category, each **active+visible** product, each **published** blog post. Excludes cart/checkout/account/auth. The file is a **generated artifact** → gitignored (`/public/sitemap.xml`, `/public/sitemap*.xml`). Scheduled **daily** (+ run on deploy). If the URL set exceeds 50k, log a warning (single-file cap is acceptable for now).
4. **Slug-change 301s** — a `slug_histories` table (`model_type`, `model_id`, `old_slug`, unique on `model_type`+`old_slug`); on `updating`, when a Product/Category/BlogPost `slug` changes, record the old slug; the storefront show controllers, on a slug miss, look up `slug_histories` and **301-redirect** to the current slug before `abort(404)`. Implemented via a small `HasSlugHistory` trait (records on update) + a lookup helper used in the 3 show controllers.

## Scope (out — later)
- Host/HTTPS/www/case normalization middleware (audit HIGH) — **deferred to a small Plan 3.5** (production-only, hard to test in this env; lower urgency than canonical/sitemap). Noted, not built here.
- H1/images/CWV performance → Plan 4. hreflang/i18n → Plan 5.
- 404 status codes — already correct (no work).

## Approach / correctness
- Canonical/sitemap URLs absolute via `route()`/`url()`; strip tracking params from canonical.
- `BuildsSeo::cleanCanonical` is pure/unit-testable; the sitemap command is feature-testable (assert file written + contains product/category URLs + excludes cart).
- Slug-history: 301 (permanent) only when an old slug uniquely resolves to a current record; otherwise 404.
- Reuse Plan-1 `Seo`; Plan-2 env-noindex guard still applies at render.
- `.gitignore`: add `/public/sitemap.xml` and `/public/sitemap*.xml`. Do NOT gitignore robots.txt (committed static).

## Files
**New:** `Modules/Ecommerce/app/Console/GenerateSitemap.php`; `Modules/Ecommerce/app/Models/SlugHistory.php` + migration `..._create_slug_histories_table.php`; `Modules/Ecommerce/app/Support/HasSlugHistory.php` (trait); tests (`SitemapGenerateTest`, `CanonicalTest`/extend `SeoQuickWinsTest`, `SlugRedirectTest`).
**Modified:** `Modules/Ecommerce/app/Support/BuildsSeo.php` (cleanCanonical); `ShopController@index/@show`, `StorefrontCategoryController@show`, `BlogController@show` (canonical + slug-redirect); `Modules/Product/app/Models/Product.php`, `Modules/Category/app/Models/Category.php`, `Modules/Ecommerce/app/Models/BlogPost.php` (use `HasSlugHistory`); `public/robots.txt`; `.gitignore`; the scheduler (`routes/console.php` or `bootstrap/app.php`).

## QA
- Unit: `cleanCanonical` strips sort/price/utm, keeps category, keeps page>1.
- Feature: `/shop?sort=x&utm_source=fb` canonical = `/shop`; `/shop?q=foo` is `noindex,follow`; `sitemap:generate` writes valid XML with product/category/blog URLs and no cart/checkout; changed slug → 301 to new slug; unknown slug → 404.
- Manual: validate sitemap.xml + robots.txt; submit sitemap in Search Console (post-deploy).
