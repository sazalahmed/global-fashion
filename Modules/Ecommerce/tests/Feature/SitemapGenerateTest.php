<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SitemapGenerateTest extends TestCase
{
    public function test_generates_sitemap_with_product_url(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'slug' => 'sm-prod', 'status' => 'active', 'sell_price' => 100,
        ]);

        $path = public_path('sitemap.xml');
        @unlink($path);
        Artisan::call('sitemap:generate');

        $this->assertFileExists($path);
        $xml = file_get_contents($path);
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString(url('/shop/sm-prod'), $xml);
        $this->assertStringContainsString(url('/'), $xml);
        $this->assertStringNotContainsString('/checkout', $xml);

        @unlink($path);
    }
}
