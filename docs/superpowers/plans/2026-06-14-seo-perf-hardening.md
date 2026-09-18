# SEO Plan 4.5 — Performance Hardening (Implementation Plan)

> **For agentic workers:** This plan is **browser-verified**, not headless. Execute with a real browser in the loop: run `php artisan serve` (seeded DB), and after EACH task load the affected pages (or use the gstack `browse`/`qa` skill), confirm zero new console errors, confirm the feature still works, and run Lighthouse (mobile) before/after. **Do not commit/merge a task whose browser VERIFY gate fails.** Steps use checkbox (`- [ ]`) syntax.

**Goal:** Cut render-blocking weight and per-page JS so storefront LCP/INP/CLS improve — without breaking layout or interactivity.

**Architecture:** Asset-loading changes in the storefront layout + targeted CSS/JS edits. No business logic. Each task is independently shippable and independently verifiable in a browser.

**Tech Stack:** Laravel 12 (modular), Blade, jQuery, vanilla CSS. Spec: `docs/superpowers/specs/2026-06-14-seo-perf-hardening-design.md`. Branch `feat/storefront-seo-foundation`. Stage explicit paths only — never `git add -A` (unrelated WIP in tree).

**Baseline first:** Before any change, run Lighthouse (mobile) on `/`, `/shop`, and a product page; record Performance score, LCP, TBT/INP, CLS, total CSS/JS bytes. This is the before-baseline every task compares against.

---

## Task 1: Conditional `range_slider` (safe, do first)

**Why:** `range_slider.js` (135KB) + `range_slider.css` load on every page; the price filter exists only on shop/category. `custom.js` inits it unconditionally (`$('.basic').alRangeSlider()`, `$('.range_slider').alRangeSlider()`), and `shop/index.blade.php:290` also inits it.

**Files:** `master.blade.php` (remove global load), `public/website/assets/js/custom.js` (guard init), `shop/index.blade.php` + `category/show.blade.php` (page-scoped load), possibly a new `@stack('pageVendorJs')` in the layout.

- [ ] **Step 1: Add a page-vendor JS/CSS stack to the layout** rendered in the right order. In `master.blade.php`, add `@stack('pageVendorCss')` in `<head>` (after the main CSS) and `@stack('pageVendorJs')` **immediately before** the `custom.js` `<script>` (so page vendor libs are defined before `custom.js` runs its inits).
- [ ] **Step 2: Remove the global range_slider** `<link ... range_slider.css>` (master line ~28) and `<script ... range_slider.js>` (master line ~114).
- [ ] **Step 3: Guard `custom.js`** — wrap both init calls: `if ($.fn.alRangeSlider) { $('.basic').alRangeSlider(); }` and the `.range_slider` one likewise. `node --check public/website/assets/js/custom.js`.
- [ ] **Step 4: Load range_slider on shop + category only** — in `shop/index.blade.php` and `category/show.blade.php`:
```blade
@push('pageVendorCss')<link rel="stylesheet" href="{{ asset('website/assets/css/range_slider.css') }}">@endpush
@push('pageVendorJs')<script src="{{ asset('website/assets/js/range_slider.js') }}"></script>@endpush
```
Ensure the existing inline slider-init in `shop/index.blade.php:290` still runs after the lib is loaded (it's in `@push('scripts')`, which renders after `custom.js` — fine, since the lib is now in `pageVendorJs` which is before `custom.js`; the lib is defined by then).
- [ ] **Step 5: VERIFY (browser).**
  - `/shop` and `/categories/{slug}`: price range slider renders + filters; no console errors.
  - `/`, a product page, `/blog`, `/cart`: **no** `alRangeSlider` error in console; DevTools Network shows `range_slider.js`/`.css` are **NOT** downloaded.
  - Lighthouse on `/`: total JS bytes dropped by ~135KB+.
- [ ] **Step 6: Commit** (only after VERIFY passes):
```bash
git add Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php public/website/assets/js/custom.js Modules/Ecommerce/resources/views/storefront/pages/shop/index.blade.php Modules/Ecommerce/resources/views/storefront/pages/category/show.blade.php
git commit -m "perf(seo): load range_slider only on shop/category (guarded)"
```

---

## Task 2: Defer non-critical vendor JS

**Files:** `master.blade.php` (the vendor `<script>` block).

- [ ] **Step 1:** Add `defer` to vendor `<script>`s. Keep jQuery first; `defer` preserves execution order among deferred scripts, so jQuery → bootstrap → plugins still run in order, after parse. Ensure inline `<script>` blocks that use `$`/plugins run on `DOMContentLoaded` (wrap any bare inline init in `$(function(){...})`).
- [ ] **Step 2: VERIFY (browser).** On home/shop/product/cart/checkout: every widget initializes — slick sliders, select2, nice-select, venobox lightbox, WOW animations, marquee, pwstabs tabs, countdown, sticky sidebar, mini-cart offcanvas, buy-now modal, add-to-cart. Zero console errors. Lighthouse TBT/INP improved.
- [ ] **Step 3: Commit** (after VERIFY):
```bash
git add Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php
git commit -m "perf(seo): defer non-critical vendor JS"
```
> If ANY widget breaks and can't be fixed by the DOMContentLoaded wrap, revert this task and report — deferral isn't worth a broken UI.

---

## Task 3: Trim / condense CSS

**Files:** `public/website/assets/css/custom_spacing.css`, `style.css`, `responsive.css`, `master.blade.php`.

- [ ] **Step 1:** Replace `custom_spacing.css` (117KB of `.m_0`…`.m_1000` etc.) with a compact spacing utility set (CSS variables + a small scale) OR run a purge to drop unused utilities (grep the Blade views for which `m_*`/`p_*` classes are actually used, keep only those). Keep the same class names that are in use so markup is unchanged.
- [ ] **Step 2:** Merge `responsive.css` into `style.css` (one fewer request) and minify both; update `master.blade.php` link(s).
- [ ] **Step 3: VERIFY (browser).** Visual diff home/shop/product/category/blog/cart/checkout at 360px, 768px, 992px, 1200px — layout/spacing unchanged. Lighthouse: render-blocking CSS bytes down, LCP improved.
- [ ] **Step 4: Commit** (after VERIFY):
```bash
git add public/website/assets/css/custom_spacing.css public/website/assets/css/style.css public/website/assets/css/responsive.css Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php
git commit -m "perf(seo): trim spacing utilities + merge/minify storefront CSS"
```
> High visual-regression risk — the breakpoint visual diff is the gate. If spacing shifts, narrow the purge or revert.

---

## Task 4: Reduce render-blocking CSS

**Files:** `master.blade.php`.

- [ ] **Step 1:** Combine the vendor CSS (`bootstrap`, `animate`, `mobile_menu`, `nice-select`, `scroll_button`, `slick`, `venobox`, `select2`, `pwstabs`, `range_slider`-already-removed) into one concatenated/minified vendor CSS file requested once; keep FontAwesome separate. Optionally `media`-defer purely below-the-fold CSS.
- [ ] **Step 2: VERIFY (browser).** No FOUC; all components styled correctly across pages + breakpoints; Lighthouse render-blocking + LCP improved.
- [ ] **Step 3: Commit** (after VERIFY):
```bash
git add Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php public/website/assets/css/
git commit -m "perf(seo): consolidate render-blocking vendor CSS"
```

---

## Task 5: WebP images

**Files:** image-rendering partials (`product-card.blade.php`, gallery, category, blog), possibly a helper.

- [ ] **Step 1:** Serve product/category/blog images as WebP with a JP/PNG fallback — either `<picture><source type="image/webp" srcset="...webp"><img src="...jpg"></picture>`, or generate a `.webp` variant via the upload pipeline and reference it with fallback. Keep `width`/`height`/`loading` from Plan 4.
- [ ] **Step 2: VERIFY (browser).** Images render in Chrome (WebP) and in a browser/setting without WebP (fallback); dimensions preserved (no CLS); payload reduced in Network panel.
- [ ] **Step 3: Commit** (after VERIFY):
```bash
git add Modules/Ecommerce/resources/views/storefront/partials/product-card.blade.php Modules/Ecommerce/resources/views/storefront/pages/
git commit -m "perf(seo): serve WebP images with fallback"
```

---

## Task 6: Vendor asset cache-busting

**Files:** `master.blade.php`.

- [ ] **Step 1:** Add `?v={{ filemtime(public_path('website/assets/...')) }}` to vendor CSS/JS links (mirror the existing pattern on app CSS/JS).
- [ ] **Step 2: VERIFY (browser).** Assets return 200; querystring present; changing a file busts the cache.
- [ ] **Step 3: Commit**:
```bash
git add Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php
git commit -m "perf(seo): cache-bust vendor assets"
```

---

## Final acceptance
- [ ] Lighthouse (mobile) Performance + SEO ≥ 90 on `/`, `/shop`, a product page — compared to the recorded baseline.
- [ ] No CLS-from-images; LCP element is the hero (preloaded), not lazy.
- [ ] Zero new JS console errors anywhere; every storefront interaction verified.
- [ ] Visual parity at 360/768/992/1200px on all main page types.

## Notes for the executor
- Each task is independent — ship the ones that pass VERIFY, revert any that regress. Task 1 is the safest/highest-confidence; Tasks 3–4 carry the most visual risk (gate hard on the breakpoint diff).
- If driving via gstack `qa`/`browse`: launch `php artisan serve --host=127.0.0.1 --port=8000`, log in is not required for storefront pages; capture console + screenshots per page; treat any console error or visual diff as a failing gate.
