<?php

namespace Modules\Loan\Models;

use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A personal-loan party with a two-way running account: the business can
 * lend to the person AND borrow from them. outstanding_balance is signed —
 * positive = they owe the business, negative = the business owes them.
 */
class Borrower extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

    protected $fillable = [
        'name', 'phone', 'email', 'photo', 'address',
        'opening_balance', 'total_lent', 'total_recovered',
        'total_taken', 'total_returned',
        'outstanding_balance', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'opening_balance'     => 'decimal:2',
        'total_lent'          => 'decimal:2',
        'total_recovered'     => 'decimal:2',
        'total_taken'         => 'decimal:2',
        'total_returned'      => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
    ];

    // ── Relationships ──

    public function transactions(): HasMany
    {
        return $this->hasMany(PersonalLoanTransaction::class)->orderBy('txn_date')->orderBy('id');
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

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    public function scopeHasOutstanding(Builder $query): Builder
    {
        return $query->where('outstanding_balance', '<>', 0);
    }

    // ── Accessors ──

    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }

    /**
     * What the person still owes the business on loans given (gross
     * receivable side — independent of anything the business owes them).
     * A positive opening balance sits on this side.
     */
    public function getReceivableOutstandingAttribute(): float
    {
        return max(0, round(
            max(0, (float) $this->opening_balance) + (float) $this->total_lent - (float) $this->total_recovered,
            2
        ));
    }

    /**
     * What the business still owes the person on loans taken (gross payable
     * side). A negative opening balance sits on this side.
     */
    public function getPayableOutstandingAttribute(): float
    {
        return max(0, round(
            max(0, -(float) $this->opening_balance) + (float) $this->total_taken - (float) $this->total_returned,
            2
        ));
    }

    // ── SearchableInterface ──

    public static function getSearchType(): string { return 'Borrower'; }
    public static function getSearchIcon(): string { return 'fa-hand-holding-dollar'; }
    public static function getSearchRoute(): string { return 'personal-loans.show'; }
    public static function getSearchPermission(): ?string { return 'finance.view'; }
    public static function getSearchableColumns(): array { return ['name', 'phone', 'email']; }
    public static function getSearchOrder(): int { return 44; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return $this->phone ?? ''; }
}
