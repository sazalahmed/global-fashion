<?php

namespace Modules\Ecommerce\Models;

use App\Contracts\SearchableInterface;
use App\Models\User;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Ecommerce\Support\HasSlugHistory;

class BlogPost extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch, HasSlugHistory;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image',
        'author_id', 'blog_category_id', 'tags', 'is_published', 'show_on_homepage', 'published_at',
        'seo_title', 'seo_description', 'seo_image',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_on_homepage' => 'boolean',
        'published_at' => 'datetime',
        'tags' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function comments()
    {
        return $this->hasMany(BlogComment::class, 'blog_post_id');
    }

    public function approvedComments()
    {
        return $this->hasMany(BlogComment::class, 'blog_post_id')
            ->where('is_approved', true)
            ->latest();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('published_at', '<=', now());
    }

    public function scopeByCategory($query, string $categorySlug)
    {
        return $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
    }

    /**
     * Publicly visible posts: published AND either uncategorised or in an
     * active category. Posts whose category is inactive are fully hidden.
     */
    public function scopeVisible($query)
    {
        return $query->published()
            ->where(function ($q) {
                $q->whereNull('blog_category_id')
                  ->orWhereHas('category', fn ($c) => $c->where('is_active', true));
            });
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Blog Post'; }
    public static function getSearchIcon(): string { return 'fa-pen-nib'; }
    public static function getSearchRoute(): string { return 'ecommerce.blog.edit'; }
    public static function getSearchPermission(): ?string { return 'ecommerce.view'; }
    public static function getSearchableColumns(): array { return ['title']; }
    public static function getSearchOrder(): int { return 61; }
    public function getSearchTitle(): string { return $this->title ?? ''; }
    public function getSearchSubtitle(): string { return $this->category?->name ?? ''; }
}
