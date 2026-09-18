<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSaleNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $invoiceNumber,
        private readonly float $grandTotal,
        private readonly string $source,
        private readonly ?int $saleId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_sale',
            'icon' => 'fa-chart-line',
            'color' => 'success',
            'title' => 'New Sale',
            // Through the model's label rather than the raw column, which is
            // why this read "(ecommerce)" while the sale page said otherwise.
            'message' => "Sale {$this->invoiceNumber} — BDT " . number_format($this->grandTotal)
                . ' (' . \Modules\Sale\Models\Sale::sourceLabel($this->source) . ')',
            'url' => $this->saleId ? route('sales.show', $this->saleId) : null,
        ];
    }
}
