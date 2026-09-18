<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Central manager for all file/image uploads.
 *
 * Every uploaded file is stored under the public "uploads" directory
 * (public/uploads/{folder}) with a randomized filename. The relative
 * path that is returned (e.g. "uploads/brands/ab12cd34.jpg") is what
 * should be persisted to the database, and Upload::url() turns it back
 * into a public URL.
 *
 * Legacy paths that were stored on the Laravel "public" disk
 * (storage/app/public, served from /storage) are still understood by
 * url() and delete() so existing records keep working.
 */
class Upload
{
    /** Base directory inside public/ where all uploads live. */
    public const BASE = 'uploads';

    /** Edge size (px) of the generated square "_sm" thumbnail. */
    public const SM_SIZE = 90;

    /** WebP encode quality (0-100). */
    public const WEBP_QUALITY = 82;

    /**
     * Store an uploaded file under public/uploads/{folder} with a random name.
     *
     * Images are converted to WebP and stored as a SINGLE ".webp" file (the
     * original is never kept) plus a square {SM_SIZE}px "_sm.webp" thumbnail.
     * Non-image files (PDF, etc.) are stored as-is.
     *
     * @return string Relative path for DB storage, e.g. "uploads/products/ab12.webp".
     */
    public static function store(UploadedFile $file, string $folder = ''): string
    {
        $folder = trim(str_replace('\\', '/', $folder), '/');
        $relativeDir = $folder !== '' ? self::BASE . '/' . $folder : self::BASE;
        $absoluteDir = public_path($relativeDir);

        if (! is_dir($absoluteDir)) {
            @mkdir($absoluteDir, 0755, true);
        }

        $base = Str::random(40);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');

        // Convert images to webp (+ sm thumbnail). The original is not stored.
        if (self::isConvertibleImage($extension) && function_exists('imagewebp')) {
            $webp = self::writeWebpVariants($file->getRealPath(), $absoluteDir, $base);
            if ($webp) {
                return $relativeDir . '/' . $base . '.webp';
            }
        }

        // Fallback: non-image (or conversion unavailable) — keep the original.
        $name = $base . '.' . $extension;
        $file->move($absoluteDir, $name);

        return $relativeDir . '/' . $name;
    }

    /** Image extensions we convert to webp. */
    private static function isConvertibleImage(string $ext): bool
    {
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true);
    }

    /**
     * Convert a source image to "{base}.webp" plus a square "{base}_sm.webp"
     * thumbnail inside $absoluteDir. Returns false on any failure.
     */
    private static function writeWebpVariants(string $sourcePath, string $absoluteDir, string $base): bool
    {
        try {
            $data = @file_get_contents($sourcePath);
            if ($data === false) {
                return false;
            }
            $img = @imagecreatefromstring($data);
            if (! $img) {
                return false;
            }
            imagepalettetotruecolor($img);
            imagealphablending($img, false);
            imagesavealpha($img, true);

            $main = @imagewebp($img, $absoluteDir . '/' . $base . '.webp', self::WEBP_QUALITY);
            self::writeSquareThumb($img, $absoluteDir . '/' . $base . '_sm.webp', self::SM_SIZE);

            imagedestroy($img);

            return (bool) $main;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Write a square cover-cropped webp thumbnail of $size px from a GD image.
     */
    private static function writeSquareThumb($src, string $destAbsolute, int $size): bool
    {
        try {
            $w = imagesx($src);
            $h = imagesy($src);
            if ($w < 1 || $h < 1) {
                return false;
            }

            $scale = max($size / $w, $size / $h); // cover
            $nw = (int) ceil($w * $scale);
            $nh = (int) ceil($h * $scale);

            $thumb = imagecreatetruecolor($size, $size);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefill($thumb, 0, 0, $transparent);

            $dx = (int) (($size - $nw) / 2);
            $dy = (int) (($size - $nh) / 2);
            imagecopyresampled($thumb, $src, $dx, $dy, 0, 0, $nw, $nh, $w, $h);

            $ok = @imagewebp($thumb, $destAbsolute, self::WEBP_QUALITY);
            imagedestroy($thumb);

            return (bool) $ok;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Derive the "_sm.webp" thumbnail path that corresponds to a stored path.
     * For "uploads/x/ab.webp" → "uploads/x/ab_sm.webp".
     */
    public static function smPath(string $path): string
    {
        $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');
        $name = pathinfo($path, PATHINFO_FILENAME);

        return ($dir === '.' ? '' : $dir . '/') . $name . '_sm.webp';
    }

    /**
     * Best-effort: write a sibling ".webp" next to a stored JPG/PNG so the
     * storefront can serve a smaller image via <picture>. Never throws — a
     * conversion failure must not break the upload.
     *
     * @return bool  True if a .webp was written.
     */
    public static function makeWebp(string $absolutePath): bool
    {
        try {
            if (! is_file($absolutePath) || ! function_exists('imagewebp')) {
                return false;
            }
            $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                return false;
            }
            // Decode from the raw bytes so a mislabeled extension (a PNG saved
            // as .jpg, etc.) still converts correctly.
            $data = @file_get_contents($absolutePath);
            if ($data === false) {
                return false;
            }
            $img = @imagecreatefromstring($data);
            if (! $img) {
                return false;
            }
            imagepalettetotruecolor($img);
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $ok = @imagewebp($img, $absolutePath . '.webp', 82);
            imagedestroy($img);

            return (bool) $ok;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Delete the old file (if any) and store the new one.
     *
     * @return string Relative path of the newly stored file.
     */
    public static function replace(?string $oldPath, UploadedFile $file, string $folder = ''): string
    {
        self::delete($oldPath);

        return self::store($file, $folder);
    }

    /**
     * Delete a stored file by its relative path.
     *
     * Handles both new "uploads/..." paths (public/) and legacy paths stored
     * on the "public" disk (storage/app/public).
     */
    public static function delete(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        // New public/uploads path.
        $publicPath = public_path($path);
        if (is_file($publicPath)) {
            @unlink($publicPath);
            // Legacy sibling ".webp" (e.g. "x.png.webp").
            if (is_file($publicPath . '.webp')) {
                @unlink($publicPath . '.webp');
            }
            // Square "_sm.webp" thumbnail generated at upload time.
            $sm = public_path(self::smPath($path));
            if (is_file($sm)) {
                @unlink($sm);
            }

            return;
        }

        // Legacy: file stored on the "public" disk.
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            // Nothing to delete / disk unavailable — ignore.
        }
    }

    /**
     * Build a public URL for a stored path.
     *
     * Backward compatible: new "uploads/" paths resolve under public/, while
     * legacy disk paths fall back to the "/storage" symlink.
     *
     * @param  string|null  $default  Fallback returned when $path is empty.
     */
    public static function url(?string $path, ?string $default = null): ?string
    {
        if (empty($path)) {
            return $default;
        }

        // Already an absolute URL.
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        // New uploads path or any file that physically exists under public/.
        if (Str::startsWith($path, self::BASE . '/') || file_exists(public_path($path))) {
            return asset($path);
        }

        // Legacy public-disk path served from /storage.
        return asset('storage/' . $path);
    }

    /**
     * Public URL for the square "_sm.webp" thumbnail of a stored image. Falls
     * back to the full-size URL when no thumbnail exists (e.g. legacy records
     * or non-image files).
     */
    public static function urlSm(?string $path, ?string $default = null): ?string
    {
        if (empty($path) || Str::startsWith($path, ['http://', 'https://', '//'])) {
            return self::url($path, $default);
        }

        $sm = self::smPath(ltrim(str_replace('\\', '/', $path), '/'));
        if (is_file(public_path($sm))) {
            return asset($sm);
        }

        return self::url($path, $default);
    }

    /**
     * Copy an already-stored file into public/uploads/{folder} under a new
     * random name. Understands both new "uploads/" paths and legacy
     * "public" disk paths. Returns the new relative path, or null if the
     * source could not be found.
     */
    public static function copy(?string $sourcePath, string $folder = ''): ?string
    {
        if (empty($sourcePath)) {
            return null;
        }

        // Resolve the source to an absolute file on disk.
        $absoluteSource = public_path($sourcePath);
        if (! is_file($absoluteSource)) {
            try {
                if (Storage::disk('public')->exists($sourcePath)) {
                    $absoluteSource = Storage::disk('public')->path($sourcePath);
                } else {
                    return null;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        $folder = trim(str_replace('\\', '/', $folder), '/');
        $relativeDir = $folder !== '' ? self::BASE . '/' . $folder : self::BASE;
        $absoluteDir = public_path($relativeDir);

        if (! is_dir($absoluteDir)) {
            @mkdir($absoluteDir, 0755, true);
        }

        $base = Str::random(40);
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'bin');

        // Images → re-encode to a single webp (+ sm thumbnail), matching store().
        if (self::isConvertibleImage($extension) && function_exists('imagewebp')) {
            if (self::writeWebpVariants($absoluteSource, $absoluteDir, $base)) {
                return $relativeDir . '/' . $base . '.webp';
            }
        }

        $name = $base . '.' . $extension;
        if (! @copy($absoluteSource, $absoluteDir . '/' . $name)) {
            return null;
        }

        return $relativeDir . '/' . $name;
    }

    /**
     * Whether a stored file currently exists (new or legacy location).
     */
    public static function exists(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        if (is_file(public_path($path))) {
            return true;
        }

        try {
            return Storage::disk('public')->exists($path);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
