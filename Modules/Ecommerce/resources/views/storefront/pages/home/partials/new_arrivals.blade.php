{{-- New Arrivals Section — Zenis Style New Arrival 2 --}}
@php
    $section = $section ?? null;
    $products = $newArrivals ?? collect();

    $secHeading = $section?->getSetting('heading', 'Our New arrival Products') ?? 'Our New arrival Products';
    $secHighlight = $section?->getSetting('highlight', 'New') ?? 'New';
    $secLimit = (int) ($section?->getSetting('items_count', 5) ?? 5);
    $secViewLabel = $section?->getSetting('view_all_label', 'View all') ?? 'View all';
    $secViewLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('view_all_link', 'storefront.shop.index') ?? 'storefront.shop.index',
    );
@endphp

@if ($products->count() > 0)
    <section class="new_arrival_2 mt_60">
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
                @php
                    $comboService = app(\Modules\Ecommerce\Services\ComboService::class);
                @endphp
                @foreach ($products->take($secLimit) as $item)
                    <div class="col-xl-1-5 col-6 col-md-4 col-xl-3 wow fadeInUp">
                        @if (($item->catalog_type ?? 'product') === 'combo')
                            @include('ecommerce::storefront.partials.combo-card', ['combo' => $item, 'service' => $comboService])
                        @else
                            @include('ecommerce::storefront.partials.product-card', ['product' => $item, 'isNewArrival' => true])
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
