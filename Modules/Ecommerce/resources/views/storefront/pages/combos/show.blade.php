@extends('ecommerce::storefront.layouts.master')

@section('title', $combo->name)
@section('breadcrumb_title', 'Combo Details')
@section('meta_description', Str::limit(strip_tags($combo->description), 160))

@section('breadcrumb')
    <li><a href="{{ route('storefront.combos.index') }}">Combo Packages</a></li>
    @if ($combo->categories->first())
        <li><a
                href="{{ route('storefront.combos.index', ['category' => $combo->categories->first()->slug]) }}">{{ $combo->categories->first()->name }}</a>
        </li>
    @endif
@endsection

@php
    $mainImageUrl = upload_url($combo->thumbnail, asset('website/assets/images/product_placeholder.png'));

    $summed = $service->summedPrice($combo);
    $effective = $service->effectivePrice($combo);
    $hasSaving = $summed > $effective;

    $inStock = $service->isInStock($combo);

    $comboCategory = $combo->categories->first();

    $inWishlist = in_array($combo->id, (array) session('wishlist_combos', []), true);
    $inCompare = in_array($combo->id, (array) session('compare_combos', []), true);
@endphp

@section('content')
    <!--============================ COMBO DETAILS START =============================-->
    <section class="shop_details combo_details mt_70">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        {{-- Combo Images --}}
                        <div class="col-lg-6 col-xxl-5 col-md-12">
                            <div class="shop_details_slider_area">
                                @php
                                    $comboGallery = collect([$mainImageUrl])
                                        ->merge(
                                            $combo->galleryImages->map(
                                                fn($g) => upload_url(
                                                    $g->image_path,
                                                    asset('website/assets/images/product_placeholder.png'),
                                                ),
                                            ),
                                        )
                                        ->filter()
                                        ->unique()
                                        ->values();
                                @endphp
                                <div class="row">
                                    @if ($comboGallery->count() > 1)
                                        <div class="col-xxl-2 col-xl-2 col-lg-2 col-md-12 col-sm-10 order-2 order-lg-1">
                                            <div class="details_slider_nav">
                                                <div class="details_slider_nav_track">
                                                    @foreach ($comboGallery as $gUrl)
                                                        <div class="details_slider_nav_item {{ $loop->first ? 'active' : '' }}"
                                                            data-index="{{ $loop->index }}">
                                                            <img src="{{ $gUrl }}" alt="{{ $combo->name }}"
                                                                class="img-fluid w-100" loading="lazy">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xxl-10 col-xl-10 col-lg-10 col-md-12 col-sm-10 order-lg-1">
                                            <div class="swiper details_slider_thumb">
                                                <div class="swiper-wrapper">
                                                    @foreach ($comboGallery as $gUrl)
                                                        <div class="swiper-slide">
                                                            <div class="details_slider_thumb_item">
                                                                <img src="{{ $gUrl }}" alt="{{ $combo->name }}"
                                                                    class="img-fluid w-100">
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="col-12">
                                            <div class="details_slider_thumb_item">
                                                <img src="{{ $mainImageUrl }}" alt="{{ $combo->name }}"
                                                    class="img-fluid w-100">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Combo Info --}}
                        <div class="col-lg-6 col-xxl-7">
                            <div class="shop_details_text">
                                @if ($comboCategory)
                                    <p class="category">{{ $comboCategory->name }}</p>
                                @endif

                                <div class="details_title_row">
                                    <h1 class="details_title">{{ $combo->name }}</h1>
                                    <div class="details_action_btns">
                                        <a href="#"
                                            class="details_wishlist_btn add-to-wishlist {{ $inWishlist ? 'active' : '' }}"
                                            data-combo-id="{{ $combo->id }}" aria-label="Toggle wishlist"
                                            title="{{ $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' }}">
                                            <i class="fas fa-heart"></i>
                                        </a>
                                        <a href="#"
                                            class="details_compare_btn add-to-compare {{ $inCompare ? 'active' : '' }}"
                                            data-combo-id="{{ $combo->id }}" aria-label="Toggle compare"
                                            title="{{ $inCompare ? 'Remove from Compare' : 'Add to Compare' }}">
                                            <i class="fas fa-code-compare"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="product_short_info">
                                    Availability:
                                    @if ($inStock)
                                        <span class="green">In Stock</span>
                                    @else
                                        <span class="out_stock">Out of Stock</span>
                                    @endif
                                </div>

                                <div class="price">
                                    {{ currency_symbol() }} {{ bd_price($effective) }}
                                    @if ($hasSaving)
                                        <del>{{ currency_symbol() }} {{ bd_price($summed) }}</del>
                                    @endif
                                </div>

                                @if ($combo->size_required && !empty($sizeOptions))
                                    {{-- Customer picks one size for the whole combo; each component
                                         resolves to its matching size-variant at add-to-cart. --}}
                                    <div class="details_single_variant combo_size_field">
                                        <p class="variant_title">Size :</p>
                                        <ul class="details_variant_size" id="comboSizeOptions">
                                            @foreach ($sizeOptions as $opt)
                                                <li class="variant_option_btn combo-size-option {{ $opt['in_stock'] ? '' : 'is-disabled' }}"
                                                    data-size="{{ $opt['value'] }}">{{ $opt['value'] }}</li>
                                            @endforeach
                                        </ul>
                                        <input type="hidden" id="comboSizeInput" name="combo_size" value="">
                                        <p class="text-danger fs-12 mt-1 d-none" id="comboSizeError">Please select a
                                            size.</p>
                                    </div>
                                @endif

                                {{-- Quantity & Add to Cart --}}
                                <div class="d-flex flex-wrap align-items-center">
                                    <div class="details_qty_input">
                                        <button type="button" class="minus" id="comboQtyMinus"
                                            aria-label="Decrease quantity"><i class="fas fa-minus"
                                                aria-hidden="true"></i></button>
                                        <input type="number" id="comboQtyInput" min="1" value="1"
                                            aria-label="Quantity">
                                        <button type="button" class="plus" id="comboQtyPlus"
                                            aria-label="Increase quantity"><i class="fas fa-plus"
                                                aria-hidden="true"></i></button>
                                    </div>
                                    <div class="details_btn_area">
                                        @if ($inStock)
                                            <a class="common_btn buy_now" href="#" id="comboBuyNow"
                                                data-combo-id="{{ $combo->id }}">Buy Now <i
                                                    class="fas fa-long-arrow-right"></i></a>
                                            <a class="common_btn" href="#" id="comboAddToCart"
                                                data-combo-id="{{ $combo->id }}">Add to cart <i
                                                    class="fas fa-long-arrow-right"></i></a>
                                        @else
                                            <button type="button" class="common_btn out_of_stock_btn" disabled>
                                                Out of stock
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if (!empty($sizeCharts))
                                    {{-- Inline Size Chart(s) — one per size-like attribute used by the
                                         combo's component products. Mirrors the product detail page. --}}
                                    @foreach ($sizeCharts as $chart)
                                        <div class="bp-sizechart-inline">
                                            <div class="bp-sizechart-title">
                                                {{ $chart['attribute'] }} chart - <span class="bp-sc-unit-label">In
                                                    inches</span> (Expected Deviation &lt; 3%)
                                            </div>
                                            <ul class="bp-sizechart-tabs" role="tablist">
                                                <li><button type="button" class="bp-sc-tab active"
                                                        data-unit="inch">INCH</button></li>
                                                <li><button type="button" class="bp-sc-tab" data-unit="cm">CM</button>
                                                </li>
                                            </ul>
                                            <div class="table-responsive">
                                                <table class="bp-sizechart-table">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ $chart['attribute'] }}</th>
                                                            @foreach ($chart['rows'] as $row)
                                                                <th>{{ $row['label'] }}</th>
                                                            @endforeach
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($chart['sizes'] as $sIdx => $size)
                                                            <tr>
                                                                <td class="bp-sc-size-label">{{ $size['label'] }}</td>
                                                                @foreach ($chart['rows'] as $row)
                                                                    @php $cell = $row['cells'][$sIdx] ?? null; @endphp
                                                                    <td class="bp-sc-cell"
                                                                        data-inch="{{ $cell ?? '' }}">
                                                                        {{ $cell !== null && $cell !== '' ? $cell : '—' }}
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="row mt_60">
                        <div class="col-12">
                            <div class="shop_details_des_area">
                                <div class="shop_details_description">
                                    @if (trim(strip_tags($combo->description ?? '')) !== '')
                                        {!! strip_tags(
                                            $combo->description,
                                            '<p><br><strong><em><ul><ol><li><h2><h3><h4><h5><h6><a><img><table><thead><tbody><tr><td><th><blockquote><span>',
                                        ) !!}
                                    @else
                                        <p>No description available for this combo package.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--============================ COMBO DETAILS END =============================-->

    {{-- Related Products — same design (other colors/styles) first, then
         other combos of that design, then other products in this combo's
         categories. Shown as a plain wrapping grid (no slider). --}}
    @if (isset($relatedItems) && $relatedItems->count())
        <section class="related_products mb_70 mt_60">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="section_heading">
                            <h2>Related Products</h2>
                        </div>
                    </div>
                </div>
                <div class="row">
                    @foreach ($relatedItems as $item)
                        <div class="col-xl-1-5 col-6 col-md-4 col-xl-3 mb-4">
                            @if (($item->catalog_type ?? 'product') === 'combo')
                                @include('ecommerce::storefront.partials.combo-card', ['combo' => $item, 'service' => $service])
                            @else
                                @include('ecommerce::storefront.partials.product-card', ['product' => $item])
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

@push('pageVendorCss')
    <link rel="stylesheet" href="{{ asset('website/assets/vendor/swiper/swiper-bundle.min.css') }}">
@endpush

@push('pageVendorJs')
    <script src="{{ asset('website/assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
@endpush

@push('scripts')
    <script>
        'use strict';
        $(function() {
            // Gallery is the shared Swiper slider (.details_slider_thumb + .details_slider_nav),
            // initialised in custom.js (thumbs module) — same as the product page.

            // ── Combo size selection ──
            var comboSizeRequired = {{ $combo->size_required && !empty($sizeOptions) ? 'true' : 'false' }};

            $('#comboSizeOptions').on('click', '.combo-size-option', function() {
                if ($(this).hasClass('is-disabled')) {
                    return;
                }
                $('#comboSizeOptions .combo-size-option').removeClass('active');
                $(this).addClass('active');
                $('#comboSizeInput').val($(this).data('size'));
                $('#comboSizeError').addClass('d-none');
            });

            // Pre-select a default size on load — the first in-stock option,
            // falling back to the first overall — so Add to Cart/Buy Now work
            // immediately without forcing the shopper to click a size first.
            // Mirrors the product detail page's default-variant selection.
            if (comboSizeRequired) {
                var $defaultSize = $('#comboSizeOptions .combo-size-option:not(.is-disabled)').first();
                if (!$defaultSize.length) {
                    $defaultSize = $('#comboSizeOptions .combo-size-option').first();
                }
                if ($defaultSize.length) {
                    $defaultSize.addClass('active');
                    $('#comboSizeInput').val($defaultSize.data('size'));
                }
            }

            // Returns the chosen size, '' when not required, or null when
            // required-but-missing (and surfaces the inline error).
            function comboChosenSize() {
                if (!comboSizeRequired) {
                    return '';
                }
                var size = $('#comboSizeInput').val();
                if (!size) {
                    $('#comboSizeError').removeClass('d-none');
                    return null;
                }
                return size;
            }

            // Quantity controls
            $('#comboQtyMinus').on('click', function(e) {
                e.preventDefault();
                var input = $('#comboQtyInput');
                var val = parseInt(input.val(), 10) || 1;
                if (val > 1) input.val(val - 1);
            });

            $('#comboQtyPlus').on('click', function(e) {
                e.preventDefault();
                var input = $('#comboQtyInput');
                var val = parseInt(input.val(), 10) || 1;
                if (val < 999) input.val(val + 1);
            });

            $('#comboQtyInput').on('change', function() {
                var val = parseInt($(this).val(), 10) || 1;
                if (val < 1) val = 1;
                if (val > 999) val = 999;
                $(this).val(val);
            });

            // Add combo to cart — POSTs combo_id + quantity. The layout's csrf.js /
            // $.ajaxSetup attaches the CSRF header, so a plain $.post works.
            $('#comboAddToCart').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var comboId = $btn.data('combo-id');
                var qty = parseInt($('#comboQtyInput').val(), 10) || 1;
                var size = comboChosenSize();
                if (size === null) {
                    return;
                }

                $btn.addClass('disabled');
                $.ajax({
                    url: '{{ route('storefront.cart.add-combo') }}',
                    method: 'POST',
                    data: {
                        combo_id: comboId,
                        quantity: qty,
                        combo_size: size,
                    },
                    success: function(res) {
                        if (res && res.success) {
                            if (res.cart_count !== undefined) {
                                $('.cart-count').text(res.cart_count);
                            }
                            if (res.mini_cart_html !== undefined) {
                                $('#offcanvasRightBody').html(res.mini_cart_html);
                            }
                            var oc = document.getElementById('offcanvasRight');
                            if (oc && typeof bootstrap !== 'undefined') {
                                bootstrap.Offcanvas.getOrCreateInstance(oc).show();
                            }
                        } else if (res && res.message) {
                            alert(res.message);
                        }
                    },
                    error: function(xhr) {
                        alert((xhr.responseJSON && xhr.responseJSON.message) ||
                            'Could not add combo to cart.');
                    },
                    complete: function() {
                        $btn.removeClass('disabled');
                    }
                });
            });

            // Buy Now — same add-combo POST, but jump straight to checkout on
            // success (mirrors the product page's Buy Now).
            $('#comboBuyNow').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var comboId = $btn.data('combo-id');
                var qty = parseInt($('#comboQtyInput').val(), 10) || 1;
                var size = comboChosenSize();
                if (size === null) {
                    return;
                }

                $btn.addClass('disabled');
                $.ajax({
                    url: '{{ route('storefront.cart.add-combo') }}',
                    method: 'POST',
                    data: {
                        combo_id: comboId,
                        quantity: qty,
                        combo_size: size,
                    },
                    success: function(res) {
                        if (res && res.success) {
                            window.location.href = '{{ route('storefront.checkout.index') }}';
                        } else {
                            alert((res && res.message) || 'Could not add combo to cart.');
                            $btn.removeClass('disabled');
                        }
                    },
                    error: function(xhr) {
                        alert((xhr.responseJSON && xhr.responseJSON.message) ||
                            'Could not add combo to cart.');
                        $btn.removeClass('disabled');
                    }
                });
            });

            // ── Inline Size Chart: INCH ↔ CM toggle ───────────────────────────
            // Stored values are inches; the CM tab swaps every numeric substring
            // with its centimetre equivalent (× 2.54, one decimal). Non-numeric
            // cells (e.g. "S–M") pass through unchanged. Mirrors the product page.
            $('.bp-sizechart-inline').on('click', '.bp-sc-tab', function() {
                var $wrap = $(this).closest('.bp-sizechart-inline');
                var unit = $(this).data('unit');
                $wrap.find('.bp-sc-tab').removeClass('active');
                $(this).addClass('active');
                $wrap.find('.bp-sc-unit-label').text(unit === 'cm' ? 'In centimeters' : 'In inches');
                $wrap.find('.bp-sc-cell').each(function() {
                    var raw = String($(this).attr('data-inch') || '');
                    if (!raw) {
                        $(this).text('—');
                        return;
                    }
                    if (unit === 'cm') {
                        $(this).text(raw.replace(/\d+(?:\.\d+)?/g, function(m) {
                            var v = parseFloat(m) * 2.54;
                            var s = v.toFixed(1);
                            return s.replace(/\.0$/, '');
                        }));
                    } else {
                        $(this).text(raw);
                    }
                });
            });
        });
    </script>
@endpush
