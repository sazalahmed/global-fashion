<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourierWithdrawal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'withdrawal_number', 'courier_provider', 'settlement_id', 'amount', 'delivery_charge',
        'cod_charge', 'withdrawal_date', 'payment_account_id', 'reference',
        'note', 'journal_entry_id', 'created_by',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'cod_charge'      => 'decimal:2',
        'withdrawal_date' => 'date',
    ];

    /** COD the courier had collected: what we received plus what they kept. */
    public function getGrossAmountAttribute(): float
    {
        return round((float) $this->amount + (float) $this->delivery_charge + (float) $this->cod_charge, 2);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Payment\Models\PaymentAccount::class, 'payment_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\JournalEntry::class, 'journal_entry_id');
    }
}
