<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, target-densityDpi=device-dpi" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $page->meta_title ?? $page->name }} - BizPOS</title>
    @if($page->meta_description)
    <meta name="description" content="{{ $page->meta_description }}">
    @endif

    @php
        $lpCanonical = url()->current();
        $lpTitle = ($page->meta_title ?? null) ?: ($page->name ?? config('app.name'));
        $lpDesc = ($page->meta_description ?? null) ?: \Modules\Ecommerce\Models\EcommerceSetting::get('seo_default_description');
        $lpImage = \Modules\Ecommerce\Models\EcommerceSetting::get('seo_default_image');
        $lpImage = $lpImage ? (\Illuminate\Support\Str::startsWith($lpImage, ['http://','https://']) ? $lpImage : url($lpImage)) : null;
        $lpSite = \Modules\Ecommerce\Models\EcommerceSetting::get('seo_site_name') ?: config('app.name', 'BizPOS Pro');
    @endphp
    <link rel="canonical" href="{{ $lpCanonical }}">
    <meta name="robots" content="{{ config('app.env') === 'production' ? 'index,follow' : 'noindex,nofollow' }}">
    <meta property="og:site_name" content="{{ $lpSite }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $lpTitle }}">
    @if($lpDesc)<meta property="og:description" content="{{ $lpDesc }}">@endif
    <meta property="og:url" content="{{ $lpCanonical }}">
    @if($lpImage)<meta property="og:image" content="{{ $lpImage }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $lpTitle }}">
    @if($lpDesc)<meta name="twitter:description" content="{{ $lpDesc }}">@endif
    @if($lpImage)<meta name="twitter:image" content="{{ $lpImage }}">@endif
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $lpSite, 'url' => url('/')], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

    <!-- Vendor CSS (reuse from main app) -->
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">

    <!-- Landing page CSS -->
    <link href="{{ asset('vendor/landing/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/landing/css/slick.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/landing/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/landing/css/responsive.css') }}" rel="stylesheet">

    @if($page->custom_css)
    <style>{!! $page->custom_css !!}</style>
    @endif

    {{-- GTM + Pixel head scripts --}}
    <x-core::tracking-head />

    @stack('styles')
</head>

<body>
    {{-- GTM noscript --}}
    <x-core::tracking-body />

    @yield('content')

    <!-- Vendor JS (reuse from main app) -->
    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Landing page JS -->
    <script src="{{ asset('vendor/landing/js/slick.min.js') }}"></script>
    <script src="{{ asset('vendor/landing/js/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/landing/js/main.js') }}"></script>

    {{-- Tracking helper --}}
    <script src="{{ asset('js/tracking.js') }}?v={{ filemtime(public_path('js/tracking.js')) }}"></script>

    @stack('scripts')
</body>

</html>
