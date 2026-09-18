<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Setting\Models\Setting;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $language = Setting::get('localization', 'language', 'English');

        $localeCode = match ($language) {
            'Bangla', 'Bengali', 'bn' => 'bn',
            default => 'en',
        };

        App::setLocale($localeCode);

        return $next($request);
    }
}
