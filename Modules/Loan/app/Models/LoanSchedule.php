<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanSchedule extends Model
{
    protected $fillable = [
        'loan_id', 'installment_number', 'due_date', 'amount',
        'paid_amount', 'status', 'paid_date',
        'payment_account_id', 'journal_entry_id', 'note',
    ];

    protected $casts = [
        'loan_id'     => 'integer',
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date'    => 'date',
        'paid_date'   => 'date',
    ];

    // ── Relationships ──

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\JournalEntry::class, 'journal_entry_id');
    }

    // ── Scopes ──

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'overdue');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeDueWithin(Builder $query, int $days): Builder
    {
        return $query->whereIn('status', ['upcoming', 'partial', 'overdue'])
                     ->where('due_date', '<=', now()->addDays($days))
                     ->orderBy('due_date');
    }

    // ── Accessors ──

    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }
}
