<?php

namespace Modules\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Modules\Ecommerce\Support\HasSlugHistory;

class Category extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch, HasSlugHistory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'image',
        'description',
        'sort_order',
        'status',
        'show_in_menu',
        'show_in_top',
        'meta_title',
        'meta_description',
        'meta_image',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'parent_id' => 'integer',
        'show_in_menu' => 'boolean',
        'show_in_top' => 'boolean',
    ];

    // ───────────────────────────────────────────────
    // Relationships
    // ───────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function combos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\Modules\Ecommerce\Models\Combo::class, 'category_combo');
    }

    /**
     * Given a set of selected category IDs, return the most specific one —
     * the deepest in the tree. Used to pick a product's primary category:
     * when a child is chosen its ancestors are auto-selected too, so the
     * leaf the user actually wanted (e.g. "Formal Shirt") must win over its
     * parent (e.g. "Full Sleeves Shirt"). Ties keep the first given ID.
     *
     * @param  array<int, int|string>  $ids
     */
    public static function mostSpecificId(array $ids): ?int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn ($v) => $v > 0));
        if (count($ids) <= 1) {
            return $ids[0] ?? null;
        }

        // id => parent_id for the whole (small) category table — one query.
        $parents = static::query()->pluck('parent_id', 'id');

        $depthOf = function (int $id) use ($parents): int {
            $depth = 0;
            $guard = 0;
            while (($p = $parents[$id] ?? null) !== null && $guard++ < 100) {
                $id = (int) $p;
                $depth++;
            }
            return $depth;
        };

        $best = $ids[0];
        $bestDepth = $depthOf($best);
        foreach ($ids as $id) {
            $d = $depthOf($id);
            if ($d > $bestDepth) {
                $best = $id;
                $bestDepth = $d;
            }
        }

        return $best;
    }

    /**
     * Self-recursive children for arbitrary-depth trees. The eager-load
     * chain (children → children → …) bottoms out automatically when a
     * level returns no rows. Active-scope is reapplied at every level so
     * inactive subtrees are pruned, not just the parent row.
     */
    public function recursiveChildren(): HasMany
    {
        return $this->children()
            ->active()
            ->ordered()
            ->with(['recursiveChildren' => fn ($q) => $q->active()->ordered()]);
    }

    // ───────────────────────────────────────────────
    // Scopes
    // ───────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ───────────────────────────────────────────────
    // Accessors
    // ───────────────────────────────────────────────

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    // ───────────────────────────────────────────────
    // Storefront visibility
    // ───────────────────────────────────────────────

    /** Request-lifetime memo for hiddenStorefrontIds(). */
    protected static ?array $hiddenStorefrontIds = null;

    /**
     * IDs of categories that must NOT surface products on the storefront:
     * a category is hidden when it is inactive itself OR sits anywhere under
     * an inactive ancestor (mirrors how the nav prunes inactive subtrees).
     *
     * Returned as a "hidden" list (rather than a "visible" one) so callers can
     * `whereNotIn('category_id', …)` — keeping uncategorised products and
     * products whose category was deleted visible, and only pruning the
     * inactive subtree. Memoised per request; the tree is small.
     */
    public static function hiddenStorefrontIds(): array
    {
        if (static::$hiddenStorefrontIds !== null) {
            return static::$hiddenStorefrontIds;
        }

        $all  = static::query()->get(['id', 'parent_id', 'status']);
        $byId = $all->keyBy('id');
        $memo = [];

        $isHidden = function ($cat) use (&$isHidden, $byId, &$memo) {
            if (array_key_exists($cat->id, $memo)) {
                return $memo[$cat->id];
            }
            if ($cat->status !== 'active') {
                return $memo[$cat->id] = true;
            }
            if (! $cat->parent_id) {
                return $memo[$cat->id] = false;
            }
            $parent = $byId->get($cat->parent_id);
            // A dangling parent (deleted ancestor) is treated as root-visible.
            return $memo[$cat->id] = ($parent ? $isHidden($parent) : false);
        };

        return static::$hiddenStorefrontIds = $all
            ->filter(fn ($c) => $isHidden($c))
            ->pluck('id')
            ->all();
    }

    /**
     * Clear the per-request memo so a subsequent hiddenStorefrontIds() call
     * recomputes — needed when category statuses change within one process.
     */
    public static function flushHiddenStorefrontMemo(): void
    {
        static::$hiddenStorefrontIds = null;
    }

    // ───────────────────────────────────────────────
    // Boot — auto-generate unique slug
    // ───────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }

            $original = $category->slug;
            $count = 1;

            while (static::withTrashed()->where('slug', $category->slug)->exists()) {
                $category->slug = $original . '-' . $count++;
            }
        });
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Category'; }
    public static function getSearchIcon(): string { return 'fa-tags'; }
    public static function getSearchRoute(): string { return 'categories.index'; }
    public static function getSearchPermission(): ?string { return 'categories.view'; }
    public static function getSearchableColumns(): array { return ['name']; }
    public static function getSearchOrder(): int { return 25; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return $this->description ?? ''; }
    public function getSearchUrl(): string { return route('categories.index') . '?highlight=' . $this->id; }
}
