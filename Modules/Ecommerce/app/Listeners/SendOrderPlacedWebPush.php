<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Core\Services\WebPushService;
use Modules\Ecommerce\Events\OrderPlaced;

/**
 * Fires alongside the Pusher broadcast — sends a Web Push to every admin so the
 * notification arrives even when the PWA / browser is closed.
 *
 * Runs SYNCHRONOUSLY (no ShouldQueue) because most installs of this app don't
 * have a queue worker running, so a queued listener would never fire. Web Push
 * delivery to a handful of admin subscriptions takes <1s and is wrapped in a
 * try/catch so a push failure can't block the order. Re-add ShouldQueue if/when
 * a queue worker is actually deployed.
 */
class SendOrderPlacedWebPush
{
    public function __construct(private WebPushService $webPush)
    {
    }

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        $payload = [
            'title' => 'New online order',
            'body'  => ($order->customer_name ?: 'Customer') . ' — ' . currency_symbol() . ' ' . number_format((float) $order->grand_total, 2),
            'tag'   => 'order-' . $order->id,
            'url'   => $order->sale_id
                ? route('sales.show', $order->sale_id)
                : route('sales.index', ['source' => 'ecommerce']),
        ];

        try {
            $this->webPush->sendToRole('Super Admin', $payload);
        } catch (\Throwable $e) {
            Log::warning('Web Push for OrderPlaced failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
        }
    }
}
