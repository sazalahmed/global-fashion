# Landing Page Mode — Implementation Plan

## Context

BizPOS has a full ecommerce storefront (shop, cart, checkout, customer dashboard). This feature adds an optional **single landing page mode** — where the entire storefront is replaced by one product-focused landing page. Admin can select a template, assign products, edit content, and toggle between full ecommerce vs landing page mode from the Settings panel.

There are **5 landing page templates** (source: `E:\Others\www\landing-page\`):

| # | File | Category | Description |
|---|---|---|---|
| 1 | `index.html` | Clothing | T-Shirt pack offer (3-piece combo) |
| 2 | `index_2.html` | Footwear | Leather loafer shoes |
| 3 | `index_3.html` | Fragrance | Perfume/Attar bottle |
| 4 | `index_4.html` | Skincare | Beauty Pearl combo (face wash + creams) |
| 5 | `index_5.html` | Fashion | Indian Rajasthani cotton 3-piece sets |

**Shared tech stack:** Bengali text, Bootstrap 5, jQuery 3.7.1, Slick carousel, Select2, Font Awesome, bKash/Nagad/Rocket payment methods, built-in checkout form.

### Design Decisions
- **Order flow**: Use Ecommerce Order pipeline (not Sale) — keeps landing page orders in the ecommerce system
- **Edit style**: Template-based with editable content fields — admin picks a template, fills in fields
- **Assets**: Separate CSS/JS in `public/vendor/landing/` — no style conflicts with admin panel

---

## 1. Module Structure

### New Module: `Modules/LandingPage/`

```
Modules/LandingPage/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── LandingPageController.php         # Admin CRUD (list, create, edit, delete, activate)
│   │   │   └── LandingFrontController.php        # Public: render landing page + handle order submission
│   │   ├── Middleware/
│   │   │   └── LandingPageMode.php               # Blocks storefront routes when landing mode is active
│   │   └── Requests/
│   │       └── StoreLandingPageRequest.php        # Validation for create/update
│   ├── Models/
│   │   └── LandingPage.php                       # Eloquent model
│   ├── Services/
│   │   └── LandingPageService.php                # Business logic (CRUD, activation, order processing)
│   └── Providers/
│       ├── LandingPageServiceProvider.php         # Module service provider
│       └── RouteServiceProvider.php               # Route registration
├── database/
│   └── migrations/
│       └── 2026_04_09_500001_create_landing_pages_table.php
├── resources/
│   └── views/
│       ├── admin/
│       │   ├── index.blade.php                   # Admin: list all landing pages
│       │   ├── create.blade.php                  # Admin: create new landing page
│       │   └── edit.blade.php                    # Admin: edit landing page content
│       ├── templates/
│       │   ├── template-1.blade.php              # T-Shirt template (converted from index.html)
│       │   ├── template-2.blade.php              # Shoes template (converted from index_2.html)
│       │   ├── template-3.blade.php              # Perfume template (converted from index_3.html)
│       │   ├── template-4.blade.php              # Beauty template (converted from index_4.html)
│       │   └── template-5.blade.php              # Fashion template (converted from index_5.html)
│       ├── layouts/
│       │   └── landing.blade.php                 # Minimal public layout (no admin nav/sidebar)
│       └── order-success.blade.php               # Order confirmation page
├── routes/
│   ├── web.php                                   # Admin routes (prefix: /admin)
│   └── front.php                                 # Public routes (/, /landing/order)
└── module.json
```

---

## 2. Database Schema

### `landing_pages` table

```php
Schema::create('landing_pages', function (Blueprint $table) {
    $table->id();
    $table->string('name');                                    // Admin label: "Summer T-Shirt Sale"
    $table->string('template');                                // template-1 through template-5
    $table->boolean('is_active')->default(false);              // Only ONE active at a time
    
    // Hero section
    $table->text('hero_title');                                // Main headline (Bengali)
    $table->text('hero_subtitle')->nullable();                 // Supporting headline
    $table->string('hero_image')->nullable();                  // Custom hero banner image path
    
    // Pricing display
    $table->decimal('offer_price', 15, 2)->nullable();         // Display offer price on hero
    $table->decimal('original_price', 15, 2)->nullable();      // Strikethrough original price
    
    // Content
    $table->string('video_url')->nullable();                   // YouTube embed URL
    $table->json('sections')->nullable();                      // Flexible content:
    // {
    //   "faqs": [{"question": "...", "answer": "..."}],
    //   "benefits": [{"icon": "fa-check", "text": "..."}],
    //   "sizes": [{"size": "M", "chest": "38", "length": "28"}],
    //   "details": [{"title": "...", "text": "...", "image": "..."}]
    // }
    
    // Products
    $table->json('product_ids');                               // [1, 5, 12] — selected product IDs
    
    // Delivery & contact
    $table->decimal('delivery_inside_dhaka', 10, 2)->default(60);
    $table->decimal('delivery_outside_dhaka', 10, 2)->default(120);
    $table->string('contact_phone')->nullable();
    
    // Customization
    $table->text('custom_css')->nullable();                    // Per-page CSS overrides
    
    // SEO
    $table->string('meta_title')->nullable();
    $table->text('meta_description')->nullable();
    
    // Audit
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
    
    $table->index('is_active');
    $table->index('template');
});
```

### Settings entries (group: `landing_page`)

Stored in the existing `settings` table using the `Setting::set()` / `Setting::get()` pattern:

| Group | Key | Type | Default | Description |
|---|---|---|---|---|
| `landing_page` | `mode` | string | `full_site` | `full_site` or `landing_page` |
| `landing_page` | `active_landing_page_id` | integer | null | ID of the active landing page |

---

## 3. Model: `LandingPage`

```php
class LandingPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'template', 'is_active',
        'hero_title', 'hero_subtitle', 'hero_image',
        'offer_price', 'original_price',
        'video_url', 'sections', 'product_ids',
        'delivery_inside_dhaka', 'delivery_outside_dhaka',
        'contact_phone', 'custom_css',
        'meta_title', 'meta_description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active'              => 'boolean',
            'sections'               => 'json',
            'product_ids'            => 'json',
            'offer_price'            => 'decimal:2',
            'original_price'         => 'decimal:2',
            'delivery_inside_dhaka'  => 'decimal:2',
            'delivery_outside_dhaka' => 'decimal:2',
        ];
    }

    // Relationships
    public function creator(): BelongsTo { ... }

    // Get the actual Product models for the selected product_ids
    public function products()
    {
        return Product::whereIn('id', $this->product_ids ?? [])->active()->get();
    }

    // Scopes
    public function scopeActive($query) { return $query->where('is_active', true); }
}
```

---

## 4. Middleware: `LandingPageMode`

**File:** `Modules/LandingPage/app/Http/Middleware/LandingPageMode.php`

**Logic:**

```
handle($request, $next):
    1. $mode = Setting::get('landing_page', 'mode', 'full_site')
    2. If $mode === 'full_site' → return $next($request)    // Full ecommerce works normally
    3. If $mode === 'landing_page':
       - Get current path
       - ALLOW these paths (pass through):
         • '/' (exact) — landing page itself
         • '/landing/order' — order submission
         • '/landing/order-success/*' — order confirmation
         • '/admin/*' — entire admin panel
         • '/login', '/logout' — auth routes
         • Any API route '/api/*'
       - BLOCK everything else:
         • '/shop', '/shop/*'
         • '/cart', '/cart/*'
         • '/checkout', '/checkout/*'
         • '/customer/*'
         • '/categories', '/categories/*'
         • '/wishlist', '/wishlist/*'
         • '/blog', '/blog/*'
         • Any other storefront route
         → redirect to '/' with 302
```

**Registration:** Add to `bootstrap/app.php` in the `web` middleware group:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Modules\LandingPage\Http\Middleware\LandingPageMode::class,
    ]);
})
```

**Caching:** The setting lookup should be cached per-request to avoid repeated DB queries. Use `once()` or a static property.

---

## 5. Routes

### Admin Routes (`Modules/LandingPage/routes/web.php`)

```php
Route::middleware('auth')->prefix('landing-pages')->name('landing-pages.')->group(function () {
    Route::get('/', [LandingPageController::class, 'index'])->name('index');
    Route::get('/create', [LandingPageController::class, 'create'])->name('create');
    Route::post('/', [LandingPageController::class, 'store'])->name('store');
    Route::get('/{landingPage}/edit', [LandingPageController::class, 'edit'])->name('edit');
    Route::put('/{landingPage}', [LandingPageController::class, 'update'])->name('update');
    Route::delete('/{landingPage}', [LandingPageController::class, 'destroy'])->name('destroy');
    Route::post('/{landingPage}/activate', [LandingPageController::class, 'activate'])->name('activate');
    Route::post('/deactivate', [LandingPageController::class, 'deactivate'])->name('deactivate');
    Route::get('/{landingPage}/preview', [LandingPageController::class, 'preview'])->name('preview');
});
```

### Public Routes (`Modules/LandingPage/routes/front.php`)

```php
// These only work when landing page mode is active (middleware handles the check)
Route::post('/landing/order', [LandingFrontController::class, 'submitOrder'])->name('landing.order');
Route::get('/landing/order-success/{orderNumber}', [LandingFrontController::class, 'orderSuccess'])->name('landing.order.success');
```

The landing page itself renders on `/` — this is handled by the `LandingFrontController@show` which is called by the Ecommerce `HomeController` when landing mode is active (or via middleware redirect).

---

## 6. Admin Edit Page — Split Layout with Live Preview

### Layout: Form (left) + Live Preview (right)

```
┌──────────────────────────────────────────────────────────────────────┐
│  Edit Landing Page: "Summer T-Shirt Sale"              [Save] [Back]│
├──────────────────────────┬───────────────────────────────────────────┤
│  FORM (col-xl-5)         │  LIVE PREVIEW (col-xl-7)                 │
│                          │                                          │
│  Template Selection      │  ┌─────────────────────────────────┐     │
│  [T-Shirt] [Shoes] ...   │  │                                 │     │
│                          │  │   <iframe> showing the           │     │
│  Hero Title              │  │   rendered landing page          │     │
│  [________________]      │  │   template with current          │     │
│                          │  │   field values                   │     │
│  Hero Subtitle           │  │                                 │     │
│  [________________]      │  │   Updates live as admin          │     │
│                          │  │   types in the form fields       │     │
│  Offer Price   Orig      │  │                                 │     │
│  [______] [______]       │  │   Scrollable full-height         │     │
│                          │  │   preview panel                  │     │
│  Products                │  │                                 │     │
│  [Select2 picker...]     │  │                                 │     │
│                          │  │                                 │     │
│  Video URL               │  │                                 │     │
│  [________________]      │  │                                 │     │
│                          │  │                                 │     │
│  Benefits [+ Add]        │  │                                 │     │
│  FAQ Items [+ Add]       │  │                                 │     │
│  Size Guide [+ Add]      │  │                                 │     │
│                          │  │                                 │     │
│  Delivery Charges        │  │                                 │     │
│  Inside [60] Outside[120]│  │                                 │     │
│                          │  └─────────────────────────────────┘     │
│  Custom CSS              │                                          │
│  [________________]      │  [Open Preview in New Tab]                │
│                          │                                          │
│  [Save]  [Cancel]        │                                          │
└──────────────────────────┴───────────────────────────────────────────┘
```

### How Live Preview Works

1. **Preview Route**: `GET /admin/landing-pages/{id}/preview` renders the landing page template in an iframe-friendly layout (no admin chrome)
2. **Iframe Embed**: The right panel contains an `<iframe>` pointing to the preview route
3. **Live Updates via JS**:
   - On form field `input`/`change` events (debounced 500ms), JS collects all current form values
   - Sends an AJAX `POST` to `/admin/landing-pages/{id}/preview-data` — returns rendered HTML
   - Or simpler approach: JS uses `postMessage()` to send field values to the iframe, and the iframe's JS updates the DOM directly:
     - `hero_title` → updates `.hero-title` text
     - `hero_subtitle` → updates `.hero-subtitle` text
     - `offer_price` → updates `.offer-price` text
     - `video_url` → updates YouTube iframe `src`
     - Template change → full iframe reload with new template
     - Product change → iframe reload (needs server-side product data)
4. **"Open in New Tab"** button: Opens the full preview route in a new browser tab for testing responsive design

### Preview API Route

```php
// Admin route — authenticated
Route::get('/{landingPage}/preview', [LandingPageController::class, 'preview'])->name('preview');
Route::post('/{landingPage}/preview-data', [LandingPageController::class, 'previewWithData'])->name('preview-data');
```

**`preview()`** — Renders the saved landing page template (used for iframe `src` and "Open in New Tab")
**`previewWithData()`** — Accepts POST with unsaved form data, renders template with that data (used for live updates on field change). Returns HTML fragment that replaces iframe content.

### Field Groups in the Form Panel

### Section 1: Template Selection
- 5 visual radio cards in a row
- Each card shows: template thumbnail image, template name, category label
- Template names: "T-Shirt", "Shoes", "Perfume", "Beauty", "Fashion"
- **On change → full iframe reload** with the new template

### Section 2: Basic Info
| Field | Type | Validation |
|---|---|---|
| Name | text input | required, max:255 |
| Contact Phone | text input | nullable, max:20 |
| Meta Title | text input | nullable, max:255 |
| Meta Description | textarea | nullable, max:500 |

### Section 3: Hero Section
| Field | Type | Validation |
|---|---|---|
| Hero Title | textarea | required |
| Hero Subtitle | textarea | nullable |
| Hero Image | file upload | nullable, image, max:2MB |
| Offer Price (BDT) | number | nullable, min:0 |
| Original Price (BDT) | number | nullable, min:0 |
- **Live updates**: title, subtitle, prices update in iframe via postMessage as admin types

### Section 4: Products
- Select2 multi-select dropdown
- Searches products by name/SKU via AJAX (`/admin/landing-pages/search-products?q=...`)
- Shows: product name, SKU, sell price, primary image thumbnail
- Minimum 1 product, maximum 10
- **On change → iframe reload** (products need server-side rendering with images/prices)

### Section 5: Video
| Field | Type | Validation |
|---|---|---|
| YouTube URL | url input | nullable, url |
- **Live update**: YouTube iframe src updates in preview via postMessage

### Section 6: Content Sections (JSON-driven repeaters)

**Benefits/Features:**
- Repeatable row: Icon selector (dropdown) + Text (input)
- Add/Remove buttons
- Renders as bullet list in the landing page
- **On change → iframe reload** (repeater changes need server re-render)

**FAQ Items:**
- Repeatable row: Question (input) + Answer (textarea)
- Add/Remove buttons
- Renders as Bootstrap accordion
- **On change → iframe reload**

**Size Guide (optional, template-dependent):**
- Repeatable row: Size (input) + Chest (input) + Length (input)
- Add/Remove buttons
- Renders as a table
- **On change → iframe reload**

**Detail Sections (optional):**
- Repeatable row: Title (input) + Text (textarea) + Image (file upload)
- Add/Remove buttons
- Renders as alternating text+image sections
- **On change → iframe reload**

### Section 7: Delivery
| Field | Type | Validation |
|---|---|---|
| Inside Dhaka (BDT) | number | required, min:0 |
| Outside Dhaka (BDT) | number | required, min:0 |
- **Live update**: delivery charge text updates in iframe via postMessage

### Section 8: Custom CSS
- Code textarea (monospace font)
- Per-page CSS overrides injected into the landing page `<style>` tag

---

## 7. Settings Page: New "Landing Page" Tab

**Location:** `Modules/Setting/resources/views/index.blade.php`
**Position:** New tab #6 (between "Courier & Delivery" and "Invoice & Receipt")

### Tab Content

```
┌─────────────────────────────────────────────────────┐
│  Landing Page Settings                              │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Website Mode                                       │
│  ┌──────────────────┐  ┌──────────────────┐        │
│  │ ● Full eCommerce │  │ ○ Landing Page   │        │
│  │   Site            │  │   Mode           │        │
│  └──────────────────┘  └──────────────────┘        │
│                                                     │
│  Active Landing Page (shown when mode=landing_page) │
│  ┌──────────────────────────────────────────┐      │
│  │ ▼ Select a landing page...               │      │
│  └──────────────────────────────────────────┘      │
│                                                     │
│  ┌──────────────────────────────────────────┐      │
│  │ 📋 Manage Landing Pages →                │      │
│  └──────────────────────────────────────────┘      │
│                                                     │
│                              [Save Settings]        │
└─────────────────────────────────────────────────────┘
```

### Changes to Setting module files:

**`SettingService.php`** — add `updateLandingPage()`:
```php
public function updateLandingPage(array $data): void
{
    Setting::set('landing_page', 'mode', $data['mode'] ?? 'full_site');
    if (isset($data['active_landing_page_id'])) {
        Setting::set('landing_page', 'active_landing_page_id', $data['active_landing_page_id'], 'integer');
    }
}
```

**`SettingController.php`** — add `landing_page` case in the `update()` switch.

**`UpdateSettingsRequest.php`** — add validation for `mode` and `active_landing_page_id`.

---

## 8. Order Processing (Ecommerce Order Pipeline)

### Flow: Landing Page Order Submission

```
Customer fills form → POST /landing/order → LandingFrontController@submitOrder
    │
    ├── Validate input (name, phone, address, district, products, quantities, payment method)
    │
    ├── Find or create Customer by phone number
    │   └── Customer::firstOrCreate(['phone' => $phone], ['name' => $name, 'address' => $address])
    │
    ├── Create Ecommerce Order via existing pipeline:
    │   ├── Order header: customer_id, status='pending', source='landing_page'
    │   ├── Order items: foreach selected product → order_item with quantity and unit_price
    │   ├── Shipping: delivery charge based on district (inside/outside Dhaka)
    │   ├── Payment method: 'cod' / 'bkash' / 'nagad' / 'rocket'
    │   ├── Payment reference: bKash/Nagad/Rocket transaction ID (if provided)
    │   └── Total = sum(items) + delivery charge
    │
    └── Redirect to /landing/order-success/{orderNumber}
```

### Validation Rules:
```php
'customer_name'    => 'required|string|max:255',
'customer_phone'   => 'required|string|max:20',
'address'          => 'required|string|max:500',
'district'         => 'required|string',
'products'         => 'required|array|min:1',
'products.*.id'    => 'required|exists:products,id',
'products.*.qty'   => 'required|integer|min:1',
'payment_method'   => 'required|in:cod,bkash,nagad,rocket',
'txn_id'           => 'nullable|string|max:100',    // For mobile payments
'bkash_number'     => 'nullable|string|max:20',     // bKash sender number
```

---

## 9. Template Conversion: HTML → Blade

Each static HTML file is converted to a Blade template with dynamic content from the `LandingPage` model.

### Mapping Table

| Static HTML | Blade Equivalent |
|---|---|
| Hardcoded Bengali headline | `{{ $page->hero_title }}` |
| Hardcoded subtitle | `{{ $page->hero_subtitle }}` |
| Static product images | `@foreach($products as $product) {{ $product->image }} @endforeach` |
| Static product prices | `BDT {{ number_format($product->sell_price) }}` |
| Hardcoded YouTube URL | `{{ $page->video_url }}` |
| Static FAQ items | `@foreach($page->sections['faqs'] ?? [] as $i => $faq)` |
| Static benefits list | `@foreach($page->sections['benefits'] ?? [] as $benefit)` |
| Static size guide rows | `@foreach($page->sections['sizes'] ?? [] as $size)` |
| Static delivery charge | `{{ number_format($page->delivery_inside_dhaka) }}` / `{{ number_format($page->delivery_outside_dhaka) }}` |
| Static phone number | `{{ $page->contact_phone }}` |
| Offer price display | `{{ number_format($page->offer_price) }}` |
| Original price (strikethrough) | `<del>{{ number_format($page->original_price) }}</del>` |
| Form `action=""` | `action="{{ route('landing.order') }}"` + `@csrf` |
| Payment logos (bkash.png etc.) | `{{ asset('vendor/landing/images/bkash.png') }}` |
| Product radio buttons in form | `@foreach($products as $product)` → radio with product data |

### Layout: `layouts/landing.blade.php`

Minimal layout — **NO admin header, sidebar, or footer**:

```blade
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $page->meta_title ?? $page->name }} - BizPOS</title>
    @if($page->meta_description)
    <meta name="description" content="{{ $page->meta_description }}">
    @endif
    
    <!-- Vendor CSS (reuse from main app) -->
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
    
    <!-- Landing page specific CSS -->
    <link href="{{ asset('vendor/landing/css/slick.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/landing/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/landing/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/landing/css/responsive.css') }}" rel="stylesheet">
    
    @if($page->custom_css)
    <style>{{ $page->custom_css }}</style>
    @endif
</head>
<body>
    @yield('content')
    
    <!-- Vendor JS (reuse from main app) -->
    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- Landing page specific JS -->
    <script src="{{ asset('vendor/landing/js/slick.min.js') }}"></script>
    <script src="{{ asset('vendor/landing/js/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/landing/js/main.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
```

---

## 10. Assets — File Mapping

### Copy from `E:\Others\www\landing-page\` to `public/vendor/landing/`:

```
public/vendor/landing/
├── css/
│   ├── style.css              ← from landing-page/css/style.css
│   ├── responsive.css         ← from landing-page/css/responsive.css
│   ├── slick.css              ← from landing-page/css/slick.css
│   └── select2.min.css        ← from landing-page/css/select2.min.css
├── js/
│   ├── main.js                ← from landing-page/js/main.js
│   ├── slick.min.js           ← from landing-page/js/slick.min.js
│   └── select2.min.js         ← from landing-page/js/select2.min.js
└── images/
    ├── bkash.png              ← from landing-page/img/bkash.png
    ├── nagad.png              ← from landing-page/img/nagad.png
    ├── rocket.png             ← from landing-page/img/rocket.png
    ├── logo.png               ← from landing-page/img/logo.png
    └── (template default images as fallbacks)
```

**Note:** jQuery, Bootstrap, and Font Awesome are already in `public/vendor/` — reuse those, don't duplicate.

---

## 11. Sidebar Menu Addition

**File:** `Modules/Core/resources/views/partials/sidebar.blade.php`

Add under the **Online** group (near Ecommerce):

```blade
<li class="{{ request()->routeIs('landing-pages.*') ? 'active' : '' }}">
    <a href="{{ route('landing-pages.index') }}">
        <i class="fa-solid fa-rocket"></i>
        <span>Landing Pages</span>
    </a>
</li>
```

---

## 12. Files Summary

### New Files to Create

| # | File | Purpose |
|---|---|---|
| 1 | `Modules/LandingPage/module.json` | Module registration |
| 2 | `Modules/LandingPage/app/Providers/LandingPageServiceProvider.php` | Service provider |
| 3 | `Modules/LandingPage/app/Providers/RouteServiceProvider.php` | Route provider |
| 4 | `Modules/LandingPage/database/migrations/..._create_landing_pages_table.php` | DB schema |
| 5 | `Modules/LandingPage/app/Models/LandingPage.php` | Eloquent model |
| 6 | `Modules/LandingPage/app/Services/LandingPageService.php` | Business logic |
| 7 | `Modules/LandingPage/app/Http/Controllers/LandingPageController.php` | Admin CRUD |
| 8 | `Modules/LandingPage/app/Http/Controllers/LandingFrontController.php` | Public render + order |
| 9 | `Modules/LandingPage/app/Http/Requests/StoreLandingPageRequest.php` | Validation |
| 10 | `Modules/LandingPage/app/Http/Middleware/LandingPageMode.php` | Route blocker |
| 11 | `Modules/LandingPage/resources/views/admin/index.blade.php` | Admin list page |
| 12 | `Modules/LandingPage/resources/views/admin/create.blade.php` | Admin create form |
| 13 | `Modules/LandingPage/resources/views/admin/edit.blade.php` | Admin edit form |
| 14 | `Modules/LandingPage/resources/views/layouts/landing.blade.php` | Public minimal layout |
| 15 | `Modules/LandingPage/resources/views/templates/template-1.blade.php` | T-Shirt template |
| 16 | `Modules/LandingPage/resources/views/templates/template-2.blade.php` | Shoes template |
| 17 | `Modules/LandingPage/resources/views/templates/template-3.blade.php` | Perfume template |
| 18 | `Modules/LandingPage/resources/views/templates/template-4.blade.php` | Beauty template |
| 19 | `Modules/LandingPage/resources/views/templates/template-5.blade.php` | Fashion template |
| 20 | `Modules/LandingPage/resources/views/order-success.blade.php` | Order confirmation |
| 21 | `Modules/LandingPage/routes/web.php` | Admin routes |
| 22 | `Modules/LandingPage/routes/front.php` | Public routes |
| 23 | `public/vendor/landing/css/*` | Landing page CSS files |
| 24 | `public/vendor/landing/js/*` | Landing page JS files |
| 25 | `public/vendor/landing/images/*` | Payment logos + default images |

### Existing Files to Modify

| # | File | Change |
|---|---|---|
| 1 | `Modules/Setting/resources/views/index.blade.php` | Add "Landing Page" tab (#6) |
| 2 | `Modules/Setting/app/Services/SettingService.php` | Add `updateLandingPage()` method |
| 3 | `Modules/Setting/app/Http/Controllers/SettingController.php` | Handle `landing_page` group in `update()` |
| 4 | `Modules/Setting/app/Http/Requests/UpdateSettingsRequest.php` | Add validation for landing_page fields |
| 5 | `bootstrap/app.php` | Register `LandingPageMode` middleware in `web` group |
| 6 | `Modules/Core/resources/views/partials/sidebar.blade.php` | Add "Landing Pages" menu item |

---

## 13. Implementation Order

| Step | Task | Depends On |
|---|---|---|
| 1 | Create module scaffold (module.json, providers, route files) | — |
| 2 | Create migration + run it | Step 1 |
| 3 | Create LandingPage model | Step 2 |
| 4 | Copy landing page assets to `public/vendor/landing/` | — |
| 5 | Create landing layout (`layouts/landing.blade.php`) | Step 4 |
| 6 | Convert 5 HTML templates → Blade with dynamic placeholders | Steps 3, 5 |
| 7 | Create LandingPageService | Step 3 |
| 8 | Create StoreLandingPageRequest | Step 3 |
| 9 | Create LandingPageController (admin CRUD) | Steps 7, 8 |
| 10 | Create admin views (index + create + edit with product picker) | Step 9 |
| 11 | Register admin routes + add sidebar menu item | Steps 9, 10 |
| 12 | Add "Landing Page" tab to Settings page | Step 7 |
| 13 | Create LandingPageMode middleware | Step 3 |
| 14 | Register middleware in bootstrap/app.php | Step 13 |
| 15 | Create LandingFrontController (render page + handle order) | Steps 6, 7 |
| 16 | Register public routes | Step 15 |
| 17 | Create order success page | Step 15 |
| 18 | End-to-end testing | All steps |

---

## 14. Verification Checklist

1. **Admin CRUD**: Create a landing page → select template-1 → pick 3 products → set hero text → save
2. **Admin Edit**: Edit the page → change template → change products → verify form works
3. **Admin List**: View all landing pages → verify activate/deactivate/delete actions
4. **Settings**: Go to Settings → Landing Page tab → switch to "Landing Page Mode" → select the page → save
5. **Landing Page Render**: Visit `/` → should show the selected landing page template with dynamic content
6. **Route Blocking**: Visit `/shop`, `/cart`, `/checkout`, `/customer/profile` → all redirect to `/`
7. **Admin Still Works**: Visit `/admin/dashboard` → should work normally (not blocked)
8. **Order Submission**: Fill out the landing page order form → submit → verify Ecommerce Order created
9. **Order Success**: After order → redirected to success page with order number
10. **Switch Back**: Settings → change mode to "Full eCommerce Site" → save → visit `/shop` → works again
11. **All 5 Templates**: Test each template renders correctly with dynamic content
12. **Responsive**: Test on mobile viewport (375px, 768px)
13. **Product Carousel**: Verify Slick carousel works with selected products
14. **Payment Methods**: Verify bKash/Nagad/Rocket form fields show/hide correctly
15. **Bengali Text**: Verify Hind Siliguri font renders Bengali text correctly
16. **Live Preview**: Edit a landing page → change hero title → verify iframe preview updates in real-time
17. **Preview in New Tab**: Click "Open Preview in New Tab" → full landing page renders correctly

---

## 15. Future: Multiple Homepage Themes (Full Website)

> This section documents the planned extension — **not part of the current implementation**.

Currently the storefront has a single homepage. The plan is to later add **selectable full-website themes** (not just landing pages) for different business types.

### How It Fits Into the Current Architecture

The settings mode toggle is designed to be extensible:

```
Current modes:
  • full_site      → Default ecommerce storefront (single theme)
  • landing_page   → Single product landing page

Future modes (extend, don't break):
  • full_site      → Full ecommerce with selectable theme
  • landing_page   → Single product landing page
```

### Future Theme System Concept

```
Settings → Appearance → Theme
┌──────────────────────────────────────────────────┐
│  Website Mode                                    │
│  [● Full eCommerce]  [○ Landing Page]            │
│                                                  │
│  Homepage Theme (when Full eCommerce selected)   │
│  ┌────────┐  ┌────────┐  ┌────────┐             │
│  │ Theme1 │  │ Theme2 │  │ Theme3 │   ...        │
│  │ Default│  │Fashion │  │Grocery │             │
│  │  ✓     │  │        │  │        │             │
│  └────────┘  └────────┘  └────────┘             │
│                                                  │
│  Active Landing Page (when Landing Page selected)│
│  [▼ Select a landing page...]                    │
└──────────────────────────────────────────────────┘
```

### What Needs to Be Built Later

| Component | Description |
|---|---|
| `storefront_themes` table | Theme registry: name, slug, preview image, folder path, is_active |
| Theme views folder | `Modules/Ecommerce/resources/views/themes/{theme-slug}/` — home, shop, product, cart, checkout, etc. |
| Theme assets | `public/vendor/themes/{theme-slug}/css/`, `js/`, `images/` |
| Settings key | `appearance.active_theme` (string, default: `default`) |
| Theme middleware | Resolves active theme and sets the view namespace dynamically |
| Admin theme manager | Preview themes, activate/deactivate, customize colors/sections |

### Design Principles (Apply Now)

To keep the current implementation future-proof:

1. **Settings mode key is a string, not boolean** — already done (`full_site` / `landing_page`), easy to add more values
2. **Middleware checks mode value, not a boolean flag** — already designed this way
3. **Landing page templates are in their own module** — won't conflict with future storefront themes
4. **Ecommerce views remain untouched** — the current single homepage stays as-is, future themes add alternatives alongside it
5. **Don't hardcode "two modes only"** — use `match` or `in_array` checks, not `if/else`

---

## 16. Google Tag Manager & Facebook Pixel Integration

### Overview

Full GTM and Facebook Pixel integration across **both** the storefront (full site) and landing pages. Admin controls credentials, toggle on/off, from the Settings panel. All standard ecommerce events are tracked.

### Settings (group: `tracking`)

| Key | Type | Default | Description |
|---|---|---|---|
| `gtm_enabled` | boolean | false | Enable/disable GTM |
| `gtm_container_id` | string | null | GTM Container ID (e.g. `GTM-XXXXXXX`) |
| `fbpixel_enabled` | boolean | false | Enable/disable Facebook Pixel |
| `fbpixel_id` | string | null | Facebook Pixel ID (e.g. `1234567890`) |
| `fbpixel_access_token` | string | null | Conversions API access token (server-side, optional) |

### Settings Page: New "Tracking & Analytics" Tab

Add as tab #10 (after SMS Gateway) in `Modules/Setting/resources/views/index.blade.php`:

```
┌──────────────────────────────────────────────────────────────────┐
│  Tracking & Analytics                                            │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Google Tag Manager                                              │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Enable GTM          [Toggle Switch]                     │   │
│  │  Container ID         [GTM-XXXXXXX_____]                 │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  Facebook Pixel                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Enable Pixel         [Toggle Switch]                    │   │
│  │  Pixel ID             [1234567890______]                 │   │
│  │  Conversions API Token [_____________] (optional)        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  Event Status (read-only summary)                                │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  PageView          ✓ GTM  ✓ Pixel                        │   │
│  │  ViewContent       ✓ GTM  ✓ Pixel                        │   │
│  │  AddToCart         ✓ GTM  ✓ Pixel                        │   │
│  │  InitiateCheckout  ✓ GTM  ✓ Pixel                        │   │
│  │  Purchase          ✓ GTM  ✓ Pixel                        │   │
│  │  Search            ✓ GTM  ✓ Pixel                        │   │
│  │  Lead (Landing)    ✓ GTM  ✓ Pixel                        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│                                        [Save Tracking Settings]  │
└──────────────────────────────────────────────────────────────────┘
```

### Implementation Architecture

#### Blade Component: `<x-core::tracking-scripts />`

A single Blade component injected into **all public-facing layouts**:
- `Modules/Ecommerce/resources/views/layouts/storefront.blade.php` (full site)
- `Modules/LandingPage/resources/views/layouts/landing.blade.php` (landing pages)

```blade
{{-- resources/views/components/tracking-scripts.blade.php --}}
@php
    $gtmEnabled = \Modules\Setting\Models\Setting::get('tracking', 'gtm_enabled', false);
    $gtmId = \Modules\Setting\Models\Setting::get('tracking', 'gtm_container_id');
    $fbEnabled = \Modules\Setting\Models\Setting::get('tracking', 'fbpixel_enabled', false);
    $fbId = \Modules\Setting\Models\Setting::get('tracking', 'fbpixel_id');
@endphp

{{-- GTM Head Script (goes in <head>) --}}
@if($gtmEnabled && $gtmId)
<script>
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{{ $gtmId }}');
</script>
@endif

{{-- Facebook Pixel Base Code (goes in <head>) --}}
@if($fbEnabled && $fbId)
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ $fbId }}');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ $fbId }}&ev=PageView&noscript=1"/></noscript>
@endif
```

#### Blade Component: `<x-core::tracking-body />`

GTM noscript tag (goes immediately after `<body>`):

```blade
@if($gtmEnabled && $gtmId)
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
```

#### JS Helper: `window.BizPOS.track()`

A global JS function in `public/js/app.js` (or a dedicated `tracking.js`) that fires events to both GTM dataLayer and Facebook Pixel simultaneously:

```javascript
window.BizPOS = window.BizPOS || {};
window.BizPOS.track = function(eventName, params) {
    params = params || {};

    // Push to GTM dataLayer
    if (window.dataLayer) {
        window.dataLayer.push(Object.assign({ event: eventName }, params));
    }

    // Push to Facebook Pixel
    if (typeof fbq === 'function') {
        fbq('track', eventName, params);
    }
};
```

### Complete Event List

#### Standard Events (Both GTM + Pixel)

| # | Event Name | FB Pixel Event | GTM dataLayer Event | When Fired | Data Sent |
|---|---|---|---|---|---|
| 1 | **PageView** | `PageView` | (automatic via GTM) | Every page load | page_title, page_path |
| 2 | **ViewContent** | `ViewContent` | `view_item` | Product detail page | content_ids, content_name, content_type, value, currency |
| 3 | **ViewCategory** | `ViewContent` | `view_item_list` | Category/shop page | content_category, content_type, item_list_name |
| 4 | **Search** | `Search` | `search` | Search results page | search_string, content_category |
| 5 | **AddToCart** | `AddToCart` | `add_to_cart` | Add product to cart (button click) | content_ids, content_name, content_type, value, currency, quantity |
| 6 | **RemoveFromCart** | — | `remove_from_cart` | Remove item from cart | content_ids, content_name, value, currency |
| 7 | **ViewCart** | — | `view_cart` | Cart page load | items[], value, currency |
| 8 | **InitiateCheckout** | `InitiateCheckout` | `begin_checkout` | Checkout page load | content_ids, num_items, value, currency |
| 9 | **AddPaymentInfo** | `AddPaymentInfo` | `add_payment_info` | Payment method selected | content_ids, value, currency, payment_type |
| 10 | **Purchase** | `Purchase` | `purchase` | Order success page | content_ids, content_name, num_items, value, currency, order_id |
| 11 | **Lead** | `Lead` | `generate_lead` | Landing page order submit | content_name, value, currency |
| 12 | **AddToWishlist** | `AddToWishlist` | `add_to_wishlist` | Wishlist add button click | content_ids, content_name, value, currency |
| 13 | **CompleteRegistration** | `CompleteRegistration` | `sign_up` | Customer registration success | content_name, status |
| 14 | **Contact** | `Contact` | `contact` | Phone number click / contact form | — |

#### Event Data Payloads — All 14 Events

**1. PageView (Every Page Load)**:
```javascript
// Automatic — fired by GTM snippet and fbq('track', 'PageView') in <head>
// No manual call needed. GTM dataLayer auto-captures:
window.dataLayer.push({
    event: 'page_view',
    page_title: document.title,
    page_path: window.location.pathname,
    page_url: window.location.href
});
```

**2. ViewContent (Product Detail Page)**:
```javascript
BizPOS.track('ViewContent', {
    content_ids: ['SKU-001'],
    content_name: 'Premium T-Shirt',
    content_type: 'product',
    content_category: 'Clothing',
    value: 1190.00,
    currency: 'BDT'
});
// Blade source data:
// content_ids:     [$product->sku]
// content_name:    $product->name
// content_category: $product->category->name
// value:           $product->sell_price
```

**3. ViewCategory (Category / Shop Page)**:
```javascript
BizPOS.track('ViewContent', {
    content_type: 'product_group',
    content_category: 'Clothing',
    content_name: 'Shop — Clothing',
    contents: [
        { id: 'SKU-001', quantity: 1 },
        { id: 'SKU-002', quantity: 1 },
        { id: 'SKU-003', quantity: 1 }
    ],
    num_items: 15
});
// GTM also gets:
window.dataLayer.push({
    event: 'view_item_list',
    item_list_name: 'Clothing',
    items: [
        { item_id: 'SKU-001', item_name: 'Premium T-Shirt', price: 1190.00, item_category: 'Clothing', index: 0 },
        { item_id: 'SKU-002', item_name: 'Polo Shirt', price: 890.00, item_category: 'Clothing', index: 1 },
        // ... first 10 products on the page
    ]
});
// Blade source data:
// content_category: $category->name ?? 'All Products'
// contents:         $products->map(fn($p) => ['id' => $p->sku, 'quantity' => 1])
// num_items:        $products->total()
// items:            $products->take(10)->map(...)
```

**4. Search (Search Results Page)**:
```javascript
BizPOS.track('Search', {
    search_string: 't-shirt',
    content_category: 'All Products',
    contents: [
        { id: 'SKU-001', quantity: 1 },
        { id: 'SKU-005', quantity: 1 }
    ],
    num_items: 8
});
// GTM also gets:
window.dataLayer.push({
    event: 'search',
    search_term: 't-shirt',
    items: [
        { item_id: 'SKU-001', item_name: 'Premium T-Shirt', price: 1190.00, index: 0 },
        // ... first 10 results
    ]
});
// Blade source data:
// search_string: request('search')
// contents:      $products->map(fn($p) => ['id' => $p->sku, 'quantity' => 1])
// num_items:     $products->total()
```

**5. AddToCart (Add to Cart Button Click)**:
```javascript
BizPOS.track('AddToCart', {
    content_ids: ['SKU-001'],
    content_name: 'Premium T-Shirt',
    content_type: 'product',
    content_category: 'Clothing',
    value: 1190.00,
    currency: 'BDT',
    quantity: 1
});
// GTM also gets:
window.dataLayer.push({
    event: 'add_to_cart',
    currency: 'BDT',
    value: 1190.00,
    items: [{
        item_id: 'SKU-001',
        item_name: 'Premium T-Shirt',
        item_category: 'Clothing',
        price: 1190.00,
        quantity: 1
    }]
});
// JS source: product data attributes on the add-to-cart button or AJAX response
// data-sku, data-name, data-price, data-category, data-qty
```

**6. RemoveFromCart (Remove Item from Cart)**:
```javascript
// FB Pixel: no standard RemoveFromCart event — skip
// GTM dataLayer only:
window.dataLayer.push({
    event: 'remove_from_cart',
    currency: 'BDT',
    value: 1190.00,
    items: [{
        item_id: 'SKU-001',
        item_name: 'Premium T-Shirt',
        item_category: 'Clothing',
        price: 1190.00,
        quantity: 1
    }]
});
// JS source: data from the cart row being removed (AJAX callback)
```

**7. ViewCart (Cart Page Load)**:
```javascript
// FB Pixel: no standard ViewCart — use custom event
if (typeof fbq === 'function') {
    fbq('trackCustom', 'ViewCart', {
        content_ids: ['SKU-001', 'SKU-003'],
        num_items: 2,
        value: 2380.00,
        currency: 'BDT'
    });
}
// GTM:
window.dataLayer.push({
    event: 'view_cart',
    currency: 'BDT',
    value: 2380.00,
    items: [
        { item_id: 'SKU-001', item_name: 'Premium T-Shirt', price: 1190.00, quantity: 1 },
        { item_id: 'SKU-003', item_name: 'Polo Shirt Blue', price: 1190.00, quantity: 1 }
    ]
});
// Blade source data:
// content_ids: $cartItems->map(fn($i) => $i['sku'])
// items:       $cartItems->map(fn($i) => [...])
// value:       $cartTotal
```

**8. InitiateCheckout (Checkout Page Load)**:
```javascript
BizPOS.track('InitiateCheckout', {
    content_ids: ['SKU-001', 'SKU-003'],
    content_type: 'product',
    contents: [
        { id: 'SKU-001', quantity: 1, item_price: 1190.00 },
        { id: 'SKU-003', quantity: 1, item_price: 1190.00 }
    ],
    num_items: 2,
    value: 2380.00,
    currency: 'BDT'
});
// GTM:
window.dataLayer.push({
    event: 'begin_checkout',
    currency: 'BDT',
    value: 2380.00,
    coupon: '',
    items: [
        { item_id: 'SKU-001', item_name: 'Premium T-Shirt', price: 1190.00, quantity: 1 },
        { item_id: 'SKU-003', item_name: 'Polo Shirt Blue', price: 1190.00, quantity: 1 }
    ]
});
// Blade source data: same as ViewCart — cart items, total
```

**9. AddPaymentInfo (Payment Method Selected)**:
```javascript
BizPOS.track('AddPaymentInfo', {
    content_ids: ['SKU-001', 'SKU-003'],
    content_type: 'product',
    value: 2500.00,
    currency: 'BDT',
    payment_type: 'bkash'
});
// GTM:
window.dataLayer.push({
    event: 'add_payment_info',
    currency: 'BDT',
    value: 2500.00,
    payment_type: 'bkash',
    items: [
        { item_id: 'SKU-001', item_name: 'Premium T-Shirt', price: 1190.00, quantity: 1 },
        { item_id: 'SKU-003', item_name: 'Polo Shirt Blue', price: 1190.00, quantity: 1 }
    ]
});
// JS source: payment method radio/accordion change event
// payment_type: 'cod' | 'bkash' | 'nagad' | 'rocket' | 'card' | 'bank_transfer'
```

**10. Purchase (Order Success Page)**:
```javascript
BizPOS.track('Purchase', {
    content_ids: ['SKU-001', 'SKU-003'],
    content_name: 'Order #EC20260409001',
    content_type: 'product',
    contents: [
        { id: 'SKU-001', quantity: 1, item_price: 1190.00 },
        { id: 'SKU-003', quantity: 1, item_price: 1190.00 }
    ],
    num_items: 2,
    value: 2500.00,
    currency: 'BDT'
});
// GTM:
window.dataLayer.push({
    event: 'purchase',
    transaction_id: 'EC20260409001',
    currency: 'BDT',
    value: 2500.00,
    tax: 0.00,
    shipping: 120.00,
    coupon: '',
    items: [
        { item_id: 'SKU-001', item_name: 'Premium T-Shirt', price: 1190.00, quantity: 1, item_category: 'Clothing' },
        { item_id: 'SKU-003', item_name: 'Polo Shirt Blue', price: 1190.00, quantity: 1, item_category: 'Clothing' }
    ]
});
// Blade source data:
// transaction_id: $order->order_number
// value:          $order->grand_total
// tax:            $order->tax_amount
// shipping:       $order->shipping_charge
// coupon:         $order->coupon_code
// items:          $order->items->map(...)
// content_ids:    $order->items->pluck('product.sku')
```

**11. Lead (Landing Page Order Submit)**:
```javascript
BizPOS.track('Lead', {
    content_name: 'Landing Page: Summer T-Shirt Sale',
    content_category: 'Landing Page',
    value: 1190.00,
    currency: 'BDT',
    num_items: 1
});
// GTM:
window.dataLayer.push({
    event: 'generate_lead',
    currency: 'BDT',
    value: 1190.00,
    landing_page_name: 'Summer T-Shirt Sale',
    landing_page_template: 'template-1'
});
// JS source: form submit handler, reads total from order summary
```

**12. AddToWishlist (Wishlist Button Click)**:
```javascript
BizPOS.track('AddToWishlist', {
    content_ids: ['SKU-001'],
    content_name: 'Premium T-Shirt',
    content_type: 'product',
    content_category: 'Clothing',
    value: 1190.00,
    currency: 'BDT'
});
// GTM:
window.dataLayer.push({
    event: 'add_to_wishlist',
    currency: 'BDT',
    value: 1190.00,
    items: [{
        item_id: 'SKU-001',
        item_name: 'Premium T-Shirt',
        item_category: 'Clothing',
        price: 1190.00
    }]
});
// JS source: product data attributes on wishlist button or AJAX response
```

**13. CompleteRegistration (Customer Registration Success)**:
```javascript
BizPOS.track('CompleteRegistration', {
    content_name: 'Customer Registration',
    status: 'success'
});
// GTM:
window.dataLayer.push({
    event: 'sign_up',
    method: 'email'
});
// Blade source: fired on the registration success page or redirect-after-register page
```

**14. Contact (Phone Number Click / Contact Action)**:
```javascript
BizPOS.track('Contact', {
    content_name: 'Phone Call',
    content_category: 'Landing Page',
    value: 0,
    currency: 'BDT'
});
// GTM:
window.dataLayer.push({
    event: 'contact',
    method: 'phone',
    phone_number: '01XXXXXXXXX'
});
// JS source: click handler on <a href="tel:..."> links and contact buttons
// For landing pages: attach to the contact phone number display
// For storefront: attach to any tel: links in header/footer
```

#### Enhanced `BizPOS.track()` Helper (handles GTM/Pixel differences)

```javascript
'use strict';

window.BizPOS = window.BizPOS || {};

/**
 * Track an event across GTM dataLayer and Facebook Pixel.
 *
 * @param {string} eventName  - Unified event name (ViewContent, AddToCart, etc.)
 * @param {object} params     - Event data payload
 * @param {object} [options]  - { gtmEvent: 'custom_gtm_name', fbEvent: 'custom_fb_name', fbCustom: false }
 */
window.BizPOS.track = function(eventName, params, options) {
    params = params || {};
    options = options || {};

    // ── GTM dataLayer ──
    if (window.dataLayer) {
        var gtmEvent = options.gtmEvent || {
            'ViewContent': 'view_item',
            'ViewCategory': 'view_item_list',
            'Search': 'search',
            'AddToCart': 'add_to_cart',
            'RemoveFromCart': 'remove_from_cart',
            'ViewCart': 'view_cart',
            'InitiateCheckout': 'begin_checkout',
            'AddPaymentInfo': 'add_payment_info',
            'Purchase': 'purchase',
            'Lead': 'generate_lead',
            'AddToWishlist': 'add_to_wishlist',
            'CompleteRegistration': 'sign_up',
            'Contact': 'contact'
        }[eventName] || eventName;

        var gtmData = Object.assign({ event: gtmEvent }, params);
        // Map FB-style fields to GA4-style for GTM
        if (params.content_ids && !params.items) {
            gtmData.items = params.content_ids.map(function(id) {
                return { item_id: id };
            });
        }
        window.dataLayer.push(gtmData);
    }

    // ── Facebook Pixel ──
    if (typeof fbq === 'function') {
        var fbEvent = options.fbEvent || eventName;
        var isCustom = options.fbCustom || ['RemoveFromCart', 'ViewCart'].indexOf(eventName) !== -1;

        if (isCustom) {
            fbq('trackCustom', fbEvent, params);
        } else {
            fbq('track', fbEvent, params);
        }
    }
};
```

### Where Events Are Fired (File Locations)

| Event | Location | Trigger |
|---|---|---|
| PageView | `<x-core::tracking-scripts>` in `<head>` | Automatic on every page |
| ViewContent | `Modules/Ecommerce/resources/views/storefront/product-show.blade.php` | `@push('scripts')` on page load |
| ViewCategory | `Modules/Ecommerce/resources/views/storefront/shop.blade.php` | `@push('scripts')` on page load |
| Search | `Modules/Ecommerce/resources/views/storefront/shop.blade.php` | When `request('search')` is present |
| AddToCart | `public/js/storefront.js` (or inline) | AJAX success callback of add-to-cart button |
| RemoveFromCart | `public/js/storefront.js` | AJAX success callback of remove button |
| ViewCart | `Modules/Ecommerce/resources/views/storefront/cart.blade.php` | `@push('scripts')` on page load |
| InitiateCheckout | `Modules/Ecommerce/resources/views/storefront/checkout.blade.php` | `@push('scripts')` on page load |
| AddPaymentInfo | `public/js/storefront.js` | Payment method radio/accordion change |
| Purchase | `Modules/Ecommerce/resources/views/storefront/checkout-success.blade.php` | `@push('scripts')` on page load |
| Lead | `Modules/LandingPage/resources/views/templates/*.blade.php` | Form submit handler (before POST) |
| AddToWishlist | `public/js/storefront.js` | AJAX success callback of wishlist button |
| CompleteRegistration | `Modules/Ecommerce/resources/views/storefront/register-success.blade.php` | `@push('scripts')` on page load |
| Contact | Landing page templates | Phone number link click handler |

### Landing Page Specific Events

Landing pages fire a reduced set of events since there's no cart/checkout flow:

| Event | When |
|---|---|
| PageView | Landing page loads |
| ViewContent | Landing page loads (the promoted product) |
| Lead | Order form submitted |
| Purchase | Order success page loads |
| Contact | Phone number clicked |
| AddPaymentInfo | Payment method selected in order form |

### Optional: Server-Side Events (Facebook Conversions API)

For better attribution (iOS 14+ privacy changes), the **Purchase** and **Lead** events can also be sent server-side via Facebook Conversions API:

**Where:** In the order processing flow (controller/service, after order is saved)

```php
// In LandingFrontController@submitOrder or CheckoutController@process
if (Setting::get('tracking', 'fbpixel_enabled') && Setting::get('tracking', 'fbpixel_access_token')) {
    dispatch(new SendFacebookConversionEvent(
        event_name: 'Purchase', // or 'Lead'
        pixel_id: Setting::get('tracking', 'fbpixel_id'),
        access_token: Setting::get('tracking', 'fbpixel_access_token'),
        event_data: [
            'value' => $order->total,
            'currency' => 'BDT',
            'content_ids' => $order->items->pluck('product_id'),
            'order_id' => $order->order_number,
        ],
        user_data: [
            'ph' => hash('sha256', $customer->phone),
            'fn' => hash('sha256', strtolower($customer->name)),
        ],
    ));
}
```

This is dispatched as a **queued job** so it doesn't slow down the user's request. The job sends an HTTP POST to `https://graph.facebook.com/v18.0/{pixel_id}/events`.

### Files to Create/Modify for Tracking

| # | File | Action |
|---|---|---|
| 1 | `Modules/Core/resources/views/components/tracking-head.blade.php` | **Create** — GTM + Pixel `<head>` scripts |
| 2 | `Modules/Core/resources/views/components/tracking-body.blade.php` | **Create** — GTM `<noscript>` after `<body>` |
| 3 | `public/js/tracking.js` | **Create** — `BizPOS.track()` helper function |
| 4 | `app/Jobs/SendFacebookConversionEvent.php` | **Create** — Server-side Conversions API job (optional) |
| 5 | `Modules/Setting/resources/views/index.blade.php` | **Modify** — Add "Tracking & Analytics" tab |
| 6 | `Modules/Setting/app/Services/SettingService.php` | **Modify** — Add `updateTracking()` method |
| 7 | `Modules/Setting/app/Http/Controllers/SettingController.php` | **Modify** — Handle `tracking` group |
| 8 | `Modules/Setting/app/Http/Requests/UpdateSettingsRequest.php` | **Modify** — Add tracking validation rules |
| 9 | Storefront layout (`layouts/storefront.blade.php`) | **Modify** — Include `<x-core::tracking-head>` and `<x-core::tracking-body>` |
| 10 | Landing layout (`layouts/landing.blade.php`) | **Modify** — Include `<x-core::tracking-head>` and `<x-core::tracking-body>` |
| 11 | All storefront views (product, shop, cart, checkout, success) | **Modify** — Add `BizPOS.track()` calls in `@push('scripts')` |
| 12 | Landing page templates (template-1..5) | **Modify** — Add `BizPOS.track()` calls for Lead/Contact events |
| 13 | `public/js/storefront.js` | **Modify** — Add tracking calls in AJAX callbacks (AddToCart, Wishlist, etc.) |
