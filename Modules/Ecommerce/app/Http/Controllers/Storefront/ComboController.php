<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Ecommerce\Support\Seo;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Services\VariantChartService;

class ComboController extends Controller
{
    public function __construct(
        private ComboService $service,
        private VariantChartService $variantChartService,
        private StorefrontService $storefrontService,
    ) {}

    public function index(Request $request): View
    {
        $slug = trim((string) $request->query('category', ''));
        $activeCategory = $slug !== '' ? Category::where('slug', $slug)->first() : null;

        $combos = Combo::active()
            ->when($activeCategory, fn ($q) => $q->whereHas(
                'categories', fn ($c) => $c->where('categories.id', $activeCategory->id)
            ))
            ->with(['items.product.images', 'items.variant'])
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(\Modules\Ecommerce\Models\EcommerceSetting::perPage('per_page_combos', 12))
            ->appends($request->query());

        $title = $activeCategory ? $activeCategory->name.' Combo Packages' : 'Combo Packages';
        $seo = Seo::make()
            ->title($title)
            ->description('Shop curated combo packages — bundled products at a better price.')
            ->breadcrumbs([
                ['name' => 'Home', 'url' => route('storefront.home')],
                ['name' => 'Combo Packages', 'url' => null],
            ])
            ->canonical(route('storefront.combos.index', $activeCategory ? ['category' => $activeCategory->slug] : []));

        return view('ecommerce::storefront.pages.combos.index', [
            'combos'  => $combos,
            'service' => $this->service,
            'seo'     => $seo,
        ]);
    }

    public function show(string $slug): View
    {
        $combo = Combo::active()->where('slug', $slug)
            ->with([
                'items.product.images',
                'items.product.variants.attributeValues.attribute',
                'items.product.sizeChartOverrides',
                'items.variant.attributeValues.attribute',
                'galleryImages',
                'categories',
            ])
            ->firstOrFail();

        // Size chart(s) built from the combo's component products — one per
        // size-like attribute, projected to the sizes those products carry.
        $sizeCharts = $this->buildSizeChartsForCombo($combo);

        // Selectable sizes when the combo lets the customer choose one.
        $sizeOptions = $combo->size_required ? $this->service->availableSizes($combo) : [];

        $seo = Seo::make()
            ->title($combo->name)
            ->description(\Illuminate\Support\Str::limit(strip_tags($combo->description ?? ''), 160)
                ?: ('Buy the '.$combo->name.' combo package online with secure checkout and fast delivery.'))
            ->image($combo->thumbnail)
            ->type('product')
            ->breadcrumbs([
                ['name' => 'Home', 'url' => route('storefront.home')],
                ['name' => 'Combo Packages', 'url' => route('storefront.combos.index')],
                ['name' => $combo->name, 'url' => null],
            ])
            ->canonical(route('storefront.combos.show', $combo->slug));

        // Related section: same-design products (other colors/styles) first,
        // then other combos of that same design, then other products in this
        // combo's categories — mirrors the product page's Related Products
        // (see StorefrontService::getRelatedCatalogItems()).
        $categoryIds = $combo->categories
            ->flatMap(fn ($c) => $this->storefrontService->categoryIdsWithChildren($c->id))
            ->unique()->values()->all();
        $relatedItems = $this->storefrontService->getRelatedCatalogItems($combo, $categoryIds);

        return view('ecommerce::storefront.pages.combos.show', [
            'combo'        => $combo,
            'service'      => $this->service,
            'relatedItems' => $relatedItems,
            'sizeCharts'   => $sizeCharts,
            'sizeOptions'  => $sizeOptions,
            'seo'          => $seo,
        ]);
    }

    /**
     * Lightweight JSON payload the "Order Now" / "Add to Cart" triggers use
     * to resolve a default size client-side for a size-required combo (no
     * picker shown) — mirrors ShopController::variantData() for products.
     */
    public function sizeData(string $slug): JsonResponse
    {
        $combo = Combo::active()->where('slug', $slug)
            ->with(['items.product.variants.attributeValues.attribute'])
            ->first();

        if (! $combo) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json([
            'id'              => $combo->id,
            'name'            => $combo->name,
            'image'           => upload_url($combo->thumbnail, asset('website/assets/images/product_placeholder.png')),
            'sell_price'      => $this->service->summedPrice($combo),
            'effective_price' => $this->service->effectivePrice($combo),
            'in_stock'        => $this->service->isInStock($combo),
            'size_required'   => (bool) $combo->size_required,
            'sizes'           => $combo->size_required ? $this->service->availableSizes($combo) : [],
        ]);
    }

    /**
     * Build a measurement chart for every size-like attribute used by the
     * combo's component products. Mirrors the product page's chart, but the
     * allowed size values are the UNION across all components (combos bundle
     * the same garment in several colours, so they share one size chart), and
     * per-product chart overrides are merged across the components.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildSizeChartsForCombo(Combo $combo): array
    {
        $sizeAttrs = VariantAttribute::where(function ($q) {
            $q->where('name', 'Size')
              ->orWhere('name', 'like', 'Size (%')
              ->orWhere('name', 'like', 'Size(%');
        })->get();

        if ($sizeAttrs->isEmpty()) {
            return [];
        }

        // [row_id][value_id] => override, merged across every component product.
        $overrides = [];
        foreach ($combo->items as $item) {
            if (! $item->product) {
                continue;
            }
            foreach ($item->product->sizeChartOverrides as $o) {
                $overrides[(int) $o->variant_attribute_chart_row_id][(int) $o->variant_attribute_value_id] = $o->value;
            }
        }

        $charts = [];
        foreach ($sizeAttrs as $sizeAttr) {
            $valueIds = [];
            foreach ($combo->items as $item) {
                if (! $item->product) {
                    continue;
                }
                foreach ($item->product->variants as $variant) {
                    if (! $variant->is_active) {
                        continue;
                    }
                    foreach ($variant->attributeValues as $attrValue) {
                        if ((int) $attrValue->variant_attribute_id === (int) $sizeAttr->id) {
                            $valueIds[] = (int) $attrValue->id;
                        }
                    }
                }
            }

            if (empty($valueIds)) {
                continue;
            }

            $chart = $this->variantChartService->buildChartFor(
                $sizeAttr,
                array_values(array_unique($valueIds)),
                $overrides ?: null
            );

            if ($chart !== null) {
                $charts[] = ['attribute' => $sizeAttr->base_name] + $chart;
            }
        }

        return $charts;
    }
}
