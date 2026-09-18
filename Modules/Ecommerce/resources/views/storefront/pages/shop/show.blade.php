@extends('ecommerce::storefront.layouts.master')

@section('title', $product->name)
@section('breadcrumb_title', 'Shop Details')
@section('meta_description', Str::limit(strip_tags($product->description), 160))

@section('breadcrumb')
    <li><a href="{{ route('storefront.shop.index') }}">Shop</a></li>
    @if ($product->category)
        <li><a
                href="{{ route('storefront.shop.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a>
        </li>
    @endif
@endsection

@php
    $primaryImage = $product->images->where('is_primary', true)->first();
    $mainImage = $primaryImage->image_path ?? ($product->images->first()->image_path ?? null);
    $mainImageUrl = upload_url($mainImage, asset('website/assets/images/product_placeholder.png'));

    $price = $product->displayPrice();
    $sellPrice = $price->sell;
    $effectivePrice = $price->effective;
    $hasDiscount = $price->has_discount;
    $discountPercentage = $price->discount_percent;
    $campaignBadge = $price->campaign_badge;

    $variants = $product->variants->where('is_active', true);
    $hasVariants = $variants->isNotEmpty();

    // Stock status
    $inStock = $product->is_in_stock;

    // Wishlist + compare state (session-backed)
    $inWishlist = in_array($product->id, (array) session('wishlist', []), true);
    $inCompare = in_array($product->id, (array) session('compare', []), true);
@endphp

@section('content')
    <!--============================ SHOP DETAILS START =============================-->
    <section class="shop_details mt_70">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        {{-- Product Images --}}
                        <div class="col-lg-6 col-xxl-5 col-md-12">
                            <div class="shop_details_slider_area">
                                {{-- Gallery uses Swiper (thumbs + main). Markup is the
                                     Swiper wrapper/slide structure; the .details_slider_*
                                     classes keep the original design. Init in custom.js. --}}
                                <div class="row">
                                    @if ($product->images->count() > 1)
                                        <div class="col-xl-2 col-lg-3 col-md-2 col-sm-12 order-2 order-md-1">
                                            <div class="details_slider_nav">
                                                <div class="details_slider_nav_track">
                                                    @foreach ($product->images as $image)
                                                        <div class="details_slider_nav_item {{ $loop->first ? 'active' : '' }}"
                                                            data-index="{{ $loop->index }}">
                                                            <x-webp :src="$image->image_path" sm
                                                                alt="{{ $image->alt_text ?? $product->name }}"
                                                                class="img-fluid w-100" loading="lazy" />
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-10 col-lg-9 col-md-8 col-sm-10 order-md-1">
                                            <div class="swiper details_slider_thumb">
                                                <div class="swiper-wrapper">
                                                    @foreach ($product->images as $image)
                                                        <div class="swiper-slide">
                                                            <div class="details_slider_thumb_item">
                                                                <x-webp :src="$image->image_path"
                                                                    alt="{{ $image->alt_text ?? $product->name }}"
                                                                    class="img-fluid w-100" :loading="$loop->first ? null : 'lazy'" />
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="col-12">
                                            <div class="details_slider_thumb_item">
                                                <x-webp :src="$mainImage" :default="asset('website/assets/images/product_placeholder.png')" alt="{{ $product->name }}"
                                                    class="img-fluid w-100" id="productMainImg" />
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Product Info --}}
                        <div class="col-lg-6 col-xxl-7">
                            <div class="shop_details_text">
                                @if ($product->category)
                                    <p class="category">{{ $product->category->name }}</p>
                                @endif

                                <div class="details_title_row">
                                    <h1 class="details_title">{{ $product->name }}</h1>
                                    <div class="details_action_btns">
                                        <a href="#"
                                            class="details_wishlist_btn add-to-wishlist {{ $inWishlist ? 'active' : '' }}"
                                            data-product-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                            data-price="{{ (float) $effectivePrice }}"
                                            data-discount="{{ $hasDiscount ? round((float) $sellPrice - (float) $effectivePrice, 2) : 0 }}"
                                            data-brand="{{ optional($product->brand)->name }}"
                                            data-category="{{ optional($product->category)->name }}"
                                            aria-label="Toggle wishlist"
                                            title="{{ $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' }}">
                                            <i class="fas fa-heart"></i>
                                        </a>
                                        <a href="#"
                                            class="details_compare_btn add-to-compare {{ $inCompare ? 'active' : '' }}"
                                            data-product-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                            data-price="{{ (float) $effectivePrice }}"
                                            data-discount="{{ $hasDiscount ? round((float) $sellPrice - (float) $effectivePrice, 2) : 0 }}"
                                            data-brand="{{ optional($product->brand)->name }}"
                                            data-category="{{ optional($product->category)->name }}"
                                            aria-label="Toggle compare"
                                            title="{{ $inCompare ? 'Remove from Compare' : 'Add to Compare' }}">
                                            <i class="fas fa-code-compare"></i>
                                        </a>
                                    </div>
                                </div>

                                @if ($product->brand)
                                    <div class="product_short_info">
                                        Brand: <span>{{ $product->brand->name }}</span>
                                    </div>
                                @endif
                                <div class="product_short_info">
                                    Availability:
                                    @if ($inStock)
                                        <span class="green">In Stock</span>
                                    @else
                                        <span class="out_stock">Out of Stock</span>
                                    @endif
                                </div>
                                @if ($product->model)
                                    <div class="product_short_info">
                                        Model: <span>{{ $product->model }}</span>
                                    </div>
                                @endif

                                <div class="price">
                                    {{ currency_symbol() }} {{ bd_price($effectivePrice) }}
                                    @if ($hasDiscount)
                                        <del>{{ currency_symbol() }} {{ bd_price($sellPrice) }}</del>
                                    @endif
                                </div>

                                @if ($product->description)
                                    <p class="short_description">{{ Str::limit(strip_tags($product->description), 250) }}
                                    </p>
                                @endif

                                {{-- Variant Selection --}}
                                @if ($hasVariants)
                                    @php
                                        $variantAttributes = collect();
                                        foreach ($variants as $variant) {
                                            foreach ($variant->attributeValues as $attrValue) {
                                                $attrName = $attrValue->attribute->base_name;
                                                $attrId = $attrValue->attribute->id;
                                                if (!$variantAttributes->has($attrId)) {
                                                    $variantAttributes[$attrId] = [
                                                        'name' => $attrName,
                                                        'display_type' => $attrValue->attribute->display_type,
                                                        'sort_order' => $attrValue->attribute->sort_order,
                                                        'values' => collect(),
                                                    ];
                                                }
                                                if (
                                                    !$variantAttributes[$attrId]['values']->contains(
                                                        'id',
                                                        $attrValue->id,
                                                    )
                                                ) {
                                                    $variantAttributes[$attrId]['values']->push([
                                                        'id' => $attrValue->id,
                                                        'value' => $attrValue->value,
                                                        'color_code' => $attrValue->color_code,
                                                        'sort_order' => $attrValue->sort_order,
                                                    ]);
                                                }
                                            }
                                        }
                                        // Show attributes in their configured order (e.g. Color before Size)
                                        // rather than the order values happen to appear on the variants.
                                        $variantAttributes = $variantAttributes->sortBy('sort_order');
                                        // Sort the values within each attribute ascending by their configured
                                        // sort_order, falling back to a natural (number-aware) compare of the
                                        // value itself so sizes/numbers read S,M,L,XL / 64,128,256 (Bug_80).
                                        $variantAttributes = $variantAttributes->map(function ($attr) {
                                            $attr['values'] = $attr['values']
                                                ->sort(function ($a, $b) {
                                                    return (int) ($a['sort_order'] ?? 0) <=>
                                                        (int) ($b['sort_order'] ?? 0) ?:
                                                        strnatcasecmp((string) $a['value'], (string) $b['value']);
                                                })
                                                ->values();
                                            return $attr;
                                        });
                                    @endphp

                                    @foreach ($variantAttributes as $attrId => $attr)
                                        <div class="details_single_variant">
                                            <p class="variant_title">{{ $attr['name'] }} :</p>
                                            @if ($attr['display_type'] === 'color_swatch')
                                                <ul class="details_variant_color">
                                                    @foreach ($attr['values'] as $val)
                                                        <li class="variant_option_btn" data-attribute="{{ $attrId }}"
                                                            data-value="{{ $val['id'] }}"
                                                            data-color="{{ $val['color_code'] ?? '#ccc' }}"
                                                            title="{{ $val['value'] }}"
                                                            style="background: {{ $val['color_code'] ?? '#cccccc' }}; border: 1px solid #00000020;">
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <ul class="details_variant_size">
                                                    @foreach ($attr['values'] as $val)
                                                        <li class="variant_option_btn" data-attribute="{{ $attrId }}"
                                                            data-value="{{ $val['id'] }}">{{ $val['value'] }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @endforeach
                                    <input type="hidden" id="selectedVariantId" name="variant_id" value="">
                                @endif

                                {{-- Quantity & Add to Cart --}}
                                <div class="d-flex flex-wrap align-items-center">
                                    <div class="details_qty_input">
                                        <button type="button" class="minus" id="qtyMinus"
                                            aria-label="Decrease quantity"><i class="fas fa-minus"
                                                aria-hidden="true"></i></button>
                                        <input type="text" id="qtyInput" placeholder="01" value="1"
                                            aria-label="Quantity">
                                        <button type="button" class="plus" id="qtyPlus"
                                            aria-label="Increase quantity"><i class="fas fa-plus"
                                                aria-hidden="true"></i></button>
                                    </div>
                                    <div class="details_btn_area">
                                        @if ($inStock)
                                            <a class="common_btn buy_now" href="#" id="buyNowBtn"
                                                data-product-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                                data-price="{{ (float) $effectivePrice }}"
                                                data-discount="{{ $hasDiscount ? round((float) $sellPrice - (float) $effectivePrice, 2) : 0 }}"
                                                data-brand="{{ optional($product->brand)->name }}"
                                                data-category="{{ optional($product->category)->name }}">Buy Now <i
                                                    class="fas fa-long-arrow-right"></i></a>
                                            <a class="common_btn" href="#" id="addToCartBtn"
                                                data-product-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                                data-price="{{ (float) $effectivePrice }}"
                                                data-discount="{{ $hasDiscount ? round((float) $sellPrice - (float) $effectivePrice, 2) : 0 }}"
                                                data-brand="{{ optional($product->brand)->name }}"
                                                data-category="{{ optional($product->category)->name }}">Add to cart <i
                                                    class="fas fa-long-arrow-right"></i></a>
                                        @else
                                            <button type="button" class="common_btn out_of_stock_btn" disabled>
                                                Out of Stock
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if ($hasVariants && !empty($sizeCharts))
                                    {{-- Inline Size Chart(s) — one per size-like attribute (Size, Size (Pant),
                                         Size (Shirt), …). Rendered below the action buttons, not a modal. --}}
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
                                    @if ($product->long_description)
                                        {!! strip_tags(
                                            $product->long_description,
                                            '<p><br><strong><em><ul><ol><li><h2><h3><h4><h5><h6><a><img><table><thead><tbody><tr><td><th><blockquote><span>',
                                        ) !!}
                                    @elseif($product->description)
                                        <p>{{ $product->description }}</p>
                                    @else
                                        <p>No description available for this product.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!--============================ SHOP DETAILS END =============================-->


    {{-- Related Products — same design (other colors/styles) first, then
         combos of that design, then other products in this category. Shown
         as a plain wrapping grid (no slider) so everything is visible. --}}
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
                    @php
                        $comboService = app(\Modules\Ecommerce\Services\ComboService::class);
                    @endphp
                    @foreach ($relatedItems as $item)
                        <div class="col-xl-1-5 col-6 col-md-4 col-xl-3 mb-4">
                            @if (($item->catalog_type ?? 'product') === 'combo')
                                @include('ecommerce::storefront.partials.combo-card', ['combo' => $item, 'service' => $comboService])
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
            // Quantity controls
            $('#qtyMinus').on('click', function(e) {
                e.preventDefault();
                var input = $('#qtyInput');
                var val = parseInt(input.val()) || 1;
                if (val > 1) input.val(val - 1);
            });

            $('#qtyPlus').on('click', function(e) {
                e.preventDefault();
                var input = $('#qtyInput');
                var val = parseInt(input.val()) || 1;
                if (val < 999) input.val(val + 1);
            });

            $('#qtyInput').on('change', function() {
                var val = parseInt($(this).val()) || 1;
                if (val < 1) val = 1;
                if (val > 999) val = 999;
                $(this).val(val);
            });

            // Variant selection
            @if ($hasVariants)
                @php
                    $sfService = app(\Modules\Ecommerce\Services\StorefrontService::class);
                    $variantData = $variants
                        ->map(function ($v) use ($product, $sfService) {
                            return [
                                'id' => $v->id,
                                'sell_price' => (float) $v->sell_price,
                                // Per-variant price after the product discount/campaign/flash deal.
                                'effective_price' => $sfService->calculateEffectivePrice($product, $v),
                                'sku' => $v->sku,
                                'image' => upload_url($v->effective_image),
                                'attribute_values' => $v->attributeValues->pluck('id')->toArray(),
                                // Sellable when the product doesn't track stock, allows
            // backorder, or this variant has stock on hand. Mirrors the
            // add-to-cart guard so out-of-stock options are disabled up front.
            'in_stock' => !(bool) $product->track_stock || (bool) $product->allow_negative_stock || (int) $v->total_stock > 0,
                            ];
                        })
                        ->values();
                @endphp
                var variants = @json($variantData);
                var totalAttrGroups = $('.details_single_variant').length;
                var selectedAttributes = {};

                // Build a map: attribute_value_id → its parent attribute_id (read off the DOM).
                var valueToAttr = {};
                $('.variant_option_btn').each(function() {
                    var aid = parseInt($(this).attr('data-attribute'), 10);
                    var vid = parseInt($(this).attr('data-value'), 10);
                    if (!isNaN(aid) && !isNaN(vid)) valueToAttr[vid] = aid;
                });

                function variantsContaining(values) {
                    return variants.filter(function(v) {
                        var vals = (v.attribute_values || []).map(function(x) {
                            return parseInt(x, 10);
                        });
                        return values.every(function(sv) {
                            return vals.indexOf(sv) !== -1;
                        });
                    });
                }

                // Enable/disable each .variant_option_btn based on the current selection.
                // An option in attribute A is enabled iff there's at least one variant that
                // contains (this option) + (every selected option from OTHER attributes).
                function refreshOptionAvailability() {
                    $('.variant_option_btn').each(function() {
                        var $btn = $(this);
                        var optAttr = parseInt($btn.attr('data-attribute'), 10);
                        var optVal = parseInt($btn.attr('data-value'), 10);

                        // Build the constraint set: own value + every selection that is NOT
                        // for this option's attribute group.
                        var constraints = [optVal];
                        Object.keys(selectedAttributes).forEach(function(k) {
                            if (parseInt(k, 10) !== optAttr) {
                                constraints.push(selectedAttributes[k]);
                            }
                        });

                        // Reachable only if at least one matching variant is in stock —
                        // greys out e.g. a size that's sold out (for the chosen colour).
                        var reachable = variantsContaining(constraints).some(function(v) {
                            return v.in_stock;
                        });
                        $btn.toggleClass('is-disabled', !reachable);
                    });
                }

                refreshOptionAvailability();

                // Match the currently selected attribute combination to a variant
                // (all attribute groups must have a selection that resolves to it).
                function resolveMatchedVariant() {
                    var selectedVals = Object.keys(selectedAttributes).map(function(k) {
                        return selectedAttributes[k];
                    });
                    for (var i = 0; i < variants.length; i++) {
                        var attrVals = (variants[i].attribute_values || []).map(function(x) {
                            return parseInt(x, 10);
                        });
                        var allInVariant = selectedVals.every(function(sv) {
                            return attrVals.indexOf(sv) !== -1;
                        });
                        if (allInVariant && selectedVals.length === attrVals.length) {
                            return variants[i];
                        }
                    }
                    return null;
                }

                // Reflect a resolved variant (or the lack of one) in the price,
                // hidden input, and the action buttons' tracking data. Shared by
                // the default pre-selection and every manual option click.
                function applyMatchedVariant(matchedVariant) {
                    if (matchedVariant) {
                        $('#selectedVariantId').val(matchedVariant.id);
                        // Show the variant's discounted price, striking through the
                        // original when the product discount actually lowered it.
                        var sell = parseFloat(matchedVariant.sell_price) || 0;
                        var eff = (matchedVariant.effective_price != null) ?
                            parseFloat(matchedVariant.effective_price) :
                            sell;
                        if (sell) {
                            var priceHtml = window.bdMoney(eff);
                            if (eff < sell) {
                                priceHtml += ' <del>' + window.bdMoney(sell) + '</del>';
                            }
                            $('.price').first().html(priceHtml);
                        }
                        // Keep the action buttons' tracking data in sync with the
                        // selected variant (read by cart.js bpItemFromEl).
                        $('#addToCartBtn, #buyNowBtn')
                            .data('price', eff)
                            .data('variant', matchedVariant.sku || '')
                            .data('discount', sell > eff ? Math.round((sell - eff) * 100) / 100 : 0);
                    } else {
                        $('#selectedVariantId').val('');
                        $('#addToCartBtn, #buyNowBtn').each(function() {
                            $(this)
                                .data('price', Number(this.getAttribute('data-price')) || 0)
                                .data('variant', '')
                                .data('discount', Number(this.getAttribute('data-discount')) || 0);
                        });
                    }
                }

                // Pre-select a default variant on load — the lowest-sort-order
                // one that's actually sellable, falling back to the first — so
                // Add to Cart / Buy Now work immediately without forcing the
                // shopper to click every attribute first.
                (function selectDefaultVariant() {
                    var defaultVariant = variants.filter(function(v) { return v.in_stock; })[0] || variants[0];
                    if (!defaultVariant) return;

                    (defaultVariant.attribute_values || []).forEach(function(valId) {
                        var attrId = valueToAttr[valId];
                        if (attrId === undefined) return;
                        selectedAttributes[attrId] = valId;
                        $('.variant_option_btn[data-attribute="' + attrId + '"][data-value="' + valId + '"]')
                            .addClass('active');
                    });

                    applyMatchedVariant(resolveMatchedVariant());
                    refreshOptionAvailability();
                })();

                $(document).on('click', '.variant_option_btn', function() {
                    var $btn = $(this);
                    if ($btn.hasClass('is-disabled')) {
                        return; // Hard block — cannot select an unreachable combination.
                    }
                    var attrId = parseInt($btn.attr('data-attribute'), 10);
                    var valId = parseInt($btn.attr('data-value'), 10);
                    if (isNaN(attrId) || isNaN(valId)) return;

                    $btn.closest('ul').find('.variant_option_btn').removeClass('active');
                    $btn.addClass('active');
                    selectedAttributes[attrId] = valId;

                    var matchedVariant = resolveMatchedVariant();
                    applyMatchedVariant(matchedVariant);

                    // Recompute which options are still reachable given the new selection.
                    refreshOptionAvailability();

                    if (window.console && console.debug) {
                        console.debug('[variant]', {
                            clicked: {
                                attrId: attrId,
                                valId: valId
                            },
                            selectedAttributes: selectedAttributes,
                            matchedVariantId: matchedVariant ? matchedVariant.id : null
                        });
                    }
                });
            @endif

            // Add to Cart
            $('#addToCartBtn').on('click', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                var qty = parseInt($('#qtyInput').val()) || 1;
                var variantId = $('#selectedVariantId').length ? $('#selectedVariantId').val() : null;

                @if ($hasVariants)
                    if (!variantId) {
                        // Tell the user which group(s) they still need to pick.
                        var missing = [];
                        $('.details_single_variant').each(function() {
                            if ($(this).find('.variant_option_btn.active').length === 0) {
                                var label = ($(this).find('.variant_title').text() || '').replace(
                                    /:\s*$/, '').trim();
                                if (label) missing.push(label);
                            }
                        });
                        var msg = missing.length ?
                            'Please select: ' + missing.join(', ') :
                            'Selected combination is not available. Try a different option.';
                        if (typeof Toast !== 'undefined') {
                            Toast.show(msg, 'error');
                        } else {
                            alert(msg);
                        }
                        return;
                    }
                @endif

                if (typeof window.EcommerceCart !== 'undefined') {
                    window.EcommerceCart.add(productId, qty, variantId);
                } else {
                    var btn = $(this);
                    btn.addClass('disabled');
                    $.ajax({
                        url: '{{ route('storefront.cart.add') }}',
                        method: 'POST',
                        data: {
                            product_id: productId,
                            variant_id: variantId,
                            quantity: qty,
                        },
                        success: function(res) {
                            if (res.success) {
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
                            }
                        },
                        error: function(xhr) {
                            alert(xhr.responseJSON?.message || 'Could not add to cart.');
                        },
                        complete: function() {
                            btn.removeClass('disabled');
                        }
                    });
                }
            });

            // Buy Now
            $('#buyNowBtn').on('click', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                var qty = parseInt($('#qtyInput').val()) || 1;
                var variantId = $('#selectedVariantId').length ? $('#selectedVariantId').val() : null;

                @if ($hasVariants)
                    if (!variantId) {
                        // Tell the user which group(s) they still need to pick.
                        var missing = [];
                        $('.details_single_variant').each(function() {
                            if ($(this).find('.variant_option_btn.active').length === 0) {
                                var label = ($(this).find('.variant_title').text() || '').replace(
                                    /:\s*$/, '').trim();
                                if (label) missing.push(label);
                            }
                        });
                        var msg = missing.length ?
                            'Please select: ' + missing.join(', ') :
                            'Selected combination is not available. Try a different option.';
                        if (typeof Toast !== 'undefined') {
                            Toast.show(msg, 'error');
                        } else {
                            alert(msg);
                        }
                        return;
                    }
                @endif

                var btn = $(this);
                btn.addClass('disabled');

                $.ajax({
                    url: '{{ route('storefront.cart.add') }}',
                    method: 'POST',
                    data: {
                        product_id: productId,
                        variant_id: variantId,
                        quantity: qty,
                    },
                    success: function() {
                        window.location.href = '{{ route('storefront.checkout.index') }}';
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.message || 'Could not add to cart.');
                        btn.removeClass('disabled');
                    }
                });
            });

            // Hover-to-zoom on the main gallery image is initialised globally in
            // custom.js (bpInitDetailZoom) — shared with the combo detail page.

            // ── Inline Size Chart: INCH ↔ CM toggle ───────────────────────────
            // Stored values are treated as inches; CM tab swaps every numeric
            // substring with its centimetre equivalent (× 2.54), one decimal.
            // Non-numeric cells (e.g. "S–M") pass through unchanged.
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

    @php
        $bpViewItem = [
            'item_id' => (string) $product->id,
            'item_name' => $product->name,
            'price' => (float) $effectivePrice,
            'quantity' => 1,
            'item_brand' => optional($product->brand)->name,
            'item_category' => optional($product->category)->name,
        ];
        if ($hasDiscount) {
            $bpViewItem['discount'] = round((float) $sellPrice - (float) $effectivePrice, 2);
        }
    @endphp
    @include('ecommerce::storefront.partials.track-event', [
        'event' => 'ViewContent',
        'ga' => ['currency' => 'BDT', 'value' => (float) $effectivePrice, 'items' => [$bpViewItem]],
        'fb' => [
            'data' => [
                'content_type' => 'product',
                'content_ids' => [(string) $product->id],
                'contents' => [
                    ['id' => (string) $product->id, 'quantity' => 1, 'item_price' => (float) $effectivePrice],
                ],
                'content_name' => $product->name,
                'value' => (float) $effectivePrice,
                'currency' => 'BDT',
            ],
        ],
    ])
@endpush
