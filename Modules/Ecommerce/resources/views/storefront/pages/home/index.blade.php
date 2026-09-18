@extends('ecommerce::storefront.layouts.master')

@section('title', 'Home')

{{-- Tabs lib: the trending-products tabs are home-only (perf) --}}
@push('pageVendorCss')<link rel="stylesheet" href="{{ asset('website/assets/css/jquery.pwstabs.css') }}">@endpush
@push('pageVendorJs')<script src="{{ asset('website/assets/js/jquery.pwstabs.min.js') }}"></script>@endpush

{{-- Swiper powers the hero banner slider (.banner_2_slider) --}}
@push('pageVendorCss')<link rel="stylesheet" href="{{ asset('website/assets/vendor/swiper/swiper-bundle.min.css') }}">@endpush
@push('pageVendorJs')<script src="{{ asset('website/assets/vendor/swiper/swiper-bundle.min.js') }}"></script>@endpush

@push('styles')
    @php
        // Best-effort: preload the first hero LCP image to reduce render-blocking delay.
        // $heroBanners is populated by StorefrontHomeController; fall back to the static
        // placeholder that hero_slider.blade.php renders when no banners exist in DB.
        // Prefer the .webp variant so the preload matches what image-set() actually
        // serves (avoids double-downloading the JPEG + WebP).
        $bpFirstHeroBanner = ($heroBanners ?? collect())->first();
        $bpHeroRel = $bpFirstHeroBanner ? ltrim((string) $bpFirstHeroBanner->image, '/') : 'website/assets/images/slider_1.jpg';
        $bpHeroWebp = ($bpHeroRel && is_file(public_path($bpHeroRel . '.webp'))) ? asset($bpHeroRel . '.webp') : null;
        $bpHeroPreloadUrl = $bpFirstHeroBanner ? upload_url($bpFirstHeroBanner->image) : asset('website/assets/images/slider_1.jpg');
    @endphp
    @if($bpHeroWebp)
        <link rel="preload" as="image" href="{{ $bpHeroWebp }}" type="image/webp">
    @elseif($bpHeroPreloadUrl)
        <link rel="preload" as="image" href="{{ $bpHeroPreloadUrl }}">
    @endif
@endpush

@section('content')
    <h1 class="bp-visually-hidden">Online Shopping</h1>
    @if(isset($sections) && $sections->count() > 0)
        @foreach($sections as $section)
            @if($section->is_active)
                @include('ecommerce::storefront.pages.home.partials.' . $section->section_type, [
                    'section' => $section
                ])
            @endif
        @endforeach
    @else
        {{-- Fallback: show default sections if homepage sections not seeded yet --}}
        @include('ecommerce::storefront.pages.home.partials.hero_slider')
        @include('ecommerce::storefront.pages.home.partials.flash_deals')
        @include('ecommerce::storefront.pages.home.partials.categories')
        @include('ecommerce::storefront.pages.home.partials.special_brand')
        @include('ecommerce::storefront.pages.home.partials.trending')
        @include('ecommerce::storefront.pages.home.partials.best_selling')
        @include('ecommerce::storefront.pages.home.partials.new_arrivals')
        @include('ecommerce::storefront.pages.home.partials.favourite')
        @include('ecommerce::storefront.pages.home.partials.brands')
        @include('ecommerce::storefront.pages.home.partials.blog')
        @include('ecommerce::storefront.partials.newsletter')
    @endif
@endsection
