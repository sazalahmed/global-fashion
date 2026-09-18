<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendGa4McEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $measurementId,
        public string $apiSecret,
        public string $clientId,
        public array $params,
        public string $eventName = 'purchase',
    ) {}

    public function handle(): void
    {
        // Jobs queued before this property existed deserialize without it, so
        // reading $this->eventName directly would throw on the uninitialized
        // typed property. isset() on an uninitialized typed property returns
        // false without throwing, so this is a safe deploy-window fallback.
        $eventName = isset($this->eventName) ? $this->eventName : 'purchase';

        $res = Http::asJson()->post(
            "https://www.google-analytics.com/mp/collect?measurement_id={$this->measurementId}&api_secret={$this->apiSecret}",
            [
                'client_id' => $this->clientId,
                'events'    => [[ 'name' => $eventName, 'params' => $this->params ]],
            ]
        );

        if ($res->failed()) {
            Log::warning('GA4 MP send failed', ['status' => $res->status(), 'tid' => $this->params['transaction_id'] ?? null]);
        }
    }
}
