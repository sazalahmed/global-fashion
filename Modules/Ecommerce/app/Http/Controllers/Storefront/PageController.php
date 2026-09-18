<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Page;
use Modules\Ecommerce\Support\BuildsSeo;
use Modules\Ecommerce\Support\Seo;

class PageController extends Controller
{
    use BuildsSeo;

    public function show(string $slug): View
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();
        $cartItems = session('cart', []);

        $seo = Seo::make()
            ->title($page->seo_title ?: $page->title)
            ->description($page->seo_description
                ?: Str::limit(strip_tags($page->content ?? ''), 160))
            ->breadcrumbs([
                ['name' => 'Home', 'url' => route('storefront.home')],
                ['name' => $page->title, 'url' => null],
            ]);

        if ($page->seo_image) {
            $seo->image($page->seo_image);
        }
        $seo->canonical(route('storefront.page.show', $page->slug));

        return view('ecommerce::storefront.pages.page.show', compact('page', 'cartItems', 'seo'));
    }
}
