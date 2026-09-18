<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;

class SlugRedirectTest extends TestCase
{
    public function test_changing_slug_records_history(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $p = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'slug' => 'old-slug', 'status' => 'active', 'sell_price' => 100,
        ]);
        $p->update(['slug' => 'new-slug']);

        $this->assertDatabaseHas('slug_histories', [
            'old_slug' => 'old-slug', 'model_id' => $p->id,
        ]);
    }

    public function test_old_product_slug_301s_to_new(): void
    {
        $cat = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $p = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $cat->id, 'slug' => 'old-p', 'status' => 'active', 'sell_price' => 100,
        ]);
        $p->update(['slug' => 'new-p']);

        $res = $this->get('/shop/old-p');
        $res->assertStatus(301);
        $res->assertRedirect(route('storefront.shop.show', 'new-p'));
    }

    public function test_unknown_slug_404s(): void
    {
        $this->get('/shop/totally-unknown')->assertStatus(404);
    }
}
