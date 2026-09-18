<?php

namespace Modules\AdSpend\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AdSpend\Models\AdCampaign;
use Modules\AdSpend\Models\AdPlatform;
use Modules\Setting\Models\Setting;

class MetaAdsService
{
    private const API_VERSION = 'v19.0';
    private const API_BASE    = 'https://graph.facebook.com';

    public function getConfig(): array
    {
        return [
            'app_id'         => Setting::get('meta_ads', 'app_id') ?: '',
            'app_secret'     => Setting::get('meta_ads', 'app_secret') ?: '',
            'access_token'   => Setting::get('meta_ads', 'access_token') ?: '',
            'ad_account_id'  => $this->normalizeAccountId(Setting::get('meta_ads', 'ad_account_id') ?: ''),
            'last_synced_at' => Setting::get('meta_ads', 'last_synced_at'),
        ];
    }

    public function saveConfig(array $data): void
    {
        foreach (['app_id', 'app_secret', 'access_token', 'ad_account_id'] as $k) {
            if (array_key_exists($k, $data)) {
                Setting::set('meta_ads', $k, (string) $data[$k]);
            }
        }
    }

    public function isConfigured(): bool
    {
        $c = $this->getConfig();
        return $c['access_token'] !== '' && $c['ad_account_id'] !== '';
    }

    public function testConnection(): array
    {
        $config = $this->getConfig();
        if ($config['access_token'] === '') {
            return ['success' => false, 'message' => 'Access token is missing.'];
        }

        $response = Http::timeout(15)->get(self::API_BASE . '/' . self::API_VERSION . '/me/adaccounts', [
            'fields'       => 'id,name,account_id,currency,account_status',
            'access_token' => $config['access_token'],
            'limit'        => 25,
        ]);

        if (!$response->successful()) {
            $err = $response->json('error.message') ?? $response->body();
            return ['success' => false, 'message' => "Meta API error: {$err}"];
        }

        $accounts = $response->json('data') ?? [];

        return [
            'success'  => true,
            'message'  => 'Connected. Found ' . count($accounts) . ' ad account(s).',
            'accounts' => $accounts,
        ];
    }

    public function fetchCampaigns(): array
    {
        $config = $this->getConfig();
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Meta API is not configured.', 'campaigns' => []];
        }

        $response = Http::timeout(30)->get(self::API_BASE . '/' . self::API_VERSION . '/' . $config['ad_account_id'] . '/campaigns', [
            'fields'       => 'id,name,objective,status,daily_budget,lifetime_budget,start_time,stop_time,created_time',
            'access_token' => $config['access_token'],
            'limit'        => 100,
        ]);

        if (!$response->successful()) {
            $err = $response->json('error.message') ?? $response->body();
            return ['success' => false, 'message' => "Meta API error: {$err}", 'campaigns' => []];
        }

        return ['success' => true, 'campaigns' => $response->json('data') ?? []];
    }

    public function fetchInsights(string $campaignId): array
    {
        $config = $this->getConfig();
        if (!$this->isConfigured()) {
            return ['success' => false, 'insights' => []];
        }

        $response = Http::timeout(30)->get(self::API_BASE . '/' . self::API_VERSION . '/' . $campaignId . '/insights', [
            'fields'       => 'spend,impressions,clicks,reach,ctr,cpc,cpm,actions',
            // 'maximum' = all-time. Meta removed the old 'lifetime' preset in
            // v19 (returns "(#100) lifetime is not a valid date_preset"), which
            // previously made every insights call fail and zeroed all metrics.
            'date_preset'  => 'maximum',
            'access_token' => $config['access_token'],
        ]);

        if (!$response->successful()) {
            // Surface the reason instead of silently returning zeros.
            Log::warning('Meta insights fetch failed for campaign ' . $campaignId . ': '
                . ($response->json('error.message') ?? $response->body()));
            return ['success' => false, 'insights' => []];
        }

        $data = $response->json('data') ?? [];
        return ['success' => true, 'insights' => $data[0] ?? []];
    }

    public function syncAll(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Meta API is not configured. Save credentials first.', 'created' => 0, 'updated' => 0];
        }

        $metaPlatform = AdPlatform::firstOrCreate(
            ['name' => 'Meta Ads'],
            ['icon' => 'fa-brands fa-meta', 'color' => '#1877F2', 'is_active' => true, 'sort_order' => 1]
        );

        $campaigns = $this->fetchCampaigns();
        if (!$campaigns['success']) {
            return ['success' => false, 'message' => $campaigns['message'], 'created' => 0, 'updated' => 0];
        }

        $created = 0;
        $updated = 0;

        foreach ($campaigns['campaigns'] as $c) {
            $insights = $this->fetchInsights($c['id']);
            $i = $insights['insights'] ?? [];

            $conversions = $this->extractConversions($i['actions'] ?? []);

            $existing = AdCampaign::where('campaign_id_external', $c['id'])->first();
            $payload = [
                'ad_platform_id'       => $metaPlatform->id,
                'campaign_name'        => $c['name'] ?? 'Unnamed campaign',
                'campaign_id_external' => $c['id'],
                'objective'            => $c['objective'] ?? null,
                'status'               => $this->mapStatus($c['status'] ?? null),
                'spend_date'           => isset($c['start_time']) ? date('Y-m-d', strtotime($c['start_time'])) : now()->toDateString(),
                'amount'               => (float) ($i['spend'] ?? 0),
                'total_amount'         => (float) ($i['spend'] ?? 0),
                'impressions'          => (int) ($i['impressions'] ?? 0),
                'clicks'               => (int) ($i['clicks'] ?? 0),
                'reach'                => (int) ($i['reach'] ?? 0),
                'conversions'          => $conversions,
                'actions'              => $i['actions'] ?? null,
            ];

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                $payload['ad_number'] = $this->generateAdNumber();
                $payload['payment_status'] = 'unpaid';
                $payload['created_by'] = auth()->id();
                AdCampaign::create($payload);
                $created++;
            }
        }

        Setting::set('meta_ads', 'last_synced_at', now()->toDateTimeString());

        return [
            'success' => true,
            'message' => "Synced {$created} new + {$updated} updated campaigns from Meta.",
            'created' => $created,
            'updated' => $updated,
        ];
    }

    private function normalizeAccountId(string $id): string
    {
        $id = trim($id);
        if ($id === '') {
            return '';
        }
        return str_starts_with($id, 'act_') ? $id : ('act_' . $id);
    }

    private function mapStatus(?string $metaStatus): string
    {
        // Map Meta's campaign status onto the ad_campaigns.status enum
        // (active | paused | completed) — never 'running', which the column
        // rejects (Data truncated for column 'status').
        return match (strtoupper((string) $metaStatus)) {
            'ACTIVE'   => 'active',
            'PAUSED'   => 'paused',
            'DELETED', 'ARCHIVED' => 'completed',
            default    => 'active',
        };
    }

    private function extractConversions(array $actions): int
    {
        $sum = 0;
        foreach ($actions as $a) {
            if (in_array($a['action_type'] ?? '', ['purchase', 'lead', 'complete_registration', 'offsite_conversion.fb_pixel_purchase'], true)) {
                $sum += (int) ($a['value'] ?? 0);
            }
        }
        return $sum;
    }

    private function generateAdNumber(): string
    {
        $last = AdCampaign::withTrashed()->latest('id')->first();
        $next = $last ? ($last->id + 1) : 1;
        return 'ADS-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
