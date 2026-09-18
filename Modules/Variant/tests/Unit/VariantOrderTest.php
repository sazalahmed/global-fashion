<?php

namespace Modules\Variant\Tests\Unit;

use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Unit\Models\Unit;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Tests\TestCase;

/**
 * Variants must list in their attribute's own order (XS, S, M, L …), not the
 * order they were created in. Adding a size to an existing product used to push
 * it to the bottom of every picker, so a shirt that gained an S showed
 * M, L, XL, XXL, S.
 */
class VariantOrderTest extends TestCase
{
    protected Product $product;
    protected VariantAttribute $size;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin);

        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'status' => 'active', 'sort_order' => 0]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'status' => 'active']);

        $this->product = Product::create([
            'name' => 'Test Shirt', 'slug' => 'test-shirt', 'sku' => 'TS-001',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'cost_price' => 100, 'sell_price' => 200, 'product_type' => 'variable',
            'status' => 'active', 'vat_rate' => 0, 'vat_inclusive' => 'no',
            'discount_type' => 'none', 'min_stock_alert' => 0, 'show_in_pos' => true,
            'track_stock' => true, 'created_by' => $this->admin->id,
        ]);

        $this->size = VariantAttribute::create([
            'name' => 'Size (Shirt)', 'display_name' => 'Size', 'display_type' => 'dropdown',
            'sort_order' => 0, 'status' => 'active',
        ]);
    }

    /** A size on the scale, e.g. S at position 2. */
    private function sizeValue(string $value, int $position): VariantAttributeValue
    {
        return VariantAttributeValue::create([
            'variant_attribute_id' => $this->size->id,
            'value' => $value, 'is_active' => true, 'sort_order' => $position,
        ]);
    }

    private function variant(string $sku, ?VariantAttributeValue $value): ProductVariant
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id, 'sku' => $sku,
            'cost_price' => 100, 'sell_price' => 200, 'is_active' => true,
        ]);

        if ($value) {
            $variant->attributeValues()->attach($value->id);
        }

        return $variant;
    }

    private function skusInOrder(): array
    {
        return $this->product->fresh()->variants->pluck('sku')->all();
    }

    public function test_a_size_added_later_still_sorts_into_place(): void
    {
        // Created in this order, exactly as the reported product was.
        $m = $this->sizeValue('M', 3);
        $l = $this->sizeValue('L', 4);
        $xl = $this->sizeValue('XL', 5);
        $s = $this->sizeValue('S', 2);

        $this->variant('TS-001-M', $m);
        $this->variant('TS-001-L', $l);
        $this->variant('TS-001-XL', $xl);
        $this->variant('TS-001-S', $s);   // added last, lowest position

        $this->assertEquals(['TS-001-S', 'TS-001-M', 'TS-001-L', 'TS-001-XL'], $this->skusInOrder());
    }

    public function test_creation_order_does_not_decide_the_listing(): void
    {
        $xl = $this->sizeValue('XL', 5);
        $s = $this->sizeValue('S', 2);
        $m = $this->sizeValue('M', 3);

        // Deliberately reversed on creation.
        $this->variant('TS-001-XL', $xl);
        $this->variant('TS-001-M', $m);
        $this->variant('TS-001-S', $s);

        $this->assertEquals(['TS-001-S', 'TS-001-M', 'TS-001-XL'], $this->skusInOrder());
    }

    public function test_variants_without_an_attribute_value_go_last(): void
    {
        $m = $this->sizeValue('M', 3);

        $this->variant('TS-001-PLAIN', null);  // created first, but has no size
        $this->variant('TS-001-M', $m);

        $this->assertEquals(['TS-001-M', 'TS-001-PLAIN'], $this->skusInOrder());
    }

    public function test_equal_positions_keep_a_stable_order(): void
    {
        $a = $this->sizeValue('One Size', 1);
        $b = $this->sizeValue('Free Size', 1);

        $this->variant('TS-001-A', $a);
        $this->variant('TS-001-B', $b);

        // Same position — falls back to id, so the sequence never shuffles.
        $this->assertEquals(['TS-001-A', 'TS-001-B'], $this->skusInOrder());
    }
}
