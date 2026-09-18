@extends('ecommerce::storefront.layouts.master')

@section('title', 'Blog')
@section('breadcrumb_title', 'Blog')

@section('breadcrumb')
    <li>Blog</li>
@endsection

@section('content')
    <section class="blog_2 pt_45 pb_70">
        <div class="container">
            <div class="row">
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

            @if ($posts->hasPages())
                <div class="row mt-4">
                    <div class="pagination_area">
                        {{ $posts->links('ecommerce::storefront.partials.pagination') }}
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
