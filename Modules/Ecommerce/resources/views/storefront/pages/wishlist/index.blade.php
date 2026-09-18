@extends('ecommerce::storefront.layouts.master')

@section('title', 'Wishlist')

@section('breadcrumb_title', 'Wishlist')
@section('breadcrumb')
    <li>Wishlist</li>
@endsection

@section('content')
    <!--============================ WISHLIST START ==============================-->
    <section class="wishlist_page pt_70 pb_70">
        <div class="container">
            @if ($products->count() > 0 || (isset($combos) && $combos->count() > 0))
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-12 col-xl-10 col-xxl-9 wow fadeInUp">
                        <div class="cart_table_area">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="cart_page_img">Image</th>
                                            <th class="cart_page_details">Details</th>
                                            <th class="cart_page_price">Price</th>
                                            <th class="cart_page_action">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($products as $product)
                                            @php
                                                $wImg = $product->thumbnail;
                                                if (
                                                    !$wImg &&
                                                    $product->relationLoaded('images') &&
                                                    $product->images->count()
                                                ) {
                                                    $wPrimary = $product->images->where('is_primary', true)->first();
                                                    $wImg = $wPrimary
                                                        ? $wPrimary->image_path
                                                        : $product->images->first()->image_path;
                                                }
                                                $wImg = upload_url(
                                                    $wImg,
                                                    asset('website/assets/images/product_placeholder.png'),
                                                );
                                                $wPrice = $product->displayPrice();
                                                $wInStock = $product->is_in_stock;
                                            @endphp
                                            <tr class="wishlist-item" data-product-id="{{ $product->id }}">
                                                <td class="cart_page_img">
                                                    <div class="img">
                                                        <a href="{{ route('storefront.shop.show', $product->slug) }}">
                                                            <img src="{{ $wImg }}" alt="{{ $product->name }}"
                                                                class="img-fluid w-100">
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="cart_page_details">
                                                    <a class="title"
                                                        href="{{ route('storefront.shop.show', $product->slug) }}">{{ $product->name }}</a>
                                                    <p>{{ currency_symbol() }} {{ bd_price($wPrice->effective) }}
                                                        @if ($wPrice->has_discount)
                                                            <del>{{ currency_symbol() }} {{ bd_price($wPrice->sell) }}</del>
                                                        @endif
                                                    </p>
                                                    <span>{{ $product->category->name ?? '' }}</span>
                                                </td>
                                                <td class="cart_page_price">
                                                    <h3>{{ currency_symbol() }} {{ bd_price($wPrice->effective) }}</h3>
                                                </td>
                                                <td class="cart_page_action">
                                                    @if ($wInStock)
                                                        <a href="#" class="common_btn add-to-cart"
                                                            data-product-id="{{ $product->id }}">Add To Cart</a>
                                                    @else
                                                        <a href="#" class="common_btn disabled" tabindex="-1"
                                                            aria-disabled="true">Out Of Stock</a>
                                                    @endif
                                                    <a href="#" class="common_btn remove remove-from-wishlist"
                                                        data-product-id="{{ $product->id }}" title="Remove"><i
                                                            class="fas fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        @endforeach

                                        {{-- Combo packages saved to the wishlist --}}
                                        @foreach ($combos ?? [] as $combo)
                                            @php
                                                $cImg = upload_url(
                                                    $combo->thumbnail,
                                                    asset('website/assets/images/product_placeholder.png'),
                                                );
                                                $cSummed = $comboService->summedPrice($combo);
                                                $cEffective = $comboService->effectivePrice($combo);
                                                $cHasSaving = $cSummed > $cEffective;
                                                $cInStock = $comboService->isInStock($combo);
                                            @endphp
                                            <tr class="wishlist-item" data-combo-id="{{ $combo->id }}">
                                                <td class="cart_page_img">
                                                    <div class="img">
                                                        <a href="{{ route('storefront.combos.show', $combo->slug) }}">
                                                            <img src="{{ $cImg }}" alt="{{ $combo->name }}"
                                                                class="img-fluid w-100">
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="cart_page_details">
                                                    <a class="title"
                                                        href="{{ route('storefront.combos.show', $combo->slug) }}">{{ $combo->name }}</a>
                                                    <p>{{ currency_symbol() }} {{ bd_price($cEffective) }}
                                                        @if ($cHasSaving)
                                                            <del>{{ currency_symbol() }} {{ bd_price($cSummed) }}</del>
                                                        @endif
                                                    </p>
                                                    <span>{{ __('Combo Package') }}</span>
                                                </td>
                                                <td class="cart_page_price">
                                                    <h3>{{ currency_symbol() }} {{ bd_price($cEffective) }}</h3>
                                                </td>
                                                <td class="cart_page_action">
                                                    @if ($cInStock)
                                                        <a href="#" class="common_btn combo-add-cart-trigger"
                                                            data-combo-id="{{ $combo->id }}">Add To Cart</a>
                                                    @else
                                                        <a href="#" class="common_btn disabled" tabindex="-1"
                                                            aria-disabled="true">Out Of Stock</a>
                                                    @endif
                                                    <a href="#" class="common_btn remove remove-from-wishlist"
                                                        data-combo-id="{{ $combo->id }}" title="Remove"><i
                                                            class="fas fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="empty_wishlist text-center">
                            <i class="fas fa-heart fa-2x text-muted mb-3 d-block"></i>
                            <h3>Your wishlist is empty</h3>
                            <p class="text-muted">Browse our products and add your favorites here.</p>
                            <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">
                                Browse Products <i class="fas fa-long-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!--============================ WISHLIST END ==============================-->
@endsection

@push('scripts')
    <script>
        'use strict';
        $(document).on('click', '.remove-from-wishlist', function() {
            var $btn = $(this);
            var comboId = $btn.data('combo-id');
            var productId = $btn.data('product-id');

            $.ajax({
                url: '{{ route('storefront.wishlist.remove') }}',
                method: 'POST',
                data: comboId ? {
                    combo_id: comboId,
                    _token: '{{ csrf_token() }}'
                } : {
                    product_id: productId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.success) {
                        $btn.closest('.wishlist-item').fadeOut(300, function() {
                            $(this).remove();
                            // Update wishlist count
                            $('.wishlist-count').text(data.wishlist_count);
                            // If no more items, reload
                            if ($('.wishlist-item:visible').length === 0) {
                                location.reload();
                            }
                        });
                    }
                }
            });
        });
    </script>
@endpush
