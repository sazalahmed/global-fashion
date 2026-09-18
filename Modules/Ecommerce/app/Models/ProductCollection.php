<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Product\Models\Product;

class ProductCollection extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'type',
        'filter_rules', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'filter_rules' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($collection) {
            if (empty($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
            }
        });
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_product')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
