<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FabricIssuanceItem extends Model
{
    protected $table = 'fabric_issuance_items';

    protected $fillable = [
        'issuance_id', 'raw_material_id', 'bom_item_id',
        'quantity_issued', 'unit_cost', 'line_total',
    ];

    protected $casts = [
        'quantity_issued' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function issuance(): BelongsTo { return $this->belongsTo(FabricIssuance::class, 'issuance_id'); }
    public function rawMaterial(): BelongsTo { return $this->belongsTo(RawMaterial::class); }
    public function bomItem(): BelongsTo { return $this->belongsTo(ProductionOrderMaterial::class, 'bom_item_id'); }
}
