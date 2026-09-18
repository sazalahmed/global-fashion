<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $productName,
        private readonly int $currentStock,
        private readonly int $reorderLevel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'icon' => 'fa-triangle-exclamation',
            'color' => 'warning',
            'title' => 'Low Stock Alert',
            'message' => "{$this->productName} has only {$this->currentStock} units left (reorder level: {$this->reorderLevel})",
        ];
    }
}
