<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Tests\TestCase;

/**
 * Default-size selection for combos — the combo equivalent of the
 * default-variant behavior on product cards/detail pages. A size-required
 * combo must resolve a default size instead of forcing the shopper through
 * a "choose a size" detour.
 */
class ComboDefaultSizeTest extends TestCase
{
    use CreatesComboTestData;

    private function sizedCombo(array $labels): Combo
    {
        $attr = VariantAttribute::create(['name' => 'Size', 'display_type' => 'button', 'status' => 'active', 'sort_order' => 1]);
        $p = $this->makeProduct(['track_stock' => false]);
        foreach ($labels as $i => $label) {
            $value = VariantAttributeValue::create([
                'variant_attribute_id' => $attr->id, 'value' => $label,
                'is_active' => true, 'sort_order' => $i,
            ]);
            $variant = ProductVariant::create([
                'product_id' => $p->id, 'sku' => uniqid('v-'), 'sell_price' => 100, 'is_active' => true,
            ]);
            $variant->attributeValues()->attach($value->id);
        }

        $combo = Combo::create(['name' => 'Sized Combo', 'combo_price' => 900, 'is_active' => true, 'size_required' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1, 'sort_order' => 0]);

        return $combo;
    }

    public function test_size_data_endpoint_returns_the_size_list_for_a_size_required_combo(): void
    {
        $combo = $this->sizedCombo(['M', 'L']);

        $res = $this->getJson(route('storefront.combos.size-data', $combo->slug));

        $res->assertOk()->assertJson([
            'id'            => $combo->id,
            'size_required' => true,
        ]);
        $labels = array_column($res->json('sizes'), 'value');
        $this->assertEqualsCanonicalizing(['M', 'L'], $labels);
    }

    public function test_size_data_endpoint_404s_for_an_unknown_slug(): void
    {
        $this->getJson(route('storefront.combos.size-data', 'does-not-exist'))
            ->assertStatus(404);
    }

    public function test_non_size_required_combo_reports_no_sizes(): void
    {
        $p = $this->makeProduct();
        $combo = Combo::create(['name' => 'Plain Combo', 'combo_price' => 500, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1]);

        $res = $this->getJson(route('storefront.combos.size-data', $combo->slug));

        $res->assertOk()->assertJson(['size_required' => false, 'sizes' => []]);
    }
}
