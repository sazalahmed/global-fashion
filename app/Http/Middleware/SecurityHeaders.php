<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            // server.globalfashion.com.bd is the merchant's server-side GTM (sGTM)
            // container: the GTM web container sends first-party GA4 hits there and
            // loads Meta's CAPI param-builder bundle from its S3 origin.
            // pixelfly.io + *.pixelfly.io is the PixelFly server-side tracking
            // relay (Meta CAPI / GA4 / TikTok forwarding). The wildcard covers
            // track.pixelfly.io and CDN subdomains; the apex must be listed
            // separately because CSP wildcards don't match the bare domain.
            "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://www.facebook.com https://server.globalfashion.com.bd https://capi-automation.s3.us-east-2.amazonaws.com https://pixelfly.io https://*.pixelfly.io",
            "connect-src 'self' https://www.google-analytics.com https://*.google-analytics.com https://www.googletagmanager.com https://connect.facebook.net https://graph.facebook.com https://server.globalfashion.com.bd https://pixelfly.io https://*.pixelfly.io",
            // blob: lets client-side previews render a just-selected file via
            // URL.createObjectURL (e.g. the expense/sale receipt image preview)
            // before it's uploaded. These are same-origin object URLs the page
            // builds from the user's own file, so they carry no external risk.
            "img-src 'self' data: blob: https://www.google-analytics.com https://*.google-analytics.com https://www.googletagmanager.com https://www.facebook.com https://connect.facebook.net https://server.globalfashion.com.bd https://pixelfly.io https://*.pixelfly.io",
            "style-src 'self' 'unsafe-inline'",
            // data: needed by vendored Swiper CSS, which embeds its icon font
            // (next/prev arrows) as a base64 data URI.
            "font-src 'self' data:",
            // blob: allows the PDF receipt preview (iframe src = object URL of
            // the selected file) to render before upload — same-origin, safe.
            "frame-src 'self' blob: https://www.googletagmanager.com https://www.google.com https://maps.google.com https://server.globalfashion.com.bd https://pixelfly.io https://*.pixelfly.io",
        ]) . ';');

        // HTML pages embed a per-session CSRF token (<meta> + @csrf fields), so
        // they must never be replayed from the browser back/forward cache or a
        // proxy. A stale page carries a stale token and triggers a 419 on the
        // next POST. Force a fresh fetch for HTML; static assets are unaffected.
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
