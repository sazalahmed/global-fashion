@extends('ecommerce::storefront.layouts.master')

@section('title', 'Combo Packages')
@section('breadcrumb_title', 'Combo Packages')

@section('breadcrumb')
    <li><a href="{{ route('storefront.combos.index') }}">Combo Packages</a></li>
@endsection

@section('content')
    <!--============================ COMBO PACKAGES START =============================-->
    <h1 class="bp-visually-hidden">Combo Packages</h1>
    <section class="flash_sell_page pt_60 pb_70">
        <div class="container">
            @if ($combos->count() > 0)
                <div class="row" id="combo-list-container">
                    @foreach ($combos as $c)
                        <div class="col-xl-1-5 col-6 col-md-4 col-xl-3 wow fadeInUp">
                            @include('ecommerce::storefront.partials.combo-card', [
                                'combo' => $c,
                                'service' => $service,
                            ])
                        </div>
                    @endforeach
                </div>

                @if ($combos->hasPages())
                    <div class="row">
                        <div class="col-12 text-center" id="infinite-scroll-trigger" data-next-page="{{ $combos->nextPageUrl() }}">
                            <div class="spinner-border text-primary my-4 d-none" role="status" id="infinite-scroll-spinner">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-box-open fa-5x text-muted mb-4 d-block"></i>
                        <h3>No combo packages available right now</h3>
                        <p class="text-muted">Please check back soon.</p>
                        <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">
                            Browse Products <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!--============================ COMBO PACKAGES END =============================-->
@endsection

@push('scripts')
<script>
    'use strict';
    $(function() {
        // ── Infinite Scroll Caching ──
        var CACHE_KEY = 'combo_infinite_scroll_cache';
        var CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

        // Restore cache on load
        var cached = sessionStorage.getItem(CACHE_KEY);
        if (cached) {
            try {
                var cacheData = JSON.parse(cached);
                var now = new Date().getTime();
                if (now - cacheData.timestamp < CACHE_TTL_MS) {
                    $('#combo-list-container').html(cacheData.html);
                    
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
                console.error('Error restoring combo cache', e);
            }
        }

        // Save cache when navigating to a combo
        $('#combo-list-container').on('click', 'a', function() {
            var $trigger = $('#infinite-scroll-trigger');
            var cacheData = {
                html: $('#combo-list-container').html(),
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
                            var newCombos = $html.find('#combo-list-container').html();
                            
                            // Remove wow classes from existing items to prevent re-animation flashing
                            $('#combo-list-container .wow').removeClass('wow fadeInUp');
                            
                            $('#combo-list-container').append(newCombos);
                            
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
