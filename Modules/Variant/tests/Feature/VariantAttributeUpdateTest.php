<?php

namespace Modules\Variant\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Services\VariantService;
use Tests\TestCase;

/**
 * Regression coverage for the attribute edit that wiped variant links: editing a
 * variant attribute (e.g. adding a Color value) must preserve existing value ids
 * so product_variant_values pivots stay valid, instead of deleting and recreating
 * every value with fresh ids.
 */
class VariantAttributeUpdateTest extends TestCase
{
    use RefreshDatabase;

    private VariantService $service;
    private VariantAttribute $color;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VariantService::class);

        $this->color = VariantAttribute::create([
            'name' => 'Color', 'display_name' => 'Color', 'display_type' => 'color_swatch',
            'sort_order' => 0, 'status' => 'active',
        ]);
        $this->color->values()->create(['value' => 'Red',  'sort_order' => 0]);
        $this->color->values()->create(['value' => 'Blue', 'sort_order' => 1]);

        $category = Category::create(['name' => 'Gen', 'slug' => 'gen', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);
        $this->product = Product::create([
            'name' => 'Tee', 'slug' => 'tee', 'sku' => 'TEE-1',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'variable',
            'status' => 'active', 'discount_type' => 'none', 'track_stock' => true,
        ]);
    }

    public function test_adding_a_value_preserves_existing_ids_and_variant_links(): void
    {
        $red  = $this->color->values()->where('value', 'Red')->first();
        $blue = $this->color->values()->where('value', 'Blue')->first();

        // A product variant references Red.
        $variant = ProductVariant::create([
            'product_id' => $this->product->id, 'sku' => 'TEE-1-RED',
            'cost_price' => 100, 'sell_price' => 200, 'is_active' => true, 'is_default' => true,
        ]);
        $variant->attributeValues()->attach($red->id);

        // Edit the attribute: keep Red & Blue (with ids), add Green.
        $this->service->updateAttribute($this->color, [
            'name' => 'Color', 'display_type' => 'color_swatch', 'status' => 'active',
            'values' => [
                ['id' => $red->id,  'name' => 'Red',   'sort_order' => 0],
                ['id' => $blue->id, 'name' => 'Blue',  'sort_order' => 1],
                ['name' => 'Green', 'sort_order' => 2],
            ],
        ]);

        // Red kept its id, so the variant's link is intact.
        $this->assertDatabaseHas('variant_attribute_values', ['id' => $red->id, 'value' => 'Red']);
        $variant->refresh()->load('attributeValues.attribute');
        $this->assertSame(1, $variant->attributeValues->count());
        $this->assertSame($red->id, $variant->attributeValues->first()->id);
        $this->assertSame('Red', $variant->attributeValues->first()->value);

        // Green was added.
        $this->assertDatabaseHas('variant_attribute_values', ['variant_attribute_id' => $this->color->id, 'value' => 'Green']);
        $this->assertSame(3, $this->color->values()->count());
    }

    public function test_removing_an_in_use_value_throws_and_keeps_it(): void
    {
        $red = $this->color->values()->where('value', 'Red')->first();
        $variant = ProductVariant::create([
            'product_id' => $this->product->id, 'sku' => 'TEE-1-RED',
            'cost_price' => 100, 'sell_price' => 200, 'is_active' => true, 'is_default' => true,
        ]);
        $variant->attributeValues()->attach($red->id);

        $this->expectException(\RuntimeException::class);

        try {
            // Submit only Blue — i.e. attempt to drop Red, which is in use.
            $blue = $this->color->values()->where('value', 'Blue')->first();
            $this->service->updateAttribute($this->color, [
                'name' => 'Color', 'display_type' => 'color_swatch', 'status' => 'active',
                'values' => [['id' => $blue->id, 'name' => 'Blue', 'sort_order' => 0]],
            ]);
        } finally {
            $this->assertDatabaseHas('variant_attribute_values', ['id' => $red->id]);
        }
    }

    public function test_removing_an_unused_value_deletes_it(): void
    {
        $blue = $this->color->values()->where('value', 'Blue')->first();
        $red  = $this->color->values()->where('value', 'Red')->first();

        $this->service->updateAttribute($this->color, [
            'name' => 'Color', 'display_type' => 'color_swatch', 'status' => 'active',
            'values' => [['id' => $red->id, 'name' => 'Red', 'sort_order' => 0]],
        ]);

        $this->assertDatabaseMissing('variant_attribute_values', ['id' => $blue->id]);
        $this->assertSame(1, $this->color->values()->count());
    }
}
