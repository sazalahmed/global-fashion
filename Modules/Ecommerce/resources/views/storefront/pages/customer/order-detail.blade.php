@extends('ecommerce::storefront.layouts.master')

@section('title', 'Order ' . $order->order_number)
@section('breadcrumb_title', 'Order Details')

@section('breadcrumb')
    <li><a href="{{ route('storefront.customer.profile') }}">Dashboard</a></li>
    <li><a href="{{ route('storefront.customer.orders') }}">Orders</a></li>
    <li>{{ $order->order_number }}</li>
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
                        <div class="order_details_top">
                            {{-- <h4> Order {{ $order->order_number }}</h4> --}}
                            @php
                                $statusClass = match ($order->status) {
                                    'delivered' => 'complete',
                                    'cancelled', 'refunded' => 'cancel',
                                    default => 'active',
                                };
                            @endphp
                            <span class="{{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                        </div>

                        {{-- Order Info --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="dashboard_profile_info">
                                    <h4>Order Information</h4>
                                    <ul>
                                        <li><span>Order ID:</span> {{ $order->order_number }}</li>
                                        <li><span>Order Date:</span> {{ $order->created_at->format('d M Y, h:i A') }}</li>
                                        <li><span>Payment:</span> {{ strtoupper($order->payment_method) }} —
                                            {{ ucfirst($order->payment_status) }}</li>
                                        @if ($order->tracking_number)
                                            <li><span>Tracking:</span> {{ $order->tracking_number }}</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="dashboard_profile_info">
                                    <h4>Shipping Address</h4>
                                    <ul>
                                        <li><span>Name:</span> {{ $order->customer_name }}</li>
                                        <li><span>Phone:</span> {{ $order->customer_phone }}</li>
                                        <li><span>Address:</span> {{ $order->shipping_address }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Order Items --}}
                        <div class="dashboard_order_table mt-4">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Variant</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->items as $item)
                                            <tr>
                                                <td>{{ $item->product_name }}</td>
                                                <td>{{ $item->variant_name ?? '-' }}</td>
                                                <td>৳{{ number_format($item->unit_price, 0) }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>৳{{ number_format($item->subtotal, 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Subtotal</strong></td>
                                            <td>৳{{ number_format($order->subtotal, 0) }}</td>
                                        </tr>
                                        @if ($order->discount_amount > 0)
                                            <tr>
                                                <td colspan="4" class="text-end"><strong>Discount</strong></td>
                                                <td class="text-success">(-)
                                                    ৳{{ number_format($order->discount_amount, 0) }}</td>
                                            </tr>
                                        @endif
                                        @if ($order->shipping_charge > 0)
                                            <tr>
                                                <td colspan="4" class="text-end"><strong>Shipping</strong></td>
                                                <td>(+) ৳{{ number_format($order->shipping_charge, 0) }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Grand Total</strong></td>
                                            <td><strong>৳{{ number_format($order->grand_total, 0) }}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        @if ($order->notes)
                            <div class="mt-3">
                                <strong>Notes:</strong> {{ $order->notes }}
                            </div>
                        @endif

                        <div class="mt-4">
                            <a href="{{ route('storefront.customer.orders') }}" class="common_btn">
                                Back to Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
