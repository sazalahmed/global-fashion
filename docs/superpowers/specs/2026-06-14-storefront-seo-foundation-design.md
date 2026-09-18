# Storefront SEO — Plan 1: Pipeline + Admin-Managed Content SEO + Structured Data

**Status:** Approved design — ready for implementation plan
**Date:** 2026-06-14
**Scope:** Phases 1–3 of `docs/STOREFRONT_SEO_PLAN.md` (which remains the exhaustive page-by-page reference). This is the first of three sequential plans:
- **Plan 1 (this):** SEO pipeline + admin-managed content SEO + JSON-LD structured data.
- **Plan 2 (later):** canonical/robots/pagination, URL normalization, sitemap + robots.txt, 404/410 + slug-301 + env noindex.
- **Plan 3 (later):** on-page H1/heading/image fixes, Core Web Vitals, final Rich-Results/Lighthouse QA.

> The full page-by-page tag/JSON-LD specification lives in `docs/STOREFRONT_SEO_PLAN.md` §D0–D10 and §Guardrails. This spec defines the **architecture and Plan-1 build scope**; the implementation plan will cite the page matrix from that doc.

---

## 1. Confirmed decisions
| Decision | Choice |
|---|---|
| Structure | Decompose into 3 sequential ship-able plans; build Plan 1 first |
| Admin control | **Full** admin management (per-entity fields + static-page SEO + site-defaults page + snippet preview/counters) |
| Sitemap | Generated static file + scheduler (Plan **2**, not here) |
| Analytics in head | **Excluded** — GTM/Pixel already shipped via Core tracking components; `seo-head` must coexist, never re-add analytics. No GTM/GA keys in SEO Settings. |
| Site-default storage | `EcommerceSetting` (existing key/value store) |
| Static-page SEO storage | new `seo_pages` table |
| Breadcrumb JSON-LD source | structured breadcrumb array passed from controller → `Seo` object (NOT parsed from rendered HTML) |
| LocalBusiness geo | Branch has no lat/lng columns → emit address/phone/hours only (no `geo`) |

---

## 2. Current state (audit confirmed)
- **Head** (`storefront/layouts/master.blade.php` L9–25): ad-hoc `@yield('title')` + `@hasSection('meta_description'/'og_image')`; partial OG; **no** canonical, robots meta, Twitter, or JSON-LD.
- **SEO fields exist but unused:** `products.seo_title/seo_description` (Product form already has an "eCommerce & SEO" section, L393+ create / L396+ edit), `categories.meta_title/meta_description` (Category form has "SEO Information" section). **Never output to the head.**
- **Blog:** `BlogPost` has no SEO columns and no SEO form fields.
- **No** `Seo` pipeline classes, **no** JSON-LD anywhere.
- `EcommerceSetting`: `get(key,default)` / `set(key,value)` over `ecommerce_settings` (key unique). Branch model: name/address/city/district/phone/email/opening_time/closing_time (no geo). `ProductReview`: `approvedReviews()`, integer `rating`, `is_approved`. Storefront routes confirmed: `storefront.home`, `storefront.shop.index`, `storefront.shop.show`, `storefront.flash-deals`, `storefront.category.index`, `storefront.category.show`, `storefront.blog.index`, `storefront.blog.show`.

---

## 3. Architecture (Phase 1)

### 3.1 `Seo` value object — `Modules/Ecommerce/app/Support/Seo.php`
An immutable-ish fluent builder the controllers populate and the layout renders:
- Fields: `title`, `description`, `canonical`, `robots` (default `index,follow`), `image` (OG/Twitter), `type` (`website`|`product`|`article`), `schema` (array of JSON-LD graph nodes), `breadcrumbs` (array of `['name'=>, 'url'=>null]`).
- Fluent setters: `title()`, `description()`, `canonical()`, `robots()`, `image()`, `type()`, `addSchema(array)`, `breadcrumbs(array)`.
- **Resolution helper** `resolve($adminValue, $derivedValue, $defaultKey)` → first non-empty of admin → derived → site default (`EcommerceSetting`).
- **Central formatting** at render time: title formatted as `{title} {sep} {site}` (sep + site from settings), title truncated to ~60 chars, description to ~160. Absolute URLs for canonical/image.
- A factory `Seo::make()` seeds site-wide defaults (default title/description/image, site name, separator) so a bare `Seo` is never blank.

### 3.2 `SeoSchemaService` — `Modules/Ecommerce/app/Services/SeoSchemaService.php`
Pure builders returning JSON-LD arrays (no echo):
- `organization()`, `website()` (with `SearchAction` → `/shop?q=`) — the **global** graph.
- `product(Product, $canonical)` → `Product`+`Offer` (or `AggregateOffer` for variant price ranges), `availability`, `itemCondition`, `brand`, `sku`, `aggregateRating` when `approvedReviews` exist.
- `breadcrumbList(array $items)`.
- `blogPosting(BlogPost, $canonical)`.
- `collectionPage($name, $canonical, iterable $items)` → `CollectionPage`+`ItemList`.
- `localBusiness(Branch)` → `Store`/`LocalBusiness` (name/address/telephone/openingHours; no geo).
- Consistency rule: every emitted field must match visible on-page data (price, availability, rating).

### 3.3 `seo-head` partial — `Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php`
Renders from `$seo`: `<title>`, `<meta description>`, `<link canonical>`, `<meta robots>`, `theme-color`, `manifest`, `google-site-verification`, full Open Graph (title/description/image/url/type/site_name/locale) + product/article OG extras, Twitter `summary_large_image`, and each JSON-LD node via `<script type="application/ld+json">` using `json_encode(..., JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)`. Meta content escaped with `e()`.

### 3.4 Layout integration
`master.blade.php`: replace the ad-hoc L9–25 head block with `@include('ecommerce::storefront.partials.seo-head')` (rendered from a shared `$seo`). Keep `@stack('styles')`, the `<x-core::tracking-head />` analytics include, favicon, and `breadcrumb_title` (the visible breadcrumb is separate from JSON-LD). Provide a `View::share`/composer fallback `$seo = Seo::make()` so any page lacking an explicit `$seo` still renders complete defaults.

---

## 4. Admin-managed SEO (Phase 2)

| Entity | Storage | Work |
|---|---|---|
| Product | `seo_title`, `seo_description` (exist) | **wire to output**; add `seo_image` (migration + form field in existing SEO section) |
| Category | `meta_title`, `meta_description` (exist) | **wire to output**; add `meta_image` (migration + form field in existing SEO section) |
| Blog post | none | **add** `seo_title`, `seo_description`, `seo_image` (migration + fillable + new SEO section in `blog-post-create/edit.blade.php`) |
| Static pages | new `seo_pages` table (`key` unique, `title`, `description`, `image`, `robots`) | seed keys: `home`, `shop`, `categories`, `blog`, `flash-deals`; edited in SEO Settings page |
| Site defaults | `EcommerceSetting` keys | `seo_site_name`, `seo_title_separator` (default `\|`), `seo_default_title`, `seo_default_description`, `seo_default_image`, `seo_twitter_handle`, `seo_org_name`, `seo_org_logo`, `google_site_verification`, social `sameAs` URLs (reuse existing social URL settings if present) |

- **SEO Settings admin page:** a new section/tab in `ecommerce::settings` (saved via existing `EcommerceController@settingsUpdate` → `EcommerceService::updateSettings`) for site defaults + the per-static-page SEO rows.
- **Form UX:** each SEO section (product/category/blog/static) shows a **Google snippet preview** (title/URL/description) and **live char counters** (title ~60, desc ~160) via a small shared JS helper + placeholder showing the derived fallback when empty.
- **Controllers populate `$seo`:** `HomeController`, `ShopController@index/@show/@flashDeals`, `StorefrontCategoryController@index/@show`, `BlogController@index/@show` each build a `Seo` from `admin field → derived → default` and pass it to the view (or `View::share` in a controller middleware/trait). Each also passes a structured `breadcrumbs` array.

---

## 5. Structured data (Phase 3)
Emitted via `$seo->addSchema(...)` → `seo-head`:
- **Global (all pages):** `Organization` + `WebSite`(+`SearchAction`).
- **Product detail:** `Product`+`Offer`/`AggregateOffer` (+`aggregateRating`/`review` when approved reviews exist) + `BreadcrumbList`. OG `product:price:amount`/`:currency`=BDT + `og:availability` when `type=product`.
- **Blog post:** `BlogPosting` + `BreadcrumbList`.
- **Listings (shop, category show, flash-deals, category index, blog index):** `CollectionPage`+`ItemList` + `BreadcrumbList`.
- **LocalBusiness:** `Store`/`LocalBusiness` from active branches (home page).
- Prices = plain numeric BDT; all fields match on-page content.

---

## 6. Out of scope for Plan 1 (explicitly deferred)
- Canonical/pagination/robots-directive logic, URL normalization, tracking-param stripping → **Plan 2**.
- `sitemap.xml` generation + scheduler + `robots.txt` → **Plan 2**.
- 404/410 for inactive entities, slug-change 301s, env noindex guard → **Plan 2**.
- One-`<h1>`-per-page fixes, image lazy/dimensions/WebP, Core Web Vitals, final QA → **Plan 3**.
- Any analytics/GTM/GA wiring (already shipped separately).
> Plan 1 still emits a self-referencing `canonical` and a `robots` default (`index,follow`) per page so the head is complete; the *smart* canonical/robots logic (filter stripping, noindex states) is Plan 2.

---

## 7. Correctness / security
- **Escaping:** meta via `e()`; JSON-LD via `json_encode` (data context, CSP-safe — not subject to `script-src`). Never `{!! !!}` for user input.
- **Resolution order** strictly admin → derived → site default; no page ever blank.
- **Lengths:** title ≤ ~60, description ≤ ~160, truncated centrally in `Seo`.
- **Absolute URLs** for canonical/OG/schema via `url()`.
- **Coexistence:** `seo-head` must not duplicate or break the existing `<x-core::tracking-head />` analytics; staff-exclusion of analytics is unchanged.

---

## 8. Files
**New:** `Modules/Ecommerce/app/Support/Seo.php`; `Modules/Ecommerce/app/Services/SeoSchemaService.php`; `storefront/partials/seo-head.blade.php`; migrations (blog `seo_*`, products `seo_image`, categories `meta_image`, `seo_pages` table + model); SEO Settings view section + a small snippet-preview JS helper; seeder for `seo_pages` keys.
**Modified:** `storefront/layouts/master.blade.php` (head → seo-head); the 7 storefront controllers (+ a shared trait/middleware to build & share `$seo`); product/category/blog admin form views + their FormRequests (validate new image/seo fields); `ecommerce::settings` view + (no controller change needed — `updateSettings` already persists arbitrary keys, verify).

---

## 9. Phasing (within Plan 1)
1. **Pipeline:** `Seo` object + `SeoSchemaService` (global Organization/WebSite) + `seo-head` partial + layout integration + site-default factory + `View::share` fallback. Verify every page renders a complete head with defaults.
2. **Admin fields:** migrations (blog seo_*, product seo_image, category meta_image, seo_pages) + models/fillable + form sections + FormRequest validation + SEO Settings page + snippet-preview/counter JS.
3. **Controllers populate `$seo`** (admin→derived→default) + structured breadcrumbs for all 8 storefront pages.
4. **JSON-LD:** wire `SeoSchemaService` builders per page (Product, BlogPosting, CollectionPage/ItemList, BreadcrumbList, LocalBusiness).
5. **QA:** feature tests asserting each page type renders `<title>`, meta description, canonical, OG/Twitter, and ≥1 valid JSON-LD block; admin SEO field changes the rendered head; fallbacks fill gaps.
