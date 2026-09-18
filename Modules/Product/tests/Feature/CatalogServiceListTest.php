<?php

namespace Modules\Product\Tests\Feature;

use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Product\Services\CatalogService;
use Tests\TestCase;

class CatalogServiceListTest extends TestCase
{
    use CreatesComboTestData;

    private function combo(string $name, array $categoryIds = []): Combo
    {
        $c = Combo::create(['name' => $name, 'combo_price' => 100, 'is_active' => true]);
        if ($categoryIds) {
            $c->categories()->sync($categoryIds);
        }
        return $c;
    }

    public function test_unfiltered_list_includes_products_and_combos(): void
    {
        $this->makeProduct(['name' => 'Prod X']);
        $this->combo('Combo Y');

        $items = app(CatalogService::class)->list([], 50);
        $names = collect($items->items())->map(fn ($i) => $i->name)->all();

        $this->assertContains('Prod X', $names);
        $this->assertContains('Combo Y', $names);
    }

    public function test_type_filter_limits_to_combos(): void
    {
        $this->makeProduct();
        $this->combo('Only Combo');

        $items = app(CatalogService::class)->list(['type' => 'combo'], 50);
        $this->assertCount(1, $items->items());
        $this->assertEquals('combo', $items->items()[0]->catalog_type);
    }

    public function test_category_filter_includes_subcategory_members(): void
    {
        $parent = Category::create(['name' => 'Shirts', 'slug' => 'shirts-'.uniqid(), 'status' => 'active']);
        $child = Category::create(['name' => 'Formal', 'slug' => 'formal-'.uniqid(), 'status' => 'active', 'parent_id' => $parent->id]);

        // Product only in the CHILD category (via category_id).
        $p = $this->makeProduct(['category_id' => $child->id]);
        // Combo assigned to the PARENT category.
        $c = $this->combo('Parent Combo', [$parent->id]);

        $items = app(CatalogService::class)->list(['category' => $parent->id], 50);
        $ids = collect($items->items())->map(fn ($i) => $i->catalog_type.':'.$i->id)->all();

        $this->assertContains('product:'.$p->id, $ids);
        $this->assertContains('combo:'.$c->id, $ids);
    }

    public function test_positioned_items_sort_before_unpositioned(): void
    {
        $cat = Category::create(['name' => 'C', 'slug' => 'c-'.uniqid(), 'status' => 'active']);
        $a = $this->makeProduct(['category_id' => $cat->id, 'name' => 'A']);
        $b = $this->makeProduct(['category_id' => $cat->id, 'name' => 'B']);
        $combo = $this->combo('Z Combo', [$cat->id]);

        // Put the combo first, product B second; product A left unpositioned.
        app(CatalogService::class)->reorder($cat->id, [
            ['type' => 'combo', 'id' => $combo->id],
            ['type' => 'product', 'id' => $b->id],
        ]);

        $order = collect(app(CatalogService::class)->list(['category' => $cat->id], 50)->items())
            ->map(fn ($i) => $i->catalog_type.':'.$i->id)->all();

        $this->assertEquals('combo:'.$combo->id, $order[0]);
        $this->assertEquals('product:'.$b->id, $order[1]);
        $this->assertEquals('product:'.$a->id, $order[2]); // unpositioned last
    }
}
