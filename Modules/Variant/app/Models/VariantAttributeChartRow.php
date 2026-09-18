<?php

namespace Modules\Variant\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariantAttributeChartRow extends Model
{
    protected $fillable = [
        'variant_attribute_id',
        'label',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'variant_attribute_id' => 'integer',
        'sort_order'           => 'integer',
        'is_active'            => 'boolean',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(VariantAttribute::class, 'variant_attribute_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(VariantAttributeChartValue::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('id');
    }
}
