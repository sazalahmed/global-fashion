<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Ecommerce\Jobs\SendGa4McEvent;
use Tests\TestCase;

class SendGa4McEventTest extends TestCase
{
    public function test_posts_purchase_to_measurement_protocol(): void
    {
        Http::fake(['www.google-analytics.com/*' => Http::response('', 204)]);

        (new SendGa4McEvent(
            measurementId: 'G-ABC123',
            apiSecret: 'SECRET',
            clientId: '111.222',
            params: ['transaction_id' => 'ORD-1', 'value' => 1500.0, 'currency' => 'BDT', 'items' => []],
        ))->handle();

        Http::assertSent(function ($request) {
            $body = $request->data();
            return str_contains($request->url(), 'measurement_id=G-ABC123')
                && str_contains($request->url(), 'api_secret=SECRET')
                && $body['client_id'] === '111.222'
                && $body['events'][0]['name'] === 'purchase'
                && $body['events'][0]['params']['transaction_id'] === 'ORD-1';
        });
    }
}
