<?php

use Illuminate\Support\Facades\Cache;

if (! function_exists('currency_symbol')) {
    /**
     * The currency symbol configured in admin (Settings → Localization).
     * Defaults to the Bangladeshi Taka sign. Cached for the request/hour.
     */
    function currency_symbol(): string
    {
        try {
            return Cache::remember('settings.currency_symbol', 3600, function () {
                $value = \Modules\Setting\Models\Setting::get('localization', 'currency_symbol', '৳');

                return ($value === null || $value === '') ? '৳' : (string) $value;
            });
        } catch (\Throwable $e) {
            return '৳';
        }
    }
}

if (! function_exists('trim_decimals')) {
    /**
     * Resolve the decimal precision for an amount: whole numbers get 0
     * (so the redundant ".00" is dropped) and fractional numbers get 2.
     * Pass an explicit $decimals to force a fixed precision.
     *
     * Shared by money() and num() so the "integer-when-whole" rule lives in
     * exactly one place.
     */
    function trim_decimals(float $amount, ?int $decimals = null): int
    {
        if ($decimals !== null) {
            return $decimals;
        }

        // Decide on the value rounded to 2dp so floating-point residue
        // (e.g. 1e-13 from subtraction) is treated as a whole number, not ".00".
        return (fmod(round($amount, 2), 1.0) === 0.0) ? 0 : 2;
    }
}

if (! function_exists('money')) {
    /**
     * Format an amount with the configured currency symbol.
     *
     * Bug_18: when $decimals is omitted, whole amounts drop the decimals
     * (".00"/".000") and only fractional amounts keep two — e.g.
     * money(8005) => "৳ 8,005", money(8005.5) => "৳ 8,005.50". Pass an explicit
     * $decimals to force a fixed precision (e.g. invoices/exports).
     */
    function money($amount, ?int $decimals = null): string
    {
        $amount   = (float) $amount;
        $decimals = trim_decimals($amount, $decimals);

        return currency_symbol() . ' ' . number_format($amount, $decimals);
    }
}

if (! function_exists('num')) {
    /**
     * Format a bare number (no currency symbol) with the same
     * "integer-when-whole, else two decimals" rule as money().
     *
     * Use for in-UI numeric cells that are NOT preceded by a currency symbol
     * — quantities, unit prices in tables, percentages, etc. — e.g.
     * num(1365) => "1,365", num(1365.5) => "1,365.50", num(2.5) => "2.50".
     *
     * Do NOT use for <input> values or data-* attributes consumed by JS: the
     * thousands separator breaks numeric parsing. Output the raw value there.
     */
    function num($amount, ?int $decimals = null): string
    {
        $amount   = (float) $amount;
        $decimals = trim_decimals($amount, $decimals);

        return number_format($amount, $decimals);
    }
}

if (! function_exists('num_input')) {
    /**
     * Plain numeric string suitable for <input> values and data-* attributes:
     * NO thousands separator, and a whole number drops its ".00" so it renders
     * as an integer (280.00 => "280", 280.50 => "280.50"). Returns '' for null
     * so empty fields stay empty rather than showing "0".
     */
    function num_input($amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $amount   = (float) $amount;
        $decimals = trim_decimals($amount);

        return number_format($amount, $decimals, '.', '');
    }
}

if (! function_exists('storefront_price_separator')) {
    /**
     * Whether storefront prices should render the thousands separator.
     * Toggled by the admin in Ecommerce → Settings. Defaults to OFF.
     * Cached for the hour; the cache is cleared when settings are saved.
     */
    function storefront_price_separator(): bool
    {
        try {
            return Cache::remember('ecommerce.price_thousands_separator', 3600, function () {
                return \Modules\Ecommerce\Models\EcommerceSetting::get('price_thousands_separator', '0') === '1';
            });
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('bd_number_group')) {
    /**
     * Group an already-rounded number string using the Bangladesh lakh
     * system: the last three integer digits stay together, then digits are
     * grouped in pairs (e.g. 1234567 => "12,34,567").
     */
    function bd_number_group(float $amount, int $decimals): string
    {
        $negative = $amount < 0;
        $raw      = number_format(abs($amount), $decimals, '.', '');
        [$int, $frac] = array_pad(explode('.', $raw), 2, '');

        if (strlen($int) > 3) {
            $last3 = substr($int, -3);
            $rest  = substr($int, 0, -3);
            // Insert a comma before every pair of digits in the remaining head.
            $rest  = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $int   = $rest . ',' . $last3;
        }

        return ($negative ? '-' : '') . $int . ($frac !== '' ? '.' . $frac : '');
    }
}

if (! function_exists('bd_price')) {
    /**
     * Format a storefront price as a bare number (no currency symbol):
     *  - whole amounts drop the decimals (1500), fractional keep two (1500.50)
     *  - the BD lakh thousands separator is applied only when the admin has
     *    enabled it (see storefront_price_separator()).
     *
     * Markup keeps its own "BDT" prefix so the numeric part can be swapped by
     * JS (e.g. cart line totals) using the matching window.bdPrice() helper.
     */
    function bd_price($amount): string
    {
        $amount   = (float) $amount;
        $decimals = (fmod($amount, 1.0) === 0.0) ? 0 : 2;

        return storefront_price_separator()
            ? bd_number_group($amount, $decimals)
            : number_format($amount, $decimals, '.', '');
    }
}
