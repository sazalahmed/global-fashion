{{-- Combo Packages Section — renders admin-curated combos for this homepage section. --}}
@php
    $section = $section ?? null;
    $secHeading = $section?->getSetting('heading', 'Combo Packages') ?? 'Combo Packages';
    $secHighlight = $section?->getSetting('highlight', 'Combo') ?? 'Combo';
    $secLimit = (int) ($section?->getSetting('items_count', 6) ?? 6);
    $secViewLabel = $section?->getSetting('view_all_label', 'View all') ?? 'View all';
    $secViewLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
        $section?->getSetting('view_all_link', 'storefront.combos.index') ?? 'storefront.combos.index',
    );

    $comboService = app(\Modules\Ecommerce\Services\ComboService::class);
    $combos = $section
        ? $section->combos()->active()
            ->with(['items.product.images', 'items.variant'])
            ->take($secLimit)->get()
        : collect();
@endphp

@if ($combos->count() > 0)
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
                @foreach ($combos as $combo)
                    <div class="col-xl-1-5 col-6 col-md-4 col-xl-3 wow fadeInUp">
                        @include('ecommerce::storefront.partials.combo-card', ['combo' => $combo, 'service' => $comboService])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
