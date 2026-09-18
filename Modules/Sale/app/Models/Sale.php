<?php

namespace Modules\Sale\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Branch\Models\Branch;
use Modules\Customer\Models\Customer;
use Modules\Location\Models\District;
use Modules\Location\Models\Thana;
use Modules\Payment\Models\PaymentAllocation;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class Sale extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'sales';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_number',
        'reference_number',
        'customer_id',
        'customer_name_snapshot',
        'customer_phone_snapshot',
        'customer_address',
        'billing_address',
        'district_id',
        'thana_id',
        'branch_id',
        'sale_date',
        'due_date',
        'source',
        'price_type',
        'status',
        'payment_status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'shipping_charge',
        'grand_total',
        'paid_amount',
        'due_amount',
        'due_discount_amount',
        'courier_collected_amount',
        'courier_delivery_charge',
        'courier_name',
        'courier_consignment_id',
        'courier_tracking_code',
        'courier_status',
        'courier_tracking_url',
        'courier_status_updated_at',
        'delivered_at',
        'needs_return',
        'redx_area_id',
        'redx_area_name',
        'pathao_city',
        'pathao_zone',
        'pathao_area_id',
        'pathao_weight',
        'notes',
        'item_description',
        'staff_note',
        'created_by',
        'assigned_to',
    ];

    /**
     * Available sale status values across the full order workflow.
     * Used by the form select and by validation.
     */
    public const STATUSES = [
        'pending'         => 'Pending',
        'packing'         => 'Packing',
        'courier'         => 'Courier',
        'delivered'       => 'Delivered',
        'partial_cancelled' => 'Partial Cancel',
        'cancelled'       => 'Cancelled',
        'returned'        => 'Returned',
        'return_received' => 'Return Received',
        'draft'           => 'Draft',
        'on_hold'         => 'On-Hold',
        'exchange'        => 'Exchange',
        'incompleted'     => 'Incompleted',
        'confirmed'       => 'Confirmed',
    ];

    /**
     * Statuses that are applied only by the sale-return workflow, never picked
     * manually. They remain valid values (for labels, filtering and the return
     * process) but are excluded from the status pickers on the sale forms.
     */
    public const RETURN_STATUSES = ['returned', 'return_received'];

    /**
     * Deprecated statuses: kept in STATUSES only so existing records still
     * render a label, but never offered in the status pickers or list tabs.
     */
    public const DEPRECATED_STATUSES = ['exchange'];

    /**
     * Statuses an admin may assign manually on the sale forms — the full list
     * minus the return-only and deprecated statuses. Pass the sale's current
     * status to keep it visible in an edit/inline picker when it already holds
     * an excluded status (so the dropdown reflects reality without offering
     * that status to other sales).
     */
    public static function selectableStatuses(?string $includeCurrent = null): array
    {
        $excluded = array_merge(self::RETURN_STATUSES, self::DEPRECATED_STATUSES);
        $statuses = array_diff_key(self::STATUSES, array_flip($excluded));

        if ($includeCurrent && isset(self::STATUSES[$includeCurrent]) && ! isset($statuses[$includeCurrent])) {
            $statuses[$includeCurrent] = self::STATUSES[$includeCurrent];
        }

        return $statuses;
    }

    /**
     * Default courier options for the order workflow. Kept in the model
     * so callers don't need a separate Courier table for simple use.
     */
    public const COURIERS = [
        'Steadfast',
        'Pathao',
        'Redx',
        'eCourier',
        'Paperfly',
        'Sundarban',
        'SA Paribahan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * Stamp delivered_at the first time a sale reaches 'delivered', whichever
     * path sets the status (admin status change, courier webhook, ecommerce
     * order sync, or a sale created/edited directly as delivered). Kept once
     * it is set so a later status correction doesn't erase the delivery record.
     */
    protected static function booted(): void
    {
        static::saving(function (Sale $sale) {
            if ($sale->status === 'delivered' && ! $sale->delivered_at) {
                $sale->delivered_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
            'due_discount_amount' => 'decimal:2',
            'courier_collected_amount' => 'decimal:2',
            'courier_delivery_charge' => 'decimal:2',
            'courier_status_updated_at' => 'datetime',
            'delivered_at' => 'datetime',
            'needs_return' => 'boolean',
            'pathao_weight' => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * What a sale's source is called on screen. The stored values stay as they
     * are — 'ecommerce' is written across the sales table, the source filter
     * and half the service layer — so only the wording shown to people changes.
     *
     * Use this wherever a source is displayed: the label lived in three
     * separate @switch blocks and a notification that printed the raw column,
     * which is how notifications came to read "(ecommerce)" while the sale page
     * beside them said "eCommerce".
     */
    public static function sourceLabel(?string $source): string
    {
        return match ($source) {
            'ecommerce', 'storefront' => 'Website',
            'store'                   => 'Invoice',
            'pos'                     => 'POS',
            default                   => ucfirst((string) ($source ?: 'Unknown')),
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return static::sourceLabel($this->source);
    }

    /**
     * Display name for the buyer: the linked customer's name, else the snapshot
     * captured at sale time (ecommerce/guest orders), else "Walk-in Customer".
     * Use this everywhere a sale's customer name is shown so guest orders never
     * fall back to "Walk-in" when a real name was provided.
     */
    public function getCustomerDisplayNameAttribute(): string
    {
        return $this->customer?->name
            ?: ($this->customer_name_snapshot ?: 'Walk-in Customer');
    }

    /**
     * Public Steadfast tracking link. Prefers the consignment URL
     * (https://steadfast.com.bd/user/consignment/{id}) when a consignment id
     * is known — this also corrects older records whose stored
     * courier_tracking_url uses the legacy /t/{code} format. Falls back to the
     * stored URL when no consignment id is available.
     */
    public function getCourierTrackingLinkAttribute(): ?string
    {
        if (!empty($this->courier_consignment_id)) {
            return 'https://steadfast.com.bd/user/consignment/' . $this->courier_consignment_id;
        }

        return $this->courier_tracking_url;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function thana(): BelongsTo
    {
        return $this->belongsTo(Thana::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Staff member (web-guard user) responsible for handling this sale. */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Whether this sale contains any combo-sourced line (derived, no column).
     */
    public function getHasComboAttribute(): bool
    {
        return $this->items->whereNotNull('combo_id')->isNotEmpty();
    }

    /**
     * Items arranged for display: combo lines grouped by combo_group under a
     * combo header, plain lines kept individual. Returns a collection of
     * ['combo' => bool, 'group' => ?string, 'name' => ?string, 'items' => Collection].
     */
    public function itemsGroupedByCombo(): \Illuminate\Support\Collection
    {
        $groups = collect();

        foreach ($this->items as $item) {
            if ($item->combo_group) {
                $key = 'combo:' . $item->combo_group;
                if (! $groups->has($key)) {
                    $groups->put($key, [
                        'combo'    => true,
                        'group'    => $item->combo_group,
                        'combo_id' => $item->combo_id,
                        'name'     => $item->combo_name,
                        'items'    => collect(),
                    ]);
                }
                $groups[$key]['items']->push($item);
            } else {
                $groups->put('item:' . $item->id, [
                    'combo' => false,
                    'group' => null,
                    'name'  => null,
                    'items' => collect([$item]),
                ]);
            }
        }

        return $groups->values();
    }

    public function allocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'allocatable');
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(CourierTrackingEvent::class)->orderByDesc('occurred_at')->orderByDesc('id');
    }

    /**
     * The storefront order this sale was mirrored from (ecommerce source).
     * Used to recover the customer phone for fraud checks when the linked
     * Customer record has no phone and no snapshot was taken.
     */
    public function ecommerceOrder(): HasOne
    {
        return $this->hasOne(\Modules\Ecommerce\Models\EcommerceOrder::class, 'sale_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePos(Builder $query): Builder
    {
        return $query->where('source', 'pos');
    }

    public function scopeStore(Builder $query): Builder
    {
        return $query->where('source', 'store');
    }

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus(Builder $query, string $status): Builder
    {
        return $query->where('payment_status', $status);
    }

    public function scopeByDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('sale_date', [$from, $to]);
    }

    public function scopeByCustomer(Builder $query, int $id): Builder
    {
        return $query->where('customer_id', $id);
    }

    public function scopeByBranch(Builder $query, int $id): Builder
    {
        return $query->where('branch_id', $id);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        // Digits-only form of the term so phone searches match regardless of
        // dash/space formatting ("01872-339070" finds "01872339070" and back).
        $digits = preg_replace('/\D+/', '', $term);

        return $query->where(function (Builder $q) use ($term, $digits) {
            $q->where('invoice_number', 'like', '%' . $term . '%')
                ->orWhere('reference_number', 'like', '%' . $term . '%')
                ->orWhere('customer_name_snapshot', 'like', '%' . $term . '%')
                ->orWhere('customer_phone_snapshot', 'like', '%' . $term . '%')
                ->orWhereHas('customer', function (Builder $c) use ($term) {
                    $c->where('name', 'like', '%' . $term . '%')
                        ->orWhere('phone', 'like', '%' . $term . '%');
                });

            if ($digits !== '' && strlen($digits) >= 6) {
                $q->orWhereRaw(
                    "REPLACE(REPLACE(customer_phone_snapshot, '-', ''), ' ', '') like ?",
                    ['%' . $digits . '%']
                )->orWhereHas('customer', fn (Builder $c) => $c->whereRaw(
                    "REPLACE(REPLACE(phone, '-', ''), ' ', '') like ?",
                    ['%' . $digits . '%']
                ));
            }
        });
    }

    // -------------------------------------------------------------------------
    // SearchableInterface Methods
    // -------------------------------------------------------------------------

    public static function getSearchType(): string { return 'Sale'; }
    public static function getSearchIcon(): string { return 'fa-chart-line'; }
    public static function getSearchRoute(): string { return 'sales.show'; }
    public static function getSearchPermission(): ?string { return 'sales.view'; }
    public static function getSearchableColumns(): array { return ['invoice_number', 'reference_number']; }
    public static function getSearchOrder(): int { return 11; }
    public static function getSearchWith(): array { return ['customer']; }
    public function getSearchTitle(): string { return $this->invoice_number ?? ''; }
    public function getSearchSubtitle(): string
    {
        $amount = currency_symbol() . ' ' . number_format($this->grand_total ?? 0);

        return $this->customer?->name ? $this->customer->name . ' — ' . $amount : $amount;
    }

    /**
     * Match invoice/reference OR the related customer's name/phone, so a sale
     * can be found by who it was sold to.
     */
    public function scopeGlobalSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        $like = '%' . $term . '%';

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($like) {
            $q->where('invoice_number', 'like', $like)
                ->orWhere('reference_number', 'like', $like)
                ->orWhereHas('customer', function (\Illuminate\Database\Eloquent\Builder $c) use ($like) {
                    $c->where('name', 'like', $like)->orWhere('phone', 'like', $like);
                });
        });
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Determine if the sale is fully paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Determine if the sale is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->payment_status !== 'paid';
    }

    /**
     * Determine if the sale originated from POS.
     */
    public function isPOS(): bool
    {
        return $this->source === 'pos';
    }
}
