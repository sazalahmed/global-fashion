# SEO Plan 2 — Quick Wins (design)

**Status:** Approved (audit-driven) — ready for implementation plan
**Date:** 2026-06-14
**Branch:** continues on `feat/storefront-seo-foundation` (stacks on Plan 1's `Seo` pipeline)
**Source:** `docs/SEO_AUDIT_AND_PLAN.md` (issues 1, 2, 3, 4, 8, 12, 14, 16)

Small, high-impact, mostly-mechanical SEO/crawl fixes. No new architecture — uses the existing `Modules\Ecommerce\Support\Seo` + `seo-head` partial.

## Scope (in)
1. **CSP analytics whitelist** — `app/Http/Middleware/SecurityHeaders.php`: extend the CSP so the shipped GTM/Pixel/GA (and CAPI-adjacent) scripts/beacons load in production. Add Google + Facebook origins to `script-src`, `connect-src`, `img-src`, `frame-src` (keep `'self' 'unsafe-inline'`). Origins: `https://www.googletagmanager.com https://www.google-analytics.com https://*.google-analytics.com https://connect.facebook.net https://www.facebook.com`. `frame-src` for the GTM `<noscript>` iframe; `img-src` for the Pixel `<noscript>` tracking img.
2. **Environment noindex guard** — in `seo-head.blade.php`, force `robots = noindex,nofollow` when `! app()->environment('production')` (overrides any page `$seo->robots`), so staging/dev is never indexed. (Render-time so it covers every page regardless of controller.)
3. **Transactional/account noindex + `$seo`** — pass an explicit `$seo` from the controllers/views that currently rely on the default `index,follow`:
   - `noindex,follow`: cart, checkout, checkout success, checkout cancel, wishlist, compare.
   - `noindex,nofollow`: customer login, register, forgot/reset password, profile, profile edit, orders, order detail, change password.
4. **Viewport fix** — `storefront/layouts/master.blade.php`: change viewport to `width=device-width, initial-scale=1.0` (drop `maximum-scale`/`minimum-scale`/`user-scalable=no`/`target-densityDpi`).
5. **PWA head on storefront** — add `<link rel="manifest" href="{{ asset('manifest.json') }}">` + `<meta name="theme-color" content="#1B4F72">` to the storefront `<head>`.
6. **Landing-page SEO head** — `Modules/LandingPage/resources/views/layouts/landing.blade.php`: add canonical (self), robots (`index,follow`), Open Graph (title/description/url/type/site_name/image), Twitter card, and a basic Organization JSON-LD, sourced from `$page->meta_title/meta_description` + `EcommerceSetting` site defaults. Keep the existing `<x-core::tracking-head />`.
7. **Transient pages noindex** — `Modules/Sale/resources/views/invoice-share.blade.php` and `Modules/LandingPage/resources/views/order-success.blade.php`: add `<meta name="robots" content="noindex,follow">` + self canonical.
8. **Richer 404** — `resources/views/errors/404-frontend.blade.php`: add links to shop + a few popular categories + contact (keep the 404 status).

## Scope (out — later plans)
- Clean canonical/param-stripping, sitemap, robots.txt rules, slug-301, host/HTTPS normalization → **Plan 3**.
- H1 restructure, image lazy/dims/WebP, CSS/JS perf/defer/preload/preconnect → **Plan 4**.
- hreflang / dynamic `<html lang>` / per-locale URLs → **Plan 5**. *(Note: dynamic `<html lang>` + `og:locale` are small and MAY be folded here if trivial, but full hreflang needs the locale-URL decision — deferred.)*

## Approach / correctness
- Reuse `Seo::make()->robots(...)`; pass `'seo' => $seo` from the relevant controllers (or set in the views via a `@php $seo = ... @endphp` only where a controller edit is heavy — prefer controller).
- The env-noindex guard lives in `seo-head` (single source) so it can't be bypassed by a page setting `index,follow`.
- CSP change must keep all existing directives; only widen the analytics-related ones. Verify the storefront still renders and analytics origins are allowed.
- Landing-page head must not duplicate/replace its existing title/description logic destructively; add the missing tags around them.
- No inline CSS; `'use strict';` in any JS; named routes/`asset()`.

## Files
**Modified:** `app/Http/Middleware/SecurityHeaders.php`; `Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php`; `Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php`; storefront controllers (Cart, Checkout, Wishlist, Compare, CustomerAuth) — pass `$seo`; `Modules/LandingPage/resources/views/layouts/landing.blade.php`; `Modules/Sale/resources/views/invoice-share.blade.php`; `Modules/LandingPage/resources/views/order-success.blade.php`; `resources/views/errors/404-frontend.blade.php`.

## QA
Feature tests: cart/checkout/account pages emit `noindex`; (test-env) pages emit `noindex,nofollow` via the env guard; landing layout (if routable in tests) emits canonical + OG; storefront head contains manifest + theme-color + corrected viewport. Manual: confirm CSP allows GTM/Pixel in a browser (Network/Console) — documented, not automatable here.
