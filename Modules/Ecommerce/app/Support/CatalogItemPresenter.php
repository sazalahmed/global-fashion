<?php

namespace Modules\Ecommerce\Support;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Product\Models\Product;

/**
 * Normalizes a Product or a Combo into the handful of display fields the
 * bespoke homepage tiles (best_selling, special_brand) need, so those
 * layouts can render either kind of catalog item without knowing which one
 * they have. The simple grid sections use product-card/combo-card directly
 * instead — this presenter exists only for the two hand-built layouts that
 * previously assumed a Product and called its methods straight from the
 * template (`$product->displayPrice()`, `$product->category`, …).
 */
class CatalogItemPresenter
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly string $name,
        public readonly string $url,
        public readonly ?string $image,
        public readonly float $sellPrice,
        public readonly float $effectivePrice,
        public readonly bool $hasDiscount,
        public readonly float $discountPercent,
        public readonly ?string $campaignBadge,
        public readonly ?string $categoryName,
        public readonly ?string $categorySlug,
    ) {
    }

    public static function for($item): self
    {
        return $item instanceof Combo ? self::forCombo($item) : self::forProduct($item);
    }

    private static function forProduct(Product $product): self
    {
        $image = $product->homepage_thumbnail ?? $product->thumbnail;
        if (!$image && $product->relationLoaded('images') && $product->images->count()) {
            $primary = $product->images->where('is_primary', true)->first();
            $image = $primary ? $primary->image_path : $product->images->first()->image_path;
        }

        $price = $product->displayPrice();
        $category = $product->relationLoaded('category') ? $product->category : $product->category()->first();

        return new self(
            type: 'product',
            id: $product->id,
            name: $product->name,
            url: route('storefront.shop.show', $product->slug),
            image: $image,
            sellPrice: (float) $price->sell,
            effectivePrice: (float) $price->effective,
            hasDiscount: (bool) $price->has_discount,
            discountPercent: (float) $price->discount_percent,
            campaignBadge: $price->campaign_badge,
            categoryName: $category?->name,
            categorySlug: $category?->slug,
        );
    }

    private static function forCombo(Combo $combo): self
    {
        $service = app(ComboService::class);
        $summed = $service->summedPrice($combo);
        $effective = $service->effectivePrice($combo);
        $hasSaving = $summed > $effective;
        $category = $combo->relationLoaded('categories') ? $combo->categories->first() : $combo->categories()->first();

        return new self(
            type: 'combo',
            id: $combo->id,
            name: $combo->name,
            url: route('storefront.combos.show', $combo->slug),
            image: $combo->thumbnail,
            sellPrice: $summed,
            effectivePrice: $effective,
            hasDiscount: $hasSaving,
            discountPercent: $hasSaving && $summed > 0 ? round((($summed - $effective) / $summed) * 100) : 0.0,
            campaignBadge: null,
            categoryName: $category?->name,
            categorySlug: $category?->slug,
        );
    }
}
