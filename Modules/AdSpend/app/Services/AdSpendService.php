<?php

namespace Modules\AdSpend\Services;

use App\Helpers\Upload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\JournalEntryService;
use Modules\AdSpend\Models\AdCampaign;
use Modules\AdSpend\Models\AdPlatform;

class AdSpendService
{
    public function __construct(
        private readonly JournalEntryService $journalService,
    ) {}

    // ══════════════════════════════════════
    //  PLATFORMS
    // ══════════════════════════════════════

    public function listPlatforms(): Collection
    {
        return AdPlatform::ordered()->get();
    }

    public function createPlatform(array $data): AdPlatform
    {
        return AdPlatform::create($data);
    }

    public function updatePlatform(AdPlatform $platform, array $data): AdPlatform
    {
        $platform->update($data);
        return $platform;
    }

    public function deletePlatform(AdPlatform $platform): void
    {
        if ($platform->campaigns()->exists()) {
            throw new \RuntimeException("Cannot delete platform — it has {$platform->campaigns()->count()} campaign(s).");
        }
        $platform->delete();
    }

    // ══════════════════════════════════════
    //  CAMPAIGNS
    // ══════════════════════════════════════

    public function listCampaigns(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return AdCampaign::with(['platform', 'paymentAccount'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['platform_id'] ?? null, fn ($q, $id) => $q->where('ad_platform_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['payment_status'] ?? null, fn ($q, $s) => $q->where('payment_status', $s))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('spend_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('spend_date', '<=', $d))
            ->latest('spend_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findCampaign(int $id): AdCampaign
    {
        return AdCampaign::with([
            'platform', 'paymentAccount', 'journalEntry.lines.account', 'branch', 'creator',
        ])->findOrFail($id);
    }

    public function createCampaign(array $data): AdCampaign
    {
        return DB::transaction(function () use ($data) {
            $amount = (float) ($data['amount'] ?? 0);
            $tax = (float) ($data['tax_amount'] ?? 0);
            $total = $amount + $tax;

            if (!empty($data['receipt']) && $data['receipt'] instanceof \Illuminate\Http\UploadedFile) {
                $data['receipt_path'] = \App\Helpers\Upload::store($data['receipt'], 'ad-spend-receipts');
                unset($data['receipt']);
            }

            $campaign = AdCampaign::create([
                'ad_number'            => $this->generateNumber(),
                'ad_platform_id'       => $data['ad_platform_id'],
                'campaign_name'        => $data['campaign_name'],
                'campaign_id_external' => $data['campaign_id_external'] ?? null,
                'spend_date'           => $data['spend_date'],
                'amount'               => $amount,
                'tax_amount'           => $tax,
                'total_amount'         => $total,
                'payment_account_id'   => $data['payment_account_id'] ?? null,
                'payment_method'       => $data['payment_method'] ?? 'Cash',
                'paid_amount'          => 0,
                'due_amount'           => $total,
                'payment_status'       => 'unpaid',
                'status'               => $data['status'] ?? 'active',
                'impressions'          => $data['impressions'] ?? 0,
                'clicks'               => $data['clicks'] ?? 0,
                'conversions'          => $data['conversions'] ?? 0,
                'reach'                => $data['reach'] ?? 0,
                'target_url'           => $data['target_url'] ?? null,
                'notes'                => $data['notes'] ?? null,
                'receipt_path'         => $data['receipt_path'] ?? null,
                'branch_id'            => $data['branch_id'] ?? auth()->user()->branch_id ?? null,
                'created_by'           => auth()->id(),
            ]);

            // Update platform total
            AdPlatform::where('id', $campaign->ad_platform_id)->increment('total_spent', $total);

            return $campaign;
        });
    }

    public function updateCampaign(AdCampaign $campaign, array $data): AdCampaign
    {
        $oldTotal = (float) $campaign->total_amount;

        if (isset($data['amount']) || isset($data['tax_amount'])) {
            $data['total_amount'] = ((float) ($data['amount'] ?? $campaign->amount))
                + ((float) ($data['tax_amount'] ?? $campaign->tax_amount));
            $data['due_amount'] = max(0, $data['total_amount'] - (float) $campaign->paid_amount);
        }

        if (!empty($data['receipt']) && $data['receipt'] instanceof \Illuminate\Http\UploadedFile) {
            $data['receipt_path'] = \App\Helpers\Upload::store($data['receipt'], 'ad-spend-receipts');
            unset($data['receipt']);
        }

        $campaign->update($data);

        // Adjust platform total
        if (isset($data['total_amount'])) {
            $diff = (float) $data['total_amount'] - $oldTotal;
            if (abs($diff) > 0.01) {
                AdPlatform::where('id', $campaign->ad_platform_id)->increment('total_spent', $diff);
            }
        }

        return $campaign->fresh();
    }

    public function deleteCampaign(AdCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign) {
            AdPlatform::where('id', $campaign->ad_platform_id)
                ->decrement('total_spent', (float) $campaign->total_amount);

            if ($campaign->journal_entry_id) {
                try {
                    $je = \Modules\Accounting\Models\JournalEntry::find($campaign->journal_entry_id);
                    if ($je && $je->status === 'posted') {
                        $this->journalService->void($je, "Ad spend {$campaign->ad_number} deleted");
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to void ad spend journal: {$e->getMessage()}");
                }
            }

            $campaign->delete();
        });
    }

    public function recordPayment(AdCampaign $campaign, array $data): AdCampaign
    {
        $amount = (float) $data['amount'];
        $remaining = (float) $campaign->total_amount - (float) $campaign->paid_amount;
        $payAmount = min($amount, $remaining);
        $paymentAccountId = $data['payment_account_id'];

        return DB::transaction(function () use ($campaign, $payAmount, $paymentAccountId, $data) {
            // Journal entry: DR Ad Expense, CR Cash/Bank
            $adAccountId = Account::where('account_code', '5510')->value('id')
                ?? Account::where('account_type', 'expense')->value('id');
            $assetAccountId = $this->resolveAssetAccount($paymentAccountId);

            $je = null;
            if ($adAccountId && $assetAccountId) {
                try {
                    $je = $this->journalService->createFromSource(
                        'ad_spend',
                        $campaign->id,
                        [
                            ['account_id' => $adAccountId, 'debit_amount' => $payAmount, 'credit_amount' => 0, 'description' => "Ad spend: {$campaign->campaign_name}"],
                            ['account_id' => $assetAccountId, 'debit_amount' => 0, 'credit_amount' => $payAmount, 'description' => "Paid for: {$campaign->ad_number}"],
                        ],
                        "Ad spend payment: {$campaign->ad_number}",
                        $data['reference'] ?? $campaign->ad_number,
                        $data['payment_date'] ?? now()->toDateString(),
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record ad spend journal: {$e->getMessage()}");
                }
            }

            $newPaid = (float) $campaign->paid_amount + $payAmount;
            $newDue = max(0, (float) $campaign->total_amount - $newPaid);

            $campaign->update([
                'paid_amount'      => $newPaid,
                'due_amount'       => $newDue,
                'payment_status'   => $newDue <= 0 ? 'paid' : 'partial',
                'journal_entry_id' => $je?->id ?? $campaign->journal_entry_id,
            ]);

            return $campaign->fresh();
        });
    }

    // ══════════════════════════════════════
    //  STATS & ANALYTICS
    // ══════════════════════════════════════

    public function getStats(array $filters = []): array
    {
        $base = AdCampaign::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['platform_id'] ?? null, fn ($q, $id) => $q->where('ad_platform_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('spend_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('spend_date', '<=', $d));

        // All-time (or filtered) totals so the headline reconciles with the
        // campaign table below it. Daily Average is spread across the active
        // spend window (earliest → latest spend date) rather than a calendar
        // month, so it stays meaningful regardless of scope.
        $totalSpent       = (float) (clone $base)->sum('total_amount');
        $totalClicks      = (clone $base)->sum('clicks');
        $totalImpressions = (clone $base)->sum('impressions');

        $firstDate = (clone $base)->min('spend_date');
        $lastDate  = (clone $base)->max('spend_date');
        $days = ($firstDate && $lastDate)
            ? max(1, \Carbon\Carbon::parse($firstDate)->startOfDay()->diffInDays(\Carbon\Carbon::parse($lastDate)->startOfDay()) + 1)
            : 1;

        return [
            'total_spent'    => $totalSpent,
            'daily_average'  => round($totalSpent / $days, 2),
            'campaign_count' => (clone $base)->count(),
            'avg_cpc'        => $totalClicks > 0 ? round($totalSpent / $totalClicks, 2) : 0,
            'avg_cpm'        => $totalImpressions > 0 ? round(($totalSpent / $totalImpressions) * 1000, 2) : 0,
        ];
    }

    public function getSpendByPlatform(?string $from = null, ?string $to = null): Collection
    {
        $query = DB::table('ad_campaigns')
            ->join('ad_platforms', 'ad_platforms.id', '=', 'ad_campaigns.ad_platform_id')
            ->whereNull('ad_campaigns.deleted_at')
            ->select(
                'ad_platforms.id', 'ad_platforms.name', 'ad_platforms.icon', 'ad_platforms.color',
                DB::raw('SUM(ad_campaigns.total_amount) as total_spent'),
                DB::raw('SUM(ad_campaigns.impressions) as total_impressions'),
                DB::raw('SUM(ad_campaigns.clicks) as total_clicks'),
                DB::raw('SUM(ad_campaigns.conversions) as total_conversions'),
                DB::raw('COUNT(ad_campaigns.id) as campaign_count'),
            )
            ->groupBy('ad_platforms.id', 'ad_platforms.name', 'ad_platforms.icon', 'ad_platforms.color');

        if ($from) $query->where('ad_campaigns.spend_date', '>=', $from);
        if ($to) $query->where('ad_campaigns.spend_date', '<=', $to);

        return $query->orderByDesc('total_spent')->get();
    }

    public function getSpendTrend(int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $data = DB::table('ad_campaigns')
            ->select(DB::raw('DATE(spend_date) as date'), DB::raw('SUM(total_amount) as total'))
            ->whereNull('deleted_at')
            ->where('spend_date', '>=', $startDate)
            ->groupBy(DB::raw('DATE(spend_date)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $labels[] = $startDate->copy()->addDays($i)->format('d M');
            $values[] = (float) ($data[$date]->total ?? 0);
        }

        return ['labels' => $labels, 'data' => $values];
    }

    public function getTopCampaigns(int $limit = 10, ?string $from = null, ?string $to = null): Collection
    {
        return AdCampaign::with('platform')
            ->when($from, fn ($q) => $q->where('spend_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('spend_date', '<=', $to))
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();
    }

    public function getPeriodComparison(): array
    {
        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];
        $lastMonth = [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];

        $current = (float) AdCampaign::whereBetween('spend_date', $thisMonth)->sum('total_amount');
        $previous = (float) AdCampaign::whereBetween('spend_date', $lastMonth)->sum('total_amount');
        $change = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;

        return [
            'current'  => $current,
            'previous' => $previous,
            'change'   => $change,
        ];
    }

    // ══════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $count = AdCampaign::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return 'ADS-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function resolveAssetAccount(?int $paymentAccountId): ?int
    {
        $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];

        if ($paymentAccountId) {
            $type = \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type');
            $code = $typeToCode[$type] ?? '1001';

            return Account::where('account_code', $code)->value('id')
                ?? Account::where('account_type', 'asset')->value('id');
        }

        return Account::where('account_code', '1001')->value('id');
    }
}
