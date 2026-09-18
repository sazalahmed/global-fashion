<?php

namespace Modules\Customer\Models;

use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model implements SearchableInterface
{
    use HasGlobalSearch;

    protected $fillable = [
        'name',
        'description',
        'discount_percentage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'is_active'           => 'boolean',
        ];
    }

    // ── Relationships ──

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ── SearchableInterface Methods ──

    public static function getSearchType(): string { return 'Customer Group'; }
    public static function getSearchIcon(): string { return 'fa-users-rectangle'; }
    public static function getSearchRoute(): string { return 'customer-groups.index'; }
    public static function getSearchPermission(): ?string { return 'customers.view'; }
    public static function getSearchableColumns(): array { return ['name']; }
    public static function getSearchOrder(): int { return 14; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return $this->description ?? ''; }
    public function getSearchUrl(): string { return route('customer-groups.index') . '?highlight=' . $this->id; }
}
