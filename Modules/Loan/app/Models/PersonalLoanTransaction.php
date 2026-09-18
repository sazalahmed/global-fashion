<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalLoanTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'borrower_id', 'txn_number', 'type', 'amount', 'txn_date',
        'payment_account_id', 'reference', 'note', 'journal_entry_id', 'created_by',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'txn_date' => 'date',
    ];

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Payment\Models\PaymentAccount::class, 'payment_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
