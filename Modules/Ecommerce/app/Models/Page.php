<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'is_published',
        'seo_title', 'seo_description', 'seo_image',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Slugs that would collide with real storefront routes. A page may never
     * use one of these, so the /{slug} catch-all can only resolve to a
     * legitimately created page.
     */
    public const RESERVED_SLUGS = [
        'shop', 'cart', 'checkout', 'blog', 'category', 'categories',
        'flash-deals', 'combos', 'wishlist', 'compare', 'contact', 'customer',
        'search', 'faq', 'auth', 'page', 'pages', 'admin', 'ecommerce', 'api',
        'login', 'register', 'newsletter',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if (blank($page->slug)) {
                $page->slug = static::uniqueSlug(Str::slug($page->title));
            }
        });
    }

    protected static function uniqueSlug(string $base): string
    {
        $slug = $base ?: 'page';
        $i = 1;
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
