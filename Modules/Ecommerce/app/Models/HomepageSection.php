<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Product\Models\Product;
use Modules\Ecommerce\Models\Combo;

class HomepageSection extends Model
{
    /**
     * Section types that render a row of products and therefore support an
     * admin-curated product list (with per-product thumbnail override).
     * When a section of one of these types has curated products, the
     * storefront uses them instead of the automatic query.
     */
    public const PRODUCT_SECTION_TYPES = [
        'new_arrivals', 'best_selling', 'trending', 'special_brand', 'favourite',
    ];

    protected $fillable = [
        'section_type', 'title', 'subtitle',
        'sort_order', 'is_active', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Admin-curated products for this section, ordered, carrying an optional
     * per-product `thumbnail` override on the pivot.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'homepage_section_products')
            ->withPivot('thumbnail', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Admin-curated combo packages for this section, ordered by the pivot.
     */
    public function combos(): BelongsToMany
    {
        return $this->belongsToMany(Combo::class, 'homepage_section_combos')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Whether this section renders a product row that supports curation.
     */
    public function isProductSection(): bool
    {
        return in_array($this->section_type, self::PRODUCT_SECTION_TYPES, true);
    }

    /**
     * Whether this section renders a combo-package row (admin-curated combos).
     */
    public function isComboSection(): bool
    {
        return $this->section_type === 'combos';
    }

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }
}
