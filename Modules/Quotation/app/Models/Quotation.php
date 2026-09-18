<?php

namespace Modules\Quotation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Branch\Models\Branch;
use Modules\Customer\Models\Customer;
use App\Traits\LogsActivity;
use Modules\Sale\Models\Sale;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class Quotation extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'quotations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quotation_number',
        'reference',
        'customer_id',
        'branch_id',
        'quotation_date',
        'valid_until',
        'status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'shipping_charge',
        'grand_total',
        'notes',
        'terms',
        'billing_address',
        'shipping_address',
        'converted_sale_id',
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
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function convertedSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_sale_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('quotation_date', [$from, $to]);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('quotation_number', 'like', '%' . $term . '%')
              ->orWhere('reference', 'like', '%' . $term . '%')
              ->orWhereHas('customer', function (Builder $q) use ($term) {
                  $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('phone', 'like', '%' . $term . '%');
              });
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('valid_until', '<', now()->toDateString())
                     ->whereNotIn('status', ['expired', 'converted', 'rejected']);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['draft', 'pending']);
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Determine if the quotation has expired.
     */
    public function isExpired(): bool
    {
        return $this->valid_until->isPast()
            && !in_array($this->status, ['expired', 'converted']);
    }

    /**
     * Determine if the quotation can be converted to a sale.
     */
    public function isConvertible(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'accepted'])
            && !$this->isExpired();
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Quotation'; }
    public static function getSearchIcon(): string { return 'fa-file-lines'; }
    public static function getSearchRoute(): string { return 'quotations.show'; }
    public static function getSearchPermission(): ?string { return 'quotations.view'; }
    public static function getSearchableColumns(): array { return ['quotation_number', 'reference']; }
    public static function getSearchOrder(): int { return 20; }
    public static function getSearchWith(): array { return ['customer']; }
    public function getSearchTitle(): string { return $this->quotation_number ?? ''; }
    public function getSearchSubtitle(): string
    {
        $amount = currency_symbol() . ' ' . number_format($this->grand_total ?? 0);

        return $this->customer?->name ? $this->customer->name . ' — ' . $amount : $amount;
    }

    /**
     * Match quotation/reference number OR the related customer's name/phone.
     */
    public function scopeGlobalSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        $like = '%' . $term . '%';

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($like) {
            $q->where('quotation_number', 'like', $like)
                ->orWhere('reference', 'like', $like)
                ->orWhereHas('customer', function (\Illuminate\Database\Eloquent\Builder $c) use ($like) {
                    $c->where('name', 'like', $like)->orWhere('phone', 'like', $like);
                });
        });
    }
}
