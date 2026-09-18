<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Modules\Branch\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'journal_entries';

    protected $fillable = [
        'entry_number', 'entry_date', 'reference', 'description',
        'source_type', 'source_id', 'total_amount', 'status',
        'attachment_path', 'notes', 'posted_at', 'posted_by',
        'voided_at', 'voided_by', 'void_reason', 'created_by', 'branch_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    // ── Relationships ──

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    // ── Scopes ──

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Posted entries that represent a real, standing effect on the books —
     * i.e. posted() minus void_reversal entries.
     *
     * Voiding an entry does two things: marks the original 'voided' (so
     * posted() already excludes it) AND posts a new 'void_reversal' entry
     * with debit/credit swapped, to keep an audit trail. A balance or
     * period-movement query that filters posted() alone therefore counts
     * the reversal with nothing left to cancel it against — the voided
     * transaction doesn't net to zero, it flips sign and counts backwards.
     * Use this scope (not posted()) for any balance, trial balance, P&L, or
     * general ledger movement calculation. SimpleMoneyService::cashFlow()
     * already applies this same exclusion inline; this scope is the
     * reusable form of that fix.
     */
    public function scopePostedEffective($query)
    {
        return $query->where('status', 'posted')
            ->where(fn ($q) => $q->whereNull('source_type')->orWhere('source_type', '!=', 'void_reversal'));
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }

    public function scopeByDateRange($query, $from = null, $to = null)
    {
        return $query
            ->when($from, fn ($q) => $q->where('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('entry_date', '<=', $to));
    }

    public function scopeBySource($query, string $type, ?int $id = null)
    {
        return $query->where('source_type', $type)
            ->when($id, fn ($q) => $q->where('source_id', $id));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('entry_number', 'like', '%' . $term . '%')
              ->orWhere('reference', 'like', '%' . $term . '%')
              ->orWhere('description', 'like', '%' . $term . '%');
        });
    }

    // ── Methods ──

    public function isBalanced(): bool
    {
        return bccomp((string) $this->totalDebit(), (string) $this->totalCredit(), 2) === 0;
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function totalDebit(): float
    {
        return (float) $this->lines->sum('debit_amount');
    }

    public function totalCredit(): float
    {
        return (float) $this->lines->sum('credit_amount');
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Journal Entry'; }
    public static function getSearchIcon(): string { return 'fa-book'; }
    public static function getSearchRoute(): string { return 'accounting.journal-entries.show'; }
    public static function getSearchPermission(): ?string { return 'accounting.view'; }
    public static function getSearchableColumns(): array { return ['entry_number', 'reference', 'description']; }
    public static function getSearchOrder(): int { return 45; }
    public function getSearchTitle(): string { return $this->entry_number ?? ''; }
    public function getSearchSubtitle(): string { return $this->description ?? ''; }
}
