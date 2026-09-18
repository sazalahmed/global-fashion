<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Models\Product;

class SearchController extends Controller
{
    /**
     * Lightweight active-product index for the storefront header live search.
     *
     * The full (small) catalog is returned once; the browser caches it and
     * Fuse.js does the fuzzy, typo-tolerant matching client-side. This keeps
     * each keystroke instant and lets misspelled queries still resolve.
     */
    public function suggest(): JsonResponse
    {
        $placeholder = asset('website/assets/images/product_placeholder.png');

        $products = Product::storefrontVisible()
            ->with(['images', 'category', 'brand'])
            ->withCount(['variants as active_variants_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->map(function (Product $p) use ($placeholder) {
                // Mirror the storefront product card: the dedicated thumbnail
                // (curated override first), then the primary gallery image.
                $image = $p->homepage_thumbnail ?? $p->thumbnail;
                if (! $image && $p->relationLoaded('images') && $p->images->count()) {
                    $primary = $p->images->firstWhere('is_primary', true) ?: $p->images->first();
                    $image = $primary->image_path;
                }

                // Resolve the URL the same way <x-webp> does: when the legacy
                // original (.jpg/.png) was removed by the webp-only migration,
                // serve the generated ".webp" sibling that the card renders.
                $imageUrl = $placeholder;
                if ($image) {
                    $rel = ltrim(str_replace('\\', '/', $image), '/');
                    $imageUrl = is_file(public_path($rel . '.webp'))
                        ? asset($rel . '.webp')
                        : upload_url($image, $placeholder);
                }

                return [
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'sku'             => $p->sku,
                    'slug'            => $p->slug,
                    'category'        => optional($p->category)->name,
                    'brand'           => optional($p->brand)->name,
                    'price'           => (float) $p->sell_price,
                    'price_formatted' => bd_price($p->sell_price),
                    'has_variants'    => $p->isVariable() && (int) ($p->active_variants_count ?? 0) > 0,
                    'image'           => $imageUrl,
                    'url'             => route('storefront.shop.show', $p->slug),
                ];
            });

        return response()->json(['products' => $products]);
    }
}
