<?php

namespace Modules\Setting\Services;

use Modules\Setting\Models\Setting;
use App\Helpers\Upload;

class SettingService
{
    public static function getAccountingMode(): string
    {
        return 'simple';
    }

    public static function isSimpleMode(): bool
    {
        return self::getAccountingMode() === 'simple';
    }

    public static function isAdvancedMode(): bool
    {
        return self::getAccountingMode() === 'advanced';
    }

    /**
     * Get all settings grouped by group, with type casting.
     */
    public function getAll(): array
    {
        $grouped = [];

        // Built from the cached settings map (Bug_91) rather than re-querying.
        foreach (Setting::cachedAll() as $group => $entries) {
            foreach ($entries as $key => $entry) {
                $grouped[$group][$key] = match ($entry['type']) {
                    'boolean' => filter_var($entry['value'], FILTER_VALIDATE_BOOLEAN),
                    'integer' => (int) $entry['value'],
                    'json' => json_decode($entry['value'], true),
                    default => $entry['value'],
                };
            }
        }

        return $grouped;
    }

    /**
     * Get all settings for a specific group.
     */
    public function getGroup(string $group): array
    {
        return Setting::getGroup($group);
    }

    /**
     * Update all keys in a group from an associative array.
     */
    public function updateGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === '_token' || $key === '_method') {
                continue;
            }

            Setting::set($group, $key, $value);
        }
    }

    /**
     * Update business profile settings, handling logo upload.
     */
    public function updateBusinessProfile(array $data): void
    {
        if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
            $path = Upload::store($data['logo'], 'settings');
            Setting::set('business', 'logo', $path, 'string');
            unset($data['logo']);
        }

        if (isset($data['favicon']) && $data['favicon'] instanceof \Illuminate\Http\UploadedFile) {
            $path = Upload::store($data['favicon'], 'settings');
            Setting::set('business', 'favicon', $path, 'string');

            // Keep the installable PWA app icons (manifest.json) in sync with
            // the brand favicon. Best with a square source >= 512px; never
            // fatal if generation fails (e.g. an .ico source GD can't read).
            app(\App\Services\PwaIconGenerator::class)->generateFromFile(public_path($path));

            unset($data['favicon']);
        }

        // Normalise a bare domain into a full, canonical address so the user
        // can simply type "bizpos.com" and we store "https://www.bizpos.com".
        if (array_key_exists('website', $data)) {
            $data['website'] = $this->normalizeWebsite($data['website']);
        }

        // Handle boolean checkboxes
        $booleans = ['allow_backdate'];
        foreach ($booleans as $key) {
            $data[$key] = isset($data[$key]) ? '1' : '0';
        }

        $startDateChanged = array_key_exists('business_start_date', $data)
            && (string) $data['business_start_date'] !== (string) Setting::get('business', 'business_start_date', '');

        foreach ($data as $key => $value) {
            if ($key === '_token' || $key === '_method') {
                continue;
            }

            Setting::set('business', $key, $value);
        }

        // Courier settlements are filtered by the start date and cached for an
        // hour, so a change would otherwise appear to do nothing until the TTL
        // lapsed.
        if ($startDateChanged) {
            app(\Modules\Ecommerce\Services\SteadfastApiService::class)->forgetPaymentsCache();
        }
    }

    /**
     * Turn whatever the user typed in the Website field into a canonical
     * address. Accepts a bare domain ("bizpos.com"), a www host, or a full
     * URL, and always returns "https://…". A bare registrable domain (no
     * subdomain) gains a "www." prefix, so "bizpos.com" → "https://www.bizpos.com".
     * An empty value stays empty.
     */
    private function normalizeWebsite($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // Drop any scheme the user typed — we re-apply a canonical https://.
        $work = preg_replace('#^[a-z][a-z0-9+.\-]*://#i', '', $value);

        // Split host from any path/query so we only touch the host.
        $slashPos = strpos($work, '/');
        $host = $slashPos === false ? $work : substr($work, 0, $slashPos);
        $rest = $slashPos === false ? '' : substr($work, $slashPos);
        $host = strtolower(rtrim($host, '.'));

        if ($host !== '' && ! str_starts_with($host, 'www.') && $this->isBareDomain($host)) {
            $host = 'www.' . $host;
        }

        return 'https://' . $host . $rest;
    }

    /**
     * A "bare" domain is a registrable domain with no subdomain — the kind
     * that should get a "www." prefix. Handles common two-level TLDs (e.g.
     * Bangladesh's .com.bd, .co.uk) so "bizpos.com.bd" still counts as bare.
     */
    private function isBareDomain(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        $twoLevelTlds = [
            'com.bd', 'net.bd', 'org.bd', 'gov.bd', 'edu.bd', 'ac.bd',
            'co.uk', 'org.uk', 'me.uk', 'co.in', 'com.au', 'net.au',
            'org.au', 'co.nz', 'com.sg', 'com.my',
        ];

        foreach ($twoLevelTlds as $suffix) {
            if ($host === $suffix) {
                return false;
            }
            if (str_ends_with($host, '.' . $suffix)) {
                $prefix = substr($host, 0, -(strlen($suffix) + 1));
                return $prefix !== '' && ! str_contains($prefix, '.');
            }
        }

        // Single-level TLD: a registrable domain has exactly two labels.
        return substr_count($host, '.') === 1;
    }

    /**
     * Update tax/VAT settings.
     */
    public function updateTaxSettings(array $data): void
    {
        $booleanKeys = [
            'vat_enabled', 'tax_inclusive',
            'mushak_63', 'mushak_65', 'mushak_91',
        ];

        $this->updateGroupWithBooleans('tax', $data, $booleanKeys);
    }

    /**
     * Update invoice and receipt settings.
     */
    public function updateInvoiceSettings(array $data): void
    {
        $booleanKeys = [
            'show_logo', 'auto_print', 'show_vat', 'bangla_amount_words',
        ];

        $this->updateGroupWithBooleans('invoice', $data, $booleanKeys);
    }

    /**
     * Update courier and delivery settings.
     *
     * Also syncs the per-courier credentials + enabled flag into the
     * `courier_providers` table — that's the source of truth read by the
     * sales page when listing "Send to courier" options, by the Steadfast
     * API service for real API calls, etc.
     */
    public function updateCourierSettings(array $data): void
    {
        $booleanKeys = [
            'cod_enabled', 'tracking_enabled', 'courier_sms',
            'pathao_enabled', 'steadfast_enabled', 'ecourier_enabled',
            'redx_enabled', 'paperfly_enabled',
        ];

        $this->updateGroupWithBooleans('courier', $data, $booleanKeys);

        $this->syncCourierProviders($data);
    }

    /**
     * Sync credentials + enabled flag from the settings form into the
     * matching `courier_providers` row. Only fields actually present in
     * `$data` are written — empty/missing fields don't clobber existing
     * rows in case the form ever submits a partial payload.
     */
    private function syncCourierProviders(array $data): void
    {
        $providerMap = [
            'steadfast' => [
                'is_active'  => 'steadfast_enabled',
                'api_key'    => 'steadfast_api_key',
                'api_secret' => 'steadfast_api_secret',
            ],
            'pathao' => [
                'is_active'  => 'pathao_enabled',
                'api_key'    => 'pathao_client_id',
                'api_secret' => 'pathao_client_secret',
                'store_id'   => 'pathao_merchant_id',
            ],
        ];

        foreach ($providerMap as $slug => $fields) {
            $updates = [];
            foreach ($fields as $providerCol => $settingKey) {
                if (!array_key_exists($settingKey, $data)) {
                    continue;
                }
                $value = $data[$settingKey];
                if ($providerCol === 'is_active') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } else {
                    $value = is_string($value) ? trim($value) : $value;
                    $value = ($value === '' ? null : $value);
                }
                $updates[$providerCol] = $value;
            }
            if ($updates !== []) {
                \Modules\Ecommerce\Models\CourierProvider::where('slug', $slug)->update($updates);
            }
        }
    }

    /**
     * Update notification settings.
     */
    public function updateNotificationSettings(array $data): void
    {
        $booleanKeys = array_filter(array_keys($data), function ($key) {
            return str_starts_with($key, 'notify_') || str_starts_with($key, 'low_stock_alert') || str_starts_with($key, 'payment_reminder');
        });

        $this->updateGroupWithBooleans('notification', $data, $booleanKeys);
    }

    /**
     * Update localization settings.
     */
    public function updateLocalization(array $data): void
    {
        $booleanKeys = [
            'bangla_digits', 'bangla_receipt', 'bangla_invoice',
        ];

        $this->updateGroupWithBooleans('localization', $data, $booleanKeys);
    }

    /**
     * Update SMS gateway settings.
     */
    public function updateSmsGateway(array $data): void
    {
        $this->updateGroup('sms', $data);
    }

    public function updateLandingPage(array $data): void
    {
        $mode = $data['mode'] ?? 'full_site';
        $activeId = !empty($data['active_landing_page_id']) ? (int) $data['active_landing_page_id'] : null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($mode, $activeId) {
            Setting::set('landing_page', 'mode', $mode);
            Setting::set('landing_page', 'active_landing_page_id', $activeId ?? 0, 'integer');

            if (!class_exists(\Modules\LandingPage\Models\LandingPage::class)) {
                return;
            }

            \Modules\LandingPage\Models\LandingPage::where('is_active', true)
                ->when($activeId, fn($q) => $q->where('id', '!=', $activeId))
                ->update(['is_active' => false]);

            if ($activeId) {
                \Modules\LandingPage\Models\LandingPage::where('id', $activeId)
                    ->update(['is_active' => $mode === 'landing_page']);
            }
        });
    }

    /**
     * Update tracking & analytics settings.
     */
    public function updateTracking(array $data): void
    {
        $this->updateGroupWithBooleans('tracking', $data, ['gtm_enabled', 'fbpixel_enabled', 'ga4_enabled']);
    }

    /**
     * Update a group, treating specified keys as booleans (checkboxes).
     * Checkboxes that are unchecked are not submitted, so we default them to false.
     */
    protected function updateGroupWithBooleans(string $group, array $data, array $booleanKeys): void
    {
        // Set unchecked checkboxes to false
        foreach ($booleanKeys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = false;
            }
        }

        foreach ($data as $key => $value) {
            if ($key === '_token' || $key === '_method') {
                continue;
            }

            if (in_array($key, $booleanKeys, true)) {
                Setting::set($group, $key, (bool) $value, 'boolean');
            } else {
                Setting::set($group, $key, $value);
            }
        }
    }

    /**
     * Branding data for the printed letterhead documents (quotation /
     * invoice): business identity, footer contact links, and the accent
     * color picked in Settings → Invoice & Receipt.
     *
     * For DomPDF ($forPdf) the logo is inlined as a PNG data URI — DomPDF
     * cannot decode webp (the upload pipeline's format) and remote fetches
     * are disabled by default, so a data URI is the only reliable source.
     */
    public static function printBranding(bool $forPdf = false): array
    {
        $stripUrl = fn ($u) => preg_replace('#^https?://#', '', rtrim((string) $u, '/'));

        $logoPath = Setting::get('business', 'logo');
        $logoUrl = $logoPath ? upload_url($logoPath) : null;

        if ($forPdf && $logoPath && is_file(public_path($logoPath))) {
            $abs = public_path($logoPath);
            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
            if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                $im = @imagecreatefromwebp($abs);
                if ($im) {
                    ob_start();
                    imagepng($im);
                    $png = ob_get_clean();
                    imagedestroy($im);
                    $logoUrl = 'data:image/png;base64,' . base64_encode($png);
                }
            } else {
                $logoUrl = 'data:' . mime_content_type($abs) . ';base64,' . base64_encode(file_get_contents($abs));
            }
        }

        $facebook = $stripUrl(Setting::get('business', 'facebook_url', ''));
        $accent = Setting::get('invoice', 'accent_color') ?: '#ED1C24';

        $brand = [
            'name'     => Setting::get('business', 'company_name', 'BizPOS Pro'),
            'address'  => Setting::get('business', 'address', ''),
            'phone'    => Setting::get('business', 'company_phone', ''),
            'email'    => Setting::get('business', 'company_email') ?: Setting::get('business', 'email', ''),
            'facebook' => $facebook ? str_replace('www.facebook.com', 'fb.com', $facebook) : '',
            'website'  => $stripUrl(Setting::get('business', 'website', '')),
            'logoUrl'  => $logoUrl,
            'accent'   => $accent,
            'currency' => Setting::get('localization', 'currency', 'BDT'),
        ];

        return $brand;
    }
}
