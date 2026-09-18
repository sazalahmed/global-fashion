# SEO Plan 4 — On-page & Core Web Vitals (design)

**Status:** Approved (audit-driven) — ready for implementation plan
**Date:** 2026-06-14
**Branch:** continues on `feat/storefront-seo-foundation`
**Source:** `docs/SEO_AUDIT_AND_PLAN.md` (issues 9, 10, 11, 15) + `docs/STOREFRONT_SEO_PLAN.md` Phases 6, 8

On-page semantic fixes + the **safe, verifiable** Core Web Vitals wins. Risky asset-pipeline restructuring is split into Plan 4.5 (needs real-browser QA).

## Scope (in — verifiable / low-risk)
1. **One `<h1>` per page.**
   - `storefront/partials/breadcrumb.blade.php`: the page-banner heading is currently `<h1>@yield('breadcrumb_title')</h1>` (renders on every page that has a breadcrumb) → demote to `<h2 class="bp-breadcrumb-title">` (keep visual styling via a CSS class; no inline CSS).
   - Promote the primary content heading to the single `<h1>`: product name (`shop/show` — currently `<h2 class="details_title">`), blog title (`blog/show` — `<h2>`), category name (`category/show`).
   - Add a real `<h1>` to home, shop index, category index, blog index, flash-deals where missing (may be visually-hidden via a `.bp-visually-hidden` class if the design has no visible title).
2. **Images — lazy + dimensions + alt.**
   - Add `loading="lazy"` + `width`/`height` (or a CSS `aspect-ratio` box) to product-card, product-detail gallery, category, and blog images (below-the-fold ones especially) to cut CLS.
   - Fix the empty `alt` in `buy-now-modal.blade.php` → `{{ $product->name ?? 'Product' }}` (it's JS-populated; set a sensible default + have cart.js set alt when it swaps the image).
   - Ensure every dynamic `<img>` has a meaningful `alt` (entity name).
3. **Safe head performance additions.**
   - `preconnect` to analytics origins (`https://www.googletagmanager.com`, `https://connect.facebook.net`) in the storefront `<head>` — cheap, speeds 3rd-party connect.
   - `preload` the LCP hero image: in the home hero, output a `<link rel="preload" as="image" href="...">` for the **first** hero slide image (best-effort; only when a hero image exists). Convert the first hero slide's `background-image` to a real `<img>` only if low-risk; otherwise preload the background URL.
4. **CLS reservations (CSS only).** Add `aspect-ratio`/min-height CSS for: hero/banner containers, the breadcrumb banner, newsletter banner, footer banner, product-card image box, and the mini-cart offcanvas body — so async images/content don't shift layout. All via `bp-`/storefront classes in the storefront stylesheet, with dark-mode parity where relevant.
5. **Remove grep-confirmed-unused libs.** Only remove a `<script>`/`<link>` from the layout if a repo-wide grep confirms zero references (candidates from audit: `jquery.youtube-background`, `animated_barfiller`, `multiple-image-video`, `Font-Awesome.js`). Conservative — skip anything with any reference.

## Scope (out → Plan 4.5, needs live-browser QA)
- Adding `defer`/reordering the jQuery vendor chain (risk: breaks plugin init / inline-script ordering).
- CSS consolidation/minification (merging `responsive.css` into `style.css`, trimming `custom_spacing.css` 117KB).
- Conditional loading of `range_slider.js` (135KB) only on shop/category pages.
- WebP image conversion + an image CDN.
These need Lighthouse + manual browser verification; do them as a separate, carefully-QA'd pass.

## Out (other plans)
- Canonical/sitemap/robots (Plan 3 ✅), i18n/hreflang (Plan 5), host/HTTPS (Plan 3.5).

## Approach / correctness
- H1 changes are **semantic/markup only** — preserve existing CSS classes so visual design is unchanged; where a heading changes tag, carry its classes so styling holds.
- Image `loading="lazy"` must NOT be applied to the LCP/hero (above-the-fold) image (lazy-loading the LCP hurts LCP) — only below-the-fold images.
- Tests assert rendered HTML: exactly one `<h1>` per page type; product/blog/category `<h1>` contains the entity name; `loading="lazy"` present on listing images; `preconnect` present; buy-now alt non-empty.
- No inline CSS; CLS reservations via classes + dark-mode parity; `'use strict';` in any JS.

## Files
**Modified:** `storefront/partials/breadcrumb.blade.php`; `storefront/pages/shop/show.blade.php`, `blog/show.blade.php`, `category/show.blade.php`, `home/index.blade.php` (+ section partials), `shop/index.blade.php`, `category/index.blade.php`, `flash-deals/index.blade.php`; `storefront/partials/product-card.blade.php`, `buy-now-modal.blade.php`, hero slider partial; `storefront/layouts/master.blade.php` or `seo-head.blade.php` (preconnect/preload); `public/website/assets/css/style.css` (H1/CLS classes); possibly `public/website/assets/js/cart.js` (buy-now img alt). Remove unused `<script>`/`<link>` from `master.blade.php` if confirmed unused.
**New:** `Modules/Ecommerce/tests/Feature/OnPageSeoTest.php`.

## QA
- Feature: one `<h1>` on home/shop/product/category/blog; product/blog/category `<h1>` = entity name; listing images have `loading="lazy"`; head has `preconnect`; buy-now img alt non-empty.
- Manual (post-deploy): Lighthouse SEO ≥95 + no CLS-from-images; confirm hero preload; PageSpeed before/after.
