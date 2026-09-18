<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class OnPageSeoTest extends TestCase
{
    private function product(): \Modules\Product\Models\Product
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        return \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'name' => 'Blue Mug', 'slug' => 'blue-mug', 'status' => 'active', 'sell_price' => 100,
        ]);
    }

    public function test_product_page_has_exactly_one_h1_with_name(): void
    {
        $this->product();
        $html = $this->get('/shop/blue-mug')->getContent();
        $this->assertSame(1, substr_count($html, '<h1'), 'exactly one <h1>');
        $this->assertMatchesRegularExpression('/<h1[^>]*>.*Blue Mug.*<\/h1>/s', $html);
    }

    public function test_home_and_shop_have_one_h1(): void
    {
        foreach (['/', '/shop'] as $url) {
            $html = $this->get($url)->getContent();
            $this->assertSame(1, substr_count($html, '<h1'), "one <h1> on {$url}");
        }
    }

    public function test_product_cards_lazyload_images(): void
    {
        $this->product();
        $html = $this->get('/shop')->getContent();
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    public function test_no_empty_alt_in_layout(): void
    {
        $html = $this->get('/')->getContent();
        $this->assertStringNotContainsString('alt=""', $html);
    }

    public function test_head_has_analytics_preconnect(): void
    {
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('rel="preconnect" href="https://www.googletagmanager.com"', $html);
        $this->assertStringContainsString('rel="preconnect" href="https://connect.facebook.net"', $html);
    }
}
