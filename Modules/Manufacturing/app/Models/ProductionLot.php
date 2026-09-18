<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionLot extends Model
{
    protected $table = 'production_lots';

    protected $fillable = [
        'production_order_id', 'lot_number', 'delivery_date',
        'making_cost', 'delivery_charge', 'other_costs', 'other_costs_note',
        'total_received', 'total_damaged', 'total_wasted', 'total_good',
        'rm_waste_cost', 'product_waste_cost', 'provisional_cost_per_unit',
        'stock_added', 'notes', 'received_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'making_cost' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'total_received' => 'integer',
        'total_damaged' => 'integer',
        'total_wasted' => 'integer',
        'total_good' => 'integer',
        'rm_waste_cost' => 'decimal:2',
        'product_waste_cost' => 'decimal:2',
        'provisional_cost_per_unit' => 'decimal:2',
        'stock_added' => 'boolean',
    ];

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function items(): HasMany { return $this->hasMany(ProductionLotItem::class, 'lot_id'); }
    public function damages(): HasMany { return $this->hasMany(ProductionDamage::class, 'lot_id'); }
    public function rmWastes(): HasMany { return $this->hasMany(RmWaste::class, 'lot_id'); }
    public function productWastes(): HasMany { return $this->hasMany(ProductWaste::class, 'lot_id'); }
    public function receiver(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'received_by'); }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->lot_number)) {
                $model->lot_number = static::generateLotNumber($model->production_order_id);
            }
        });
    }

    protected static function generateLotNumber(int $productionOrderId): string
    {
        $po = ProductionOrder::find($productionOrderId);
        $prefix = 'LOT-' . ($po ? $po->po_number : 'PO') . '-';
        $count = static::where('production_order_id', $productionOrderId)->count();
        return $prefix . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
    }
}
