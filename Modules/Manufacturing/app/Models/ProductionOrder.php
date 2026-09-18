<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

    protected $table = 'production_orders';

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PARTIAL_DELIVERED = 'partial_delivered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PARTIAL = 'partial';
    const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'po_number', 'factory_id', 'order_date', 'expected_delivery_date',
        'total_quantity', 'received_quantity', 'damaged_quantity', 'wasted_quantity', 'good_quantity',
        'estimated_making_cost_per_unit', 'actual_making_cost_per_unit', 'estimated_total_cost',
        'total_fabric_cost', 'total_making_cost', 'total_delivery_cost', 'total_other_cost',
        'total_damage_cost', 'total_compensation', 'total_fabric_returned_cost',
        'total_rm_waste_cost', 'total_rm_waste_abnormal_cost',
        'total_product_waste_cost', 'total_product_waste_abnormal_cost',
        'grand_total', 'paid_amount', 'due_amount', 'advance_deducted', 'payment_status',
        'running_weighted_avg_cost', 'final_cost_per_unit',
        'status', 'notes', 'created_by', 'approved_by', 'approved_at', 'completed_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_quantity' => 'integer',
        'received_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'wasted_quantity' => 'integer',
        'good_quantity' => 'integer',
        'estimated_making_cost_per_unit' => 'decimal:2',
        'actual_making_cost_per_unit' => 'decimal:2',
        'estimated_total_cost' => 'decimal:2',
        'total_fabric_cost' => 'decimal:2',
        'total_making_cost' => 'decimal:2',
        'total_delivery_cost' => 'decimal:2',
        'total_other_cost' => 'decimal:2',
        'total_damage_cost' => 'decimal:2',
        'total_compensation' => 'decimal:2',
        'total_fabric_returned_cost' => 'decimal:2',
        'total_rm_waste_cost' => 'decimal:2',
        'total_rm_waste_abnormal_cost' => 'decimal:2',
        'total_product_waste_cost' => 'decimal:2',
        'total_product_waste_abnormal_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'advance_deducted' => 'decimal:2',
        'running_weighted_avg_cost' => 'decimal:2',
        'final_cost_per_unit' => 'decimal:2',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PARTIAL_DELIVERED => 'Partial Delivered',
            self::STATUS_COMPLETED => 'Completed',
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

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canReceiveLot(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_IN_PROGRESS, self::STATUS_PARTIAL_DELIVERED]);
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_IN_PROGRESS, self::STATUS_PARTIAL_DELIVERED]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_APPROVED]);
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterial::class);
    }

    public function fabricIssuances(): HasMany
    {
        return $this->hasMany(FabricIssuance::class);
    }

    public function fabricReturns(): HasMany
    {
        return $this->hasMany(FabricReturn::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(ProductionLot::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(ProductionDamage::class);
    }

    public function rmWastes(): HasMany
    {
        return $this->hasMany(RmWaste::class);
    }

    public function productWastes(): HasMany
    {
        return $this->hasMany(ProductWaste::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByFactory(Builder $query, int $factoryId): Builder
    {
        return $query->where('factory_id', $factoryId);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (ProductionOrder $order) {
            if (empty($order->po_number)) {
                $order->po_number = static::generateUniqueCode();
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        $prefix = 'PO-';
        $lastRecord = static::withTrashed()->where('po_number', 'like', $prefix . '%')->orderByDesc('po_number')->first();
        if ($lastRecord) {
            $lastNumber = (int) str_replace($prefix, '', $lastRecord->po_number);
            return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Production Order'; }
    public static function getSearchIcon(): string { return 'fa-clipboard-list'; }
    public static function getSearchRoute(): string { return 'manufacturing.production-orders.show'; }
    public static function getSearchPermission(): ?string { return null; }
    public static function getSearchableColumns(): array { return ['po_number']; }
    public static function getSearchOrder(): int { return 44; }
    public function getSearchTitle(): string { return $this->po_number ?? ''; }
    public function getSearchSubtitle(): string { return ucfirst($this->status ?? ''); }
}
