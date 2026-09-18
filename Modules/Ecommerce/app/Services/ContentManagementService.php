<?php

namespace Modules\Ecommerce\Services;

use App\Helpers\Upload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Ecommerce\Models\Banner;
use Modules\Ecommerce\Models\BlogCategory;
use Modules\Ecommerce\Models\BlogComment;
use Modules\Ecommerce\Models\BlogPost;
use Modules\Ecommerce\Models\FlashDeal;
use Modules\Ecommerce\Models\HomepageSection;
use Modules\Ecommerce\Models\ProductCollection;
use Modules\Product\Models\Product;

class ContentManagementService
{
    // ── Banners ──

    public function listBanners(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Banner::query()
            ->when($filters['position'] ?? null, fn ($q, $p) => $q->where('position', $p))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createBanner(array $data): Banner
    {
        return Banner::create($data);
    }

    public function updateBanner(Banner $banner, array $data): Banner
    {
        $banner->update($data);

        return $banner->fresh();
    }

    public function deleteBanner(Banner $banner): void
    {
        Upload::delete($banner->image);

        $banner->delete();
    }

    public function getActiveBannersByPosition(string $position): Collection
    {
        return Banner::active()->byPosition($position)->ordered()->get();
    }

    public function toggleStatus(Banner $banner): Banner
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return $banner;
    }

    public function getBannerStats(): array
    {
        return [
            'total' => Banner::count(),
            'active_hero' => Banner::active()->byPosition('hero')->count(),
            'active_promo' => Banner::active()->byPosition('promo_large')->count(),
            'scheduled' => Banner::where('is_active', true)->where('starts_at', '>', now())->count(),
        ];
    }

    // ── Homepage Sections ──

    public function getHomepageSections(): Collection
    {
        return HomepageSection::ordered()->get();
    }

    public function getActiveHomepageSections(): Collection
    {
        return HomepageSection::active()->ordered()->get();
    }

    /**
     * Persist a drag-and-drop reorder: the given product/section IDs in their
     * new visual order become sort_order 1..N.
     */
    public function reorderSections(array $ids): void
    {
        foreach (array_values($ids) as $i => $id) {
            HomepageSection::where('id', (int) $id)->update(['sort_order' => $i + 1]);
        }
    }

    /**
     * Flip a section's active flag; returns the new state.
     */
    public function toggleSection(HomepageSection $section): bool
    {
        $section->update(['is_active' => ! $section->is_active]);

        return $section->is_active;
    }

    /**
     * Merge schema-driven content values into a section's settings JSON.
     * Callers resolve image uploads to paths before passing them in.
     */
    public function saveSectionContent(HomepageSection $section, array $settings): HomepageSection
    {
        $section->update([
            'settings' => array_merge($section->settings ?? [], $settings),
        ]);

        return $section->fresh();
    }

    public function updateSectionSettings(HomepageSection $section, array $data): HomepageSection
    {
        $section->update([
            'title' => $data['title'] ?? $section->title,
            'subtitle' => $data['subtitle'] ?? $section->subtitle,
            'settings' => array_merge($section->settings ?? [], $data['settings'] ?? []),
        ]);

        return $section->fresh();
    }

    /**
     * Replace a section's curated product list. Each item: product_id,
     * optional thumbnail (override path) and sort_order. Thumbnail files
     * are stored by the caller; this only persists the resolved paths.
     */
    public function syncSectionProducts(HomepageSection $section, array $products): void
    {
        $sync = [];
        foreach (array_values($products) as $idx => $row) {
            if (empty($row['product_id'])) {
                continue;
            }
            $sync[(int) $row['product_id']] = [
                'thumbnail' => $row['thumbnail'] ?? null,
                'sort_order' => $row['sort_order'] ?? $idx,
            ];
        }

        $section->products()->sync($sync);
    }

    /**
     * Replace a section's curated combo list. Each item: combo_id and
     * sort_order.
     */
    public function syncSectionCombos(HomepageSection $section, array $combos): void
    {
        $sync = [];
        foreach (array_values($combos) as $idx => $row) {
            if (empty($row['combo_id'])) {
                continue;
            }
            $sync[(int) $row['combo_id']] = [
                'sort_order' => $row['sort_order'] ?? $idx,
            ];
        }

        $section->combos()->sync($sync);
    }

    // ── Product Collections ──

    public function listCollections(int $perPage = 15): LengthAwarePaginator
    {
        return ProductCollection::withCount('products')
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createCollection(array $data): ProductCollection
    {
        return ProductCollection::create($data);
    }

    public function updateCollection(ProductCollection $collection, array $data): ProductCollection
    {
        $collection->update($data);

        return $collection->fresh();
    }

    public function deleteCollection(ProductCollection $collection): void
    {
        $collection->delete();
    }

    public function toggleCollectionStatus(ProductCollection $collection): ProductCollection
    {
        $collection->update(['is_active' => ! $collection->is_active]);

        return $collection;
    }

    public function syncCollectionProducts(ProductCollection $collection, array $productIds): void
    {
        $syncData = [];
        foreach ($productIds as $index => $productId) {
            $syncData[$productId] = ['sort_order' => $index];
        }

        $collection->products()->sync($syncData);
    }

    public function getCollectionProducts(ProductCollection $collection, int $limit = 12): Collection
    {
        if ($collection->type === 'manual') {
            return $collection->products()
                ->with(['images', 'category'])
                ->limit($limit)
                ->get();
        }

        // Auto collection
        $rules = $collection->filter_rules ?? [];
        $query = Product::storefrontVisible()
            ->with(['images', 'category']);

        if (!empty($rules['category_ids'])) {
            $query->whereIn('category_id', $rules['category_ids']);
        }

        if (!empty($rules['brand_ids'])) {
            $query->whereIn('brand_id', $rules['brand_ids']);
        }

        if (!empty($rules['price_min'])) {
            $query->where('sell_price', '>=', $rules['price_min']);
        }

        if (!empty($rules['price_max'])) {
            $query->where('sell_price', '<=', $rules['price_max']);
        }

        $sortBy = $rules['sort_by'] ?? 'latest';
        $query = match ($sortBy) {
            'price_low' => $query->orderBy('sell_price'),
            'price_high' => $query->orderByDesc('sell_price'),
            'name_asc' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return $query->limit($rules['limit'] ?? $limit)->get();
    }

    // ── Flash Deals ──

    public function listFlashDeals(int $perPage = 15): LengthAwarePaginator
    {
        return FlashDeal::withCount('products')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createFlashDeal(array $data): FlashDeal
    {
        return FlashDeal::create($data);
    }

    public function updateFlashDeal(FlashDeal $flashDeal, array $data): FlashDeal
    {
        $flashDeal->update($data);

        return $flashDeal->fresh();
    }

    public function deleteFlashDeal(FlashDeal $flashDeal): void
    {
        Upload::delete($flashDeal->banner_image);

        $flashDeal->delete();
    }

    public function syncFlashDealProducts(FlashDeal $flashDeal, array $products): void
    {
        $syncData = [];
        foreach ($products as $index => $item) {
            $syncData[$item['product_id']] = [
                'discount_type' => $item['discount_type'],
                'discount_value' => $item['discount_value'],
                'sort_order' => $index,
            ];
        }

        $flashDeal->products()->sync($syncData);
    }

    public function getActiveFlashDeal(): ?FlashDeal
    {
        return FlashDeal::active()
            ->with(['products' => function ($q) {
                $q->with(['images', 'category', 'approvedReviews']);
            }])
            ->first();
    }

    public function getFlashDealStats(): array
    {
        return [
            'total' => FlashDeal::count(),
            'running' => FlashDeal::active()->count(),
            'upcoming' => FlashDeal::upcoming()->count(),
            'expired' => FlashDeal::expired()->count(),
        ];
    }

    // ── Blog Posts ──

    public function listBlogPosts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return BlogPost::with(['author', 'category'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('blog_category_id', $c))
            ->when(isset($filters['is_published']), fn ($q) => $q->where('is_published', $filters['is_published']))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    // ── Blog Categories ──

    public function listBlogCategories(int $perPage = 15): LengthAwarePaginator
    {
        return BlogCategory::withCount('posts')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Active categories for the post form dropdown. */
    public function activeBlogCategories(): Collection
    {
        return BlogCategory::active()->orderBy('name')->get();
    }

    public function createBlogCategory(array $data): BlogCategory
    {
        return BlogCategory::create($data);
    }

    public function updateBlogCategory(BlogCategory $category, array $data): BlogCategory
    {
        $category->update($data);

        return $category->fresh();
    }

    public function deleteBlogCategory(BlogCategory $category): void
    {
        $category->delete();
    }

    public function toggleBlogCategoryStatus(BlogCategory $category): BlogCategory
    {
        $category->update(['is_active' => ! $category->is_active]);

        return $category;
    }

    public function createBlogPost(array $data): BlogPost
    {
        return BlogPost::create($data);
    }

    public function updateBlogPost(BlogPost $blogPost, array $data): BlogPost
    {
        $blogPost->update($data);

        return $blogPost->fresh();
    }

    public function deleteBlogPost(BlogPost $blogPost): void
    {
        Upload::delete($blogPost->featured_image);

        $blogPost->delete();
    }

    // ── Blog Comments (moderation) ──

    public function listBlogComments(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return BlogComment::with('post')
            ->when(($filters['status'] ?? null) === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when(($filters['status'] ?? null) === 'approved', fn ($q) => $q->where('is_approved', true))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('comment', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function approveBlogComment(BlogComment $comment): void
    {
        $comment->update(['is_approved' => true]);
    }

    public function unapproveBlogComment(BlogComment $comment): void
    {
        $comment->update(['is_approved' => false]);
    }

    public function deleteBlogComment(BlogComment $comment): void
    {
        $comment->delete();
    }

    public function pendingCommentCount(): int
    {
        return BlogComment::where('is_approved', false)->count();
    }

    public function getPublishedPosts(int $limit = 6): Collection
    {
        return BlogPost::visible()
            ->with('author')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Posts to show on the homepage blog section. Admin-curated posts
     * (show_on_homepage) win; if none are flagged, fall back to the latest
     * published posts so the section is never empty.
     */
    public function getHomepagePosts(int $limit = 4): Collection
    {
        $curated = BlogPost::visible()
            ->where('show_on_homepage', true)
            ->with('author')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return $curated->isNotEmpty() ? $curated : $this->getPublishedPosts($limit);
    }

    public function findPostBySlug(string $slug): ?BlogPost
    {
        return BlogPost::visible()
            ->with('author')
            ->where('slug', $slug)
            ->first();
    }
}
