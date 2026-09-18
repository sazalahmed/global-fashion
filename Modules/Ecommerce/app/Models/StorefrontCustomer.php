<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class StorefrontCustomer extends Authenticatable
{
    use SoftDeletes, Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'google_id',
        'password',
        'division',
        'district',
        'upazila',
        'address',
        'shipping_address',
        'photo',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /* -------------------------------------------------------
     * Relationships
     * ----------------------------------------------------- */

    public function orders(): HasMany
    {
        return $this->hasMany(EcommerceOrder::class, 'customer_id');
    }

    /* -------------------------------------------------------
     * Scopes
     * ----------------------------------------------------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
