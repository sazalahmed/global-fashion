# BizPOS — Complete Project SEO Audit & Plan (2026-06-14)

Whole-project SEO audit (storefront, landing pages, technical/global, performance) with concrete issues by severity and a phased plan. Plan 1 (storefront SEO pipeline + admin SEO + JSON-LD) is **already implemented** on `feat/storefront-seo-foundation`; this audit covers what remains and what Plan 1 didn't reach.

Severity: **CRITICAL** (breaks indexing/feature) · **HIGH** (significant ranking/crawl impact) · **MEDIUM** · **LOW**.

---

## A. Executive summary

| Area | State |
|---|---|
| Storefront `<head>` pipeline + JSON-LD (Plan 1) | ✅ Done (title/desc/canonical/robots/OG/Twitter/schema, admin-managed) |
| Transactional/account pages noindex | ❌ Indexable (cart/checkout/account default `index,follow`) |
| Canonical for filtered/sorted/UTM URLs | ❌ No param-stripping (duplicate-content risk) |
| robots.txt + XML sitemap | ❌ robots.txt empty allow-all, no `Sitemap:`; no sitemap |
| Environment noindex guard (staging) | ❌ Missing — non-prod is indexable |
| CSP vs analytics | ❌ Blocks external GTM/Pixel/GA scripts in prod |
| Landing-page SEO | ❌ Only title/desc — no canonical/OG/Twitter/JSON-LD |
| i18n SEO (en/bn) | ❌ `<html lang>` hardcoded `en`, no hreflang, `og:locale` hardcoded |
| Mobile viewport | ❌ `user-scalable=no` / `maximum-scale=1.0` (Google flags) |
| H1 structure | ⚠️ breadcrumb `<h1>` generic; product/blog title is `<h2>` |
| Images (lazy/dims/alt/WebP) | ⚠️ no lazy/dimensions; one empty alt; all JPG/PNG |
| Core Web Vitals | ❌ ~1.1MB render-blocking CSS, undeferred JS chain, no preload/preconnect |
| Status codes (404 for missing) | ✅ `abort(404)`/`firstOrFail` correct |
| Slug-change 301s | ❌ none (renamed entities 404) |

---

## B. Issues by severity

### CRITICAL
1. **CSP blocks production analytics.** `app/Http/Middleware/SecurityHeaders.php:20` sets `script-src 'self' 'unsafe-inline'` (no Google/Facebook domains), applied globally (`bootstrap/app.php:17`). The shipped GTM/Pixel snippets load **external** scripts (`googletagmanager.com/gtm.js`, `connect.facebook.net/fbevents.js`) + send beacons — all blocked (script-src, and connect-src/img-src/frame-src fall back to `default-src 'self'`). **The tracking feature silently fails in production.** Fix: extend CSP — `script-src` + `connect-src` + `img-src` + `frame-src` to allow `https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://www.facebook.com` (and `https://*.google-analytics.com`).
2. **Landing pages have no SEO head.** `Modules/LandingPage/resources/views/layouts/landing.blade.php` emits only `<title>`/meta description (from `$page->meta_title/meta_description`) — no canonical, OG, Twitter, robots, or JSON-LD. When landing mode is active these serve at `/`. Fix: add a landing seo-head (reuse the `Seo`/`seo-head` approach or a dedicated partial).
3. **No environment noindex guard.** `Seo` defaults `robots = index,follow` regardless of `APP_ENV` (`Modules/Ecommerce/app/Support/Seo.php:13`); robots.txt is static allow-all. Staging/dev on a public domain gets indexed. Fix: when `app()->environment() !== 'production'` (or a `seo_indexable=false` setting) force global `noindex,nofollow` + disallow-all robots.

### HIGH
4. **Transactional/account pages are indexable.** Cart, checkout, checkout success/cancel, wishlist, compare, login, register, all `customer/*` pass no `$seo` and render `index,follow` (audit: CartController:23, CheckoutController success/cancel, WishlistController:31, CompareController:40, CustomerAuthController multiple). Fix: pass `Seo::make()->robots('noindex,follow')` (transactional) / `noindex,nofollow` (account) per page.
5. **Canonical includes filter/sort/UTM params.** `ShopController@index` / `StorefrontCategoryController@show` canonical = `url()->current()` with `?sort=&min_price=&page=&utm_*` → duplicate-content variants. Fix: clean-canonical (drop sort/price/tracking; keep `category`; keep `page` only when >1) + a `Seo::stripQueryParams()`/canonical-base helper. Also `noindex,follow` on empty search results.
6. **No robots.txt rules + no XML sitemap.** `public/robots.txt` is `Disallow:` (allow-all), no `Sitemap:` line; no sitemap generation. Fix: robots.txt with disallows (/admin, cart, checkout, customer, login, register) + `Sitemap:` line; `php artisan sitemap:generate` → `public/sitemap.xml` (products/categories/blog/static), scheduled daily.
7. **i18n SEO gaps (en/bn).** Storefront `<html lang="en">` hardcoded (`master.blade.php:2`) though Bangla is supported; `og:locale` hardcoded `en_US` (`seo-head.blade.php:17`); no `hreflang`. Fix: dynamic `<html lang="{{ app()->getLocale() }}">`, dynamic `og:locale`, and `hreflang` (requires deciding locale-URL strategy — see Plan 4).
8. **Mobile viewport blocks zoom.** `master.blade.php:5` viewport has `maximum-scale=1.0, minimum-scale=1.0, user-scalable=no` — WCAG violation + Google mobile-usability flag. Fix: `width=device-width, initial-scale=1.0`.
9. **Core Web Vitals — render-blocking weight.** ~1.1MB CSS across 14 `<head>` files (incl. `custom_spacing.css` 117KB, `style.css` 301KB, `responsive.css` 138KB) + ~16 undeferred JS files (jQuery chain incl. `range_slider.js` 135KB loaded on every page; several unused libs: youtube-background 31KB, animated_barfiller, multiple-image-video, Font-Awesome.js). No `defer`, no hero `preload`, no `preconnect` to analytics origins. Fix: defer vendor JS, consolidate/trim CSS, load `range_slider` only on shop/category, delete unused libs, preload LCP hero, preconnect to GTM/FB.

### MEDIUM
10. **H1 structure.** Breadcrumb partial renders a generic `<h1>@yield('breadcrumb_title')</h1>` on every page (`breadcrumb.blade.php:8`) while product/blog titles are `<h2>` (shop/show:97, blog/show:44). Fix: demote breadcrumb to non-h1; promote product/post name to the single `<h1>`; add real `<h1>` to home/shop/category-index/blog-index.
11. **Images: no lazy/dimensions, one empty alt, no WebP.** Product cards/detail/blog images lack `loading="lazy"` + `width`/`height` (CLS); buy-now modal `<img alt="">` empty (`buy-now-modal.blade.php:20`); all JPG/PNG. Fix: lazy + dimensions/aspect-ratio below the fold, real alt, WebP where feasible.
12. **PWA on storefront.** `manifest.json` + `theme-color` linked only in admin layout, not storefront `<head>`. Fix: add `<link rel="manifest">` + `<meta name="theme-color" content="#1B4F72">` to storefront head.
13. **Slug-change 301s.** No slug-history/redirect map; renaming a product/category/blog slug 404s the old URL. Fix: slug-history table + 301 on 404 lookup.
14. **Invoice-share + landing order-success pages** lack `robots noindex` + canonical/OG (`Modules/Sale/.../invoice-share.blade.php`, `Modules/LandingPage/.../order-success.blade.php`). Fix: `noindex,follow` + minimal head.
15. **CLS sources:** hero/banner background-image divs, mini-cart `.html()` injection, WOW animations, marquee — no reserved space.

### LOW
16. **404 page UX** (`resources/views/errors/404-frontend.blade.php`) — returns correct 404 status (good) but only a "home" link; add search + popular categories.
17. **Admin layout** — auth-gated (not indexable) but could add `noindex,nofollow` belt-and-suspenders.
18. **Vendor asset cache-busting** — app CSS/JS are `filemtime`-versioned; vendor files are not.
19. **`X-Frame-Options: DENY`** — not an SEO issue; revisit only if embeds are needed.

> Already correct (no action): 404 status codes via `abort(404)`/`firstOrFail`; fonts self-hosted with `font-display: swap`; trailing-slash removal in `.htaccess`; admin routes auth-gated; `manifest.json` well-formed.

---

## C. Recommended plan (phased)

Plan 1 is done. The rest is sequenced quick-wins-first.

### Plan 1 — Storefront SEO pipeline + admin SEO + JSON-LD — ✅ DONE (`feat/storefront-seo-foundation`)

### Plan 2 — SEO Quick Wins (small, high-impact; ~1 short plan)
Addresses issues **1, 3, 4, 8, 12** + 14, 16:
- CSP: whitelist Google/Facebook analytics domains (script/connect/img/frame-src).
- Environment noindex guard in `Seo::make()` (+ disallow-all robots when non-prod).
- Transactional/account pages: pass `$seo` with `noindex,follow` / `noindex,nofollow`.
- Viewport fix (remove `user-scalable=no`/`maximum-scale`).
- Storefront `<link rel="manifest">` + `theme-color`.
- `noindex` + canonical on invoice-share & landing order-success; richer 404 page.

### Plan 3 — Crawl & Indexing (was "Plan 2")
Addresses **5, 6, 13**:
- Clean canonical (strip sort/price/UTM; keep category; page>1) + `noindex` empty search.
- `robots.txt` (disallows + `Sitemap:`), `php artisan sitemap:generate` → `public/sitemap.xml` + daily schedule.
- Slug-history table + 301 redirects for changed product/category/blog slugs.
- Host/HTTPS/case normalization for canonical consistency (production).

### Plan 4 — On-page & Core Web Vitals (was "Plan 3")
Addresses **9, 10, 11, 15**:
- One `<h1>` per page; demote breadcrumb; heading hierarchy.
- Images: lazy + dimensions/aspect-ratio + real alts + WebP; fix buy-now alt.
- Performance: defer vendor JS, consolidate/trim CSS (drop unused libs, conditional `range_slider`), preload LCP hero, preconnect analytics origins, reserve space for sliders/mini-cart.
- Final Rich-Results + Lighthouse + Search Console verification.

### Plan 5 — i18n / hreflang (strategic; addresses **7**)
- Decide locale-URL strategy (path-prefix `/bn/` vs cookie-based). If distinct URLs: hreflang (`en`/`bn`/`x-default`), dynamic `<html lang>` + `og:locale`, per-locale canonicals. If cookie-based: at minimum dynamic `<html lang>` + `og:locale` now, document hreflang limitation.
- Landing-page SEO head (issue **2**) folds in here or into Plan 2 depending on priority (recommend Plan 2 if landing pages are actively used).

---

## D. Suggested order
1. **Plan 2 (Quick Wins)** — biggest risk-reduction per effort (unblocks analytics, stops staging indexing, fixes mobile/viewport, stops indexing private pages). Landing-page SEO head if landing mode is in use.
2. **Plan 3 (Crawl & Indexing)** — canonical + sitemap + robots so Google crawls the right URLs.
3. **Plan 4 (On-page & CWV)** — rankings polish + performance.
4. **Plan 5 (i18n)** — when the bn/en strategy is decided.
