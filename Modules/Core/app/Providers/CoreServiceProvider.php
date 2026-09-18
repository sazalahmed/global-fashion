<?php

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CoreServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Core';

    protected string $nameLower = 'core';

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
        $this->applyBrandSettings();
    }

    protected function applyBrandSettings(): void
    {
        $brand = $this->resolveBrandSettings();
        config(['app.name' => $brand['name']]);

        View::composer('*', function ($view) use ($brand) {
            foreach ($brand as $key => $value) {
                if ($key === 'social') {
                    if (!$view->offsetExists('companySocial')) {
                        $view->with('companySocial', $value);
                    }
                    continue;
                }
                $viewKey = 'company' . ucfirst($key);
                if (!$view->offsetExists($viewKey)) {
                    $view->with($viewKey, $value);
                }
            }
        });
    }

    protected function resolveBrandSettings(): array
    {
        $fallbackName = config('app.name') ?: 'BizPOS Pro';

        try {
            return \Illuminate\Support\Facades\Cache::remember('settings.brand', 3600, function () use ($fallbackName) {
                $name = \Modules\Setting\Models\Setting::get('business', 'company_name', $fallbackName) ?: $fallbackName;
                $logoPath = \Modules\Setting\Models\Setting::get('business', 'logo');
                $faviconPath = \Modules\Setting\Models\Setting::get('business', 'favicon');

                $whatsapp = \Modules\Setting\Models\Setting::get('business', 'whatsapp_number') ?: null;

                return [
                    'name'    => $name,
                    // Use upload_url() so both new "uploads/..." paths and legacy
                    // "/storage" disk paths resolve correctly (the old hardcoded
                    // "storage/" prefix 404'd for files saved under public/uploads).
                    'logo'    => upload_url($logoPath),
                    'favicon' => upload_url($faviconPath),
                    'phone'   => \Modules\Setting\Models\Setting::get('business', 'company_phone') ?: null,
                    'email'   => \Modules\Setting\Models\Setting::get('business', 'company_email') ?: null,
                    'address' => \Modules\Setting\Models\Setting::get('business', 'address') ?: null,
                    'social'  => array_filter([
                        'facebook'  => \Modules\Setting\Models\Setting::get('business', 'facebook_url') ?: null,
                        'instagram' => \Modules\Setting\Models\Setting::get('business', 'instagram_url') ?: null,
                        'youtube'   => \Modules\Setting\Models\Setting::get('business', 'youtube_url') ?: null,
                        'twitter'   => \Modules\Setting\Models\Setting::get('business', 'twitter_url') ?: null,
                        'linkedin'  => \Modules\Setting\Models\Setting::get('business', 'linkedin_url') ?: null,
                        'tiktok'    => \Modules\Setting\Models\Setting::get('business', 'tiktok_url') ?: null,
                        'whatsapp'  => $whatsapp ? 'https://wa.me/' . preg_replace('/\D+/', '', $whatsapp) : null,
                    ]),
                ];
            });
        } catch (\Throwable $e) {
            return ['name' => $fallbackName, 'logo' => null, 'favicon' => null, 'phone' => null, 'email' => null, 'address' => null, 'social' => []];
        }
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
        // $this->commands([]);
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
