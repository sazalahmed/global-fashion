# Homepage Sections — Full Dynamic Content Plan (Phase-wise)

> **Goal:** make every piece of content in every storefront homepage section editable from
> the admin panel — titles, subtitles, headings (with an emphasized word), banner images,
> banner texts, buttons (label + link), behavior flags, and a working newsletter — with no
> code changes per edit.
>
> **Builds on** the merged work: curated products per section, drag-reorder, instant
> active toggle, per-section settings page (PR #18).
>
> **How to read this doc:** §A–C are reference (decisions, inventory, architecture). §1
> onward is the **phase-by-phase build plan** — each phase is self-contained with its own
> objective, tasks, files, deliverable, and acceptance check. Ship phases in order.

---

## A. Confirmed decisions

| Topic | Decision |
|---|---|
| **Headings** | Two plain-text fields `heading` + `highlight`; partial wraps first match of `highlight` in a `<span>` (escape-then-wrap → XSS-safe). No admin raw HTML. |
| **Newsletter** | Editable **and** functional — content fields + working subscribe endpoint storing emails in a new `newsletter_subscribers` table. |
| **Links** | `link` field = preset storefront-route dropdown + "Custom URL", stored as a resolved URL string. |
| **Storage** | No new `homepage_sections` columns; all extras live in the existing `settings` JSON. Images via `Upload` → `public/uploads/homepage-sections` (path stored in JSON). |

---

## B. Section inventory & coverage (13 canonical types)

| # | section_type | Partial | Data source | Seeded? | Renders? | Action |
|---|---|---|:--:|:--:|:--:|---|
| 1 | hero_slider | `hero_slider` | `Banner` (`hero`) | ✅ | ✅ | autoplay/interval; slides via Banners |
| 2 | features | `features` (empty) | — | ✅ | ❌ | rebuild as trust-badges **or** drop |
| 3 | flash_deals | `flash_deals` | `FlashDeal` | ✅ | ✅ | heading/highlight/view-all |
| 4 | categories | `categories` | `getTopCategories` | ✅ | ✅ | heading/highlight/subtitle/count |
| 5 | promo_banners | `promo_banners` | `Banner` (`promo_large`) | ✅ | ✅ | optional heading; via Banners |
| 6 | new_arrivals | `new_arrivals` | products (+curation) | ✅ | ✅ | heading/highlight/view-all |
| 7 | best_selling | `best_selling` | products (+curation) | ✅ | ✅ | + promo card (image/title/button) |
| 8 | brands | `brands` (empty) | `getActiveBrands` | ✅ | ❌ | rebuild logos row **or** drop |
| 9 | blog | `blog` | `getPublishedPosts` | ✅ | ✅ | heading/highlight/view-all |
| 10 | newsletter | `partials/newsletter` | — | ✅ | ✅ static | full content + working form |
| 11 | trending | `trending` | products (+curation) | ❌ **gap** | ❌ | **seed it** |
| 12 | special_brand | `special_brand` | products (+curation) | ❌ **gap** | ❌ | **seed it** + side banner |
| 13 | favourite | `favourite` | products (+curation) | ❌ **gap** | ❌ | **seed it** + side banner |

**Orphaned partials to delete:** `banner`, `flash-sale`, `new-arrivals` (hyphen).

---

## C. Architecture (schema-driven)

One declarative **field schema per `section_type`** drives the admin form, validation, and
documents what each partial reads — add a field/section by editing one config (Open/Closed).

```
Modules/Ecommerce/app/Support/HomepageSectionSchema.php   ← single source of truth
  section_type => [ {key, label, type, group, default, options}, … ]
```

**Field types:** `text · textarea · image · link · number · switch · highlight · repeater`
**Groups (form sections):** Heading · Banner · Button · Behavior

**Per-section fields**

| Section | Fields |
|---|---|
| hero_slider | autoplay(switch), interval(number) |
| features | heading, highlight, badges **repeater**(icon,title,subtitle) |
| flash_deals | heading, highlight, view_all_label, view_all_link |
| categories | heading, highlight, subtitle, items_count |
| promo_banners | heading (optional) |
| new_arrivals | heading, highlight, subtitle, view_all_label, view_all_link, items_count |
| best_selling | …common… + promo_image, promo_title, promo_button_label, promo_button_link |
| trending | heading, highlight, subtitle, items_count |
| special_brand | …common… + banner_image, banner_heading, banner_subtitle, banner_button_label, banner_button_link |
| favourite | same as special_brand |
| blog | heading, highlight, view_all_label, view_all_link |
| brands | heading, highlight, items_count |
| newsletter | background_image, heading, highlight, subheading, input_placeholder, button_label |

---

# Phase-by-phase build plan

Dependency chain: **0 → 1 → 2 → 3 → 4 → 5**. Phases 1–2 give a working editor; Phase 3
makes edits visible; Phase 4 adds the newsletter; Phase 5 seeds defaults + QA.

---

## Phase 0 — Reconcile section coverage
**Objective:** make "all sections" literally true before wiring dynamic content.

**Tasks**
- [ ] Seed the 3 missing sections (`trending`, `special_brand`, `favourite`) in `HomepageSectionSeeder` with sensible `title`/`sort_order`/`is_active`.
- [ ] Resolve `features`: rebuild as a trust-badges section **or** remove its seeder row + partial. *(Recommended: rebuild.)*
- [ ] Resolve `brands`: rebuild `brands.blade.php` as a dynamic logos row (data from `getActiveBrands`) **or** remove its seeder row. *(Recommended: rebuild.)*
- [ ] Delete orphaned partials: `banner`, `flash-sale`, `new-arrivals`.

**Files:** `HomepageSectionSeeder.php`, `home/partials/{features,brands}.blade.php`, delete 3 orphans.

**Deliverable:** every seeded `section_type` has a matching non-empty partial and a `match()` arm.

**Acceptance:** homepage renders all intended rows in DB-driven mode; no empty/orphan partials; `php artisan db:seed --class=HomepageSectionSeeder` is idempotent.

---

## Phase 1 — Schema foundation (backend)
**Objective:** define the field schema and make the settings controller persist any field.

**Tasks**
- [ ] Create `HomepageSectionSchema` with the §C field map.
- [ ] Add a `link` resolver helper (`{route_name | custom_url}` → URL string) + a list of preset storefront routes.
- [ ] Extend `ContentController@homepageSectionSettingsUpdate`: iterate the schema, validate per field type, handle `image` upload/replace/delete via `Upload`, persist all keys into `settings`.
- [ ] Service helper(s) in `ContentManagementService` for schema-aware settings save if needed.

**Files:** `app/Support/HomepageSectionSchema.php` (new), `ContentController.php`, `ContentManagementService.php`.

**Deliverable:** posting arbitrary schema fields persists them into `settings` (images stored, links resolved).

**Acceptance:** tinker/HTTP save of each field type round-trips correctly; replacing an image deletes the old file; invalid input is rejected.

**Depends on:** Phase 0.

---

## Phase 2 — Dynamic admin settings form
**Objective:** auto-render the per-section settings form from the schema.

**Tasks**
- [ ] Rewrite `homepage-section-settings.blade.php` as a generic schema renderer.
- [ ] One small Blade partial per field type: `text`, `textarea`, `image` (preview + remove), `link` (preset select + custom URL), `number`, `switch`, `repeater`.
- [ ] Group fields by their `group` (Heading / Banner / Button / Behavior); multipart form; reuse the existing **Settings** button.

**Files:** `homepage-section-settings.blade.php`, new field partials under `resources/views/.../sections/fields/`.

**Deliverable:** each section's Settings page shows exactly the fields its schema defines.

**Acceptance:** every field type renders, saves, and re-populates on reload; image preview works; link picker toggles custom URL.

**Depends on:** Phase 1.

---

## Phase 3 — Storefront partials read from settings
**Objective:** make the storefront honor the edited content, with zero visual change until edited.

**Tasks**
- [ ] Add shared component `<x-section-heading :heading :highlight />` (escape-then-wrap first match).
- [ ] Refactor each rendered partial to read `$section->title/subtitle/getSetting(...)` with current hardcoded strings as **defaults**; make `$section` null-safe.
- [ ] Replace hardcoded banner/promo images, texts, buttons in `best_selling`, `special_brand`, `favourite`.
- [ ] Replace hardcoded headings/view-all links in `flash_deals`, `categories`, `new_arrivals`, `trending`, `blog`.

**Files:** the section partials above + new `section-heading` component.

**Deliverable:** editing a field in admin changes the storefront; untouched fields look identical to today.

**Acceptance:** edit each section's fields → verify on storefront; defaults unchanged when settings empty.

**Depends on:** Phase 2.

---

## Phase 4 — Newsletter (editable + functional)
**Objective:** newsletter content editable and the form actually captures subscribers.

**Tasks**
- [ ] Migration `create_newsletter_subscribers_table` (`email` unique, `is_active`, timestamps) + `NewsletterSubscriber` model.
- [ ] `NewsletterController@subscribe` + storefront route (validate email, handle duplicates, return flash/JSON).
- [ ] Make newsletter a proper editable partial (bg image, heading, highlight, subheading, placeholder, button) + wire the form to the endpoint.

**Files:** new migration, `NewsletterSubscriber.php`, `NewsletterController.php`, `routes/storefront.php`, `partials/newsletter.blade.php`.

**Deliverable:** editable newsletter section that stores real subscribers.

**Acceptance:** subscribe happy-path stores a row; duplicate handled gracefully; content edits reflect on storefront.

**Depends on:** Phase 3.

---

## Phase 5 — Seed defaults, sync-guard & QA
**Objective:** ship-ready — pre-filled editor, drift protection, full verification.

**Tasks**
- [ ] Update `HomepageSectionSeeder` so every section ships sensible default `settings` (editor opens pre-filled, not blank).
- [ ] Add a test asserting **seeded types ↔ `getHomepageData()` match arms ↔ schema keys ↔ partials** are all in sync.
- [ ] QA: route sweep; each section curated + empty; image upload/replace/remove; link presets + custom; newsletter subscribe + duplicate; `php artisan view:cache` compiles clean.

**Files:** `HomepageSectionSeeder.php`, new test under `Modules/Ecommerce/tests/`.

**Deliverable:** every section fully dynamic, defaults preserved, drift-guarded.

**Acceptance:** sync test passes; full QA checklist green.

**Depends on:** Phase 4.

---

## Guardrails (apply across all phases)
- **XSS:** headings stored as plain text; span wrap escapes first. No admin raw HTML.
- **Defaults preserve appearance:** every `getSetting` read defaults to the current hardcoded value.
- **Image cleanup:** replace/remove deletes the previous file via `Upload::delete`.
- **Null-safety:** partials tolerate a missing `$section`.
- **Migrations:** run `php artisan migrate` on deploy (Phase 4 adds a table).

## Risk & footprint
Low risk (additive, JSON-backed, defaults preserve output). ~3 backend files + 1 schema +
1–2 migrations + form rewrite + field partials + ~10 partial refactors + 1 component + 1 test.
