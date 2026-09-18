@extends('ecommerce::storefront.layouts.master')

@section('title', 'Flash Deals')

@section('breadcrumb_title', 'Flash Deals')
@section('breadcrumb')
    <li>Flash Deals</li>
@endsection

@section('content')
    <!--============================ FLASH DEALS PAGE START ==============================-->
    <section class="flash_sell_page pt_60 pb_70">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 col-xl-7 col-xxl-8 col-7">
                    <div class="section_heading">
                        <h3>Flash <span>Deals</span></h3>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-5 col-xxl-4 col-5 flash_sell_2 ">
                    @if ($countdownEndsAt ?? null)
                        <div class="simply-countdown simply-countdown-one"
                            data-ends="{{ $countdownEndsAt->toIso8601String() }}"
                            aria-label="Time remaining until current sale ends"></div>
                        {{-- <div class="text-muted mt-2 fs-12">Ends {{ $countdownEndsAt->format('d M Y, H:i') }}</div> --}}
                    @endif
                </div>
            </div>

            @if ($products->count() > 0)
                <div class="row">
                    @foreach ($products as $product)
                        <div class="col-xl-1-5 col-6 col-md-4 col-xl-3 wow fadeInUp">
                            @include('ecommerce::storefront.partials.product-card', [
                                'product' => $product,
                            ])
                        </div>
                    @endforeach
                </div>

                @if ($products->hasPages())
                    <div class="row">
                        <div class="pagination_area">
                            {{ $products->links('ecommerce::storefront.partials.pagination') }}
                        </div>
                    </div>
                @endif
            @else
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-bolt fa-5x text-muted mb-4 d-block"></i>
                        <h3>No flash deals right now</h3>
                        <p class="text-muted">Check back later for amazing deals!</p>
                        <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">
                            Browse Products <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!--============================
                                                                            FLASH DEALS PAGE END
                                                                        ==============================-->
@endsection

@push('scripts')
    <script>
        'use strict';
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.querySelector('.simply-countdown-one');
            if (typeof simplyCountdown === 'undefined' || !el || !el.dataset.ends) return;
            // Earliest active flash deal end — provided server-side. Parse with the
            // native Date constructor so the user sees their local-timezone ticks.
            var ends = new Date(el.dataset.ends);
            if (isNaN(ends.getTime())) return;
            // Wipe any previous render so a stray double-init can't stack two
            // sets of digits inside the same element.
            el.innerHTML = '';
            simplyCountdown('.simply-countdown-one', {
                year: ends.getFullYear(),
                month: ends.getMonth() + 1,
                day: ends.getDate(),
                hours: ends.getHours(),
                minutes: ends.getMinutes(),
                seconds: ends.getSeconds(),
            });
        });
    </script>
@endpush
