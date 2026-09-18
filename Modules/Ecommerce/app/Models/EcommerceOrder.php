<?php

namespace Modules\Ecommerce\Models;

use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceOrder extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

    protected $fillable = [
        'order_number', 'customer_id', 'customer_name', 'customer_email',
        'customer_phone', 'shipping_address', 'billing_address', 'status',
        'payment_status', 'payment_method', 'subtotal', 'discount_amount',
        'tax_amount', 'shipping_charge', 'shipping_zone_id', 'grand_total', 'coupon_code',
        'sale_id', 'courier_provider_id',
        'consignment_id', 'tracking_number', 'tracking_url',
        'courier_status', 'fraud_report', 'fraud_score',
        'shipped_at', 'delivered_at', 'source', 'notes', 'item_notes',
        'tracking_data', 'purchase_reported_at', 'refund_reported_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'fraud_report' => 'array',
        'fraud_score' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'tracking_data' => 'array',
        'purchase_reported_at' => 'datetime',
        'refund_reported_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(EcommerceOrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Customer\Models\Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(\Modules\Sale\Models\Sale::class);
    }

    public function courierProvider(): BelongsTo
    {
        return $this->belongsTo(CourierProvider::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Global Search ──
    // Online orders are mirrored into the sales table, so search results
    // jump straight to the mirrored Sale page. The route key is unused
    // because getSearchUrl() below builds the URL with the sale_id.
    public static function getSearchType(): string { return 'Online Order'; }
    public static function getSearchIcon(): string { return 'fa-globe'; }
    public static function getSearchRoute(): string { return 'sales.show'; }
    public static function getSearchPermission(): ?string { return 'sales.view'; }
    public static function getSearchableColumns(): array { return ['order_number', 'customer_name', 'customer_phone']; }
    public static function getSearchOrder(): int { return 60; }
    public function getSearchTitle(): string { return $this->order_number ?? ''; }
    public function getSearchSubtitle(): string { return ($this->customer_name ?? '') . ' — ' . currency_symbol() . ' ' . number_format($this->grand_total ?? 0); }
    public function getSearchUrl(): string
    {
        return $this->sale_id
            ? route('sales.show', $this->sale_id)
            : route('sales.index', ['source' => 'ecommerce']);
    }
}
