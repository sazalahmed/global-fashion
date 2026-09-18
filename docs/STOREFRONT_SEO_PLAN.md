# Storefront SEO Plan (Google-standard, admin-managed)

> **Goal:** bring the storefront to Google-standard SEO — correct, unique, admin-editable
> `title` / `meta description` / `canonical` / `robots` / Open Graph / Twitter on every page,
> full schema.org **JSON-LD** (Organization, WebSite+SearchAction, Product+Offer+Rating,
> BreadcrumbList, Article, CollectionPage/ItemList), an XML **sitemap**, clean canonical/
> pagination handling, one `<h1>` per page, and image optimization.
>
> **Admin-managed:** SEO title/description (and OG image) are editable from the admin panel —
> per product, per category, per blog post, per static page, plus site-wide defaults.
>
> Phase-wise; ship in order. §A–C are reference (audit, architecture, data model). §1+ is the build.

---

## A. Audit summary — current state

**Working:** slug URLs (`/shop/{slug}`, `/categories/{slug}`, `/blog/{slug}`), responsive viewport,
dynamic `<title>` via `@yield`, most product/category images have `alt`, `robots.txt` exists, favicon.

**Gaps (priority):**
1. **No JSON-LD** anywhere (no Product/Breadcrumb/Organization/Article/WebSite schema).
2. **H1 problems** — product/blog use `<h2>` for the name/title; home, shop, category-show have no `<h1>`.
3. **SEO fields stored but unused** — `products.seo_title/seo_description`, `categories.meta_title/meta_description` exist but are **never output**.
4. **No `<link rel="canonical">`** — filtered/sorted/paginated shop & category URLs create duplicate-content variants.
5. **No XML sitemap**; `robots.txt` is empty of a `Sitemap:` line.
6. **Blog posts have no SEO fields** at all.
7. **No OG image** is ever set (the `@section('og_image')` hook is unused); **no Twitter cards**; no `og:url`, `og:site_name`.
8. Listing/index pages (home, shop, category index, blog index) have **generic hardcoded titles** and **no meta description**.
9. Images: no `loading="lazy"` / `width`/`height`; buy-now modal img has empty `alt`.
10. No `og:url`, `theme-color`, manifest link, analytics/GTM hook.

---

## B. Architecture — one SEO pipeline

A single, DRY pipeline so every page is consistent and Google-standard.

```
App/Modules\Ecommerce\app\Support\Seo  (a small value object / builder)
  ->title(), ->description(), ->canonical(), ->robots(), ->image(), ->type()
  ->schema([... JSON-LD graph ...])
```

- **Resolution order for title/description/image** (first non-empty wins):
  `admin SEO field (entity) → derived from content → site-wide default (settings)`.
- Controllers build a `Seo` object and pass it to the view (via a shared `seo` variable or a
  `View::share`/composer). The **master layout** renders the full, correct `<head>` from it.
- Title format: `{page title} {separator} {site name}` (separator + site name from settings),
  with a hard cap (~60 chars title, ~160 description) applied centrally.
- A `<x-seo::head>` (or `@include('partials.seo-head')`) outputs: title, description, canonical,
  robots, OG (title/description/image/url/type/site_name/locale), Twitter card, and the JSON-LD.

This replaces the current ad-hoc `@section('meta_description')`/`@section('og_image')` approach
with a controller-driven, always-complete head.

---

## C. Data model — where admin-managed SEO lives

| Entity | SEO storage | Action |
|---|---|---|
| **Product** | `seo_title`, `seo_description` (exist) | **wire to output** + add `seo_image` (nullable) for OG override; surface in product form |
| **Category** | `meta_title`, `meta_description` (exist) | **wire to output** + optional `meta_image`; surface in category form |
| **Blog post** | none | **add** `seo_title`, `seo_description`, `seo_image` columns + form fields |
| **Static pages** (home, shop, categories index, blog index, flash-deals) | none | **add** a `seo_pages` table (`key`, `title`, `description`, `image`, `robots`) + a small admin editor |
| **Site-wide defaults** | `EcommerceSetting` (key/value) | add SEO keys: `seo_site_name`, `seo_title_separator`, `seo_default_title`, `seo_default_description`, `seo_default_image`, `seo_twitter_handle`, `seo_org_name/logo`, `google_site_verification`, `gtm_id`/`ga_id`, social profile URLs (for `sameAs`) |

All admin-managed via existing module screens (Products, Categories, Blog) + a new **eCommerce → SEO Settings** page for site-wide defaults and static-page SEO.

---

# D. Page-by-page specification (review this)

Notation: `{site}` = SEO site name (settings), `{sep}` = title separator (default `|`),
`{url}` = absolute current URL, `{base}` = clean URL without filter/sort query, BDT prices are
plain numbers. Every value follows **admin field → derived → site default**. All pages also emit
the **Global** block below.

---

## D0. Global — emitted on EVERY page (via `seo-head`)

**Tags (defaults; pages override title/description/canonical/og:image/robots):**
```html
<title>{page title} {sep} {site}</title>
<meta name="description" content="{page description}">
<link rel="canonical" href="{canonical}">
<meta name="robots" content="index,follow">           {{-- pages may set noindex --}}
<meta name="theme-color" content="{theme color}">
<link rel="manifest" href="/manifest.json">
<meta name="google-site-verification" content="{settings}">

<!-- Open Graph -->
<meta property="og:site_name" content="{site}">
<meta property="og:locale" content="en_US">
<meta property="og:type" content="website">          {{-- product/article override --}}
<meta property="og:title" content="{page title}">
<meta property="og:description" content="{page description}">
<meta property="og:url" content="{canonical}">
<meta property="og:image" content="{og image | site default}">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@{settings handle}">
<meta name="twitter:title" content="{page title}">
<meta name="twitter:description" content="{page description}">
<meta name="twitter:image" content="{og image | site default}">
```

**Global JSON-LD `@graph` (Organization + WebSite + SearchAction):**
```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "{base}/#organization",
      "name": "{settings: org name | site}",
      "url": "{APP_URL}",
      "logo": "{settings: org logo absolute}",
      "sameAs": ["{facebook}", "{instagram}", "{youtube}", "..."]
    },
    {
      "@type": "WebSite",
      "@id": "{base}/#website",
      "url": "{APP_URL}",
      "name": "{site}",
      "publisher": { "@id": "{base}/#organization" },
      "potentialAction": {
        "@type": "SearchAction",
        "target": { "@type": "EntryPoint", "urlTemplate": "{APP_URL}/shop?q={search_term_string}" },
        "query-input": "required name=search_term_string"
      }
    }
  ]
}
```

---

## D1. Home — `/`
- **title:** `Home` (or settings `seo_default_title`) → e.g. `{site} — Online Shopping in Bangladesh`
- **description:** static-page SEO (admin) → settings default
- **canonical:** `{APP_URL}/`
- **robots:** `index,follow`
- **og:type:** `website` · **og:image:** static-page image → site default
- **H1:** one visible `<h1>` (e.g. the hero headline or a visually-hidden `<h1>{site}</h1>`)
- **JSON-LD:** Global only (Organization + WebSite/SearchAction). No extra type.
- **Other:** ensure hero slides carry a single h1, not multiple.

## D2. Shop listing — `/shop` (+ `?q=&category=&sort=&min_price=&max_price=&page=`)
- **title:** `Shop` / `Search: {q}` when searching / `{category name}` when category filter
- **description:** static-page SEO → settings default (or `Search results for "{q}"`)
- **canonical:** **`{base}`** — drop `sort`,`min_price`,`max_price`; keep `category`; keep `page` only when >1
- **robots:** `index,follow`; **`noindex,follow`** when `q` present or zero results (thin/duplicate)
- **og:type:** `website`
- **H1:** `<h1>Shop</h1>` (or `Search results for "{q}"`)
- **JSON-LD:** Global **+** `CollectionPage` containing `ItemList` of the products on the page:
```json
{
  "@type": "CollectionPage",
  "@id": "{canonical}#collection",
  "name": "Shop",
  "url": "{canonical}",
  "mainEntity": {
    "@type": "ItemList",
    "numberOfItems": 24,
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "url": "{product url}", "name": "{product name}", "image": "{product image}" }
    ]
  }
}
```
- **Other:** product cards already have alt; add `loading="lazy"` + dimensions. Add `BreadcrumbList` (Home › Shop).

## D3. Product detail — `/shop/{slug}`
- **title:** `product.seo_title` → `product.name` → e.g. `{name} {sep} {site}`
- **description:** `product.seo_description` → `Str::limit(strip_tags(description),160)`
- **canonical:** `{APP_URL}/shop/{slug}`
- **robots:** `index,follow` (active) ; `noindex` if inactive/out-of-catalog
- **og:type:** `product` · **og:image:** `product.seo_image` → primary product image
- **twitter:card:** `summary_large_image`
- **H1:** **change `<h2>` → `<h1>`** for the product name (one h1); price stays a styled element, related = `<h2>`
- **JSON-LD:** Global **+** `Product` + `BreadcrumbList`:
```json
[
  {
    "@type": "Product",
    "@id": "{canonical}#product",
    "name": "{product.name}",
    "image": ["{primary image}", "{gallery images...}"],
    "description": "{seo_description | description}",
    "sku": "{product.sku}",
    "brand": { "@type": "Brand", "name": "{brand name}" },
    "category": "{category name}",
    "offers": {
      "@type": "Offer",
      "url": "{canonical}",
      "priceCurrency": "BDT",
      "price": "{effective price as number}",
      "availability": "https://schema.org/{InStock|OutOfStock}",
      "itemCondition": "https://schema.org/NewCondition"
    },
    "aggregateRating": {            // only when approved reviews exist
      "@type": "AggregateRating",
      "ratingValue": "{avg}", "reviewCount": "{count}"
    },
    "review": [                     // optional, a few approved reviews
      { "@type": "Review", "author": {"@type":"Person","name":"{name}"},
        "reviewRating": {"@type":"Rating","ratingValue":"{n}"}, "reviewBody": "{text}" }
    ]
  },
  { "@type": "BreadcrumbList", "itemListElement": [
      {"@type":"ListItem","position":1,"name":"Home","item":"{APP_URL}/"},
      {"@type":"ListItem","position":2,"name":"{category}","item":"{category url}"},
      {"@type":"ListItem","position":3,"name":"{product.name}"}
  ]}
]
```
- **Variable products:** if variants have distinct prices, use `offers` as `AggregateOffer`
  (`lowPrice`/`highPrice`/`offerCount`, `priceCurrency: BDT`).
- **Other:** fix gallery image alts (already use `alt_text ?? name`); buy-now modal img needs a real `alt`.

## D4. Category index — `/categories`
- **title:** `Categories` (static-page SEO) → default
- **description:** static-page SEO → default
- **canonical:** `{APP_URL}/categories`
- **robots:** `index,follow`
- **H1:** `<h1>Categories</h1>`; each category tile name → `<h2>`/`<h3>` (not h1)
- **JSON-LD:** Global **+** `CollectionPage` + `ItemList` of categories (name + url + image) + `BreadcrumbList` (Home › Categories)

## D5. Category show — `/categories/{slug}`
- **title:** `category.meta_title` → `category.name`
- **description:** `category.meta_description` → `Str::limit(strip_tags(category.description),160)` → default
- **canonical:** **`{base}`** (clean category URL; drop sort/price filters; keep `page` when >1)
- **robots:** `index,follow`
- **og:image:** `category.meta_image` → category image → site default
- **H1:** add `<h1>{category.name}</h1>` in main content (currently only in breadcrumb)
- **JSON-LD:** Global **+** `CollectionPage` (name = category) + `ItemList` of products + `BreadcrumbList`
  (Home › [parent category] › {category})

## D6. Blog index — `/blog`
- **title:** `Blog` (static-page SEO) → default
- **description:** static-page SEO → default
- **canonical:** `{APP_URL}/blog` (+ `page` when >1)
- **robots:** `index,follow`
- **H1:** `<h1>Blog</h1>` (the dedicated index page currently lacks one)
- **JSON-LD:** Global **+** `Blog` + `ItemList` of posts (headline + url + image) + `BreadcrumbList`

## D7. Blog post — `/blog/{slug}`
- **title:** `post.seo_title` (new) → `post.title`
- **description:** `post.seo_description` (new) → `Str::limit(strip_tags(post.excerpt|content),160)`
- **canonical:** `{APP_URL}/blog/{slug}`
- **robots:** `index,follow` (published) ; `noindex` if draft/preview
- **og:type:** `article` · **og:image:** `post.seo_image` (new) → featured image
- **H1:** **change `<h2>` → `<h1>`** for the post title
- **JSON-LD:** Global **+** `BlogPosting` + `BreadcrumbList`:
```json
[
  {
    "@type": "BlogPosting",
    "@id": "{canonical}#article",
    "headline": "{post.title}",
    "image": "{featured image}",
    "datePublished": "{published_at ISO8601}",
    "dateModified": "{updated_at ISO8601}",
    "author": { "@type": "Person", "name": "{author | site}" },
    "publisher": { "@id": "{base}/#organization" },
    "mainEntityOfPage": "{canonical}",
    "description": "{seo_description | excerpt}"
  },
  { "@type": "BreadcrumbList", "itemListElement": [
      {"@type":"ListItem","position":1,"name":"Home","item":"{APP_URL}/"},
      {"@type":"ListItem","position":2,"name":"Blog","item":"{APP_URL}/blog"},
      {"@type":"ListItem","position":3,"name":"{post.title}"}
  ]}
]
```

## D8. Flash deals — `/flash-deals`
- **title:** `Flash Deals` (static-page SEO) → default · **description:** default/admin
- **canonical:** `{APP_URL}/flash-deals` · **robots:** `index,follow`
- **H1:** `<h1>Flash Deals</h1>`
- **JSON-LD:** Global **+** `CollectionPage` + `ItemList` of deal products + `BreadcrumbList`

## D9. Transactional / account pages — cart, checkout, login, register, wishlist, compare, customer/*
- **robots:** **`noindex,nofollow`** (not for search) · **canonical:** self
- **title/description:** simple, not optimized · **JSON-LD:** Global only
- **Excluded from sitemap.**

---

## D10. Per-page change matrix (quick reference)

| Page | title source | description source | canonical | robots | og:type | H1 change | JSON-LD added |
|---|---|---|---|---|---|---|---|
| Home | static/default | static/default | self | index | website | ensure 1 h1 | — (global) |
| Shop | Shop/Search/cat | static/default | base (drop sort/price) | index / noindex on `q` | website | add h1 | CollectionPage+ItemList, Breadcrumb |
| Product | seo_title→name | seo_description→desc | self | index | product | h2→h1 | Product+Offer(+Rating), Breadcrumb |
| Categories | static/default | static/default | self | index | website | add h1 | CollectionPage+ItemList, Breadcrumb |
| Category | meta_title→name | meta_description→desc | base | index | website | add h1 | CollectionPage+ItemList, Breadcrumb |
| Blog index | static/default | static/default | self | index | website | add h1 | Blog+ItemList, Breadcrumb |
| Blog post | seo_title→title | seo_description→excerpt | self | index | article | h2→h1 | BlogPosting, Breadcrumb |
| Flash deals | static/default | static/default | self | index | website | add h1 | CollectionPage+ItemList, Breadcrumb |
| Cart/checkout/account | basic | basic | self | **noindex** | website | — | — (global) |

---

# Phase-by-phase build

Dependency chain: **1 → 2 → 3 → 4 → 5 → 6 → 7**.

## Phase 1 — SEO pipeline + Google-standard `<head>`
**Objective:** every page emits a complete, correct, unique head — driven centrally.

- Create the `Seo` builder/value object + a `seo-head` partial/component.
- Rewrite `storefront/layouts/master.blade.php` `<head>` to render from `$seo`:
  `<title>`, `<meta description>`, `<link rel=canonical>`, `<meta robots>`, full Open Graph
  (`og:title/description/image/url/type/site_name/locale`), Twitter (`summary_large_image`),
  `theme-color`, manifest link, `google-site-verification`, GTM/GA snippet (from settings).
- Apply central title/description length caps + title-format (`… | Site Name`).
- Provide a site-wide **default** title/description/OG image (from settings) so no page is ever blank.

**Acceptance:** view-source on every storefront page shows title, description, canonical (self URL), robots, OG + Twitter tags; defaults fill any gaps.

## Phase 2 — Admin-managed SEO fields
**Objective:** admins control SEO title/description/image per entity + site-wide.

- **Products:** wire `seo_title`/`seo_description` into the `Seo` builder (fallback to name/description); add `seo_image`; ensure the product form exposes these (add the SEO section if missing).
- **Categories:** wire `meta_title`/`meta_description`; add to category form (alongside the existing meta fields).
- **Blog:** migration adding `seo_title`/`seo_description`/`seo_image`; add form fields; wire output.
- **Static pages + site defaults:** `seo_pages` table + **eCommerce → SEO Settings** admin page (site name, separator, default title/description/OG image, verification codes, GTM/GA, social URLs, and per-static-page title/description/robots).
- Each storefront controller (`HomeController`, `ShopController@index/show`, `StorefrontCategoryController`, `BlogController`) populates the `Seo` object from these sources.
- **Admin SEO UX:** in each SEO form section show a **Google snippet preview** (rendered title/URL/description) and **live character counters** (title ~60, description ~160) so editors see truncation; placeholder text shows the derived fallback when the field is empty.

**Acceptance:** editing a product/category/blog/static-page SEO field changes the rendered head; empty fields fall back to derived → site default; admin shows snippet preview + counters.

## Phase 3 — Structured data (JSON-LD, schema.org)
**Objective:** rich-result-eligible markup on every relevant page.

- **Global (all pages):** `Organization` (name, logo, `sameAs` social URLs) + `WebSite` with `SearchAction` (wired to `/shop?q=`).
- **Product detail:** `Product` with `name`, `image`, `description`, `sku`, `brand`, `offers` and `aggregateRating`/`review` when reviews exist. For full Google **merchant listing** eligibility, the `Offer` should also include `priceValidUntil`, `itemCondition`, and (where data exists) `hasMerchantReturnPolicy` + `shippingDetails`; include `gtin`/`mpn` if stored. **Variable products** → `AggregateOffer` (`lowPrice`/`highPrice`/`offerCount`/`priceCurrency`).
- **Open Graph product tags:** when `og:type=product`, also emit `product:price:amount`, `product:price:currency` (BDT), and `og:availability`.
- **Breadcrumbs:** `BreadcrumbList` generated from the same breadcrumb data the page renders.
- **Blog post:** `Article`/`BlogPosting` (headline, image, datePublished/Modified, author, publisher).
- **Listings (shop, category, flash-deals):** `CollectionPage` + `ItemList` of products.
- **LocalBusiness/Store:** the app has **branches** — emit a `Store`/`LocalBusiness` graph (name, address, geo, telephone, openingHours) from branch data, on home/footer (and a stores page if added). Optional but strong local-SEO signal.
- **Optional:** `FAQPage` if product/landing FAQs exist; `VideoObject` for product videos.
- **Consistency rule:** every schema field must match visible on-page content (price, availability, rating) — mismatches risk Google penalties.
- Output via the `Seo` builder's `schema()` so it lands in the `seo-head` partial.

**Acceptance:** Google Rich Results Test passes for Product, Breadcrumb, Article, Organization (and LocalBusiness) with no errors/warnings on required fields.

## Phase 4 — Canonical, pagination & robots directives
**Objective:** consolidate duplicate-content variants.

- Self-referencing canonical on every page (Phase 1) — but for **filtered/sorted** shop & category
  pages, canonical points to the **clean** base URL (drop `sort`, `min_price`, `max_price`, etc.);
  keep `page` only where pagination is real.
- **Strip tracking params** from canonical/OG URLs: `utm_*`, `fbclid`, `gclid`, `ref`, etc.
- **URL normalization** (middleware): force lowercase host, consistent trailing-slash, HTTPS, and a
  single host (www vs non-www → 301 to the canonical host from `APP_URL`).
- Paginated pages: canonical to the paginated URL, and (optionally) `rel=prev/next` hints.
- `robots`: `noindex,follow` for thin/duplicate states — empty search results, deep faceted
  combinations (multiple filters), and any internal-search result pages — via the `Seo` object.

**Acceptance:** `/shop?sort=price_low&utm_source=fb` canonicalizes to `/shop`; `/shop?category=x` canonical to `/shop?category=x`; paginated pages canonical to themselves; non-canonical host 301-redirects.

## Phase 5 — XML sitemap (generated file) + robots.txt
**Objective:** feed Google a complete, fresh URL set as a **static file written to the server**
(served directly by the web server — fast, no PHP per crawl), regenerated on a schedule.

- **Artisan command** `php artisan sitemap:generate` (`Modules\Ecommerce\app\Console\GenerateSitemap`)
  that builds the URL set and **writes `public/sitemap.xml`** (and child sitemaps + a `sitemap_index.xml`
  if it exceeds 50k URLs / 50MB). Include: home, shop, active categories, active+visible products,
  published blog posts, flash-deals — each with `<loc>` (absolute), `<lastmod>` (updated_at, W3C
  datetime), and sensible `<changefreq>`/`<priority>`. **Exclude** cart/checkout/account/login/register,
  and raw filtered query URLs.
- **Schedule** the command (e.g. daily) in the app scheduler (`routes/console.php` /
  `bootstrap/app.php` `->command('sitemap:generate')->daily()`), plus run it once on deploy.
- The generated files are **runtime artifacts, not source** → add to `.gitignore`:
  ```
  /public/sitemap.xml
  /public/sitemap*.xml
  /public/sitemap_index.xml
  ```
- Add `Sitemap: {APP_URL}/sitemap.xml` to `public/robots.txt`; keep crawl-allow defaults; disallow
  `/admin`, cart/checkout/account.
- (Optional) a thin route fallback `GET /sitemap.xml` that returns the file if present, or 404 —
  but the canonical approach is the generated static file at `public/sitemap.xml`.

**Acceptance:** running `php artisan sitemap:generate` writes a valid `public/sitemap.xml` with all
public URLs + `lastmod`; the file is gitignored; robots.txt references it; the scheduler entry runs it daily.

## Phase 6 — On-page fixes (H1, images, headings)
**Objective:** clean semantic signals.

- Exactly **one `<h1>`** per page carrying the primary topic: product name (product detail),
  category name (category show), post title (blog detail), and a real `<h1>` on home/shop/category-index/blog-index. Demote the current `<h2>`/`<h3>` name tags; fix the breadcrumb `<h1>` duplication.
- Images: add `loading="lazy"` + `width`/`height` to product/category/blog images; fix the empty
  `alt` on the buy-now modal; ensure all dynamic imgs use entity-name alt.
- Ensure OG image per entity (product/category/blog featured image; else site default).

**Acceptance:** each audited page has one `<h1>`; Lighthouse SEO ≥ 95; no missing-alt warnings.

## Phase 7 — Verification & QA
- Run Google **Rich Results Test** + **Mobile-Friendly** on home, a product, a category, a blog post.
- Validate sitemap (XML), robots.txt, canonical/OG/Twitter via view-source on each page type.
- Lighthouse SEO audit on the same set.
- A small test asserting: every storefront page type renders `<title>`, `meta description`, `canonical`, and at least one JSON-LD block; sitemap route returns 200 valid XML.
- **Google Search Console / Bing:** verify the site (meta tag from settings), **submit the sitemap**, and monitor Coverage/Enhancements (Products, Breadcrumbs) + Core Web Vitals after launch.

## Phase 8 — Performance / Core Web Vitals (ranking factor)
**Objective:** fast, stable pages — LCP, CLS, INP are Google ranking signals.

- **CLS:** add explicit `width`/`height` (or aspect-ratio) to all storefront images so layout doesn't shift; reserve space for banners/sliders.
- **LCP:** `preconnect`/`dns-prefetch` for any external origins; `preload` the hero image + primary font; serve hero eagerly while lazy-loading below-the-fold images.
- **Fonts:** `font-display: swap`; self-host (already local per project rules) and subset if possible.
- **JS/CSS:** `defer` non-critical JS; avoid render-blocking; load Chart.js etc. only where needed (already partly done); minimize unused CSS.
- **Images:** prefer WebP with fallback; compress; correct sizing; `loading="lazy"` below the fold.
- (Optional) image sitemap entries for product images.

**Acceptance:** Lighthouse Performance + SEO ≥ 90 on home/product/category; no CLS from images; LCP element preloaded.

## Phase 9 — Redirects, status codes & environment guard
**Objective:** correct crawl signals; don't index the wrong things.

- **Status codes:** inactive/unpublished/deleted product, category, or blog post returns **404** (or **410 Gone** if permanently removed) — never a soft-200. (`findOrFail` + active scope, or explicit abort.)
- **Slug-change 301s:** when a product/category/blog slug changes, **301-redirect** the old slug to the new (keep a slug-history table or redirect map) so link equity is preserved.
- **Environment noindex:** when `APP_ENV !== 'production'` (or a `seo_indexable=false` setting), force `<meta robots="noindex,nofollow">` site-wide and a disallow-all `robots.txt` — so staging/dev never gets indexed.
- **Custom 404 page** that's genuinely a 404 status with helpful links (search, popular categories).

**Acceptance:** inactive product → 404; old slug → 301 to new; staging returns global noindex; 404 page returns HTTP 404.

---

## Guardrails
- **No duplicate titles/descriptions** — always entity → derived → site default, never blank.
- **XSS/escaping** — JSON-LD output via `json_encode` with proper flags; meta via `e()`/escaped.
- **Lengths** — title ≤ ~60 chars, description ≤ ~160, centrally truncated.
- **Currency** — schema `priceCurrency` = `BDT`; price as plain number (not formatted).
- **Absolute URLs** — canonical, OG, sitemap, schema all use absolute `url()`; strip tracking params.
- **Admin-first** — admin-entered SEO always wins over derived content.
- **CSP note** — JSON-LD `<script type="application/ld+json">` is data, **not** subject to `script-src`, so it's CSP-safe as-is. **GTM/GA inline snippets ARE blocked** by the current `script-src 'self'` (see `SecurityHeaders`); adding analytics needs a CSP update (allow Google domains + a nonce or `'unsafe-inline'` for those tags). Plan analytics with that in mind.
- **Environment** — non-production env (or `seo_indexable=false`) forces global `noindex` + disallow-all robots.
- **Status codes** — inactive/missing entities return 404/410, not soft-200; changed slugs 301.

## Files (anticipated)
```
NEW  Modules/Ecommerce/app/Support/Seo.php                 (builder/value object)
NEW  Modules/Ecommerce/app/Services/SeoSchemaService.php   (JSON-LD graph builders)
NEW  resources view: storefront/partials/seo-head.blade.php (renders head + JSON-LD)
NEW  migration: blog seo_title/seo_description/seo_image
NEW  migration: products.seo_image, categories.meta_image (OG overrides) — optional
NEW  migration + model: seo_pages (static-page SEO)
NEW  controller + view: eCommerce → SEO Settings (site defaults + static pages)
NEW  console command: Modules/Ecommerce/app/Console/GenerateSitemap.php (sitemap:generate -> writes public/sitemap.xml)
EDIT scheduler: register sitemap:generate (daily) + run on deploy
EDIT .gitignore: ignore /public/sitemap*.xml (generated artifacts)
EDIT storefront master layout <head> → render from $seo
EDIT HomeController, ShopController, StorefrontCategoryController, BlogController → build $seo
EDIT product/category/blog admin forms → SEO fields
EDIT robots.txt (+ Sitemap line; env-aware disallow on non-prod)
EDIT storefront partials/pages → single <h1>, image lazy/alt/dimensions/WebP, breadcrumb h1 fix
NEW  middleware: URL normalization (host/case/trailing-slash/HTTPS) + tracking-param-stripped canonical
NEW  slug-history (redirect map) + 301 handling for changed product/category/blog slugs
EDIT storefront controllers → 404/410 for inactive/missing entities
EDIT SecurityHeaders middleware → CSP allowance for GTM/GA (when analytics added)
NEW  admin SEO form widgets → Google snippet preview + live char counters
EDIT branches → LocalBusiness/Store JSON-LD source
EDIT head/perf → preconnect/preload/defer, font-display:swap (Phase 8)
```
