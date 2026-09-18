<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEO: in production, 301-redirect any non-canonical host (e.g. www vs non-www)
 * to the host configured in APP_URL, over HTTPS — so search engines see one
 * canonical origin. No-op outside production so local/dev/tests are unaffected.
 */
class EnforceCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') === 'production') {
            $canonicalHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            if ($canonicalHost && strcasecmp($request->getHost(), $canonicalHost) !== 0) {
                return redirect()->to('https://' . $canonicalHost . $request->getRequestUri(), 301);
            }
        }

        return $next($request);
    }
}
