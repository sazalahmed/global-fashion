# Menu Builder — Analysis & Plan

Status: **Planned (not yet implemented)**
Scope: Admin-managed menu builder for the eCommerce storefront, under the Ecommerce ("Online") group.

---

## 1. Current state (what drives each surface today)

| Surface | File | How it's built today |
|---|---|---|
| **Desktop main menu** | `Modules/Ecommerce/resources/views/storefront/partials/navigation.blade.php` | "Browse Categories" dropdown = dynamic (`$menuCategories`, view composer). Main items (Home, Shop, Categories, Flash Deals, Blog, **Contact → dead `#`**) = **hardcoded**. Right icons (Wishlist, Compare, Cart, Account) = **hardcoded**, counts from `session()`. |
| **Footer** | `partials/footer.blade.php` | **3 link columns** — **Company**, **Category** (dynamic `$footerCategories` top-5), **Quick Links** (incl. dead `FAQ #`) — plus a **Contact** column that is contact *info* (address/phone/email, not links) and a logo/social column. Link columns **hardcoded**; logo/social/contact/payment-icons already dynamic (settings-driven). |
| **Mobile drawer** | `partials/mobile-menu.blade.php` | Categories tab (dynamic) + "Menu" tab = **hardcoded** duplicate of desktop. |
| **Mobile bottom nav** | `partials/mobile-bottom-nav.blade.php` | Home / Categories / Search / Cart / Profile = **hardcoded**. |
| **Cart / Wishlist / Compare** | inside navigation + mobile partials | Icon + live count badge widgets (session-backed). Routes exist; markup hardcoded in 3+ places. |

> Note: `partials/header.blade.php` holds only the logo, mobile-menu toggle and the search form — no menu links/icons — so it is **not** part of the menu refactor.

**Problems:** menu links are hardcoded and **duplicated across 4 partials** (navigation, footer, mobile-menu, mobile-bottom-nav), not admin-editable, with dead links (`Contact`, `FAQ`). No single source of truth.

**Reusable conventions found:**
- Admin **Ecommerce ("Online") group**: `ecommerce.*` routes, `ContentController`, sidebar in `Modules/Core/resources/views/partials/sidebar.blade.php`. **Homepage Builder** is the precedent (model `HomepageSection` with `sort_order`/`is_active`/`settings` JSON, AJAX toggle, drag reorder).
- **Shared admin reorder JS** in `public/js/app.js` (`tbody.bp-reorderable` + `.bp-drag-handle` → POST `{ordered_ids:[…]}` to `data-reorder-url`).
- **Storefront view composer** in `EcommerceServiceProvider::registerStorefrontViewComposers()` injects `menuCategories`/`footerCategories`/`customerAuthEnabled` — the hook to inject resolved menus.
- Settings caching pattern (`EcommerceSetting` + `Cache::forget`) like the price-separator toggle.
- **Greenfield**: no existing `Menu`/`MenuItem` model.

---

## 2. Confirmed decisions
- **Nested drag-and-drop** builder (WordPress-style indent/nest).
- **Widgets managed with defaults** — Cart/Wishlist/Compare/Account/Search seeded as `widget` items, reorderable/toggleable, with sensible defaults pre-set.
- Currency/price work already standardizes on `currency_symbol()` + `bd_price()` — menus are independent of that.

---

## 3. Architecture

### Data model (2 tables)
- **`menus`** — one row per *location* (seeded, not free-form). `location` is the canonical key.
  Locations: `header_main`, `footer`, `mobile_drawer`, `mobile_bottom`.
- **`menu_items`** — self-nesting items belonging to a menu.

### Migrations & schema (Laravel Blueprint)

> Follows project DB rules (Section 14): `foreignId()->constrained()`, snake_case, indexes, `decimal` for money (n/a here), explicit `down()`.

**`*_create_menus_table.php`**
```php
Schema::create('menus', function (Blueprint $table) {
    $table->id();
    $table->string('name');                       // "Main Menu", "Footer"
    $table->string('location')->unique();         // header_main | footer | mobile_drawer | mobile_bottom
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**`*_create_menu_items_table.php`**
```php
Schema::create('menu_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
    // Self-reference. nullOnDelete (NOT cascade) — MySQL self-referencing
    // ON DELETE CASCADE is unreliable; child/subtree deletion is handled in
    // the MenuItem model's `deleting` hook (see "Edit & delete" below).
    $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();

    $table->string('label');                      // display text (also used for headings)
    $table->string('type')->default('url');       // route|category|url|heading|categories_dropdown|widget
    $table->string('value')->nullable();          // route name | category_id | URL | widget key
    $table->string('target')->default('_self');   // _self | _blank
    $table->string('icon')->nullable();           // FontAwesome class or asset key
    $table->string('css_class')->nullable();      // optional extra classes
    $table->string('visibility')->default('all'); // all | guest | auth
    $table->json('settings')->nullable();         // per-item extras (e.g. categories_dropdown limit)
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['menu_id', 'parent_id', 'sort_order']);
});
```

**Item `type` values:**
| type | value → resolves to | use |
|---|---|---|
| `route` | whitelisted named route | Home, Shop, Flash Deals… |
| `category` | category id → `category.show` slug | category links |
| `url` | raw URL | custom/external (Contact, FAQ) |
| `heading` | — (non-link label) | **footer column titles**, group headers |
| `categories_dropdown` | — (auto-fills live categories, `limit` setting) | "Browse Categories" mega-menu |
| `widget` | `cart`/`wishlist`/`compare`/`account`/`search` | icon+count widgets |

> Footer = **one** `footer` menu where top-level `heading` items are the columns and their children are the links. Mobile drawer & bottom nav are their own locations (seeded from current markup) for independent control.

### Models

**`Menu`**
```php
class Menu extends Model {
    protected $fillable = ['name', 'location', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function items(): HasMany;            // all items for this menu
    public function rootItems(): HasMany;        // parent_id = null, ordered (eager-loads recursiveChildren)
    public function scopeActive($q);             // where is_active = true
    public function scopeLocation($q, string $location);

    public const LOCATIONS = ['header_main', 'footer', 'mobile_drawer', 'mobile_bottom'];
}
```

**`MenuItem`**
```php
class MenuItem extends Model {
    protected $fillable = [
        'menu_id','parent_id','label','type','value','target',
        'icon','css_class','visibility','settings','sort_order','is_active',
    ];
    protected $casts = ['settings' => 'array', 'is_active' => 'boolean'];

    public function menu(): BelongsTo;
    public function parent(): BelongsTo;         // self
    public function children(): HasMany;         // self, ordered by sort_order
    public function recursiveChildren(): HasMany;// children()->with('recursiveChildren') for deep trees

    public function scopeActive($q);
    public function scopeOrdered($q);            // orderBy sort_order

    // type+value → href. route()=whitelisted route name; category=category.show by id→slug;
    // url=raw; heading/categories_dropdown/widget = null (rendered specially).
    public function resolveUrl(): ?string;

    public function isVisibleTo(?Authenticatable $customer): bool; // all|guest|auth gate
    public function badgeCount(): ?int;          // widget items: cart/wishlist/compare session counts

    public const TYPES = ['route','category','url','heading','categories_dropdown','widget'];
    public const WIDGETS = ['cart','wishlist','compare','account','search'];

    // Recursive subtree delete — see "Edit & delete" below.
    protected static function booted(): void {
        static::deleting(function (MenuItem $item) {
            $item->children->each->delete();     // cascades the whole subtree
        });
    }
}
```

### Storefront integration
- New `MenuService`:
  ```php
  getTree(string $location, ?Authenticatable $customer = null): array; // resolved + visibility-filtered + ordered nested array (cached per location + auth-state)
  forget(string $location): void;     // or forgetAll() — called after any admin save
  ```
  `getTree()` returns plain arrays (not models) so the cache is serializable:
  `['label','url','target','icon','css_class','type','value','is_widget','badge_count','children'=>[…]]`.
- Extend the **view composer** (`EcommerceServiceProvider::registerStorefrontViewComposers`) to inject
  `$mainMenu`, `$footerMenu`, `$mobileMenu`, `$bottomNav` (only `if (!$view->offsetExists(...))`, matching the existing pattern).
- Reusable recursive partial `storefront.partials.menu-render` — takes `['items' => $tree, 'style' => 'main|footer|mobile|bottom']` and renders `<li><a>` trees; a `widget` branch emits the existing cart/wishlist/compare/account/search icon+count markup (so counts keep updating via `cart.js`).
- Refactor the 4 partials to call `@include('…menu-render', …)` — **each wrapped so that when its location has no items it falls back to the current hardcoded markup**; nothing breaks before the seeder runs.
- Categories stay live via `category` / `categories_dropdown` item types (resolve against `Category` at render).

### Admin (under Ecommerce group)

**Routes (`ecommerce.menus.*`)**
| Method | URI | Name | Purpose |
|---|---|---|---|
| GET | `/ecommerce/menus` | `menus.index` | List the 4 menu locations + item counts |
| GET | `/ecommerce/menus/{menu}/edit` | `menus.edit` | Builder UI for one location |
| POST | `/ecommerce/menus/{menu}/items` | `menus.items.store` | Add an item |
| PUT | `/ecommerce/menus/items/{item}` | `menus.items.update` | **Edit** an item |
| DELETE | `/ecommerce/menus/items/{item}` | `menus.items.destroy` | **Delete** an item (+ its subtree) |
| POST | `/ecommerce/menus/{menu}/reorder` | `menus.reorder` | Persist nested order (tree payload) |
| POST | `/ecommerce/menus/items/{item}/toggle` | `menus.items.toggle` | Show/hide an item |
| POST | `/ecommerce/menus/{menu}/clear` | `menus.clear` | **Delete all items** in this menu |
| POST | `/ecommerce/menus/{menu}/reset` | `menus.reset` | Clear + re-seed this location to defaults |

**`MenuController` methods:** `index`, `edit`, `storeItem`, `updateItem`, `destroyItem`, `reorder`, `toggleItem`, `clear`, `reset`. Thin — delegates persistence/tree-rebuild to `MenuService` (SRP).

**Builder UI** (`menus/builder.blade.php`, two-pane, WordPress-style):
- **Left "Add item"** accordion: Routes/Pages (whitelisted route picker), Category picker (select from active categories), Custom URL (label + URL + target), Widget (cart/wishlist/compare/account/search), Heading (label only). "Add to menu" appends to the tree.
- **Right "Menu structure"**: nested, drag-to-reorder/indent tree (SortableJS, local). Each row has a drag handle, label, type badge, an **Edit** disclosure (label, target `_self/_blank`, visibility `all/guest/auth`, icon, css_class, plus `limit` for categories_dropdown) and a **Delete** button (confirm; warns that nested children are removed too).
- **Bulk:** "Clear menu" (delete all) and "Reset to default" buttons, both behind confirm dialogs.

**Edit & delete behavior (full lifecycle):**
- **Edit:** inline disclosure per item → PUT `menus.items.update` (AJAX) via `UpdateMenuItemRequest`; updates label/type/value/target/visibility/icon/css_class/settings. Tree re-renders the row.
- **Delete (single):** DELETE `menus.items.destroy` with a confirm. The `MenuItem::deleting` hook **recursively deletes the whole subtree** (children, grandchildren), so no orphans. Cache forgotten.
- **Delete all / Clear:** POST `menus.clear` removes every item in the location (confirm) → empty menu → storefront falls back to hardcoded defaults until repopulated.
- **Reset to default:** POST `menus.reset` clears then re-runs that location's seed block — one-click restore.
- **Reorder:** SortableJS emits the full nested tree (`[{id, children:[…]}]`); `reorder` walks it, persisting `parent_id` + `sort_order` for every node in a single `DB::transaction`.
- All mutations call `MenuService::forget()` so the storefront reflects changes immediately.

- **Nested-sortable library served locally** (SortableJS `Sortable.min.js` under `public/vendor/sortablejs/`, no CDN), loaded only on the builder page via `@push('scripts')`.
- **Sidebar:** add **"Menus"** link in the Online group (after "Homepage Builder") with `request()->routeIs('ecommerce.menus.*')` active state.

### Seeder + migration
- Migrations as specified above.
- `MenuSeeder` reproducing **today's exact menus** so day-one behavior is identical, then editable. Run with:
  `php artisan db:seed --class="Modules\Ecommerce\Database\Seeders\MenuSeeder"`
- Idempotent: `updateOrCreate` menus by `location`; clear+recreate items per location on re-seed.

**Seed contents (mirrors current markup):**
- `header_main`: `route` Home, `route` Shop, `route` Categories, `route` Flash Deals, `route` Blog, `url` Contact (`#` placeholder, now editable). Plus a leading `categories_dropdown` ("Browse Categories", `settings.limit=10`) and trailing widgets: `widget` wishlist, `widget` compare, `widget` cart, `widget` account.
- `footer`: four `heading` parents with children —
  - **Company** → Shop, Categories, Blog, Login/My-Account (visibility-aware).
  - **Category** → `categories_dropdown` (or curated category links, `limit=5`).
  - **Quick Links** → Flash Deals, Wishlist, Cart, `url` FAQ (editable).
  - **Contact** → handled by existing dynamic block (not a link list) — left as-is.
- `mobile_drawer`: same items as `header_main` main list (Home/Shop/Categories/Flash Deals/Blog/Wishlist/Cart) + categories tab stays live.
- `mobile_bottom`: `route` Home, `route` Categories, `widget` search, `widget` cart, `widget` account.

### Caching / performance
- Cache key per location: `ecommerce.menu.{location}` (and a guest/auth variant since visibility differs). Forget on every create/update/delete/reorder/toggle/clear/reset.
- Eager-load: `rootItems.recursiveChildren`; resolve all `category` items' slugs in one query (avoid N+1).

### Security / validation
- **Route whitelist:** `route` type `value` must be in an allowlist of storefront route names (`storefront.home`, `storefront.shop.index`, `storefront.category.index`, `storefront.flash-deals`, `storefront.blog.index`, `storefront.cart.index`, `storefront.wishlist.index`, `storefront.compare.index`, …). Never pass raw input to `route()`.
- **FormRequests:** `StoreMenuItemRequest` / `UpdateMenuItemRequest` — `type` in `MenuItem::TYPES`; `value` validated per type (route in whitelist; category `exists:categories,id`; url `string` + URL/relative sanitize; widget in `MenuItem::WIDGETS`); `target` in `_self,_blank`; `visibility` in `all,guest,auth`; `parent_id` must belong to the same `menu_id` (and not create a cycle).
- **XSS:** labels rendered with `{{ }}` (auto-escaped). Custom URLs sanitized (strip `javascript:` etc.).
- **Auth/permission:** admin routes under existing `auth` middleware + the module's permission gate (mirror other `ecommerce.*` screens).

---

## 3b. Storefront implementation (per-surface, detailed)

This is the consumption side — how the resolved menus replace the hardcoded markup in each of the 4 partials (navigation, footer, mobile-menu, mobile-bottom-nav) **without changing the theme's CSS/JS hooks** (class names must stay identical so existing styles and `cart.js` count-updates keep working).

### View-composer injection
In `EcommerceServiceProvider::registerStorefrontViewComposers()`, after the existing `menuCategories`/`footerCategories` blocks, add (guarded with `!$view->offsetExists()`):
```php
$customer = auth('customer')->user();
$menu = app(MenuService::class);
$view->with('mainMenu',   $menu->getTree('header_main',   $customer));
$view->with('footerMenu', $menu->getTree('footer',        $customer));
$view->with('mobileMenu', $menu->getTree('mobile_drawer', $customer));
$view->with('bottomNav',  $menu->getTree('mobile_bottom', $customer));
```
`getTree()` is **visibility-resolved against the current customer** (so `guest`/`auth` items are filtered server-side) and cached per `location + auth-state`.

### The render partial — `storefront.partials.menu-render`
Recursive, but **style-aware** so it emits the exact Zenis class names per surface. Signature:
```blade
@include('ecommerce::storefront.partials.menu-render', ['items' => $tree, 'style' => 'main'])
```
Per node, branch on `type`:
- `heading` → label (used as footer column `<h3>` / group title); render `children` as the list.
- `widget` → `@include('…menu-widget', ['widget' => $node['value'], 'style' => $style])`.
- `categories_dropdown` → render the live "Browse Categories" block from `$menuCategories` (honours `settings.limit`).
- else (`route`/`category`/`url`) → `<a href="{{ $node['url'] }}" target="{{ $node['target'] }}" class="{{ active($node) }} {{ $node['css_class'] }}">{{ $node['label'] }}</a>`, recursing `children` into the surface's dropdown wrapper.

**Active state:** a tiny helper compares the node to the current request — for `route` items `request()->routeIs($value.'*')` (heuristic, e.g. `storefront.shop.*`), otherwise exact-URL match. Mirrors the current `active` classes.

### The widget sub-partial — `storefront.partials.menu-widget`
Maps a widget key → the **existing markup** (so nothing visually changes and counts keep live-updating):
| widget | renders |
|---|---|
| `cart` | offcanvas trigger `<a data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight">` + `.cart-count` badge |
| `wishlist` | link to `storefront.wishlist.index` + `.wishlist-count` |
| `compare` | link to `storefront.compare.index` + `.compare-count` |
| `account` | `@auth('customer')` → user dropdown block / `@else` → login link (the current navigation markup) |
| `search` | mobile: search-modal trigger; desktop: search field/icon |

Icons resolve from the item's `icon` (FontAwesome) or the theme's SVG asset for that widget.

### Per-surface refactor (each keeps a hardcoded fallback)
Pattern in every partial — **each checks its own injected variable** (`$mainMenu` in navigation, `$footerMenu` in footer, `$mobileMenu` in mobile-menu, `$bottomNav` in mobile-bottom-nav):
```blade
@if(!empty($mainMenu))            {{-- use the surface's own variable --}}
    {{-- render from menu builder --}}
@else
    {{-- existing hardcoded markup (unchanged) --}}
@endif
```

1. **Desktop main menu** (`navigation.blade.php`)
   - `categories_dropdown` node → existing `.menu_category_area` "Browse Categories" mega-dropdown (live categories).
   - Text items → `<ul class="menu_item">` `<li><a class="active?">`; children → `<ul class="menu_cat_droapdown">` dropdown (existing classes).
   - Widget items → `<ul class="menu_icon">` with wishlist/compare/cart/account `<li>` (via menu-widget). The `account` widget preserves the full auth dropdown / guest login.

2. **Footer** (`footer.blade.php`)
   - Iterate top-level `heading` items → one `<div class="footer_link"><h3>{label}</h3><ul>…children…</ul></div>` column each.
   - Logo / social / contact / payment-icons blocks stay as-is (not menu-driven — they're settings-driven).
   - "Category" column = a `categories_dropdown`/`category` group (live, `limit=5`).

3. **Mobile drawer** (`mobile-menu.blade.php`)
   - "Menu" tab consumes `mobile_drawer` (renders `.main_mobile_menu` with `.inner_menu` for children).
   - "Categories" tab stays live (`$menuCategories`).
   - Header icons (wishlist/cart) via menu-widget.

4. **Mobile bottom nav** (`mobile-bottom-nav.blade.php`) — the fixed bottom app-bar (ref. screenshot: Home · Categories · Search · Cart · Profile, active item shown in a highlighted orange circle)
   - Consume `mobile_bottom`: each item → `.bp-mbn-item` with **`.bp-mbn-icon` (icon) + `.bp-mbn-label` (text)** — every bottom-nav item therefore **requires an `icon`** (FontAwesome), enforced in the builder for this location.
   - Active state preserved: current item gets the existing `active` class (the orange-circle highlight) via `request()->routeIs()`.
   - `search` widget → `#bpSearchModal` trigger button; `cart` widget → icon + `.cart-count`/`.cart-badge`; `account` widget → `@auth('customer')` profile / `@else` login (the current Profile item).
   - Recommended cap: ~5 items (theme is a 5-slot bar); builder shows a soft warning beyond that.

### Guarantees / non-regressions
- Class names (`menu_item`, `menu_icon`, `menu_cat_droapdown`, `footer_link`, `main_mobile_menu`, `bp-mbn-item`, `cart-count`, `wishlist-count`, `compare-count`) are **unchanged** → existing CSS + `cart.js` keep working.
- Empty/missing menu → hardcoded fallback → identical to today.
- Counts remain session-driven and updated by `cart.js` (we only relocate the markup, not the behavior).

### Storefront QA checklist
- Each location renders from DB after seeding; clearing a location falls back cleanly.
- Every resolved link returns 200 (route whitelist + category slugs valid).
- `guest`/`auth` visibility correct when logged out vs. logged in as a customer.
- Dropdowns (desktop categories + submenus), footer columns, mobile tabs, and bottom-nav all render with correct nesting.
- Cart/wishlist/compare counts still live-update after add/remove.

---

## 4. Phasing
1. **Foundation** — migration, models, `MenuService`, seeder, view-composer wiring, recursive render partial, refactor **desktop main menu** (with fallback). *Visible win, low risk.*
2. **Remaining surfaces** — footer (heading-columns), mobile drawer, bottom nav, widget rendering.
3. **Admin builder** — full item CRUD (add / **edit** / **delete-with-subtree** / **clear-all** / **reset-to-default**), nested drag-reorder, widgets, visibility, toggle, sidebar link.
4. **Polish** — caching, admin dark-mode overrides, QA (every link resolves, counts live, guest/auth visibility, delete cascades leave no orphans).

---

## 5. Files expected to be touched/created (reference)
**New:**
- `Modules/Ecommerce/database/migrations/*_create_menus_table.php`, `*_create_menu_items_table.php`
- `Modules/Ecommerce/app/Models/Menu.php`, `MenuItem.php`
- `Modules/Ecommerce/app/Services/MenuService.php`
- `Modules/Ecommerce/app/Http/Controllers/MenuController.php`
- `Modules/Ecommerce/app/Http/Requests/StoreMenuItemRequest.php`, `UpdateMenuItemRequest.php`
- `Modules/Ecommerce/database/seeders/MenuSeeder.php`
- `Modules/Ecommerce/resources/views/menus/index.blade.php`, `menus/builder.blade.php`
- `Modules/Ecommerce/resources/views/storefront/partials/menu-render.blade.php`
- `Modules/Ecommerce/resources/views/storefront/partials/menu-widget.blade.php`
- `public/vendor/sortablejs/Sortable.min.js` (+ nested usage)

**Modified:**
- `Modules/Ecommerce/routes/web.php` (admin routes)
- `Modules/Ecommerce/app/Providers/EcommerceServiceProvider.php` (view composer)
- `Modules/Core/resources/views/partials/sidebar.blade.php` (Online group link)
- `partials/navigation.blade.php`, `footer.blade.php`, `mobile-menu.blade.php`, `mobile-bottom-nav.blade.php`

---

## 6. Open follow-ups (decide at build time)
- Whether a CMS/landing "page" item type is needed (depends on LandingPage/PageBuilder availability).
- Footer "Category" column: keep auto (live categories) vs fully curated.
- Per-role/permission gating of the Menus admin screen (reuse existing permission middleware if applicable).
