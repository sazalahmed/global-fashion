<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    /** Cache key holding the whole settings table, grouped. */
    public const CACHE_KEY = 'settings.all';

    /**
     * Whole settings table cached as a nested map:
     *   [group => [key => ['value' => string, 'type' => string]]]
     *
     * Populated on first read and kept until any setting is written (set()
     * forgets it). This makes get()/getGroup() — called dozens of times per
     * request across the app — hit memory instead of the database (Bug_91).
     *
     * @return array<string, array<string, array{value: string, type: string}>>
     */
    public static function cachedAll(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $map = [];
            foreach (static::all(['group', 'key', 'value', 'type']) as $s) {
                $map[$s->group][$s->key] = ['value' => $s->value, 'type' => $s->type];
            }

            return $map;
        });
    }

    /**
     * Cast a raw stored value to its typed form.
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get a single setting value with type casting (served from cache).
     */
    public static function get(string $group, string $key, $default = null)
    {
        $entry = self::cachedAll()[$group][$key] ?? null;

        if ($entry === null) {
            return $default;
        }

        return self::castValue($entry['value'], $entry['type']);
    }

    /**
     * Set a single setting value.
     */
    public static function set(string $group, string $key, $value, string $type = 'string'): void
    {
        $storeValue = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $storeValue, 'type' => $type]
        );

        // Any write invalidates the whole-table cache so reads pick up the
        // new value on the next request (Bug_91).
        Cache::forget(self::CACHE_KEY);

        if ($group === 'business' && in_array($key, [
            'company_name', 'logo', 'favicon', 'company_phone', 'company_email', 'address',
            'facebook_url', 'instagram_url', 'youtube_url', 'twitter_url', 'linkedin_url', 'tiktok_url', 'whatsapp_number',
        ], true)) {
            Cache::forget('settings.brand');
        }

        if ($group === 'localization' && $key === 'currency_symbol') {
            Cache::forget('settings.currency_symbol');
        }
    }

    /**
     * Get all settings for a group as key-value array (raw values, no type
     * casting), served from the cached settings map (Bug_91).
     */
    public static function getGroup(string $group): array
    {
        $entries = self::cachedAll()[$group] ?? [];

        return array_map(static fn ($entry) => $entry['value'], $entries);
    }

    /**
     * Forget the cached settings map (used by tests/console after bulk writes).
     */
    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
