<?php

namespace Modules\AdSpend\Models;

use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdCampaign extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

    protected $fillable = [
        'ad_number', 'ad_platform_id', 'campaign_name', 'campaign_id_external', 'objective',
        'spend_date', 'amount', 'tax_amount', 'total_amount',
        'payment_account_id', 'payment_method',
        'paid_amount', 'due_amount', 'payment_status',
        'status', 'impressions', 'clicks', 'conversions', 'actions', 'reach',
        'target_url', 'notes', 'receipt_path',
        'journal_entry_id', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
        'due_amount'   => 'decimal:2',
        'spend_date'   => 'date',
        'impressions'  => 'integer',
        'clicks'       => 'integer',
        'conversions'  => 'integer',
        'actions'      => 'array',
        'reach'        => 'integer',
    ];

    // ── Relationships ──

    public function platform(): BelongsTo
    {
        return $this->belongsTo(AdPlatform::class, 'ad_platform_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Payment\Models\PaymentAccount::class, 'payment_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\JournalEntry::class, 'journal_entry_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Branch\Models\Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ── Scopes ──

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('campaign_name', 'like', "%{$term}%")
              ->orWhere('ad_number', 'like', "%{$term}%")
              ->orWhere('campaign_id_external', 'like', "%{$term}%");
        });
    }

    public function scopeByPlatform(Builder $query, int $platformId): Builder
    {
        return $query->where('ad_platform_id', $platformId);
    }

    // ── Accessors ──

    public function getCpcAttribute(): ?float
    {
        return $this->clicks > 0 ? round((float) $this->amount / $this->clicks, 2) : null;
    }

    public function getCpmAttribute(): ?float
    {
        return $this->impressions > 0 ? round(((float) $this->amount / $this->impressions) * 1000, 2) : null;
    }

    public function getCtrAttribute(): ?float
    {
        return $this->impressions > 0 ? round(($this->clicks / $this->impressions) * 100, 2) : null;
    }

    public function getConversionRateAttribute(): ?float
    {
        return $this->clicks > 0 ? round(($this->conversions / $this->clicks) * 100, 2) : null;
    }

    public function getCostPerConversionAttribute(): ?float
    {
        return $this->conversions > 0 ? round((float) $this->amount / $this->conversions, 2) : null;
    }

    /**
     * Friendly campaign-type label derived from the Meta objective
     * (e.g. OUTCOME_SALES -> "Sales", OUTCOME_ENGAGEMENT -> "Engagement").
     * Null for manually-entered campaigns with no objective.
     */
    public function getCampaignTypeLabelAttribute(): ?string
    {
        if (empty($this->objective)) {
            return null;
        }

        return match (strtoupper($this->objective)) {
            'OUTCOME_SALES', 'CONVERSIONS', 'PRODUCT_CATALOG_SALES' => 'Sales',
            'OUTCOME_TRAFFIC', 'LINK_CLICKS'                        => 'Traffic',
            'OUTCOME_ENGAGEMENT', 'POST_ENGAGEMENT', 'PAGE_LIKES',
            'EVENT_RESPONSES', 'MESSAGES'                          => 'Engagement',
            'OUTCOME_LEADS', 'LEAD_GENERATION'                      => 'Leads',
            'OUTCOME_AWARENESS', 'BRAND_AWARENESS', 'REACH'         => 'Awareness',
            'OUTCOME_APP_PROMOTION', 'APP_INSTALLS'                 => 'App Promotion',
            'VIDEO_VIEWS'                                           => 'Video Views',
            // Humanize any other/legacy objective: OUTCOME_X / FOO_BAR -> "X" / "Foo Bar".
            default => ucwords(strtolower(str_replace(['OUTCOME_', '_'], ['', ' '], $this->objective))),
        };
    }

    /**
     * The headline outcome for this campaign's objective, pulled from the
     * stored Meta "actions" breakdown:
     *   Sales      → purchases
     *   Messages   → messaging conversations started
     *   Engagement → post engagements (likes/reactions/comments/shares)
     *   Leads      → leads, Traffic → link clicks
     * Returns ['count' => int, 'label' => string] (count is 0 until a sync
     * stores the insights actions), or null for manual campaigns with no
     * objective to map.
     */
    public function getResultAttribute(): ?array
    {
        $obj = strtoupper((string) $this->objective);

        [$types, $label] = match (true) {
            in_array($obj, ['OUTCOME_SALES', 'CONVERSIONS', 'PRODUCT_CATALOG_SALES'], true)
                => [['purchase', 'offsite_conversion.fb_pixel_purchase', 'onsite_conversion.purchase'], 'Purchases'],
            str_contains($obj, 'MESSAGE')
                => [['onsite_conversion.messaging_conversation_started_7d', 'messaging_conversation_started_7d', 'onsite_conversion.total_messaging_connection'], 'Messages'],
            in_array($obj, ['OUTCOME_ENGAGEMENT', 'POST_ENGAGEMENT', 'PAGE_LIKES', 'EVENT_RESPONSES'], true)
                => [['post_engagement', 'post_reaction', 'like'], 'Engagements'],
            in_array($obj, ['OUTCOME_LEADS', 'LEAD_GENERATION'], true)
                => [['lead', 'leadgen_grouped', 'offsite_conversion.fb_pixel_lead'], 'Leads'],
            in_array($obj, ['OUTCOME_TRAFFIC', 'LINK_CLICKS'], true)
                => [['link_click'], 'Link Clicks'],
            default => [[], null],
        };

        if ($label === null) {
            return null;
        }

        // Label is known from the objective even before a sync brings actions;
        // the count fills in once insights "actions" are stored (0 until then).
        $count = 0;
        foreach ($this->actions ?? [] as $a) {
            if (in_array($a['action_type'] ?? '', $types, true)) {
                $count += (int) ($a['value'] ?? 0);
            }
        }

        return ['count' => $count, 'label' => $label];
    }

    /**
     * Deep link into Meta Ads Manager for this campaign (view/edit on Meta).
     * Only for Meta-synced campaigns that carry an external id, and only when
     * the Meta ad account id is configured. Null otherwise.
     */
    public function getMetaUrlAttribute(): ?string
    {
        if (empty($this->campaign_id_external) || optional($this->platform)->name !== 'Meta Ads') {
            return null;
        }

        $accountId = preg_replace('/^act_/', '', (string) \Modules\Setting\Models\Setting::get('meta_ads', 'ad_account_id'));
        if ($accountId === '') {
            return null;
        }

        return 'https://business.facebook.com/adsmanager/manage/campaigns'
            . '?act=' . $accountId
            . '&selected_campaign_ids=' . $this->campaign_id_external;
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Ad Campaign'; }
    public static function getSearchIcon(): string { return 'fa-bullhorn'; }
    public static function getSearchRoute(): string { return 'adspend.show'; }
    public static function getSearchPermission(): ?string { return 'marketing.view'; }
    public static function getSearchableColumns(): array { return ['campaign_name', 'ad_number']; }
    public static function getSearchOrder(): int { return 65; }
    public function getSearchTitle(): string { return $this->campaign_name ?? ''; }
    public function getSearchSubtitle(): string { return $this->ad_number ?? ''; }
}
