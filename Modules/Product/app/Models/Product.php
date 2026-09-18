<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;
use Modules\Brand\Models\Brand;
use Modules\Unit\Models\Unit;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Modules\Ecommerce\Support\HasSlugHistory;

class Product extends Model implements SearchableInterface
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalSearch, HasSlugHistory;

    protected string $activityLogName = 'products';

    protected $fillable = [
        'name', 'slug', 'sku', 'barcode', 'model', 'category_id', 'brand_id', 'supplier_id',
        'unit_id', 'purchase_unit_id', 'sale_unit_id', 'product_type', 'cost_price', 'sell_price', 'wholesale_price', 'resell_price',
        'vat_rate', 'vat_inclusive', 'discount_type', 'discount_value', 'description',
        'long_description', 'warranty', 'weight', 'country_of_origin', 'min_stock_alert',
        'max_stock_level', 'valuation_method', 'status', 'show_in_pos', 'track_stock',
        'allow_negative_stock', 'ecom_sync', 'ecom_visible', 'seo_title', 'seo_description', 'seo_image',
        'created_by', 'position', 'thumbnail',
    ];

    protected $casts = [
        'cost_price'           => 'decimal:2',
        'sell_price'           => 'decimal:2',
        'wholesale_price'      => 'decimal:2',
        'resell_price'         => 'decimal:2',
        'vat_rate'             => 'decimal:2',
        'discount_value'       => 'decimal:2',
        'weight'               => 'decimal:3',
        'show_in_pos'          => 'boolean',
        'track_stock'          => 'boolean',
        'allow_negative_stock' => 'boolean',
        'ecom_sync'            => 'boolean',
        'ecom_visible'         => 'boolean',
        'min_stock_alert'      => 'integer',
        'max_stock_level'      => 'integer',
        'supplier_id'          => 'integer',
        'created_by'           => 'integer',
        'position'             => 'integer',
    ];

    // ── Relationships ──

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function catalogPositions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Modules\Product\Models\CatalogPosition::class, 'positionable');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function saleUnit()
    {
        return $this->belongsTo(Unit::class, 'sale_unit_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    /**
     * Variants in their attribute's own order — XS, S, M, L … — rather than the
     * order they were created in. Adding a size to an existing product must not
     * park it at the bottom of every picker, which is what plain id order did.
     *
     * The position comes from variant_attribute_values.sort_order, reached
     * through the pivot. Ordered here on the relation rather than at each call
     * site because roughly forty screens load variants and every one of them
     * wants the same sequence. Variants carrying no attribute value sort last,
     * then by id, so the order is always stable.
     */
    public function variants()
    {
        return $this->hasMany(\Modules\Variant\Models\ProductVariant::class)
            ->orderByRaw('COALESCE((
                SELECT MIN(vav.sort_order)
                FROM product_variant_values pvv
                JOIN variant_attribute_values vav ON vav.id = pvv.variant_attribute_value_id
                WHERE pvv.product_variant_id = product_variants.id
            ), 2147483647)')
            ->orderBy('product_variants.id');
    }

    public function warehouseStock()
    {
        return $this->hasMany(\Modules\Inventory\Models\WarehouseStock::class, 'product_id');
    }

    public function sizeChartOverrides()
    {
        return $this->hasMany(ProductSizeChartValue::class);
    }

    public function reviews()
    {
        return $this->hasMany(\Modules\Ecommerce\Models\ProductReview::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(\Modules\Ecommerce\Models\ProductReview::class)->where('is_approved', true);
    }

    /**
     * Inverse of FlashDeal::products(). Lets the storefront query "products
     * in any active flash deal" and read the per-deal pivot discount.
     */
    public function flashDeals()
    {
        return $this->belongsToMany(
            \Modules\Ecommerce\Models\FlashDeal::class,
            'flash_deal_product',
        )->withPivot('discount_type', 'discount_value', 'sort_order');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Products that may surface on the customer-facing storefront: active AND
     * not sitting in a deactivated category (or under a deactivated ancestor).
     * Uncategorised products — and products whose category was deleted — stay
     * visible; only the inactive category subtree is pruned. Use this instead
     * of active() for every storefront listing/search/cart/checkout query so
     * deactivating a category truly hides its products everywhere.
     */
    public function scopeStorefrontVisible($query)
    {
        $hiddenCategoryIds = Category::hiddenStorefrontIds();

        return $query->where('status', 'active')
            ->when($hiddenCategoryIds, fn ($q) => $q->whereNotIn('category_id', $hiddenCategoryIds));
    }

    /**
     * Products that are available to buy: either they don't track stock,
     * allow negative stock, or have a positive total stock quantity.
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_stock', false)
              ->orWhere('allow_negative_stock', true)
              ->orWhereIn('id', function ($sub) {
                  $sub->from('warehouse_stock')
                      ->select('product_id')
                      ->groupBy('product_id')
                      ->havingRaw('SUM(quantity) > 0');
              });
        });
    }

    /**
     * Products that are sold out: they track stock, don't allow negative
     * stock, and have no positive total stock quantity.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('track_stock', true)
            ->where('allow_negative_stock', false)
            ->whereNotIn('id', function ($sub) {
                $sub->from('warehouse_stock')
                    ->select('product_id')
                    ->groupBy('product_id')
                    ->havingRaw('SUM(quantity) > 0');
            });
    }

    /**
     * Products running low: they track stock and have a positive total that is
     * at or below the product's own min_stock_alert threshold. Mirrors the
     * warning badge shown in the product list (0 < total <= min_stock_alert).
     */
    public function scopeLowStock($query)
    {
        $sumSql = '(SELECT COALESCE(SUM(ws.quantity), 0) FROM warehouse_stock ws WHERE ws.product_id = products.id)';

        return $query->where('track_stock', true)
            ->whereRaw("{$sumSql} > 0")
            ->whereRaw("{$sumSql} <= products.min_stock_alert");
    }

    public function scopeForPos($query)
    {
        return $query->where('show_in_pos', true)->where('status', 'active');
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand($query, int $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('product_type', $type);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', '%' . $term . '%')
              ->orWhere('sku', 'like', '%' . $term . '%')
              ->orWhere('barcode', 'like', '%' . $term . '%')
              ->orWhere('model', 'like', '%' . $term . '%');
        });
    }

    // ── Accessors ──

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getPrimaryImageAttribute()
    {
        return $this->images->where('is_primary', true)->first();
    }

    public function getImageAttribute(): ?string
    {
        $primary = $this->primary_image;
        return $primary ? $primary->image_path : $this->images->first()?->image_path;
    }

    /**
     * The single representative image path for line-item / row displays:
     * the dedicated thumbnail when set, else the gallery (primary/first) image,
     * else null. Returns a RAW stored path (callers wrap with upload_url()),
     * matching getImageAttribute(). When a thumbnail exists the gallery relation
     * is never touched, avoiding an extra query.
     */
    public function getDisplayImageAttribute(): ?string
    {
        return $this->thumbnail ?: $this->image;
    }

    public function getFormattedCostPriceAttribute(): string
    {
        return $this->formatBdt($this->cost_price);
    }

    public function getFormattedSellPriceAttribute(): string
    {
        return $this->formatBdt($this->sell_price);
    }

    public function getFormattedWholesalePriceAttribute(): string
    {
        return $this->formatBdt($this->wholesale_price);
    }

    public function getFormattedResellPriceAttribute(): string
    {
        return $this->formatBdt($this->resell_price);
    }

    public function getProfitAttribute(): float
    {
        return (float) $this->sell_price - (float) $this->cost_price;
    }

    public function getProfitMarginAttribute(): float
    {
        if ((float) $this->sell_price <= 0) {
            return 0;
        }
        return round(($this->profit / (float) $this->sell_price) * 100, 2);
    }

    public function getFormattedProfitAttribute(): string
    {
        return $this->formatBdt($this->profit);
    }

    public function getTotalStockAttribute(): int
    {
        // Prefer an eager-loaded sum (withSum) to avoid N+1 in lists; otherwise
        // compute live from warehouse_stock.
        if (array_key_exists('warehouse_stock_sum_quantity', $this->attributes)) {
            return (int) $this->attributes['warehouse_stock_sum_quantity'];
        }

        return (int) $this->warehouseStock()->sum('quantity');
    }

    /**
     * Whether the product is purchasable. Mirrors scopeInStock: untracked or
     * negative-stock-allowed products are always available; otherwise it needs
     * a positive total stock quantity.
     */
    public function getIsInStockAttribute(): bool
    {
        return ! $this->track_stock
            || $this->allow_negative_stock
            || $this->total_stock > 0;
    }

    public function isSimple(): bool
    {
        return $this->product_type === 'simple';
    }

    public function isVariable(): bool
    {
        return $this->product_type === 'variable';
    }

    public function isService(): bool
    {
        return $this->product_type === 'service';
    }

    /**
     * Single source of truth for the storefront price block.
     *
     * Returns an object with everything a price view needs:
     *   sell       — original sell_price (float)
     *   effective  — current after-discount price (float)
     *   has_discount      — boolean
     *   discount_percent  — integer
     *   campaign_badge    — string|null (only when a campaign was decorated)
     *
     * Priority: campaign decoration (CampaignService::decorate set
     * $product->campaign, ->campaign_price, ->campaign_discount_percentage)
     * beats the product-level discount_type/discount_value. Without either,
     * effective == sell.
     */
    public function displayPrice(): object
    {
        $sell      = (float) $this->sell_price;
        $effective = $sell;
        $hasDiscount = false;
        $percent     = 0;
        $badge       = null;

        if (! empty($this->campaign)) {
            $hasDiscount = true;
            $effective   = (float) $this->campaign_price;
            $percent     = (int) ($this->campaign_discount_percentage ?? 0);
            $badge       = $this->campaign->badge_label ?: $this->campaign->name;
        } elseif ($this->discount_type && $this->discount_value > 0) {
            $hasDiscount = true;
            if ($this->discount_type === 'percentage') {
                $percent   = (int) round((float) $this->discount_value);
                $effective = $sell - ($sell * (float) $this->discount_value / 100);
            } elseif ($this->discount_type === 'fixed') {
                $effective = $sell - (float) $this->discount_value;
                if ($sell > 0) {
                    $percent = (int) round(((float) $this->discount_value / $sell) * 100);
                }
            }
            $effective = max(0, $effective);
        }

        // Round the effective price UP to the next whole BDT so the
        // storefront never quotes (or charges) a half-taka amount like
        // 76.50. Matches StorefrontService::calculateEffectivePrice() so
        // cards, side drawer, checkout, and the server-side guard all
        // agree on the same integer figure.
        return (object) [
            'sell'             => $sell,
            'effective'        => (float) ceil($effective),
            'has_discount'     => $hasDiscount,
            'discount_percent' => $percent,
            'campaign_badge'   => $badge,
        ];
    }

    // ── Boot ──

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });

        static::deleting(function (Product $product) {
            $product->catalogPositions()->delete();
        });
    }

    public static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    // ── SearchableInterface Methods ──

    public static function getSearchType(): string { return 'Product'; }
    public static function getSearchIcon(): string { return 'fa-boxes-stacked'; }
    public static function getSearchRoute(): string { return 'products.show'; }
    public static function getSearchPermission(): ?string { return 'products.view'; }
    public static function getSearchableColumns(): array { return ['name', 'sku', 'barcode']; }
    public static function getSearchOrder(): int { return 10; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return ($this->sku ?? '') . ' — ' . currency_symbol() . ' ' . number_format($this->sell_price ?? 0); }

    /**
     * Match on the product's own columns OR any of its variants' SKU/barcode,
     * so scanning/typing a variant code surfaces the parent product.
     */
    public function scopeGlobalSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        $like = '%' . $term . '%';

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($like, $term) {
            foreach (static::getSearchableColumns() as $column) {
                $q->orWhere($column, 'like', $like);
            }

            $q->orWhereHas('variants', function (\Illuminate\Database\Eloquent\Builder $v) use ($like) {
                $v->where('sku', 'like', $like)->orWhere('barcode', 'like', $like);
            });
        });
    }

    // ── Helpers ──

    private function formatBdt(float|string|null $amount): string
    {
        if ($amount === null) {
            return '0';
        }

        $amount = (float) $amount;
        $isNegative = $amount < 0;
        $amount = abs($amount);

        $formatted = number_format($amount, 2, '.', '');
        $parts = explode('.', $formatted);
        $whole = $parts[0];
        $decimal = $parts[1] ?? '00';

        // Bangladesh lakh format
        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $whole = $rest . ',' . $last3;
        }

        $result = $whole;
        if ($decimal !== '00') {
            $result .= '.' . $decimal;
        }

        return ($isNegative ? '-' : '') . $result;
    }
}
