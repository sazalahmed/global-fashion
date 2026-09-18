# Branded Invoice & Letterhead System — Design Spec

**Date:** 2026-06-18
**Branch:** feat/product-variant-management (or a new feature branch)
**Status:** Approved for planning
**Hosting target:** Shared cPanel (no root, no Node/Chrome, `exec`/`proc_open` likely disabled,
GD available, imagick NOT assumed). All choices below are constrained by this.

## 1. Summary

Implement the "Global" branded invoice design (per the provided reference image) as the
real invoice template for sales, backed by a **dedicated admin page** where the admin can
manage every visual/branding element (logo, seal, colors, footer contacts, social links,
labels, toggles). Provide:

- A reusable **letterhead "pad"** (logo, angled red side-tabs, footer contact bar, seal)
  that wraps invoice content.
- **Blank-pad printing** so the admin can print letterhead stationery with no invoice body.
- **Two preview pages** (blank pad, sample invoice) opened in a new browser tab.
- All CSS extracted to a standalone **`public/css/invoice.css`** file.
- A **single DomPDF-safe template** used for screen, browser-print, AND the downloadable/emailed
  PDF — so all three render identically and there is one source of truth.

The reference mockups at the repo root (`invoice.html`, `pad.html`, `final-invoice.html`)
are the visual source of truth and are NOT shipped.

## 2. Goals / Non-Goals

### Goals
- Faithful reproduction of the reference design for real sale invoices, within DomPDF's
  rendering capabilities.
- One dedicated admin page to configure all letterhead/invoice branding.
- Blank-pad print + two previews.
- Single shared, shared-hosting-safe rendering path for screen, print, and PDF.

### Non-Goals
- No headless-Chrome / Browsershot / Node dependency (incompatible with shared cPanel).
- No per-customer/per-branch invoice theming (single global template).
- No drag-and-drop layout editor — fields are form inputs, layout is fixed.
- No changes to sale data, totals, or business logic.
- Quotation/Purchase/Return print templates are out of scope (future reuse possible).

## 3. Rendering strategy (DomPDF-safe, shared-hosting-safe)

DomPDF cannot render `flexbox`, CSS `clip-path`, or `position` the way modern browsers do. The
design is therefore built with techniques that render identically in **both** a browser and
DomPDF:

| Design element | Mockup technique (NOT used) | Shipped technique (DomPDF-safe) |
|---|---|---|
| Page layout | flexbox | `<table>`-based layout + DomPDF-supported absolute positioning |
| Angled red side-tabs | `clip-path` | **Inline `<svg>` polygon**, `fill` templated from `brand_color`, absolutely positioned |
| Angled black/red base bar | `clip-path` | **Inline `<svg>`** with two polygons, `fill` from `brand_color`/`dark_color` |
| Footer contact bar | flexbox | `<table>` with one cell per contact item |
| Footer icons | Font Awesome webfont | **Inline `<svg>` icons** (no font dependency, render in DomPDF) |
| Logo / seal | `<img>` (URL) | `<img>` — URL for browser, **base64 data-URI** for PDF |
| QR code | n/a | **PNG via GD** (endroid/qr-code), embedded as base64 data-URI |

**Why inline SVG for shapes:** the brand color is admin-configurable. A pre-rendered PNG would
bake the color in. SVG is templated text, so the configured hex is injected into the `fill`
attribute at render time — staying dynamic while remaining DomPDF-renderable (simple polygons).
**Fallback:** if a given DomPDF version mis-renders an SVG polygon, the shape degrades to a
solid-color rectangle (`<div>` with `background-color`) — verified during implementation.

### Single template, two asset strategies
A boolean `$forPdf` flag controls only *how assets are referenced*, not the markup:
- **Browser/screen/print** (`$forPdf = false`): CSS linked via `{{ asset('css/invoice.css') }}`,
  images via `upload_url()`.
- **PDF** (`$forPdf = true`): CSS inlined via `<style>{!! file_get_contents(public_path('css/invoice.css')) !!}</style>`,
  images as base64 data-URIs (so DomPDF needs no network / `isRemoteEnabled`).

The body markup, letterhead frame, and inline-SVG shapes are identical in both paths.

## 4. Prerequisites (new dependencies)

| Dependency | Purpose | Shared-hosting notes |
|---|---|---|
| `endroid/qr-code` (composer) | Generate QR as **PNG via GD** | Pure PHP + ext-gd (standard on cPanel). No imagick, no Node. |

- **DomPDF** (`barryvdh/laravel-dompdf`) is **already installed** and is the PDF engine — no new
  PDF dependency, no system packages.
- No Node, no Puppeteer, no Chrome, no `exec`/`proc_open` usage.

## 5. Settings model

All editable fields live in the `settings` table under a new group **`letterhead`**, read/written
with the existing `Setting::get/set/getGroup` API.

| Area | Key | Type | Default behavior |
|---|---|---|---|
| Header | `logo` | image path | Falls back to `business.logo` if empty |
| Header | `show_qr` | boolean | true |
| Header | `title_text` | string | "INVOICE" |
| QR | `qr_fields` | json (array of field keys) | `["invoice_number","date","customer_name","customer_phone","total","due"]` — which data fields the QR encodes (admin toggles each) |
| Company block | `company_name` | string | Falls back to `business.company_name` |
| Company block | `address_line1` | string | Falls back to `business.address` |
| Company block | `address_line2` | string | "" |
| Company block | `phone` | string | Falls back to `business.company_phone` |
| Company block | `email` | string | Falls back to `business.email` |
| Company block | `website` | string | "" |
| Seal | `seal_image` | image path | none |
| Seal | `show_seal` | boolean | true |
| Colors | `brand_color` | string (hex) | `#ED1C24` — side tabs, base-bar red, footer icon squares, seal, accents |
| Colors | `dark_color` | string (hex) | `#111111` — base-bar dark wedge, footer text |
| Colors | `table_header_bg` | string (hex) | `#4A4A4A` — items table header background |
| Colors | `table_header_text` | string (hex) | `#FFFFFF` — items table header text |
| Colors | `text_color` | string (hex) | `#000000` — invoice body text |
| Colors | `title_color` | string (hex) | `#000000` — "INVOICE" title + section heads |
| Footer | `show_footer` | boolean | true |
| Footer | `footer_phone` | string | Falls back to `phone` |
| Footer | `footer_email` | string | Falls back to `email` |
| Footer | `footer_facebook` | string | "" |
| Footer | `footer_website` | string | Falls back to `website` |
| Footer | `footer_address` | string | Falls back to `address_line1` |
| Side tabs | `show_side_tabs` | boolean | true |
| Signatures | `left_sign_label` | string | "Received by" |
| Signatures | `right_sign_label` | string | "Authorized by" |

**Defaults resolution:** `Modules\Setting\Services\LetterheadService::config(bool $forPdf): array`
returns a normalized `$pad` array — every key present, fallback chain resolved to concrete
values, colors validated to hex, and image fields turned into either `upload_url()` strings
(`$forPdf=false`) or base64 data-URIs (`$forPdf=true`). Views never branch on empty settings.

**Image handling:** uploads via `Upload::store($file, 'letterhead')`; replace via
`Upload::replace()`. A new `Upload::dataUri(?string $path): ?string` helper reads a stored file
and returns `data:<mime>;base64,...` for PDF embedding.

**Validation — `UpdateInvoiceTemplateRequest`:**
- `logo`, `seal_image`: `nullable|image|mimes:jpg,jpeg,png,webp|max:2048`
- all six color fields (`brand_color`, `dark_color`, `table_header_bg`, `table_header_text`,
  `text_color`, `title_color`): `nullable|regex:/^#[0-9A-Fa-f]{6}$/` (invalid/empty falls back to the
  documented default in `LetterheadService::config()`)
- text fields: `nullable|string|max:255` (address lines `max:500`)
- booleans: `nullable` (presence = true)

## 6. Admin page (dedicated)

### Routes (in `Modules/Setting/routes/web.php`, inside the `settings.` group)
```php
Route::get('/invoice-template', [InvoiceTemplateController::class, 'edit'])->name('invoice-template.edit');
Route::put('/invoice-template', [InvoiceTemplateController::class, 'update'])->name('invoice-template.update');
Route::get('/invoice-template/preview/pad', [InvoiceTemplateController::class, 'previewPad'])->name('invoice-template.preview.pad');
Route::get('/invoice-template/preview/invoice', [InvoiceTemplateController::class, 'previewInvoice'])->name('invoice-template.preview.invoice');
```

### Controller — `InvoiceTemplateController` (thin; delegates to `LetterheadService`)
- `edit()` → `view('setting::invoice-template', ['pad' => $service->config(false), 'raw' => Setting::getGroup('letterhead')])`
- `update(UpdateInvoiceTemplateRequest $r)` → `$service->update(...)` → back with success.
- `previewPad()` → renders blank letterhead (print-optimized standalone view).
- `previewInvoice()` → builds a **sample (non-persisted) Sale-like object** with dummy items/totals
  and renders the full invoice.

### View — `setting::invoice-template`
Full BizPOS-styled page (master layout, `bp-` classes, `[data-theme="dark"]` overrides in
`style.css`). Sections as `bp-card`s:
1. Header & Logo (logo upload + current preview, title_text) + **QR card**: `show_qr` toggle and a
   checkbox group of the 12 catalog fields (grouped Customer / Financial / Meta / Business) writing
   to `qr_fields`, with a small "this is what the QR will contain" hint.
2. Company Details (name, address lines, phone, email, website)
3. Seal / Stamp (seal upload + preview, show_seal)
4. Colors — six color-picker inputs (brand, dark, table header bg, table header text, body text,
   title), each showing a live swatch + a "Reset to default" affordance. These drive the whole
   invoice palette.
5. Footer Contact Bar (show_footer + the 5 footer fields)
6. Side Tabs & Signatures (show_side_tabs, left/right labels)

Page actions: **Save**, **Preview Letterhead** (`preview.pad`, `target="_blank"`),
**Preview Sample Invoice** (`preview.invoice`, `target="_blank"`).
Form: `enctype="multipart/form-data"`, `@csrf`, `@method('PUT')`, posts to
`invoice-template.update`. Named routes only; no inline CSS; `'use strict';` in any script block.

### Sidebar
Add an "Invoice Template" item (icon `fa-file-invoice`) in the System/Settings area of the
sidebar partial, active via `request()->routeIs('settings.invoice-template.*')`.

## 7. Rendering architecture (shared / DRY)

### `public/css/invoice.css`
All styles from the mockups, rewritten DomPDF-safe:
- A small per-render inline `:root{ ... }` block carries ALL six dynamic colors as CSS variables
  (`--gf-red`, `--gf-dark`, `--gf-th-bg`, `--gf-th-text`, `--gf-text`, `--gf-title`) — the ONLY
  inline style, justified as runtime-dynamic server values. DomPDF has limited CSS-variable
  support, so each color is ALSO emitted where DomPDF needs a literal: the **inline-SVG `fill`
  attributes** (tabs, base bar, footer icons, seal — authoritative for shapes) and, for the items
  table header / body text / title, concrete values are injected via small per-element inline
  styles or a generated `<style>` block keyed off the resolved `$pad` colors. Net effect: every
  invoice color is admin-controlled and renders correctly in both the browser and DomPDF.
- Table-based layout rules, A4 `.page`, header/QR, title, shipping box, items table, financial
  summary, signatures, seal, side-tab/base-bar SVG positioning, footer table, `@media print`.

### Blade structure (in `Modules/Sale/resources/views/`)
- `partials/letterhead.blade.php` — frame ONLY: logo, QR slot, **inline-SVG** side tabs, seal,
  footer contact `<table>`, **inline-SVG** base bar. Driven entirely by `$pad`. No invoice data.
- `partials/invoice-body.blade.php` — invoice content ONLY (title, vendor/meta, bill/ship to,
  items `<table>`, due/summary, signatures). Driven by `$sale`. Exists once; reused everywhere.
- `invoice-layout.blade.php` — base doc; takes `$forPdf`. Head links OR inlines `invoice.css` and
  the dynamic `:root` block; includes the letterhead frame; `@yield('invoice-body')`.
- `invoice-print.blade.php` — `@extends('invoice-layout')` with `$forPdf=false`, body =
  `@include('sale::partials.invoice-body')`. Used by `sales.print` (browser).
- `invoice-pdf.blade.php` — same, `$forPdf=true`. Rendered by DomPDF.
- `pad-blank.blade.php` — layout with an EMPTY body (letterhead only). Used by `previewPad` and
  the pad-print button.

**DRY rule:** invoice body markup and the letterhead frame each exist exactly once.

### QR generation — admin-selected data fields
The QR encodes **chosen data fields as readable text lines** (not a URL). The admin toggles which
fields are included via the `qr_fields` setting (array of field keys).

**Field catalog** (key → label → value source). A `QrContentBuilder` resolves these from the
source document (a `Sale` now; the same map is reused when Quotation adopts the template):

| Group | Key | Label | Value |
|---|---|---|---|
| Customer | `customer_name` | Name | `$sale->customer_display_name` |
| Customer | `customer_phone` | Phone | `$sale->customer?->phone` |
| Customer | `customer_address` | Address | `$sale->customer?->address` |
| Financial | `subtotal` | Subtotal | money(`$sale->subtotal`) |
| Financial | `vat` | VAT | money(`$sale->tax_amount`) |
| Financial | `total` | Total | money(`$sale->grand_total`) |
| Financial | `paid` | Paid | money(`$sale->paid_amount`) |
| Financial | `due` | Due | money(`$sale->due_amount`) |
| Meta | `invoice_number` | Invoice No | `$sale->invoice_number` |
| Meta | `date` | Date | `$sale->sale_date` (DD MMM YYYY) |
| Business | `business_name` | Business | `Setting business.company_name` |
| Business | `business_phone` | Business Phone | `Setting business.company_phone` |

- The builder iterates the catalog **in this fixed display order**, includes only keys present in
  `qr_fields` (and whose value is non-empty), and outputs one `Label: value` line per field. Money
  uses `currency_symbol()` + BD `number_format`.
- The resulting multi-line string is rendered to a **PNG via `endroid/qr-code`** (GD) and embedded
  as a base64 data-URI. QR error-correction/size scales with content length (longer text → denser
  QR); the builder caps included fields gracefully.
- `show_qr=false` → no QR rendered. Empty `qr_fields` → no QR (nothing to encode).
- **Sample/blank previews:** the sample invoice uses dummy values through the same builder so the
  admin sees exactly what real invoices encode; the blank pad shows no QR.
- Passed into the layout as `$qrDataUri`; the letterhead partial renders the slot (or nothing).

`QrContentBuilder` lives in the Sale module (`Modules\Sale\Support\QrContentBuilder`) and takes the
source model + the `qr_fields` array, returning the text payload. Keeping it separate from the QR
image generation makes it trivially reusable for Quotation.

## 8. PDF engine (DomPDF — already installed)

- `SaleController::pdf()` and `email()` keep `\Barryvdh\DomPDF\Facade\Pdf::loadView('sale::invoice-pdf', ...)`
  but now render the new shared template with `$forPdf=true` (inlined CSS + data-URI images +
  PNG QR). `setPaper('a4','portrait')`, margins handled in CSS.
- No `isRemoteEnabled` needed because every asset is embedded.
- `print()` (on-screen) renders `invoice-pdf`'s sibling `invoice-print` with `$forPdf=false`.

## 9. Preview & blank-pad behavior

- **Preview Letterhead (blank pad):** renders `pad-blank` with live `letterhead` settings.
  Standalone HTML, print-optimized, with a non-printing toolbar holding a "Print" button
  (hidden via `@media print`).
- **Preview Sample Invoice:** builds a dummy in-memory sale (Water Purifier sample matching the
  mockup) and renders the full invoice. Same non-printing toolbar.
- Both open via `target="_blank"` from the admin page.
- The real sale "Print" action (`sales.print`) URL is unchanged; it now renders the new design.

## 10. Files

### New
- `Modules/Setting/app/Http/Controllers/InvoiceTemplateController.php`
- `Modules/Setting/app/Http/Requests/UpdateInvoiceTemplateRequest.php`
- `Modules/Setting/app/Services/LetterheadService.php`
- `Modules/Setting/resources/views/invoice-template.blade.php`
- `Modules/Sale/resources/views/invoice-layout.blade.php`
- `Modules/Sale/resources/views/partials/letterhead.blade.php`
- `Modules/Sale/resources/views/partials/invoice-body.blade.php`
- `Modules/Sale/resources/views/pad-blank.blade.php`
- `Modules/Sale/app/Support/QrContentBuilder.php`
- `public/css/invoice.css`

### Modified
- `Modules/Setting/routes/web.php` (4 routes)
- sidebar partial — nav item
- `Modules/Sale/app/Http/Controllers/SaleController.php` — `print()`, `pdf()`, `email()`
- `Modules/Sale/resources/views/invoice-print.blade.php` — rebuilt on shared layout
- `Modules/Sale/resources/views/invoice-pdf.blade.php` — rebuilt on shared layout
- `app/Helpers/Upload.php` — add `dataUri()` helper
- `composer.json` — add `endroid/qr-code`

### Removed (after the Blade version is verified)
- `invoice.html`, `pad.html`, `final-invoice.html` (root mockups)

## 11. Security & conventions

- All output escaped with `{{ }}`; inline SVG shapes/icons and the QR `<img>` are generated
  server-side from trusted/validated values.
- File uploads validated (image mimes, 2MB) via Form Request.
- No hardcoded routes — named routes everywhere (Blade, JS, redirects).
- No inline CSS except the single dynamic `:root` color block.
- `'use strict';` atop any `<script>` block on the admin page.
- Currency via existing `currency_symbol()` + `number_format` (BD lakh format preserved).
- Dark-mode overrides for the new admin page added to `style.css`.
- Font Awesome on the admin page uses the local `vendor/fontawesome` (no CDN); the invoice
  footer uses inline SVG, so the shipped invoice has no FA/CDN dependency at all.

## 12. Testing / acceptance

1. Admin page loads at `settings/invoice-template`; fields populate from settings (or business
   defaults). Save persists; reload shows saved values; images upload & preview.
2. **Preview Letterhead** shows a blank pad reflecting saved logo/colors/footer; prints cleanly to A4.
3. **Preview Sample Invoice** shows the full design with dummy data matching the mockup.
4. Real sale `sales.print` renders the new design with live data + per-invoice QR; browser print → A4
   matches the mockup.
5. `sales.pdf` downloads a **DomPDF** PDF that visually matches the print view: red side-tabs
   (inline SVG), footer bar + base bar, seal, and QR all present. `file` reports a PDF.
6. `email` attaches the same PDF.
7. Toggles work: hiding side tabs / footer / seal / QR removes them from all outputs.
7b. QR content: enabling/disabling fields in `qr_fields` changes the encoded text — scanning the
    QR on a real invoice returns exactly the selected `Label: value` lines (verify with a scanner
    or decoder), in the fixed catalog order, money formatted with `BDT`. Empty selection → no QR.
8. Changing ANY of the six colors propagates to the matching elements in BOTH browser and PDF:
   `brand_color` → side tabs / base-bar red / footer icons / seal; `dark_color` → base-bar wedge /
   footer text; `table_header_bg` + `table_header_text` → items table header; `text_color` → body;
   `title_color` → title + section heads. Confirms inline-SVG fill templating + injected literals
   work in DomPDF.
9. No regressions on `sales.index`/`show`; route sweep returns 200.
10. CLAUDE.md checks: no CDN in shipped invoice Blade, no hardcoded routes, no stray inline CSS
    beyond the dynamic color block. GD-only (no imagick) confirmed for QR.

## 13. Open considerations (decided defaults)

- **DomPDF SVG fidelity:** simple polygons (tabs/base bar) and small icon paths render in DomPDF
  ≥1.x, but exact output is verified early in implementation against a real DomPDF render. If any
  shape fails, fall back to a solid-color `<div>` rectangle for that element (color still dynamic).
- **Currency display:** the mockup shows bare `50715.00`; production shows the `BDT` prefix +
  BD-formatted numbers, consistent with the existing invoice templates (default = show `BDT`).
- **WebP uploads:** `Upload::store()` converts images to WebP. DomPDF does not support WebP. The
  `dataUri()` helper (and/or upload handling for `letterhead` images) must produce a
  **PNG/JPEG** data-URI for the logo/seal in the PDF path — decode the stored WebP to PNG via GD
  before base64 embedding. (Browser path can use WebP directly.)
