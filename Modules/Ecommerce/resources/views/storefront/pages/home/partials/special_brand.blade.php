{{-- Special Brand Products Section — Zenis Style Special Product 2 --}}
@php
    $section = $section ?? null;
    $products = $specialProducts ?? collect();

    $secHeading = $section?->getSetting('heading', 'Our Spatial Brand Products') ?? 'Our Spatial Brand Products';
    $secHighlight = $section?->getSetting('highlight', 'Spatial') ?? 'Spatial';
    $secLimit = (int) ($section?->getSetting('items_count', 9) ?? 9);
    $secViewLabel = $section?->getSetting('view_all_label', 'View all') ?? 'View all';
    $secViewLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('view_all_link', 'storefront.shop.index') ?? 'storefront.shop.index',
    );

    $bannerImage = $section?->getSetting('banner_image') ?: null;
    $bannerBtnLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('banner_button_link', 'storefront.shop.index') ?? 'storefront.shop.index',
    );
@endphp

@if ($products->count() > 0)
    <section class="special_product_2 pt_55">
        <div class="container">
            <div class="row">
                <div class="col-xl-6 col-9">
                    <div class="section_heading_2 section_heading">
                        <h3><x-ecommerce::section-heading :heading="$secHeading" :highlight="$secHighlight" /></h3>
                    </div>
                </div>
                <div class="col-xl-6 col-3">
                    <div class="view_all_btn_area">
                        <a class="view_all_btn" href="{{ $secViewLink }}">{{ $secViewLabel }}</a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-3 wow fadeInLeft order-2">
                    <div class="special_product_banner">
                        <a href="{{ $bannerBtnLink }}" class="d-block">
                            <x-webp :src="$bannerImage" :default="asset('website/assets/images/home2_special_banner.jpg')" alt="special product" class="img-fluid w-100" loading="lazy" />
                        </a>
                    </div>
                </div>
                <div class="col-xl-9 order-xl-2 ">
                    <div class="row">
                        @foreach ($products->take($secLimit) as $item)
                            @php
                                $p = \Modules\Ecommerce\Support\CatalogItemPresenter::for($item);
                                $savingAmount = max(0, round($p->sellPrice - $p->effectivePrice));
                            @endphp
                            <div class="col-sm-6 col-xl-4 wow fadeInUp">
                                <div class="special_product_item">
                                    <div class="special_product_img">
                                        <a href="{{ $p->url }}">
                                            <x-webp :src="$p->image" :default="asset('website/assets/images/product_placeholder.png')" alt="{{ $p->name }}" class="img-fluid w-100" loading="lazy" />
                                        </a>
                                        @if ($p->hasDiscount)
                                            <span class="discount">save {{ currency_symbol() }}
                                                {{ bd_price($savingAmount) }}</span>
                                        @endif
                                    </div>
                                    <div class="special_product_text">
                                        @if ($p->categoryName)
                                            <a class="sp_category"
                                                href="{{ route('storefront.shop.index', ['category' => $p->categorySlug]) }}">{{ $p->categoryName }}</a>
                                        @endif
                                        <a class="title" href="{{ $p->url }}">{{ $p->name }}</a>
                                        <p class="price">{{ currency_symbol() }} {{ bd_price($p->effectivePrice) }}
                                            @if ($p->hasDiscount)
                                                <del>{{ currency_symbol() }} {{ bd_price($p->sellPrice) }}</del>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
