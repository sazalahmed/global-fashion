<?php

namespace Modules\Customer\Models;

use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model implements SearchableInterface
{
    use HasGlobalSearch;
    protected $fillable = ['name', 'parent_id', 'level', 'delivery_charge', 'is_active'];

    protected function casts(): array
    {
        return [
            'delivery_charge' => 'decimal:2',
            'is_active'       => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function getFullPathAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_path . ' > ' . $this->name;
        }
        return $this->name;
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Area'; }
    public static function getSearchIcon(): string { return 'fa-map-marker-alt'; }
    public static function getSearchRoute(): string { return 'customer-areas.index'; }
    public static function getSearchPermission(): ?string { return 'customers.view'; }
    public static function getSearchableColumns(): array { return ['name']; }
    public static function getSearchOrder(): int { return 63; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return 'Level ' . ($this->level ?? ''); }
    public function getSearchUrl(): string { return route('customer-areas.index') . '?highlight=' . $this->id; }
}
