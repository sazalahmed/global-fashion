@extends('ecommerce::storefront.layouts.master')

@section('title', $post->title)
@section('breadcrumb_title', 'Blog Details')

{{-- Sticky-sidebar lib: used by this page's sidebar (perf) --}}
@push('pageVendorJs')<script src="{{ asset('website/assets/js/sticky_sidebar.js') }}"></script>@endpush

@section('breadcrumb')
    <li><a href="{{ route('storefront.blog.index') }}">Blog</a></li>
    <li>{{ Str::limit($post->title, 40) }}</li>
@endsection

@section('content')
    <!--============================
        BLOG DETAILS START
    =============================-->
    <section class="blog_details blog_2 mt_75 mb_100">
        <div class="container">
            <div class="row">
                {{-- Main Content --}}
                <div class="col-xl-9 col-lg-8 wow fadeInUp">
                    <div class="blog_details_left">
                        @if($post->featured_image)
                            <div class="blog_details_img_1">
                                <x-webp :src="$post->featured_image" alt="{{ $post->title }}" class="img-fluid w-100" />
                            </div>
                        @endif

                        <ul class="blog_details_top d-flex flex-wrap">
                            @if($post->published_at)
                                <li>
                                    <span><img src="{{ asset('website/assets/images/calender.png') }}" alt="{{ __('Published date') }}" class="img-fluid w-100" loading="lazy"></span>
                                    {{ $post->published_at->format('d M Y') }}
                                </li>
                            @endif
                            <li>
                                <span><img src="{{ asset('website/assets/images/user_icon_black.svg') }}" alt="{{ __('Author') }}" class="img-fluid w-100" loading="lazy"></span>
                                {{ __('By') }} {{ $post->author->name ?? 'Admin' }}
                            </li>
                            <li>
                                <span><img src="{{ asset('website/assets/images/massage.png') }}" alt="{{ __('Comments') }}" class="img-fluid w-100" loading="lazy"></span>
                                {{ $commentCount }} {{ trans_choice('Comment|Comments', $commentCount) }}
                            </li>
                        </ul>

                        <h1 class="blog_post_title">{{ $post->title }}</h1>

                        @if($post->content)
                            {!! strip_tags($post->content, '<p><br><strong><em><ul><ol><li><h2><h3><h4><h5><h6><a><img><table><thead><tbody><tr><td><th><blockquote><span><div>') !!}
                        @endif
                    </div>

                    {{-- Tags & Share --}}
                    <div class="blog_shear_area">
                        <div class="row">
                            <div class="col-xl-7">
                                @php($postTags = collect($post->tags ?? [])->map(fn($t) => trim($t))->filter()->values())
                                @if($postTags->isNotEmpty())
                                    <div class="blog_shear_area_left d-flex flex-wrap">
                                        <h5>{{ __('Post Tags:') }}</h5>
                                        <ul class="blog_details_tag d-flex flex-wrap">
                                            @foreach($postTags as $tag)
                                                <li><a href="{{ route('storefront.blog.index', ['tag' => $tag]) }}">{{ $tag }}</a></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                            <div class="col-xl-5">
                                <div class="blog_shear_area_right d-flex flex-wrap">
                                    <h5>{{ __('Share:') }}</h5>
                                    <ul class="d-flex flex-wrap">
                                        <li><a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank" rel="noopener"><i class="fab fa-facebook-f" aria-hidden="true"></i></a></li>
                                        <li><a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener"><i class="fab fa-twitter" aria-hidden="true"></i></a></li>
                                        <li><a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(request()->url()) }}" target="_blank" rel="noopener"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a></li>
                                        <li><a href="https://api.whatsapp.com/send?text={{ urlencode($post->title . ' ' . request()->url()) }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Comments --}}
                    <div class="blog_details_comment" id="comments">
                        <h2>{{ $commentCount }} {{ trans_choice('Comment|Comments', $commentCount) }}</h2>
                        @forelse($comments as $comment)
                            <div class="blog_comment d-flex flex-wrap">
                                <div class="blog_comment_img">
                                    <img src="{{ asset('website/assets/images/comment_' . ($loop->even ? '2' : '1') . '.png') }}" alt="{{ $comment->name }}" class="img-fluid w-100" loading="lazy">
                                </div>
                                <div class="blog_comment_text">
                                    <h4>{{ $comment->name }}</h4>
                                    <span>{{ $comment->created_at->format('d M Y') }} {{ __('at') }} {{ $comment->created_at->format('h:i a') }}</span>
                                    <p>{{ $comment->comment }}</p>
                                </div>
                            </div>
                        @empty
                            <p>{{ __('No comments yet. Be the first to comment!') }}</p>
                        @endforelse
                    </div>

                    {{-- Comment Form --}}
                    <div class="blog_details_comment_input">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <form action="{{ route('storefront.blog.comment.store', $post->slug) }}" method="POST">
                            @csrf
                            {{-- Anti-bot: honeypot field (must stay empty) + time-trap timestamp.
                                 Real users never see or fill the honeypot. --}}
                            <div class="bp-visually-hidden" aria-hidden="true">
                                <label for="bp_website">{{ __('Website') }}</label>
                                <input type="text" id="bp_website" name="website" tabindex="-1" autocomplete="off">
                            </div>
                            <input type="hidden" name="form_started_at" value="{{ encrypt(now()->timestamp) }}">
                            <h2>{{ __('Leave a Comment') }}</h2>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="blog_form_input">
                                        <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Name *') }}">
                                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="blog_form_input">
                                        <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('E-mail *') }}">
                                        @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="blog_form_input">
                                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="{{ __('Phone') }}">
                                        @error('phone') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="blog_form_input">
                                        <input type="text" name="subject" value="{{ old('subject') }}" placeholder="{{ __('Subject') }}">
                                        @error('subject') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <div class="blog_form_input">
                                        <textarea name="comment" rows="6" placeholder="{{ __('Your Comment Here...') }}">{{ old('comment') }}</textarea>
                                        @error('comment') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <button class="common_btn" type="submit">{{ __('Submit Comment') }} <i class="fas fa-long-arrow-right"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="col-xl-3 col-lg-4 col-md-8 wow fadeInRight">
                    <div id="sticky_sidebar">
                        @include('ecommerce::storefront.partials.blog-sidebar')
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--============================
        BLOG DETAILS END
    =============================-->
@endsection
