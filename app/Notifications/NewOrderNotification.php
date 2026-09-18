<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $orderNumber,
        private readonly float $total,
        private readonly string $customerName,
        private readonly ?int $orderId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_order',
            'icon' => 'fa-cart-shopping',
            'color' => 'primary',
            'title' => 'New Ecommerce Order',
            'message' => "Order {$this->orderNumber} — BDT " . number_format($this->total) . " from {$this->customerName}",
            'url' => route('sales.index', ['source' => 'ecommerce']),
        ];
    }
}
