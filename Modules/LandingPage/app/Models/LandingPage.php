<?php

namespace Modules\LandingPage\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Product;

class LandingPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'template', 'is_active',
        'hero_title', 'hero_subtitle', 'hero_image',
        'offer_price', 'original_price',
        'video_url', 'sections', 'product_ids',
        'delivery_inside_dhaka', 'delivery_outside_dhaka',
        'contact_phone', 'custom_css',
        'meta_title', 'meta_description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active'              => 'boolean',
            'sections'               => 'json',
            'product_ids'            => 'json',
            'offer_price'            => 'decimal:2',
            'original_price'         => 'decimal:2',
            'delivery_inside_dhaka'  => 'decimal:2',
            'delivery_outside_dhaka' => 'decimal:2',
        ];
    }

    // ── Relationships ──

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Accessors ──

    public function getProductsAttribute()
    {
        if (empty($this->product_ids)) {
            return collect();
        }

        return Product::whereIn('id', $this->product_ids)
            ->active()
            ->with('images')
            ->get();
    }

    public function getTemplateNameAttribute(): string
    {
        return match ($this->template) {
            'template-1' => 'T-Shirt',
            'template-2' => 'Shoes',
            'template-3' => 'Perfume',
            'template-4' => 'Beauty',
            'template-5' => 'Fashion',
            default       => ucfirst(str_replace('-', ' ', $this->template)),
        };
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
