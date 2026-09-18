<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

    protected $fillable = [
        'loan_number', 'lender_id', 'principal_amount',
        'disbursement_date', 'disbursement_account_id',
        'total_installments', 'installment_amount', 'frequency',
        'start_date', 'next_due_date',
        'total_repaid', 'total_remaining',
        'status', 'reference', 'note',
        'journal_entry_id', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'principal_amount'  => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'total_repaid'      => 'decimal:2',
        'total_remaining'   => 'decimal:2',
        'disbursement_date' => 'date',
        'start_date'        => 'date',
        'next_due_date'     => 'date',
    ];

    // ── Relationships ──

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('installment_number');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\JournalEntry::class, 'journal_entry_id');
    }

    public function disbursementAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Payment\Models\PaymentAccount::class, 'disbursement_account_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Branch\Models\Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'overdue');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    // ── Accessors ──

    public function getProgressPercentageAttribute(): float
    {
        if ((float) $this->principal_amount <= 0) return 0;

        return round(((float) $this->total_repaid / (float) $this->principal_amount) * 100, 1);
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Loan'; }
    public static function getSearchIcon(): string { return 'fa-hand-holding-dollar'; }
    public static function getSearchRoute(): string { return 'loans.show'; }
    public static function getSearchPermission(): ?string { return 'finance.view'; }
    public static function getSearchableColumns(): array { return ['loan_number', 'reference']; }
    public static function getSearchOrder(): int { return 43; }
    public function getSearchTitle(): string { return $this->loan_number ?? ''; }
    public function getSearchSubtitle(): string { return currency_symbol() . ' ' . number_format($this->principal_amount ?? 0); }
}
