<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RmPurchaseOrder extends Model
{
    use SoftDeletes;

    protected $table = 'rm_purchase_orders';

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIAL_RECEIVED = 'partial_received';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    // Payment status constants
    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'po_number',
        'po_date',
        'supplier_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_cost',
        'grand_total',
        'paid_amount',
        'due_amount',
        'status',
        'payment_status',
        'expected_delivery_date',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(RawMaterialSupplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RmPurchaseItem::class, 'purchase_order_id');
    }

    public function receives(): HasMany
    {
        return $this->hasMany(RmReceive::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus(Builder $query, string $status): Builder
    {
        return $query->where('payment_status', $status);
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

    // Static helpers
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PARTIAL_RECEIVED => 'Partial Received',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function getPaymentStatuses(): array
    {
        return [
            self::PAYMENT_UNPAID => 'Unpaid',
            self::PAYMENT_PARTIAL => 'Partial',
            self::PAYMENT_PAID => 'Paid',
        ];
    }

    // Accessors
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bp-badge-dark',
            self::STATUS_PENDING => 'bp-badge-warning',
            self::STATUS_APPROVED => 'bp-badge-primary',
            self::STATUS_PARTIAL_RECEIVED => 'bp-badge-info',
            self::STATUS_RECEIVED => 'bp-badge-success',
            self::STATUS_CANCELLED => 'bp-badge-danger',
            default => 'bp-badge-secondary',
        };
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_UNPAID => 'bp-badge-danger',
            self::PAYMENT_PARTIAL => 'bp-badge-warning',
            self::PAYMENT_PAID => 'bp-badge-success',
            default => 'bp-badge-secondary',
        };
    }

    // Boot
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RmPurchaseOrder $po) {
            if (empty($po->po_number)) {
                $po->po_number = static::generatePoNumber();
            }
        });
    }

    public static function generatePoNumber(): string
    {
        $prefix = 'RMPO-';

        $last = static::withTrashed()
            ->where('po_number', 'like', $prefix . '%')
            ->orderByDesc('po_number')
            ->value('po_number');

        $nextSeq = 1;
        if ($last) {
            $nextSeq = (int) str_replace($prefix, '', $last) + 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
