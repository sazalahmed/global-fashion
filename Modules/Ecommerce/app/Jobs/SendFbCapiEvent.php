<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFbCapiEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $pixelId,
        public string $token,
        public string $eventName,
        public string $eventId,
        public array $customData,
        public array $userData,
        public ?string $eventSourceUrl = null,
        public ?string $testEventCode = null,
    ) {}

    public function handle(): void
    {
        $payload = [
            'data' => [[
                'event_name'       => $this->eventName,
                'event_time'       => time(),
                'event_id'         => $this->eventId,
                'action_source'    => 'website',
                'event_source_url' => $this->eventSourceUrl,
                'user_data'        => $this->userData,
                'custom_data'      => $this->customData,
            ]],
        ];
        if ($this->testEventCode) {
            $payload['test_event_code'] = $this->testEventCode;
        }

        $res = Http::asJson()->post(
            "https://graph.facebook.com/v19.0/{$this->pixelId}/events?access_token={$this->token}",
            $payload
        );

        if ($res->failed()) {
            Log::warning('FB CAPI send failed', ['status' => $res->status(), 'event_id' => $this->eventId]);
        }
    }
}
