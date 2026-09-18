<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Why a stock adjustment was made — damage, expiry, a stock count correction.
 * Deliberately independent of direction: the adjustment itself carries whether
 * stock went up or down, so the same reason can serve either way.
 */
class AdjustmentReason extends Model
{
    protected $fillable = [
        'name', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relationships ──

    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'reason_id');
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
