<?php

namespace Modules\SaleReturn\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class SaleReturn extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'sale_returns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'return_number',
        'sale_id',
        'customer_id',
        'branch_id',
        'return_date',
        'reason',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'refund_method',
        'credit_note_id',
        'courier_return_id',
        'courier_return_status',
        'journal_entry_id',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function sale(): BelongsTo
    {
        return $this->belongsTo(\Modules\Sale\Models\Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Customer\Models\Customer::class);
    }

    /**
     * Buyer display name: the return's customer, else the originating sale's
     * customer/snapshot name, else "Walk-in Customer".
     */
    public function getCustomerDisplayNameAttribute(): string
    {
        return $this->customer?->name
            ?: ($this->sale?->customer_display_name ?: 'Walk-in Customer');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Branch\Models\Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\CreditNote::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by customer.
     */
    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('return_date', [$from, $to]);
    }

    /**
     * Scope to search by return number or reason.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('return_number', 'like', '%' . $term . '%')
                ->orWhere('reason', 'like', '%' . $term . '%');
        });
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Determine if the return can still be edited (draft only).
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Determine if the return can be completed (approved only).
     */
    public function isCompletable(): bool
    {
        return $this->status === 'approved';
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Sale Return'; }
    public static function getSearchIcon(): string { return 'fa-rotate-left'; }
    public static function getSearchRoute(): string { return 'sale-returns.show'; }
    public static function getSearchPermission(): ?string { return 'sales.view'; }
    public static function getSearchableColumns(): array { return ['return_number']; }
    public static function getSearchOrder(): int { return 22; }
    public static function getSearchWith(): array { return ['customer']; }
    public function getSearchTitle(): string { return $this->return_number ?? ''; }
    public function getSearchSubtitle(): string
    {
        $amount = currency_symbol() . ' ' . number_format($this->total_amount ?? 0);

        return $this->customer?->name ? $this->customer->name . ' — ' . $amount : $amount;
    }

    /**
     * Match return number OR the related customer's name/phone.
     */
    public function scopeGlobalSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        $like = '%' . $term . '%';

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($like) {
            $q->where('return_number', 'like', $like)
                ->orWhereHas('customer', function (\Illuminate\Database\Eloquent\Builder $c) use ($like) {
                    $c->where('name', 'like', $like)->orWhere('phone', 'like', $like);
                });
        });
    }
}
