<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust reverse proxies (e.g. ngrok tunnel, load balancers) so Laravel
        // reads X-Forwarded-Proto/Host and generates correct https:// URLs and
        // redirects using the public host instead of the internal one.
        // NOTE: do NOT use '*' — it resolves to [REMOTE_ADDR], which is null
        // on synthetic requests (IDE extensions, some CLI bootstraps) and
        // crashes Symfony IpUtils with a TypeError. Loopback covers ngrok
        // (tunnel terminates locally); private ranges cover LBs on a VPC.
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '::1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Spatie permission middleware aliases — enables `role:`, `permission:`, `role_or_permission:` on routes
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Set locale from admin settings + enforce a single canonical host (SEO)
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnforceCanonicalHost::class,
        ]);

        // Landing page mode: disabled (module not installed)
        // $middleware->web(append: [
        //     \Modules\LandingPage\Http\Middleware\LandingPageMode::class,
        // ]);

        // Rate limit API routes
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
        ]);

        // Redirect unauthenticated customers to the storefront login page
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('customer/*')) {
                return route('storefront.customer.login');
            }
            return route('login');
        });

        // Redirect authenticated customers away from guest-only pages
        $middleware->redirectUsersTo(function ($request) {
            if ($request->is('login', 'register')) {
                return route('storefront.customer.profile');
            }
            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // AI assistant: SDK exceptions can fire DURING response streaming
        // (after the controller already returned), so they bypass any
        // try/catch in the controller. Render them here so the frontend
        // sees a clean JSON error instead of the styled HTML 500 page.
        $exceptions->renderable(function (\Laravel\Ai\Exceptions\InsufficientCreditsException $e, $request) {
            if ($request->is('ai/*') || $request->wantsJson()) {
                return response()->json([
                    'error' => 'AI provider is out of credits. Please contact support or top up the account.',
                ], 503);
            }
        });

        $exceptions->renderable(function (\Laravel\Ai\Exceptions\RateLimitedException $e, $request) {
            if ($request->is('ai/*') || $request->wantsJson()) {
                return response()->json([
                    'error' => 'AI assistant is rate-limited right now. Please try again in a minute.',
                    'retry_after' => 60,
                ], 429);
            }
        });

        $exceptions->renderable(function (\Laravel\Ai\Exceptions\FailoverableException $e, $request) {
            if ($request->is('ai/*') || $request->wantsJson()) {
                return response()->json([
                    'error' => 'AI assistant is temporarily unavailable. Please try again shortly.',
                ], 503);
            }
        });

        // Mid-stream connection failures from the AI provider (OpenAI's SSE
        // dropping a chunk, Gemini timing out, etc.) surface as Guzzle
        // RuntimeException at PSR7 Stream::read. Convert to a clean JSON
        // error for /ai/* routes so the chat widget shows a friendly bubble
        // instead of waiting forever on the dead stream.
        $exceptions->renderable(function (\RuntimeException $e, $request) {
            if (! $request->is('ai/*')) {
                return null;
            }
            $msg = $e->getMessage();
            if (str_contains($msg, 'Unable to read from stream') || str_contains($msg, 'stream is closed')) {
                report($e);
                return response()->json([
                    'error' => 'Connection to AI provider was interrupted. Please try again — your message may not have been received.',
                ], 502);
            }
            return null;
        });

        // CSRF token expired (the page was left open past the session lifetime).
        // Laravel converts TokenMismatchException into a 419 HttpException before
        // renderables run, so we match on the status code. The raw "419 PAGE
        // EXPIRED" screen is jarring on customer-facing pages, so bounce the
        // visitor back to the form with their input + a friendly note (or a clean
        // JSON message for AJAX) instead. Return null for non-419 so the 404
        // handler below still runs.
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Your session expired. Please refresh the page and try again.',
                ], 419);
            }

            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', 'Your session expired — please try again.');
        });

        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            $isAdmin = $request->is('admin/*') || $request->is('admin') || $request->is('pos/*') || $request->is('pos');

            if ($isAdmin) {
                // Try to boot session/auth for admin error pages
                try {
                    $sessionName = config('session.cookie', 'laravel_session');
                    $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
                    $rawCookie = $request->cookies->get($sessionName);

                    if ($rawCookie) {
                        $sessionId = $encrypter->decrypt($rawCookie, false);
                        $handler = app(\Illuminate\Session\SessionManager::class)->driver()->getHandler();
                        $store = new \Illuminate\Session\Store($sessionName, $handler, $sessionId);
                        $store->start();
                        $request->setLaravelSession($store);

                        // Check if user is logged in via session
                        $userId = $store->get(\Illuminate\Support\Facades\Auth::guard()->getName());
                        if ($userId) {
                            return response()->view('errors.404-admin', [], 404);
                        }
                    }
                } catch (\Throwable $ex) {
                    // Session read failed — fall through to frontend 404
                }
            }

            return response()->view('errors.404-frontend', [], 404);
        });
    })->create();
