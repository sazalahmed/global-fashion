<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payment\Models\PaymentAccount;

class InvestorCapital extends Model
{
    protected $table = 'investor_capital';

    protected $fillable = [
        'investor_id', 'type', 'transaction_date', 'amount',
        'payment_account_id', 'reference', 'note',
        'journal_entry_id', 'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
    ];

    public function investor(): BelongsTo       { return $this->belongsTo(Investor::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function journalEntry(): BelongsTo   { return $this->belongsTo(JournalEntry::class); }
    public function creator(): BelongsTo        { return $this->belongsTo(User::class, 'created_by'); }
}
