@extends('ecommerce::storefront.layouts.master')

@section('title', 'Blog')
@section('breadcrumb_title', 'Blog')

@section('breadcrumb')
    <li>Blog</li>
@endsection

@section('content')
    <section class="blog_2 pt_45 pb_70">
        <div class="container">
            <div class="row" id="blog-list-container">
                @forelse($posts as $post)
                    <div class="col-lg-4 col-xxl-3 col-6 wow fadeInUp">
                        <div class="blog_item">
                            <a href="{{ route('storefront.blog.show', $post->slug) }}" class="blog_img">
                                <x-webp :src="$post->featured_image" :default="asset('website/assets/images/blog_img_1.png')" alt="{{ $post->title }}" class="img-fluid w-100" />
                            </a>
                            <div class="blog_text">
                                <ul class="top">
                                    <li>
                                        <span>
                                            <img src="{{ asset('website/assets/images/user_icon_black.svg') }}"
                                                alt="user" class="img-fluid w-100">
                                        </span>
                                        {{ $post->author->name ?? 'Admin' }}
                                    </li>
                                    @if ($post->published_at)
                                        <li>
                                            <span>
                                                <img src="{{ asset('website/assets/images/calender.png') }}" alt="date"
                                                    class="img-fluid w-100">
                                            </span>
                                            {{ $post->published_at->format('d M Y') }}
                                        </li>
                                    @endif
                                </ul>
                                <a class="title"
                                    href="{{ route('storefront.blog.show', $post->slug) }}">{{ Str::limit($post->title, 55) }}</a>
                                <ul class="bottom">
                                    <li><a href="{{ route('storefront.blog.show', $post->slug) }}">read more <i
                                                class="fas fa-long-arrow-right"></i></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                        <h5>No blog posts yet</h5>
                        <p class="text-muted">Check back soon for updates!</p>
                    </div>
                @endforelse
            </div>

            {{-- Infinite Scroll Trigger --}}
            @if ($posts->hasPages())
                <div class="row mt-4">
                    <div class="col-12 text-center" id="infinite-scroll-trigger" data-next-page="{{ $posts->nextPageUrl() }}">
                        <div class="spinner-border text-primary my-4 d-none" role="status" id="infinite-scroll-spinner">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
<script>
    'use strict';
    $(function() {
        // ── Infinite Scroll Caching ──
        var CACHE_KEY = 'blog_infinite_scroll_cache';
        var CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

        // Restore cache on load
        var cached = sessionStorage.getItem(CACHE_KEY);
        if (cached) {
            try {
                var cacheData = JSON.parse(cached);
                var now = new Date().getTime();
                if (now - cacheData.timestamp < CACHE_TTL_MS) {
                    $('#blog-list-container').html(cacheData.html);
                    
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
                console.error('Error restoring blog cache', e);
            }
        }

        // Save cache when navigating to a post
        $('#blog-list-container').on('click', 'a', function() {
            var $trigger = $('#infinite-scroll-trigger');
            var cacheData = {
                html: $('#blog-list-container').html(),
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
                            var newPosts = $html.find('#blog-list-container').html();
                            
                            // Remove wow classes from existing items to prevent re-animation flashing
                            $('#blog-list-container .wow').removeClass('wow fadeInUp');
                            
                            $('#blog-list-container').append(newPosts);
                            
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
