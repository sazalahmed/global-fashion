<?php

namespace Modules\Supplier\Models;

use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierGroup extends Model implements SearchableInterface
{
    use HasGlobalSearch;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ── SearchableInterface Methods ──

    public static function getSearchType(): string { return 'Supplier Group'; }
    public static function getSearchIcon(): string { return 'fa-layer-group'; }
    public static function getSearchRoute(): string { return 'supplier-groups.index'; }
    public static function getSearchPermission(): ?string { return 'suppliers.view'; }
    public static function getSearchableColumns(): array { return ['name']; }
    public static function getSearchOrder(): int { return 16; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return $this->description ?? ''; }
    public function getSearchUrl(): string { return route('supplier-groups.index') . '?highlight=' . $this->id; }
}
