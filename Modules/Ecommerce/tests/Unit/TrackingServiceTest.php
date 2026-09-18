<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Services\TrackingService;
use Modules\Setting\Models\Setting;
use Tests\TestCase;

class TrackingServiceTest extends TestCase
{
    private function service(): TrackingService
    {
        return app(TrackingService::class);
    }

    public function test_blocked_risk_levels_defaults_to_high_and_critical(): void
    {
        Setting::where('group', 'tracking')->where('key', 'purchase_block_risk_levels')->delete();
        $this->assertSame(['high', 'critical'], array_values($this->service()->blockedRiskLevels()));
    }

    public function test_should_report_purchase_blocks_critical_risk(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');
        $order = new EcommerceOrder(['fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 30]]]);
        $this->assertFalse($this->service()->shouldReportPurchase($order));
    }

    public function test_should_report_purchase_allows_low_risk(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');
        $order = new EcommerceOrder(['fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]]]);
        $this->assertTrue($this->service()->shouldReportPurchase($order));
    }

    public function test_should_report_purchase_allows_new_customer(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');
        $order = new EcommerceOrder(['fraud_report' => ['aggregate' => ['total_deliveries' => 0]]]);
        $this->assertTrue($this->service()->shouldReportPurchase($order));
    }

    public function test_items_from_order_use_numeric_unit_price(): void
    {
        $order = new EcommerceOrder();
        $order->setRelation('items', collect([
            new \Modules\Ecommerce\Models\EcommerceOrderItem([
                'product_id' => 7, 'product_name' => 'Tee', 'quantity' => 2, 'unit_price' => 499.00,
            ]),
        ]));

        $items = app(TrackingService::class)->itemsFromOrder($order);

        $this->assertSame('7', $items[0]['item_id']);
        $this->assertSame('Tee', $items[0]['item_name']);
        $this->assertSame(499.0, $items[0]['price']);
        $this->assertSame(2, $items[0]['quantity']);
        $this->assertArrayNotHasKey('item_variant', $items[0]);
    }

    public function test_items_from_order_include_variant_name_when_set(): void
    {
        $order = new EcommerceOrder();
        $order->setRelation('items', collect([
            new \Modules\Ecommerce\Models\EcommerceOrderItem([
                'product_id' => 7, 'product_name' => 'Tee', 'variant_name' => 'XL — Navy',
                'quantity' => 1, 'unit_price' => 499.00,
            ]),
        ]));

        $items = app(TrackingService::class)->itemsFromOrder($order);

        $this->assertSame('XL — Navy', $items[0]['item_variant']);
    }

    public function test_item_from_product_includes_discount_when_discounted(): void
    {
        $category = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $product  = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id'    => $category->id,
            'sell_price'     => 500,
            'discount_type'  => 'fixed',
            'discount_value' => 100,
        ]);

        $item = app(TrackingService::class)->itemFromProduct($product->fresh());

        $this->assertSame(400.0, $item['price']);
        $this->assertSame(100.0, $item['discount']);

        $plain = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $category->id,
            'sell_price'  => 500,
        ]);

        $this->assertArrayNotHasKey('discount', app(TrackingService::class)->itemFromProduct($plain->fresh()));
    }

    public function test_customer_type_new_then_returning(): void
    {
        $phone = '017' . random_int(10000000, 99999999);

        $first = EcommerceOrder::create([
            'order_number' => 'ORD-CT1-' . uniqid(), 'customer_name' => 'A', 'customer_phone' => $phone,
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'confirmed',
            'subtotal' => 500, 'grand_total' => 500,
        ]);

        $this->assertSame('new', $this->service()->customerType($first));

        $second = EcommerceOrder::create([
            'order_number' => 'ORD-CT2-' . uniqid(), 'customer_name' => 'A', 'customer_phone' => $phone,
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'confirmed',
            'subtotal' => 700, 'grand_total' => 700,
        ]);

        $this->assertSame('returning', $this->service()->customerType($second));
    }

    public function test_contents_from_items_maps_to_meta_shape(): void
    {
        $contents = app(TrackingService::class)->contentsFromItems([
            ['item_id' => '7', 'item_name' => 'Tee', 'price' => 499.0, 'quantity' => 2],
        ]);

        $this->assertSame([['id' => '7', 'quantity' => 2, 'item_price' => 499.0]], $contents);
    }

    public function test_capture_client_identifiers_derives_fbc_from_fbclid(): void
    {
        $request = Request::create('/checkout?fbclid=abc123', 'POST');
        $request->headers->set('User-Agent', 'PHPUnit-UA');

        $data = app(TrackingService::class)->captureClientIdentifiers($request);

        $this->assertStringStartsWith('fb.1.', $data['fbc']);
        $this->assertStringEndsWith('.abc123', $data['fbc']);
        $this->assertSame('PHPUnit-UA', $data['ua']);
    }

    public function test_hashed_user_data_hashes_email_and_phone(): void
    {
        $order = new EcommerceOrder([
            'customer_email' => 'Test@Example.com',
            'customer_phone' => '01712-345678',
        ]);
        $order->tracking_data = ['fbp' => 'fb.1.1.2', 'ip' => '1.2.3.4'];

        $ud = app(TrackingService::class)->hashedUserData($order);

        $this->assertSame(hash('sha256', 'test@example.com'), $ud['em']);
        $this->assertSame(hash('sha256', '01712345678'), $ud['ph']);
        $this->assertSame('fb.1.1.2', $ud['fbp']);
        $this->assertSame('1.2.3.4', $ud['client_ip_address']);
    }

    public function test_order_casts_tracking_data_to_array(): void
    {
        $order = new EcommerceOrder();
        $order->tracking_data = ['fbp' => 'x'];
        $this->assertIsArray($order->tracking_data);
        $this->assertArrayHasKey('purchase_reported_at', $order->getCasts());
    }

    public function test_report_purchase_dispatches_only_capi_for_trusted_order(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');
        Setting::set('tracking', 'ga4_measurement_id', 'G-ABC123');
        Setting::set('tracking', 'ga4_api_secret', 'SECRET');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-T1', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'confirmed',
            'subtotal' => 1500, 'grand_total' => 1500,
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]],
        ]);

        app(TrackingService::class)->reportPurchase($order);

        Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class, function ($job) {
            return $job->eventId === 'purchase.ORD-T1' && $job->eventName === 'Purchase';
        });
        // GA4 purchase is browser-only now — MP must NOT be dispatched.
        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class);
        $this->assertNotNull($order->fresh()->purchase_reported_at);
    }

    public function test_report_purchase_suppressed_for_high_risk_but_guard_set(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-T2', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'confirmed',
            'subtotal' => 1500, 'grand_total' => 1500,
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 20]],
        ]);

        app(TrackingService::class)->reportPurchase($order);

        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
        $this->assertNotNull($order->fresh()->purchase_reported_at);
    }

    public function test_item_from_product_uses_brand_name_string(): void
    {
        $brand    = \Modules\Brand\Database\Factories\BrandFactory::new()->create(['name' => 'Acme']);
        $category = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $product  = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'brand_id'    => $brand->id,
            'category_id' => $category->id,
            'sell_price'  => 500,
        ]);

        $item = app(\Modules\Ecommerce\Services\TrackingService::class)->itemFromProduct($product->fresh());

        $this->assertSame('Acme', $item['item_brand']);
        $this->assertIsString($item['item_brand']);
    }

    public function test_report_purchase_is_idempotent(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-T3', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'confirmed',
            'subtotal' => 1500, 'grand_total' => 1500, 'purchase_reported_at' => now(),
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]],
        ]);

        app(TrackingService::class)->reportPurchase($order);

        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
    }

    public function test_report_refund_dispatches_ga4_refund_for_reported_order(): void
    {
        Bus::fake();
        Setting::set('tracking', 'ga4_measurement_id', 'G-ABC123');
        Setting::set('tracking', 'ga4_api_secret', 'SECRET');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-R1', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'cancelled',
            'subtotal' => 1500, 'grand_total' => 1500, 'purchase_reported_at' => now(),
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]],
        ]);

        app(TrackingService::class)->reportRefund($order);

        Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class, function ($job) {
            return $job->eventName === 'refund'
                && $job->params['transaction_id'] === 'ORD-R1';
        });
        $this->assertNotNull($order->fresh()->refund_reported_at);

        // Second call must not dispatch again.
        Bus::fake();
        app(TrackingService::class)->reportRefund($order->fresh());
        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class);
    }

    public function test_report_refund_skips_unreported_or_suppressed_orders(): void
    {
        Bus::fake();
        Setting::set('tracking', 'ga4_measurement_id', 'G-ABC123');
        Setting::set('tracking', 'ga4_api_secret', 'SECRET');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        // Never purchase-reported → no refund.
        $unreported = EcommerceOrder::create([
            'order_number' => 'ORD-R2', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'cancelled',
            'subtotal' => 1500, 'grand_total' => 1500,
        ]);
        app(TrackingService::class)->reportRefund($unreported);

        // Purchase was suppressed by the fraud gate → no refund either.
        $suppressed = EcommerceOrder::create([
            'order_number' => 'ORD-R3', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'cancelled',
            'subtotal' => 1500, 'grand_total' => 1500, 'purchase_reported_at' => now(),
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 20]],
        ]);
        app(TrackingService::class)->reportRefund($suppressed);

        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendGa4McEvent::class);
        // Guard still set on the suppressed order — decision is final.
        $this->assertNotNull($suppressed->fresh()->refund_reported_at);
    }

    public function test_browser_purchase_payload_contains_ga4_and_meta_shapes(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        // ecommerce_order_items.product_id is FK-constrained — persist a real product.
        $category = \Modules\Category\Database\Factories\CategoryFactory::new()->create();
        $product = \Modules\Product\Database\Factories\ProductFactory::new()->create([
            'category_id' => $category->id, 'sell_price' => 499.00,
        ]);

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-BP1', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'pending',
            'subtotal' => 998, 'grand_total' => 1058, 'shipping_charge' => 60,
            'coupon_code' => 'SAVE10',
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 95]],
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'product_name' => 'Tee', 'quantity' => 2,
            'unit_price' => 499.00, 'subtotal' => 998.00,
        ]);

        $payload = $this->service()->browserPurchasePayload($order);

        $this->assertSame('ORD-BP1', $payload['ga']['transaction_id']);
        $this->assertSame(1058.0, $payload['ga']['value']);
        $this->assertSame('BDT', $payload['ga']['currency']);
        $this->assertSame(60.0, $payload['ga']['shipping']);
        $this->assertSame('SAVE10', $payload['ga']['coupon']);
        $this->assertSame('new', $payload['ga']['customer_type']);
        $this->assertSame((string) $product->id, $payload['ga']['items'][0]['item_id']);

        $this->assertSame('purchase.ORD-BP1', $payload['event_id']);
        $this->assertSame('product', $payload['fb']['content_type']);
        $this->assertSame(2, $payload['fb']['num_items']);
        $this->assertSame('ORD-BP1', $payload['fb']['order_id']);
        $this->assertSame([['id' => (string) $product->id, 'quantity' => 2, 'item_price' => 499.0]], $payload['fb']['contents']);
    }

    public function test_browser_purchase_payload_includes_user_data(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');
        \Illuminate\Support\Facades\DB::table('districts')->updateOrInsert(
            ['district_name' => 'Dhaka'],
            ['bn_name' => 'ঢাকা', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
        );

        $zone = \Modules\Ecommerce\Models\ShippingZone::create([
            'name' => 'Inside Dhaka', 'flat_rate' => 60, 'is_active' => true,
        ]);

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-UD1', 'customer_name' => 'Rahim Uddin Khan',
            'customer_email' => ' Rahim@Example.com ', 'customer_phone' => '01712-345678',
            'shipping_address' => 'House 5, Road 2, Dhanmondi, Dhaka',
            'billing_address' => 'House 5, Road 2, Dhanmondi, Dhaka',
            'shipping_zone_id' => $zone->id, 'payment_method' => 'cod',
            'notes' => 'Alt: 01911-222333 | Please deliver after 5pm',
            'status' => 'pending', 'subtotal' => 500, 'grand_total' => 500,
        ]);

        $payload = $this->service()->browserPurchasePayload($order);

        $this->assertSame('rahim@example.com', $payload['user']['email_address']);
        $this->assertSame('+8801712345678', $payload['user']['phone_number']); // E.164
        $this->assertSame('cod', $payload['ga']['payment_type']);

        // Full address in Google's enhanced-conversions shape — exact key order.
        $this->assertSame([
            'first_name' => 'Rahim',
            'last_name'  => 'Uddin Khan',
            'street'     => 'House 5, Road 2, Dhanmondi, Dhaka',
            'city'       => 'Dhaka',
            'country'    => 'BD',
        ], $payload['user']['address']);

        // Plain customer block for GTM-side mapping (dataLayer only) — carries
        // everything the shopper typed at checkout.
        $this->assertSame([
            'name'             => 'Rahim Uddin Khan',
            'email'            => 'rahim@example.com',
            'phone'            => '+8801712345678',
            'shipping_address' => 'House 5, Road 2, Dhanmondi, Dhaka',
            'delivery_zone'    => 'Inside Dhaka',
            'payment_method'   => 'cod',
            'note'             => 'Please deliver after 5pm',
        ], $payload['customer']);
    }

    public function test_browser_purchase_payload_user_data_skips_missing_email(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-UD2', 'customer_name' => 'Karim',
            'customer_phone' => '8801912345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'pending',
            'subtotal' => 500, 'grand_total' => 500,
        ]);

        $payload = $this->service()->browserPurchasePayload($order);

        $this->assertArrayNotHasKey('email_address', $payload['user']);
        $this->assertSame('+8801912345678', $payload['user']['phone_number']);
        $this->assertSame('Karim', $payload['user']['address']['first_name']);
        $this->assertArrayNotHasKey('last_name', $payload['user']['address']);
        // No district match in "x" → no city; unknown keys never invented.
        $this->assertArrayNotHasKey('city', $payload['user']['address']);
        $this->assertArrayNotHasKey('email', $payload['customer']);
        $this->assertSame('x', $payload['customer']['shipping_address']);
        // No zone / note on this order → keys absent, never null. alt_phone
        // is never sent (no ad-platform consumer for a secondary phone).
        $this->assertArrayNotHasKey('alt_phone', $payload['customer']);
        $this->assertArrayNotHasKey('delivery_zone', $payload['customer']);
        $this->assertArrayNotHasKey('note', $payload['customer']);
    }

    public function test_browser_purchase_payload_null_for_blocked_risk(): void
    {
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-BP2', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'pending',
            'subtotal' => 1500, 'grand_total' => 1500,
            'fraud_report' => ['aggregate' => ['total_deliveries' => 10, 'success_ratio' => 20]],
        ]);

        $this->assertNull($this->service()->browserPurchasePayload($order));
    }

    public function test_report_purchase_at_placement_captures_identifiers_and_defers_capi(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');
        Setting::set('tracking', 'purchase_block_risk_levels', 'high,critical');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-PL1', 'customer_name' => 'A', 'customer_phone' => '01712345678',
            'shipping_address' => 'x', 'billing_address' => 'x', 'status' => 'pending',
            'subtotal' => 1500, 'grand_total' => 1500,
        ]);

        $request = Request::create('/checkout/process?fbclid=abc123', 'POST');
        $request->headers->set('User-Agent', 'PHPUnit-UA');

        $this->service()->reportPurchaseAtPlacement($order, $request);

        // Not dispatched yet — the send is deferred until after the response.
        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
        $this->assertSame('PHPUnit-UA', $order->fresh()->tracking_data['ua']);

        // Simulate end-of-request: run the deferred callbacks.
        app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();

        Bus::assertDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class, function ($job) {
            return $job->eventId === 'purchase.ORD-PL1';
        });
        $this->assertNotNull($order->fresh()->purchase_reported_at);
    }
}
