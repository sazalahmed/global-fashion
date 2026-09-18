<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payment\Models\PaymentAccount;

class CapitalTransaction extends Model
{
    protected $fillable = [
        'type', 'payment_account_id', 'transaction_date', 'amount', 'note', 'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
    ];

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDeposits(Builder $q): Builder
    {
        return $q->where('type', 'deposit');
    }

    public function scopeWithdrawals(Builder $q): Builder
    {
        return $q->where('type', 'withdraw');
    }
}
