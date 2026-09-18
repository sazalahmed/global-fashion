# BizPOS Pro — Design & Code Rules for Claude

## Project Overview
- **Product:** BizPOS Pro — POS + Accounting + eCommerce System
- **Market:** Bangladesh (Retail, Super Shop, Mobile, Fashion, Electronics, SMBs)
- **Web Stack:** Laravel 12, Blade Templates, Bootstrap 5.3.3, jQuery 3.7.1, FontAwesome 6.5.1, Chart.js 4.4.0
- **Mobile Stack:** React Native 0.84, TypeScript, React Navigation — project at `../bizpos-mobile/`
- **Backend:** Laravel (PHP 8.2+) with Eloquent ORM, MySQL 8.0+
- **API:** Laravel REST API (JSON) via `routes/api.php` — serves the mobile app with Laravel Sanctum token auth
- **Web Rendering:** Server-side via Blade
- **Architecture:** SOLID + DRY principles strictly enforced
- **Font:** Nunito Sans (Google Fonts) — weights 400, 500, 600, 700, 800, 900
- **Currency:** BDT (Bangladeshi Taka) — format: `BDT 1,23,456` (BD number format)

---

## 1. Design System

### Color Palette (CSS Variables — always use vars, never hardcode)
| Variable | Hex | Use |
|---|---|---|
| `--bp-primary` | `#1B4F72` | Buttons, active states, links |
| `--bp-primary-light` | `#2E86C1` | Hover, info badges |
| `--bp-primary-dark` | `#154360` | Button hover state |
| `--bp-secondary` | `#117A65` | Secondary actions |
| `--bp-accent` | `#D4AC0D` | Active sidebar border, highlights |
| `--bp-danger` | `#C0392B` | Delete, error, overdue |
| `--bp-warning` | `#E67E22` | Caution, partial, pending |
| `--bp-success` | `#1E8449` | Paid, active, confirmed |
| `--bp-info` | `#2E86C1` | Info states |

### Typography
- Body: 14px regular, line-height 1.5
- Page title: 22px, fw-800
- Card title: 15px, fw-700
- Table header: 12px, uppercase, fw-700
- Labels: 13px, fw-600
- Small/muted: 11–12px
- Use utility classes: `.fw-800`, `.fs-11`, `.fs-12`, `.fs-13`

### Spacing
- Page padding: 24px (mobile: 16px)
- Card body padding: 20px
- Card header padding: 16px 20px
- Gap between cards: `g-3` (12px) or `g-4` (16px)

---

## 2. Strict Rules — NEVER Break These

1. **No gradients** — flat colors only
2. **No inline CSS** — never use `style="..."` attributes in HTML. All styles go in `public/css/style.css` using class names. **Only exception:** dynamic CSS applied via jQuery (e.g., `.css()`, `.addClass()`, `.show()/.hide()`) for runtime state changes that cannot be achieved with pre-defined classes
3. **Dark mode support** — every new element must have a `[data-theme="dark"]` override in style.css
4. **Responsive** — all pages must work at 768px, 992px, 1200px breakpoints
5. **No Bootstrap utility overrides** — use custom `bp-` prefixed classes
6. **Currency format** — always `BDT X,XX,XXX` (Bangladesh format: lakh system)
7. **Date format** — always `DD MMM YYYY` (e.g., `04 Mar 2026`)
8. **All libraries served locally** — no CDN links. All CSS/JS/font files must be downloaded and served from `public/vendor/`. See Section 16 for full vendor file structure
9. **"use strict" in all JavaScript** — every JS file and every `<script>` block must start with `'use strict';`. No exceptions
10. **Avoid modals** — use dedicated pages (create/edit/show) instead of Bootstrap modals. Modals are only acceptable for: quick confirmations (delete confirm), small single-action dialogs (payment collection, status change), and lightweight pickers (select customer/product). Forms with more than 3-4 fields MUST be a full page, not a modal
11. **No static/hardcoded routes** — **NEVER** use hardcoded URL paths like `/customers`, `/payments/create`, etc. Always use Laravel named routes: `{{ route('customers.index') }}` in Blade, `route('customers.index')` in PHP, and `'{{ route("customers.index") }}'` in inline JS. This applies everywhere: Blade templates, controllers, services, redirects, AJAX URLs, and links. No exceptions
12. **Always download Lightshot (`prnt.sc`) screenshots before responding** — when the user pastes a `https://prnt.sc/...` URL (or any other screenshot host), do NOT guess what the image shows or ask the user to describe it. Resolve the page to its direct image URL, download the PNG to the repo, view it with the Read tool, then delete it. Workflow:

    ```bash
    # 1. Get the direct CDN URL (prnt.sc redirects to img.lightshot.app/<id>.png).
    #    Use WebFetch with a prompt like "Return just the direct image URL".
    # 2. Download — `-k` is required on Windows because of cert revocation checks.
    curl -skL -o .tmp_shot.png "https://img.lightshot.app/<id>.png"
    # 3. Read .tmp_shot.png with the Read tool — Claude sees the image inline.
    # 4. rm -f .tmp_shot.png
    ```

    Most users share screenshots specifically *because* describing the UI is too tedious — guessing what's circled produces wrong fixes and wastes another round trip.

---

## 3. Page Structure Template (Blade)

Every page uses Blade layouts and components. **NEVER** duplicate sidebar, header, or footer markup across pages.

### Master Layout — `resources/views/layouts/app.blade.php`
```blade
<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') - BizPOS Pro</title>
  <!-- Vendor CSS (local) — ALWAYS in this order -->
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <!-- App CSS -->
  <link href="{{ asset('css/style.css') }}" rel="stylesheet">
  @stack('styles')
</head>
<body>

@include('partials.sidebar')
<div class="bp-overlay"></div>
@include('partials.header')

<main class="bp-main">
  <div class="bp-page-header">
    <div>
      <h1 class="bp-page-title">@yield('page-title')</h1>
      <div class="bp-breadcrumb">
        <a href="{{ route('dashboard') }}">Home</a>
        @yield('breadcrumb')
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      @yield('page-actions')
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  @yield('content')
</main>

<!-- Vendor JS (local) — ALWAYS in this order -->
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
'use strict';
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
```

### Page View Example — `resources/views/products/index.blade.php`
```blade
@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Products</span>
@endsection

@section('page-actions')
  <a href="{{ route('products.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i>Add Product</a>
@endsection

@section('content')
  <!-- Page content here -->
@endsection
```

### Blade Rules
- **ALWAYS** use `{{ $variable }}` (auto-escaped) — NEVER use `{!! !!}` unless rendering trusted, pre-sanitized HTML
- Use `@extends`, `@section`, `@yield`, `@include`, `@component` — never raw PHP `include()`
- Shared partials go in `resources/views/partials/` (sidebar, header, footer, modals)
- Reusable UI blocks → Blade Components in `resources/views/components/`
- Use `@csrf` in every `<form>` — no exceptions
- Use `@method('PUT')` / `@method('DELETE')` for non-POST forms
- Use `{{ route('name') }}` for URLs — never hardcode paths
- Use `{{ asset('path') }}` for static assets
- Use `@error('field')` for validation error display
- Use `@auth`, `@guest`, `@can` for conditional rendering

---

## 4. Component Rules

### Cards
```html
<div class="bp-card">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-icon me-2"></i>Title</h5>
    <!-- Optional: action button on right -->
  </div>
  <div class="bp-card-body">...</div>
  <div class="bp-card-footer text-end">
    <button class="bp-btn bp-btn-primary">Save</button>
  </div>
</div>
```

### Buttons
- Primary action: `bp-btn bp-btn-primary`
- Secondary/outline: `bp-btn bp-btn-outline`
- Danger/delete: `bp-btn bp-btn-danger`
- Success: `bp-btn bp-btn-success`
- Small: add `bp-btn-sm`
- Icon only: add `bp-btn-icon`
- Large: add `bp-btn-lg`

### Badges
- `bp-badge bp-badge-success` — green (paid, active, confirmed)
- `bp-badge bp-badge-danger` — red (unpaid, inactive, overdue)
- `bp-badge bp-badge-warning` — orange (partial, pending)
- `bp-badge bp-badge-primary` — blue (POS source)
- `bp-badge bp-badge-info` — light blue (invoice)
- `bp-badge bp-badge-secondary` — teal (eCommerce)
- `bp-badge bp-badge-dark` — gray (draft)

### Tables
```html
<div class="bp-card">
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead><tr><th>Col</th>...</tr></thead>
        <tbody><tr><td>...</td></tr></tbody>
      </table>
    </div>
  </div>
  <!-- Pagination in card-footer -->
  <div class="bp-card-footer">
    <div class="bp-pagination">
      <span class="page-info">Showing X-Y of Z</span>
      <nav><ul class="pagination pagination-sm mb-0">...</ul></nav>
    </div>
  </div>
</div>
```

### Forms
```html
<div class="row g-3">
  <div class="col-md-6">
    <label class="bp-form-label">Field Name *</label>
    <input type="text" class="bp-form-control" required>
  </div>
  <div class="col-md-6">
    <label class="bp-form-label">Select Field</label>
    <select class="bp-form-select w-100">...</select>
  </div>
</div>
```

### Filter Bar
```html
<div class="bp-filter-bar">
  <div class="bp-table-search">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" placeholder="Search...">
  </div>
  <select class="bp-form-select">...</select>
  <button class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-rotate"></i></button>
</div>
```

### Stat Cards
```html
<div class="bp-stat-card">
  <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-icon"></i></div>
  <div class="bp-stat-content">
    <div class="bp-stat-label">Label</div>
    <div class="bp-stat-value">Value</div>
    <div class="bp-stat-change up"><i class="fa-solid fa-arrow-up"></i> 12%</div>
  </div>
</div>
```

---

## 5. Sidebar Rules

- Sidebar lives in `resources/views/partials/sidebar.blade.php` — single source of truth
- Use `request()->routeIs('products.*')` to determine active state — never hardcode active class
- For submenu items, add `open` class to parent when any child route is active
- Sidebar groups: Main, Inventory, Transactions, People, Finance, Online, Analytics, HR, System
- Use `data-toggle="submenu"` on parent menu items with submenus
- Courier/Delivery settings are inside Settings (not a separate sidebar item)

---

## 6. Settings Page Sections (in order)

1. Business Profile
2. Branches
3. Tax / VAT
4. Payment Methods
5. **Courier & Delivery** ← Add this
6. Invoice & Receipt
7. Notifications
8. Localization
9. SMS Gateway
10. Users & Roles (future)

---

## 7. Bangladesh-Specific Rules

- Currency: BDT (৳) — display as `BDT 1,23,456` not `৳1,23,456`
- Number format: Lakh system — `1,23,456` not `123,456`
- Payment methods: Cash, bKash, Nagad, Rocket, Card (Visa/Master), Bank Transfer
- Courier partners: Pathao, Steadfast, eCourier, Redx, Paperfly, Sundarban, SA Paribahan
- VAT: 15% standard (NBR Bangladesh)
- Mushak forms: 6.3 (VAT Invoice), 6.5 (Credit Note), 9.1 (Monthly Return)
- SMS gateway: SSL Wireless, BulkSMSBD
- Banks: DBBL, BRAC Bank, Islami Bank, City Bank

---

## 8. File & Route Naming Convention

### Blade Views
| Page | View Path | Route Name |
|---|---|---|
| Dashboard | `views/dashboard/index.blade.php` | `dashboard` |
| POS Terminal | `views/pos/index.blade.php` | `pos.index` |
| Product List | `views/products/index.blade.php` | `products.index` |
| Product View | `views/products/show.blade.php` | `products.show` |
| Product Create | `views/products/create.blade.php` | `products.create` |
| Product Edit | `views/products/edit.blade.php` | `products.edit` |
| Categories | `views/categories/index.blade.php` | `categories.index` |
| Sales List | `views/sales/index.blade.php` | `sales.index` |
| Sale Detail | `views/sales/show.blade.php` | `sales.show` |
| Purchases List | `views/purchases/index.blade.php` | `purchases.index` |
| Settings | `views/settings/index.blade.php` | `settings.index` |

### Laravel Naming Conventions
- Controllers: PascalCase, singular — `ProductController`, `SaleController`
- Models: PascalCase, singular — `Product`, `Sale`, `SaleItem`
- Migrations: Laravel default — `2026_03_08_000001_create_products_table.php`
- Routes: Use `Route::resource()` for CRUD — generates standard RESTful routes
- Form Requests: `StoreProductRequest`, `UpdateProductRequest`
- Services: `ProductService`, `SaleService`
- Policies: `ProductPolicy`, `SalePolicy`

---

## 9. CSS Class Naming Convention

- Prefix: `bp-` (BizPOS)
- Use kebab-case: `bp-stat-card`, `bp-filter-bar`
- Never use Bootstrap utility classes to override `bp-` components
- New component classes go at the bottom of `style.css` with a section comment block

---

## 10. Icon Guidelines (FontAwesome Solid only)

- Dashboard: `fa-gauge-high`
- Sales: `fa-chart-line`
- Products: `fa-boxes-stacked`
- Customers: `fa-users`
- Settings: `fa-gears`
- Courier: `fa-truck-fast`
- Delivery: `fa-box`
- Payment: `fa-bangladeshi-taka-sign`
- Edit: `fa-pen`
- Delete: `fa-trash`
- View: `fa-eye`
- Save: `fa-save`
- Add: `fa-plus`
- Filter: `fa-filter`
- Export: `fa-download`
- Print: `fa-print`

---

## 11. SOLID Principles — Strictly Enforced

All Laravel code MUST follow SOLID principles:

### S — Single Responsibility
- Each class handles ONE concern only
- **Controllers:** Handle request/response only — delegate to Services. Max ~10 lines per method
- **Services:** Business logic lives here — `App\Services\SaleService`, `App\Services\ProductService`
- **Models:** Eloquent relationships, scopes, accessors/mutators — no business logic
- **Form Requests:** Validation rules and authorization — never validate in controllers
- **Policies:** Authorization logic — never check permissions in controllers directly
- Flow: `Route → Controller → FormRequest (validate) → Service (logic) → Model (DB) → Blade (render)`

### O — Open/Closed
- Classes are open for extension, closed for modification
- Use interfaces and abstract classes for extensibility
- New payment methods, courier providers, or report types should be added via new classes, not by modifying existing ones
- Use strategy pattern for interchangeable behaviors (e.g., `PaymentStrategyInterface` → `BkashPayment`, `NagadPayment`)
- Bind interfaces to implementations in `AppServiceProvider`

### L — Liskov Substitution
- Subtypes must be substitutable for their base types
- All implementations of an interface must honor its contract completely
- Never throw unexpected exceptions in subclass implementations

### I — Interface Segregation
- Keep interfaces small and focused — no "fat" interfaces
- Clients should not be forced to depend on methods they don't use
- Example: Separate `Reportable` and `Exportable` interfaces instead of one giant `ReportInterface`

### D — Dependency Inversion
- Depend on abstractions (interfaces), not concrete classes
- Use Laravel's service container for dependency injection — type-hint interfaces in constructors
- Bind implementations in service providers
- Never use `new ClassName()` inside a class for dependencies — always inject or resolve from container

---

## 12. DRY Principle — No Code Duplication

- **NEVER** copy-paste code — extract into reusable functions, classes, or traits
- Common patterns MUST be abstracted:
  - Shared model behavior → Eloquent Traits (e.g., `HasBdtFormat`, `SoftDeletesWithUser`)
  - Formatting (BDT currency, dates) → `App\Helpers\Formatter` helper or Blade directives
  - Common scopes → Eloquent local scopes or global scopes
  - Repeated query patterns → Eloquent query scopes or Service methods
  - Flash messages → Use Laravel `session()->flash()` with the layout alert block
- **Blade:** Shared UI → `@include('partials.name')` or Blade Components (`<x-stat-card>`, `<x-filter-bar>`)
- **Blade Components** for repeated UI patterns: stat cards, filter bars, data tables, modals, badge status
- jQuery: Common AJAX patterns, form handlers, and UI behaviors must be in shared utility functions in `public/js/app.js`. All JS must use `'use strict';` at the top
- CSS: Never repeat style blocks — use shared `bp-` classes in `public/css/style.css`

---

## 13. Security Rules — MANDATORY (Never Skip)

### SQL Injection Prevention
- **NEVER** use raw SQL string concatenation — this is a fireable offense
- **ALWAYS** use Eloquent ORM or Query Builder with parameter binding
- **NEVER** pass user input into `DB::raw()`, `whereRaw()`, or `orderByRaw()` without binding
- Use whitelists for dynamic column names and sort orders — never interpolate user input
- Example (CORRECT):
  ```php
  Product::where('id', $id)->first();
  DB::table('products')->where('name', 'like', '%' . $search . '%')->get(); // Query Builder binds automatically
  DB::select('SELECT * FROM products WHERE id = ?', [$id]); // Raw with binding
  ```
- Example (WRONG — NEVER do this):
  ```php
  DB::select("SELECT * FROM products WHERE id = $id"); // SQL INJECTION!
  Product::whereRaw("name = '$name'"); // SQL INJECTION!
  ```

### XSS (Cross-Site Scripting) Prevention
- **ALWAYS** use `{{ $variable }}` in Blade — it auto-escapes via `htmlspecialchars()`
- **NEVER** use `{!! $variable !!}` for plain user input (names, emails, comments, etc.)
- **Rich text (text editor content) is the ONLY exception** — follow these strict rules:
  1. Install HTMLPurifier: `composer require mews/purifier`
  2. **Sanitize on INPUT** (before saving to DB): `clean($request->input('description'))` — uses HTMLPurifier to strip dangerous tags/attributes (`<script>`, `onclick`, `onerror`, `javascript:`, `<iframe>`, etc.)
  3. Configure allowed tags in `config/purifier.php` — whitelist only safe tags: `<p>, <br>, <strong>, <em>, <ul>, <ol>, <li>, <h2>-<h6>, <a href>, <img src alt>, <table>, <thead>, <tbody>, <tr>, <td>, <th>, <blockquote>, <span style>`
  4. **Render with `{!! !!}` ONLY after purification**: `{!! $product->description !!}` — safe because HTML was already sanitized before storage
  5. **NEVER** store raw editor HTML without purification — treat it as untrusted input
  6. Add a reusable helper or Form Request rule for purification:
     ```php
     // In Form Request or Service
     $validated['description'] = clean($validated['description']); // HTMLPurifier
     ```
- Laravel's `e()` helper is available for manual escaping outside Blade
- In jQuery: use `.text()` instead of `.html()` when inserting user-provided data
- **NEVER** use `innerHTML` or `.html()` with unsanitized user input in JavaScript
- Set `Content-Security-Policy` headers via middleware

### CSRF (Cross-Site Request Forgery) Prevention
- Laravel handles CSRF automatically via `VerifyCsrfToken` middleware
- **ALWAYS** use `@csrf` in every Blade form — no exceptions
- For AJAX: set CSRF token in headers via `$.ajaxSetup()` using the `meta[name="csrf-token"]` tag (already in layout)
- **NEVER** disable or exclude routes from CSRF protection unless absolutely necessary

### Authentication & Session Security
- Use Laravel's built-in Auth system — `Auth::attempt()`, guards, and providers
- Hash passwords with `Hash::make()` (bcrypt) — **NEVER** store plain text or MD5/SHA1
- Laravel auto-regenerates session ID on login — do not override this behavior
- Configure in `config/session.php`: `'http_only' => true`, `'same_site' => 'lax'`, `'secure' => env('SESSION_SECURE_COOKIE', true)`
- Implement session timeout: `'lifetime' => 30` (minutes)
- Rate-limit login attempts: use Laravel's built-in `ThrottleRequests` middleware (max 5 per minute)

### Input Validation & Sanitization
- **ALWAYS** validate in Form Request classes — never in controllers
- Use Laravel validation rules: `required`, `string`, `max:255`, `integer`, `email`, `exists:table,column`
- Validate ALL input server-side — never trust client-side validation alone
- Reject any input that doesn't match expected patterns — fail closed
- File uploads: `'file' => 'required|file|mimes:jpg,png,pdf|max:2048'` — never allow `.php`, `.exe`, `.js`
- Store uploaded files with `Storage::putFile()` — randomized names, never user-provided filenames

### DDoS & Rate Limiting
- Use Laravel's `RateLimiter` and `throttle` middleware on all routes
- Web routes: `throttle:60,1` (60 requests/min per IP)
- Login/Register/Password reset: `throttle:5,15` (5 requests per 15 min)
- Define rate limiters in `App\Providers\RouteServiceProvider` or `bootstrap/app.php`

### HTTP Security Headers (via middleware)
Create `App\Http\Middleware\SecurityHeaders`:
```php
public function handle($request, Closure $next)
{
    $response = $next($request);
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:;");
    return $response;
}
```

### Additional Security Rules
- Set `APP_DEBUG=false` in production — **NEVER** expose stack traces or SQL errors
- **NEVER** commit `.env` — it's in `.gitignore` by default
- Use `config()` helper to access env values — never use `env()` outside config files
- Use Laravel's `Log` facade to log failed login attempts, permission violations, suspicious activity
- Use `php artisan key:generate` — never commit `APP_KEY`
- Use `Gate` and `Policy` classes for authorization — never check permissions with raw `if` statements
- Use HTTPS in production — set `APP_URL` to `https://` and `FORCE_HTTPS=true`
- Mass assignment protection: **ALWAYS** define `$fillable` on models — never use `$guarded = []`

---

## 14. Database (MySQL) Rules

### Connection
- Configure in `.env`: `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- **NEVER** hardcode credentials — always use `.env`
- Use `config/database.php` for connection settings — never modify directly, use env vars

### Schema Conventions (Laravel Migrations)
- Table names: lowercase, plural, snake_case (e.g., `products`, `sale_items`, `payment_methods`) — Laravel default
- Column names: lowercase, snake_case (e.g., `created_at`, `unit_price`, `is_active`)
- Primary key: `$table->id()` (auto-increment `BIGINT UNSIGNED`)
- Foreign keys: `$table->foreignId('product_id')->constrained()->cascadeOnDelete()`
- Timestamps: `$table->timestamps()` — adds `created_at` and `updated_at` automatically
- Soft deletes: `$table->softDeletes()` — use `SoftDeletes` trait on model. Never hard-delete transactional data
- Money columns: `$table->decimal('amount', 15, 2)` — NEVER use `float` for money
- Booleans: `$table->boolean('is_active')->default(true)`
- Add indexes: `$table->index('column')`, `$table->unique('column')`

### Eloquent Rules
- Use Eloquent relationships (`hasMany`, `belongsTo`, `belongsToMany`) — avoid raw joins for related data
- Use eager loading (`with()`) to prevent N+1 queries — **NEVER** lazy load in loops
- Use `select()` to limit columns when needed — avoid fetching unnecessary data
- Use `DB::transaction()` for multi-table writes (e.g., creating a sale + sale_items)
- Paginate all list queries: `->paginate(15)` — never `->get()` on unbounded queries
- Use query scopes for reusable filters: `scopeActive()`, `scopeByBranch()`
- Use `firstOrFail()` / `findOrFail()` — let Laravel handle 404s automatically

### Migration Rules
- Use `php artisan make:migration` — follow Laravel's timestamp naming convention
- Every schema change gets a new migration — **NEVER** modify old migration files that are already in production
- Always define `down()` method for rollback
- Use seeders (`DatabaseSeeder`, `ProductSeeder`) for default/test data

---

## 15. Laravel Project Structure

```
bizpos/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Thin controllers (delegate to Services)
│   │   ├── Middleware/          # SecurityHeaders, etc.
│   │   └── Requests/           # Form Request validation classes
│   ├── Models/                 # Eloquent models
│   ├── Services/               # Business logic layer
│   ├── Interfaces/             # Contracts for services
│   ├── Helpers/                # Formatter, utility functions
│   ├── Policies/               # Authorization policies
│   └── Providers/              # Service providers (bind interfaces)
├── config/                     # App configuration files
├── database/
│   ├── migrations/             # Schema migrations
│   ├── seeders/                # Database seeders
│   └── factories/              # Model factories (for testing)
├── resources/
│   └── views/
│       ├── layouts/            # app.blade.php (master layout)
│       ├── partials/           # sidebar, header, footer, modals
│       ├── components/         # Reusable Blade components
│       ├── dashboard/          # Dashboard views
│       ├── products/           # index, create, edit, show
│       ├── sales/              # index, create, show
│       ├── purchases/          # index, create, show
│       ├── customers/          # index, create, edit, show
│       ├── settings/           # index (tabbed sections)
│       ├── pos/                # POS terminal view
│       └── auth/               # login, register, forgot-password
├── public/
│   ├── vendor/                 # Third-party libraries (LOCAL, no CDN)
│   │   ├── bootstrap/
│   │   │   ├── css/bootstrap.min.css
│   │   │   └── js/bootstrap.bundle.min.js
│   │   ├── jquery/
│   │   │   └── jquery-3.7.1.min.js
│   │   ├── fontawesome/
│   │   │   ├── css/all.min.css
│   │   │   └── webfonts/       # .woff2, .ttf files
│   │   ├── nunito-sans/
│   │   │   ├── nunito-sans.css # @font-face declarations
│   │   │   └── fonts/          # .woff2 files (400-900 weights)
│   │   └── chartjs/
│   │       └── chart.min.js
│   ├── css/style.css           # All custom styles (bp- classes)
│   ├── js/app.js               # jQuery app logic
│   └── images/                 # Static images
├── routes/
│   └── web.php                 # All web routes (Blade, no API)
├── .env                        # Environment variables (NEVER commit)
├── .env.example                # Template for .env
└── .gitignore                  # Excludes .env, vendor/, node_modules/, etc.
```

### Key Rules
- **Routes:** All routes in `routes/web.php` — use `Route::resource()` for CRUD controllers
- **No API routes** for now — everything is server-rendered via Blade
- **Assets:** CSS and JS go in `public/` — referenced via `{{ asset() }}`
- **No Vite/Mix** — keep it simple with local vendor libs and plain files in `public/`
- **No CDN links** — all libraries are local in `public/vendor/`

---

## 16. Vendor Libraries (Local Only — No CDN)

**NEVER** use CDN links. All third-party libraries must be downloaded and served from `public/vendor/`.

### Required Libraries & Versions
| Library | Version | Local Path |
|---|---|---|
| Bootstrap CSS | 5.3.3 | `vendor/bootstrap/css/bootstrap.min.css` |
| Bootstrap JS | 5.3.3 | `vendor/bootstrap/js/bootstrap.bundle.min.js` |
| jQuery | 3.7.1 | `vendor/jquery/jquery-3.7.1.min.js` |
| FontAwesome | 6.5.1 | `vendor/fontawesome/css/all.min.css` + `vendor/fontawesome/webfonts/` |
| Nunito Sans | latest | `vendor/nunito-sans/nunito-sans.css` + `vendor/nunito-sans/fonts/` |
| Chart.js | 4.4.0 | `vendor/chartjs/chart.min.js` |

### Load Order in Layout
```blade
{{-- CSS (in <head>) --}}
<link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
<link href="{{ asset('css/style.css') }}" rel="stylesheet">

{{-- JS (before </body>) --}}
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script> {{-- Only on pages that need charts --}}
<script src="{{ asset('js/app.js') }}"></script>
```

### Nunito Sans Local Setup
The `vendor/nunito-sans/nunito-sans.css` file must contain `@font-face` declarations pointing to local `.woff2` files:
```css
@font-face {
  font-family: 'Nunito Sans';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url('fonts/nunito-sans-400.woff2') format('woff2');
}
/* Repeat for weights: 500, 600, 700, 800, 900 */
```

### FontAwesome Local Setup
Download the full "Free for Web" package. Copy:
- `css/all.min.css` → `public/vendor/fontawesome/css/`
- `webfonts/` (entire folder) → `public/vendor/fontawesome/webfonts/`

The CSS references `../webfonts/` by default — this path works with the folder structure above.

### Rules
- **NEVER** add CDN `<link>` or `<script>` tags — always use `{{ asset('vendor/...') }}`
- Commit vendor files to the repo (they are static assets, not Composer packages)
- When upgrading a library, replace the files and update the version in this table
- Chart.js should only be loaded on pages that need it — use `@push('scripts')` in those views

---

## 17. REST API for Mobile App (Laravel Sanctum)

The Laravel backend serves a REST API for the React Native mobile app at `../bizpos-mobile/`.

### API Architecture
- **Auth:** Laravel Sanctum (token-based) — mobile app sends `Authorization: Bearer <token>` header
- **Routes:** All API routes go in `routes/api.php` with `api/v1/` prefix
- **Controllers:** Separate API controllers in `App\Http\Controllers\Api\V1\` — never reuse web controllers for API
- **Resources:** Use Laravel API Resources (`App\Http\Resources\`) for consistent JSON response formatting
- **Versioning:** URL-based versioning — `api/v1/products`, `api/v1/sales`, etc.

### API Response Format
All API responses MUST follow this structure:
```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": { ... },
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 72 }
}
```
Error responses:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": { "name": ["The name field is required."] }
}
```

### API Controllers
- Keep thin — delegate to the **same Service classes** used by web controllers
- This ensures business logic is shared between web and mobile, preventing drift
- Example flow: `API Route → ApiProductController → ProductService → Model → API Resource → JSON`

### API Route Conventions
```php
// routes/api.php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', Api\V1\ProductController::class);
    Route::apiResource('sales', Api\V1\SaleController::class);
    Route::apiResource('customers', Api\V1\CustomerController::class);
    Route::apiResource('purchases', Api\V1\PurchaseController::class);
    // ... all modules
});

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [Api\V1\AuthController::class, 'login']);
    Route::post('auth/register', [Api\V1\AuthController::class, 'register']);
});
```

### API Rules
- **ALWAYS** return JSON — never redirect or return HTML from API controllers
- **ALWAYS** use API Resources for response formatting — never return raw models
- **ALWAYS** validate with Form Request classes (shared with web where possible)
- **ALWAYS** paginate list endpoints — `->paginate($request->input('per_page', 15))`
- Use HTTP status codes correctly: 200 (OK), 201 (Created), 204 (No Content), 400 (Bad Request), 401 (Unauthorized), 403 (Forbidden), 404 (Not Found), 422 (Validation Error), 500 (Server Error)
- Rate-limit API routes: `throttle:api` (60 requests/min per user)
- Return `401` for unauthenticated, `403` for unauthorized — never expose stack traces

### Keeping Web & Mobile in Sync
When modifying any module (products, sales, customers, purchases, etc.):
1. **Service layer changes** automatically apply to both web and API since both use the same Services
2. **New fields/columns** — update the corresponding API Resource class AND the mobile app's TypeScript types
3. **New features/modules** — create both the web Blade views AND the API controller + resource + mobile screens
4. **Database migrations** — run on the shared database, both web and mobile benefit automatically
5. **Validation rules** — share Form Request classes between web and API controllers when possible

---

## 18. Mobile App — BizPOS Mobile (`../bizpos-mobile/`)

### Overview
- **Location:** `../bizpos-mobile/` (sibling directory to the Laravel project)
- **Stack:** React Native 0.84, TypeScript, React Navigation
- **Platforms:** Android (primary), iOS (secondary)
- **API:** Connects to the Laravel backend via REST API (`api/v1/`)
- **Design:** Matches the web app's design system (colors, typography, spacing)

### Mobile Design System (Must Match Web)
Use the **same color palette** as the web app:
```typescript
// src/theme/colors.ts
export const colors = {
  primary: '#1B4F72',
  primaryLight: '#2E86C1',
  primaryDark: '#154360',
  secondary: '#117A65',
  accent: '#D4AC0D',
  danger: '#C0392B',
  warning: '#E67E22',
  success: '#1E8449',
  info: '#2E86C1',
  background: '#F5F6FA',
  backgroundDark: '#1A1D23',
  surface: '#FFFFFF',
  surfaceDark: '#23272F',
  text: '#2C3E50',
  textDark: '#E8E8E8',
  textMuted: '#7F8C8D',
  border: '#E0E0E0',
  borderDark: '#3A3F4B',
};
```

Typography:
```typescript
// src/theme/typography.ts
export const typography = {
  fontFamily: 'NunitoSans',
  sizes: {
    body: 14,
    pageTitle: 22,
    cardTitle: 15,
    tableHeader: 12,
    label: 13,
    small: 11,
    muted: 12,
  },
  weights: {
    regular: '400' as const,
    medium: '500' as const,
    semibold: '600' as const,
    bold: '700' as const,
    extrabold: '800' as const,
    black: '900' as const,
  },
};
```

### Mobile Project Structure
```
bizpos-mobile/
├── src/
│   ├── api/                    # API client, interceptors, endpoints
│   │   ├── client.ts           # Axios instance with base URL, token
│   │   ├── endpoints/          # Per-module API functions
│   │   │   ├── auth.ts
│   │   │   ├── products.ts
│   │   │   ├── sales.ts
│   │   │   ├── customers.ts
│   │   │   └── ...
│   │   └── types/              # TypeScript interfaces matching API Resources
│   │       ├── product.ts
│   │       ├── sale.ts
│   │       ├── customer.ts
│   │       └── ...
│   ├── components/             # Reusable UI components
│   │   ├── BpCard.tsx
│   │   ├── BpButton.tsx
│   │   ├── BpBadge.tsx
│   │   ├── BpInput.tsx
│   │   ├── BpStatCard.tsx
│   │   ├── BpTable.tsx
│   │   └── ...
│   ├── screens/                # Screen components (one per route)
│   │   ├── auth/
│   │   │   ├── LoginScreen.tsx
│   │   │   └── RegisterScreen.tsx
│   │   ├── dashboard/
│   │   │   └── DashboardScreen.tsx
│   │   ├── products/
│   │   │   ├── ProductListScreen.tsx
│   │   │   ├── ProductDetailScreen.tsx
│   │   │   └── ProductFormScreen.tsx
│   │   ├── sales/
│   │   │   ├── SaleListScreen.tsx
│   │   │   └── SaleDetailScreen.tsx
│   │   ├── pos/
│   │   │   └── PosScreen.tsx
│   │   ├── customers/
│   │   ├── purchases/
│   │   └── settings/
│   ├── navigation/             # React Navigation setup
│   │   ├── AppNavigator.tsx    # Main stack/tab navigator
│   │   ├── AuthNavigator.tsx   # Login/Register stack
│   │   └── types.ts            # Navigation param types
│   ├── theme/                  # Design tokens
│   │   ├── colors.ts
│   │   ├── typography.ts
│   │   ├── spacing.ts
│   │   └── index.ts
│   ├── hooks/                  # Custom React hooks
│   │   ├── useAuth.ts
│   │   ├── useApi.ts
│   │   └── ...
│   ├── context/                # React Context providers
│   │   ├── AuthContext.tsx
│   │   └── ThemeContext.tsx
│   ├── utils/                  # Utility functions
│   │   ├── formatter.ts        # BDT formatting, date formatting
│   │   ├── storage.ts          # AsyncStorage wrapper
│   │   └── ...
│   └── constants/              # App-wide constants
│       └── config.ts           # API_BASE_URL, etc.
├── assets/                     # Fonts, images
│   └── fonts/
│       └── NunitoSans/         # .ttf files for all weights
├── App.tsx                     # Root component
├── index.js                    # Entry point
├── package.json
├── tsconfig.json
└── CLAUDE.md                   # Mobile-specific Claude rules
```

### Mobile App Rules
1. **TypeScript strict mode** — no `any` types, all props and state typed
2. **Functional components only** — no class components
3. **No inline styles** — use StyleSheet.create() or theme constants
4. **Dark mode support** — use `useColorScheme()` hook, all components must support both themes
5. **BDT formatting** — same `BDT X,XX,XXX` lakh system format as web
6. **Date formatting** — same `DD MMM YYYY` format as web
7. **Nunito Sans font** — bundle locally in `assets/fonts/`, link via react-native.config.js
8. **Component naming** — prefix reusable components with `Bp` (e.g., `BpCard`, `BpButton`)
9. **Screen naming** — suffix with `Screen` (e.g., `ProductListScreen`, `DashboardScreen`)
10. **API types must match** — TypeScript interfaces in `src/api/types/` must match Laravel API Resource output exactly
11. **Secure token storage** — use `react-native-encrypted-storage` for auth tokens, never AsyncStorage
12. **No hardcoded API URLs** — use `src/constants/config.ts` with environment-based base URL
13. **Handle offline gracefully** — show cached data when offline, queue mutations for retry

### Mobile ↔ Web Sync Checklist
When working on EITHER project, check if the other needs updates:

| Web Change | Mobile Action Required |
|---|---|
| New database column/field | Update API Resource + mobile TypeScript type |
| New module/feature | Create API controller + resource + mobile screens |
| Changed validation rules | Update mobile form validation to match |
| New payment method added | Add to mobile payment selection UI |
| Design/color change in CSS | Update `src/theme/colors.ts` to match |
| New sidebar menu item | Add corresponding mobile navigation item |
| Changed business logic in Service | Automatically applies via shared API |
| New status/badge type | Add to mobile BpBadge component |

### Mobile Dependencies (Core)
| Package | Purpose |
|---|---|
| `@react-navigation/native` | Navigation framework |
| `@react-navigation/native-stack` | Stack navigator |
| `@react-navigation/bottom-tabs` | Bottom tab navigator |
| `axios` | HTTP client for API calls |
| `react-native-encrypted-storage` | Secure token storage |
| `react-native-vector-icons` | FontAwesome icons (matching web) |
| `react-native-safe-area-context` | Safe area handling |
| `@react-native-async-storage/async-storage` | General local storage |
| `react-native-screens` | Native screen optimization |

---

## 19. Progressive Web App (PWA)

BizPOS Pro is a PWA — installable on mobile devices via browser without app stores.

### PWA Files
| File | Purpose |
|---|---|
| `public/manifest.json` | Web app manifest — name, icons, shortcuts, display mode |
| `public/sw.js` | Main service worker — app-wide caching, push notifications |
| `public/sw-pos.js` | POS-specific service worker — offline POS with IndexedDB queue |
| `public/images/icons/` | PWA icons (72, 96, 128, 144, 152, 192, 384, 512px) |

### PWA Capabilities
1. **Installable** — "Add to Home Screen" on Android/iOS, standalone mode
2. **Offline support** — static assets cached, POS works offline via IndexedDB queue
3. **Push notifications** — low-stock alerts, order updates (via `sw.js` push handler)
4. **Camera access** — available via `navigator.mediaDevices` for barcode scanning
5. **App shortcuts** — POS Terminal and Dashboard available from long-press on app icon

### PWA Rules
- **manifest.json** must be updated when app name, theme color, or icons change
- **Service worker versioning** — increment `APP_CACHE_NAME` / `STATIC_CACHE_NAME` when deploying new assets
- **Icons required** — must provide 192x192 and 512x512 PNG icons minimum for installability
- **HTTPS required** — PWA features only work over HTTPS (except localhost for development)
- Service worker registration is in the master layout `Modules/Core/resources/views/layouts/master.blade.php`
- The POS service worker (`sw-pos.js`) is registered separately in `public/js/pos-offline.js` with scope `/pos`

### PWA vs Native Mobile App
| Feature | PWA (Web) | React Native (Mobile) |
|---|---|---|
| Installation | Browser → Add to Home Screen | App Store / APK |
| Offline | Service Worker + IndexedDB | AsyncStorage + API queue |
| Push Notifications | Web Push API | Firebase Cloud Messaging |
| Camera/Barcode | `navigator.mediaDevices` | `react-native-camera` |
| Performance | Good (browser-limited) | Native performance |
| Updates | Instant (no app store review) | Store review required |

Both approaches share the same Laravel API backend. Use PWA for quick deployment and instant updates. Use the native app for users who prefer app store distribution or need deeper device integration.

---

## 20. Testing Instructions

### Dev Server
```bash
cd /e/Others/www/bizpos
php artisan serve --host=127.0.0.1 --port=8000
```
- Admin login: `http://127.0.0.1:8000/admin/login` — Email: `admin@gmail.com` / Password: `1234`
- Storefront: `http://127.0.0.1:8000/`
- Timezone: `Asia/Dhaka` (config/app.php)

### After Importing the Live Database — `dev:restore-local-config`

**Run this whenever the production database is copied into a dev environment.**
Symptoms it fixes: *"Steadfast not configured"* despite credentials being
present, and Settings → Business Profile losing its Business Start Date.

Why it is needed: `courier_providers.api_key` / `api_secret` use the
`encrypted` cast, so the stored value is ciphertext bound to the **APP_KEY that
wrote it**. An imported database carries production's ciphertext, a dev
`APP_KEY` cannot decrypt it (`The MAC is invalid.`), and the provider reports
itself unconfigured. The import also overwrites local-only settings.

```bash
# Preview — writes nothing
php artisan dev:restore-local-config --dry-run

# Apply, passing values explicitly
php artisan dev:restore-local-config \
  --steadfast-key=<key> --steadfast-secret=<secret> \
  --business-start-date=2026-06-19

# Or set these in .env and just run it bare
DEV_STEADFAST_API_KEY=...
DEV_STEADFAST_SECRET_KEY=...
DEV_BUSINESS_START_DATE=2026-06-19
```

- Idempotent — re-running reports "already configured" and writes nothing.
- Refuses to run when `APP_ENV=production` unless `--force`.
- Clears the settlement cache, so the change shows immediately.
- Config lives in `config/localdev.php`; command in
  `app/Console/Commands/RestoreLocalConfig.php`.

**Not the same as `courier:fix-credentials`.** That one re-encrypts credentials
stored as *plaintext*; it detects values encrypted under a different APP_KEY and
skips them as unrecoverable, because the plaintext genuinely cannot be derived.
Recovery requires supplying the credentials again — which is what
`dev:restore-local-config` does. Neither command can invent them: if they are
not in `.env` or passed as options, get them from the Steadfast dashboard.

### QA Route Testing (curl-based)
```bash
# Login and get session
curl -s -c /tmp/bz.txt http://localhost:8000/admin/login -o /tmp/lp.html
CSRF=$(grep -oE 'name="_token"[^>]*value="[^"]+"' /tmp/lp.html | head -1 | grep -oE 'value="[^"]+"' | sed 's/value="//;s/"//')
curl -s -c /tmp/bz.txt -b /tmp/bz.txt -X POST http://localhost:8000/admin/login -d "_token=${CSRF}&email=admin@gmail.com&password=1234" -o /dev/null

# Test a GET page
curl -s -b /tmp/bz.txt http://localhost:8000/admin/sales -o /dev/null -w "%{http_code}"

# Submit a POST form
CSRF=$(curl -s -c /tmp/bz.txt -b /tmp/bz.txt "http://localhost:8000/admin/sales/create" -o /tmp/_pg.html && grep -oE 'name="_token"[^>]*value="[^"]+"' /tmp/_pg.html | head -1 | grep -oE 'value="[^"]+"' | sed 's/value="//;s/"//')
curl -s -b /tmp/bz.txt -X POST http://localhost:8000/admin/sales -d "_token=${CSRF}&customer_id=1&..." -D /tmp/rh.txt -o /tmp/rb.html -w "%{http_code}"
```

### What to Test After Every Change

**1. Route sweep** — test ALL GET routes return 200:
```bash
php artisan route:list --json | python3 -c "..." > /tmp/all_routes.txt
# Loop through and curl each, check for 500/404/302
```

**2. Form submissions** — for each create form:
- Submit with valid data → expect 302 to show/index
- Submit with missing required fields → expect 302 back to create (validation)
- Check database state after submission

**3. Data consistency checks** (via tinker):
```php
// Customer balance: stored must match actual
$actual = Sale::where('customer_id', $id)->whereIn('status', ['confirmed','delivered'])->sum('grand_total');
// Must equal $customer->total_purchased

// Journal entries must always balance
$d = DB::table('journal_entry_lines')->join(...)->sum('debit_amount');
$c = DB::table('journal_entry_lines')->join(...)->sum('credit_amount');
// $d must equal $c
```

**4. Price security** — backend must never trust frontend prices:
- POS: `POSService` re-fetches `Product.sell_price` from DB
- Sale Return: validates `unit_price` against original `SaleItem`
- Purchase Return: validates against original `PurchaseItem`
- Storefront checkout: re-validates cart prices from DB before order

**5. Static content scan** — no hardcoded data in Blade views:
```bash
grep -rn "BDT [1-9][0-9,]*" Modules/ --include="*.blade.php" | grep -v "{{|number_format|placeholder"
grep -rn "Showing [0-9]" --include="*.blade.php" | grep -v "{{|count|paginator"
grep -rn "cdn\.|fonts.bunny" --include="*.blade.php"
```

**6. Export test** — verify all modules export:
```bash
curl -s -b /tmp/bz.txt "http://localhost:8000/export/sales?format=xlsx" -o /tmp/test.xlsx
file /tmp/test.xlsx  # Should show "Microsoft Excel 2007+"
```

**7. Print/PDF test** — verify no action buttons in PDF:
```bash
curl -s -b /tmp/bz.txt "http://localhost:8000/admin/sales/1/pdf" -o /tmp/test.pdf
file /tmp/test.pdf  # Should show "PDF document"
# Action bars hidden via @if(!($isPdf ?? false))
```

### Key Validation Rules
- **Backdate control:** `AllowedTransactionDate` rule on all date fields — controlled by Settings → Business → "Allow Back-dated Transactions"
- **Split payments:** `splits[]` array accepted by Customer Payment, Supplier Payment, Expense Payment, Ad Spend — creates one payment record per split
- **Payment account required:** `payment_account_id` is `required_without:splits` — needed when no splits provided
- **Sale stats:** `getStats()` takes no filters — the cards are fixed today/this-month KPIs. The filtered list's sums come from `getListTotals($filters)` and render as the table's calculation (total) row

### Module List (40 modules)
Core, Auth, Dashboard, Product, Sale, Purchase, Customer, Expense, POS, Setting, Supplier, Payment, Inventory, Employee, Asset, Activity, Attendance, Accounting, Report, Ecommerce, Marketing, Security, Payroll, Category, Brand, Unit, Variant, Quotation, SaleReturn, PurchaseReturn, Barcode, Delivery, Installment, Warehouse, Branch, Manufacturing, Loan, AdSpend, LandingPage (disabled)

### Common Issues & Fixes
| Issue | Cause | Fix |
|---|---|---|
| 500 on list page | Wrong column name in query | Check `Schema::getColumnListing('table')` vs code |
| 500 on show page | `route('xxx.edit')` not defined | Check `php artisan route:list --name=xxx` |
| 302 on form submit | Validation failed (redirects back) | Check `StoreXxxRequest::rules()` |
| 419 on POST | CSRF token expired/mismatch | Re-fetch token from page before submitting |
| 429 on export | Rate limit (`throttle:heavy`) | Wait 60s between export tests |
| PDF shows buttons | `@if(!($isPdf ?? false))` missing | Wrap action bars + auto-print scripts |
| `total_paid` double-counted | Multiple services incrementing | Only `PaymentService::updatePartyTotals()` should increment |
| `generateNumber()` collision | Soft-deleted records not counted | Use `withTrashed()->count()` |
