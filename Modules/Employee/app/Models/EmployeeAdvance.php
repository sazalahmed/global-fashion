<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAdvance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'advance_number', 'type', 'amount', 'advance_date',
        'payment_account_id', 'reference', 'note', 'journal_entry_id', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'advance_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
