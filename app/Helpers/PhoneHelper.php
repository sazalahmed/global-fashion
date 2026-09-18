<?php

namespace App\Helpers;

use Modules\Setting\Models\Setting;

class PhoneHelper
{
    /**
     * Country phone configurations.
     * Each entry: [code, dial_code, length, placeholder, pattern, format_example]
     */
    protected static array $countries = [
        'BD' => ['code' => 'BD', 'dial_code' => '+880', 'length' => 11, 'placeholder' => '01XXX-XXXXXX', 'pattern' => '01[3-9]\d{8}', 'example' => '01712-345678'],
        'IN' => ['code' => 'IN', 'dial_code' => '+91',  'length' => 10, 'placeholder' => '9XXXX XXXXX',  'pattern' => '[6-9]\d{9}',   'example' => '98765 43210'],
        'PK' => ['code' => 'PK', 'dial_code' => '+92',  'length' => 11, 'placeholder' => '03XX-XXXXXXX', 'pattern' => '03\d{9}',      'example' => '0312-3456789'],
        'LK' => ['code' => 'LK', 'dial_code' => '+94',  'length' => 10, 'placeholder' => '07X XXXX XXX', 'pattern' => '07\d{8}',      'example' => '071 2345 678'],
        'NP' => ['code' => 'NP', 'dial_code' => '+977', 'length' => 10, 'placeholder' => '98XXXXXXXX',   'pattern' => '9[78]\d{8}',   'example' => '9812345678'],
        'MM' => ['code' => 'MM', 'dial_code' => '+95',  'length' => 11, 'placeholder' => '09XXXXXXXXX',  'pattern' => '09\d{7,9}',    'example' => '09123456789'],
        'MY' => ['code' => 'MY', 'dial_code' => '+60',  'length' => 11, 'placeholder' => '01X-XXX XXXX', 'pattern' => '01\d{8,9}',    'example' => '012-345 6789'],
        'SG' => ['code' => 'SG', 'dial_code' => '+65',  'length' => 8,  'placeholder' => '9XXX XXXX',    'pattern' => '[89]\d{7}',     'example' => '9123 4567'],
        'AE' => ['code' => 'AE', 'dial_code' => '+971', 'length' => 10, 'placeholder' => '05X XXX XXXX', 'pattern' => '05\d{8}',      'example' => '050 123 4567'],
        'SA' => ['code' => 'SA', 'dial_code' => '+966', 'length' => 10, 'placeholder' => '05X XXX XXXX', 'pattern' => '05\d{8}',      'example' => '050 123 4567'],
        'QA' => ['code' => 'QA', 'dial_code' => '+974', 'length' => 8,  'placeholder' => 'XXXX XXXX',    'pattern' => '[3567]\d{7}',   'example' => '5512 3456'],
        'KW' => ['code' => 'KW', 'dial_code' => '+965', 'length' => 8,  'placeholder' => 'XXXX XXXX',    'pattern' => '[569]\d{7}',    'example' => '5012 3456'],
        'OM' => ['code' => 'OM', 'dial_code' => '+968', 'length' => 8,  'placeholder' => 'XXXX XXXX',    'pattern' => '[79]\d{7}',     'example' => '9212 3456'],
        'BH' => ['code' => 'BH', 'dial_code' => '+973', 'length' => 8,  'placeholder' => 'XXXX XXXX',    'pattern' => '3[2-9]\d{6}',   'example' => '3612 3456'],
        'US' => ['code' => 'US', 'dial_code' => '+1',   'length' => 10, 'placeholder' => '(XXX) XXX-XXXX', 'pattern' => '[2-9]\d{9}',  'example' => '(212) 555-1234'],
        'GB' => ['code' => 'GB', 'dial_code' => '+44',  'length' => 11, 'placeholder' => '07XXX XXXXXX', 'pattern' => '07\d{9}',      'example' => '07911 123456'],
    ];

    /**
     * Get the configured country code, defaulting to BD.
     */
    public static function getCountryCode(): string
    {
        return Setting::get('localization', 'country', 'BD');
    }

    /**
     * Get phone config for a country code.
     */
    public static function getConfig(?string $countryCode = null): array
    {
        $code = $countryCode ?? static::getCountryCode();

        return static::$countries[$code] ?? static::$countries['BD'];
    }

    /**
     * Get all country configs (for JS usage).
     */
    public static function getAllConfigs(): array
    {
        return static::$countries;
    }

    /**
     * The anchored validation regex for a country (single source of truth,
     * shared by the backend rule and the front-end validators).
     */
    public static function pattern(?string $countryCode = null): string
    {
        return '/^' . static::getConfig($countryCode)['pattern'] . '$/';
    }

    /**
     * Whether a phone number is valid for the country. Non-digits (spaces,
     * dashes, a leading country dial code) are stripped before matching.
     */
    public static function isValid(?string $phone, ?string $countryCode = null): bool
    {
        if (empty($phone)) {
            return false;
        }

        $config  = static::getConfig($countryCode);
        $digits  = preg_replace('/\D/', '', $phone);
        $dialled = ltrim((string) $config['dial_code'], '+');

        // Tolerate a leading dial code (e.g. 8801712345678 for BD).
        if ($dialled !== '' && str_starts_with($digits, $dialled)) {
            $national = substr($digits, strlen($dialled));
            if (preg_match(static::pattern($countryCode), '0' . $national)
                || preg_match(static::pattern($countryCode), $national)) {
                return true;
            }
        }

        return (bool) preg_match(static::pattern($countryCode), $digits);
    }

    /**
     * Format a phone number based on country.
     */
    public static function format(?string $phone, ?string $countryCode = null): string
    {
        if (empty($phone)) {
            return '';
        }

        $config = static::getConfig($countryCode);
        $digits = preg_replace('/\D/', '', $phone);

        return match ($config['code']) {
            'BD' => static::formatBD($digits),
            'IN' => static::formatIN($digits),
            'PK' => static::formatPK($digits),
            'US' => static::formatUS($digits),
            'GB' => static::formatGB($digits),
            'AE', 'SA' => static::formatGulf10($digits),
            'QA', 'KW', 'OM', 'BH' => static::formatGulf8($digits),
            'SG' => static::formatSG($digits),
            default => $phone,
        };
    }

    /**
     * Format a phone number in international form, e.g. Bangladesh
     * "01740497649" -> "+8801740497649". Strips any existing dial code or
     * national trunk "0", then prefixes the country dial code. Returns ''
     * for empty input.
     */
    public static function formatIntl(?string $phone, ?string $countryCode = null): string
    {
        if (empty($phone)) {
            return '';
        }

        $config = static::getConfig($countryCode);
        $digits = preg_replace('/\D+/', '', $phone);
        $dial   = ltrim((string) $config['dial_code'], '+');

        // Strip a leading dial code if present, otherwise drop the national
        // trunk "0" so we don't double it after the dial code.
        if ($dial !== '' && str_starts_with($digits, $dial)) {
            $national = substr($digits, strlen($dial));
        } else {
            $national = ltrim($digits, '0');
        }

        return $national !== '' ? '+' . $dial . $national : '';
    }

    protected static function formatBD(string $digits): string
    {
        if (strlen($digits) === 11) {
            return substr($digits, 0, 5) . '-' . substr($digits, 5);
        }

        return $digits;
    }

    protected static function formatIN(string $digits): string
    {
        if (strlen($digits) === 10) {
            return substr($digits, 0, 5) . ' ' . substr($digits, 5);
        }

        return $digits;
    }

    protected static function formatPK(string $digits): string
    {
        if (strlen($digits) === 11) {
            return substr($digits, 0, 4) . '-' . substr($digits, 4);
        }

        return $digits;
    }

    protected static function formatUS(string $digits): string
    {
        if (strlen($digits) === 10) {
            return '(' . substr($digits, 0, 3) . ') ' . substr($digits, 3, 3) . '-' . substr($digits, 6);
        }

        return $digits;
    }

    protected static function formatGB(string $digits): string
    {
        if (strlen($digits) === 11) {
            return substr($digits, 0, 5) . ' ' . substr($digits, 5);
        }

        return $digits;
    }

    protected static function formatGulf10(string $digits): string
    {
        if (strlen($digits) === 10) {
            return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6);
        }

        return $digits;
    }

    protected static function formatGulf8(string $digits): string
    {
        if (strlen($digits) === 8) {
            return substr($digits, 0, 4) . ' ' . substr($digits, 4);
        }

        return $digits;
    }

    protected static function formatSG(string $digits): string
    {
        if (strlen($digits) === 8) {
            return substr($digits, 0, 4) . ' ' . substr($digits, 4);
        }

        return $digits;
    }
}
