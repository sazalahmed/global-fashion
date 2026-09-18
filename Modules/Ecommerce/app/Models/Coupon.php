<?php

namespace Modules\Ecommerce\Models;

use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model implements SearchableInterface
{
    use HasGlobalSearch;
    protected $fillable = [
        'code', 'name', 'type', 'value', 'min_order_amount',
        'max_discount_amount', 'usage_limit', 'used_count',
        'per_customer_limit', 'start_date', 'end_date', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }

    public function isValid(float $orderAmount = 0): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }
        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }
        if ($orderAmount < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $orderAmount): float
    {
        $discount = $this->type === 'percentage'
            ? $orderAmount * ($this->value / 100)
            : $this->value;

        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return round($discount, 2);
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Coupon'; }
    public static function getSearchIcon(): string { return 'fa-ticket'; }
    public static function getSearchRoute(): string { return 'ecommerce.coupons'; }
    public static function getSearchPermission(): ?string { return 'ecommerce.view'; }
    public static function getSearchableColumns(): array { return ['code', 'name']; }
    public static function getSearchOrder(): int { return 62; }
    public function getSearchTitle(): string { return $this->code ?? ''; }
    public function getSearchSubtitle(): string { return ($this->name ?? '') . ' — ' . ($this->type ?? ''); }
    public function getSearchUrl(): string { return route('ecommerce.coupons') . '?highlight=' . $this->id; }
}
