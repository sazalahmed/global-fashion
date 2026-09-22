<?php

namespace Nayemuf\SteadfastCourier;

use Illuminate\Support\ServiceProvider;

class SteadfastCourierServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/steadfast.php',
            'steadfast'
        );

        // Register singleton for SteadfastCourier client
        $this->app->singleton('steadfast.courier', function ($app) {
            return new SteadfastCourier(
                config('steadfast.api_key'),
                config('steadfast.secret_key'),
                config('steadfast.base_url')
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__ . '/../config/steadfast.php' => config_path('steadfast.php'),
        ], 'steadfast-config');
    }
}

