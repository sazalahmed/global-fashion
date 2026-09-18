<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Services\CampaignService;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Ecommerce\Support\BuildsSeo;

class StorefrontCategoryController extends Controller
{
    use BuildsSeo;
    public function __construct(
        protected StorefrontService $storefrontService,
        protected CampaignService $campaigns,
    ) {}

    /**
     * Display all active root categories.
     */
    public function index(): View
    {
        $categories = $this->storefrontService->getAllCategories(
            \Modules\Ecommerce\Models\EcommerceSetting::perPage('per_page_categories', 24)
        );
        $cartItems  = session('cart', []);
        $seo        = $this->staticPageSeo('categories');

        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        $items = $categories->getCollection()->map(fn ($c) => [
            'name'  => $c->name,
            'url'   => route('storefront.category.show', $c->slug),
            'image' => $c->image ? url($c->image) : null,
        ])->all();
        $seo->addSchema($schema->collectionPage('Categories', url()->current(), $items));
        if ($seo->breadcrumbs) { $seo->addSchema($schema->breadcrumbList($seo->breadcrumbs)); }

        return view('ecommerce::storefront.pages.category.index', compact(
            'categories',
            'cartItems',
            'seo'
        ));
    }

    /**
     * Display all active brands.
     */
    public function brands(): View
    {
        $brands = \Modules\Brand\Models\Brand::active()
            ->ordered()
            ->paginate(24)
            ->withQueryString();

        $cartItems = session('cart', []);

        return view('ecommerce::storefront.pages.category.index', compact(
            'cartItems'
        ))->with('categories', $brands)->with('isBrands', true);
    }

    /**
     * Redirect a category URL to the shop listing filtered by that category.
     *
     * The shop page (/shop?category={slug}) is the single source of truth for
     * the catalog design — its sidebar already links every category there. So
     * /categories/{slug} 301-redirects to the shop filtered view: identical
     * design, no duplicated markup, and it consolidates the duplicate-content
     * between the two URLs. Any sort/price filters are carried over.
     */
    public function show(Request $request, string $slug): \Illuminate\Http\RedirectResponse
    {
        $category = $this->storefrontService->findCategoryBySlug($slug);

        if (!$category) {
            $current = \Modules\Category\Models\Category::currentSlugFor($slug);
            if ($current && $current !== $slug) {
                $category = $this->storefrontService->findCategoryBySlug($current);
            }
            if (!$category) {
                abort(404);
            }
        }

        $params = array_merge(
            $request->except(['page']),
            ['category' => $category->slug]
        );

        return redirect()->route('storefront.shop.index', $params, 301);
    }
}
