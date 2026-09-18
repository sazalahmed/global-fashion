<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmReceiveItem extends Model
{
    protected $table = 'rm_receive_items';

    protected $fillable = [
        'receive_id',
        'purchase_item_id',
        'raw_material_id',
        'quantity_received',
        'quantity_damaged',
        'damage_notes',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:4',
        'quantity_damaged' => 'decimal:4',
    ];

    // Relationships
    public function receive(): BelongsTo
    {
        return $this->belongsTo(RmReceive::class, 'receive_id');
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(RmPurchaseItem::class, 'purchase_item_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
