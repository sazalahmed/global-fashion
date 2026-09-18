<?php

namespace Modules\AdSpend\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdPlatform extends Model
{
    protected $fillable = [
        'name', 'icon', 'color', 'is_active', 'total_spent', 'sort_order',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'total_spent' => 'decimal:2',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class, 'ad_platform_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
