<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Product\Services\CatalogService;
use Tests\TestCase;

class StorefrontCatalogOrderTest extends TestCase
{
    use CreatesComboTestData;

    public function test_category_listing_matches_admin_defined_order(): void
    {
        $cat = Category::create(['name' => 'Shirts', 'slug' => 'shirts-'.uniqid(), 'status' => 'active']);
        $p = $this->makeProduct(['category_id' => $cat->id, 'status' => 'active']);
        $c = Combo::create(['name' => 'Combo', 'combo_price' => 100, 'is_active' => true]);
        $c->categories()->sync([$cat->id]);

        // Admin orders the combo FIRST, then the product.
        app(CatalogService::class)->reorder($cat->id, [
            ['type' => 'combo', 'id' => $c->id],
            ['type' => 'product', 'id' => $p->id],
        ]);

        $items = app(StorefrontService::class)->getCatalogListing(['category_slug' => $cat->slug], 50);
        $order = collect($items->items())
            ->map(fn ($i) => ($i instanceof Combo ? 'combo:' : 'product:').$i->id)
            ->all();

        $this->assertEquals('combo:'.$c->id, $order[0]);
        $this->assertEquals('product:'.$p->id, $order[1]);
    }

    public function test_reordering_flips_the_storefront_order(): void
    {
        $cat = Category::create(['name' => 'Pants', 'slug' => 'pants-'.uniqid(), 'status' => 'active']);
        $p = $this->makeProduct(['category_id' => $cat->id, 'status' => 'active']);
        $c = Combo::create(['name' => 'Pant Combo', 'combo_price' => 100, 'is_active' => true]);
        $c->categories()->sync([$cat->id]);

        // Product first this time.
        app(CatalogService::class)->reorder($cat->id, [
            ['type' => 'product', 'id' => $p->id],
            ['type' => 'combo', 'id' => $c->id],
        ]);

        $order = collect(app(StorefrontService::class)->getCatalogListing(['category_slug' => $cat->slug], 50)->items())
            ->map(fn ($i) => ($i instanceof Combo ? 'combo:' : 'product:').$i->id)
            ->all();

        $this->assertEquals('product:'.$p->id, $order[0]);
        $this->assertEquals('combo:'.$c->id, $order[1]);
    }
}
