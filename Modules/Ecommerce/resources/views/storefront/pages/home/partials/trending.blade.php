{{-- Trending Products Section — Zenis Style Trending Product 2 --}}
@php
    $section = $section ?? null;
    $products = $trendingProducts ?? collect();

    $secHeading = $section?->getSetting('heading', 'Trending Products') ?? 'Trending Products';
    $secHighlight = $section?->getSetting('highlight', 'Trending') ?? 'Trending';
    $secLimit = (int) ($section?->getSetting('items_count', 10) ?? 10);
    $secViewLabel = $section?->getSetting('view_all_label', 'View all') ?? 'View all';
    $secViewLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('view_all_link', 'storefront.shop.index') ?? 'storefront.shop.index',
    );
@endphp

@if ($products->count() > 0)
    <section class="trending_product_2 mt_60">
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
            <div class="row wow fadeInUp">
                <div class="col-12">
                    <div class="product_tabs">
                        <div class="row">
                            @php
                                $comboService = app(\Modules\Ecommerce\Services\ComboService::class);
                            @endphp
                            @foreach ($products->take($secLimit) as $item)
                                <div class="col-xl-1-5 col-6 col-md-4 col-xl-3">
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
