<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Ecommerce\Support\BuildsSeo;
use Modules\Ecommerce\Support\Seo;

class HomeController extends Controller
{
    use BuildsSeo;
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    public function index(): View
    {
        if (class_exists(\Modules\LandingPage\Services\LandingPageService::class)) {
            $page = app(\Modules\LandingPage\Services\LandingPageService::class)->getActive();
            if ($page) {
                return view('landingpage::templates.' . $page->template, [
                    'page'     => $page,
                    'products' => $page->products,
                ]);
            }
        }

        $homepageData = $this->storefrontService->getHomepageData();
        $homepageData['cartItems'] = session('cart', []);
        $seo = $this->staticPageSeo('home', [
            'description' => 'Discover the latest products, exclusive deals, and new arrivals. Shop online with fast delivery and secure checkout.',
        ]);

        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        foreach (\Modules\Branch\Models\Branch::where('is_active', true)->where('is_ecom_enabled', true)->get() as $branch) {
            $seo->addSchema($schema->localBusiness($branch));
        }

        $homepageData['seo'] = $seo;

        return view('ecommerce::storefront.pages.home.index', $homepageData);
    }
}
