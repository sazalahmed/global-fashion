<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class EcommerceSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Resolve a storefront "items per page" setting, clamped to a safe range.
     * Falls back to $default when unset/non-numeric, and hard-caps at 1..100 so
     * a stray DB value can never trigger an enormous, DB-tanking page query.
     */
    public static function perPage(string $key, int $default, int $min = 1, int $max = 100): int
    {
        $value = (int) static::get($key, $default);

        return max($min, min($max, $value > 0 ? $value : $default));
    }
}
