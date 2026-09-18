<?php

namespace Modules\Ecommerce\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Ecommerce\Models\StorefrontCustomer;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class EcommerceServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Ecommerce';

    protected string $nameLower = 'ecommerce';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerStorefrontViewComposers();
        $this->registerPasswordResetUrl();
    }

    /**
     * Point the password-reset notification at the storefront reset route
     * for customers (the default targets the web "password.reset" route).
     */
    protected function registerPasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            if ($notifiable instanceof StorefrontCustomer) {
                return route('storefront.customer.password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]);
            }

            // Fallback for any other notifiable (e.g. admin users).
            return url('/reset-password/' . $token . '?email=' . urlencode($notifiable->getEmailForPasswordReset()));
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Ecommerce\Console\GenerateSitemap::class,
            \Modules\Ecommerce\Console\FixCourierCredentials::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Share common data with all storefront views.
     */
    protected function registerStorefrontViewComposers(): void
    {
        View::composer('ecommerce::storefront.*', function ($view) {
            if (! array_key_exists('seo', $view->getData())) {
                $view->with('seo', \Modules\Ecommerce\Support\Seo::make());
            }

            if (!$view->offsetExists('menuCategories')) {
                $view->with('menuCategories', \Modules\Category\Models\Category::active()
                    ->root()
                    ->where('show_in_menu', true)
                    ->ordered()
                    ->with(['children' => function ($q) {
                        $q->where('status', 'active')->orderBy('sort_order')
                            ->with(['children' => function ($q2) {
                                $q2->where('status', 'active')->orderBy('sort_order');
                            }]);
                    }])
                    ->limit(9)
                    ->get());
            }

            if (!$view->offsetExists('footerCategories')) {
                $view->with('footerCategories', \Modules\Category\Models\Category::active()
                    ->root()
                    ->ordered()
                    ->limit(6)
                    ->get());
            }

            if (!$view->offsetExists('customerAuthEnabled')) {
                $view->with('customerAuthEnabled', \Modules\Ecommerce\Models\EcommerceSetting::get('customer_auth_enabled', '1') === '1');
            }

            // Admin-managed menus (with hardcoded fallbacks in each partial when
            // a location has no items). Resolved against the current customer so
            // guest/auth visibility is applied server-side.
            if (!$view->offsetExists('mainMenu')) {
                $menuService = app(\Modules\Ecommerce\Services\MenuService::class);
                $customer    = auth('customer')->user();
                $view->with('mainMenu',   $menuService->getTree('header_main', $customer));
                $view->with('footerMenu', $menuService->getTree('footer', $customer));
                $view->with('mobileMenu', $menuService->getTree('mobile_drawer', $customer));
                $view->with('bottomNav',  $menuService->getTree('mobile_bottom', $customer));
            }
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
