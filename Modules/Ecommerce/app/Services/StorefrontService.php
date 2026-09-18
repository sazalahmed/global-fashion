<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\Coupon;
use Modules\Ecommerce\Models\FlashDeal;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Models\EcommerceOrderItem;
use Modules\Ecommerce\Models\HomepageSection;
use Modules\Ecommerce\Models\ShippingZone;
use Modules\Product\Models\Product;
use Modules\Sale\Services\SaleService;

class StorefrontService
{
    public function __construct(
        protected SaleService $saleService,
        protected CampaignService $campaigns,
        protected \Modules\Customer\Services\CustomerService $customerService,
    ) {}

    // ── Homepage Data Assembly ──

    /**
     * Assemble all data needed for the homepage based on active sections.
     */
    public function getHomepageData(): array
    {
        $cms = app(ContentManagementService::class);

        $sections = HomepageSection::active()->ordered()->get();
        $data = ['sections' => $sections];

        foreach ($sections as $section) {
            $limit = $section->getSetting('items_count', 12);

            match ($section->section_type) {
                'hero_slider'    => $data['heroBanners'] = $cms->getActiveBannersByPosition('hero'),
                'features'       => null,
                'flash_deals'    => $data['flashDeal'] = $cms->getActiveFlashDeal(),
                'categories'     => $data['topCategories'] = $this->getTopCategories($limit),
                'promo_banners'  => $data['promoBanners'] = $cms->getActiveBannersByPosition('promo_large'),
                'new_arrivals'   => $data['newArrivals'] = $this->curatedSectionItems($section) ?? $this->tagAsProducts($this->getNewArrivals($limit)),
                'best_selling'   => $data['bestSelling'] = $this->curatedSectionItems($section) ?? $this->tagAsProducts($this->getBestSellingProducts($limit)),
                'brands'         => $data['brands'] = $this->getActiveBrands($limit),
                'blog'           => $data['blogPosts'] = $cms->getHomepagePosts($limit),
                'trending'       => $data['trendingProducts'] = $this->curatedSectionItems($section) ?? $this->tagAsProducts($this->getTrendingProducts($limit)),
                'special_brand'  => $data['specialProducts'] = $this->curatedSectionItems($section) ?? $this->tagAsProducts($this->getSpecialBrandProducts($limit)),
                'favourite'      => $data['favouriteProducts'] = $this->curatedSectionItems($section) ?? $this->tagAsProducts($this->getFavouriteProducts($limit)),
                'newsletter'     => null,
                default          => null,
            };
        }

        // The hero slider always renders a right-hand promotional box. Feed it
        // from the "Promotional Large" banners so it's backend-managed instead
        // of the hardcoded placeholder. A dedicated promo_banners section, if
        // present, will already have set this — don't clobber it.
        if (! array_key_exists('promoBanners', $data)) {
            $data['promoBanners'] = $cms->getActiveBannersByPosition('promo_large');
        }

        // Provide fallback data if sections are empty. Must mirror every
        // partial the homepage view @includes in its fallback branch —
        // hero slider, flash deals and blog included, otherwise those
        // sections silently render empty (e.g. an active flash deal not
        // showing because $flashDeal was never set).
        if ($sections->isEmpty()) {
            $data['heroBanners'] = $cms->getActiveBannersByPosition('hero');
            $data['flashDeal'] = $cms->getActiveFlashDeal();
            $data['topCategories'] = $this->getTopCategories(10);
            $data['newArrivals'] = $this->tagAsProducts($this->getNewArrivals(10));
            $data['bestSelling'] = $this->tagAsProducts($this->getBestSellingProducts(8));
            $data['brands'] = $this->getActiveBrands(12);
            $data['trendingProducts'] = $this->tagAsProducts($this->getTrendingProducts(10));
            $data['specialProducts'] = $this->tagAsProducts($this->getSpecialBrandProducts(8));
            $data['favouriteProducts'] = $this->tagAsProducts($this->getFavouriteProducts(6));
            $data['blogPosts'] = $cms->getPublishedPosts(6);
        }

        // Decorate every product collection we just loaded so views can render
        // campaign + flash-deal pricing without re-resolving in templates.
        // Order matters: campaigns first so their decoration is in place,
        // then decorateFlashDeals can compare and beat them where cheaper.
        foreach (['newArrivals', 'bestSelling', 'trendingProducts', 'specialProducts', 'favouriteProducts'] as $key) {
            if (!empty($data[$key])) {
                $this->campaigns->decorate($data[$key]);
                $this->decorateFlashDeals($data[$key]);
            }
        }

        // The home flash-deals partial also receives the deal's products
        // collection — decorate those too so the cards show pivot prices.
        if (! empty($data['flashDeal']?->products)) {
            $this->decorateFlashDeals($data['flashDeal']->products);
        }

        return $data;
    }

    /**
     * Curated products AND combos attached to a homepage section, merged
     * into one collection, or null when the admin has not picked anything
     * (so the caller falls back to the automatic query). Each item carries
     * a `catalog_type` ('product'|'combo') so the homepage partials know
     * which card to render; products also carry their per-section
     * `homepage_thumbnail` override.
     *
     * Products and combos are picked from a single ordered list in the
     * admin (see ContentController::homepageSectionProductsUpdate), so the
     * two pivots' sort_order values share one continuous sequence — sorting
     * the merged collection by it reconstructs the admin's chosen order.
     */
    private function curatedSectionItems(HomepageSection $section): ?Collection
    {
        $hiddenCategoryIds = Category::hiddenStorefrontIds();
        $section->loadMissing([
            'products' => function ($q) use ($hiddenCategoryIds) {
                $q->where('products.status', 'active')
                  ->when($hiddenCategoryIds, fn ($qq) => $qq->whereNotIn('products.category_id', $hiddenCategoryIds))
                  ->with(['images', 'category', 'approvedReviews']);
            },
            'combos' => fn ($q) => $q->active()->with(['items.product.images', 'items.variant', 'categories']),
        ]);

        $products = $section->products->each(function ($product) {
            $product->homepage_thumbnail = $product->pivot->thumbnail;
            $product->catalog_type = 'product';
            $product->catalog_sort = (int) $product->pivot->sort_order;
        });

        $combos = $section->combos->each(function ($combo) {
            $combo->catalog_type = 'combo';
            $combo->catalog_sort = (int) $combo->pivot->sort_order;
        });

        $merged = $products->values()->concat($combos->values());
        if ($merged->isEmpty()) {
            return null;
        }

        return $merged->sortBy('catalog_sort')->values();
    }

    /** Tag an automatic (uncurated) product collection for the homepage partials. */
    private function tagAsProducts(Collection $products): Collection
    {
        $products->each(fn ($p) => $p->catalog_type = 'product');

        return $products;
    }

    /**
     * Get best selling products based on order item quantities.
     */
    public function getBestSellingProducts(int $limit = 8): Collection
    {
        $productIds = EcommerceOrderItem::select('product_id')
            ->selectRaw('SUM(quantity) as total_sold')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return $this->getFeaturedProducts($limit);
        }

        return Product::storefrontVisible()
            ->whereIn('id', $productIds)
            ->with(['images', 'category', 'approvedReviews'])
            ->get()
            ->sortBy(fn ($p) => $productIds->search($p->id))
            ->values();
    }

    // ── Product Queries ──

    /**
     * Get latest active products.
     */
    public function getNewArrivals(int $limit = 10): Collection
    {
        return Product::storefrontVisible()
            ->with(['images', 'category', 'approvedReviews'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get flash sale products (products with active discounts).
     */
    public function getFlashSaleProducts(int $limit = 12): Collection
    {
        return Product::storefrontVisible()
            ->where(function ($q) {
                $q->where('discount_type', 'percentage')
                  ->orWhere('discount_type', 'fixed');
            })
            ->where('discount_value', '>', 0)
            ->with(['images', 'category', 'approvedReviews'])
            ->orderByDesc('discount_value')
            ->limit($limit)
            ->get();
    }

    /**
     * Paginated products from every currently-running FlashDeal, with each
     * product's per-deal pivot discount (flash_deal_product.discount_type
     * / discount_value) overlaid onto the in-memory Product instance so
     * Product::displayPrice() picks it up automatically — no extra
     * branching in the views.
     */
    public function getFlashSaleProductsPaginated(int $perPage = 12): LengthAwarePaginator
    {
        $activeDealIds = FlashDeal::active()->pluck('id');

        if ($activeDealIds->isEmpty()) {
            return Product::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        $paginator = Product::storefrontVisible()
            ->with(['images', 'category', 'brand', 'approvedReviews'])
            ->whereHas('flashDeals', fn ($q) => $q->whereIn('flash_deals.id', $activeDealIds))
            ->paginate($perPage)
            ->withQueryString();

        // Shared decorator handles the per-product overlay.
        $this->decorateFlashDeals($paginator->getCollection());

        return $paginator;
    }

    /**
     * Earliest `ends_at` across all currently-running flash deals — used
     * by the flash-deals page countdown. Returns null when no deal is
     * live, so the view can hide the timer.
     */
    public function earliestActiveFlashDealEnd(): ?\Carbon\Carbon
    {
        return FlashDeal::active()->orderBy('ends_at')->value('ends_at');
    }

    /**
     * Get featured products (highest priced active products).
     */
    public function getFeaturedProducts(int $limit = 12): Collection
    {
        return Product::storefrontVisible()
            ->with(['images', 'category', 'approvedReviews', 'variants.attributeValues.attribute'])
            ->orderByDesc('sell_price')
            ->limit($limit)
            ->get();
    }

    /**
     * Get paginated shop products with filters.
     */
    /**
     * Interleaved products + combos for category browsing. Under the "featured"
     * sort, items follow the admin-defined per-category positions (items without
     * a position row fall after positioned ones, newest first) — matching the
     * admin unified list ordering exactly. Any other sort (price/name/date)
     * orders the whole merged set by that key instead.
     *
     * Combos are included only when a category is selected; the unfiltered /shop
     * listing stays product-only. Faceted/search listings (brand, price range,
     * variant, stock, keyword) stay product-only via getShopProducts — combos
     * aren't faceted.
     */
    public function getCatalogListing(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $categoryId = null;
        $subtreeIds = null;
        if (! empty($filters['category_slug'])) {
            $category = Category::where('slug', $filters['category_slug'])->first();
            if ($category) {
                $categoryId = $category->id;
                $subtreeIds = $this->getCategoryAndChildrenIds($category);
            }
        }

        $products = Product::storefrontVisible()
            ->with(['images', 'category', 'brand', 'approvedReviews'])
            ->when($subtreeIds, fn ($q) => $q->whereIn('category_id', $subtreeIds))
            ->get()->each(fn ($p) => $p->catalog_type = 'product');

        // Combos only surface when browsing a specific category (e.g.
        // /shop?category=formal-shirt). The unfiltered /shop listing stays
        // product-only so combos don't clutter the full catalog.
        $combos = $categoryId === null
            ? collect()
            : \Modules\Ecommerce\Models\Combo::active()
                ->with(['items.product.images', 'items.variant'])
                ->when($subtreeIds, fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $subtreeIds)))
                ->get()->each(fn ($c) => $c->catalog_type = 'combo');

        $rows = \Modules\Product\Models\CatalogPosition::query()
            ->when($categoryId === null, fn ($q) => $q->whereNull('category_id'))
            ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
            ->get(['positionable_type', 'positionable_id', 'position']);
        $pos = [];
        foreach ($rows as $r) {
            $pos[$r->positionable_type.':'.$r->positionable_id] = (int) $r->position;
        }

        // concat (not merge): products and combos can share integer ids.
        $merged = $products->values()->concat($combos->values());

        // Price key parallels the product-only sort (raw list price): products
        // use sell_price, combos use combo_price — the pre-discount base each
        // shows before campaign/combo-discount decoration.
        $priceOf = fn ($i) => $i instanceof \Modules\Ecommerce\Models\Combo
            ? (float) $i->combo_price
            : (float) $i->sell_price;
        $timeOf = fn ($i) => optional($i->created_at)->timestamp ?? 0;
        $nameOf = fn ($i) => mb_strtolower((string) $i->name);

        // "featured" (default) keeps the admin-defined catalog-position order,
        // newest-first for unpositioned items. An explicit sort overrides that
        // and orders the whole merged set by the chosen key.
        $sort = $filters['sort'] ?? 'featured';
        $merged = (match ($sort) {
            'price_low'        => $merged->sortBy($priceOf),
            'price_high'       => $merged->sortByDesc($priceOf),
            'name_asc'         => $merged->sortBy($nameOf),
            'name_desc'        => $merged->sortByDesc($nameOf),
            'oldest'           => $merged->sortBy($timeOf),
            'latest', 'newest' => $merged->sortByDesc($timeOf),
            default            => $merged->sort(function ($x, $y) use ($pos) {
                $px = $pos[$x->catalog_type.':'.$x->id] ?? null;
                $py = $pos[$y->catalog_type.':'.$y->id] ?? null;
                if ($px !== null && $py !== null) {
                    return $px <=> $py;
                }
                if ($px !== null) {
                    return -1;
                }
                if ($py !== null) {
                    return 1;
                }
                return $y->created_at <=> $x->created_at;
            }),
        })->values();

        // Use the concrete paginator explicitly: the class-level import here is
        // the Contract (return-type only), which has no static resolve* helpers.
        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $slice = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $merged->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }

    public function getShopProducts(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::storefrontVisible()
            ->with(['images', 'category', 'brand', 'approvedReviews']);

        if (!empty($filters['category_slug'])) {
            $category = Category::where('slug', $filters['category_slug'])->first();
            if ($category) {
                $categoryIds = $this->getCategoryAndChildrenIds($category);
                $query->whereIn('category_id', $categoryIds);
            }
        }

        if (!empty($filters['brand_slug'])) {
            $brand = Brand::where('slug', $filters['brand_slug'])->first();
            if ($brand) {
                $query->where('brand_id', $brand->id);
            }
        }

        if (!empty($filters['q'])) {
            $query->search($filters['q']);
        }

        if (!empty($filters['price_min'])) {
            $query->where('sell_price', '>=', (float) $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $query->where('sell_price', '<=', (float) $filters['price_max']);
        }

        // Variant attribute facets (e.g. Color, Size). Selected value IDs are
        // grouped by their attribute: a product must match at least one value
        // within EACH selected attribute (AND across attributes, OR within an
        // attribute) — the standard storefront faceting behaviour.
        if (!empty($filters['variant_values'])) {
            $valueIds = array_values(array_filter(array_map('intval', (array) $filters['variant_values'])));
            if ($valueIds) {
                $grouped = \Modules\Variant\Models\VariantAttributeValue::whereIn('id', $valueIds)
                    ->get()
                    ->groupBy('variant_attribute_id');

                foreach ($grouped as $group) {
                    $groupIds = $group->pluck('id')->all();
                    $query->whereHas('variants', function ($q) use ($groupIds) {
                        $q->where('is_active', true)
                          ->whereHas('attributeValues', function ($q2) use ($groupIds) {
                              $q2->whereIn('variant_attribute_values.id', $groupIds);
                          });
                    });
                }
            }
        }

        // Availability facet ("Product Status"). Values: 'in' / 'out'.
        // Selecting both (or neither) is a no-op — it means "show everything".
        $stockStatus = array_values(array_filter((array) ($filters['stock_status'] ?? [])));
        $wantIn  = in_array('in', $stockStatus, true);
        $wantOut = in_array('out', $stockStatus, true);
        if ($wantIn && ! $wantOut) {
            $query->inStock();
        } elseif ($wantOut && ! $wantIn) {
            $query->outOfStock();
        }

        $sort = $filters['sort'] ?? 'latest';
        $query = $this->applySorting($query, $sort);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Find a product by slug with full relations.
     */
    public function findProductBySlug(string $slug): ?Product
    {
        return Product::storefrontVisible()
            ->where('slug', $slug)
            ->with([
                'images', 'category', 'brand', 'variants', 'tags', 'approvedReviews',
                'sizeChartOverrides',
            ])
            ->first();
    }

    /**
     * Get trending products (recently viewed or random active products).
     */
    public function getTrendingProducts(int $limit = 10): Collection
    {
        return Product::storefrontVisible()
            ->with(['images', 'category', 'approvedReviews'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Get special brand products (featured brands products).
     */
    public function getSpecialBrandProducts(int $limit = 8): Collection
    {
        $featuredBrandIds = Brand::active()->where('is_featured', true)->pluck('id');

        if ($featuredBrandIds->isEmpty()) {
            return $this->getFeaturedProducts($limit);
        }

        return Product::storefrontVisible()
            ->whereIn('brand_id', $featuredBrandIds)
            ->with(['images', 'category', 'brand', 'approvedReviews', 'variants.attributeValues.attribute'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Get favourite products (top discounted products).
     */
    public function getFavouriteProducts(int $limit = 6): Collection
    {
        return Product::storefrontVisible()
            ->where(function ($q) {
                $q->where('discount_type', 'percentage')
                  ->orWhere('discount_type', 'fixed');
            })
            ->where('discount_value', '>', 0)
            ->with(['images', 'category', 'approvedReviews'])
            ->orderByDesc('discount_value')
            ->limit($limit)
            ->get();
    }

    /**
     * The shared "design" title behind a per-color/style product or combo
     * name — strips a trailing " - (Color)" or " - Combo-NN-(...)" suffix.
     * "Premium Cotton Full Sleeve Formal Shirt - (Sky Blue)" and
     * "Premium Cotton Full Sleeve Formal Shirt - Combo-02-(Beige & Aqua-blue)"
     * both resolve to "Premium Cotton Full Sleeve Formal Shirt".
     */
    public function baseTitleOf(string $name): string
    {
        return trim(preg_replace('/\s*-\s*(Combo[\-\s].*|\(.*\))\s*$/i', '', $name));
    }

    /**
     * A category id plus its direct children and, when it's itself a child,
     * its sibling categories too — so a leaf category with only one design
     * in it (e.g. "Formal Shirt" holding just this shirt's colors) still
     * surfaces other products from the same section (e.g. "Casual Shirt")
     * instead of leaving the related-products fallback empty.
     *
     * @return array<int, int>
     */
    public function categoryIdsWithChildren(int $categoryId): array
    {
        $category = Category::find($categoryId);
        if (! $category) {
            return [$categoryId];
        }

        $ids = [$categoryId];
        $ids = array_merge($ids, Category::where('parent_id', $categoryId)->pluck('id')->all());

        if ($category->parent_id) {
            $ids = array_merge($ids, Category::where('parent_id', $category->parent_id)
                ->where('id', '!=', $categoryId)
                ->pluck('id')->all());
        }

        return array_values(array_unique($ids));
    }

    /**
     * Related items for a product or combo detail page, in a fixed priority:
     *   1) Products sharing the current item's base title — other colors or
     *      styles of the same design.
     *   2) Combos sharing that same base title.
     *   3) Other products in the given categories (already-listed items
     *      excluded), as a fallback so the section is never sparse.
     * Every item carries `catalog_type` so the caller's card-branching logic
     * (product-card vs combo-card) works uniformly.
     *
     * @param  Product|Combo  $current
     * @param  array<int, int>  $categoryIds
     */
    public function getRelatedCatalogItems($current, array $categoryIds): Collection
    {
        $isCombo = $current instanceof Combo;
        $baseTitle = $this->baseTitleOf($current->name);
        $comboService = app(ComboService::class);

        $sameTitleProducts = Product::storefrontVisible()
            ->inStock()
            ->where('name', 'like', $baseTitle . '%')
            ->when(!$isCombo, fn ($q) => $q->where('id', '!=', $current->id))
            ->with(['images', 'category', 'approvedReviews'])
            ->orderBy('name')
            ->get()
            ->each(fn ($p) => $p->catalog_type = 'product');

        $sameTitleCombos = Combo::active()
            ->where('name', 'like', $baseTitle . '%')
            ->when($isCombo, fn ($q) => $q->where('id', '!=', $current->id))
            ->with(['items.product.images', 'items.variant'])
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => $comboService->isInStock($c))
            ->each(fn ($c) => $c->catalog_type = 'combo');

        $excludedProductIds = $sameTitleProducts->pluck('id')->all();
        if (!$isCombo) {
            $excludedProductIds[] = $current->id;
        }

        $categoryProducts = collect();
        if (!empty($categoryIds)) {
            $categoryProducts = Product::storefrontVisible()
                ->inStock()
                ->whereIn('category_id', $categoryIds)
                ->whereNotIn('id', $excludedProductIds)
                ->with(['images', 'category', 'approvedReviews'])
                ->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
                ->orderBy('position')
                ->latest()
                ->get()
                ->each(fn ($p) => $p->catalog_type = 'product');
        }

        return $sameTitleProducts->values()
            ->concat($sameTitleCombos->values())
            ->concat($categoryProducts->values())
            ->values();
    }

    // ── Category Queries ──

    /**
     * Get top active root categories with product counts.
     */
    public function getTopCategories(int $limit = 9): Collection
    {
        return Category::active()
            ->root()
            ->where('show_in_top', true)
            ->ordered()
            ->withCount(['children' => function ($q) {
                $q->where('status', 'active');
            }])
            ->limit($limit)
            ->get()
            ->map(function ($category) {
                $categoryIds = $this->getCategoryAndChildrenIds($category);
                $category->product_count = Product::storefrontVisible()
                    ->whereIn('category_id', $categoryIds)
                    ->count();
                return $category;
            });
    }

    /**
     * Get all active root categories paginated with product counts.
     */
    public function getAllCategories(int $perPage = 24): LengthAwarePaginator
    {
        $categories = Category::active()
            ->root()
            ->ordered()
            ->paginate($perPage);

        $categories->getCollection()->transform(function ($category) {
            $categoryIds = $this->getCategoryAndChildrenIds($category);
            $category->product_count = Product::storefrontVisible()
                ->whereIn('category_id', $categoryIds)
                ->count();
            return $category;
        });

        return $categories;
    }

    /**
     * Find a category by slug.
     */
    public function findCategoryBySlug(string $slug): ?Category
    {
        return Category::active()
            ->where('slug', $slug)
            ->with(['children' => function ($q) {
                $q->active()->ordered();
            }, 'parent'])
            ->first();
    }

    /**
     * Get products belonging to a category and its children.
     */
    public function getCategoryProducts(Category $category, ?string $sort, int $perPage = 12): LengthAwarePaginator
    {
        $categoryIds = $this->getCategoryAndChildrenIds($category);

        // Match products whose primary category_id is in the tree OR that are
        // linked to any category in the tree via the many-to-many pivot, so a
        // product assigned to a child category (even when its primary category
        // is elsewhere) still shows on that category's page (Bug_81).
        $query = Product::storefrontVisible()
            ->where(function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                    ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds));
            })
            ->with(['images', 'category', 'brand', 'approvedReviews']);

        $query = $this->applySorting($query, $sort ?? 'latest');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get sidebar categories (active root categories with children).
     */
    public function getSidebarCategories(): Collection
    {
        // Recursive eager-load — the shop sidebar renders the full tree
        // (parent → sub → child…) so deeper levels aren't N+1 queried as
        // the user expands branches.
        return Category::active()
            ->root()
            ->ordered()
            ->with('recursiveChildren')
            ->get();
    }

    /**
     * Lowest and highest sell_price across all active products. Drives the
     * shop's price-range slider bounds so they always reflect real inventory.
     * Falls back to [0, 0] when the catalogue is empty.
     *
     * @return array{min: float, max: float}
     */
    public function getActivePriceBounds(): array
    {
        $bounds = Product::storefrontVisible()
            ->selectRaw('MIN(sell_price) as min_price, MAX(sell_price) as max_price')
            ->first();

        return [
            'min' => (float) ($bounds->min_price ?? 0),
            'max' => (float) ($bounds->max_price ?? 0),
        ];
    }

    /**
     * Variant attribute facets for the shop sidebar. Only returns active
     * attributes whose values are actually used by active products' active
     * variants, so the filter never offers a value that yields zero results.
     *
     * Scoped to the current category (incl. children) when one is selected, so
     * the facets reflect the products actually on screen — e.g. a Pant
     * category surfaces "Size (Pant)" values while a Shirt category surfaces
     * "Size (Shirt)". Same-base-name size attributes are collapsed to a single
     * "Size" facet; when no category narrows it, the shirt-style sizing is the
     * default. Labels are shown as the clean base name ("Size").
     *
     * @param  array  $filters  Accepts 'category_slug'.
     * @return \Illuminate\Support\Collection<int, array>
     */
    public function getShopVariantFilters(array $filters = []): \Illuminate\Support\Collection
    {
        // Value IDs in use by an active variant of an active product, scoped to
        // the selected category (and its children) when present.
        $usedQuery = DB::table('product_variant_values as pvv')
            ->join('product_variants as pv', 'pv.id', '=', 'pvv.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->where('pv.is_active', true)
            ->whereNull('pv.deleted_at')
            ->where('p.status', 'active')
            ->whereNull('p.deleted_at');

        if (!empty($filters['category_slug'])) {
            $category = Category::where('slug', $filters['category_slug'])->first();
            if ($category) {
                $usedQuery->whereIn('p.category_id', $this->getCategoryAndChildrenIds($category));
            }
        }

        $usedValueIds = $usedQuery->distinct()->pluck('pvv.variant_attribute_value_id')->all();

        if (empty($usedValueIds)) {
            return collect();
        }

        $attributes = \Modules\Variant\Models\VariantAttribute::active()
            ->ordered()
            ->with(['values' => function ($q) use ($usedValueIds) {
                $q->whereIn('id', $usedValueIds);
            }])
            ->get()
            ->filter(fn ($attr) => $attr->values->isNotEmpty());

        return $this->collapseSizeAttributes($attributes)
            ->map(fn ($attr) => [
                'id'           => (int) $attr->id,
                'name'         => $attr->base_name,
                'display_type' => $attr->display_type,
                'values'       => $attr->values->map(fn ($v) => [
                    'id'         => (int) $v->id,
                    'value'      => $v->value,
                    'color_code' => $v->color_code,
                ])->values()->all(),
            ])
            ->values();
    }

    /**
     * Collapse attributes that share a customer-facing base name (e.g.
     * "Size (Pant)" + "Size (Shirt)" -> a single "Size" facet). When a group
     * has more than one — i.e. the category hasn't already narrowed it to one —
     * default to the shirt-style sizing.
     *
     * @param  \Illuminate\Support\Collection  $attributes
     * @return \Illuminate\Support\Collection
     */
    private function collapseSizeAttributes(\Illuminate\Support\Collection $attributes): \Illuminate\Support\Collection
    {
        return $attributes
            ->groupBy(fn ($attr) => mb_strtolower($attr->base_name))
            ->map(function ($group) {
                if ($group->count() === 1) {
                    return $group->first();
                }

                return $group->first(fn ($a) => stripos($a->name, 'shirt') !== false)
                    ?? $group->first();
            })
            ->values();
    }

    // ── Brand Queries ──

    /**
     * Get active brands.
     */
    public function getActiveBrands(int $limit = 12): Collection
    {
        return Brand::active()->ordered()->limit($limit)->get();
    }

    /**
     * Get all active brands for sidebar.
     */
    public function getSidebarBrands(): Collection
    {
        return Brand::active()->ordered()->get();
    }

    // ── Cart Helpers ──

    /**
     * Effective price for a product, server-side. Used by cart add/update
     * and re-validated at checkout, so the customer cannot be charged more
     * than the lowest currently-available price.
     *
     * Priority: flash deal (most explicit + time-bound + opt-in by admin)
     * → campaign (scope-based time-bound) → product-level discount. The
     * lowest of all applicable prices wins so the customer always gets
     * the best deal regardless of which discount mechanism the merchant
     * is using.
     *
     * When a $variant is supplied, its own sell_price becomes the base the
     * discount/campaign/flash-deal applies to — so a discounted variable
     * product discounts each variant individually rather than charging the
     * raw variant price. Falls back to the product sell_price when the
     * variant has no price of its own.
     */
    public function calculateEffectivePrice(Product $product, ?\Modules\Variant\Models\ProductVariant $variant = null): float
    {
        $sell  = ($variant && $variant->sell_price)
            ? (float) $variant->sell_price
            : (float) $product->sell_price;
        $best  = $sell;

        $flashPrice = $this->flashDealPriceFor($product, $sell);
        if ($flashPrice !== null && $flashPrice < $best) $best = $flashPrice;

        $campaign = $this->campaigns->bestFor($product, $sell);
        if ($campaign) {
            $cPrice = (float) $campaign->applyTo($sell);
            if ($cPrice < $best) $best = $cPrice;
        }

        if ($product->discount_type && $product->discount_value > 0) {
            $value = (float) $product->discount_value;
            if ($product->discount_type === 'percentage') {
                $pPrice = round($sell * (1 - min($value, 100) / 100), 2);
            } else {
                $pPrice = round(max(0, $sell - $value), 2);
            }
            if ($pPrice < $best) $best = $pPrice;
        }

        // Round UP to the next whole BDT — keeps prices clean (no 76.50)
        // and matches Product::displayPrice() so the card, side drawer,
        // checkout, and place-order guard all agree on the same number.
        return (float) ceil($best);
    }

    /**
     * Lowest price this product would get from any currently-running flash
     * deal it's part of, or null if it isn't in any active deal. Looks at
     * the pivot's per-deal discount_type / discount_value (a product can
     * be in multiple deals — we pick the cheapest result).
     */
    private function flashDealPriceFor(Product $product, float $sell): ?float
    {
        $activeDealIds = $this->activeFlashDealIds();
        if ($activeDealIds->isEmpty()) return null;

        $pivots = DB::table('flash_deal_product')
            ->where('product_id', $product->id)
            ->whereIn('flash_deal_id', $activeDealIds)
            ->get(['discount_type', 'discount_value']);

        if ($pivots->isEmpty()) return null;

        $best = null;
        foreach ($pivots as $pivot) {
            if (! $pivot->discount_type || (float) $pivot->discount_value <= 0) continue;
            $value = (float) $pivot->discount_value;
            $effective = $pivot->discount_type === 'percentage'
                ? round($sell * (1 - min($value, 100) / 100), 2)
                : round(max(0, $sell - $value), 2);
            if ($best === null || $effective < $best) $best = $effective;
        }
        return $best;
    }

    /**
     * Cached for the lifetime of the request — avoids hitting the
     * flash_deals table once per cart line during checkout validation.
     * Untyped because pluck() returns Illuminate\Support\Collection,
     * not the Eloquent Collection imported at the top of the file.
     */
    private $cachedActiveFlashDealIds = null;
    private function activeFlashDealIds(): \Illuminate\Support\Collection
    {
        if ($this->cachedActiveFlashDealIds === null) {
            $this->cachedActiveFlashDealIds = FlashDeal::active()->pluck('id');
        }
        return $this->cachedActiveFlashDealIds;
    }

    /**
     * Overlay each product's best active flash deal discount onto the
     * Product instance in memory so the storefront views (which read
     * discount_type / discount_value via Product::displayPrice) pick it
     * up automatically. Call this on any product collection going to a
     * customer-facing view, parallel to CampaignService::decorate().
     */
    public function decorateFlashDeals($products): void
    {
        if (! $products || (method_exists($products, 'isEmpty') && $products->isEmpty())) return;

        $activeDealIds = $this->activeFlashDealIds();
        if ($activeDealIds->isEmpty()) return;

        // A curated section can mix in Combo items (see curatedSectionItems) —
        // flash deals only ever apply to products, and a Combo's id lives in a
        // different id-space that must never be looked up against product_id.
        $productIds = collect($products)->filter(fn ($p) => $p instanceof Product)->pluck('id')->filter()->unique()->values();
        if ($productIds->isEmpty()) return;

        // One query for all (product, deal) pivot rows — fans out below.
        $pivotRows = DB::table('flash_deal_product')
            ->whereIn('product_id', $productIds)
            ->whereIn('flash_deal_id', $activeDealIds)
            ->get(['product_id', 'discount_type', 'discount_value'])
            ->groupBy('product_id');

        foreach ($products as $product) {
            if (! $product instanceof Product) continue;
            $rows = $pivotRows->get($product->id);
            if (! $rows || $rows->isEmpty()) continue;

            $sell = (float) $product->sell_price;
            $bestType = null;
            $bestValue = 0.0;
            $bestEffective = $sell;
            foreach ($rows as $r) {
                if (! $r->discount_type || (float) $r->discount_value <= 0) continue;
                $v = (float) $r->discount_value;
                $eff = $r->discount_type === 'percentage'
                    ? round($sell * (1 - min($v, 100) / 100), 2)
                    : round(max(0, $sell - $v), 2);
                if ($eff < $bestEffective) {
                    $bestEffective = $eff;
                    $bestType  = $r->discount_type;
                    $bestValue = $v;
                }
            }
            if ($bestType !== null) {
                // Effective price of the product's OWN existing discount
                // (legacy columns). Never overwrite a cheaper own-discount
                // with a pricier flash deal — e.g. a fixed ৳155 off (695)
                // must beat a flash deal's rounded 18% (697).
                $ownEffective = $sell;
                if ($product->discount_type && (float) $product->discount_value > 0) {
                    $ownEffective = $product->discount_type === 'percentage'
                        ? round($sell * (1 - min((float) $product->discount_value, 100) / 100), 2)
                        : round(max(0, $sell - (float) $product->discount_value), 2);
                }
                if ($bestEffective >= $ownEffective) continue;

                // If a campaign was decorated first AND it gives a better
                // (or equal) price, leave it in place. Otherwise overlay
                // the flash deal onto the legacy discount columns and
                // clear the campaign so Product::displayPrice() falls
                // through to the legacy path. Net effect: customer
                // always sees the lowest available price.
                if (isset($product->campaign) && $product->campaign) {
                    $campaignEffective = (float) ($product->campaign_price ?? $sell);
                    if ($campaignEffective <= $bestEffective) continue;
                    $product->campaign = null;
                    $product->campaign_price = null;
                    $product->campaign_discount_percentage = null;
                }
                $product->discount_type  = $bestType;
                $product->discount_value = $bestValue;
            }
        }
    }

    /**
     * Calculate cart subtotal from session cart items.
     */
    public function calculateCartSubtotal(array $cart): float
    {
        $subtotal = 0;

        foreach ($cart as $item) {
            $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
        }

        return round($subtotal, 2);
    }

    // ── Coupon ──

    /**
     * Find and validate a coupon by code.
     */
    public function findValidCoupon(string $code, float $orderAmount): ?Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon || !$coupon->isValid($orderAmount)) {
            return null;
        }

        return $coupon;
    }

    // ── Shipping ──

    /**
     * Get default shipping charge.
     */
    public function getDefaultShippingCharge(): float
    {
        $zone = ShippingZone::active()->first();

        return $zone ? (float) $zone->flat_rate : 0;
    }

    // ── Order Creation ──

    /**
     * Create an ecommerce order from cart data.
     */
    public function createOrder(array $data, array $cart, float $subtotal, float $discountAmount, float $shippingCharge): EcommerceOrder
    {
        return DB::transaction(function () use ($data, $cart, $subtotal, $discountAmount, $shippingCharge) {
            $grandTotal = round($subtotal - $discountAmount + $shippingCharge, 2);

            // Guest checkout: attach (or create) the CRM customer record,
            // matched by phone so repeat guests never become duplicates. The
            // mirrored Sale below then links to a real customer instead of a
            // name/phone snapshot. Logged-in customers already arrive with
            // customer_id set (StorefrontCustomer shares the customers table).
            if (empty($data['customer_id'])) {
                $data['customer_id'] = $this->customerService->findOrCreateByPhone([
                    'name'    => $data['customer_name'] ?? '',
                    'phone'   => $data['customer_phone'] ?? '',
                    'email'   => $data['customer_email'] ?? null,
                    'address' => $data['shipping_address'] ?? null,
                ])?->id;
            }

            // Variant info ("Model: X, Color: Y, Size: Z") is stored in its
            // own `item_notes` column so the customer's free-text `notes`
            // stays clean.
            $itemNotes = $this->buildItemVariantNotes($cart);

            $order = EcommerceOrder::create([
                'order_number'     => $this->generateOrderNumber(),
                'customer_id'      => $data['customer_id'] ?? null,
                'customer_name'    => $data['customer_name'],
                'customer_email'   => $data['customer_email'] ?? null,
                'customer_phone'   => $data['customer_phone'],
                'shipping_address' => $data['shipping_address'],
                'billing_address'  => $data['billing_address'] ?? $data['shipping_address'],
                'status'           => 'pending',
                'payment_status'   => 'unpaid',
                'payment_method'   => $data['payment_method'] ?? 'cod',
                'subtotal'         => $subtotal,
                'discount_amount'  => $discountAmount,
                'tax_amount'       => 0,
                'shipping_charge'  => $shippingCharge,
                'shipping_zone_id' => $data['shipping_zone_id'] ?? null,
                'grand_total'      => $grandTotal,
                'coupon_code'      => $data['coupon_code'] ?? null,
                'source'           => 'storefront',
                'notes'            => $data['notes'] ?? null,
                'item_notes'       => $itemNotes !== '' ? $itemNotes : null,
            ]);

            foreach ($cart as $item) {
                // A combo line expands into one order item per component so the
                // existing stock/sales machinery deducts per product/variant at
                // fulfillment. Component prices are allocated from the combo's
                // effective price (the last component absorbs rounding).
                if (($item['type'] ?? 'product') === 'combo') {
                    $combo = Combo::with('items.product')->find($item['combo_id']);
                    $lineTotals = $combo ? app(ComboService::class)->allocateLineTotals($combo, (int) $item['quantity']) : [];
                    foreach ($combo?->items ?? [] as $ci) {
                        $lineTotal = $lineTotals[$ci->id] ?? 0.0;
                        $lineQty = $ci->quantity * (int) $item['quantity'];
                        EcommerceOrderItem::create([
                            'ecommerce_order_id' => $order->id,
                            'product_id'         => $ci->product_id,
                            'variant_id'         => $ci->variant_id,
                            'combo_id'           => $combo->id,
                            'combo_name'         => $combo->name,
                            'product_name'       => $ci->product->name ?? $item['name'],
                            'variant_name'       => null,
                            'quantity'           => $lineQty,
                            // unit_price is the per-unit display value; subtotal is the
                            // exact reconciled line total (sums to the combo price).
                            'unit_price'         => $lineQty > 0 ? round($lineTotal / $lineQty, 2) : 0.0,
                            'subtotal'           => $lineTotal,
                        ]);
                    }
                    continue;
                }

                EcommerceOrderItem::create([
                    'ecommerce_order_id' => $order->id,
                    'product_id'         => $item['product_id'],
                    'variant_id'         => $item['variant_id'] ?? null,
                    'product_name'       => $item['name'],
                    'variant_name'       => $item['variant_name'] ?? null,
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $item['price'],
                    'subtotal'           => round($item['price'] * $item['quantity'], 2),
                ]);
            }

            // Mirror the storefront order into the `sales` table so it shows
            // up on /admin/sales alongside POS + manual invoices. The two
            // records are linked via `ecommerce_orders.sale_id`. The Sale is
            // the source of truth for the admin order workflow.
            $sale = $this->createMirroredSale($order, $cart, $itemNotes);
            $order->update(['sale_id' => $sale->id]);

            // Increment coupon usage if applicable
            if (!empty($data['coupon_code'])) {
                Coupon::where('code', $data['coupon_code'])->increment('used_count');
            }

            try {
                event(new \Modules\Ecommerce\Events\OrderPlaced($order->fresh('items')));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to broadcast OrderPlaced for {$order->order_number}: {$e->getMessage()}");
            }

            return $order;
        });
    }

    /**
     * Find an order by order number.
     */
    public function findOrderByNumber(string $orderNumber): ?EcommerceOrder
    {
        return EcommerceOrder::where('order_number', $orderNumber)
            ->with(['items', 'shippingZone'])
            ->first();
    }

    // ── Private Helpers ──

    /**
     * Build a `sales` row + sale_items from a freshly-created storefront
     * EcommerceOrder so the order surfaces on /admin/sales (the unified
     * order list). status='pending' so SaleService::createSale skips the
     * stock-deduction step — stock should only move when the order is
     * actually fulfilled, not at checkout.
     */
    private function createMirroredSale(EcommerceOrder $order, array $cart, string $itemNotes): \Modules\Sale\Models\Sale
    {
        $saleItems = $this->cartToSaleItems($cart);

        return $this->saleService->createSale(
            [
                'reference_number'        => $order->order_number,
                'customer_id'             => $order->customer_id,
                'customer_name_snapshot'  => $order->customer_id ? null : $order->customer_name,
                'customer_phone_snapshot' => $order->customer_id ? null : $order->customer_phone,
                'customer_address'        => $order->shipping_address,
                'sale_date'               => $order->created_at?->toDateString() ?? now()->toDateString(),
                'source'                  => 'ecommerce',
                'sale_status'             => 'pending',
                'discount_value'          => (float) $order->discount_amount,
                'shipping_charge'         => (float) $order->shipping_charge,
                'notes'                   => $order->notes,
                'staff_note'              => $itemNotes !== '' ? $itemNotes : null,
            ],
            $saleItems
        );
    }

    /**
     * Map storefront cart lines to SaleService item arrays. A combo expands
     * into per-component sale items so the mirrored Sale (and thus fulfillment
     * stock deduction) sees individual products/variants — prices allocated
     * from the combo's effective price. Shared by the checkout order mirror
     * and the abandoned-checkout (incomplete order) capture.
     */
    public function cartToSaleItems(array $cart): array
    {
        $saleItems = [];
        foreach ($cart as $item) {
            if (($item['type'] ?? 'product') === 'combo') {
                $combo = Combo::with('items.product')->find($item['combo_id']);
                $alloc = $combo ? app(ComboService::class)->allocatePrices($combo, (int) $item['quantity']) : [];
                // Tag the component lines so this combo is editable-as-a-combo
                // in Sales Edit (one group per combo line in the cart).
                $comboGroup = (string) \Illuminate\Support\Str::uuid();
                $comboPrice = $combo ? (float) app(ComboService::class)->effectivePrice($combo) : null;
                // Customer-chosen size (when the combo requires one) + the cart
                // line's resolved components (per-size variant ids), aligned to
                // $combo->items by order (both come from the items() relation).
                $size = $item['size'] ?? null;
                $components = array_values($item['components'] ?? []);
                foreach (($combo?->items ?? []) as $idx => $ci) {
                    $comp = $components[$idx] ?? null;
                    $saleItems[] = [
                        'product_id'      => $ci->product_id,
                        'variant_id'      => $comp['variant_id'] ?? $ci->variant_id,
                        // Keep it short (column is varchar(80)) and meaningful: the
                        // chosen size, else the resolved variant name, else null.
                        // combo_name/combo_group below carry the combo grouping.
                        'variant_label'   => $size ?: ($comp['variant_name'] ?? null),
                        'quantity'        => $ci->quantity * (int) $item['quantity'],
                        'unit_price'      => $alloc[$ci->id] ?? 0.0,
                        'discount_amount' => 0,
                        'tax_amount'      => 0,
                        'combo_id'        => $combo?->id,
                        'combo_group'     => $comboGroup,
                        'combo_name'      => $combo?->name,
                        'combo_price'     => $comboPrice,
                    ];
                }
                continue;
            }

            $saleItems[] = [
                'product_id'      => $item['product_id'],
                'variant_id'      => $item['variant_id'] ?? null,
                'variant_label'   => $item['variant_name'] ?? null,
                'quantity'        => $item['quantity'],
                'unit_price'      => $item['price'],
                'discount_amount' => 0,
                'tax_amount'      => 0,
            ];
        }

        return $saleItems;
    }

    /**
     * Compose a per-cart-item line of variant info for the order notes, e.g.
     *   "Black T-Shirt — Model: GCS-04, Color: Black, Size: M"
     *
     * Variant attributes (Color, Size, …) come from the product_variants
     * attribute pivot and are dynamic — any attribute the variant has will
     * be included. Empty fields are skipped. Returns an empty string when
     * there's nothing to add.
     */
    private function buildItemVariantNotes(array $cart): string
    {
        if (empty($cart)) {
            return '';
        }

        $productIds = array_filter(array_column($cart, 'product_id'));
        $variantIds = array_filter(array_column($cart, 'variant_id'));

        $productModels = $productIds
            ? \Modules\Product\Models\Product::whereIn('id', $productIds)->pluck('model', 'id')
            : collect();

        $variants = $variantIds
            ? \Modules\Variant\Models\ProductVariant::with('attributeValues.attribute')
                ->whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        $lines = [];
        foreach ($cart as $item) {
            $parts = [];

            $model = $productModels[$item['product_id'] ?? 0] ?? null;
            if (!empty($model)) {
                $parts[] = 'Model: ' . $model;
            }

            $variantId = $item['variant_id'] ?? null;
            if ($variantId && isset($variants[$variantId])) {
                foreach ($variants[$variantId]->attributeValues as $av) {
                    $attrName = $av->attribute?->name;
                    $attrValue = $av->value;
                    if ($attrName && $attrValue !== null && $attrValue !== '') {
                        $parts[] = $attrName . ': ' . $attrValue;
                    }
                }
            }

            if (!empty($parts)) {
                $label = trim((string) ($item['name'] ?? 'Item'));
                $lines[] = $label . ' — ' . implode(', ', $parts);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get IDs for a category and all of its active descendants (recursively),
     * so a parent category page also surfaces products filed under deeper
     * sub/child categories — not just immediate children (Bug_81).
     */
    private function getCategoryAndChildrenIds(Category $category): array
    {
        $ids = [$category->id];

        $children = Category::active()
            ->where('parent_id', $category->id)
            ->get();

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getCategoryAndChildrenIds($child));
        }

        return array_values(array_unique($ids));
    }

    /**
     * Apply sorting to a product query.
     *
     * 'featured' (the default) honors the manual position set in the admin —
     * products with position > 0 sort first, then unranked products by latest.
     */
    private function applySorting($query, string $sort)
    {
        $allowedSorts = ['featured', 'latest', 'newest', 'oldest', 'price_low', 'price_high', 'name_asc', 'name_desc'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'featured';

        return match ($sort) {
            'featured'   => $query->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
                                  ->orderBy('position')
                                  ->latest(),
            'latest', 'newest' => $query->latest(),
            'oldest'     => $query->oldest(),
            'price_low'  => $query->orderBy('sell_price', 'asc'),
            'price_high' => $query->orderByDesc('sell_price'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'name_desc'  => $query->orderByDesc('name'),
            default      => $query->orderByRaw('CASE WHEN position > 0 THEN 0 ELSE 1 END')
                                  ->orderBy('position')
                                  ->latest(),
        };
    }

    /**
     * Generate a unique order number.
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-';
        $date = now()->format('Ymd');

        // withTrashed(): soft-deleted orders keep their number, and the UNIQUE
        // index spans them — skipping trashed rows here would re-issue a taken
        // number and fail the insert.
        $lastOrder = EcommerceOrder::withTrashed()
            ->where('order_number', 'like', $prefix . $date . '%')
            ->orderByDesc('id')
            ->first();

        if ($lastOrder) {
            $lastSequence = (int) substr($lastOrder->order_number, -4);
            $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }

        return $prefix . $date . '-' . $sequence;
    }
}
