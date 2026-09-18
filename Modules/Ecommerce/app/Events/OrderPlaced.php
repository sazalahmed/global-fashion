<?php

namespace Modules\Ecommerce\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\EcommerceOrder;

/**
 * Broadcast when a new storefront order is placed.
 * Fans out on the public 'admin.notifications' channel so the admin panel
 * notification bell can light up in real time.
 */
class OrderPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public EcommerceOrder $order) {}

    public function broadcastOn(): array
    {
        return [new Channel('admin.notifications')];
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->order->id,
            'order_number'   => $this->order->order_number,
            'customer_name'  => $this->order->customer_name,
            'customer_phone' => $this->order->customer_phone,
            'grand_total'    => (float) $this->order->grand_total,
            'item_count'     => $this->order->items()->count(),
            'created_at'     => optional($this->order->created_at)->toIso8601String(),
            'url'            => $this->order->sale_id
                ? route('sales.show', $this->order->sale_id)
                : route('sales.index', ['source' => 'ecommerce']),
        ];
    }
}
