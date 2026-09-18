<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Modules\Variant\Models\ProductVariant;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Tests\TestCase;

class ComboCartTest extends TestCase
{
    use CreatesComboTestData;

    public function test_add_combo_to_cart_uses_server_price(): void
    {
        $p = $this->makeProduct(['sell_price' => 500]);
        $combo = Combo::create(['name' => 'C', 'combo_price' => 800, 'discount_type' => 'fixed', 'discount_value' => 100, 'is_active' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 2]);

        $res = $this->postJson(route('storefront.cart.add-combo'), ['combo_id' => $combo->id, 'quantity' => 1]);
        $res->assertOk()->assertJson(['success' => true]);

        $cart = session('cart');
        $line = $cart['combo:'.$combo->id];
        $this->assertSame('combo', $line['type']);
        $this->assertSame(700.0, (float) $line['price']); // 800 - 100, ignores any client price
        $this->assertCount(1, $line['components']);
        $this->assertSame(2, $line['components'][0]['quantity']);
    }

    public function test_size_required_combo_requires_a_valid_size_and_resolves_variants(): void
    {
        $attr = VariantAttribute::create(['name' => 'Size', 'display_type' => 'button', 'status' => 'active', 'sort_order' => 1]);
        $l = VariantAttributeValue::create(['variant_attribute_id' => $attr->id, 'value' => 'L', 'is_active' => true, 'sort_order' => 1])->id;
        $p = $this->makeProduct(['track_stock' => false]);
        $variant = ProductVariant::create(['product_id' => $p->id, 'sku' => uniqid('v-'), 'sell_price' => 100, 'is_active' => true]);
        $variant->attributeValues()->attach($l);

        $combo = Combo::create(['name' => 'Sized', 'combo_price' => 900, 'is_active' => true, 'size_required' => true]);
        $combo->items()->create(['product_id' => $p->id, 'quantity' => 1, 'sort_order' => 0]);

        // Missing size → rejected (422).
        $this->postJson(route('storefront.cart.add-combo'), ['combo_id' => $combo->id, 'quantity' => 1])
            ->assertStatus(422);

        // Valid size → added under combo:{id}:L with the resolved variant.
        $this->postJson(route('storefront.cart.add-combo'), ['combo_id' => $combo->id, 'quantity' => 1, 'combo_size' => 'L'])
            ->assertOk();

        $cart = session('cart', []);
        $this->assertArrayHasKey('combo:'.$combo->id.':L', $cart);
        $line = $cart['combo:'.$combo->id.':L'];
        $this->assertSame('L', $line['size']);
        $this->assertSame($variant->id, $line['components'][0]['variant_id']);
    }
}
