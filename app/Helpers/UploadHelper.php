<?php

use App\Helpers\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

if (! function_exists('upload_store')) {
    /**
     * Store an uploaded file under public/uploads/{folder}.
     * Returns the relative path to persist in the database.
     */
    function upload_store(UploadedFile $file, string $folder = ''): string
    {
        return Upload::store($file, $folder);
    }
}

if (! function_exists('upload_replace')) {
    /**
     * Delete the old file (if any) and store the new one.
     */
    function upload_replace(?string $oldPath, UploadedFile $file, string $folder = ''): string
    {
        return Upload::replace($oldPath, $file, $folder);
    }
}

if (! function_exists('upload_delete')) {
    /**
     * Delete a stored file by its relative path (new or legacy location).
     */
    function upload_delete(?string $path): void
    {
        Upload::delete($path);
    }
}

if (! function_exists('upload_url')) {
    /**
     * Build a public URL for a stored file path. Backward compatible with
     * legacy "/storage" disk paths.
     */
    function upload_url(?string $path, ?string $default = null): ?string
    {
        return Upload::url($path, $default);
    }
}

if (! function_exists('upload_url_sm')) {
    /**
     * Public URL for the square 90x90 "_sm.webp" thumbnail of a stored image,
     * falling back to the full-size image when no thumbnail exists.
     */
    function upload_url_sm(?string $path, ?string $default = null): ?string
    {
        return Upload::urlSm($path, $default);
    }
}

if (! function_exists('storefront_image')) {
    /**
     * Resolve an admin-managed storefront image (stored in EcommerceSetting as a
     * relative upload path) to a public URL, falling back to a bundled theme
     * asset when the setting is empty. Keeps the "dynamic ?? static" pattern in
     * one place so the customer auth pages and blog sidebar stay DRY.
     *
     * @param  string       $key            EcommerceSetting key (e.g. "auth_login_image").
     * @param  string|null  $fallbackAsset  Public-relative asset path used when unset (e.g. "website/assets/images/sign_in_img.jpg").
     */
    function storefront_image(string $key, ?string $fallbackAsset = null): ?string
    {
        $path = \Modules\Ecommerce\Models\EcommerceSetting::get($key);

        if (filled($path)) {
            return upload_url($path);
        }

        return $fallbackAsset ? asset($fallbackAsset) : null;
    }
}

if (! function_exists('bg_image_set')) {
    /**
     * Build a CSS background declaration that serves a WebP via image-set()
     * with the original as fallback, for use in a style attribute. Works for
     * both uploaded paths and theme assets (any public-relative path). When no
     * .webp sibling exists it falls back to a plain url(). Output is safe CSS
     * (no HTML special chars) and is intended to be printed with {!! !!}.
     *
     * @param  string|null  $path     Public-relative path (e.g. "uploads/a.jpg" or "website/assets/images/b.jpg").
     * @param  string|null  $default  Fallback URL used when $path is empty.
     */
    function bg_image_set(?string $path, ?string $default = null): string
    {
        if (filled($path) && ! Str::startsWith($path, ['http://', 'https://', '//'])) {
            $rel  = ltrim(str_replace('\\', '/', $path), '/');
            $orig = upload_url($path);
            if ($orig && is_file(public_path($rel . '.webp'))) {
                $webp = asset($rel . '.webp');

                return "background-image: url($orig); "
                    . "background-image: image-set(url($webp) type('image/webp'), url($orig) type('image/jpeg'))";
            }
            if ($orig) {
                return "background-image: url($orig)";
            }
        }

        $url = filled($path) ? upload_url($path, $default) : $default;

        return $url ? "background-image: url($url)" : '';
    }
}
