<?php

namespace Modules\Ecommerce\Console;

use Illuminate\Console\Command;
use Modules\Ecommerce\Services\SitemapService;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate public/sitemap.xml for storefront SEO';

    public function handle(SitemapService $sitemap): int
    {
        $count = $sitemap->generate();

        if ($sitemap->exceedsLimit($count)) {
            $this->warn('Sitemap exceeds 50k URLs; consider splitting into a sitemap index.');
        }

        $this->info('Sitemap generated: ' . $count . ' URLs.');

        return self::SUCCESS;
    }
}
