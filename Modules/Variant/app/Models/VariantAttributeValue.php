<?php

namespace Modules\Variant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VariantAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = ['variant_attribute_id', 'value', 'color_code', 'is_active', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    // ── Relationships ──

    public function attribute()
    {
        return $this->belongsTo(VariantAttribute::class, 'variant_attribute_id');
    }

    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_values');
    }

    // ── Scopes ──

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
