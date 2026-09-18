<?php

namespace Modules\Marketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Marketing\Models\EmailCampaign;
use Modules\Marketing\Models\LoyaltyTransaction;
use Modules\Marketing\Models\SmsCampaign;
use Modules\Marketing\Jobs\SendSmsCampaignJob;
use Modules\Marketing\Jobs\SendEmailCampaignJob;

class MarketingService
{
    // SMS Campaigns
    public function listSmsCampaigns(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return SmsCampaign::with('creator')
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createSmsCampaign(array $data): SmsCampaign
    {
        $data['created_by'] = Auth::id();

        if ($data['audience'] === 'all') {
            $numbers = DB::table('customers')
                ->whereNotNull('phone')
                ->pluck('phone')
                ->toArray();
            $data['recipient_numbers'] = $numbers;
            $data['total_recipients'] = count($numbers);
        } elseif ($data['audience'] === 'custom' && !empty($data['recipient_numbers'])) {
            if (is_string($data['recipient_numbers'])) {
                $data['recipient_numbers'] = array_filter(array_map('trim', explode("\n", $data['recipient_numbers'])));
            }
            $data['total_recipients'] = count($data['recipient_numbers']);
        }

        $data['total_cost'] = ($data['total_recipients'] ?? 0) * ($data['cost_per_sms'] ?? 0.25);

        $campaign = SmsCampaign::create($data);

        // Dispatch the send job
        if (!empty($campaign->recipient_numbers)) {
            $delay = $campaign->scheduled_at && $campaign->scheduled_at->isFuture()
                ? $campaign->scheduled_at
                : null;

            if ($delay) {
                SendSmsCampaignJob::dispatch($campaign->id)->delay($delay);
                $campaign->update(['status' => 'scheduled']);
            } else {
                SendSmsCampaignJob::dispatch($campaign->id);
            }
        }

        return $campaign;
    }

    public function findSmsCampaign(int $id): SmsCampaign
    {
        return SmsCampaign::with('creator')->findOrFail($id);
    }

    /**
     * Update a draft SMS campaign and recompute its recipients/cost.
     */
    public function updateSmsCampaign(SmsCampaign $campaign, array $data): SmsCampaign
    {
        if ($data['audience'] === 'all') {
            $numbers = DB::table('customers')->whereNotNull('phone')->pluck('phone')->toArray();
            $data['recipient_numbers'] = $numbers;
            $data['total_recipients'] = count($numbers);
        } elseif ($data['audience'] === 'custom' && !empty($data['recipient_numbers'])) {
            if (is_string($data['recipient_numbers'])) {
                $data['recipient_numbers'] = array_filter(array_map('trim', preg_split('/[\n,]+/', $data['recipient_numbers'])));
            }
            $data['total_recipients'] = count($data['recipient_numbers']);
        }

        $data['total_cost'] = ($data['total_recipients'] ?? $campaign->total_recipients) * ($data['cost_per_sms'] ?? $campaign->cost_per_sms ?? 0.25);

        $campaign->update($data);

        return $campaign;
    }

    /**
     * Send a campaign immediately by dispatching the send job. Guards against
     * resending an already completed/sending campaign.
     */
    public function sendSmsCampaignNow(SmsCampaign $campaign): void
    {
        if (in_array($campaign->status, ['sending', 'completed'], true)) {
            return;
        }

        $campaign->update(['status' => 'sending', 'scheduled_at' => null]);
        SendSmsCampaignJob::dispatch($campaign->id);
    }

    /**
     * Clone a campaign as a fresh draft (counts/timestamps reset).
     */
    public function duplicateSmsCampaign(SmsCampaign $campaign): SmsCampaign
    {
        return SmsCampaign::create([
            'name'              => $campaign->name . ' (Copy)',
            'gateway'           => $campaign->gateway,
            'message'           => $campaign->message,
            'audience'          => $campaign->audience,
            'recipient_numbers' => $campaign->recipient_numbers,
            'total_recipients'  => $campaign->total_recipients,
            'sent_count'        => 0,
            'failed_count'      => 0,
            'cost_per_sms'      => $campaign->cost_per_sms,
            'total_cost'        => $campaign->total_cost,
            'status'            => 'draft',
            'scheduled_at'      => null,
            'sent_at'           => null,
            'created_by'        => Auth::id(),
        ]);
    }

    /**
     * Soft-delete a campaign.
     */
    public function deleteSmsCampaign(SmsCampaign $campaign): void
    {
        $campaign->delete();
    }

    // Email Campaigns
    public function listEmailCampaigns(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return EmailCampaign::with('creator')
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createEmailCampaign(array $data): EmailCampaign
    {
        $data['created_by'] = Auth::id();

        if ($data['audience'] === 'all') {
            $emails = DB::table('customers')
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();
            $data['recipient_emails'] = $emails;
            $data['total_recipients'] = count($emails);
        } elseif ($data['audience'] === 'custom' && !empty($data['recipient_emails'])) {
            if (is_string($data['recipient_emails'])) {
                $data['recipient_emails'] = array_filter(array_map('trim', explode("\n", $data['recipient_emails'])));
            }
            $data['total_recipients'] = count($data['recipient_emails']);
        }

        $campaign = EmailCampaign::create($data);

        // Dispatch the send job
        if (!empty($campaign->recipient_emails)) {
            $delay = $campaign->scheduled_at && $campaign->scheduled_at->isFuture()
                ? $campaign->scheduled_at
                : null;

            if ($delay) {
                SendEmailCampaignJob::dispatch($campaign->id)->delay($delay);
                $campaign->update(['status' => 'scheduled']);
            } else {
                SendEmailCampaignJob::dispatch($campaign->id);
            }
        }

        return $campaign;
    }

    // Loyalty
    public function getLoyaltyStats(): array
    {
        return [
            'total_points_earned' => LoyaltyTransaction::where('type', 'earn')->sum('points'),
            'total_points_redeemed' => LoyaltyTransaction::where('type', 'redeem')->sum('points'),
            'active_customers' => LoyaltyTransaction::distinct('customer_id')->count('customer_id'),
        ];
    }

    public function listLoyaltyTransactions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return LoyaltyTransaction::with('customer')
            ->when($filters['customer_id'] ?? null, fn($q, $c) => $q->where('customer_id', $c))
            ->when($filters['type'] ?? null, fn($q, $t) => $q->where('type', $t))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function earnPoints(int $customerId, int $points, string $description, ?string $sourceType = null, ?int $sourceId = null): LoyaltyTransaction
    {
        return LoyaltyTransaction::create([
            'customer_id' => $customerId,
            'type' => 'earn',
            'points' => abs($points),
            'description' => $description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }

    public function redeemPoints(int $customerId, int $points, string $description): LoyaltyTransaction
    {
        $balance = $this->getCustomerPoints($customerId);
        if ($balance < $points) {
            throw new \Exception('Insufficient loyalty points.');
        }

        return LoyaltyTransaction::create([
            'customer_id' => $customerId,
            'type' => 'redeem',
            'points' => -abs($points),
            'description' => $description,
        ]);
    }

    public function getCustomerPoints(int $customerId): int
    {
        return (int) LoyaltyTransaction::where('customer_id', $customerId)->sum('points');
    }

    public function getStats(): array
    {
        return [
            'sms_campaigns' => SmsCampaign::count(),
            'sms_sent' => SmsCampaign::where('status', 'completed')->sum('sent_count'),
            'email_campaigns' => EmailCampaign::count(),
            'email_sent' => EmailCampaign::where('status', 'completed')->sum('sent_count'),
        ];
    }
}
