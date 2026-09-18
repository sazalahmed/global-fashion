<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\EcommerceOrderItem;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Ecommerce\Tests\Concerns\CreatesComboTestData;
use Tests\TestCase;

class ComboCheckoutTest extends TestCase
{
    use CreatesComboTestData;

    public function test_combo_line_expands_into_component_order_items(): void
    {
        $a = $this->makeProduct(['sell_price' => 500]);
        $b = $this->makeProduct(['sell_price' => 300]);
        $combo = Combo::create(['name' => 'Combo', 'combo_price' => 1000, 'discount_type' => 'none', 'discount_value' => 0, 'is_active' => true]);
        $combo->items()->create(['product_id' => $a->id, 'quantity' => 1]);
        $combo->items()->create(['product_id' => $b->id, 'quantity' => 2]);

        $cart = ['combo:'.$combo->id => [
            'type' => 'combo', 'combo_id' => $combo->id, 'name' => 'Combo', 'price' => 1000.0, 'quantity' => 1,
            'components' => [
                ['product_id' => $a->id, 'variant_id' => null, 'quantity' => 1, 'name' => 'A', 'variant_name' => null],
                ['product_id' => $b->id, 'variant_id' => null, 'quantity' => 2, 'name' => 'B', 'variant_name' => null],
            ],
        ]];

        $svc = app(StorefrontService::class);
        $order = $svc->createOrder(
            ['customer_name' => 'T', 'customer_phone' => '01712345678', 'shipping_address' => 'X', 'payment_method' => 'cod'],
            $cart, 1000.0, 0.0, 0.0
        );

        $items = EcommerceOrderItem::where('ecommerce_order_id', $order->id)->get();
        $this->assertCount(2, $items); // expanded per component
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $items->pluck('product_id')->all());
        $this->assertSame(1000.0, round($items->sum('subtotal'), 2)); // prices sum to the combo total
        $this->assertSame($combo->id, (int) $items->first()->combo_id);
    }

    public function test_combo_with_long_names_does_not_overflow_sale_item_variant_label(): void
    {
        // Long combo + product names previously produced "{combo}: {product}" in
        // sale_items.variant_label (varchar 80) → SQLSTATE[22001] on checkout.
        $a = $this->makeProduct(['name' => 'Remi Cotton Premium Short Sleeve Shirt - (Sky blue)', 'sell_price' => 500]);
        $combo = Combo::create([
            'name' => 'Remi Cotton Premium Short Sleeve Shirt - Combo-02-(Sky & Off-white)',
            'combo_price' => 1000, 'is_active' => true,
        ]);
        $combo->items()->create(['product_id' => $a->id, 'quantity' => 1]);

        $cart = ['combo:'.$combo->id => [
            'type' => 'combo', 'combo_id' => $combo->id, 'name' => $combo->name, 'price' => 1000.0, 'quantity' => 1,
            'size' => null,
            'components' => [
                ['product_id' => $a->id, 'variant_id' => null, 'quantity' => 1, 'name' => $a->name, 'variant_name' => null],
            ],
        ]];

        // Must not throw (the overflow was here).
        $order = app(StorefrontService::class)->createOrder(
            ['customer_name' => 'T', 'customer_phone' => '01712345678', 'shipping_address' => 'X', 'payment_method' => 'cod'],
            $cart, 1000.0, 0.0, 0.0
        );

        $this->assertNotNull($order->id);
        // The mirrored sale item keeps variant_label within the column limit.
        $saleItem = \Modules\Sale\Models\SaleItem::where('combo_id', $combo->id)->first();
        $this->assertNotNull($saleItem);
        $this->assertLessThanOrEqual(80, strlen((string) $saleItem->variant_label));
    }
}
