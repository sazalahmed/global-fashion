<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Models\BlogCategory;
use Modules\Ecommerce\Models\BlogPost;

class BlogService
{
    /**
     * Active categories that have at least one published post, with counts
     * (for the storefront sidebar widget).
     *
     * @return Collection<int, BlogCategory>
     */
    public function categoriesWithCounts(): Collection
    {
        return BlogCategory::active()
            ->withCount(['posts as total' => fn ($q) => $q->published()])
            ->having('total', '>', 0)
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Distinct tags aggregated across published posts (for the sidebar widget).
     *
     * @return Collection<int, string>
     */
    public function popularTags(int $limit = 12): Collection
    {
        return BlogPost::published()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take($limit)
            ->values();
    }

    /**
     * Recent published posts for the "Popular Blog" sidebar widget.
     */
    public function popularPosts(int $limit = 3, ?int $excludeId = null): Collection
    {
        return BlogPost::visible()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}
