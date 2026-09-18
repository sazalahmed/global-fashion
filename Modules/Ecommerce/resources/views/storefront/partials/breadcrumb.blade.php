{{-- Page Banner / Breadcrumb Section (Zenis style) --}}
@php
    // Admin-configurable banner background (Ecommerce → Settings → Page Banner);
    // falls back to the bundled theme image when unset.
    $pageBannerBg = \Modules\Ecommerce\Models\EcommerceSetting::get('page_banner_bg')
        ?: 'website/assets/images/page_banner_bg.jpg';
@endphp
<section class="page_banner" style="{!! bg_image_set($pageBannerBg) !!}">
    <div class="page_banner_overlay">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="page_banner_text wow fadeInUp">
                        <h2 class="bp-breadcrumb-title">@yield('breadcrumb_title', 'Page')</h2>
                        <ul>
                            <li><a href="{{ route('storefront.home') }}"><i class="fas fa-home"></i> Home</a></li>
                            @yield('breadcrumb')
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
