<?php

namespace Modules\Ecommerce\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'starts_at', 'ends_at',
        'scope', 'discount_type', 'discount_value',
        'priority', 'badge_label', 'is_active',
    ];

    protected $casts = [
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'discount_value' => 'decimal:2',
        'priority'       => 'integer',
        'is_active'      => 'boolean',
    ];

    public const SCOPES = ['all', 'categories', 'products'];
    public const TYPES  = ['percentage', 'flat'];

    /**
     * How specific this campaign is. Higher = more specific (beats lower
     * scopes when picking the winner for a given product).
     */
    public const SCOPE_RANK = [
        'all'        => 1,
        'categories' => 2,
        'products'   => 3,
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'campaign_categories');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_products');
    }

    public function scopeActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    /**
     * Apply this campaign to a base price; returns the discounted price.
     * Percentage caps at 100%; flat amount can't push the price below 0.
     */
    public function applyTo(float $basePrice): float
    {
        if ($basePrice <= 0) {
            return $basePrice;
        }

        $value = (float) $this->discount_value;

        if ($this->discount_type === 'percentage') {
            $value = min($value, 100);
            return round($basePrice * (1 - $value / 100), 2);
        }

        return round(max(0, $basePrice - $value), 2);
    }

    /**
     * Discount amount in BDT (not percentage). Used to compare two
     * same-scope campaigns when picking the winner.
     */
    public function discountAmountFor(float $basePrice): float
    {
        return round($basePrice - $this->applyTo($basePrice), 2);
    }

    public function scopeRank(): int
    {
        return self::SCOPE_RANK[$this->scope] ?? 0;
    }

    /**
     * Display percent for a given base price — useful for the "-20%" badge
     * regardless of whether the discount is flat or percent.
     */
    public function percentageFor(float $basePrice): int
    {
        if ($basePrice <= 0) {
            return 0;
        }
        return (int) round($this->discountAmountFor($basePrice) / $basePrice * 100);
    }
}
