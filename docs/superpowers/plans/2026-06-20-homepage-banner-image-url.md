# Plan — Homepage section banners → image + URL (clickable), with the product-style uploader

**Date:** 2026-06-20
**Branch:** `feat/product-variant-management`
**Goal:** Make the homepage-section "banner" options work like the banner slider — **image + URL only** — and render the banner image on the storefront as a single clickable link to that URL. Also replace the plain `<input type=file>` in section settings with the product-create thumbnail uploader (`<x-core::image-upload>`).

---

## Scope — the 3 banners

All driven by `Modules/Ecommerce/app/Support/HomepageSectionSchema.php`:

| Section | Current banner fields | After |
|---|---|---|
| `best_selling` (section 7) | `promo_image`, `promo_title`, `promo_button_label`, `promo_button_link` | `promo_image` + `promo_button_link` (relabel **"Banner URL"**) |
| `special_brand` | `banner_image`, `banner_heading`, `banner_subtitle`, `banner_button_label`, `banner_button_link` | `banner_image` + `banner_button_link` (**"Banner URL"**) |
| `favourite` | same `sideBanner()` set | same → `banner_image` + `banner_button_link` |

`special_brand` + `favourite` share the `sideBanner()` builder, so one edit covers both.

> **Design note / assumption:** "like the banner slider … image, url" → the heading/subtitle/button **text overlays are dropped** from these banners; the storefront shows just the clickable image. This is a visible homepage change. If you'd rather keep the text and *only* make the image clickable, say so — it's a small variation.

Out of scope: `newsletter` background image and `features` badge icons are not "banners" — left as-is (but they *do* benefit from the new uploader in Part A, since it applies to all image fields).

---

## Part A — Settings form: use the product thumbnail uploader

**File:** `Modules/Ecommerce/resources/views/homepage-section-settings.blade.php` — the `@case('image')` block (lines ~86-103).

Replace the bespoke `<input type=file>` + `<img class=bp-settings-thumb>` + "Remove (revert to default)" checkbox with:

```blade
@case('image')
    @php $imgUrl = $cur ? upload_url($cur) : asset($f['default']); @endphp
    <x-core::image-upload
        name="fields[{{ $key }}]"
        id="secimg_{{ $key }}"
        :label="false"
        removeName="remove[{{ $key }}]"
        :current="$imgUrl"
        hint="JPG, PNG, WebP — max 2MB" />
@break
```

- The component's hidden remove flag flips to `1` on ✕ — the controller already reads `$request->boolean("remove.$key")`, so **no controller change needed**.
- File posts as `fields[$key]` exactly as today → `homepageSectionSettingsUpdate()` keeps working unchanged.
- The inline FileReader preview script + `.bp-settings-thumb` CSS become dead for these fields (the component has its own preview). Remove the now-unused `input[type=file][name^=fields]` preview handler from the page's `@push('scripts')`.
- `:label="false"` because the field already prints its own `<label>` above the switch.

This swap applies to **every** `image` field in section settings (banners, newsletter bg, feature badges) — consistent uploader everywhere.

---

## Part B — Schema: reduce the 3 banners to image + URL

**File:** `HomepageSectionSchema.php`

1. **`best_selling`** — replace the 4-field promo block with:
   ```php
   self::field('promo_image', 'Banner image', 'image', 'Banner', 'website/assets/images/best_sell_pro_img_4.jpg'),
   self::field('promo_button_link', 'Banner URL', 'link', 'Banner', 'storefront.shop.index'),
   ```
2. **`sideBanner()`** builder (used by `special_brand` + `favourite`) — reduce to:
   ```php
   private static function sideBanner(string $image): array {
       return [
           self::field('banner_image', 'Banner image', 'image', 'Banner', $image),
           self::field('banner_button_link', 'Banner URL', 'link', 'Banner', 'storefront.shop.index'),
       ];
   }
   ```
   Update the two `sideBanner(...)` calls to pass only the image path.

**Keeping the existing key names** (`promo_button_link`, `banner_button_link`, `*_image`) means **no data migration** — stored values keep working, and dropped keys (`promo_title`, `banner_heading`, …) simply stop being read (harmless leftovers in the settings JSON).

The `link` field type already gives preset routes + a custom-URL box — that's our "URL" control.

---

## Part C — Storefront: clickable banner image, no overlay

Wrap the banner `<x-webp>` in an `<a href="resolvedLink">` and drop the `.text` overlay block.

1. **`best_selling.blade.php`** — the `@else` (no 4th product) promo block (lines ~117-127):
   ```blade
   <div class="best_selling_product_item_large">
       <a href="{{ $promoBtnLink }}">
           <x-webp :src="$promoImage" :default="asset('website/assets/images/best_sell_pro_img_4.jpg')" alt="Best Sales" class="img-fluid w-100" loading="lazy" />
       </a>
   </div>
   ```
   Drop `$promoTitle` / `$promoBtnLbl` usage. (`$promoBtnLink` already resolved at top.)

2. **`special_brand.blade.php`** (lines ~45-53): wrap `.special_product_banner` image in `<a href="{{ $bannerBtnLink }}">`, remove the `<div class="text">…</div>`.

3. **`favourite.blade.php`** (lines ~26-34): wrap `.bundle_product_banner` image in `<a href="{{ $bannerBtnLink }}">`, remove the `<div class="text">…</div>`.

Keep the `@php` link resolution (`resolveLink(...)`); remove the now-unused heading/subtitle/button `getSetting` lines.

**CSS:** the existing `.text` overlay styling becomes unused for these blocks; the image already fills the banner container, so no layout change expected. Verify the banner box still has height (it's driven by the image) — if a section relied on the text for height, add `d-block` to the image. Check during verification.

---

## Verification

1. `php artisan view:clear`; load `/admin/ecommerce/homepage-sections/7/settings` (best_selling) → Banner group shows **only** the new image uploader + a "Banner URL" link control.
2. Upload an image + set a custom URL, Save → 200, value persisted (`section->settings`).
3. Repeat for the `special_brand` and `favourite` sections.
4. Storefront `/` → each banner image is wrapped in `<a href>` pointing at the configured URL; clicking navigates there; no leftover overlay text. Verify with curl/grep for `<a href=…><…webp` around the banner blocks.
5. Confirm the new uploader's ✕ remove still reverts to default (controller `remove.$key` path).
6. Blade-compile check on the 4 edited views; full route smoke (home + the 3 settings pages return 200).

---

## Commit plan (one branch, grouped)
1. `feat(ecommerce): product-style image uploader in homepage section settings` (Part A)
2. `feat(ecommerce): homepage banners → image + URL only` (Part B + C)

No DB/migration changes. Display + admin-form only.
