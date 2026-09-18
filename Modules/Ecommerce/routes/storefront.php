<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Storefront\HomeController;
use Modules\Ecommerce\Http\Controllers\Storefront\ShopController;
use Modules\Ecommerce\Http\Controllers\Storefront\StorefrontCategoryController;
use Modules\Ecommerce\Http\Controllers\Storefront\CartController;
use Modules\Ecommerce\Http\Controllers\Storefront\WishlistController;
use Modules\Ecommerce\Http\Controllers\Storefront\CompareController;
use Modules\Ecommerce\Http\Controllers\Storefront\BlogController;
use Modules\Ecommerce\Http\Controllers\Storefront\CheckoutController;
use Modules\Ecommerce\Http\Controllers\Storefront\CustomerAuthController;
use Modules\Ecommerce\Http\Controllers\Storefront\NewsletterController;
use Modules\Ecommerce\Http\Controllers\Storefront\ContactController;
use Modules\Ecommerce\Http\Controllers\Storefront\SearchController;

/*
|--------------------------------------------------------------------------
| Storefront Routes (Public — No Auth Required)
|--------------------------------------------------------------------------
*/

// Home
Route::get('/', [HomeController::class, 'index'])->name('storefront.home');

// Newsletter subscribe
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('storefront.newsletter.subscribe');

// Contact
Route::get('/contact', [ContactController::class, 'index'])->name('storefront.contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1')->name('storefront.contact.store');

// Live product search (header) — returns the lightweight catalog for Fuse.js
Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:120,1')->name('storefront.search.suggest');

// Shop
Route::get('/shop', [ShopController::class, 'index'])->name('storefront.shop.index');
Route::get('/shop/{slug}/variant-data', [ShopController::class, 'variantData'])->name('storefront.shop.variant-data');
Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('storefront.shop.show');

// Categories
Route::get('/categories', [StorefrontCategoryController::class, 'index'])->name('storefront.category.index');
Route::get('/categories/{slug}', [StorefrontCategoryController::class, 'show'])->name('storefront.category.show');

// Flash Deals
Route::get('/flash-deals', [ShopController::class, 'flashDeals'])->name('storefront.flash-deals');

// Combo Packages
Route::get('/combos', [\Modules\Ecommerce\Http\Controllers\Storefront\ComboController::class, 'index'])->name('storefront.combos.index');
Route::get('/combos/{slug}/size-data', [\Modules\Ecommerce\Http\Controllers\Storefront\ComboController::class, 'sizeData'])->name('storefront.combos.size-data');
Route::get('/combos/{slug}', [\Modules\Ecommerce\Http\Controllers\Storefront\ComboController::class, 'show'])->name('storefront.combos.show');

// Wishlist
Route::get('/wishlist', [WishlistController::class, 'index'])->name('storefront.wishlist.index');
Route::post('/wishlist/add', [WishlistController::class, 'add'])->name('storefront.wishlist.add');
Route::post('/wishlist/remove', [WishlistController::class, 'remove'])->name('storefront.wishlist.remove');

// Compare
Route::get('/compare', [CompareController::class, 'index'])->name('storefront.compare.index');
Route::post('/compare/add', [CompareController::class, 'add'])->name('storefront.compare.add');
Route::post('/compare/remove', [CompareController::class, 'remove'])->name('storefront.compare.remove');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('storefront.cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('storefront.cart.add');
Route::post('/cart/add-combo', [CartController::class, 'addCombo'])->name('storefront.cart.add-combo');
Route::post('/cart/update', [CartController::class, 'update'])->name('storefront.cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('storefront.cart.remove');
Route::post('/cart/remove-selected', [CartController::class, 'removeSelected'])->name('storefront.cart.remove.selected');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('storefront.cart.clear');
Route::post('/cart/coupon/apply', [CartController::class, 'applyCoupon'])->name('storefront.cart.coupon.apply');
Route::post('/cart/coupon/remove', [CartController::class, 'removeCoupon'])->name('storefront.cart.coupon.remove');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('storefront.blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('storefront.blog.show');

// FAQ
Route::get('/faq', [\Modules\Ecommerce\Http\Controllers\Storefront\FaqController::class, 'index'])->name('storefront.faq.index');
Route::post('/blog/{slug}/comments', [BlogController::class, 'storeComment'])
    ->middleware('throttle:5,1')
    ->name('storefront.blog.comment.store');

// Checkout
Route::get('/checkout', [CheckoutController::class, 'index'])->name('storefront.checkout.index');
Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('storefront.checkout.process');
Route::post('/checkout/capture', [CheckoutController::class, 'capture'])
    ->middleware('throttle:20,1')
    ->name('storefront.checkout.capture');
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('storefront.checkout.success');
Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('storefront.checkout.cancel');

// Customer Auth (Guest)
Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLoginForm'])->name('storefront.customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:login')->name('storefront.customer.login.post');
    Route::get('/register', [CustomerAuthController::class, 'showRegistrationForm'])->name('storefront.customer.register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:login')->name('storefront.customer.register.post');
    Route::post('/register/send-otp', [CustomerAuthController::class, 'sendRegisterOtp'])
        ->middleware('throttle:login')->name('storefront.customer.register.send-otp');
    Route::post('/register/verify-otp', [CustomerAuthController::class, 'verifyRegisterOtp'])
        ->middleware('throttle:login')->name('storefront.customer.register.verify-otp');

    // Google OAuth (social login)
    Route::get('/auth/google/redirect', [CustomerAuthController::class, 'redirectToGoogle'])->name('storefront.customer.google.redirect');
    Route::get('/auth/google/callback', [CustomerAuthController::class, 'handleGoogleCallback'])->name('storefront.customer.google.callback');

    // Forgot / reset password (customer) — phone OTP based. Namespaced under
    // /customer to avoid colliding with the admin Auth module's routes.
    Route::get('/customer/forgot-password', [CustomerAuthController::class, 'showForgotPasswordForm'])->name('storefront.customer.password.request');
    Route::post('/customer/forgot-password', [CustomerAuthController::class, 'sendResetOtp'])->middleware('throttle:login')->name('storefront.customer.password.email');
    Route::get('/customer/reset-password', [CustomerAuthController::class, 'showResetForm'])->name('storefront.customer.password.reset');
    Route::post('/customer/reset-password', [CustomerAuthController::class, 'resetPassword'])->middleware('throttle:login')->name('storefront.customer.password.store');
});

// Customer Auth (Authenticated)
Route::middleware('auth:customer')->group(function () {
    Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('storefront.customer.logout');
    Route::get('/customer/profile', [CustomerAuthController::class, 'showProfile'])->name('storefront.customer.profile');
    Route::get('/customer/profile/edit', [CustomerAuthController::class, 'editProfile'])->name('storefront.customer.profile.edit');
    Route::put('/customer/profile', [CustomerAuthController::class, 'updateProfile'])->name('storefront.customer.profile.update');
    Route::get('/customer/password', [CustomerAuthController::class, 'changePassword'])->name('storefront.customer.password');
    Route::put('/customer/password', [CustomerAuthController::class, 'updatePassword'])->name('storefront.customer.password.update');
    Route::get('/customer/wishlist', [CustomerAuthController::class, 'wishlist'])->name('storefront.customer.wishlist');
    Route::get('/customer/orders', [CustomerAuthController::class, 'orders'])->name('storefront.customer.orders');
    Route::get('/customer/orders/{orderNumber}', [CustomerAuthController::class, 'orderDetail'])->name('storefront.customer.order.detail');
});

// Custom CMS pages — MUST be the last storefront route so it never shadows a
// real route. Single path segment only (alnum + hyphen); reserved slugs can
// never be saved (StorePageRequest), so this only resolves to real pages.
Route::get('/{slug}', [\Modules\Ecommerce\Http\Controllers\Storefront\PageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('storefront.page.show');
