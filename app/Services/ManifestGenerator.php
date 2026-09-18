<?php

namespace App\Services;

use Modules\Setting\Models\Setting;

/**
 * Writes public/manifest.json with the brand name taken from Settings, so the
 * PWA "Install app" prompt and the installed home-screen icon show the business
 * name instead of the shipped default. Run on demand from Settings, and kept as
 * a static file so the service worker can precache it.
 */
class ManifestGenerator
{
    /**
     * Regenerate the manifest at $path (defaults to public/manifest.json),
     * preserving everything except the name fields. Returns the written array.
     */
    public function generate(?string $path = null): array
    {
        $path = $path ?? public_path('manifest.json');

        $manifest = [];
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $manifest = $decoded;
            }
        }

        $name = trim((string) Setting::get('business', 'company_name', 'BizPOS Pro'));
        if ($name === '') {
            $name = 'BizPOS Pro';
        }

        $manifest['name']       = $name;
        $manifest['short_name'] = $this->shortName($name);

        file_put_contents(
            $path,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
        );

        return $manifest;
    }

    /**
     * Home-screen labels read best at ~12 characters: keep a short name whole,
     * otherwise fall back to the first word instead of a mid-word cut.
     */
    private function shortName(string $name): string
    {
        if (mb_strlen($name) <= 12) {
            return $name;
        }

        return explode(' ', $name)[0];
    }
}
