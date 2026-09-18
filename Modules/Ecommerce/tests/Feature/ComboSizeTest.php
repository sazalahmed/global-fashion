<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Services\ComboService;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Tests\TestCase;

class ComboSizeTest extends TestCase
{
    use CreatesComboTestData;

    /** Create the Size attribute + values, returns [label => valueId]. */
    private function makeSizeValues(array $labels): array
    {
        $attr = VariantAttribute::create([
            'name' => 'Size', 'display_type' => 'button', 'status' => 'active', 'sort_order' => 1,
        ]);
        $ids = [];
        foreach (array_values($labels) as $i => $label) {
            $ids[$label] = VariantAttributeValue::create([
                'variant_attribute_id' => $attr->id, 'value' => $label,
                'is_active' => true, 'sort_order' => $i,
            ])->id;
        }
        return $ids;
    }

    /** Build a product with active size variants for the given labels. */
    private function productWithSizes(array $labels, array $valueIds): \Modules\Product\Models\Product
    {
        $product = $this->makeProduct(['track_stock' => false]);
        foreach ($labels as $label) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku'        => uniqid('v-'),
                'sell_price' => 100,
                'is_active'  => true,
            ]);
            $variant->attributeValues()->attach($valueIds[$label]);
        }
        return $product;
    }

    public function test_available_sizes_is_the_intersection_across_components(): void
    {
        $values = $this->makeSizeValues(['M', 'L', 'XL']);
        $p1 = $this->productWithSizes(['M', 'L', 'XL'], $values);
        $p2 = $this->productWithSizes(['M', 'L'], $values); // no XL

        $combo = Combo::create(['name' => 'C', 'combo_price' => 500, 'is_active' => true, 'size_required' => true]);
        $combo->items()->create(['product_id' => $p1->id, 'quantity' => 1, 'sort_order' => 0]);
        $combo->items()->create(['product_id' => $p2->id, 'quantity' => 1, 'sort_order' => 1]);

        $combo = $combo->fresh('items.product.variants.attributeValues.attribute');
        $sizes = app(ComboService::class)->availableSizes($combo);
        $labels = array_column($sizes, 'value');

        $this->assertEqualsCanonicalizing(['M', 'L'], $labels); // XL dropped (p2 lacks it)
    }

    public function test_resolve_component_variant_returns_the_matching_size_variant(): void
    {
        $values = $this->makeSizeValues(['M', 'L']);
        $p = $this->productWithSizes(['M', 'L'], $values);
        $combo = Combo::create(['name' => 'C', 'combo_price' => 500, 'is_active' => true, 'size_required' => true]);
        $item = $combo->items()->create(['product_id' => $p->id, 'quantity' => 1, 'sort_order' => 0]);

        $item = $item->fresh('product.variants.attributeValues.attribute');
        $variant = app(ComboService::class)->resolveComponentVariant($item, 'L');

        $this->assertNotNull($variant);
        $this->assertTrue($variant->attributeValues->pluck('value')->contains('L'));
    }
}
