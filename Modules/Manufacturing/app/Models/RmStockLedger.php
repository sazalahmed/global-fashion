<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmStockLedger extends Model
{
    /**
     * This table only has created_at, no updated_at.
     */
    public const UPDATED_AT = null;

    protected $table = 'rm_stock_ledger';

    // Type constants
    public const TYPE_PURCHASE_RECEIVE = 'purchase_receive';
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_ISSUE_TO_FACTORY = 'issue_to_factory';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'raw_material_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity_in',
        'quantity_out',
        'balance',
        'unit_cost',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity_in' => 'decimal:4',
        'quantity_out' => 'decimal:4',
        'balance' => 'decimal:4',
        'unit_cost' => 'decimal:2',
    ];

    public static function getTypes(): array
    {
        return [
            self::TYPE_PURCHASE_RECEIVE => 'Purchase Receive',
            self::TYPE_DAMAGE => 'Damage',
            self::TYPE_ISSUE_TO_FACTORY => 'Issue to Factory',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_RETURN => 'Return',
        ];
    }

    // Relationships
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
