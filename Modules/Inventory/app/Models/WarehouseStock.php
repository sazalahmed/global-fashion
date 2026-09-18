<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStock extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'warehouse_stock';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'quantity',
        'reserved_quantity',
        'reorder_level',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Variant\Models\ProductVariant::class, 'variant_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to items at or below their reorder level. Used by manufacturing,
     * reports and the low-stock alert notifications — keep it reorder_level based.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0);
    }

    /**
     * Low-stock by the product's own min_stock_alert threshold (0 < qty <=
     * min_stock_alert). This mirrors the Products list/stats definition so the
     * Inventory Overview page and the Products page stay consistent. Uses
     * whereExists (no join) to avoid column clashes with pagination/eager loads.
     */
    public function scopeLowByProductAlert(Builder $query): Builder
    {
        return $query->where('warehouse_stock.quantity', '>', 0)
            ->whereExists(function ($sub) {
                $sub->from('products')
                    ->whereColumn('products.id', 'warehouse_stock.product_id')
                    ->where('products.min_stock_alert', '>', 0)
                    ->whereColumn('products.min_stock_alert', '>=', 'warehouse_stock.quantity');
            });
    }

    /**
     * Scope to a specific product.
     */
    public function scopeByProduct(Builder $query, int $id): Builder
    {
        return $query->where('product_id', $id);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * The quantity this row is judged against. The per-warehouse reorder_level
     * is unset across the board, so the working threshold is the product's own
     * min_stock_alert — the same figure the Products list and Inventory
     * Overview use. Falls back to reorder_level where one has been set.
     */
    public function getAlertLevelAttribute(): int
    {
        return (int) ($this->reorder_level > 0
            ? $this->reorder_level
            : ($this->product->min_stock_alert ?? 0));
    }

    /**
     * Get the available (non-reserved) quantity.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }
}
