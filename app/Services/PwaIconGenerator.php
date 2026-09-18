<?php

namespace App\Services;

/**
 * Generates the PWA icon set referenced by public/manifest.json
 * (public/images/icons/icon-{size}x{size}.png) from a single source image.
 *
 * Used to keep the installable-app icons in sync with the brand favicon
 * uploaded in Business Profile. The source is scaled to "contain" inside a
 * transparent square so the whole mark stays visible (no cropping), which
 * also suits maskable icons. Best results come from a square source of at
 * least 512px; smaller sources are upscaled (soft) but still valid.
 *
 * Never throws — a generation failure must not break the settings save.
 */
class PwaIconGenerator
{
    /** Icon edge sizes required by public/manifest.json. */
    public const SIZES = [72, 96, 128, 144, 152, 192, 384, 512];

    /**
     * Generate the full icon set from an absolute source image path
     * (PNG/WebP/JPG). Returns true only if every size was written.
     */
    public function generateFromFile(string $absoluteSourcePath): bool
    {
        if (! is_file($absoluteSourcePath) || ! function_exists('imagecreatefromstring')) {
            return false;
        }

        $data = @file_get_contents($absoluteSourcePath);
        if ($data === false) {
            return false;
        }

        $source = @imagecreatefromstring($data);
        if (! $source) {
            return false;
        }

        imagepalettetotruecolor($source);
        $sw = imagesx($source);
        $sh = imagesy($source);
        if ($sw < 1 || $sh < 1) {
            imagedestroy($source);

            return false;
        }

        $dir = public_path('images/icons');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ok = true;
        foreach (self::SIZES as $size) {
            $ok = $this->writeSquare($source, $sw, $sh, $size, $dir . "/icon-{$size}x{$size}.png") && $ok;
        }

        imagedestroy($source);

        return $ok;
    }

    /**
     * Write a transparent-padded square PNG of $size px from the source GD
     * image, scaled to "contain" (whole image visible, centered).
     */
    private function writeSquare($source, int $sw, int $sh, int $size, string $dest): bool
    {
        try {
            $dst = imagecreatetruecolor($size, $size);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);

            $scale = min($size / $sw, $size / $sh); // contain
            $nw = max(1, (int) round($sw * $scale));
            $nh = max(1, (int) round($sh * $scale));
            $dx = (int) (($size - $nw) / 2);
            $dy = (int) (($size - $nh) / 2);

            imagecopyresampled($dst, $source, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);

            $written = @imagepng($dst, $dest, 9);
            imagedestroy($dst);

            return (bool) $written;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
