<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    protected $fillable = [
        'account_id', 'statement_date', 'statement_balance', 'book_balance',
        'adjusted_balance', 'status', 'reconciled_by', 'reconciled_at', 'notes',
    ];

    protected $casts = [
        'statement_date'    => 'date',
        'statement_balance' => 'decimal:2',
        'book_balance'      => 'decimal:2',
        'adjusted_balance'  => 'decimal:2',
        'reconciled_at'     => 'datetime',
    ];

    // ── Relationships ──

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function reconciledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class, 'reconciliation_id');
    }

    // ── Scopes ──

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ── Accessors ──

    public function getDifferenceAttribute(): float
    {
        return $this->statement_balance - $this->book_balance;
    }
}
