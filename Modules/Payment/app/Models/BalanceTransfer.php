<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceTransfer extends Model
{
    use LogsActivity;

    protected string $activityLogName = 'balance_transfers';
    protected $fillable = [
        'from_account_id', 'to_account_id', 'date', 'amount', 'charge',
        'journal_entry_id', 'note', 'created_by',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
        'charge' => 'decimal:2',
    ];

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'to_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
