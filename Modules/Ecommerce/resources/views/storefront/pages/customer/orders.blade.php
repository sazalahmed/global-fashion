@extends('ecommerce::storefront.layouts.master')

@section('title', 'My Orders')
@section('breadcrumb_title', 'My Orders')

@section('breadcrumb')
    <li><a href="{{ route('storefront.customer.profile') }}">Dashboard</a></li>
    <li>Orders</li>
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
                        <h3 class="dashboard_title">Order History</h3>
                        <div class="dashboard_order_table">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                            <tr>
                                                <td>{{ $order->order_number }}</td>
                                                <td>{{ $order->created_at->format('d M Y') }}</td>
                                                <td>
                                                    @php
                                                        $statusClass = match ($order->status) {
                                                            'delivered' => 'complete',
                                                            'cancelled', 'refunded' => 'cancel',
                                                            default => 'active',
                                                        };
                                                    @endphp
                                                    <span class="{{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="{{ $order->payment_status === 'paid' ? 'complete' : 'active' }}">{{ ucfirst($order->payment_status) }}</span>
                                                </td>
                                                <td>৳{{ number_format($order->grand_total, 0) }}</td>
                                                <td>
                                                    <a
                                                        href="{{ route('storefront.customer.order.detail', $order->order_number) }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                            class="size-6">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                        </svg>
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">No orders yet. <a
                                                        href="{{ route('storefront.shop.index') }}">Start shopping!</a></td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($orders->hasPages())
                            <div class="pagination_area mt-3">
                                {{ $orders->links('ecommerce::storefront.partials.pagination') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
