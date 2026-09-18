<?php

namespace Modules\LandingPage\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Setting\Models\Setting;

class LandingPageMode
{
    public function handle(Request $request, Closure $next)
    {
        $mode = $request->attributes->get('_landing_page_mode')
            ?? Setting::get('landing_page', 'mode', 'full_site');
        $request->attributes->set('_landing_page_mode', $mode);

        if ($mode !== 'landing_page') {
            return $next($request);
        }

        $path = $request->path();

        // Always allow these paths
        $allowedPrefixes = [
            'admin',           // Entire admin panel
            'landing',         // Landing order + success pages
            'login',           // Auth routes
            'logout',
            'api',             // API routes
            'storage',         // Storage files
            'vendor',          // Asset files
            'css', 'js', 'images', // Static assets
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        // Allow the home page (landing page renders here)
        if ($path === '/' || $path === '') {
            return $next($request);
        }

        // Block everything else — redirect to landing page
        return redirect('/');
    }
}
