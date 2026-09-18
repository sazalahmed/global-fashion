<?php

namespace Modules\AiAssistant\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'AiAssistant';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapStorefrontRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        // Mounted under /admin so the URL becomes /admin/ai-assistant, matching
        // the convention used by Modules/Ecommerce, Modules/Setting, etc.
        Route::middleware('web')->prefix('admin')->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }

    /**
     * Define the storefront routes for the public-facing AI assistant.
     *
     * Session-aware (web middleware) so the agent can read the storefront
     * customer guard and the session cart, but no auth is required —
     * guests are supported.
     */
    protected function mapStorefrontRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/storefront.php'));
    }
}
