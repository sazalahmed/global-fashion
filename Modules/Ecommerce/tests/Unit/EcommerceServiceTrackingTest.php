<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Support\Facades\Bus;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Services\EcommerceService;
use Modules\Setting\Models\Setting;
use Tests\TestCase;

class EcommerceServiceTrackingTest extends TestCase
{
    public function test_confirming_an_order_no_longer_reports_purchase(): void
    {
        Bus::fake();
        Setting::set('tracking', 'fbpixel_id', '1234567890');
        Setting::set('tracking', 'fbpixel_access_token', 'TOKEN');

        $order = EcommerceOrder::create([
            'order_number' => 'ORD-ST-' . uniqid(), 'customer_name' => 'A',
            'customer_phone' => '01712345678', 'shipping_address' => 'x',
            'billing_address' => 'x', 'status' => 'pending',
            'subtotal' => 500, 'grand_total' => 500,
        ]);

        app(EcommerceService::class)->updateOrderStatus($order, 'confirmed');

        // Purchase is reported at placement (checkout) — a status change must
        // neither dispatch CAPI nor claim the once-guard.
        Bus::assertNotDispatched(\Modules\Ecommerce\Jobs\SendFbCapiEvent::class);
        $this->assertNull($order->fresh()->purchase_reported_at);
    }
}
