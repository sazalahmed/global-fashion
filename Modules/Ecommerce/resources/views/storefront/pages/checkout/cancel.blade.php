@extends('ecommerce::storefront.layouts.master')

@section('title', 'Order Cancelled')
@section('breadcrumb_title', 'Order Cancelled')

@section('breadcrumb')
    <li>Order Cancelled</li>
@endsection

@section('content')
    <section class="checkout_result pt_60 pb_60">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="result_card text-center">
                        <div class="result_icon text-danger mb-3">
                            <i class="fas fa-times-circle fa-5x"></i>
                        </div>

                        <h2>Order Cancelled</h2>
                        <p class="text-muted">Your order has been cancelled. If this was a mistake, you can try placing your order again.</p>

                        @if(session('cancel_reason'))
                            <div class="alert alert-warning mt-3">
                                <strong>Reason:</strong> {{ session('cancel_reason') }}
                            </div>
                        @endif

                        <div class="mt-4 d-flex justify-content-center gap-3 flex-wrap">
                            <a href="{{ route('storefront.checkout.index') }}" class="common_btn">
                                <i class="fas fa-redo me-2"></i> Try Again
                            </a>
                            <a href="{{ route('storefront.cart.index') }}" class="common_btn_2">
                                <i class="fas fa-shopping-cart me-2"></i> View Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
