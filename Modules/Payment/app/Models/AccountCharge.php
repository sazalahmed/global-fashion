<?php

namespace Modules\Payment\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountCharge extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected string $activityLogName = 'account_charges';

    protected $fillable = [
        'payment_account_id', 'amount', 'date', 'note',
        'journal_entry_id', 'created_by',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
