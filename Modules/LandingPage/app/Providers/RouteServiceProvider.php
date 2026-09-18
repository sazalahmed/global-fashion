<?php

namespace Modules\LandingPage\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'LandingPage';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapFrontRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->prefix('admin')
            ->group(module_path($this->name, '/routes/web.php'));
    }

    protected function mapFrontRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->name, '/routes/front.php'));
    }
}
