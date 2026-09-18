# Design: FAQs CRUD + Custom Pages (CMS)

**Date:** 2026-06-30
**Module:** Ecommerce
**Status:** Approved — ready for implementation plan

## Goal

Two admin-managed content features surfaced on the storefront:

1. **FAQs** — a flat, ordered list of question/answer pairs managed in the admin
   panel and shown as an accordion on a storefront FAQ page.
2. **Custom Pages** — admin-authored pages (Privacy Policy, Terms, About, Refund
   Policy, etc.) with a rich text body, served on clean `/{slug}` URLs.

Both are linkable into the storefront via the existing Menu Builder.

## Decisions (locked)

| Decision | Choice |
|---|---|
| FAQ organization | Flat ordered list (no categories) |
| Custom page URL scheme | Clean `/{slug}` (catch-all, last route) |
| Footer/nav linking | Via existing Menu Builder |
| Editor scope | Pages = rich text (TinyMCE); FAQ answers = plain text |
| Rich-text sanitization | Render-time `strip_tags()` tag-whitelist (existing blog convention — no new package) |

## Existing patterns reused

- **Admin CRUD:** controllers under the `ecommerce.` route prefix + Form Request
  validation + blade views in `Modules/Ecommerce/resources/views/`, mirroring the
  blog-post and campaign CRUD.
- **Rich text editor:** `public/js/bp-richtext.js` auto-initializes TinyMCE on any
  `<textarea class="bp-richtext">`. Loaded lazily; URL from `window.BP_TINYMCE_SRC`
  in the Core master layout. No per-page wiring needed.
- **Rich content rendering:** blog renders with
  `{!! strip_tags($content, '<p><br><strong><em><ul><ol><li><h2>...<div>') !!}`.
  Pages follow the same whitelist approach.
- **Drag-to-reorder:** SortableJS (vendored) + a `reorder` POST endpoint writing a
  `position` column, as already done for combos and products.
- **Storefront SEO:** `Seo::make()` builder (`BuildsSeo` trait) for title/description/
  canonical/breadcrumbs.
- **Menu Builder:** `MenuItem::TYPES` = `route, category, url, heading,
  categories_dropdown, widget`. Route links use a whitelist in
  `HasMenuRoutes::menuRoutes()` (route name → friendly label). `resolveUrl()` maps
  type+value → href.

---

## Feature 1 — FAQs

### Database
New migration `faqs` table:

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| question | string | required |
| answer | text | required, plain text |
| position | unsignedInteger, default 0 | drag-to-reorder order |
| is_active | boolean, default true | |
| timestamps | | |

Index on `position`. No soft deletes (low-stakes content).

### Model — `Modules\Ecommerce\Models\Faq`
- `$fillable = ['question', 'answer', 'position', 'is_active']`
- Cast `is_active` => boolean, `position` => integer
- Scope `active()` → `where('is_active', true)`
- Scope `ordered()` → `orderBy('position')->orderBy('id')`

### Admin (routes under `ecommerce.` prefix, `auth` middleware)
- `GET  /ecommerce/faqs` → `faqs.index` — list with status toggle, edit/delete,
  drag-to-reorder.
- `GET  /ecommerce/faqs/create` → `faqs.create`
- `POST /ecommerce/faqs` → `faqs.store`
- `GET  /ecommerce/faqs/{faq}/edit` → `faqs.edit`
- `PUT  /ecommerce/faqs/{faq}` → `faqs.update`
- `DELETE /ecommerce/faqs/{faq}` → `faqs.destroy`
- `PATCH /ecommerce/faqs/{faq}/toggle-status` → `faqs.toggle-status`
- `POST /ecommerce/faqs/reorder` → `faqs.reorder` (id-ordered array → position)

`FaqController` keeps the light CRUD inline (matching the blog/campaign controllers
in this module — no separate service for trivial create/update/delete). Dedicated
create/edit pages (consistent with blog). Answer is a plain
`<textarea class="bp-form-control">` (NOT `bp-richtext`).

Validation — `StoreFaqRequest` / `UpdateFaqRequest`:
- `question` => `required|string|max:500`
- `answer` => `required|string`
- `is_active` => `boolean`
- `position` => `nullable|integer|min:0`

### Storefront
- `GET /faq` → `storefront.faq.index` (registered before the page catch-all).
- `FaqController@index` (storefront namespace) → `Faq::active()->ordered()->get()`,
  rendered as a Bootstrap accordion.
- View: `storefront/pages/faq/index.blade.php`.
- SEO via `staticPageSeo('faq', [...])` (add an entry / fallback).
- Footer: replace the dead `href="#"` FAQ link with `route('storefront.faq.index')`.
- Add `'storefront.faq.index' => 'FAQ'` to `HasMenuRoutes::menuRoutes()`.

---

## Feature 2 — Custom Pages

### Database
New migration `pages` table:

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| title | string | required |
| slug | string, unique | auto from title if blank |
| content | longText, nullable | rich HTML (TinyMCE) |
| is_published | boolean, default true | |
| seo_title | string, nullable | |
| seo_description | string, nullable | |
| seo_image | string, nullable | image path |
| timestamps | | |
| softDeletes | | safer for referenced content |

Index on `slug`, `is_published`.

### Model — `Modules\Ecommerce\Models\Page`
- `$fillable = ['title','slug','content','is_published','seo_title','seo_description','seo_image']`
- `SoftDeletes`
- Cast `is_published` => boolean
- Auto-slug on save when slug empty (`Str::slug(title)`, ensure uniqueness)
- Scope `published()` → `where('is_published', true)`
- `RESERVED_SLUGS` constant (shared with validation) — see below.

### Admin (routes under `ecommerce.` prefix, `auth` middleware)
- `GET  /ecommerce/pages` → `pages.index`
- `GET  /ecommerce/pages/create` → `pages.create`
- `POST /ecommerce/pages` → `pages.store`
- `GET  /ecommerce/pages/{page}/edit` → `pages.edit`
- `PUT  /ecommerce/pages/{page}` → `pages.update`
- `DELETE /ecommerce/pages/{page}` → `pages.destroy`
- `PATCH /ecommerce/pages/{page}/toggle-status` → `pages.toggle-status`

`PageController` modeled on the blog-post CRUD. Create/edit views reuse the blog-post
layout: title, slug, `<textarea class="bp-richtext">` body, published toggle, SEO
title/description/image. `seo_image` upload handled like blog `featured_image`
(`Storage::putFile`, randomized name).

Validation — `StorePageRequest` / `UpdatePageRequest`:
- `title` => `required|string|max:255`
- `slug` => `nullable|string|max:255|unique:pages,slug[,{id}]|not_in:<reserved>`
- `content` => `nullable|string`
- `is_published` => `boolean`
- `seo_title` => `nullable|string|max:255`
- `seo_description` => `nullable|string|max:300`
- `seo_image` => `nullable|image|mimes:jpg,jpeg,png,webp|max:2048`

**Reserved slugs** (constant on `Page`): `shop, cart, checkout, blog, category,
categories, flash-deals, combos, wishlist, compare, contact, customer, search, faq,
auth, page, pages, admin, ecommerce, api, login, register`.

### Storefront
- `GET /{slug}` → `storefront.page.show`, **registered last** in `storefront.php`,
  after every other storefront route. Defense in depth (all three apply):
  1. Route order — real routes are declared first, so they always win.
  2. A `->where('slug', '[A-Za-z0-9\-]+$')` constraint (single path segment,
     alphanumeric + hyphen only) so it never captures multi-segment paths.
  3. Reserved-slug validation blocks ever creating a page whose slug is a reserved
     word, so the catch-all can only resolve to a legitimately created page.
- `PageController@show`: `Page::published()->where('slug',$slug)->firstOrFail()`.
  Render body with the blog `strip_tags()` whitelist. SEO from page's seo_* fields
  (fallback to title + generated description), canonical = the page URL, breadcrumbs
  Home → {title}.
- View: `storefront/pages/page/show.blade.php`.

### Menu Builder integration — new `page` item type
- Add `'page'` to `MenuItem::TYPES`.
- `resolveUrl()`: `'page' => ($slug = pageSlug((int)$value)) ? route('storefront.page.show', $slug) : null`
  (with an in-request id→slug cache mirroring the existing category cache).
- `StoreMenuItemRequest`: allow `type=page`; when `page`, `value` must
  `exists:pages,id`.
- Menu-builder edit UI: when type = "Page", show a select of published pages
  (id → title). Mirrors the existing category picker.

---

## Cross-cutting

- **Admin sidebar / Ecommerce CMS nav:** add **FAQs** and **Pages** entries in the
  content group (near Blog / Banners). Use `request()->routeIs('ecommerce.faqs.*')`
  / `ecommerce.pages.*` for active state.
- **Dark mode + responsive:** every new admin and storefront view gets
  `[data-theme="dark"]` overrides in `style.css` and works at 768/992/1200px.
- **Icons:** FAQs → `fa-circle-question`; Pages → `fa-file-lines` (FontAwesome solid).
- **No hardcoded routes / no inline CSS / `'use strict';`** in any JS block, per
  CLAUDE.md.

## Security

- Reserved-slug validation + last-position catch-all registration prevent a custom
  page from shadowing a real route.
- Page body is structured TinyMCE HTML; rendered through a `strip_tags()` tag
  whitelist (same as blog) so script/iframe/event-handler markup is stripped at
  output. Stored content is treated as untrusted.
- `seo_image` upload validated (`image|mimes|max`) and stored with a randomized name
  via `Storage::putFile` — never a user-supplied filename.
- All admin routes behind `auth`; CSRF via `@csrf` on every form.

## Testing (Feature/Unit)

1. **FAQ CRUD:** create (302 + persisted), update, delete; validation rejects empty
   question/answer (302 back).
2. **FAQ reorder:** posting an id order updates `position`; storefront accordion
   renders active FAQs in `position` order; inactive FAQs hidden.
3. **Page CRUD:** create with auto-slug, update, soft delete; SEO fields persist.
4. **Reserved slug:** `slug=shop` (and other reserved) rejected by validation.
5. **Storefront page show:** published → 200 with body; unpublished → 404; unknown
   slug → 404.
6. **Catch-all ordering:** a real route (e.g. `/shop`) still resolves to its own
   controller, not `PageController`.
7. **Menu `page` type:** a menu item of type `page` resolves to the correct
   `/{slug}` URL; deleting the page degrades gracefully (null/`#`).

## Out of scope (YAGNI)

- FAQ categories/grouping.
- Page slug-change history / 301 redirects (slug is editable but no redirect map).
- Page templates / layouts beyond a single content body.
- WYSIWYG for FAQ answers.
