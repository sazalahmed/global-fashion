@extends('ecommerce::storefront.layouts.master')

@section('title', 'My Dashboard')
@section('breadcrumb_title', 'Dashboard')

@section('breadcrumb')
    <li>Dashboard</li>
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
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        {{-- Overview Stats --}}
                        <div class="row">
                            <div class="col-xl-4 col-md-6 wow fadeInUp">
                                <div class="dashboard_overview_item">
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                        </svg>
                                    </div>
                                    <h3>{{ $stats['total'] }} <span>Total Order</span></h3>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6 wow fadeInUp">
                                <div class="dashboard_overview_item blue">
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                        </svg>
                                    </div>
                                    <h3>{{ $stats['delivered'] }} <span>Completed Order</span></h3>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6 wow fadeInUp">
                                <div class="dashboard_overview_item orange">
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </div>
                                    <h3>{{ $stats['pending'] }} <span>Pending Order</span></h3>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6 wow fadeInUp">
                                <div class="dashboard_overview_item red">
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <h3>{{ $stats['cancelled'] }} <span>Cancelled Order</span></h3>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6 wow fadeInUp">
                                <div class="dashboard_overview_item purple">
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                        </svg>
                                    </div>
                                    <h3>{{ count(session('wishlist', [])) }} <span>Total Wishlist</span></h3>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6 wow fadeInUp">
                                <div class="dashboard_overview_item olive">
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                        </svg>
                                    </div>
                                    <h3>৳{{ number_format($stats['total_spent'], 0) }} <span>Total Spent</span></h3>
                                </div>
                            </div>
                        </div>

                        {{-- Recent Orders --}}
                        <div class="row mt_25">
                            <div class="col-12 wow fadeInUp">
                                <div class="dashboard_recent_order">
                                    <h3>Your Recent Orders</h3>
                                    <div class="dashboard_order_table">
                                        <div class="table-responsive">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Order ID</th>
                                                        <th>Date</th>
                                                        <th>Status</th>
                                                        <th>Amount</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($recentOrders as $order)
                                                        <tr>
                                                            <td>{{ $order->order_number }}</td>
                                                            <td>{{ $order->created_at->format('d M Y') }}</td>
                                                            <td>
                                                                @php
                                                                    $statusClass = match ($order->status) {
                                                                        'delivered' => 'complete',
                                                                        'cancelled', 'refunded' => 'cancel',
                                                                        'pending' => 'active',
                                                                        default => 'active',
                                                                    };
                                                                @endphp
                                                                <span
                                                                    class="{{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                                                            </td>
                                                            <td>৳{{ number_format($order->grand_total, 0) }}</td>
                                                            <td>
                                                                <a
                                                                    href="{{ route('storefront.customer.order.detail', $order->order_number) }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                        viewBox="0 0 24 24" stroke-width="1.5"
                                                                        stroke="currentColor" class="size-6">
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
                                                            <td colspan="5" class="text-center py-4">No orders yet. <a
                                                                    href="{{ route('storefront.shop.index') }}">Start
                                                                    shopping!</a></td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
