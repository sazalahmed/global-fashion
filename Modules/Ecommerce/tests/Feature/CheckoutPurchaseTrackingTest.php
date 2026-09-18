<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\Bus;
use Modules\Category\Database\Factories\CategoryFactory;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Models\ShippingZone;
use Modules\Product\Database\Factories\ProductFactory;
use Modules\Setting\Models\Setting;
use Tests\TestCase;

class CheckoutPurchaseTrackingTest extends TestCase
{
    public function test_placing_an_order_sends_capi_at_placement_and_flashes_purchase_order(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');
        Setting::set('tracking', 'ga4_measurement_id', 'G-ABC123');
        Setting::set('tracking', 'ga4_api_secret', 'SECRET');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $category = CategoryFactory::new()->create();
        $product = ProductFactory::new()->create([
            'status' => 'active', 'category_id' => $category->id, 'sell_price' => 500.00,
        ]);
        $zone = ShippingZone::create(['name' => 'Dhaka', 'flat_rate' => 60, 'is_active' => true]);

        $cart = [
            (string) $product->id => [
                'product_id'         => $product->id,
                'variant_id'         => null,
                'variant_name'       => null,
                'variant_attributes' => [],
                'name'               => $product->name,
                'slug'               => $product->slug,
                'price'              => 500.00,
                'sell_price'         => 500.00,
                'image'              => null,
                'quantity'           => 1,
                'sku'                => $product->sku,
            ],
        ];

        $res = $this->withSession(['cart' => $cart])->post(route('storefront.checkout.process'), [
            'customer_name'    => 'Test Guest',
            'customer_phone'   => '01712345678',
            'address'          => 'House 1, Road 2, Dhanmondi',
            'shipping_zone_id' => $zone->id,
            'payment_method'   => 'cod',
        ]);

        $order = EcommerceOrder::latest('id')->first();
        $this->assertNotNull($order, 'Checkout did not create an order.');

        $res->assertRedirect(route('storefront.checkout.success', $order->order_number));
        $res->assertSessionHas('purchase_order', $order->order_number);

        // CAPI dispatched at placement (deferred callbacks run at kernel
        // terminate, which the HTTP test harness invokes); GA4 MP must not be.
        Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class, function ($job) use ($order) {
            return $job->eventId === 'purchase.' . $order->order_number
                && $job->eventName === 'Purchase';
        });
        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class);
        $this->assertNotNull($order->fresh()->purchase_reported_at);
    }
}
