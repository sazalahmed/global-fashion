{{-- Favourite Products Section — Zenis Style Favourite Product 2 --}}
@php
    $section = $section ?? null;
    $products = $favouriteProducts ?? collect();

    $secHeading = $section?->getSetting('heading', 'Our Favorite Style Product') ?? 'Our Favorite Style Product';
    $secHighlight = $section?->getSetting('highlight', 'Favorite') ?? 'Favorite';
    $secLimit = (int) ($section?->getSetting('items_count', 6) ?? 6);

    $bannerImage = $section?->getSetting('banner_image') ?: null;
    $bannerBtnLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('banner_button_link', 'storefront.shop.index') ?? 'storefront.shop.index',
    );
@endphp

@if ($products->count() > 0)
    <section class="favourite_product_2 mt_70">
        <div class="container">
            <div class="row">
                <div class="col-xl-3 col-lg-4 wow fadeInLeft">
                    <div class="bundle_product_banner">
                        <a href="{{ $bannerBtnLink }}" class="d-block">
                            <x-webp :src="$bannerImage" :default="asset('website/assets/images/favourite_pro_2_banner_img.png')" alt="bundle" class="img-fluid" loading="lazy" />
                        </a>
                    </div>
                </div>
                <div class="col-xl-9 col-lg-8">
                    <div class="row">
                        <div class="col-xl-8">
                            <div class="section_heading_2 section_heading">
                                <h3><x-ecommerce::section-heading :heading="$secHeading" :highlight="$secHighlight" /></h3>
                            </div>
                        </div>
                        <div class="row mt_25 favourite_product_2_slider">
                            @php
                                $comboService = app(\Modules\Ecommerce\Services\ComboService::class);
                            @endphp
                            @foreach ($products->take($secLimit) as $item)
                                <div class="col-xl-3 wow fadeInUp">
                                    @if (($item->catalog_type ?? 'product') === 'combo')
                                        @include('ecommerce::storefront.partials.combo-card', ['combo' => $item, 'service' => $comboService])
                                    @else
                                        @include('ecommerce::storefront.partials.product-card', [
                                            'product' => $item,
                                        ])
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
