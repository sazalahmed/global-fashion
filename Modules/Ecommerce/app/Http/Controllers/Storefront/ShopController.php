<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\CampaignService;
use Modules\Ecommerce\Services\ComboService;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Ecommerce\Support\BuildsSeo;
use Modules\Ecommerce\Support\Seo;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Services\VariantChartService;

class ShopController extends Controller
{
    use BuildsSeo;
    public function __construct(
        protected StorefrontService $storefrontService,
        protected VariantChartService $variantChartService,
        protected CampaignService $campaigns,
        protected ComboService $comboService,
    ) {}

    /**
     * Display the shop listing page with filters.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['category', 'brand', 'q', 'sort', 'min_price', 'max_price', 'per_page', 'variants', 'stock_status']);

        // The admin-configured count is the default; the customer can still
        // pick another from the "Show: N" dropdown. Whitelist per_page against
        // that admin value plus the standard options so an attacker can't ask
        // for 100k products and tank the DB.
        $defaultPerPage = \Modules\Ecommerce\Models\EcommerceSetting::perPage('per_page_shop', 12);
        $perPageOptions = collect([$defaultPerPage, 12, 16, 20, 24])->unique()->sort()->values()->all();

        $perPage = (int) ($filters['per_page'] ?? $defaultPerPage);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = $defaultPerPage;
        }

        // Selected variant values by their human label (e.g. "M", "32") so the
        // URL reads ?variants[]=M instead of an opaque id. Resolve the labels to
        // the underlying value IDs the product query actually filters on. (Value
        // labels are unique across the shop's facets — sizes and colours don't
        // overlap — so a label maps cleanly to its value row.)
        $selectedVariants = array_values(array_filter(array_map('strval', (array) $request->input('variants', []))));
        $selectedVariantIds = $selectedVariants
            ? \Modules\Variant\Models\VariantAttributeValue::whereIn('value', $selectedVariants)
                ->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        // Category browse shows products AND combos interleaved — sorted by the
        // chosen sort (or the admin-defined per-category order under "featured").
        // Sorting alone stays on this path so combos keep showing for a category
        // even under price/name/date sorts. Only a true product facet (search,
        // brand, price range, variant, stock) falls back to the product-only
        // faceted query, since combos aren't faceted.
        $isBrowse = empty($filters['q'])
            && empty($filters['brand'])
            && empty($filters['min_price'])
            && empty($filters['max_price'])
            && empty($selectedVariantIds)
            && empty($filters['stock_status']);

        if ($isBrowse) {
            $products = $this->storefrontService->getCatalogListing([
                'category_slug' => $filters['category'] ?? null,
                'sort'          => $filters['sort'] ?? 'featured',
            ], $perPage);
        } else {
            $products = $this->storefrontService->getShopProducts([
                'category_slug'  => $filters['category'] ?? null,
                'brand_slug'     => $filters['brand'] ?? null,
                'q'              => $filters['q'] ?? null,
                'sort'           => $filters['sort'] ?? 'featured',
                // The view's price-range form posts min_price / max_price; the
                // service reads price_min / price_max. Bridge here so query
                // params and DB-facing filter keys stay consistent with both.
                'price_min'      => $filters['min_price'] ?? null,
                'price_max'      => $filters['max_price'] ?? null,
                'variant_values' => $selectedVariantIds,
                'stock_status'   => (array) ($filters['stock_status'] ?? []),
            ], $perPage);
        }

        // Campaign/flash-deal decoration applies to products only; skip any
        // combo entries interleaved into the collection.
        $productItems = $products->getCollection()->reject(fn ($i) => $i instanceof Combo);
        $this->campaigns->decorate($productItems);
        $this->storefrontService->decorateFlashDeals($productItems);

        $categories     = $this->storefrontService->getSidebarCategories();
        $brands         = $this->storefrontService->getSidebarBrands();
        $priceBounds    = $this->storefrontService->getActivePriceBounds();
        $variantFilters = $this->storefrontService->getShopVariantFilters([
            'category_slug' => $filters['category'] ?? null,
        ]);
        $cartItems      = session('cart', []);

        // Combos are interleaved into $products (browse mode) via the combo-card
        // partial, so the shop view still needs the combo service.
        $comboService = $this->comboService;

        $seo = $this->staticPageSeo('shop', [
            'description' => 'Browse our full product catalog. Filter by category and price to find exactly what you need, with secure checkout and fast delivery.',
        ]);
        if (request('q')) {
            $seo->title('Search: ' . request('q'));
        }
        $seo->canonical($this->cleanCanonical($request, route('storefront.shop.index'), ['category', 'page']));
        if (filled($request->query('q'))) {
            $seo->robots('noindex,follow')->canonical(route('storefront.shop.index'));
        }

        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        $items = $productItems->map(fn ($p) => [
            'name'  => $p->name,
            'url'   => route('storefront.shop.show', $p->slug),
            'image' => optional($p->images->first())->image_path ? url($p->images->first()->image_path) : null,
        ])->all();
        $seo->addSchema($schema->collectionPage('Shop', url()->current(), $items));
        if ($seo->breadcrumbs) { $seo->addSchema($schema->breadcrumbList($seo->breadcrumbs)); }

        return view('ecommerce::storefront.pages.shop.index', compact(
            'products',
            'categories',
            'brands',
            'filters',
            'priceBounds',
            'variantFilters',
            'selectedVariants',
            'cartItems',
            'seo',
            'perPage',
            'perPageOptions',
            'comboService'
        ));
    }

    /**
     * Display the flash deals page.
     */
    public function flashDeals(): View
    {
        $products  = $this->storefrontService->getFlashSaleProductsPaginated(
            \Modules\Ecommerce\Models\EcommerceSetting::perPage('per_page_flash_deals', 12)
        );
        $this->campaigns->decorate($products->getCollection());
        $cartItems = session('cart', []);
        // Drives the page countdown — null when no flash deal is running.
        $countdownEndsAt = $this->storefrontService->earliestActiveFlashDealEnd();

        $seo = $this->staticPageSeo('flash-deals', [
            'description' => 'Limited-time flash deals and discounts. Grab your favorite products at the best prices before the offer ends.',
        ]);

        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        $items = $products->getCollection()->map(fn ($p) => [
            'name'  => $p->name,
            'url'   => route('storefront.shop.show', $p->slug),
            'image' => optional($p->images->first())->image_path ? url($p->images->first()->image_path) : null,
        ])->all();
        $seo->addSchema($schema->collectionPage('Flash Deals', url()->current(), $items));
        if ($seo->breadcrumbs) { $seo->addSchema($schema->breadcrumbList($seo->breadcrumbs)); }

        return view('ecommerce::storefront.pages.flash-deals.index', compact(
            'products',
            'cartItems',
            'countdownEndsAt',
            'seo'
        ));
    }

    /**
     * Display a single product detail page.
     */
    public function show(string $slug): \Illuminate\Http\RedirectResponse|View
    {
        $product = $this->storefrontService->findProductBySlug($slug);

        if (!$product) {
            $current = \Modules\Product\Models\Product::currentSlugFor($slug);
            if ($current && $current !== $slug) {
                return redirect()->route('storefront.shop.show', $current, 301);
            }
            abort(404);
        }

        // Related section: same-design products (other colors/styles) first,
        // then combos of that same design, then other products in this
        // product's category — see StorefrontService::getRelatedCatalogItems().
        $categoryIds = $product->category_id
            ? $this->storefrontService->categoryIdsWithChildren($product->category_id)
            : [];
        $relatedItems = $this->storefrontService->getRelatedCatalogItems($product, $categoryIds);

        $this->campaigns->decorate(collect([$product]));
        $this->campaigns->decorate($relatedItems);
        $this->storefrontService->decorateFlashDeals(collect([$product]));
        $this->storefrontService->decorateFlashDeals($relatedItems);
        $effectivePrice  = $this->storefrontService->calculateEffectivePrice($product);
        $cartItems       = session('cart', []);

        // Build the size-chart payloads — one per size-like attribute this
        // product uses (Size, Size (Pant), Size (Shirt), …), each projected to
        // only the values the product actually has. Empty when there's no
        // usable chart.
        $sizeCharts = $this->buildSizeChartsForProduct($product);

        $primaryImg = optional($product->images->firstWhere('is_primary', true) ?? $product->images->first());
        // Generated fallback so a product always emits a meaningful meta
        // description, even when seo_description and description are empty.
        $fallbackDescription = trim(sprintf(
            'Buy %s online%s with secure checkout and fast delivery.',
            $product->name,
            $product->category ? ' in ' . $product->category->name : ''
        ));
        $seo = Seo::make()
            ->title($product->seo_title ?: $product->name)
            ->description($product->seo_description
                ?: (Str::limit(strip_tags($product->description ?? ''), 160) ?: $fallbackDescription))
            ->image($product->seo_image ?: $primaryImg->image_path)
            ->type('product')
            ->breadcrumbs([
                ['name' => 'Home', 'url' => route('storefront.home')],
                ['name' => 'Shop', 'url' => route('storefront.shop.index')],
                ['name' => $product->name, 'url' => null],
            ]);

        $seo->canonical(route('storefront.shop.show', $product->slug));

        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        $canonical = route('storefront.shop.show', $product->slug);
        $seo->addSchema($schema->product($product, $canonical))
            ->addSchema($schema->breadcrumbList($seo->breadcrumbs));

        return view('ecommerce::storefront.pages.shop.show', compact(
            'product',
            'relatedItems',
            'effectivePrice',
            'cartItems',
            'sizeCharts',
            'seo'
        ));
    }

    /**
     * Lightweight JSON payload the "Order Now" / "Add to Cart" triggers use
     * to resolve a default variant client-side (no picker shown). We only
     * fetch the product summary + variant attribute matrix + per-variant
     * prices to keep the response small enough to fire on every click.
     */
    public function variantData(string $slug): JsonResponse
    {
        $product = $this->storefrontService->findProductBySlug($slug);
        if (!$product) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $this->campaigns->decorate(collect([$product]));
        $this->storefrontService->decorateFlashDeals(collect([$product]));
        $effective = $this->storefrontService->calculateEffectivePrice($product);

        // Primary image, falling back to the first image or the placeholder.
        $img = null;
        if ($product->relationLoaded('images') && $product->images->count()) {
            $primary = $product->images->where('is_primary', true)->first()
                ?: $product->images->first();
            $img = $primary?->image_path;
        }
        $img = $img ? asset($img) : asset('website/assets/images/product_placeholder.png');

        $variants = $product->variants->where('is_active', true);

        // A variant is sellable when the product doesn't track stock, allows
        // negative stock (backorder), or the variant has stock on hand. Used to
        // grey out out-of-stock options in the variant modal so a shopper can't
        // pick something that would be rejected at add-to-cart.
        $tracksStock = (bool) $product->track_stock;
        $allowNegative = (bool) $product->allow_negative_stock;
        $variantInStock = fn ($v) => ! $tracksStock || $allowNegative || $v->total_stock > 0;

        $attributes = [];
        $seenAttrValues = [];
        foreach ($variants as $v) {
            foreach ($v->attributeValues as $av) {
                $aId = (int) $av->attribute->id;
                $vId = (int) $av->id;
                if (!isset($attributes[$aId])) {
                    $attributes[$aId] = [
                        'id'           => $aId,
                        'name'         => $av->attribute->base_name,
                        'display_type' => $av->attribute->display_type,
                        'values'       => [],
                    ];
                    $seenAttrValues[$aId] = [];
                }
                if (!in_array($vId, $seenAttrValues[$aId], true)) {
                    $attributes[$aId]['values'][] = [
                        'id'         => $vId,
                        'value'      => $av->value,
                        'color_code' => $av->color_code,
                    ];
                    $seenAttrValues[$aId][] = $vId;
                }
            }
        }

        return response()->json([
            'id'               => $product->id,
            'name'             => $product->name,
            'slug'             => $product->slug,
            'image'            => $img,
            'brand'            => $product->brand?->name,
            'model'            => $product->model,
            'in_stock'         => (bool) $product->is_in_stock,
            'sell_price'       => (float) $product->sell_price,
            'effective_price'  => (float) $effective,
            'has_discount'     => $effective < (float) $product->sell_price,
            'campaign_badge'   => $product->campaign?->badge_label ?: $product->campaign?->name,
            'has_variants'     => $product->isVariable() && $variants->isNotEmpty(),
            'attributes'       => array_values($attributes),
            'variants'         => $variants->map(fn ($v) => [
                'id'               => $v->id,
                'sku'              => $v->sku,
                'sell_price'       => (float) ($v->sell_price ?: $product->sell_price),
                // Per-variant price after the product discount/campaign/flash deal.
                'effective_price'  => $this->storefrontService->calculateEffectivePrice($product, $v),
                'attribute_values' => $v->attributeValues->pluck('id')->map(fn ($x) => (int) $x)->toArray(),
                // Stock-aware flag so the modal can disable out-of-stock options.
                'in_stock'         => $variantInStock($v),
            ])->values(),
        ]);
    }

    /**
     * Build a measurement chart for every size-like variant attribute this
     * product uses. A "size-like" attribute is named exactly "Size" or carries
     * a parenthetical qualifier — "Size (Pant)", "Size (Shirt)" — so a combo
     * product can surface a separate chart per garment.
     *
     * Each chart is projected to only the values the product actually has
     * variants for. Per-product overrides (if any) win over the attribute
     * defaults. Returns an empty array when nothing is renderable.
     */
    private function buildSizeChartsForProduct($product): array
    {
        $sizeAttrs = VariantAttribute::where(function ($q) {
            $q->where('name', 'Size')
              ->orWhere('name', 'like', 'Size (%')
              ->orWhere('name', 'like', 'Size(%');
        })->get();

        if ($sizeAttrs->isEmpty()) {
            return [];
        }

        // [row_id][value_id] => override_value (shared across all charts;
        // buildChartFor only consults the rows/values relevant to its attribute).
        $overrides = [];
        foreach ($product->sizeChartOverrides as $o) {
            $overrides[(int) $o->variant_attribute_chart_row_id][(int) $o->variant_attribute_value_id] = $o->value;
        }

        $charts = [];
        foreach ($sizeAttrs as $sizeAttr) {
            $valueIds = [];
            foreach ($product->variants as $variant) {
                if (!$variant->is_active) continue;
                foreach ($variant->attributeValues as $attrValue) {
                    if ((int) $attrValue->variant_attribute_id === (int) $sizeAttr->id) {
                        $valueIds[] = (int) $attrValue->id;
                    }
                }
            }

            if (empty($valueIds)) continue;

            $chart = $this->variantChartService->buildChartFor(
                $sizeAttr,
                array_values(array_unique($valueIds)),
                $overrides ?: null
            );

            if ($chart !== null) {
                // Shoppers see the clean base name ("Size"), not the admin's
                // bracketed qualifier ("Size (Pant)").
                $charts[] = ['attribute' => $sizeAttr->base_name] + $chart;
            }
        }

        return $charts;
    }
}
