<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketing\Http\Controllers\MarketingController;

Route::middleware('auth')->prefix('marketing')->name('marketing.')->group(function () {
    // Overview
    Route::get('/', [MarketingController::class, 'index'])->name('index');

    // SMS Campaigns
    Route::get('/sms-campaigns', [MarketingController::class, 'smsCampaigns'])->name('sms-campaigns');
    Route::get('/sms-campaigns/create', [MarketingController::class, 'smsCampaignCreate'])->name('sms-campaigns.create');
    Route::post('/sms-campaigns', [MarketingController::class, 'smsCampaignStore'])->name('sms-campaigns.store');
    Route::get('/sms-campaigns/{campaign}', [MarketingController::class, 'smsCampaignShow'])->name('sms-campaigns.show');
    Route::get('/sms-campaigns/{campaign}/edit', [MarketingController::class, 'smsCampaignEdit'])->name('sms-campaigns.edit');
    Route::put('/sms-campaigns/{campaign}', [MarketingController::class, 'smsCampaignUpdate'])->name('sms-campaigns.update');
    Route::post('/sms-campaigns/{campaign}/send', [MarketingController::class, 'smsCampaignSend'])->name('sms-campaigns.send');
    Route::post('/sms-campaigns/{campaign}/duplicate', [MarketingController::class, 'smsCampaignDuplicate'])->name('sms-campaigns.duplicate');
    Route::delete('/sms-campaigns/{campaign}', [MarketingController::class, 'smsCampaignDestroy'])->name('sms-campaigns.destroy');

    // Email
    Route::get('/email', [MarketingController::class, 'email'])->name('email');
    Route::get('/email/create', [MarketingController::class, 'emailCreate'])->name('email.create');
    Route::post('/email', [MarketingController::class, 'emailStore'])->name('email.store');

    // Loyalty
    Route::get('/loyalty', [MarketingController::class, 'loyalty'])->name('loyalty');
});
