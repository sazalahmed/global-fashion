<?php

namespace Modules\Customer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Branch\Models\Branch;
use Modules\Customer\Models\CustomerGroup;
use Modules\Sale\Models\Sale;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class Customer extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'customers';

    /**
     * Sale statuses that count as a realised purchase for a customer's running
     * totals (total_purchased / due / paid). A sale is only recognised once the
     * goods are delivered — confirmed/pending/courier/etc. are NOT counted yet.
     * Single source of truth: also used by CustomerService eager-loads and by
     * SaleService's stored-column recompute, so the rule lives in one place.
     */
    public const PURCHASED_STATUSES = ['delivered'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'company_name',
        'customer_group_id',
        'area_id',
        'district',
        'upazila',
        'address',
        'shipping_address',
        'total_purchased',
        'photo',
        'notes',
        'is_active',
        'branch_id',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_purchased' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    /* -------------------------------------------------------
     * Relationships
     * ----------------------------------------------------- */

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    /* -------------------------------------------------------
     * Scopes
     * ----------------------------------------------------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', '%' . $term . '%')
              ->orWhere('phone', 'like', '%' . $term . '%')
              ->orWhere('email', 'like', '%' . $term . '%');
        });
    }

    public function scopeByGroup(Builder $query, ?int $groupId): Builder
    {
        if (empty($groupId)) {
            return $query;
        }

        return $query->where('customer_group_id', $groupId);
    }

    public function scopeByBranch(Builder $query, ?int $branchId): Builder
    {
        if (is_null($branchId)) {
            return $query;
        }

        return $query->where('branch_id', $branchId);
    }

    /* -------------------------------------------------------
     * SearchableInterface Methods
     * ----------------------------------------------------- */

    public static function getSearchType(): string { return 'Customer'; }
    public static function getSearchIcon(): string { return 'fa-users'; }
    public static function getSearchRoute(): string { return 'customers.show'; }
    public static function getSearchPermission(): ?string { return 'customers.view'; }
    public static function getSearchableColumns(): array { return ['name', 'phone', 'email', 'company_name']; }
    public static function getSearchOrder(): int { return 13; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return $this->phone ?? $this->email ?? ''; }

    /* -------------------------------------------------------
     * Accessors
     * ----------------------------------------------------- */

    public function getTotalPurchasedAttribute(): float
    {
        // Derive live from sales so the figure never drifts from reality. Only
        // delivered sales count as a realised purchase (see PURCHASED_STATUSES).
        // Uses an eager-loaded withSum alias when available to avoid N+1 in
        // lists — that alias MUST apply the same status filter (CustomerService).
        if (array_key_exists('sales_sum_grand_total', $this->attributes)) {
            return (float) $this->attributes['sales_sum_grand_total'];
        }

        return (float) $this->sales()->whereIn('status', self::PURCHASED_STATUSES)->sum('grand_total');
    }

    public function getDueAmountAttribute(): float
    {
        return (float) $this->sales()->whereIn('status', self::PURCHASED_STATUSES)->sum('due_amount');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->sales()->whereIn('status', self::PURCHASED_STATUSES)->sum('paid_amount');
    }

    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }
}
