<?php

namespace Modules\AiAssistant\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\AiAssistant\Observers\ProductEmbeddingObserver;
use Modules\Product\Models\Product;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    protected static $shouldDiscoverEvents = true;

    public function boot(): void
    {
        parent::boot();

        Product::observe(ProductEmbeddingObserver::class);
    }

    protected function configureEmailVerification(): void {}
}
