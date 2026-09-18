<?php

namespace Modules\Brand\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class Brand extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'website',
        'description',
        'status',
        'sort_order',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
    ];

    /**
     * Scope: only active brands.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: ordered by sort_order then name.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope: only featured brands.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Accessor: check if brand is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Auto-generate unique slug on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Brand $brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }

            $original = $brand->slug;
            $count = 1;

            while (static::withTrashed()->where('slug', $brand->slug)->exists()) {
                $brand->slug = $original . '-' . $count++;
            }
        });
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Brand'; }
    public static function getSearchIcon(): string { return 'fa-copyright'; }
    public static function getSearchRoute(): string { return 'brands.index'; }
    public static function getSearchPermission(): ?string { return 'brands.view'; }
    public static function getSearchableColumns(): array { return ['name']; }
    public static function getSearchOrder(): int { return 26; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return $this->website ?? ''; }
    public function getSearchUrl(): string { return route('brands.index') . '?highlight=' . $this->id; }
}
