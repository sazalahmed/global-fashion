<?php

namespace Modules\Marketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Marketing\Mail\CampaignMail;
use Modules\Marketing\Models\EmailCampaign;

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;

    public function __construct(
        protected int $campaignId
    ) {}

    public function handle(): void
    {
        $campaign = EmailCampaign::find($this->campaignId);
        if (!$campaign || $campaign->status === 'completed') {
            return;
        }

        $campaign->update(['status' => 'sending', 'sent_at' => now()]);

        $emails = $campaign->recipient_emails ?? [];
        $sent = 0;
        $failed = 0;

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new CampaignMail(
                    $campaign->subject,
                    $campaign->html_body,
                    $campaign->from_name,
                    $campaign->from_email
                ));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Campaign email failed', ['email' => $email, 'error' => $e->getMessage()]);
                $failed++;
            }
        }

        $campaign->update([
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'status'       => $failed === count($emails) ? 'failed' : 'completed',
        ]);
    }
}
