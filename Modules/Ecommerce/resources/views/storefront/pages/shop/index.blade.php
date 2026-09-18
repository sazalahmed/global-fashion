@extends('ecommerce::storefront.layouts.master')

@section('title', 'Shop')
@section('breadcrumb_title', 'Shop')

{{-- Price-range slider lib: loaded only on pages that render the filter (perf) --}}
@push('pageVendorCss')
    <link rel="stylesheet" href="{{ asset('website/assets/css/range_slider.css') }}">
@endpush
@push('pageVendorJs')
    <script src="{{ asset('website/assets/js/range_slider.js') }}"></script>
    <script src="{{ asset('website/assets/js/sticky_sidebar.js') }}"></script>
@endpush

@section('breadcrumb')
    <li><a href="{{ route('storefront.shop.index') }}">Shop</a></li>
@endsection

@section('content')
    <!--============================ SHOP PAGE START =============================-->
    <h1 class="bp-visually-hidden">Shop</h1>
    <section class="shop_page mt_70 mb_70">
        <div class="container">
            <div class="row">
                <div class="col-xxl-2 col-lg-4 col-xl-3">
                    <div id="sticky_sidebar">
                        <div class="shop_filter_btn d-lg-none"> Filter </div>
                        <div class="shop_filter_area">
                            {{-- Price + variant facets share one form so applying either
                                 keeps the other (plus the active category / sort / search). --}}
                            @php
                                $pbMin = (int) floor($priceBounds['min'] ?? 0);
                                $pbMax = (int) ceil($priceBounds['max'] ?? 0);
                            @endphp
                            <form action="{{ route('storefront.shop.index') }}" method="GET" class="shop_filter_form"
                                id="shopFilterForm">
                                {{-- Carry over the non-filter query params (category, brand, q,
                                     sort, per_page) so they survive an Apply. --}}
                                @foreach (request()->except(['min_price', 'max_price', 'page', 'variants', 'stock_status']) as $key => $value)
                                    @if (is_array($value))
                                        @foreach ($value as $v)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach

                                {{-- Price Range — bounds come from the lowest/highest active product price --}}
                                <div class="sidebar_range">
                                    <h3>Price Range</h3>
                                    @if ($pbMax > $pbMin)
                                        <div class="price-range-slider js-price-range" data-min="{{ $pbMin }}"
                                            data-max="{{ $pbMax }}" data-from="{{ request('min_price', '') }}"
                                            data-to="{{ request('max_price', '') }}"></div>
                                        <input type="hidden" name="min_price" class="js-price-min-input"
                                            value="{{ request('min_price') }}">
                                        <input type="hidden" name="max_price" class="js-price-max-input"
                                            value="{{ request('max_price') }}">
                                    @else
                                        <p class="text-muted fs-12 mb-0">No price data available.</p>
                                    @endif
                                </div>

                                {{-- Product Status (availability) facet --}}
                                @php $selStock = (array) request('stock_status', []); @endphp
                                <div class="sidebar_stock_filter">
                                    <h3>Product Status</h3>
                                    <ul class="stock-filter-list">
                                        <li class="stock-filter-item">
                                            <label class="stock-filter-label form-check">
                                                <input class="form-check-input" type="radio" name="stock_status[]"
                                                    value="in" {{ in_array('in', $selStock) ? 'checked' : '' }}>
                                                <span class="stock-filter-name">In Stock</span>
                                            </label>
                                        </li>
                                        <li class="stock-filter-item">
                                            <label class="stock-filter-label form-check">
                                                <input class="form-check-input" type="radio" name="stock_status[]"
                                                    value="out" {{ in_array('out', $selStock) ? 'checked' : '' }}>
                                                <span class="stock-filter-name">Out of Stock</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>

                                {{-- Categories (hierarchical tree, expand/collapse via +/-).
                                     Lives inside the form for ordering; the nodes are links/
                                     type=button toggles, so they never submit the form. --}}
                                <div class="sidebar_category shop-cat-tree-wrapper">
                                    <h3>Categories</h3>
                                    <ul class="shop-cat-tree">
                                        @if (isset($categories) && $categories->count())
                                            @foreach ($categories as $category)
                                                @include(
                                                    'ecommerce::storefront.pages.shop.partials.category-node',
                                                    ['category' => $category, 'depth' => 0]
                                                )
                                            @endforeach
                                        @else
                                            <li class="text-muted">No categories available</li>
                                        @endif
                                    </ul>
                                </div>

                                {{-- Variant facets — one "Filter by {Attribute}" section each
                                     (e.g. Filter by Color, Filter by Size). Only attributes used
                                     by active products are returned, so a section renders only
                                     when such values exist. --}}
                                @if (isset($variantFilters) && $variantFilters->isNotEmpty())
                                    @foreach ($variantFilters as $attr)
                                        <div class="sidebar_variant_filter">
                                            <h3>Filter by {{ $attr['name'] }}</h3>
                                            <ul class="variant-filter-list">
                                                @foreach ($attr['values'] as $val)
                                                    <li class="variant-filter-item">
                                                        <label class="variant-filter-label form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="variants[]" value="{{ $val['value'] }}"
                                                                {{ in_array($val['value'], $selectedVariants ?? []) ? 'checked' : '' }}>
                                                            @if ($attr['display_type'] === 'color_swatch' && !empty($val['color_code']))
                                                                <span class="variant-swatch"
                                                                    data-color="{{ $val['color_code'] }}"></span>
                                                            @endif
                                                            <span class="variant-filter-name">{{ $val['value'] }}</span>
                                                        </label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                @endif

                                {{-- Filters auto-apply on change (no Apply button). --}}
                                <noscript>
                                    <button type="submit" class="common_btn w-100 mt-2">Apply Filters</button>
                                </noscript>
                            </form>

                            {{-- Clear Filters --}}
                            @if (request()->hasAny(['category', 'brand', 'min_price', 'max_price', 'q', 'search', 'variants', 'stock_status']))
                                <a href="{{ route('storefront.shop.index') }}"
                                    class="common_btn w-100 text-center d-block mt-3">
                                    <i class="fas fa-times me-1"></i> Clear All Filters
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xxl-10 col-lg-8 col-xl-9">
                    <div class="product_page_top">
                        <div class="row">
                            <div class="col-xl-6 col-md-5 col-12">
                                <div class="product_page_top_button">
                                    @if (isset($products) && $products->total() > 0)
                                        <p>Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of
                                            {{ $products->total() }} results</p>
                                    @else
                                        <p>No products found</p>
                                    @endif
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-7 col-12">
                                <ul class="product_page_sorting">
                                    <li>
                                        <select class="select_js" id="shopSortSelect">
                                            <option value="featured"
                                                {{ in_array(request('sort', 'featured'), ['featured', 'newest']) ? 'selected' : '' }}>
                                                Default Sorting</option>
                                            <option value="price_low"
                                                {{ request('sort') == 'price_low' ? 'selected' : '' }}>Low to High</option>
                                            <option value="price_high"
                                                {{ request('sort') == 'price_high' ? 'selected' : '' }}>High to Low
                                            </option>
                                            <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>
                                                Name: A-Z</option>
                                            <option value="name_desc"
                                                {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name: Z-A</option>
                                        </select>
                                    </li>
                                    <li>
                                        <select class="select_js" id="shopPerPageSelect">
                                            @foreach ($perPageOptions as $option)
                                                <option value="{{ $option }}"
                                                    {{ (int) ($perPage ?? 0) === $option ? 'selected' : '' }}>Show:
                                                    {{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Products and combos are interleaved in $products by the
                             admin-defined per-category order; each item renders with
                             its matching card. --}}
                        @forelse($products ?? [] as $item)
                            <div class="col-xxl-3 col-6 col-md-4 col-lg-6 col-xl-4 wow fadeInUp">
                                @if (($item->catalog_type ?? 'product') === 'combo')
                                    @include('ecommerce::storefront.partials.combo-card', [
                                        'combo' => $item,
                                        'service' => $comboService,
                                    ])
                                @else
                                    @include('ecommerce::storefront.partials.product-card', [
                                        'product' => $item,
                                    ])
                                @endif
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5>No products found</h5>
                                    <p class="text-muted">Try adjusting your filters or search criteria.</p>
                                    <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">View All
                                        Products</a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xxl-2 col-lg-4 col-xl-3"></div>
                <div class="col-xxl-10 col-lg-8 col-xl-9">
                    {{-- Pagination --}}
                    @if (isset($products) && $products->hasPages())
                        <div class="row">
                            <div class="pagination_area">
                                {{ $products->links('ecommerce::storefront.partials.pagination') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <!--============================ SHOP PAGE END =============================-->
@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            // Auto-apply filters on change (Apply button removed): submitting the
            // GET form reloads the product list with the new facet selection.
            $('#shopFilterForm').on('change', 'input[name="variants[]"], input[name="stock_status[]"]', function() {
                $('#shopFilterForm').trigger('submit');
            });

            $('#shopSortSelect').on('change', function() {
                var url = new URL(window.location.href);
                url.searchParams.set('sort', $(this).val());
                url.searchParams.delete('page');
                window.location.href = url.toString();
            });

            $('#shopPerPageSelect').on('change', function() {
                var url = new URL(window.location.href);
                url.searchParams.set('per_page', $(this).val());
                url.searchParams.delete('page');
                window.location.href = url.toString();
            });

            // ── Price range slider ──
            // Dual-knob slider whose bounds are the lowest/highest active product
            // price (passed via data-attributes). On change we mirror the values into
            // the hidden min_price/max_price inputs and the readout; the form's
            // "Apply Filters" button submits them. Prices are formatted with the same
            // window.bdPrice() helper the server uses, so tooltips/readout match.
            $('.js-price-range').each(function() {
                var $el = $(this);
                var min = parseFloat($el.data('min'));
                var max = parseFloat($el.data('max'));
                if (isNaN(min) || isNaN(max) || max <= min) {
                    return;
                }

                var rawFrom = $el.data('from');
                var rawTo = $el.data('to');
                var from = (rawFrom === '' || rawFrom == null) ? min : parseFloat(rawFrom);
                var to = (rawTo === '' || rawTo == null) ? max : parseFloat(rawTo);
                from = Math.max(min, Math.min(from, max));
                to = Math.max(min, Math.min(to, max));
                if (from > to) {
                    from = min;
                    to = max;
                }

                // Coarsen the step for wide ranges so the points map stays small.
                var span = max - min;
                var step = 1;
                if (span > 1000) {
                    step = 10;
                }
                if (span > 10000) {
                    step = 100;
                }
                if (span > 100000) {
                    step = 1000;
                }

                var $form = $el.closest('form');
                var $minIn = $form.find('.js-price-min-input');
                var $maxIn = $form.find('.js-price-max-input');

                $el.alRangeSlider({
                    range: {
                        min: min,
                        max: max,
                        step: step
                    },
                    initialSelectedValues: {
                        from: from,
                        to: to
                    },
                    showInputs: false,
                    // Single value display: the tooltip rides each knob, so the shown
                    // price moves with the pointer as you drag.
                    showTooltips: true,
                    grid: {
                        minTicksStep: 1,
                        marksStep: 1
                    },
                    prettify: function(value) {
                        return '{{ currency_symbol() }} ' + window.bdPrice(value);
                    },
                    onChange: function(state) {
                        var sv = (state && state.selectedValues) ? state.selectedValues : {};
                        var f = Math.round(sv.from != null ? sv.from : from);
                        var t = Math.round(sv.to != null ? sv.to : to);
                        $minIn.val(f);
                        $maxIn.val(t);
                    },
                    // Auto-apply once the user finishes dragging a knob.
                    onFinish: function(state) {
                        var sv = (state && state.selectedValues) ? state.selectedValues : {};
                        $minIn.val(Math.round(sv.from != null ? sv.from : from));
                        $maxIn.val(Math.round(sv.to != null ? sv.to : to));
                        $form.trigger('submit');
                    }
                });
            });

            // Colour the variant swatches from their data-color (dynamic value, so
            // it lives in JS rather than inline CSS).
            $('.variant-swatch[data-color]').each(function() {
                $(this).css('background-color', $(this).data('color'));
            });

            // ── Category tree expand/collapse ──
            // The +/- button toggles its own node's children list; clicking the
            // text link still navigates as before (we stop propagation on the
            // button so its parent <a> isn't followed).
            $('.shop-cat-tree').on('click', '.shop-cat-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $btn = $(this);
                var $node = $btn.closest('.shop-cat-node');
                var $kids = $node.children('.shop-cat-children');
                var open = $node.hasClass('is-open');
                if (open) {
                    $node.removeClass('is-open');
                    $kids.prop('hidden', true);
                    $btn.attr('aria-expanded', 'false').find('i').removeClass('fa-minus').addClass(
                        'fa-plus');
                } else {
                    $node.addClass('is-open');
                    $kids.prop('hidden', false);
                    $btn.attr('aria-expanded', 'true').find('i').removeClass('fa-plus').addClass(
                        'fa-minus');
                }
            });
        });
    </script>

    @php
        $bpListName = request()->filled('q')
            ? 'Search Results'
            : (request('category') ? \Illuminate\Support\Str::headline((string) request('category')) : 'Shop');
        $bpListId = request()->filled('q')
            ? 'search_results'
            : (request('category') ? 'category_' . \Illuminate\Support\Str::slug((string) request('category'), '_') : 'shop');
        $bpListItems = collect($products instanceof \Illuminate\Contracts\Pagination\Paginator ? $products->items() : ($products ?? []))
            ->values()
            ->map(function ($p, $i) use ($comboService) {
                $isCombo = ($p->catalog_type ?? 'product') === 'combo';
                if ($isCombo) {
                    $summed = (float) $comboService->summedPrice($p);
                    $effective = (float) $comboService->effectivePrice($p);
                    $discount = $summed > $effective ? round($summed - $effective, 2) : 0.0;
                } else {
                    $price = $p->displayPrice();
                    $effective = (float) $price->effective;
                    $discount = $price->has_discount ? round((float) $price->sell - $effective, 2) : 0.0;
                }
                $item = [
                    'item_id'   => $isCombo ? 'combo:' . $p->id : (string) $p->id,
                    'item_name' => $p->name,
                    'price'     => $effective,
                    'index'     => $i,
                    'quantity'  => 1,
                ];
                if ($discount > 0) {
                    $item['discount'] = $discount;
                }
                return $item;
            })->all();
    @endphp
    <script>
        'use strict';
        // List context for select_item clicks on this page (read by cart.js).
        window.bpListContext = @json(['id' => $bpListId, 'name' => $bpListName]);
    </script>
    @if(count($bpListItems))
        @include('ecommerce::storefront.partials.track-event', [
            'event' => 'ViewCategory',
            'ga' => ['currency' => 'BDT', 'item_list_id' => $bpListId, 'item_list_name' => $bpListName, 'items' => $bpListItems],
            'fb' => ['data' => [
                'content_type' => 'product',
                'content_ids'  => array_column($bpListItems, 'item_id'),
                'content_category' => $bpListName,
            ]],
        ])
    @endif
    @if(request()->filled('q'))
        @include('ecommerce::storefront.partials.track-event', [
            'event' => 'Search',
            'ga' => ['search_term' => (string) request('q')],
            'fb' => ['data' => ['search_string' => (string) request('q')]],
        ])
    @endif
@endpush
