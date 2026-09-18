<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\EcommerceSetting;
use Tests\TestCase;

class StorefrontSeoTest extends TestCase
{
    public function test_home_emits_complete_head_with_defaults(): void
    {
        EcommerceSetting::set('seo_site_name', 'BizShop');
        EcommerceSetting::set('seo_default_description', 'Best shop in BD');

        $res = $this->get(route('storefront.home'));
        $res->assertSee('<link rel="canonical"', false);
        $res->assertSee('BizShop', false);
        $res->assertSee('Best shop in BD', false);
        $res->assertSee('property="og:title"', false);
        $res->assertSee('name="twitter:card"', false);
        $res->assertSee('application/ld+json', false);
        $res->assertSee('"@type":"Organization"', false);
    }

    public function test_blog_create_form_has_seo_fields(): void
    {
        $admin = \App\Models\User::factory()->create();
        $res = $this->actingAs($admin)->get(route('ecommerce.blog.create'));
        $res->assertSee('name="seo_title"', false);
        $res->assertSee('name="seo_description"', false);
    }

    public function test_blog_post_persists_seo_fields(): void
    {
        \Modules\Ecommerce\Models\BlogPost::create([
            'title' => 'P', 'slug' => 'p-seo', 'excerpt' => 'e', 'content' => 'c',
            'seo_title' => 'T', 'seo_description' => 'D',
        ]);
        $this->assertDatabaseHas('blog_posts', ['slug' => 'p-seo', 'seo_title' => 'T']);
    }

    public function test_seo_settings_render_on_home(): void
    {
        \Modules\Ecommerce\Models\EcommerceSetting::set('seo_site_name', 'MyStore');
        \Modules\Ecommerce\Models\EcommerceSetting::set('seo_default_description', 'desc here');
        $res = $this->get(route('storefront.home'));
        $res->assertSee('MyStore', false);
        $res->assertSee('desc here', false);
    }

    public function test_seo_page_seeder_creates_rows(): void
    {
        (new \Modules\Ecommerce\Database\Seeders\SeoPageSeeder())->run();
        $this->assertDatabaseHas('seo_pages', ['key' => 'home']);
        $this->assertDatabaseHas('seo_pages', ['key' => 'flash-deals']);
    }

    public function test_product_page_uses_seo_title_then_name(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'name' => 'Blue Mug', 'slug' => 'blue-mug',
            'seo_title' => 'Best Blue Mug', 'sell_price' => 300, 'status' => 'active',
            'track_stock' => false,
        ]);
        $res = $this->get(route('storefront.shop.show', 'blue-mug'));
        $res->assertSee('Best Blue Mug', false);
        $res->assertSee('property="og:type" content="product"', false);
    }

    public function test_category_page_uses_meta_title_then_name(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create([
            'name' => 'Mugs', 'slug' => 'mugs', 'meta_title' => 'All Mugs',
        ]);
        $res = $this->get(route('storefront.category.show', 'mugs'));
        $res->assertSee('All Mugs', false);
    }

    public function test_product_page_emits_generated_meta_description_fallback(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create(['name' => 'Mugs']);
        \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'name' => 'Plain Mug', 'slug' => 'plain-mug',
            'seo_description' => null, 'description' => null,
            'sell_price' => 300, 'status' => 'active', 'track_stock' => false,
        ]);
        $res = $this->get(route('storefront.shop.show', 'plain-mug'));
        $res->assertDontSee('<meta name="description" content="">', false);
        $res->assertSee('Buy Plain Mug online in Mugs', false);
    }

    public function test_product_page_emits_product_and_breadcrumb_jsonld(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'name' => 'Mug', 'slug' => 'mug2', 'sell_price' => 300, 'sku' => 'M2', 'status' => 'active', 'track_stock' => false,
        ]);
        $res = $this->get(route('storefront.shop.show', 'mug2'));
        $res->assertSee('"@type":"Product"', false);
        $res->assertSee('"@type":"BreadcrumbList"', false);
        $res->assertSee('"priceCurrency":"BDT"', false);
    }

    public function test_blog_post_emits_blogposting_jsonld(): void
    {
        $post = \Modules\Ecommerce\Models\BlogPost::create([
            'title' => 'Hello', 'slug' => 'hello-seo', 'excerpt' => 'e', 'content' => 'c', 'is_published' => true, 'published_at' => now(),
        ]);
        $res = $this->get(route('storefront.blog.show', 'hello-seo'));
        $res->assertSee('"@type":"BlogPosting"', false);
        $res->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_static_pages_emit_default_meta_description_without_admin_setup(): void
    {
        // No SeoPage rows, no seo_default_description setting — the controller
        // defaults must still produce a non-empty meta description.
        $cases = [
            route('storefront.home')        => 'Shop online with fast delivery',
            route('storefront.shop.index')  => 'Filter by category and price',
            route('storefront.flash-deals') => 'flash deals and discounts',
        ];

        foreach ($cases as $url => $needle) {
            $res = $this->get($url);
            $res->assertOk();
            $res->assertDontSee('<meta name="description" content="">', false);
            $res->assertSee($needle, false);
        }
    }

    public function test_all_public_pages_have_head_essentials(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create(['slug' => 'c1']);
        \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'slug' => 'p1', 'status' => 'active', 'sell_price' => 100, 'track_stock' => false,
        ]);

        foreach ([
            route('storefront.home'),
            route('storefront.shop.index'),
            route('storefront.category.index'),
            route('storefront.blog.index'),
            route('storefront.flash-deals'),
        ] as $url) {
            $res = $this->get($url);
            $res->assertOk();
            $res->assertSee('rel="canonical"', false);
            $res->assertSee('application/ld+json', false);
            $res->assertSee('name="twitter:card"', false);
        }
    }
}
