@extends('ecommerce::storefront.layouts.master')

@section('title', 'Categories')
@section('breadcrumb_title', 'Category')

@section('breadcrumb')
    <li><a href="{{ route('storefront.category.index') }}">Category</a></li>
@endsection

@section('content')
    <!--============================  CATEGORY PAGE START =============================-->
    <h1 class="bp-visually-hidden">Categories</h1>
    <section class="category_page category_2 mt_45 mb_65">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-9 col-lg-12">
                    <div class="row" id="category-list-container">
                        @forelse($categories ?? [] as $category)
                            <div class="col-xl-2 col-4 col-sm-3 col-lg-auto wow fadeInUp">
                                <a href="{{ route('storefront.category.show', $category->slug) }}" class="category_item">
                                    <div class="img">
                                        <x-webp :src="$category->image" :default="asset('website/assets/images/category_placeholder.png')" alt="{{ $category->name }}" class="img-fluid w-100" />
                                    </div>
                                    <h3>{{ $category->name }}</h3>
                                </a>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                    <h5>No Categories</h5>
                                    <p class="text-muted">No categories are available at the moment.</p>
                                    <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">Browse All
                                        Products</a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            {{-- Infinite Scroll Trigger --}}
            @if (isset($categories) && method_exists($categories, 'hasPages') && $categories->hasPages())
                <div class="row">
                    <div class="col-12 text-center" id="infinite-scroll-trigger" data-next-page="{{ $categories->nextPageUrl() }}">
                        <div class="spinner-border text-primary my-4 d-none" role="status" id="infinite-scroll-spinner">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!--============================ CATEGORY PAGE END =============================-->
@endsection

@push('scripts')
<script>
    'use strict';
    $(function() {
        // ── Infinite Scroll Caching ──
        var CACHE_KEY = 'category_infinite_scroll_cache';
        var CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

        // Restore cache on load
        var cached = sessionStorage.getItem(CACHE_KEY);
        if (cached) {
            try {
                var cacheData = JSON.parse(cached);
                var now = new Date().getTime();
                if (now - cacheData.timestamp < CACHE_TTL_MS) {
                    $('#category-list-container').html(cacheData.html);
                    
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
                console.error('Error restoring category cache', e);
            }
        }

        // Save cache when navigating to a category
        $('#category-list-container').on('click', 'a', function() {
            var $trigger = $('#infinite-scroll-trigger');
            var cacheData = {
                html: $('#category-list-container').html(),
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
                            var newCategories = $html.find('#category-list-container').html();
                            
                            // Remove wow classes from existing items to prevent re-animation flashing
                            $('#category-list-container .wow').removeClass('wow fadeInUp');
                            
                            $('#category-list-container').append(newCategories);
                            
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
