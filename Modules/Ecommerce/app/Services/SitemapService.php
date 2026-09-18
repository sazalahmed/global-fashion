<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Carbon;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\BlogPost;
use Modules\Product\Models\Product;

/**
 * Builds public/sitemap.xml for storefront SEO.
 *
 * Shared by the `sitemap:generate` console command (daily schedule) and the
 * "Generate Sitemap Now" button on the eCommerce Settings page so both paths
 * produce an identical sitemap.
 */
class SitemapService
{
    /**
     * Generate the sitemap file and refresh the robots.txt Sitemap line.
     *
     * @return int Number of URLs written.
     */
    public function generate(): int
    {
        $urls = [];
        $add = function (string $loc, $lastmod = null) use (&$urls) {
            $urls[] = ['loc' => $loc, 'lastmod' => $lastmod];
        };

        $add(route('storefront.home'));
        $add(route('storefront.shop.index'));
        $add(route('storefront.category.index'));
        $add(route('storefront.blog.index'));
        $add(route('storefront.flash-deals'));

        Product::storefrontVisible()->get()->each(function ($p) use ($add) {
            $add(route('storefront.shop.show', $p->slug), optional($p->updated_at)->toAtomString());
        });

        // Category uses `status` column (value 'active'), not a boolean `is_active`
        Category::query()->where('status', 'active')->get()->each(function ($c) use ($add) {
            $add(route('storefront.category.show', $c->slug), optional($c->updated_at)->toAtomString());
        });

        BlogPost::visible()->get()->each(function ($b) use ($add) {
            $add(route('storefront.blog.show', $b->slug), optional($b->updated_at)->toAtomString());
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
                  . ($u['lastmod'] ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '')
                  . '</url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        file_put_contents($this->path(), $xml);

        $robots = public_path('robots.txt');
        if (is_file($robots)) {
            $content = preg_replace('/^Sitemap:.*$/m', 'Sitemap: ' . $this->publicUrl(), file_get_contents($robots));
            file_put_contents($robots, $content);
        }

        return count($urls);
    }

    /** True when the sitemap would exceed the 50k-URL single-file limit. */
    public function exceedsLimit(int $count): bool
    {
        return $count > 50000;
    }

    /** Absolute filesystem path to the sitemap file. */
    public function path(): string
    {
        return public_path('sitemap.xml');
    }

    /** Public URL of the sitemap. */
    public function publicUrl(): string
    {
        return url('/sitemap.xml');
    }

    /** When the sitemap was last written, or null if it has never been generated. */
    public function lastGeneratedAt(): ?Carbon
    {
        return is_file($this->path())
            ? Carbon::createFromTimestamp(filemtime($this->path()))
            : null;
    }
}
