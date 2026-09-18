# SEO Plan 4.5 — Performance Hardening (design)

**Status:** Approved design — **execute with a browser in the loop** (NOT headless). Each change is verified in a real browser + Lighthouse, not PHPUnit.
**Date:** 2026-06-14
**Branch:** continues on `feat/storefront-seo-foundation`
**Source:** `docs/SEO_AUDIT_AND_PLAN.md` (issue 9) + audit Agent-D performance findings. Deferred out of Plan 4 because these change live rendering/behavior and can only be validated in a real browser.

## Why browser-verified
PHPUnit confirms HTML is emitted; it cannot detect a broken layout, a JS init error, or a regressed LCP/CLS. Every task below has a **VERIFY (browser)** gate: load the affected pages in a real browser (or the gstack `browse`/`qa` skill against `php artisan serve`), confirm zero new console errors, confirm the feature still works, and run Lighthouse before/after. **Do not merge a task whose browser verification didn't pass.**

## Current state (audit)
- Storefront `<head>` loads ~14 CSS files (~1.1MB): incl. `custom_spacing.css` (117KB, ~2,858 utility classes), `style.css` (301KB), `responsive.css` (138KB) — all render-blocking.
- ~16 vendor JS files at end of `<body>`, none `defer`/`async`; `range_slider.js` (135KB) loaded on every page though the price filter exists only on shop/category. `custom.js` calls `$('.basic').alRangeSlider()` + `$('.range_slider').alRangeSlider()` **unconditionally** (and `shop/index.blade.php:290` also inits it).
- Fonts already self-hosted with `font-display: swap` (good). Cache-busting on app CSS/JS but not vendor.
- Hero images are CSS `background-image` (Plan 4 added a preload + preconnect already).

## Tasks (each browser-verified)
1. **Conditional `range_slider` (safe win, do first).** Guard `custom.js` slider init with `if ($.fn.alRangeSlider)`; remove the global `range_slider.css`/`.js` from `master.blade.php`; load them only on shop/category **before** their init runs (move the lib + init into the shop/category view's `@push('scripts')` so order is correct, OR add a dedicated `@stack('pageVendorJs')` rendered BEFORE `custom.js`). VERIFY: price filter works on `/shop` + `/categories/{slug}`; `/`, product, blog, cart pages have no `alRangeSlider` console error and don't download `range_slider.*`.
2. **Defer non-critical vendor JS.** Add `defer` to the vendor `<script>`s (jQuery first; plugins after) and ensure any inline scripts that call into them run after DOMContentLoaded. VERIFY: every interactive widget still initializes (slick sliders, select2, nice-select, venobox, wow, marquee, pwtabs, countdown, sticky sidebar, mini-cart, buy-now) with no console errors; INP/TBT improves in Lighthouse.
3. **Trim/condense CSS.** Replace `custom_spacing.css` (117KB of `.m_0`…`.m_1000`) with a compact spacing scale (CSS variables/utilities, ~10–20KB) OR purge unused utilities; merge `responsive.css` into `style.css` to cut a request; minify. VERIFY: visual diff of home/shop/product/category/blog/cart/checkout — layout unchanged at 360/768/992/1200px; Lighthouse LCP/render-blocking improved.
4. **Reduce render-blocking CSS.** Combine vendor CSS into fewer files; consider `media`-split or async-load below-the-fold CSS; ensure the critical above-the-fold styles load first. VERIFY: no FOUC; LCP improved.
5. **WebP images.** Serve product/category/blog images as WebP with JP/PNG fallback (`<picture>` or an on-the-fly converter/`upload_url` variant); keep dimensions. VERIFY: images render in Chrome + a non-WebP fallback path; payload reduced.
6. **Vendor asset cache-busting.** Add `?v=` (filemtime) to vendor CSS/JS for long-cache safety. VERIFY: assets load 200, bust on change.

## Acceptance (overall)
Lighthouse (mobile) Performance + SEO ≥ 90 on home/shop/product; no CLS-from-images; no new console errors; all storefront interactions work; visual parity confirmed at all breakpoints.

## Out of scope
Server-side rendering changes, CDN adoption, image pipeline infra beyond WebP variants.

## Files (anticipated)
`storefront/layouts/master.blade.php` (CSS/JS load order, defer, conditional vendor stack), `shop/index.blade.php` + `category/show.blade.php` (page-scoped range_slider), `public/website/assets/js/custom.js` (guarded init), `public/website/assets/css/*` (consolidate/trim), image partials (`<picture>`/WebP), build/minify step if introduced.
