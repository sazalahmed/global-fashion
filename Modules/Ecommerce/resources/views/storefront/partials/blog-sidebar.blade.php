{{-- Blog sidebar widgets (shared by blog index + blog details).
     Expects: $popularPosts (Collection), $categories (Collection), $popularTags (Collection).
     Optional: $blogSection (HomepageSection) — drives the sidebar ad image + URL. --}}
<div class="blog_details_right">
    <form action="{{ route('storefront.blog.index') }}" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Search...') }}">
        <button type="submit"><i class="far fa-search" aria-hidden="true"></i></button>
    </form>

    @if($popularPosts->count() > 0)
        <div class="blog_details_right_header sidebar_blog">
            <h3>{{ __('Popular Blog') }}</h3>
            @foreach($popularPosts as $popular)
                <div class="popular_blog d-flex flex-wrap">
                    <div class="popular_blog_img">
                        <img src="{{ $popular->featured_image ? upload_url($popular->featured_image) : asset('website/assets/images/blog_img_1.png') }}"
                             alt="{{ $popular->title }}" class="img-fluid w-100">
                    </div>
                    <div class="popular_blog_text">
                        @if($popular->published_at)
                            <p>
                                <span><img src="{{ asset('website/assets/images/calender.png') }}" alt="icon" class="img-fluid w-100"></span>
                                {{ $popular->published_at->format('d M Y') }}
                            </p>
                        @endif
                        <a class="title" href="{{ route('storefront.blog.show', $popular->slug) }}">{{ Str::limit($popular->title, 50) }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($categories->count() > 0)
        <div class="blog_details_right_header">
            <h3>{{ __('Categories') }}</h3>
            <ul class="sidebar_blog_category">
                @foreach($categories as $cat)
                    <li>
                        <a href="{{ route('storefront.blog.index', ['category' => $cat->slug]) }}">
                            <p>{{ $cat->name }}</p>
                            <span>({{ str_pad($cat->total, 2, '0', STR_PAD_LEFT) }})</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($popularTags->count() > 0)
        <div class="blog_details_right_header">
            <h3>{{ __('Popular Tags') }}</h3>
            <ul class="blog_details_tag d-flex flex-wrap">
                @foreach($popularTags as $tag)
                    <li><a href="{{ route('storefront.blog.index', ['tag' => $tag]) }}">{{ $tag }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $blogSection = $blogSection ?? null;
        $adImage = $blogSection?->getSetting('ad_image') ?: null;
        $adLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
            $blogSection?->getSetting('ad_link', 'storefront.shop.index') ?? 'storefront.shop.index',
        );
    @endphp
    <div class="blog_details_right_header">
        <div class="blog_seidebar_add">
            <img src="{{ $adImage ? upload_url($adImage) : asset('website/assets/images/blog_sidebar_add_img.png') }}" alt="blog add" class="img-fluid w-100">
            <div class="text">
                <h4>{{ __('Will help enhance your beauty.') }}</h4>
                <a class="common_btn" href="{{ $adLink }}" tabindex="-1">{{ __('shop now') }} <i class="fas fa-long-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>
</div>
