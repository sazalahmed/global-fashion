{{-- Renders the full SEO <head> + JSON-LD from $seo (Modules\Ecommerce\Support\Seo). --}}
@php
    $seo = $seo ?? \Modules\Ecommerce\Support\Seo::make();
    $schemaSvc = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
    $graph = array_merge([$schemaSvc->organization(), $schemaSvc->website()], $seo->schema);
    $ogImage = $seo->renderedImage();
    $verification = \Modules\Ecommerce\Models\EcommerceSetting::get('google_site_verification');
    $twitterHandle = \Modules\Ecommerce\Models\EcommerceSetting::get('seo_twitter_handle');
@endphp
<title>{{ $seo->renderedTitle() }}</title>
<meta name="description" content="{{ $seo->renderedDescription() }}">
<link rel="canonical" href="{{ $seo->canonicalUrl() }}">
<meta name="robots" content="{{ config('app.env') === 'production' ? $seo->robots : 'noindex,nofollow' }}">
@if($verification)<meta name="google-site-verification" content="{{ $verification }}">@endif
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#1B4F72">

<meta property="og:site_name" content="{{ $seo->siteName() }}">
<meta property="og:locale" content="en_US">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->renderedTitle() }}">
<meta property="og:description" content="{{ $seo->renderedDescription() }}">
<meta property="og:url" content="{{ $seo->canonicalUrl() }}">
@if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif

<meta name="twitter:card" content="summary_large_image">
@if($twitterHandle)<meta name="twitter:site" content="{{ $twitterHandle }}">@endif
<meta name="twitter:title" content="{{ $seo->renderedTitle() }}">
<meta name="twitter:description" content="{{ $seo->renderedDescription() }}">
@if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
