@extends('ecommerce::storefront.layouts.master')

@section('title', 'Compare Products')

@section('breadcrumb_title', 'Compare')
@section('breadcrumb')
    <li>Compare</li>
@endsection

@section('content')
    <!--============================  COMPARE PAGE START ============================-->
    <section class="compare_page mt_70 mb_70">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    @if ($products->count() > 0 || (isset($combos) && $combos->count() > 0))
                        <div class="compare_list_area wow fadeInUp">
                            <div class="table-responsive">
                                <table class="table">
                                    <tbody>
                                        {{-- Product image + name --}}
                                        <tr>
                                            <td>
                                                <p><b>Product</b></p>
                                            </td>
                                            @foreach ($products as $product)
                                                @php
                                                    $productImage = $product->thumbnail;
                                                    if (
                                                        !$productImage &&
                                                        $product->relationLoaded('images') &&
                                                        $product->images->count()
                                                    ) {
                                                        $primaryImage = $product->images
                                                            ->where('is_primary', true)
                                                            ->first();
                                                        $productImage = $primaryImage
                                                            ? $primaryImage->image_path
                                                            : $product->images->first()->image_path;
                                                    }
                                                    $imageUrl = upload_url(
                                                        $productImage,
                                                        asset('website/assets/images/product_placeholder.png'),
                                                    );
                                                @endphp
                                                <td data-product-id="{{ $product->id }}">
                                                    <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                                                        class="img-fluid w-100">
                                                    <a class="title"
                                                        href="{{ route('storefront.shop.show', $product->slug) }}">{{ $product->name }}</a>
                                                </td>
                                            @endforeach
                                            @foreach ($combos ?? [] as $combo)
                                                @php
                                                    $cImageUrl = upload_url(
                                                        $combo->thumbnail,
                                                        asset('website/assets/images/product_placeholder.png'),
                                                    );
                                                @endphp
                                                <td data-combo-id="{{ $combo->id }}">
                                                    <img src="{{ $cImageUrl }}" alt="{{ $combo->name }}"
                                                        class="img-fluid w-100">
                                                    <a class="title"
                                                        href="{{ route('storefront.combos.show', $combo->slug) }}">{{ $combo->name }}</a>
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Description --}}
                                        <tr>
                                            <td>
                                                <p><b>Description</b></p>
                                            </td>
                                            @foreach ($products as $product)
                                                <td data-product-id="{{ $product->id }}">
                                                    <p>{{ $product->description ? Str::limit(strip_tags($product->description), 120) : '—' }}
                                                    </p>
                                                </td>
                                            @endforeach
                                            @foreach ($combos ?? [] as $combo)
                                                <td data-combo-id="{{ $combo->id }}">
                                                    <p>{{ $combo->description ? Str::limit(strip_tags($combo->description), 120) : '—' }}
                                                    </p>
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Price --}}
                                        <tr>
                                            <td>
                                                <p><b>Price</b></p>
                                            </td>
                                            @foreach ($products as $product)
                                                @php $cp = $product->displayPrice(); @endphp
                                                <td data-product-id="{{ $product->id }}">
                                                    <p>{{ currency_symbol() }} {{ bd_price($cp->effective) }}
                                                        @if ($cp->has_discount)
                                                            <del>{{ currency_symbol() }} {{ bd_price($cp->sell) }}</del>
                                                        @endif
                                                    </p>
                                                </td>
                                            @endforeach
                                            @foreach ($combos ?? [] as $combo)
                                                @php
                                                    $ccSummed = $comboService->summedPrice($combo);
                                                    $ccEffective = $comboService->effectivePrice($combo);
                                                @endphp
                                                <td data-combo-id="{{ $combo->id }}">
                                                    <p>{{ currency_symbol() }} {{ bd_price($ccEffective) }}
                                                        @if ($ccSummed > $ccEffective)
                                                            <del>{{ currency_symbol() }} {{ bd_price($ccSummed) }}</del>
                                                        @endif
                                                    </p>
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Availability --}}
                                        <tr>
                                            <td>
                                                <p><b>Availability</b></p>
                                            </td>
                                            @foreach ($products as $product)
                                                @php $inStock = $product->is_in_stock; @endphp
                                                <td data-product-id="{{ $product->id }}">
                                                    <p>{{ $inStock ? 'In Stock' : 'Out of Stock' }}</p>
                                                </td>
                                            @endforeach
                                            @foreach ($combos ?? [] as $combo)
                                                <td data-combo-id="{{ $combo->id }}">
                                                    <p>{{ $comboService->isInStock($combo) ? 'In Stock' : 'Out of Stock' }}</p>
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Category --}}
                                        <tr>
                                            <td>
                                                <p><b>Category</b></p>
                                            </td>
                                            @foreach ($products as $product)
                                                <td data-product-id="{{ $product->id }}">
                                                    <p>{{ $product->category->name ?? '—' }}</p>
                                                </td>
                                            @endforeach
                                            @foreach ($combos ?? [] as $combo)
                                                <td data-combo-id="{{ $combo->id }}">
                                                    <p>{{ $combo->categories->first()->name ?? __('Combo Package') }}</p>
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Brand --}}
                                        <tr>
                                            <td>
                                                <p><b>Brand</b></p>
                                            </td>
                                            @foreach ($products as $product)
                                                <td data-product-id="{{ $product->id }}">
                                                    <p>{{ $product->brand->name ?? '—' }}</p>
                                                </td>
                                            @endforeach
                                            @foreach ($combos ?? [] as $combo)
                                                <td data-combo-id="{{ $combo->id }}">
                                                    <p>—</p>
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Rating --}}
                                        <tr>
                                            <td>
                                                <p><b>Rating</b></p>
                                            </td>
                                            @foreach ($products as $product)
                                                @php
                                                    $avg = $product->approvedReviews->avg('rating');
                                                    $avg = $avg ? round($avg * 2) / 2 : 0; // nearest half
                                                @endphp
                                                <td data-product-id="{{ $product->id }}">
                                                    <p class="rating">
                                                        @if ($avg > 0)
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($avg >= $i)
                                                                    <i class="fas fa-star"></i>
                                                                @elseif($avg >= $i - 0.5)
                                                                    <i class="fas fa-star-half-alt"></i>
                                                                @else
                                                                    <i class="fas fa-star"></i>
                                                                @endif
                                                            @endfor
                                                        @else
                                                            <span class="text-muted">No ratings</span>
                                                        @endif
                                                    </p>
                                                </td>
                                            @endforeach
                                            @foreach ($combos ?? [] as $combo)
                                                <td data-combo-id="{{ $combo->id }}">
                                                    <p class="rating"><span class="text-muted">—</span></p>
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Action --}}
                                        <tr>
                                            <td>
                                                <p><b>Action</b></p>
                                            </td>
                                            @foreach ($products as $product)
                                                <td data-product-id="{{ $product->id }}">
                                                    <a href="#" class="common_btn add-to-cart"
                                                        data-product-id="{{ $product->id }}">add to cart</a>
                                                    <a href="#" class="common_btn remove remove-from-compare"
                                                        data-product-id="{{ $product->id }}" title="Remove"><i
                                                            class="fas fa-trash"></i></a>
                                                </td>
                                            @endforeach
                                            @foreach ($combos ?? [] as $combo)
                                                <td data-combo-id="{{ $combo->id }}">
                                                    @if ($comboService->isInStock($combo))
                                                        <a href="#" class="common_btn combo-add-cart-trigger"
                                                            data-combo-id="{{ $combo->id }}">add to cart</a>
                                                    @endif
                                                    <a href="#" class="common_btn remove remove-from-compare"
                                                        data-combo-id="{{ $combo->id }}" title="Remove"><i
                                                            class="fas fa-trash"></i></a>
                                                </td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="empty_compare text-center">
                            <i class="fas fa-code-compare fa-2x text-muted mb-3 d-block"></i>
                            <h3>Your compare list is empty</h3>
                            <p class="text-muted">Add products to compare their features side by side.</p>
                            <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">
                                Browse Products <i class="fas fa-long-arrow-right"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <!--============================ COMPARE PAGE END =============================-->
@endsection

@push('scripts')
    <script>
        'use strict';
        $(document).on('click', '.remove-from-compare', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var comboId = $btn.data('combo-id');
            var productId = $btn.data('product-id');

            $.ajax({
                url: '{{ route('storefront.compare.remove') }}',
                method: 'POST',
                data: comboId ? {
                    combo_id: comboId,
                    _token: '{{ csrf_token() }}'
                } : {
                    product_id: productId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (!data.success) {
                        return;
                    }
                    $('.compare-count').text(data.compare_count);
                    // Drop this item's column from every row.
                    if (comboId) {
                        $('.compare_list_area td[data-combo-id="' + comboId + '"]').remove();
                    } else {
                        $('.compare_list_area td[data-product-id="' + productId + '"]').remove();
                    }
                    // Empty list → reload to show the empty state.
                    if ($('.compare_list_area td[data-product-id], .compare_list_area td[data-combo-id]')
                        .length === 0) {
                        location.reload();
                    }
                }
            });
        });
    </script>
@endpush
