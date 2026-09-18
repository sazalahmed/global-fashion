<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends Model
{
    protected $fillable = [
        'reconciliation_id', 'journal_entry_line_id', 'type',
        'transaction_date', 'description', 'reference',
        'amount', 'is_reconciled', 'matched_item_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
        'is_reconciled'    => 'boolean',
    ];

    // ── Relationships ──

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }

    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class);
    }

    public function matchedItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'matched_item_id');
    }

    // ── Scopes ──

    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    public function scopeBookTransactions($query)
    {
        return $query->where('type', 'book_transaction');
    }

    public function scopeBankStatement($query)
    {
        return $query->where('type', 'bank_statement');
    }
}
