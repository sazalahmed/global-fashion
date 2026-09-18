# SEO Plan 2 — Quick Wins (Implementation Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the high-impact, low-effort SEO/crawl fixes from the audit — unblock production analytics (CSP), stop staging + private pages from being indexed, fix the mobile viewport, add PWA head + a real SEO head to landing pages, and harden transient pages.

**Architecture:** No new architecture — reuse the existing `Modules\Ecommerce\Support\Seo` + `seo-head` partial. Controllers pass `Seo::make()->robots(...)`; a render-time env guard in `seo-head` forces `noindex` off-production.

**Tech Stack:** Laravel 12 (modular), Blade, PHPUnit. Spec: `docs/superpowers/specs/2026-06-14-seo-quick-wins-design.md`. Audit: `docs/SEO_AUDIT_AND_PLAN.md`.

**Conventions:** Branch `feat/storefront-seo-foundation` (stacks on Plan 1; do NOT switch). `php artisan test <path>`. Base `Tests\TestCase` uses `RefreshDatabase` + `BCRYPT_ROUNDS=4`; no `$this->admin` (use `\App\Models\User::factory()`); modular factories called directly. Unrelated WIP exists in the tree (Purchase/Quotation/Sale/`app.js`) — NEVER `git add -A`; stage only the explicit paths each task lists. If a transient dirty-test-DB migration error occurs, run `php artisan migrate:fresh --env=testing` once.

**Key testability note:** the env-noindex guard keys off `config('app.env')` (NOT `app()->environment()`), so tests can do `config(['app.env' => 'production'])` to verify per-page `index/noindex` values, and the default test env verifies the guard.

---

## File Structure
- **Modify:** `app/Http/Middleware/SecurityHeaders.php` (CSP).
- **Modify:** `Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php` (env guard + manifest/theme-color).
- **Modify:** `Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php` (viewport).
- **Modify:** storefront controllers (Cart, Checkout, Wishlist, Compare, CustomerAuth) — pass `$seo`.
- **Modify:** `Modules/LandingPage/resources/views/layouts/landing.blade.php` (SEO head).
- **Modify:** `Modules/Sale/resources/views/invoice-share.blade.php`, `Modules/LandingPage/resources/views/order-success.blade.php` (noindex).
- **Modify:** `resources/views/errors/404-frontend.blade.php` (richer 404).
- **Test:** `Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php` (+ a Setting/Security test).

---

## Task 1: CSP — allow analytics origins

**Files:**
- Modify: `app/Http/Middleware/SecurityHeaders.php:20`
- Test: `Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class SeoQuickWinsTest extends TestCase
{
    public function test_csp_allows_google_and_facebook_analytics(): void
    {
        $res = $this->get(route('storefront.home'));
        $csp = $res->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString('https://www.googletagmanager.com', $csp);
        $this->assertStringContainsString('https://connect.facebook.net', $csp);
        $this->assertStringContainsString('https://www.google-analytics.com', $csp);
        // analytics origins must be in script-src and connect-src
        $this->assertMatchesRegularExpression('/script-src[^;]*googletagmanager/', $csp);
        $this->assertMatchesRegularExpression('/connect-src[^;]*google-analytics/', $csp);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php`
Expected: FAIL (domains absent; no `connect-src`).

- [ ] **Step 3: Update the CSP** — replace line 20 of `SecurityHeaders.php`:

```php
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://www.facebook.com",
            "connect-src 'self' https://www.google-analytics.com https://*.google-analytics.com https://www.googletagmanager.com https://connect.facebook.net https://graph.facebook.com",
            "img-src 'self' data: https://www.google-analytics.com https://www.googletagmanager.com https://www.facebook.com",
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self'",
            "frame-src 'self' https://www.googletagmanager.com",
        ]) . ';');
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php`
Expected: PASS. (If `SecurityHeaders` isn't applied to the storefront route in the test, confirm it's globally appended in `bootstrap/app.php` — it is at line 17.)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/SecurityHeaders.php Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php
git commit -m "fix(seo): allow GTM/Pixel/GA origins in CSP so analytics loads in prod"
```

---

## Task 2: Environment noindex guard

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php`
- Test: append to `SeoQuickWinsTest`

- [ ] **Step 1: Write the failing tests** (append)

```php
    public function test_non_production_forces_noindex(): void
    {
        config(['app.env' => 'testing']); // default; explicit for clarity
        $res = $this->get(route('storefront.home'));
        $res->assertSee('content="noindex,nofollow"', false);
    }

    public function test_production_keeps_index_on_home(): void
    {
        config(['app.env' => 'production']);
        $res = $this->get(route('storefront.home'));
        $res->assertSee('name="robots" content="index,follow"', false);
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php --filter "_noindex|_index_on_home"`
Expected: the non-production test fails (currently renders `index,follow`).

- [ ] **Step 3: Add the guard** — in `seo-head.blade.php`, change the robots line. Find:

```blade
<meta name="robots" content="{{ $seo->robots }}">
```

Replace with:

```blade
<meta name="robots" content="{{ config('app.env') === 'production' ? $seo->robots : 'noindex,nofollow' }}">
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php
git commit -m "fix(seo): force noindex on non-production environments"
```

---

## Task 3: Transactional pages → noindex,follow

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CartController.php`, `CheckoutController.php`, `WishlistController.php`, `CompareController.php`
- Test: append to `SeoQuickWinsTest`

- [ ] **Step 1: Write the failing test** (append)

```php
    public function test_cart_page_is_noindex_follow(): void
    {
        config(['app.env' => 'production']); // bypass the env guard to see per-page robots
        $res = $this->get(route('storefront.cart.index'));
        $res->assertSee('content="noindex,follow"', false);
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php --filter test_cart_page_is_noindex_follow`
Expected: FAIL (cart renders `index,follow`).

- [ ] **Step 3: Pass a noindex `$seo` from each transactional controller.** In each method's `view(...)` call, add `'seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,follow')` to the view data:
- `CartController@index`
- `CheckoutController@index`, `@success`, and the cancel method (whatever renders `checkout/cancel`)
- `WishlistController@index`
- `CompareController@index`

Example (CartController@index — merge into existing data array, don't drop existing vars):

```php
        return view('ecommerce::storefront.pages.cart.index', [
            // ...existing compact/array vars...,
            'seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,follow'),
        ]);
```

Read each controller and merge cleanly (some use `compact(...)` — switch to an array merge or add `+ ['seo' => ...]`).

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/CartController.php Modules/Ecommerce/app/Http/Controllers/Storefront/CheckoutController.php Modules/Ecommerce/app/Http/Controllers/Storefront/WishlistController.php Modules/Ecommerce/app/Http/Controllers/Storefront/CompareController.php Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php
git commit -m "fix(seo): noindex,follow on cart/checkout/wishlist/compare"
```

---

## Task 4: Account pages → noindex,nofollow

**Files:**
- Modify: `Modules/Ecommerce/app/Http/Controllers/Storefront/CustomerAuthController.php`
- Test: append to `SeoQuickWinsTest` (login form is public — testable)

- [ ] **Step 1: Write the failing test** (append)

```php
    public function test_login_page_is_noindex_nofollow(): void
    {
        config(['app.env' => 'production']);
        $res = $this->get(route('storefront.customer.login'));
        $res->assertSee('content="noindex,nofollow"', false);
    }
```

Confirm the login route name via `php artisan route:list --name=customer.login` and adapt.

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php --filter test_login_page_is_noindex_nofollow`
Expected: FAIL.

- [ ] **Step 3: Pass `noindex,nofollow` `$seo`** from each CustomerAuthController method that returns a view: `showLoginForm`, the register-form method, `showForgotPasswordForm`, the reset-form method, `showProfile`, profile-edit method, `orders`, `orderDetail`, and any other account view methods. Add to each `view(...)`:

```php
        'seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow'),
```

(Methods that only redirect — login POST, logout, etc. — need nothing.)

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Ecommerce/app/Http/Controllers/Storefront/CustomerAuthController.php Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php
git commit -m "fix(seo): noindex,nofollow on customer account pages"
```

---

## Task 5: Viewport fix + PWA head on storefront

**Files:**
- Modify: `Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php:5`
- Modify: `Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php` (add manifest + theme-color)
- Test: append to `SeoQuickWinsTest`

- [ ] **Step 1: Write the failing test** (append)

```php
    public function test_storefront_head_has_fixed_viewport_and_pwa(): void
    {
        $res = $this->get(route('storefront.home'));
        $res->assertSee('width=device-width, initial-scale=1.0">', false);
        $res->assertDontSee('user-scalable=no', false);
        $res->assertSee('rel="manifest"', false);
        $res->assertSee('name="theme-color"', false);
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php --filter test_storefront_head_has_fixed_viewport_and_pwa`
Expected: FAIL.

- [ ] **Step 3: Fix the viewport** — in `master.blade.php` line 5, replace the viewport meta with:

```blade
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
```

- [ ] **Step 4: Add PWA head** — in `seo-head.blade.php`, after the canonical/robots block (before the Open Graph block), add:

```blade
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#1B4F72">
```

- [ ] **Step 5: Run to verify it passes**

Run: `php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php Modules/Ecommerce/resources/views/storefront/partials/seo-head.blade.php Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php
git commit -m "fix(seo): zoomable viewport + storefront PWA manifest/theme-color"
```

---

## Task 6: Landing-page SEO head

**Files:**
- Modify: `Modules/LandingPage/resources/views/layouts/landing.blade.php`
- Test: code-review + (if landing is routable in tests) a render assertion

- [ ] **Step 1: Read** `landing.blade.php` to find where `$page->meta_title`/`meta_description` are emitted (~lines 8-11) and where `<x-core::tracking-head />` is. Identify the `$page` variable and its fields (title/meta_title/meta_description/og image if any).

- [ ] **Step 2: Add the SEO head tags** in `<head>`, after the existing title/description, sourcing site defaults from `EcommerceSetting`:

```blade
@php
    $lpCanonical = url()->current();
    $lpTitle = $page->meta_title ?: $page->title;
    $lpDesc = $page->meta_description ?: \Modules\Ecommerce\Models\EcommerceSetting::get('seo_default_description');
    $lpImage = \Modules\Ecommerce\Models\EcommerceSetting::get('seo_default_image');
    $lpImage = $lpImage ? (\Illuminate\Support\Str::startsWith($lpImage, 'http') ? $lpImage : url($lpImage)) : null;
    $lpSite = \Modules\Ecommerce\Models\EcommerceSetting::get('seo_site_name') ?: config('app.name');
@endphp
<link rel="canonical" href="{{ $lpCanonical }}">
<meta name="robots" content="{{ config('app.env') === 'production' ? 'index,follow' : 'noindex,nofollow' }}">
<meta property="og:site_name" content="{{ $lpSite }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $lpTitle }}">
@if($lpDesc)<meta property="og:description" content="{{ $lpDesc }}">@endif
<meta property="og:url" content="{{ $lpCanonical }}">
@if($lpImage)<meta property="og:image" content="{{ $lpImage }}">@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $lpTitle }}">
@if($lpDesc)<meta name="twitter:description" content="{{ $lpDesc }}">@endif
@if($lpImage)<meta name="twitter:image" content="{{ $lpImage }}">@endif
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $lpSite, 'url' => url('/')], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
```

Use the real `$page` field names found in Step 1. Do not remove the existing `<title>`/description or `<x-core::tracking-head />`.

- [ ] **Step 3: Verify** — `php artisan view:clear` then, if a landing page is routable, GET it and confirm canonical/OG render; otherwise confirm via reading that the tags are inside `<head>` and `$page`-safe. Note verification method in the report.

- [ ] **Step 4: Commit**

```bash
git add Modules/LandingPage/resources/views/layouts/landing.blade.php
git commit -m "feat(seo): add canonical/OG/Twitter/JSON-LD to landing pages"
```

---

## Task 7: Transient pages noindex (invoice-share, landing order-success)

**Files:**
- Modify: `Modules/Sale/resources/views/invoice-share.blade.php`, `Modules/LandingPage/resources/views/order-success.blade.php`

- [ ] **Step 1: Add to each file's `<head>`** (after the existing `<title>`):

```blade
<meta name="robots" content="noindex,follow">
<link rel="canonical" href="{{ url()->current() }}">
```

Read each file first; place inside the existing `<head>`. (invoice-share already has title/description — just add these two lines; order-success has only a title — add these two.)

- [ ] **Step 2: Verify** — `php artisan view:clear`; confirm no Blade parse error (open each). No automated test (auth/transient pages).

- [ ] **Step 3: Commit**

```bash
git add Modules/Sale/resources/views/invoice-share.blade.php Modules/LandingPage/resources/views/order-success.blade.php
git commit -m "fix(seo): noindex + canonical on invoice-share & landing order-success"
```

---

## Task 8: Richer 404 page

**Files:**
- Modify: `resources/views/errors/404-frontend.blade.php`

- [ ] **Step 1: Read** the file to match its existing markup/classes. Then add, near the existing "home" link, helpful links using named routes (keep the 404 status — do not change the controller):

```blade
<div class="error-links">
    <a href="{{ route('storefront.home') }}" class="bp-btn bp-btn-primary">Home</a>
    <a href="{{ route('storefront.shop.index') }}" class="bp-btn bp-btn-outline">Browse Products</a>
    @if(\Illuminate\Support\Facades\Route::has('storefront.contact'))
        <a href="{{ route('storefront.contact') }}" class="bp-btn bp-btn-outline">Contact</a>
    @endif
</div>
```

Match the page's existing CSS classes (this is a public storefront-styled error page — use its classes, not admin `bp-`, if different). No inline CSS.

- [ ] **Step 2: Verify** — `php artisan view:clear`; trigger a 404 (`GET /this-does-not-exist`) and confirm it returns HTTP 404 and renders the links. `curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/nope` should be `404` (if a dev server is running; otherwise confirm by reading the error handler returns 404 — it does per the audit).

- [ ] **Step 3: Commit**

```bash
git add resources/views/errors/404-frontend.blade.php
git commit -m "feat(seo): helpful links on the 404 page"
```

---

## Task 9: Full regression

**Files:**
- Test: run the SEO + tracking suites

- [ ] **Step 1: Run the SEO + quick-wins suites**

Run:
```bash
php artisan test Modules/Ecommerce/tests/Feature/SeoQuickWinsTest.php Modules/Ecommerce/tests/Feature/StorefrontSeoTest.php Modules/Ecommerce/tests/Unit/SeoTest.php Modules/Ecommerce/tests/Unit/SeoSchemaServiceTest.php
```
Expected: all pass.

- [ ] **Step 2: Sanity-check the tracking suite still passes** (CSP change is unrelated but confirm nothing else broke):

```bash
php artisan test Modules/Ecommerce/tests/Unit/TrackingServiceTest.php
```
Expected: pass.

- [ ] **Step 3: Manual QA checklist (document):**
- [ ] In a browser with CSP enforced, confirm GTM/Pixel scripts load (no CSP console errors) on a storefront page.
- [ ] View-source: cart/checkout/account = `noindex`; non-prod = `noindex,nofollow` everywhere; viewport has no `user-scalable=no`; manifest + theme-color present; landing page has canonical/OG.

---

## Self-Review notes (addressed)
- **Spec coverage:** CSP (T1), env guard (T2), transactional noindex (T3), account noindex (T4), viewport+PWA (T5), landing head (T6), transient pages (T7), 404 (T8), regression (T9). All spec scope-in items mapped.
- **Type consistency:** all robots values use the literal strings `index,follow` / `noindex,follow` / `noindex,nofollow` consistently; `Seo::make()->robots(...)` is the Plan-1 API (unchanged); env guard uses `config('app.env')` in both seo-head and the landing head + tests.
- **Testability:** per-page robots tests set `config(['app.env' => 'production'])` to bypass the env guard; the guard itself is verified in the default test env.
