<?php

namespace Modules\Marketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Marketing\Contracts\SmsGatewayInterface;
use Modules\Marketing\Models\SmsCampaign;

class SendSmsCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;

    public function __construct(
        protected int $campaignId
    ) {}

    public function handle(SmsGatewayInterface $gateway): void
    {
        $campaign = SmsCampaign::find($this->campaignId);
        if (!$campaign || $campaign->status === 'completed') {
            return;
        }

        $campaign->update(['status' => 'sending', 'sent_at' => now()]);

        $numbers = $campaign->recipient_numbers ?? [];
        $message = $campaign->message;

        try {
            $result = $gateway->sendBulk($numbers, $message);

            $campaign->update([
                'sent_count'   => $result['sent'],
                'failed_count' => $result['failed'],
                'status'       => $result['failed'] === count($numbers) ? 'failed' : 'completed',
            ]);
        } catch (\Throwable $e) {
            Log::error('SMS Campaign failed', [
                'campaign_id' => $this->campaignId,
                'error'       => $e->getMessage(),
            ]);

            $campaign->update(['status' => 'failed']);
        }
    }
}
