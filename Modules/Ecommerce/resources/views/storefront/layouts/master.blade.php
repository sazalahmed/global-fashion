<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="csrf-refresh-url" content="{{ route('csrf.token') }}">

    @include('ecommerce::storefront.partials.seo-head')

    <link rel="icon" type="image/png" href="{{ $companyFavicon ?? asset('website/assets/images/favicon.png') }}">

    {{-- Performance: preconnect to analytics origins (Core Web Vitals) --}}
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <link rel="preconnect" href="https://connect.facebook.net" crossorigin>

    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/mobile_menu.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/scroll_button.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/select2.min.css') }}">
    @stack('pageVendorCss')
    <link rel="stylesheet"
        href="{{ asset('website/assets/css/custom_spacing.min.css') }}?v={{ filemtime(public_path('website/assets/css/custom_spacing.min.css')) }}">
    {{-- Main Stylesheet. Temporarily serving the UNMINIFIED source while CSS is
         being added; re-enable the minified build (and comment the source link)
         once all CSS is in and `npm run minify:css` has been re-run. --}}
    {{-- <link rel="stylesheet"
        href="{{ asset('website/assets/css/style.min.css') }}?v={{ filemtime(public_path('website/assets/css/style.min.css')) }}"> --}}
    <link rel="stylesheet"
        href="{{ asset('website/assets/css/style.css') }}?v={{ filemtime(public_path('website/assets/css/style.css')) }}">
    {{-- <link rel="stylesheet"
        href="{{ asset('website/assets/css/responsive.min.css') }}?v={{ filemtime(public_path('website/assets/css/responsive.min.css')) }}"> --}}
    <link rel="stylesheet"
        href="{{ asset('website/assets/css/responsive.css') }}?v={{ filemtime(public_path('website/assets/css/responsive.css')) }}">
    <link rel="stylesheet"
        href="{{ asset('website/assets/css/mobile-app.css') }}?v={{ filemtime(public_path('website/assets/css/mobile-app.css')) }}">

    {{-- Admin-configured color overrides --}}
    @php
        $themeOverrides = [
            '--themeColorOne' => \Modules\Ecommerce\Models\EcommerceSetting::get('theme_secondary_color'),
            '--themeColorTwo' => \Modules\Ecommerce\Models\EcommerceSetting::get('theme_primary_color'),
            '--colorYellow' => \Modules\Ecommerce\Models\EcommerceSetting::get('theme_accent_color'),
            '--colorGreen' => \Modules\Ecommerce\Models\EcommerceSetting::get('theme_success_color'),
            '--colorRed' => \Modules\Ecommerce\Models\EcommerceSetting::get('theme_danger_color'),
            '--lightBg' => \Modules\Ecommerce\Models\EcommerceSetting::get('theme_light_bg'),
        ];
        $themeOverrides = array_filter($themeOverrides, fn($v) => $v && $v !== '#000000');
    @endphp
    @if (!empty($themeOverrides))
        <style>
            :root {
                @foreach ($themeOverrides as $var => $val)
                    {{ $var }}: {{ $val }};
                @endforeach
            }
        </style>
    @endif

    <x-core::tracking-head />
    @stack('styles')
</head>

<body class="default_home">
    <x-core::tracking-body />

    {{-- Mobile App-Style Top Bar (mobile only) --}}
    @include('ecommerce::storefront.partials.mobile-app-bar')

    {{-- Header (hidden on mobile via CSS) --}}
    @include('ecommerce::storefront.partials.header')

    {{-- Navigation (hidden on mobile via its own d-none d-lg-block) --}}
    @include('ecommerce::storefront.partials.navigation')

    {{-- Mini Cart Offcanvas --}}
    @include('ecommerce::storefront.partials.mini-cart')

    {{-- Mobile Menu --}}
    @include('ecommerce::storefront.partials.mobile-menu')

    {{-- Breadcrumb --}}
    @hasSection('breadcrumb')
        @include('ecommerce::storefront.partials.breadcrumb')
    @endif

    {{-- Flash messages — redirect reasons (stock shortages, empty cart,
         coupon/order errors) were previously swallowed because no storefront
         view rendered the session flash. --}}
    @if (session('error') || session('success'))
        <div class="container">
            @if (session('error'))
                <div class="sf-flash sf-flash-error" role="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if (session('success'))
                <div class="sf-flash sf-flash-success" role="alert">
                    <i class="fas fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Main Content --}}
    @yield('content')

    {{-- Footer --}}
    @include('ecommerce::storefront.partials.footer')

    {{-- Mobile App-Style Bottom Navigation (mobile only) --}}
    @include('ecommerce::storefront.partials.mobile-bottom-nav')

    {{-- AI Shopping Assistant — Floating Chat Widget (renders only when enabled in settings) --}}
    @include('aiassistant::storefront.widget')

    {{-- Scroll Button --}}
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </div>

    {{-- Vendor JS --}}
    <script src="{{ asset('website/assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/jquery.waypoints.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/jquery.countup.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/select2.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/simplyCountdown.js') }}"></script>
    <script src="{{ asset('website/assets/js/slick.min.js') }}"></script>
    <script
        src="{{ asset('website/assets/vendor/extm/extm.js') }}?v={{ filemtime(public_path('website/assets/vendor/extm/extm.js')) }}">
    </script>
    <script src="{{ asset('website/assets/js/wow.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/jquery.marquee.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/scroll_button.js') }}"></script>
    @stack('pageVendorJs')

    {{-- Storefront price formatter — mirrors the bd_price() PHP helper so
         JS-updated prices (cart line totals, buy-now modal, range slider)
         match the server-rendered format exactly. --}}
    <script>
        'use strict';
        window.BP_PRICE_SEP = {{ storefront_price_separator() ? 'true' : 'false' }};
        window.bdPrice = function(amount) {
            amount = Number(amount) || 0;
            var negative = amount < 0;
            var decimals = (Math.abs(amount) % 1 === 0) ? 0 : 2;
            var fixed = Math.abs(amount).toFixed(decimals);
            var parts = fixed.split('.');
            var intPart = parts[0];
            var frac = parts[1] ? '.' + parts[1] : '';
            if (window.BP_PRICE_SEP && intPart.length > 3) {
                var last3 = intPart.slice(-3);
                var rest = intPart.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
                intPart = rest + ',' + last3;
            }
            return (negative ? '-' : '') + intPart + frac;
        };
        // Configured currency symbol (admin → Localization). Use bdMoney() to get
        // a fully formatted, symbol-prefixed price string in JS.
        window.CURRENCY_SYMBOL = @json(currency_symbol());
        window.bdMoney = function(amount) {
            return window.CURRENCY_SYMBOL + ' ' + window.bdPrice(amount);
        };

        // Single source for phone validation (Settings → Localization → Country).
        // Mirrors App\Helpers\PhoneHelper so storefront JS validates the same way
        // as the backend rule. Use window.bdPhoneValid(value) anywhere.
        window.BizPOS = window.BizPOS || {};
        window.BizPOS.phone = @json(\App\Helpers\PhoneHelper::getConfig());
        window.bdPhoneValid = function(value) {
            var cfg = window.BizPOS.phone || {};
            if (!value) return false;
            var digits = String(value).replace(/\D/g, '');
            var re = new RegExp('^' + (cfg.pattern || '01[3-9]\\d{8}') + '$');
            // Tolerate a leading dial code (e.g. 8801712345678), like the backend.
            var dial = String(cfg.dial_code || '').replace(/\D/g, '');
            if (dial && digits.indexOf(dial) === 0) {
                var national = digits.slice(dial.length);
                if (re.test(national) || re.test('0' + national)) return true;
            }
            return re.test(digits);
        };
    </script>

    <script
        src="{{ asset('website/assets/js/custom.js') }}?v={{ filemtime(public_path('website/assets/js/custom.js')) }}">
    </script>
    <script src="{{ asset('website/assets/js/cart.js') }}?v={{ filemtime(public_path('website/assets/js/cart.js')) }}">
    </script>
    <script src="{{ asset('website/assets/js/fuse.min.js') }}"></script>
    <script
        src="{{ asset('website/assets/js/live-search.js') }}?v={{ filemtime(public_path('website/assets/js/live-search.js')) }}">
    </script>

    <script src="{{ asset('js/csrf.js') }}?v={{ filemtime(public_path('js/csrf.js')) }}"></script>

    @if (session('bp_track'))
        @php($bpFlash = session('bp_track'))
        @include('ecommerce::storefront.partials.track-event', [
            'event' => $bpFlash['event'],
            'ga' => $bpFlash['ga'] ?? [],
            'fb' => $bpFlash['fb'] ?? [],
        ])
    @endif

    <script src="{{ asset('js/tracking.js') }}?v={{ filemtime(public_path('js/tracking.js')) }}"></script>
    @stack('scripts')
</body>

</html>
