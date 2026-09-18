@extends('ecommerce::storefront.layouts.master')

@section('title', 'My Wishlist')
@section('breadcrumb_title', 'Wishlist')

@section('breadcrumb')
    <li><a href="{{ route('storefront.customer.profile') }}">Dashboard</a></li>
    <li>Wishlist</li>
@endsection

@section('content')
    <section class="dashboard mb_70">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 wow fadeInUp">
                    @include('ecommerce::storefront.partials.dashboard-sidebar')
                </div>
                <div class="col-lg-9">
                    <div class="dashboard_content mt_70">
                        <h3 class="dashboard_title">My Wishlist</h3>

                        @if ($products->count() > 0)
                            <div class="row dashboard_wishlist_grid">
                                @foreach ($products as $product)
                                    <div class="col-xl-4 col-md-6 col-6 mb-4 wishlist-item"
                                        data-product-id="{{ $product->id }}">
                                        <div class="position-relative">
                                            @include('ecommerce::storefront.partials.product-card', [
                                                'product' => $product,
                                            ])
                                            <button type="button"
                                                class="dashboard_wishlist_remove remove-from-wishlist"
                                                data-product-id="{{ $product->id }}" title="Remove from Wishlist">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty_wishlist text-center py-5">
                                <i class="fas fa-heart fa-4x text-muted mb-3 d-block"></i>
                                <h4>Your wishlist is empty</h4>
                                <p class="text-muted">Browse our products and add your favorites here.</p>
                                <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">
                                    Browse Products <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        'use strict';
        $(document).on('click', '.dashboard_wishlist_grid .remove-from-wishlist', function (e) {
            e.preventDefault();
            var $btn = $(this);
            $.ajax({
                url: '{{ route('storefront.wishlist.remove') }}',
                method: 'POST',
                data: { product_id: $btn.data('product-id'), _token: '{{ csrf_token() }}' },
                success: function (data) {
                    if (data.success) {
                        $('.wishlist-count').text(data.wishlist_count);
                        $btn.closest('.wishlist-item').fadeOut(250, function () {
                            $(this).remove();
                            if ($('.dashboard_wishlist_grid .wishlist-item:visible').length === 0) {
                                location.reload();
                            }
                        });
                    }
                }
            });
        });
    </script>
@endpush
