{{-- Flash Deals Section — Zenis Style Flash Sell 2 --}}
@php
    $section = $section ?? null;
    $deal = $flashDeal ?? null;
    $products = $deal?->products ?? ($flashSaleProducts ?? collect());

    $secHeading = $section?->getSetting('heading', 'Flash Sell') ?? 'Flash Sell';
    $secHighlight = $section?->getSetting('highlight', 'Flash') ?? 'Flash';
    $secLimit = (int) ($section?->getSetting('items_count', 12) ?? 12);
    $secViewLabel = $section?->getSetting('view_all_label', 'View all') ?? 'View all';
    $secViewLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('view_all_link', 'storefront.flash-deals') ?? 'storefront.flash-deals',
    );
@endphp

@if ($products->count() > 0)
    @php
        // Featured items for the promotion events (view_promotion/select_promotion).
        $bpPromoItems = $products->take($secLimit)->values()->map(function ($p, $i) {
            $price = $p->displayPrice();
            $item = [
                'item_id'   => (string) $p->id,
                'item_name' => $p->name,
                'price'     => (float) $price->effective,
                'index'     => $i,
                'quantity'  => 1,
            ];
            if ($price->has_discount) {
                $item['discount'] = round((float) $price->sell - (float) $price->effective, 2);
            }
            return $item;
        })->all();
    @endphp
    <section class="flash_sell_2 flash_sell mt_60 js-bp-promo"
        data-promotion-id="section:flash_deals"
        data-promotion-name="{{ $secHeading }}"
        data-creative-slot="homepage_section"
        data-bp-items="{{ json_encode($bpPromoItems) }}">
        <div class="container">
            <div class="row align-items-center justify-content-between">
                <div class="col-auto col-md-4 col-lg-3 col-xl-2 col-xxl-2">
                    <div class="section_heading_2 section_heading">
                        <h3><x-ecommerce::section-heading :heading="$secHeading" :highlight="$secHighlight" /></h3>
                    </div>
                </div>
                <div class="col-auto col-md-5 col-lg-6 col-xl-7 col-xxl-8">
                    <div class="flash_sell_2_heading">
                        @if ($deal && $deal->ends_at)
                            <div class="simply-countdown simply-countdown-one"></div>
                        @endif
                    </div>
                </div>
                <div class="col-auto col-md-3 col-lg-3 col-xl-3 col-xxl-2">
                    <div class="d-flex flex-wrap justify-content-end">
                        <div class="view_all_btn_area">
                            <a class="view_all_btn" href="{{ $secViewLink }}">{{ $secViewLabel }}</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt_10 flash_sell_2_slider">
                @foreach ($products->take($secLimit) as $product)
                    <div class="col-xl-1-5 wow fadeInUp">
                        @include('ecommerce::storefront.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            'use strict';
            @if ($deal && $deal->ends_at)
                var flashEnd = new Date('{{ $deal->ends_at->format('Y/m/d H:i:s') }}');
                if (flashEnd && !isNaN(flashEnd.getTime())) {
                    simplyCountdown('.simply-countdown-one', {
                        year: flashEnd.getFullYear(),
                        month: flashEnd.getMonth() + 1,
                        day: flashEnd.getDate(),
                        hours: flashEnd.getHours(),
                        minutes: flashEnd.getMinutes(),
                        seconds: flashEnd.getSeconds()
                    });
                }
            @endif
        </script>
    @endpush
@endif
