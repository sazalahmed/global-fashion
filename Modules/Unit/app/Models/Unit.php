<?php

namespace Modules\Unit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class Unit extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

    protected $fillable = [
        'name',
        'short_name',
        'base_unit_id',
        'conversion_factor',
        'allow_decimal',
        'status',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
        'allow_decimal' => 'boolean',
    ];

    /* ----------------------------------------------------------------
     |  Relationships
     | ---------------------------------------------------------------- */

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    // public function products(): HasMany — Phase 2

    /* ----------------------------------------------------------------
     |  Scopes
     | ---------------------------------------------------------------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeBase(Builder $query): Builder
    {
        return $query->whereNull('base_unit_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /* ----------------------------------------------------------------
     |  Accessors
     | ---------------------------------------------------------------- */

    public function getIsBaseAttribute(): bool
    {
        return $this->base_unit_id === null;
    }

    public function getUnitTypeAttribute(): string
    {
        return $this->is_base ? 'base' : 'sub';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Unit'; }
    public static function getSearchIcon(): string { return 'fa-ruler'; }
    public static function getSearchRoute(): string { return 'units.index'; }
    public static function getSearchPermission(): ?string { return null; }
    public static function getSearchableColumns(): array { return ['name', 'short_name']; }
    public static function getSearchOrder(): int { return 27; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return $this->short_name ?? ''; }
    public function getSearchUrl(): string { return route('units.index') . '?highlight=' . $this->id; }
}
