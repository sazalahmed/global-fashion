<?php

namespace Modules\Setting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Webhook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'url', 'secret', 'events', 'is_active',
        'failure_count', 'last_triggered_at', 'last_failed_at',
        'last_response', 'created_by',
    ];

    protected $casts = [
        'events'             => 'array',
        'is_active'          => 'boolean',
        'last_triggered_at'  => 'datetime',
        'last_failed_at'     => 'datetime',
    ];

    /**
     * Available webhook events.
     */
    public const AVAILABLE_EVENTS = [
        'sale.created'        => 'New Sale Created',
        'sale.updated'        => 'Sale Updated',
        'sale.cancelled'      => 'Sale Cancelled',
        'purchase.created'    => 'New Purchase Created',
        'order.created'       => 'New Ecommerce Order',
        'order.status_changed'=> 'Order Status Changed',
        'customer.created'    => 'New Customer Created',
        'product.created'     => 'New Product Created',
        'product.updated'     => 'Product Updated',
        'stock.low'           => 'Low Stock Alert',
        'payment.received'    => 'Payment Received',
        'delivery.created'    => 'Delivery Challan Created',
        'delivery.delivered'  => 'Delivery Completed',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->active()->whereJsonContains('events', $event);
    }

    /**
     * Dispatch this webhook for a given event with payload.
     */
    public function dispatch(string $event, array $payload): bool
    {
        $body = [
            'event'      => $event,
            'timestamp'  => now()->toIso8601String(),
            'data'       => $payload,
        ];

        $headers = [
            'Content-Type'     => 'application/json',
            'X-Webhook-Event'  => $event,
        ];

        if ($this->secret) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', json_encode($body), $this->secret);
        }

        try {
            $response = Http::timeout(10)->withHeaders($headers)->post($this->url, $body);

            $this->update([
                'last_triggered_at' => now(),
                'last_response'     => $response->status() . ': ' . substr($response->body(), 0, 500),
                'failure_count'     => $response->successful() ? 0 : $this->failure_count + 1,
                'last_failed_at'    => $response->successful() ? $this->last_failed_at : now(),
            ]);

            // Auto-disable after 10 consecutive failures
            if ($this->failure_count >= 10) {
                $this->update(['is_active' => false]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            $this->update([
                'last_triggered_at' => now(),
                'last_failed_at'    => now(),
                'last_response'     => 'Error: ' . substr($e->getMessage(), 0, 500),
                'failure_count'     => $this->failure_count + 1,
            ]);

            if ($this->failure_count >= 10) {
                $this->update(['is_active' => false]);
            }

            Log::warning('Webhook dispatch failed', [
                'webhook_id' => $this->id,
                'url'        => $this->url,
                'event'      => $event,
                'error'      => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Dispatch an event to all matching active webhooks.
     */
    public static function fire(string $event, array $payload = []): void
    {
        static::forEvent($event)->each(function (self $webhook) use ($event, $payload) {
            $webhook->dispatch($event, $payload);
        });
    }
}
