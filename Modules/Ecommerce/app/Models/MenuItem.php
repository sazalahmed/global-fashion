<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Concerns\HasMenuRoutes;

class MenuItem extends Model
{
    use HasMenuRoutes;

    public const TYPES = ['route', 'category', 'page', 'url', 'heading', 'categories_dropdown', 'widget'];

    public const WIDGETS = ['cart', 'wishlist', 'compare', 'account', 'search'];

    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'type', 'value', 'target',
        'icon', 'css_class', 'visibility', 'settings', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'settings'   => 'array',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** In-request cache of category id → slug, to avoid N+1 in resolveUrl(). */
    protected static array $categorySlugCache = [];

    /** In-request cache of page id → slug, to avoid N+1 in resolveUrl(). */
    protected static array $pageSlugCache = [];

    protected static function booted(): void
    {
        // Deleting an item removes its whole subtree (no orphans). Fires per
        // model so nested hooks cascade; parent_id FK is nullOnDelete as a
        // safety net only.
        static::deleting(function (MenuItem $item) {
            $item->children->each->delete();
        });
    }

    // ── Relationships ──

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function recursiveChildren(): HasMany
    {
        return $this->children()->with('recursiveChildren');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // ── Resolution helpers ──

    /**
     * Map type + value → href. Link types only; heading / categories_dropdown
     * / widget return null (those are rendered specially by the views).
     */
    public function resolveUrl(): ?string
    {
        return match ($this->type) {
            'route' => (in_array($this->value, self::menuRouteNames(), true) && Route::has($this->value))
                ? route($this->value)
                : '#',
            'category' => ($slug = static::categorySlug((int) $this->value))
                ? route('storefront.category.show', $slug)
                : '#',
            'page' => ($slug = static::pageSlug((int) $this->value))
                ? route('storefront.page.show', $slug)
                : '#',
            'url' => $this->value ?: '#',
            default => null, // heading, categories_dropdown, widget
        };
    }

    public function isVisibleTo(?Authenticatable $customer): bool
    {
        return match ($this->visibility) {
            'guest' => $customer === null,
            'auth'  => $customer !== null,
            default => true, // 'all'
        };
    }

    /** Live badge count for cart/wishlist/compare widgets; null otherwise. */
    public function badgeCount(): ?int
    {
        if ($this->type !== 'widget') {
            return null;
        }

        return match ($this->value) {
            'cart'     => count((array) session('cart', [])),
            'wishlist' => count((array) session('wishlist', [])) + count((array) session('wishlist_combos', [])),
            'compare'  => count((array) session('compare', [])) + count((array) session('compare_combos', [])),
            default    => null, // account, search
        };
    }

    protected static function categorySlug(int $id): ?string
    {
        if (! array_key_exists($id, static::$categorySlugCache)) {
            static::$categorySlugCache[$id] = Category::whereKey($id)->value('slug');
        }

        return static::$categorySlugCache[$id];
    }

    protected static function pageSlug(int $id): ?string
    {
        if (! array_key_exists($id, static::$pageSlugCache)) {
            static::$pageSlugCache[$id] = Page::published()->whereKey($id)->value('slug');
        }

        return static::$pageSlugCache[$id];
    }
}
