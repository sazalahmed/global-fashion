<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Combo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'thumbnail', 'description', 'combo_price',
        'size_required',
        'discount_type', 'discount_value', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'combo_price'    => 'decimal:2',
        'discount_value' => 'decimal:2',
        'is_active'      => 'boolean',
        'size_required'  => 'boolean',
        'sort_order'     => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Combo $combo) {
            if (empty($combo->slug)) {
                $base = Str::slug($combo->name) ?: 'combo';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $combo->slug = $slug;
            }
        });

        static::deleting(function (Combo $combo) {
            $combo->catalogPositions()->delete();
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComboItem::class)->orderBy('sort_order');
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(ComboImage::class)->orderBy('sort_order');
    }

    public function homepageSections(): BelongsToMany
    {
        return $this->belongsToMany(HomepageSection::class, 'homepage_section_combos')
            ->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Category\Models\Category::class, 'category_combo');
    }

    public function catalogPositions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Modules\Product\Models\CatalogPosition::class, 'positionable');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
