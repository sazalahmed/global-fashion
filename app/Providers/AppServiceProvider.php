<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->loadHelpers();

        $this->app->singleton(\App\Services\Search\SearchableRegistry::class, function () {
            $registry = new \App\Services\Search\SearchableRegistry();

            foreach (config('search.models', []) as $modelClass) {
                $registry->register($modelClass);
            }

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // SEO: in production, generate absolute URLs (canonical/OG/sitemap) over HTTPS.
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        $this->registerBladeDirectives();
        $this->configureRateLimiting();

        // Super Admin bypasses all permission checks. Returning null (not false)
        // for non-super-admins lets Spatie resolve the permission normally.
        Gate::before(function ($user, $ability) {
            return $user && $user->hasRole('Super Admin') ? true : null;
        });
    }

    /**
     * Load the global function helpers.
     *
     * These files are also listed in composer.json's `autoload.files`, but that
     * list is baked into vendor/composer/autoload_files.php when the autoloader
     * is dumped. A deploy that ships code without re-running
     * `composer dump-autoload` therefore delivers the helper file but not the
     * entry that loads it, and every call fatals with "Call to undefined
     * function" — which is how initials() broke in production.
     *
     * Requiring them here survives a stale autoloader: this provider is found
     * through PSR-4, which resolves against the filesystem at request time.
     * Every function in these files is function_exists-guarded, so the second
     * load is a no-op when the autoloader is current.
     *
     * Class-based helpers (PhoneHelper, Upload) are PSR-4 and belong nowhere
     * near this list.
     */
    protected function loadHelpers(): void
    {
        $helpers = [
            'BusinessHelper',
            'CurrencyHelper',
            'PermissionHelper',
            'TextHelper',
            'UploadHelper',
        ];

        foreach ($helpers as $helper) {
            require_once app_path("Helpers/{$helper}.php");
        }
    }

    /**
     * Configure rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // API routes: 60 requests per minute per authenticated user (or IP)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login/auth: 5 attempts per 15 minutes per IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(15, 5)->by($request->ip());
        });

        // Global web: 120 requests per minute per IP
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // Export/import: 10 per minute (heavy operations)
        RateLimiter::for('heavy', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Register custom Blade directives for permission checking.
     */
    protected function registerBladeDirectives(): void
    {
        // @can already works with Spatie's HasRoles trait.
        // @bpCan('permission.name') - shorthand for checking authenticated user permission.
        Blade::if('bpCan', function (string $permission) {
            $user = Auth::user();
            return $user && $user->can($permission);
        });

        // @superAdmin - check if user is Super Admin
        Blade::if('superAdmin', function () {
            $user = Auth::user();
            return $user && $user->hasRole('Super Admin');
        });

        // @bpCanAny('a','b') - render block if user has ANY of the given permissions.
        Blade::if('bpCanAny', function (string ...$permissions) {
            return bpCanAny(...$permissions);
        });
    }
}
