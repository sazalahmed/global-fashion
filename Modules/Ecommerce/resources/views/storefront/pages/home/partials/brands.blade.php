{{-- Brands Section — Zenis Style Brand 2 (dynamic) --}}
@php
    $section = $section ?? null;
    $brandsList = $brands ?? collect();

    $secHeading   = $section?->getSetting('heading', 'Our Top Brands') ?? 'Our Top Brands';
    $secHighlight = $section?->getSetting('highlight', 'Brands') ?? 'Brands';
    $secViewLabel = $section?->getSetting('view_all_label', 'View all') ?? 'View all';
    $secViewLink  = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('view_all_link', 'storefront.shop.index') ?? 'storefront.shop.index',
    );
@endphp

@if($brandsList->count() > 0)
<section class="brand_2 mt_85">
    <div class="container">
        <div class="row">
            <div class="col-xl-6 col-sm-9">
                <div class="section_heading_2 section_heading">
                    <h3><x-ecommerce::section-heading :heading="$secHeading" :highlight="$secHighlight" /></h3>
                </div>
            </div>
            <div class="col-xl-6 col-sm-3">
                <div class="view_all_btn_area">
                    <a class="view_all_btn" href="{{ $secViewLink }}">{{ $secViewLabel }}</a>
                </div>
            </div>
        </div>
        <div class="row mt_40">
            <div class="col-12">
                <ul>
                    @foreach($brandsList as $brand)
                        <li class="wow fadeInUp">
                            <a href="{{ route('storefront.shop.index', ['brand' => $brand->slug]) }}">
                                <x-webp :src="$brand->logo" :default="asset('website/assets/images/brand1.png')" alt="{{ $brand->name }}" class="img-fluid" loading="lazy" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
@endif
