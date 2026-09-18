<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierProvider extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'base_url',
        'api_key',
        'api_secret',
        'store_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(EcommerceOrder::class);
    }

    /**
     * Whether an API key is stored, checked WITHOUT decrypting. Use this for
     * "is it set?" / masked display so a credential that can't be decrypted
     * (e.g. APP_KEY changed after saving) doesn't throw a DecryptException and
     * 500 the settings page — which would lock the admin out of re-saving it.
     */
    public function hasApiKey(): bool
    {
        return $this->hasReadableCredential('api_key');
    }

    public function hasApiSecret(): bool
    {
        return $this->hasReadableCredential('api_secret');
    }

    /**
     * Drop stored credentials that no longer decrypt, so fresh ones can be
     * assigned over them. Saving otherwise throws: Eloquent's dirty check
     * casts the existing value to compare it against the new one, and casting
     * an encrypted attribute decrypts it — which fails once APP_KEY has moved
     * on. That made unreadable credentials impossible to replace through the
     * app at all.
     *
     * Clears every field in one pass and must run before any new value is
     * assigned: re-syncing afterwards would mark an already-assigned field
     * clean again and save would skip writing it.
     */
    public function forgetUnreadableCredentials(array $fields = ['api_key', 'api_secret']): void
    {
        $attributes = $this->getAttributes();
        $cleared = false;

        foreach ($fields as $field) {
            if (filled($attributes[$field] ?? null) && ! $this->hasReadableCredential($field)) {
                $attributes[$field] = null;
                $cleared = true;
            }
        }

        if ($cleared) {
            $this->setRawAttributes($attributes, sync: true);
        }
    }

    /**
     * Whether a stored credential can actually be read back. Checking the raw
     * column only proves bytes are there — if APP_KEY changed since they were
     * saved they no longer decrypt, and the form would mask them as '********'
     * while the API reported the provider unconfigured. The edit form then
     * skips that mask as "unchanged", so the credentials could never be
     * replaced. Reporting unreadable as absent clears the field and lets a
     * fresh value be saved over it.
     */
    private function hasReadableCredential(string $field): bool
    {
        if (blank($this->getRawOriginal($field))) {
            return false;
        }

        try {
            return filled($this->{$field});
        } catch (DecryptException) {
            return false;
        }
    }
}
