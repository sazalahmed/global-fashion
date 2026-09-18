<?php

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Supplier\Models\Supplier;
use Modules\Branch\Models\Branch;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class Purchase extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'purchases';

    protected $fillable = [
        'supplier_id',
        'branch_id',
        'po_number',
        'po_date',
        'expected_delivery',
        'payment_terms',
        'subtotal',
        'discount_amount',
        'order_discount',
        'order_tax_mode',
        'order_tax_rate',
        'order_tax_amount',
        'tax_amount',
        'shipping_cost',
        'grand_total',
        'paid_amount',
        'due_amount',
        'status',
        'payment_status',
        'supplier_invoice_ref',
        'notes',
        'internal_notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'order_discount' => 'decimal:2',
        'order_tax_rate' => 'decimal:2',
        'order_tax_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIAL_RECEIVED = 'partial_received';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';

    // Scopes
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeBySupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('po_date', '>=', $from);
        }
        if ($to) {
            $query->where('po_date', '<=', $to);
        }
        return $query;
    }

    public function scopeByPaymentStatus(Builder $query, string $status): Builder
    {
        return $query->where('payment_status', $status);
    }

    // SearchableInterface Methods

    public static function getSearchType(): string { return 'Purchase'; }
    public static function getSearchIcon(): string { return 'fa-cart-shopping'; }
    public static function getSearchRoute(): string { return 'purchases.show'; }
    public static function getSearchPermission(): ?string { return 'purchases.view'; }
    public static function getSearchableColumns(): array { return ['po_number']; }
    public static function getSearchOrder(): int { return 12; }
    public static function getSearchWith(): array { return ['supplier']; }
    public function getSearchTitle(): string { return $this->po_number ?? ''; }
    public function getSearchSubtitle(): string
    {
        $amount = currency_symbol() . ' ' . number_format($this->grand_total ?? 0);

        return $this->supplier?->company_name ? $this->supplier->company_name . ' — ' . $amount : $amount;
    }

    /**
     * Match PO number OR the related supplier's company/phone.
     */
    public function scopeGlobalSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        $like = '%' . $term . '%';

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($like) {
            $q->where('po_number', 'like', $like)
                ->orWhereHas('supplier', function (\Illuminate\Database\Eloquent\Builder $s) use ($like) {
                    $s->where('company_name', 'like', $like)->orWhere('phone', 'like', $like);
                });
        });
    }

    // Workflow checks
    public function canBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function canBeReceived(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PARTIAL_RECEIVED]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function grns(): HasMany
    {
        return $this->hasMany(GoodsReceiveNote::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            PaymentAllocation::class,
            'allocatable_id',
            'id',
            'id',
            'payment_id'
        )->where('payment_allocations.allocatable_type', static::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Auto-generate PO number on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Purchase $purchase) {
            if (empty($purchase->po_number)) {
                $purchase->po_number = static::generatePoNumber($purchase->po_date);
            }
        });
    }

    public static function generatePoNumber(?string $poDate = null): string
    {
        $date = \Carbon\Carbon::parse($poDate ?? now())->format('Ymd');
        $prefix = 'P' . $date;

        $last = static::withTrashed()
            ->where('po_number', 'like', $prefix . '%')
            ->orderByDesc('po_number')
            ->value('po_number');

        $nextSeq = 1;
        if ($last) {
            $nextSeq = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
    }
}
