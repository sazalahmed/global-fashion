<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type', 'name', 'phone', 'email', 'address', 'nid_or_tin',
        'join_date', 'shares_owned', 'profit_share_pct', 'is_active', 'notes',
    ];

    protected $casts = [
        'join_date'        => 'date',
        'shares_owned'     => 'decimal:2',
        'profit_share_pct' => 'decimal:2',
        'is_active'        => 'boolean',
    ];

    public function capital(): HasMany
    {
        return $this->hasMany(InvestorCapital::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(InvestorDistribution::class);
    }

    public function scopeShareholders($q) { return $q->where('type', 'shareholder'); }
    public function scopeInvestors($q)    { return $q->where('type', 'investor'); }
    public function scopeActive($q)       { return $q->where('is_active', true); }

    /**
     * Net capital invested = sum of injections minus sum of withdrawals.
     * Read directly from investor_capital so it always reconciles with the GL.
     */
    public function netCapital(): float
    {
        $rows = $this->capital()->selectRaw(
            "SUM(CASE WHEN type = 'inject' THEN amount ELSE 0 END) as in_total,
             SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as out_total"
        )->first();

        return (float) ($rows->in_total ?? 0) - (float) ($rows->out_total ?? 0);
    }

    public function totalDistributed(): float
    {
        return (float) $this->distributions()->sum('distribution_amount');
    }

    /**
     * Effective share %. For shareholders, computed live from the cap table
     * (their shares / total shares issued). For pure investors, the stored
     * contractual %.
     */
    public function effectiveSharePct(): float
    {
        if ($this->type === 'investor') {
            return (float) $this->profit_share_pct;
        }

        $totalShares = (float) static::active()->shareholders()->sum('shares_owned');
        if ($totalShares <= 0) return 0.0;

        return ((float) $this->shares_owned / $totalShares) * 100;
    }
}
