<?php

use Illuminate\Support\Facades\Route;
use Modules\LandingPage\Http\Controllers\LandingFrontController;

Route::post('/landing/order', [LandingFrontController::class, 'submitOrder'])->name('landing.order');
Route::get('/landing/order-success/{orderNumber}', [LandingFrontController::class, 'orderSuccess'])->name('landing.order.success');
