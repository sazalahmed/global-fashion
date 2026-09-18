{{-- Promotional Banners Section — Zenis Style --}}
@php
    $banners = $promoBanners ?? collect();
@endphp

@if($banners->count() > 0)
<section class="promo_banners mt_50">
    <div class="container">
        <div class="row g-4">
            @foreach($banners->take(3) as $banner)
                <div class="col-md-{{ $banners->count() === 1 ? '12' : ($loop->first && $banners->count() >= 3 ? '6' : '6') }}">
                    <div class="promo_banner_item wow fadeInUp js-bp-promo"
                        data-wow-delay="{{ $loop->index * 0.1 }}s"
                        data-promotion-id="banner:{{ $banner->id }}"
                        data-promotion-name="{{ $banner->title ?: 'Banner ' . $banner->id }}"
                        data-creative-name="{{ $banner->subtitle ?: ($banner->title ?: 'Banner ' . $banner->id) }}"
                        data-creative-slot="promo_banners">
                        <a href="{{ $banner->button_url ?? route('storefront.shop.index') }}">
                            <x-webp :src="$banner->image" alt="{{ $banner->title }}" class="w-100" loading="lazy" />
                            <div class="promo_banner_text">
                                @if($banner->subtitle)
                                    <p>{{ $banner->subtitle }}</p>
                                @endif
                                @if($banner->title) <h3>{{ $banner->title }}</h3> @endif
                                @if($banner->button_text)
                                    <span class="common_btn_2">{{ $banner->button_text }} <i class="fas fa-arrow-right"></i></span>
                                @endif
                            </div>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
