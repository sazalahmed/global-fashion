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
                <div class="row" id="flash-deals-list-container">
                    @foreach ($products as $product)
                        <div class="col-xl-1-5 col-6 col-md-4 col-xl-3 wow fadeInUp">
                            @include('ecommerce::storefront.partials.product-card', [
                                'product' => $product,
                            ])
                        </div>
                    @endforeach
                </div>

                {{-- Infinite Scroll Trigger --}}
                @if ($products->hasPages())
                    <div class="row">
                        <div class="col-12 text-center" id="infinite-scroll-trigger" data-next-page="{{ $products->nextPageUrl() }}">
                            <div class="spinner-border text-primary my-4 d-none" role="status" id="infinite-scroll-spinner">
                                <span class="visually-hidden">Loading...</span>
                            </div>
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

        $(function() {
            // ── Infinite Scroll Caching ──
            var CACHE_KEY = 'flash_deals_infinite_scroll_cache';
            var CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

            // Restore cache on load
            var cached = sessionStorage.getItem(CACHE_KEY);
            if (cached) {
                try {
                    var cacheData = JSON.parse(cached);
                    var now = new Date().getTime();
                    if (now - cacheData.timestamp < CACHE_TTL_MS) {
                        $('#flash-deals-list-container').html(cacheData.html);
                        
                        var $trigger = $('#infinite-scroll-trigger');
                        if (cacheData.nextUrl) {
                            $trigger.attr('data-next-page', cacheData.nextUrl);
                        } else {
                            $trigger.removeAttr('data-next-page');
                            $trigger.remove();
                        }
                        
                        setTimeout(function() {
                            $(window).scrollTop(cacheData.scrollPos);
                        }, 10);
                    } else {
                        sessionStorage.removeItem(CACHE_KEY);
                    }
                } catch (e) {
                    console.error('Error restoring flash deals cache', e);
                }
            }

            // Save cache when navigating to a product
            $('#flash-deals-list-container').on('click', 'a', function() {
                var $trigger = $('#infinite-scroll-trigger');
                var cacheData = {
                    html: $('#flash-deals-list-container').html(),
                    nextUrl: $trigger.length ? $trigger.attr('data-next-page') : '',
                    scrollPos: $(window).scrollTop(),
                    timestamp: new Date().getTime()
                };
                sessionStorage.setItem(CACHE_KEY, JSON.stringify(cacheData));
            });

            // ── Infinite Scroll ──
            var isLoading = false;
            $(window).on('scroll', function() {
                var $trigger = $('#infinite-scroll-trigger');
                if ($trigger.length === 0) return;

                var nextPageUrl = $trigger.attr('data-next-page');
                if (!nextPageUrl) return;

                // Load more when user scrolls near the bottom of the page (approx 3 rows before)
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 1200) {
                    if (!isLoading) {
                        isLoading = true;
                        $('#infinite-scroll-spinner').removeClass('d-none');
                        
                        $.ajax({
                            url: nextPageUrl,
                            type: 'GET',
                            success: function(response) {
                                var $html = $(response);
                                var newProducts = $html.find('#flash-deals-list-container').html();
                                
                                // Remove wow classes from existing items to prevent re-animation flashing
                                $('#flash-deals-list-container .wow').removeClass('wow fadeInUp');
                                
                                $('#flash-deals-list-container').append(newProducts);
                                
                                var newTrigger = $html.find('#infinite-scroll-trigger');
                                if (newTrigger.length && newTrigger.attr('data-next-page')) {
                                    $trigger.attr('data-next-page', newTrigger.attr('data-next-page'));
                                } else {
                                    $trigger.removeAttr('data-next-page');
                                    $trigger.remove();
                                }
                                
                                if (typeof WOW !== 'undefined') {
                                    new WOW().init();
                                }
                                
                                isLoading = false;
                                $('#infinite-scroll-spinner').addClass('d-none');
                            },
                            error: function() {
                                isLoading = false;
                                $('#infinite-scroll-spinner').addClass('d-none');
                            }
                        });
                    }
                }
            });
        });
    </script>
@endpush
