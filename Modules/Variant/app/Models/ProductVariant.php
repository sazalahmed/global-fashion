<?php

namespace Modules\Variant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Models\Product;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'barcode', 'cost_price', 'sell_price',
        'wholesale_price', 'resell_price', 'weight', 'image_path', 'is_active', 'is_default',
    ];

    protected $casts = [
        'product_id'      => 'integer',
        'cost_price'      => 'decimal:2',
        'sell_price'      => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'resell_price'    => 'decimal:2',
        'weight'          => 'decimal:3',
        'is_active'       => 'boolean',
        'is_default'      => 'boolean',
    ];

    // ── Relationships ──

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(VariantAttributeValue::class, 'product_variant_values');
    }

    /**
     * Per-warehouse stock rows for this variant. Summing quantity gives the
     * available stock (see getTotalStockAttribute); exposed as a relation so
     * lists can eager-load the sum via withSum() instead of N+1 per variant.
     */
    public function warehouseStock()
    {
        return $this->hasMany(\Modules\Inventory\Models\WarehouseStock::class, 'variant_id');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Accessors ──

    public function getEffectiveCostPriceAttribute()
    {
        return $this->cost_price ?? $this->product->cost_price;
    }

    public function getEffectiveSellPriceAttribute()
    {
        return $this->sell_price ?? $this->product->sell_price;
    }

    public function getEffectiveWholesalePriceAttribute()
    {
        return $this->wholesale_price
            ?? $this->product->wholesale_price
            ?? $this->effective_sell_price;
    }

    public function getEffectiveResellPriceAttribute()
    {
        return $this->resell_price
            ?? $this->product->resell_price
            ?? $this->effective_wholesale_price;
    }

    public function getEffectiveWeightAttribute()
    {
        return $this->weight ?? $this->product->weight;
    }

    public function getEffectiveImageAttribute(): ?string
    {
        return $this->image_path ?? $this->product->image;
    }

    public function getVariantNameAttribute(): string
    {
        return $this->attributeValues
            ->sortBy(fn ($v) => $v->attribute?->sort_order ?? 0)
            ->pluck('value')
            ->implode(' / ');
    }

    public function getTotalStockAttribute(): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('warehouse_stock')
            ->where('product_id', $this->product_id)
            ->where('variant_id', $this->id)
            ->sum('quantity');
    }
}
