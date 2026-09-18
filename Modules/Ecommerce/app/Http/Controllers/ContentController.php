<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Http\Requests\StoreBannerRequest;
use Modules\Ecommerce\Http\Requests\StoreBlogCategoryRequest;
use Modules\Ecommerce\Http\Requests\StoreBlogPostRequest;
use Modules\Ecommerce\Http\Requests\StoreFlashDealRequest;
use Modules\Ecommerce\Http\Requests\StoreProductCollectionRequest;
use Modules\Ecommerce\Models\Banner;
use Modules\Ecommerce\Models\BlogCategory;
use Modules\Ecommerce\Models\Combo;
use Modules\Ecommerce\Models\BlogComment;
use Modules\Ecommerce\Models\BlogPost;
use Modules\Ecommerce\Models\FlashDeal;
use Modules\Ecommerce\Models\HomepageSection;
use Modules\Ecommerce\Models\ProductCollection;
use Modules\Ecommerce\Services\ContentManagementService;
use Modules\Ecommerce\Support\HomepageSectionSchema;
use Modules\Product\Models\Product;

class ContentController extends Controller
{
    public function __construct(
        protected ContentManagementService $service
    ) {}

    // ── Banners ──

    public function banners(Request $request): View
    {
        bpAuthorize('ecommerce.view');
        $banners = $this->service->listBanners($request->only(['position', 'is_active']));
        $stats = $this->service->getBannerStats();

        return view('ecommerce::banners', compact('banners', 'stats'));
    }

    public function bannerCreate(): View
    {
        bpAuthorize('ecommerce.create');
        return view('ecommerce::banner-create');
    }

    public function bannerStore(StoreBannerRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = Upload::store($request->file('image'), 'banners');
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $this->service->createBanner($data);

        return redirect()->route('ecommerce.banners')->with('success', __('Banner created successfully.'));
    }

    public function bannerEdit(Banner $banner): View
    {
        bpAuthorize('ecommerce.edit');
        return view('ecommerce::banner-edit', compact('banner'));
    }

    public function bannerUpdate(StoreBannerRequest $request, Banner $banner): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $data = $request->validated();

        if ($request->hasFile('image')) {
            Upload::delete($banner->image);
            $data['image'] = Upload::store($request->file('image'), 'banners');
        } else {
            unset($data['image']);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $this->service->updateBanner($banner, $data);

        return redirect()->route('ecommerce.banners')->with('success', __('Banner updated successfully.'));
    }

    public function bannerDestroy(Banner $banner): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $this->service->deleteBanner($banner);

        return redirect()->route('ecommerce.banners')->with('success', __('Banner deleted successfully.'));
    }

    public function toggleBannerStatus(Banner $banner): JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->toggleStatus($banner);

        return response()->json(['success' => true, 'is_active' => $banner->is_active, 'message' => __('Status updated.')]);
    }

    // ── Homepage Sections ──

    public function homepageSections(): View
    {
        bpAuthorize('ecommerce.view');
        $sections = $this->service->getHomepageSections();

        return view('ecommerce::homepage-sections', compact('sections'));
    }

    public function homepageSectionsReorder(Request $request): JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $validated = $request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'integer|exists:homepage_sections,id',
        ]);

        $this->service->reorderSections($validated['ordered_ids']);

        return response()->json(['success' => true]);
    }

    public function homepageSectionToggle(HomepageSection $section): JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $active = $this->service->toggleSection($section);

        return response()->json(['success' => true, 'is_active' => $active]);
    }

    public function homepageSectionSettings(HomepageSection $section): View
    {
        bpAuthorize('ecommerce.edit');
        return view('ecommerce::homepage-section-settings', compact('section'));
    }

    public function homepageSectionSettingsUpdate(Request $request, HomepageSection $section): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $schema = HomepageSectionSchema::for($section->section_type);

        // Validate every schema field by its type.
        $rules = [];
        foreach ($schema as $f) {
            $rules['fields.' . $f['key']] = match ($f['type']) {
                'image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'number'   => 'nullable|integer|min:0|max:100000',
                'switch'   => 'nullable|boolean',
                'textarea' => 'nullable|string|max:2000',
                default    => 'nullable|string|max:1000', // text, link, highlight
            };
        }
        $request->validate($rules);

        // Collect schema content into settings (resolving image uploads to paths).
        $settings = $section->settings ?? [];
        foreach ($schema as $f) {
            $key = $f['key'];

            if ($f['type'] === 'image') {
                $current = $settings[$key] ?? null;
                if ($request->boolean("remove.$key")) {
                    Upload::delete($current);
                    $current = null;
                }
                if ($request->hasFile("fields.$key")) {
                    Upload::delete($current);
                    $current = Upload::store($request->file("fields.$key"), 'homepage-sections');
                }
                $settings[$key] = $current;
            } elseif ($f['type'] === 'switch') {
                $settings[$key] = $request->boolean("fields.$key");
            } else {
                $settings[$key] = $request->input("fields.$key");
            }
        }

        $this->service->saveSectionContent($section, $settings);

        return redirect()->route('ecommerce.homepage-sections')->with('success', __('Section content updated.'));
    }

    public function homepageSectionProducts(HomepageSection $section): View
    {
        bpAuthorize('ecommerce.edit');
        abort_unless($section->isProductSection(), 404);

        // Eager-load images so the Product `image` accessor (primary image)
        // resolves without N+1 in the curated-products preview.
        $section->load('products.images', 'combos');

        // Full active-product catalog for the focus/click search widget
        // (client-side, same UX as Create Purchase / Create Quotation).
        $products = Product::active()
            ->with('images')
            ->orderBy('name')
            ->get();

        // Full active-combo catalog for the combo picker dropdown.
        $combos = Combo::active()->orderBy('name')->get();

        return view('ecommerce::homepage-section-products', compact('section', 'products', 'combos'));
    }

    /**
     * Save a section's curated row — products and combos picked from one
     * ordered list (`items`, each carrying `type`: 'product'|'combo'). Split
     * here and synced onto their own pivots; curatedSectionItems() merges
     * them back into the admin's chosen order using each item's sort_order.
     */
    public function homepageSectionProductsUpdate(Request $request, HomepageSection $section): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        abort_unless($section->isProductSection(), 404);

        $request->validate([
            'items'              => 'nullable|array',
            'items.*.type'       => 'required|in:product,combo',
            'items.*.id'         => [
                'required', 'integer', 'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    preg_match('/^items\.(\d+)\.id$/', $attribute, $m);
                    $type = isset($m[1]) ? $request->input("items.{$m[1]}.type") : null;
                    $exists = $type === 'combo'
                        ? Combo::whereKey($value)->exists()
                        : Product::whereKey($value)->exists();
                    if (! $exists) {
                        $fail(__('The selected :type does not exist.', ['type' => $type ?? 'item']));
                    }
                },
            ],
            'items.*.sort_order' => 'nullable|integer|min:0',
            'items.*.thumbnail'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $items = $request->input('items', []);
        $files = $request->file('items', []);

        // Delete override thumbnails for products being removed from the list,
        // so disk files don't orphan after the sync detaches them.
        $keepProductIds = collect($items)
            ->where('type', 'product')
            ->pluck('id')->filter()->map(fn ($v) => (int) $v)->all();
        foreach ($section->products()->get() as $existing) {
            if (! in_array($existing->id, $keepProductIds, true)) {
                Upload::delete($existing->pivot->thumbnail);
            }
        }

        $productPayload = [];
        $comboPayload = [];
        foreach ($items as $i => $item) {
            if (empty($item['id']) || empty($item['type'])) {
                continue;
            }

            if ($item['type'] === 'combo') {
                $comboPayload[] = [
                    'combo_id'   => (int) $item['id'],
                    'sort_order' => (int) ($item['sort_order'] ?? $i),
                ];
                continue;
            }

            $thumb = $item['existing_thumbnail'] ?? null;

            if (! empty($item['remove_thumbnail'])) {
                Upload::delete($thumb);
                $thumb = null;
            }

            $file = $files[$i]['thumbnail'] ?? null;
            if ($file) {
                Upload::delete($thumb);
                $thumb = Upload::store($file, 'homepage-sections');
            }

            $productPayload[] = [
                'product_id' => (int) $item['id'],
                'thumbnail'  => $thumb,
                'sort_order' => (int) ($item['sort_order'] ?? $i),
            ];
        }

        $this->service->syncSectionProducts($section, $productPayload);
        $this->service->syncSectionCombos($section, $comboPayload);

        return redirect()->route('ecommerce.homepage-sections')->with('success', __('Section content updated.'));
    }

    // ── Homepage Section Combos ──

    public function homepageSectionCombos(HomepageSection $section): View
    {
        bpAuthorize('ecommerce.edit');
        abort_unless($section->isComboSection(), 404);

        $section->load('combos');

        // Full active-combo catalog for the search-and-add widget.
        $combos = Combo::active()->orderBy('name')->get();

        return view('ecommerce::homepage-section-combos', compact('section', 'combos'));
    }

    public function homepageSectionCombosUpdate(Request $request, HomepageSection $section): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        abort_unless($section->isComboSection(), 404);

        $request->validate([
            'combos'             => 'nullable|array',
            'combos.*.combo_id'  => 'required|integer|exists:combos,id',
            'combos.*.sort_order' => 'nullable|integer|min:0',
        ]);

        $this->service->syncSectionCombos($section, $request->input('combos', []));

        return redirect()->route('ecommerce.homepage-sections')->with('success', __('Section combos updated.'));
    }

    // ── Product Collections ──

    public function collections(): View
    {
        bpAuthorize('ecommerce.view');
        $collections = $this->service->listCollections();

        return view('ecommerce::collections', compact('collections'));
    }

    public function collectionCreate(): View
    {
        bpAuthorize('ecommerce.create');
        $categories = Category::active()->root()->ordered()->get();
        $brands = Brand::active()->ordered()->get();

        return view('ecommerce::collection-create', compact('categories', 'brands'));
    }

    public function collectionStore(StoreProductCollectionRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $productIds = $data['product_ids'] ?? [];
        unset($data['product_ids']);

        $collection = $this->service->createCollection($data);

        if ($collection->type === 'manual' && !empty($productIds)) {
            $this->service->syncCollectionProducts($collection, $productIds);
        }

        return redirect()->route('ecommerce.collections')->with('success', __('Collection created successfully.'));
    }

    public function collectionEdit(ProductCollection $collection): View
    {
        bpAuthorize('ecommerce.edit');
        $collection->load('products');
        $categories = Category::active()->root()->ordered()->get();
        $brands = Brand::active()->ordered()->get();

        return view('ecommerce::collection-edit', compact('collection', 'categories', 'brands'));
    }

    public function collectionUpdate(StoreProductCollectionRequest $request, ProductCollection $collection): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $productIds = $data['product_ids'] ?? [];
        unset($data['product_ids']);

        $this->service->updateCollection($collection, $data);

        if ($collection->type === 'manual') {
            $this->service->syncCollectionProducts($collection, $productIds);
        }

        return redirect()->route('ecommerce.collections')->with('success', __('Collection updated successfully.'));
    }

    public function collectionDestroy(ProductCollection $collection): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $this->service->deleteCollection($collection);

        return redirect()->route('ecommerce.collections')->with('success', __('Collection deleted successfully.'));
    }

    public function toggleCollectionStatus(ProductCollection $collection): JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->toggleCollectionStatus($collection);

        return response()->json(['success' => true, 'is_active' => $collection->is_active, 'message' => __('Status updated.')]);
    }

    // ── Flash Deals ──

    public function flashDeals(): View
    {
        bpAuthorize('ecommerce.view');
        $flashDeals = $this->service->listFlashDeals();
        $stats = $this->service->getFlashDealStats();

        return view('ecommerce::flash-deals', compact('flashDeals', 'stats'));
    }

    public function flashDealCreate(): View
    {
        bpAuthorize('ecommerce.create');
        return view('ecommerce::flash-deal-create', [
            'products' => $this->flashDealProductCatalog(),
        ]);
    }

    /**
     * Lightweight product catalog for the shared product-search widget on the
     * flash-deal forms — same UX as Purchase/Quotation create (Bug_89).
     *
     * @return array<int, array<string, mixed>>
     */
    private function flashDealProductCatalog(): array
    {
        return Product::active()
            ->select('id', 'name', 'sku', 'model', 'barcode', 'sell_price')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'sku'   => $p->sku,
                'model' => $p->model,
                'barcode' => $p->barcode,
                'price' => (float) $p->sell_price,
            ])
            ->all();
    }

    public function flashDealStore(StoreFlashDealRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $data = $request->validated();
        $products = $data['products'] ?? [];
        unset($data['products']);

        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = Upload::store($request->file('banner_image'), 'flash-deals');
        }

        $flashDeal = $this->service->createFlashDeal($data);
        $this->service->syncFlashDealProducts($flashDeal, $products);

        return redirect()->route('ecommerce.flash-deals')->with('success', __('Flash deal created successfully.'));
    }

    public function flashDealEdit(FlashDeal $flashDeal): View
    {
        bpAuthorize('ecommerce.edit');
        $flashDeal->load('products');

        return view('ecommerce::flash-deal-edit', [
            'flashDeal' => $flashDeal,
            'products'  => $this->flashDealProductCatalog(),
        ]);
    }

    public function flashDealUpdate(StoreFlashDealRequest $request, FlashDeal $flashDeal): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $data = $request->validated();
        $products = $data['products'] ?? [];
        unset($data['products']);

        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('banner_image')) {
            Upload::delete($flashDeal->banner_image);
            $data['banner_image'] = Upload::store($request->file('banner_image'), 'flash-deals');
        } elseif ($request->boolean('remove_banner_image')) {
            // User cleared the banner via the image-upload "x" — delete the file
            // and null the column so the deal shows no banner.
            Upload::delete($flashDeal->banner_image);
            $data['banner_image'] = null;
        } else {
            unset($data['banner_image']);
        }

        $this->service->updateFlashDeal($flashDeal, $data);
        $this->service->syncFlashDealProducts($flashDeal, $products);

        return redirect()->route('ecommerce.flash-deals')->with('success', __('Flash deal updated successfully.'));
    }

    public function flashDealDestroy(FlashDeal $flashDeal): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $this->service->deleteFlashDeal($flashDeal);

        return redirect()->route('ecommerce.flash-deals')->with('success', __('Flash deal deleted successfully.'));
    }

    // ── Blog Posts ──

    public function blogPosts(Request $request): View
    {
        bpAuthorize('ecommerce.view');
        $posts = $this->service->listBlogPosts($request->only(['search', 'category', 'is_published']));

        return view('ecommerce::blog-posts', compact('posts'));
    }

    public function blogPostCreate(): View
    {
        bpAuthorize('ecommerce.create');
        $categories = $this->service->activeBlogCategories();

        return view('ecommerce::blog-post-create', compact('categories'));
    }

    public function blogPostStore(StoreBlogPostRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $data = $request->validated();

        $data['author_id'] = auth()->id();
        $data['is_published'] = $request->boolean('is_published', false);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = Upload::store($request->file('featured_image'), 'blog');
        }

        if ($request->hasFile('seo_image')) {
            $data['seo_image'] = Upload::store($request->file('seo_image'), 'blog/seo');
        }

        if (!empty($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_values(array_filter(array_map('trim', explode(',', $data['tags']))));
        }

        $this->service->createBlogPost($data);

        return redirect()->route('ecommerce.blog')->with('success', __('Blog post created successfully.'));
    }

    public function blogPostEdit(BlogPost $post): View
    {
        bpAuthorize('ecommerce.edit');
        $categories = $this->service->activeBlogCategories();

        return view('ecommerce::blog-post-edit', compact('post', 'categories'));
    }

    public function blogPostUpdate(StoreBlogPostRequest $request, BlogPost $post): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $data = $request->validated();

        $data['is_published'] = $request->boolean('is_published', false);

        if ($request->hasFile('featured_image')) {
            Upload::delete($post->featured_image);
            $data['featured_image'] = Upload::store($request->file('featured_image'), 'blog');
        } else {
            unset($data['featured_image']);
        }

        if ($request->hasFile('seo_image')) {
            Upload::delete($post->seo_image);
            $data['seo_image'] = Upload::store($request->file('seo_image'), 'blog/seo');
        } else {
            unset($data['seo_image']);
        }

        if (!empty($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_values(array_filter(array_map('trim', explode(',', $data['tags']))));
        }

        $this->service->updateBlogPost($post, $data);

        return redirect()->route('ecommerce.blog')->with('success', __('Blog post updated successfully.'));
    }

    public function blogPostDestroy(BlogPost $post): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $this->service->deleteBlogPost($post);

        return redirect()->route('ecommerce.blog')->with('success', __('Blog post deleted successfully.'));
    }

    // ── Blog Categories ──

    public function blogCategories(): View
    {
        bpAuthorize('ecommerce.view');
        $categories = $this->service->listBlogCategories();

        return view('ecommerce::blog-categories', compact('categories'));
    }

    public function blogCategoryCreate(): View
    {
        bpAuthorize('ecommerce.create');
        return view('ecommerce::blog-category-create');
    }

    public function blogCategoryStore(StoreBlogCategoryRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->service->createBlogCategory($data);

        return redirect()->route('ecommerce.blog-categories')->with('success', __('Blog category created successfully.'));
    }

    public function blogCategoryEdit(BlogCategory $blogCategory): View
    {
        bpAuthorize('ecommerce.edit');
        return view('ecommerce::blog-category-edit', ['category' => $blogCategory]);
    }

    public function blogCategoryUpdate(StoreBlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->service->updateBlogCategory($blogCategory, $data);

        return redirect()->route('ecommerce.blog-categories')->with('success', __('Blog category updated successfully.'));
    }

    public function blogCategoryDestroy(BlogCategory $blogCategory): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $this->service->deleteBlogCategory($blogCategory);

        return redirect()->route('ecommerce.blog-categories')->with('success', __('Blog category deleted successfully.'));
    }

    public function toggleBlogCategoryStatus(BlogCategory $blogCategory): JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->toggleBlogCategoryStatus($blogCategory);

        return response()->json(['success' => true, 'is_active' => $blogCategory->is_active, 'message' => __('Status updated.')]);
    }

    // ── Blog Comments (moderation) ──

    public function blogComments(Request $request): View
    {
        bpAuthorize('ecommerce.view');
        $comments = $this->service->listBlogComments($request->only(['status', 'search']));

        return view('ecommerce::blog-comments', compact('comments'));
    }

    public function blogCommentApprove(BlogComment $comment): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->approveBlogComment($comment);

        return back()->with('success', __('Comment approved.'));
    }

    public function blogCommentUnapprove(BlogComment $comment): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->unapproveBlogComment($comment);

        return back()->with('success', __('Comment moved back to pending.'));
    }

    public function blogCommentDestroy(BlogComment $comment): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $this->service->deleteBlogComment($comment);

        return back()->with('success', __('Comment deleted.'));
    }

    // ── AJAX Product Search ──

    public function searchProducts(Request $request): JsonResponse
    {
        bpAuthorize('ecommerce.view');
        $search = $request->input('q', '');

        $placeholder = asset('website/assets/images/product_placeholder.png');

        $products = Product::active()
            ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
            ->select('id', 'name', 'sku', 'sell_price', 'thumbnail')
            ->with('images')
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'text' => $p->name . ' (' . $p->sku . ') — ' . currency_symbol() . ' ' . number_format($p->sell_price, 0),
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $p->sell_price,
                'image' => upload_url($p->image ?: $p->thumbnail, $placeholder),
            ]);

        return response()->json(['results' => $products]);
    }
}
