<?php

namespace Modules\Loan\Models;

use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lender extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

    protected $fillable = [
        'name', 'company_name', 'phone', 'email', 'photo', 'address',
        'bank_name', 'account_number', 'bank_branch',
        'opening_balance', 'total_borrowed', 'total_repaid',
        'outstanding_balance', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'opening_balance'     => 'decimal:2',
        'total_borrowed'      => 'decimal:2',
        'total_repaid'        => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
    ];

    // ── Relationships ──

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
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
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('company_name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function scopeHasOutstanding(Builder $query): Builder
    {
        return $query->where('outstanding_balance', '>', 0);
    }

    // ── Accessors ──

    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }

    // ── SearchableInterface Methods ──

    public static function getSearchType(): string { return 'Lender'; }
    public static function getSearchIcon(): string { return 'fa-landmark'; }
    public static function getSearchRoute(): string { return 'lenders.show'; }
    public static function getSearchPermission(): ?string { return 'finance.view'; }
    public static function getSearchableColumns(): array { return ['name', 'company_name', 'phone', 'email']; }
    public static function getSearchOrder(): int { return 43; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return $this->company_name ?? $this->phone ?? ''; }
}
