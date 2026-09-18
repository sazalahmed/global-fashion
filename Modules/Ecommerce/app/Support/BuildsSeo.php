<?php

namespace Modules\Ecommerce\Support;

use Illuminate\Http\Request;
use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Models\SeoPage;

trait BuildsSeo
{
    /**
     * Build SEO for a static page. Priority for each field: admin-managed
     * SeoPage value, then the admin's global default setting, then the
     * per-page $defaults passed by the controller. The controller default is
     * the last resort so a page always emits a meaningful meta description
     * even before any admin setup.
     */
    protected function staticPageSeo(string $key, array $defaults = []): Seo
    {
        $page = SeoPage::forKey($key);

        $description = $page?->description
            ?: (EcommerceSetting::get('seo_default_description') ?: ($defaults['description'] ?? null));

        return Seo::make()
            ->title($page?->title ?: ($defaults['title'] ?? null))
            ->description($description)
            ->robots($page?->robots ?: 'index,follow')
            ->image($page?->image ?: ($defaults['image'] ?? null));
    }

    /**
     * Build a clean canonical URL: $baseUrl + only the $keep query params present.
     * "page" is kept only when > 1; all other params (sort/price/utm_* /fbclid/...) are dropped.
     */
    protected function cleanCanonical(Request $request, string $baseUrl, array $keep = []): string
    {
        $params = [];
        foreach ($keep as $k) {
            $v = $request->query($k);
            if ($k === 'page') {
                if ((int) $v > 1) { $params['page'] = (int) $v; }
            } elseif (filled($v)) {
                $params[$k] = $v;
            }
        }
        return $params ? $baseUrl . '?' . http_build_query($params) : $baseUrl;
    }
}
