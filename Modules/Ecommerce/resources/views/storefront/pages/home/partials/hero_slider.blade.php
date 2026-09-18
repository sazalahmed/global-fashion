{{-- Hero Slider Section — Zenis Style Banner 2 --}}
@php
    $banners = $heroBanners ?? collect();
    // Left "Browse Categories" sidebar follows the Browse-Categories-menu flag
    // (show_in_menu), NOT the homepage Top Categories flag (show_in_top).
    $categories = $menuCategories ?? collect();
    $promos = $promoBanners ?? collect();
@endphp

<section class="banner_2">
    <div class="container">
        <div class="row">
            {{-- Left: Category Sidebar --}}
            <div class="col-xl-2 d-none d-xxl-block">
                <ul class="menu_cat_item">
                    @forelse($categories->take(10) as $category)
                        <li>
                            <a href="{{ route('storefront.category.show', $category->slug) }}">
                                {{ $category->name }}
                            </a>
                            @if($category->relationLoaded('children') && $category->children->count() > 0)
                                <ul class="menu_cat_droapdown">
                                    @foreach($category->children as $child)
                                        <li><a href="{{ route('storefront.category.show', $child->slug) }}">{{ $child->name }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @empty
                        <li><a href="{{ route('storefront.shop.index') }}">All Products</a></li>
                    @endforelse
                    <li class="all_category">
                        <a href="{{ route('storefront.category.index') }}">View All Categories <i class="fas fa-arrow-right"></i></a>
                    </li>
                </ul>
            </div>

            {{-- Center: Main Banner Slider --}}
            <div class="col-xxl-7 col-lg-8">
                <div class="banner_content">
                    {{-- Swiper hero slider (single-slide fade + dots, init in custom.js).
                         .banner_slider_2 keeps the original design; only the
                         wrapper/slide structure is Swiper's. --}}
                    <div class="swiper banner_2_slider">
                        <div class="swiper-wrapper">
                        @if($banners->count() > 0)
                            @foreach($banners as $banner)
                                <div class="swiper-slide">
                                    <div class="banner_slider_2 wow fadeInUp js-bp-promo"
                                        data-promotion-id="banner:{{ $banner->id }}"
                                        data-promotion-name="{{ $banner->title ?: 'Banner ' . $banner->id }}"
                                        data-creative-name="{{ $banner->subtitle ?: ($banner->title ?: 'Banner ' . $banner->id) }}"
                                        data-creative-slot="hero_slider"
                                        style="{!! bg_image_set($banner->image) !!}">
                                        <a href="{{ $banner->button_url ?: route('storefront.shop.index') }}" class="banner_link_overlay" aria-label="{{ $banner->title ?: __('View offer') }}"></a>
                                        <div class="banner_slider_2_text">
                                            @if($banner->subtitle)
                                                {{-- aria-level keeps a valid heading order (h1 -> h2) without retagging the styled kicker --}}
                                                <h3 aria-level="2">{{ $banner->subtitle }}</h3>
                                            @endif
                                            @if($banner->title)
                                                <h2>{{ $banner->title }}</h2>
                                            @endif
                                            @if($banner->button_text)
                                                <a class="common_btn" href="{{ $banner->button_url ?? route('storefront.shop.index') }}">{{ $banner->button_text }} <i class="fas fa-long-arrow-right"></i></a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="swiper-slide">
                                <div class="banner_slider_2 wow fadeInUp" style="{!! bg_image_set('website/assets/images/slider_1.jpg') !!}">
                                    <div class="banner_slider_2_text">
                                        <h3 aria-level="2">New arrivals of {{ date('Y') }}</h3>
                                        <h2>Where Fashion Meets Individuality</h2>
                                        <a class="common_btn" href="{{ route('storefront.shop.index') }}">shop now <i class="fas fa-long-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="banner_slider_2 wow fadeInUp" style="{!! bg_image_set('website/assets/images/slider_2.jpg') !!}">
                                    <div class="banner_slider_2_text">
                                        <h3 aria-level="2">Trending of this month</h3>
                                        <h2>Make your fashion look more changing</h2>
                                        <a class="common_btn" href="{{ route('storefront.shop.index') }}">shop now <i class="fas fa-long-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="banner_slider_2 wow fadeInUp" style="{!! bg_image_set('website/assets/images/slider_3.jpg') !!}">
                                    <div class="banner_slider_2_text">
                                        <h3 aria-level="2">Best selling of {{ date('Y') }}</h3>
                                        <h2>Discover Your Best Fitting Clothes</h2>
                                        <a class="common_btn" href="{{ route('storefront.shop.index') }}">shop now <i class="fas fa-long-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                        @endif
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>

            {{-- Right: Promo Banner --}}
            <div class="col-xxl-3 col-lg-4 col-sm-12 col-md-12">
                <div class="row">
                    <div class="col-xl-12">
                        @if($promos->count() > 0)
                            @php $promo = $promos->first(); @endphp
                            <div class="banner_2_add wow fadeInUp js-bp-promo"
                                data-promotion-id="banner:{{ $promo->id }}"
                                data-promotion-name="{{ $promo->title ?: 'Banner ' . $promo->id }}"
                                data-creative-name="{{ $promo->subtitle ?: ($promo->title ?: 'Banner ' . $promo->id) }}"
                                data-creative-slot="promo_large"
                                style="{!! bg_image_set($promo->image) !!}">
                                <a href="{{ $promo->button_url ?: route('storefront.shop.index') }}" class="banner_link_overlay" aria-label="{{ $promo->title ?: __('View offer') }}"></a>
                                <div class="text">
                                    @if($promo->subtitle) <h4 aria-level="3">{{ $promo->subtitle }}</h4> @endif
                                    @if($promo->title) <h2>{{ $promo->title }}</h2> @endif
                                    @if($promo->button_text)
                                        <a class="common_btn" href="{{ $promo->button_url ?? route('storefront.shop.index') }}">{{ $promo->button_text }} <i class="fas fa-long-arrow-right"></i></a>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="banner_2_add wow fadeInUp" style="{!! bg_image_set('website/assets/images/banner_3_add_bg_1.jpg') !!}">
                                <div class="text">
                                    <h4 aria-level="3">Summer Offer</h4>
                                    <h2>Make Your Fashion Story Unique Every Day</h2>
                                    <a class="common_btn" href="{{ route('storefront.shop.index') }}">shop now <i class="fas fa-long-arrow-right"></i></a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
