<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionDamage extends Model
{
    protected $table = 'production_damages';

    const TYPE_FACTORY_FAULT = 'factory_fault';
    const TYPE_TRANSIT = 'transit';
    const TYPE_STORAGE = 'storage';
    const TYPE_MATERIAL_DEFECT = 'material_defect';
    const TYPE_OTHER = 'other';

    const RESP_FACTORY = 'factory';
    const RESP_OWN = 'own';
    const RESP_SUPPLIER = 'supplier';
    const RESP_TRANSIT = 'transit';

    const COMP_PENDING = 'pending';
    const COMP_PARTIAL = 'partial';
    const COMP_RECEIVED = 'received';
    const COMP_WRITTEN_OFF = 'written_off';
    const COMP_DEDUCTED = 'deducted';

    const METHOD_CASH = 'cash';
    const METHOD_DEDUCT = 'deduct_from_payable';
    const METHOD_REPLACEMENT = 'replacement';
    const METHOD_MIXED = 'mixed';

    protected $fillable = [
        'lot_id', 'production_order_id', 'catalog_id', 'color_id', 'size_id',
        'product_id', 'variant_id', 'quantity', 'estimated_cost_per_unit', 'total_damage_cost',
        'damage_type', 'responsibility', 'compensation_amount', 'compensation_status',
        'compensation_received', 'compensation_method', 'damage_date', 'notes',
        'journal_entry_id', 'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'estimated_cost_per_unit' => 'decimal:2',
        'total_damage_cost' => 'decimal:2',
        'compensation_amount' => 'decimal:2',
        'compensation_received' => 'decimal:2',
        'damage_date' => 'date',
    ];

    public static function getDamageTypes(): array
    {
        return [
            self::TYPE_FACTORY_FAULT => 'Factory Fault',
            self::TYPE_TRANSIT => 'Transit Damage',
            self::TYPE_STORAGE => 'Storage Damage',
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
            self::METHOD_DEDUCT => 'Deduct from Payable',
            self::METHOD_REPLACEMENT => 'Replacement',
            self::METHOD_MIXED => 'Mixed',
        ];
    }

    public function lot(): BelongsTo { return $this->belongsTo(ProductionLot::class, 'lot_id'); }
    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function catalog(): BelongsTo { return $this->belongsTo(Catalog::class); }
    public function color(): BelongsTo { return $this->belongsTo(MfgColor::class, 'color_id'); }
    public function size(): BelongsTo { return $this->belongsTo(MfgSize::class, 'size_id'); }
    public function product(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(\Modules\Variant\Models\ProductVariant::class, 'variant_id'); }
    public function compensations(): HasMany { return $this->hasMany(DamageCompensation::class, 'damage_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
