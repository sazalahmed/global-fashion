<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Ecommerce\Jobs\SendFbCapiEvent;
use Tests\TestCase;

class SendFbCapiEventTest extends TestCase
{
    public function test_posts_purchase_payload_to_graph_api(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);

        (new SendFbCapiEvent(
            pixelId: '1234567890',
            token: 'TOKEN',
            eventName: 'Purchase',
            eventId: 'purchase.ORD-1',
            customData: ['value' => 1500.0, 'currency' => 'BDT', 'order_id' => 'ORD-1'],
            userData: ['em' => 'hashed', 'fbp' => 'fb.1.1.2'],
            eventSourceUrl: 'https://shop.test/checkout/success/ORD-1',
            testEventCode: 'TEST123'
        ))->handle();

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($request->url(), '/1234567890/events')
                && $body['data'][0]['event_name'] === 'Purchase'
                && $body['data'][0]['event_id'] === 'purchase.ORD-1'
                && $body['data'][0]['user_data']['em'] === 'hashed'
                && $body['data'][0]['custom_data']['currency'] === 'BDT'
                && $body['test_event_code'] === 'TEST123';
        });
    }
}
