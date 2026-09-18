<?php

namespace Modules\Ecommerce\Tests\Unit;

use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Services\SeoSchemaService;
use Tests\TestCase;

class SeoSchemaServiceTest extends TestCase
{
    private function svc(): SeoSchemaService { return app(SeoSchemaService::class); }

    public function test_organization_node_uses_settings(): void
    {
        EcommerceSetting::set('seo_org_name', 'BizShop Ltd');
        $node = $this->svc()->organization();
        $this->assertSame('Organization', $node['@type']);
        $this->assertSame('BizShop Ltd', $node['name']);
        $this->assertArrayHasKey('url', $node);
    }

    public function test_website_node_has_search_action(): void
    {
        $node = $this->svc()->website();
        $this->assertSame('WebSite', $node['@type']);
        $this->assertSame('SearchAction', $node['potentialAction']['@type']);
        $this->assertStringContainsString('q={search_term_string}', $node['potentialAction']['target']['urlTemplate']);
    }

    public function test_breadcrumb_list_positions(): void
    {
        $node = $this->svc()->breadcrumbList([
            ['name' => 'Home', 'url' => 'https://x.test/'],
            ['name' => 'Shop', 'url' => 'https://x.test/shop'],
            ['name' => 'Red Shirt', 'url' => null],
        ]);
        $this->assertSame('BreadcrumbList', $node['@type']);
        $this->assertCount(3, $node['itemListElement']);
        $this->assertSame(1, $node['itemListElement'][0]['position']);
        $this->assertSame('Red Shirt', $node['itemListElement'][2]['name']);
    }

    public function test_seo_columns_and_seo_pages_exist(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('blog_posts', 'seo_title'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('blog_posts', 'seo_image'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('products', 'seo_image'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('categories', 'meta_image'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('seo_pages'));
    }

    public function test_product_node_has_offer_with_bdt_and_availability(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $product = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'name' => 'Mug', 'slug' => 'mug', 'sell_price' => 300, 'sku' => 'MUG1', 'track_stock' => false,
        ]);
        $node = $this->svc()->product($product->fresh(), 'https://x.test/shop/mug');
        $this->assertSame('Product', $node['@type']);
        $this->assertSame('BDT', $node['offers']['priceCurrency']);
        $this->assertStringContainsString('schema.org', $node['offers']['availability']);
        $this->assertSame('MUG1', $node['sku']);
    }

    public function test_collection_page_lists_items(): void
    {
        $node = $this->svc()->collectionPage('Shop', 'https://x.test/shop', [
            ['name' => 'A', 'url' => 'https://x.test/shop/a', 'image' => 'https://x.test/a.jpg'],
        ]);
        $this->assertSame('CollectionPage', $node['@type']);
        $this->assertSame('ItemList', $node['mainEntity']['@type']);
        $this->assertSame(1, $node['mainEntity']['numberOfItems']);
    }
}
