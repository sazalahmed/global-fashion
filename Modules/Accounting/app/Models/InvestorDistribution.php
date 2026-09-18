<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payment\Models\PaymentAccount;

class InvestorDistribution extends Model
{
    protected $fillable = [
        'investor_id', 'period_start', 'period_end',
        'net_profit_snapshot', 'share_pct_used', 'distribution_amount',
        'distribution_date', 'payment_account_id', 'journal_entry_id',
        'batch_ref', 'note', 'created_by',
    ];

    protected $casts = [
        'period_start'         => 'date',
        'period_end'           => 'date',
        'distribution_date'    => 'date',
        'net_profit_snapshot'  => 'decimal:2',
        'share_pct_used'       => 'decimal:4',
        'distribution_amount'  => 'decimal:2',
    ];

    public function investor(): BelongsTo       { return $this->belongsTo(Investor::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function journalEntry(): BelongsTo   { return $this->belongsTo(JournalEntry::class); }
    public function creator(): BelongsTo        { return $this->belongsTo(User::class, 'created_by'); }
}
