<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\CampaignController;
use Modules\Ecommerce\Http\Controllers\ContentController;
use Modules\Ecommerce\Http\Controllers\EcommerceController;
use Modules\Ecommerce\Http\Controllers\FaqController;
use Modules\Ecommerce\Http\Controllers\MenuController;
use Modules\Ecommerce\Http\Controllers\PageController;

Route::middleware('auth')->prefix('ecommerce')->name('ecommerce.')->group(function () {
    // Dashboard
    Route::get('/', [EcommerceController::class, 'index'])->name('index');

    // Menu Builder
    Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
    Route::patch('/menus/{menu}/toggle-status', [MenuController::class, 'toggleStatus'])->name('menus.toggle-status');
    Route::get('/menus/{menu}/edit', [MenuController::class, 'edit'])->name('menus.edit');
    Route::post('/menus/{menu}/items', [MenuController::class, 'storeItem'])->name('menus.items.store');
    Route::put('/menus/items/{item}', [MenuController::class, 'updateItem'])->name('menus.items.update');
    Route::delete('/menus/items/{item}', [MenuController::class, 'destroyItem'])->name('menus.items.destroy');
    Route::post('/menus/items/{item}/toggle', [MenuController::class, 'toggleItem'])->name('menus.items.toggle');
    Route::post('/menus/{menu}/reorder', [MenuController::class, 'reorder'])->name('menus.reorder');
    Route::post('/menus/{menu}/clear', [MenuController::class, 'clear'])->name('menus.clear');
    Route::post('/menus/{menu}/reset', [MenuController::class, 'reset'])->name('menus.reset');

    // Orders

    // Products
    Route::get('/products', [EcommerceController::class, 'products'])->name('products');

    // Campaigns (seasonal site-wide / category / product discounts)
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

    // Coupons
    Route::get('/coupons', [EcommerceController::class, 'coupons'])->name('coupons');
    Route::get('/coupons/create', [EcommerceController::class, 'couponCreate'])->name('coupons.create');
    Route::post('/coupons', [EcommerceController::class, 'couponStore'])->name('coupons.store');
    Route::get('/coupons/{coupon}/edit', [EcommerceController::class, 'couponEdit'])->name('coupons.edit');
    Route::put('/coupons/{coupon}', [EcommerceController::class, 'couponUpdate'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [EcommerceController::class, 'couponDestroy'])->name('coupons.destroy');

    // Shipping
    Route::get('/shipping', [EcommerceController::class, 'shipping'])->name('shipping');
    Route::get('/shipping/create', [EcommerceController::class, 'shippingCreate'])->name('shipping.create');
    Route::post('/shipping', [EcommerceController::class, 'shippingStore'])->name('shipping.store');
    Route::get('/shipping/{zone}/edit', [EcommerceController::class, 'shippingEdit'])->name('shipping.edit');
    Route::put('/shipping/{zone}', [EcommerceController::class, 'shippingUpdate'])->name('shipping.update');
    Route::delete('/shipping/{zone}', [EcommerceController::class, 'shippingDestroy'])->name('shipping.destroy');
    Route::patch('/shipping/{zone}/toggle-status', [EcommerceController::class, 'toggleShippingStatus'])->name('shipping.toggle-status');

    // Courier Providers
    Route::get('/courier-providers', [EcommerceController::class, 'courierProviders'])->name('courier-providers');
    Route::post('/courier-providers/{provider}/toggle', [EcommerceController::class, 'toggleCourierProvider'])->name('courier-providers.toggle');
    Route::put('/courier-providers/{provider}', [EcommerceController::class, 'updateCourierProvider'])->name('courier-providers.update');

    // Steadfast live operations
    Route::get('/steadfast', [\Modules\Ecommerce\Http\Controllers\SteadfastOperationsController::class, 'index'])->name('steadfast.index');
    Route::get('/steadfast/payments/{paymentId}', [\Modules\Ecommerce\Http\Controllers\SteadfastOperationsController::class, 'paymentDetail'])->name('steadfast.payment-detail');

    // Settings
    Route::get('/settings', [EcommerceController::class, 'settings'])->name('settings');
    Route::post('/settings', [EcommerceController::class, 'settingsUpdate'])->name('settings.update');
    Route::post('/settings/appearance', [EcommerceController::class, 'appearanceUpdate'])->name('settings.appearance.update');
    Route::post('/settings/sitemap/generate', [EcommerceController::class, 'generateSitemap'])->name('settings.sitemap.generate');

    // Banners
    Route::get('/banners', [ContentController::class, 'banners'])->name('banners');
    Route::get('/banners/create', [ContentController::class, 'bannerCreate'])->name('banners.create');
    Route::post('/banners', [ContentController::class, 'bannerStore'])->name('banners.store');
    Route::get('/banners/{banner}/edit', [ContentController::class, 'bannerEdit'])->name('banners.edit');
    Route::put('/banners/{banner}', [ContentController::class, 'bannerUpdate'])->name('banners.update');
    Route::delete('/banners/{banner}', [ContentController::class, 'bannerDestroy'])->name('banners.destroy');
    Route::patch('/banners/{banner}/toggle-status', [ContentController::class, 'toggleBannerStatus'])->name('banners.toggle-status');

    // Homepage Sections
    Route::get('/homepage-sections', [ContentController::class, 'homepageSections'])->name('homepage-sections');
    Route::post('/homepage-sections/reorder', [ContentController::class, 'homepageSectionsReorder'])->name('homepage-sections.reorder');
    Route::post('/homepage-sections/{section}/toggle', [ContentController::class, 'homepageSectionToggle'])->name('homepage-sections.toggle');
    Route::get('/homepage-sections/{section}/settings', [ContentController::class, 'homepageSectionSettings'])->name('homepage-sections.settings');
    Route::put('/homepage-sections/{section}/settings', [ContentController::class, 'homepageSectionSettingsUpdate'])->name('homepage-sections.settings.update');
    Route::get('/homepage-sections/{section}/products', [ContentController::class, 'homepageSectionProducts'])->name('homepage-sections.products');
    Route::post('/homepage-sections/{section}/products', [ContentController::class, 'homepageSectionProductsUpdate'])->name('homepage-sections.products.update');
    Route::get('/homepage-sections/{section}/combos', [ContentController::class, 'homepageSectionCombos'])->name('homepage-sections.combos');
    Route::post('/homepage-sections/{section}/combos', [ContentController::class, 'homepageSectionCombosUpdate'])->name('homepage-sections.combos.update');

    // Product Collections
    Route::get('/collections', [ContentController::class, 'collections'])->name('collections');
    Route::get('/collections/create', [ContentController::class, 'collectionCreate'])->name('collections.create');
    Route::post('/collections', [ContentController::class, 'collectionStore'])->name('collections.store');
    Route::get('/collections/{collection}/edit', [ContentController::class, 'collectionEdit'])->name('collections.edit');
    Route::put('/collections/{collection}', [ContentController::class, 'collectionUpdate'])->name('collections.update');
    Route::delete('/collections/{collection}', [ContentController::class, 'collectionDestroy'])->name('collections.destroy');
    Route::patch('/collections/{collection}/toggle-status', [ContentController::class, 'toggleCollectionStatus'])->name('collections.toggle-status');

    // Flash Deals
    Route::get('/flash-deals', [ContentController::class, 'flashDeals'])->name('flash-deals');
    Route::get('/flash-deals/create', [ContentController::class, 'flashDealCreate'])->name('flash-deals.create');
    Route::post('/flash-deals', [ContentController::class, 'flashDealStore'])->name('flash-deals.store');
    Route::get('/flash-deals/{flashDeal}/edit', [ContentController::class, 'flashDealEdit'])->name('flash-deals.edit');
    Route::put('/flash-deals/{flashDeal}', [ContentController::class, 'flashDealUpdate'])->name('flash-deals.update');
    Route::delete('/flash-deals/{flashDeal}', [ContentController::class, 'flashDealDestroy'])->name('flash-deals.destroy');

    // Blog Categories
    Route::get('/blog-categories', [ContentController::class, 'blogCategories'])->name('blog-categories');
    Route::get('/blog-categories/create', [ContentController::class, 'blogCategoryCreate'])->name('blog-categories.create');
    Route::post('/blog-categories', [ContentController::class, 'blogCategoryStore'])->name('blog-categories.store');
    Route::get('/blog-categories/{blogCategory}/edit', [ContentController::class, 'blogCategoryEdit'])->name('blog-categories.edit');
    Route::put('/blog-categories/{blogCategory}', [ContentController::class, 'blogCategoryUpdate'])->name('blog-categories.update');
    Route::delete('/blog-categories/{blogCategory}', [ContentController::class, 'blogCategoryDestroy'])->name('blog-categories.destroy');
    Route::patch('/blog-categories/{blogCategory}/toggle-status', [ContentController::class, 'toggleBlogCategoryStatus'])->name('blog-categories.toggle-status');

    // Blog
    Route::get('/blog', [ContentController::class, 'blogPosts'])->name('blog');
    Route::get('/blog/create', [ContentController::class, 'blogPostCreate'])->name('blog.create');
    Route::post('/blog', [ContentController::class, 'blogPostStore'])->name('blog.store');
    Route::get('/blog/{post}/edit', [ContentController::class, 'blogPostEdit'])->name('blog.edit');
    Route::put('/blog/{post}', [ContentController::class, 'blogPostUpdate'])->name('blog.update');
    Route::delete('/blog/{post}', [ContentController::class, 'blogPostDestroy'])->name('blog.destroy');

    // Blog Comments (moderation)
    Route::get('/blog-comments', [ContentController::class, 'blogComments'])->name('blog-comments');
    Route::patch('/blog-comments/{comment}/approve', [ContentController::class, 'blogCommentApprove'])->name('blog-comments.approve');
    Route::patch('/blog-comments/{comment}/unapprove', [ContentController::class, 'blogCommentUnapprove'])->name('blog-comments.unapprove');
    Route::delete('/blog-comments/{comment}', [ContentController::class, 'blogCommentDestroy'])->name('blog-comments.destroy');

    // FAQs
    Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
    Route::get('/faqs/create', [FaqController::class, 'create'])->name('faqs.create');
    Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
    Route::post('/faqs/reorder', [FaqController::class, 'reorder'])->name('faqs.reorder');
    Route::get('/faqs/{faq}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
    Route::put('/faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
    Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
    Route::patch('/faqs/{faq}/toggle-status', [FaqController::class, 'toggleStatus'])->name('faqs.toggle-status');

    // Testimonials
    Route::get('/testimonials', [\Modules\Ecommerce\Http\Controllers\TestimonialController::class, 'index'])->name('testimonials.index');
    Route::get('/testimonials/create', [\Modules\Ecommerce\Http\Controllers\TestimonialController::class, 'create'])->name('testimonials.create');
    Route::post('/testimonials', [\Modules\Ecommerce\Http\Controllers\TestimonialController::class, 'store'])->name('testimonials.store');
    Route::get('/testimonials/{testimonial}/edit', [\Modules\Ecommerce\Http\Controllers\TestimonialController::class, 'edit'])->name('testimonials.edit');
    Route::put('/testimonials/{testimonial}', [\Modules\Ecommerce\Http\Controllers\TestimonialController::class, 'update'])->name('testimonials.update');
    Route::delete('/testimonials/{testimonial}', [\Modules\Ecommerce\Http\Controllers\TestimonialController::class, 'destroy'])->name('testimonials.destroy');
    Route::patch('/testimonials/{testimonial}/toggle-status', [\Modules\Ecommerce\Http\Controllers\TestimonialController::class, 'toggleStatus'])->name('testimonials.toggle-status');

    // Custom Pages
    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
    Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
    Route::patch('/pages/{page}/toggle-status', [PageController::class, 'toggleStatus'])->name('pages.toggle-status');

    // AJAX Product Search
    Route::get('/api/products/search', [ContentController::class, 'searchProducts'])->name('api.products.search');

    // Combo Packages — admin management has moved under /admin/products.
    // These GET URLs 301-redirect so old bookmarks/links keep working; all
    // create/edit/delete now live under the products.combos.* routes.
    Route::get('/combos', fn () => redirect()->route('products.index', [], 301))->name('combos.index');
    Route::get('/combos/create', fn () => redirect()->route('products.combos.create', [], 301))->name('combos.create');
    Route::get('/combos/{combo}/edit', fn ($combo) => redirect()->route('products.combos.edit', ['combo' => $combo], 301))->name('combos.edit');
});
