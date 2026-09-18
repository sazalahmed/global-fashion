{{-- Best Selling Section — Zenis Style Best Selling Product 2 --}}
@php
    $section = $section ?? null;
    $products = $bestSelling ?? collect();
    $firstThree = $products->take(3);
    $largeProduct = $products->skip(3)->first();

    $secHeading = $section?->getSetting('heading', 'Our Best Selling Products') ?? 'Our Best Selling Products';
    $secHighlight = $section?->getSetting('highlight', 'Best') ?? 'Best';
    $secViewLabel = $section?->getSetting('view_all_label', 'View all') ?? 'View all';
    $secViewLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('view_all_link', 'storefront.shop.index') ?? 'storefront.shop.index',
    );

    $promoImage = $section?->getSetting('promo_image') ?: null;
    $promoBtnLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('promo_button_link', 'storefront.shop.index') ?? 'storefront.shop.index',
    );
@endphp

@if ($products->count() > 0)
    <section class="best_selling_product_2 mt_60">
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
                <div class="col-lg-7">
                    <div class="row">
                        @foreach ($firstThree as $item)
                            @php
                                $p = \Modules\Ecommerce\Support\CatalogItemPresenter::for($item);
                            @endphp
                            <div class="col-xl-4 col-6 col-md-4 wow fadeInUp">
                                <div class="best_selling_product_item">
                                    <a class="img" href="{{ $p->url }}">
                                        <x-webp :src="$p->image" :default="asset(
                                            'website/assets/images/best_sell_pro_img_' . $loop->iteration . '.jpg',
                                        )" alt="{{ $p->name }}"
                                            class="img-fluid w-100" loading="lazy" />
                                    </a>
                                    @if (($p->hasDiscount && $p->discountPercent > 0) || $p->campaignBadge)
                                        <ul class="discount_list">
                                            @if ($p->hasDiscount && $p->discountPercent > 0)
                                                <li class="discount"><b>-</b> {{ round($p->discountPercent) }}%</li>
                                            @endif
                                            @if ($p->campaignBadge)
                                                <li class="new">{{ Str::limit($p->campaignBadge, 18) }}</li>
                                            @endif
                                        </ul>
                                    @endif
                                    <div class="text">
                                        <a class="title" href="{{ $p->url }}">{{ $p->name }}</a>
                                        <p class="price">{{ currency_symbol() }} {{ bd_price($p->effectivePrice) }}
                                            @if ($p->effectivePrice < $p->sellPrice)
                                                <del>{{ currency_symbol() }} {{ bd_price($p->sellPrice) }}</del>
                                            @endif
                                        </p>
                                        <a class="buy_btn" href="{{ $p->url }}">buy now <i
                                                class="fas fa-arrow-up"></i></a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-5 wow fadeInRight">
                    @if ($largeProduct)
                        @php
                            $p = \Modules\Ecommerce\Support\CatalogItemPresenter::for($largeProduct);
                        @endphp
                        <div class="best_selling_product_item_large">
                            <a href="{{ $p->url }}" class="d-block" aria-label="{{ $p->name }}">
                                <x-webp :src="$p->image" :default="asset('website/assets/images/best_sell_pro_img_4.jpg')" alt="{{ $p->name }}"
                                    class="img-fluid w-100" loading="lazy" />
                            </a>
                            @if (($p->hasDiscount && $p->discountPercent > 0) || $p->campaignBadge)
                                <ul class="discount_list">
                                    @if ($p->hasDiscount && $p->discountPercent > 0)
                                        <li class="discount"><b>-</b> {{ round($p->discountPercent) }}%</li>
                                    @endif
                                    @if ($p->campaignBadge)
                                        <li class="new">{{ Str::limit($p->campaignBadge, 18) }}</li>
                                    @endif
                                </ul>
                            @endif
                            <div class="text">
                                <a class="title" href="{{ $p->url }}">{{ $p->name }}</a>
                                <p class="price">{{ currency_symbol() }} {{ bd_price($p->effectivePrice) }} @if ($p->effectivePrice < $p->sellPrice)
                                        <del>{{ currency_symbol() }} {{ bd_price($p->sellPrice) }}</del>
                                    @endif
                                </p>
                                <a class="common_btn" href="{{ $p->url }}">buy now <i
                                        class="fas fa-long-arrow-right"></i></a>
                            </div>
                        </div>
                    @else
                        <div class="best_selling_product_item_large">
                            <a href="{{ $promoBtnLink }}" class="d-block">
                                <x-webp :src="$promoImage" :default="asset('website/assets/images/best_sell_pro_img_4.jpg')" alt="Best Sales" class="img-fluid w-100"
                                    loading="lazy" />
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
