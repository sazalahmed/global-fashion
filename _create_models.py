import os

models_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'Modules', 'Manufacturing', 'app', 'Models')

files = {}

files['ProductionOrder.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\SoftDeletes;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class ProductionOrder extends Model
{
    use SoftDeletes;

    protected $table = 'production_orders';

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PARTIAL_DELIVERED = 'partial_delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Payment constants
    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'po_number',
        'factory_id',
        'order_date',
        'expected_delivery_date',
        'total_quantity',
        'received_quantity',
        'damaged_quantity',
        'wasted_quantity',
        'good_quantity',
        'estimated_making_cost_per_unit',
        'actual_making_cost_per_unit',
        'estimated_total_cost',
        'total_fabric_cost',
        'total_making_cost',
        'total_delivery_cost',
        'total_other_cost',
        'total_damage_cost',
        'total_compensation',
        'total_fabric_returned_cost',
        'total_rm_waste_cost',
        'total_rm_waste_abnormal_cost',
        'total_product_waste_cost',
        'total_product_waste_abnormal_cost',
        'grand_total',
        'paid_amount',
        'due_amount',
        'advance_deducted',
        'payment_status',
        'running_weighted_avg_cost',
        'final_cost_per_unit',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'completed_at',
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

    // Relationships
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class, 'production_order_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterial::class, 'production_order_id');
    }

    public function fabricIssuances(): HasMany
    {
        return $this->hasMany(FabricIssuance::class, 'production_order_id');
    }

    public function fabricReturns(): HasMany
    {
        return $this->hasMany(FabricReturn::class, 'production_order_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(ProductionLot::class, 'production_order_id');
    }

    public function damages(): HasMany
    {
        return $this->hasMany(ProductionDamage::class, 'production_order_id');
    }

    public function rmWastes(): HasMany
    {
        return $this->hasMany(RmWaste::class, 'production_order_id');
    }

    public function productWastes(): HasMany
    {
        return $this->hasMany(ProductWaste::class, 'production_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'approved_by');
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByFactory(Builder $query, int $factoryId): Builder
    {
        return $query->where('factory_id', $factoryId);
    }

    // Workflow checks
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canReceiveLot(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_PARTIAL_DELIVERED,
        ]);
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_IN_PROGRESS,
            self::STATUS_PARTIAL_DELIVERED,
        ]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_APPROVED,
        ]);
    }

    // Static helpers
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

    // Accessors
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bp-badge-dark',
            self::STATUS_APPROVED => 'bp-badge-primary',
            self::STATUS_IN_PROGRESS => 'bp-badge-info',
            self::STATUS_PARTIAL_DELIVERED => 'bp-badge-warning',
            self::STATUS_COMPLETED => 'bp-badge-success',
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

        static::creating(function (ProductionOrder $po) {
            if (empty($po->po_number)) {
                $po->po_number = static::generatePoNumber();
            }
        });
    }

    public static function generatePoNumber(): string
    {
        $prefix = 'PO-';

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
"""

files['ProductionOrderItem.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Modules\\Product\\Models\\Product;
use Modules\\Variant\\Models\\ProductVariant;

class ProductionOrderItem extends Model
{
    protected $table = 'production_order_items';

    protected $fillable = [
        'production_order_id',
        'catalog_id',
        'color_id',
        'size_id',
        'product_id',
        'variant_id',
        'quantity',
        'received_quantity',
        'damaged_quantity',
        'making_cost_per_unit',
        'current_cost_per_unit',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'making_cost_per_unit' => 'decimal:2',
        'current_cost_per_unit' => 'decimal:2',
    ];

    // Relationships
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(MfgColor::class, 'color_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(MfgSize::class, 'size_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function lotItems(): HasMany
    {
        return $this->hasMany(ProductionLotItem::class, 'production_order_item_id');
    }

    // Accessors
    public function getRemainingQuantityAttribute(): int
    {
        return (int) $this->quantity - (int) $this->received_quantity;
    }
}
"""

files['ProductionOrderMaterial.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class ProductionOrderMaterial extends Model
{
    protected $table = 'production_order_materials';

    protected $fillable = [
        'production_order_id',
        'raw_material_id',
        'planned_quantity',
        'expected_return_quantity',
        'actual_issued_quantity',
        'actual_returned_quantity',
        'unit_cost',
        'estimated_consumption',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'expected_return_quantity' => 'decimal:4',
        'actual_issued_quantity' => 'decimal:4',
        'actual_returned_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'estimated_consumption' => 'decimal:4',
    ];

    // Relationships
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    // Accessors
    public function getRemainingToIssueAttribute(): float
    {
        return (float) $this->planned_quantity - (float) $this->actual_issued_quantity;
    }

    public function getRemainingToReturnAttribute(): float
    {
        return (float) $this->expected_return_quantity - (float) $this->actual_returned_quantity;
    }
}
"""

files['FabricIssuance.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class FabricIssuance extends Model
{
    protected $table = 'fabric_issuances';

    protected $fillable = [
        'production_order_id',
        'issuance_number',
        'issuance_date',
        'factory_id',
        'total_cost',
        'notes',
        'issued_by',
    ];

    protected $casts = [
        'issuance_date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    // Relationships
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FabricIssuanceItem::class, 'issuance_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'issued_by');
    }

    // Boot
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (FabricIssuance $issuance) {
            if (empty($issuance->issuance_number)) {
                $issuance->issuance_number = static::generateIssuanceNumber();
            }
        });
    }

    public static function generateIssuanceNumber(): string
    {
        $prefix = 'FI-';

        $last = static::where('issuance_number', 'like', $prefix . '%')
            ->orderByDesc('issuance_number')
            ->value('issuance_number');

        $nextSeq = 1;
        if ($last) {
            $nextSeq = (int) str_replace($prefix, '', $last) + 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
"""

files['FabricIssuanceItem.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Modules\\Warehouse\\Models\\Warehouse;

class FabricIssuanceItem extends Model
{
    protected $table = 'fabric_issuance_items';

    protected $fillable = [
        'issuance_id',
        'raw_material_id',
        'warehouse_id',
        'bom_item_id',
        'quantity_issued',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'quantity_issued' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relationships
    public function issuance(): BelongsTo
    {
        return $this->belongsTo(FabricIssuance::class, 'issuance_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function bomItem(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderMaterial::class, 'bom_item_id');
    }
}
"""

files['FabricReturn.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class FabricReturn extends Model
{
    protected $table = 'fabric_returns';

    protected $fillable = [
        'production_order_id',
        'return_number',
        'return_date',
        'factory_id',
        'total_cost',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    // Relationships
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FabricReturnItem::class, 'return_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'received_by');
    }

    // Boot
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (FabricReturn $return) {
            if (empty($return->return_number)) {
                $return->return_number = static::generateReturnNumber();
            }
        });
    }

    public static function generateReturnNumber(): string
    {
        $prefix = 'FR-';

        $last = static::where('return_number', 'like', $prefix . '%')
            ->orderByDesc('return_number')
            ->value('return_number');

        $nextSeq = 1;
        if ($last) {
            $nextSeq = (int) str_replace($prefix, '', $last) + 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
"""

files['FabricReturnItem.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Modules\\Warehouse\\Models\\Warehouse;

class FabricReturnItem extends Model
{
    protected $table = 'fabric_return_items';

    // Condition constants
    public const CONDITION_GOOD = 'good';
    public const CONDITION_DAMAGED = 'damaged';
    public const CONDITION_PARTIAL = 'partial_usable';

    protected $fillable = [
        'return_id',
        'raw_material_id',
        'warehouse_id',
        'bom_item_id',
        'quantity_returned',
        'condition',
        'unit_cost',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relationships
    public function fabricReturn(): BelongsTo
    {
        return $this->belongsTo(FabricReturn::class, 'return_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function bomItem(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderMaterial::class, 'bom_item_id');
    }

    // Static helpers
    public static function getConditions(): array
    {
        return [
            self::CONDITION_GOOD => 'Good',
            self::CONDITION_DAMAGED => 'Damaged',
            self::CONDITION_PARTIAL => 'Partial Usable',
        ];
    }
}
"""

files['ProductionLot.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class ProductionLot extends Model
{
    protected $table = 'production_lots';

    protected $fillable = [
        'production_order_id',
        'lot_number',
        'delivery_date',
        'total_quantity',
        'good_quantity',
        'damaged_quantity',
        'delivery_cost',
        'other_cost',
        'stock_added',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'total_quantity' => 'integer',
        'good_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'delivery_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'stock_added' => 'boolean',
    ];

    // Relationships
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionLotItem::class, 'lot_id');
    }

    public function damages(): HasMany
    {
        return $this->hasMany(ProductionDamage::class, 'lot_id');
    }

    public function rmWastes(): HasMany
    {
        return $this->hasMany(RmWaste::class, 'lot_id');
    }

    public function productWastes(): HasMany
    {
        return $this->hasMany(ProductWaste::class, 'lot_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'received_by');
    }

    // Boot
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProductionLot $lot) {
            if (empty($lot->lot_number)) {
                $lot->lot_number = static::generateLotNumber($lot->production_order_id);
            }
        });
    }

    public static function generateLotNumber(int $productionOrderId): string
    {
        $po = ProductionOrder::find($productionOrderId);
        $prefix = 'LOT-' . ($po ? $po->po_number : 'PO-0000') . '-';

        $last = static::where('lot_number', 'like', $prefix . '%')
            ->orderByDesc('lot_number')
            ->value('lot_number');

        $nextSeq = 1;
        if ($last) {
            $nextSeq = (int) str_replace($prefix, '', $last) + 1;
        }

        return $prefix . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
    }
}
"""

files['ProductionLotItem.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Modules\\Product\\Models\\Product;
use Modules\\Variant\\Models\\ProductVariant;

class ProductionLotItem extends Model
{
    protected $table = 'production_lot_items';

    protected $fillable = [
        'lot_id',
        'production_order_item_id',
        'catalog_id',
        'color_id',
        'size_id',
        'product_id',
        'variant_id',
        'quantity',
        'good_quantity',
        'damaged_quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'good_quantity' => 'integer',
        'damaged_quantity' => 'integer',
    ];

    // Relationships
    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProductionLot::class, 'lot_id');
    }

    public function productionOrderItem(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderItem::class, 'production_order_item_id');
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(MfgColor::class, 'color_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(MfgSize::class, 'size_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
"""

files['ProductionDamage.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Modules\\Product\\Models\\Product;
use Modules\\Variant\\Models\\ProductVariant;

class ProductionDamage extends Model
{
    protected $table = 'production_damages';

    // Damage type constants
    public const TYPE_FACTORY_FAULT = 'factory_fault';
    public const TYPE_TRANSIT = 'transit';
    public const TYPE_STORAGE = 'storage';
    public const TYPE_MATERIAL_DEFECT = 'material_defect';
    public const TYPE_OTHER = 'other';

    // Responsibility constants
    public const RESP_FACTORY = 'factory';
    public const RESP_OWN = 'own';
    public const RESP_SUPPLIER = 'supplier';
    public const RESP_TRANSIT = 'transit';

    // Compensation status constants
    public const COMP_PENDING = 'pending';
    public const COMP_PARTIAL = 'partial';
    public const COMP_RECEIVED = 'received';
    public const COMP_WRITTEN_OFF = 'written_off';
    public const COMP_DEDUCTED = 'deducted';

    // Compensation method constants
    public const METHOD_CASH = 'cash';
    public const METHOD_DEDUCT = 'deduct';
    public const METHOD_REPLACEMENT = 'replacement';
    public const METHOD_MIXED = 'mixed';

    protected $fillable = [
        'production_order_id',
        'lot_id',
        'catalog_id',
        'color_id',
        'size_id',
        'product_id',
        'variant_id',
        'quantity',
        'estimated_cost_per_unit',
        'total_damage_cost',
        'damage_type',
        'responsibility',
        'compensation_amount',
        'compensation_status',
        'compensation_received',
        'compensation_method',
        'damage_date',
        'notes',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'estimated_cost_per_unit' => 'decimal:2',
        'total_damage_cost' => 'decimal:2',
        'compensation_amount' => 'decimal:2',
        'compensation_received' => 'decimal:2',
        'damage_date' => 'date',
    ];

    // Relationships
    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProductionLot::class, 'lot_id');
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(MfgColor::class, 'color_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(MfgSize::class, 'size_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function compensations(): HasMany
    {
        return $this->hasMany(DamageCompensation::class, 'damage_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'created_by');
    }

    // Static helpers
    public static function getDamageTypes(): array
    {
        return [
            self::TYPE_FACTORY_FAULT => 'Factory Fault',
            self::TYPE_TRANSIT => 'Transit',
            self::TYPE_STORAGE => 'Storage',
            self::TYPE_MATERIAL_DEFECT => 'Material Defect',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public static function getResponsibilities(): array
    {
        return [
            self::RESP_FACTORY => 'Factory',
            self::RESP_OWN => 'Own',
            self::RESP_SUPPLIER => 'Supplier',
            self::RESP_TRANSIT => 'Transit',
        ];
    }

    public static function getCompensationStatuses(): array
    {
        return [
            self::COMP_PENDING => 'Pending',
            self::COMP_PARTIAL => 'Partial',
            self::COMP_RECEIVED => 'Received',
            self::COMP_WRITTEN_OFF => 'Written Off',
            self::COMP_DEDUCTED => 'Deducted',
        ];
    }

    public static function getCompensationMethods(): array
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_DEDUCT => 'Deduct from Payment',
            self::METHOD_REPLACEMENT => 'Replacement',
            self::METHOD_MIXED => 'Mixed',
        ];
    }
}
"""

files['DamageCompensation.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class DamageCompensation extends Model
{
    protected $table = 'damage_compensations';

    protected $fillable = [
        'damage_id',
        'compensation_date',
        'amount',
        'method',
        'payment_account_id',
        'factory_payment_id',
        'replacement_lot_id',
        'reference',
        'notes',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'compensation_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function damage(): BelongsTo
    {
        return $this->belongsTo(ProductionDamage::class, 'damage_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'created_by');
    }
}
"""

files['RmWaste.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class RmWaste extends Model
{
    protected $table = 'rm_wastes';

    // Waste type constants
    public const TYPE_CUTTING = 'cutting';
    public const TYPE_DEFECTIVE = 'defective';
    public const TYPE_SPILLAGE = 'spillage';
    public const TYPE_SHRINKAGE = 'shrinkage';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'production_order_id',
        'lot_id',
        'raw_material_id',
        'quantity_wasted',
        'unit_cost',
        'total_cost',
        'waste_type',
        'is_normal',
        'waste_percentage',
        'notes',
        'journal_entry_id',
        'created_by',
        'waste_date',
    ];

    protected $casts = [
        'quantity_wasted' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'waste_percentage' => 'decimal:2',
        'is_normal' => 'boolean',
        'waste_date' => 'date',
    ];

    // Relationships
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProductionLot::class, 'lot_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'created_by');
    }

    // Static helpers
    public static function getWasteTypes(): array
    {
        return [
            self::TYPE_CUTTING => 'Cutting',
            self::TYPE_DEFECTIVE => 'Defective',
            self::TYPE_SPILLAGE => 'Spillage',
            self::TYPE_SHRINKAGE => 'Shrinkage',
            self::TYPE_OTHER => 'Other',
        ];
    }
}
"""

files['ProductWaste.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Modules\\Product\\Models\\Product;
use Modules\\Variant\\Models\\ProductVariant;

class ProductWaste extends Model
{
    protected $table = 'product_wastes';

    // Waste type constants
    public const TYPE_QUALITY_REJECTION = 'quality_rejection';
    public const TYPE_MANUFACTURING_DEFECT = 'manufacturing_defect';
    public const TYPE_UNRECOVERABLE = 'unrecoverable';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'production_order_id',
        'lot_id',
        'catalog_id',
        'color_id',
        'size_id',
        'product_id',
        'variant_id',
        'quantity_wasted',
        'unit_cost',
        'total_cost',
        'waste_type',
        'is_normal',
        'waste_percentage',
        'notes',
        'journal_entry_id',
        'created_by',
        'waste_date',
    ];

    protected $casts = [
        'quantity_wasted' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'waste_percentage' => 'decimal:2',
        'is_normal' => 'boolean',
        'waste_date' => 'date',
    ];

    // Relationships
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProductionLot::class, 'lot_id');
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(MfgColor::class, 'color_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(MfgSize::class, 'size_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'created_by');
    }

    // Static helpers
    public static function getWasteTypes(): array
    {
        return [
            self::TYPE_QUALITY_REJECTION => 'Quality Rejection',
            self::TYPE_MANUFACTURING_DEFECT => 'Manufacturing Defect',
            self::TYPE_UNRECOVERABLE => 'Unrecoverable',
            self::TYPE_OTHER => 'Other',
        ];
    }
}
"""

files['RmSupplierPayment.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\SoftDeletes;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class RmSupplierPayment extends Model
{
    use SoftDeletes;

    protected $table = 'rm_supplier_payments';

    protected $fillable = [
        'payment_number',
        'supplier_id',
        'purchase_order_id',
        'payment_id',
        'payment_date',
        'amount',
        'payment_method',
        'payment_account_id',
        'payment_type',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(RawMaterialSupplier::class, 'supplier_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(RmPurchaseOrder::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'created_by');
    }

    // Boot
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RmSupplierPayment $payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber();
            }
        });
    }

    public static function generatePaymentNumber(): string
    {
        $prefix = 'RMSP-';

        $last = static::withTrashed()
            ->where('payment_number', 'like', $prefix . '%')
            ->orderByDesc('payment_number')
            ->value('payment_number');

        $nextSeq = 1;
        if ($last) {
            $nextSeq = (int) str_replace($prefix, '', $last) + 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
"""

files['FactoryPayment.php'] = """<?php

namespace Modules\\Manufacturing\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\SoftDeletes;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class FactoryPayment extends Model
{
    use SoftDeletes;

    protected $table = 'factory_payments';

    protected $fillable = [
        'payment_number',
        'factory_id',
        'production_order_id',
        'payment_id',
        'payment_date',
        'amount',
        'payment_method',
        'payment_account_id',
        'payment_type',
        'deduction_amount',
        'net_amount',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    // Relationships
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\\App\\Models\\User::class, 'created_by');
    }

    // Boot
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (FactoryPayment $payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber();
            }
        });
    }

    public static function generatePaymentNumber(): string
    {
        $prefix = 'FP-';

        $last = static::withTrashed()
            ->where('payment_number', 'like', $prefix . '%')
            ->orderByDesc('payment_number')
            ->value('payment_number');

        $nextSeq = 1;
        if ($last) {
            $nextSeq = (int) str_replace($prefix, '', $last) + 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
"""

for name, content in files.items():
    path = os.path.join(models_dir, name)
    with open(path, 'w', newline='\n') as f:
        f.write(content)
    print(f'Created {name}')

print('Done! All models created.')
