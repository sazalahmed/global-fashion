<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Product\Models\Product;

class FlashDeal extends Model
{
    protected $fillable = [
        'title', 'slug', 'banner_image',
        'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($deal) {
            if (empty($deal->slug)) {
                $deal->slug = Str::slug($deal->title);
            }
        });
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'flash_deal_product')
            ->withPivot('discount_type', 'discount_value', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }

    public function getIsRunningAttribute(): bool
    {
        return $this->is_active
            && $this->starts_at <= now()
            && $this->ends_at >= now();
    }
}
