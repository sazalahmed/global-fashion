{{-- Blog Section — Zenis Style Blog 2 --}}
@php
    $section = $section ?? null;
    $posts = $blogPosts ?? collect();

    $secLimit = (int) ($section?->getSetting('items_count', 4) ?? 4);
    // Storefront heading: explicit section heading setting, else the section's
// own title, else a sensible default.
$secHeading = $section?->getSetting('heading') ?: ($section?->title ?: 'Our News & Articles');
$secHighlight = $section?->getSetting('highlight', 'News') ?? 'News';
$secViewLabel = $section?->getSetting('view_all_label', 'View all') ?? 'View all';
$secViewLink = \Modules\Ecommerce\Support\HomepageSectionSchema::resolveLink(
    $section?->getSetting('view_all_link', 'storefront.blog.index') ?? 'storefront.blog.index',
    );
@endphp

@if ($posts->count() > 0)
    <section class="blog_2 pt_60">
        <div class="container">
            <div class="row">
                <div class="col-xl-6 col-9">
                    <div class="section_heading_2 section_heading">
                        <h3><x-ecommerce::section-heading :heading="$secHeading" :highlight="$secHighlight" /></h3>
                    </div>
                </div>
                <div class="col-xl-6 col-3">
                    <div class="view_all_btn_area">
                        <a class="view_all_btn" href="{{ $secViewLink }}">{{ $secViewLabel }}</a>
                    </div>
                </div>
            </div>
            <div class="row">
                @foreach ($posts->take($secLimit) as $post)
                    <div class="col-lg-4 col-xl-3 col-6 wow fadeInUp">
                        <div class="blog_item">
                            <a href="{{ route('storefront.blog.show', $post->slug) }}" class="blog_img">
                                <x-webp :src="$post->featured_image" :default="asset('website/assets/images/blog_img_1.png')" alt="{{ $post->title }}" class="img-fluid w-100" loading="lazy" />
                            </a>
                            <div class="blog_text">
                                <ul class="top">
                                    <li>
                                        <span>
                                            <img src="{{ asset('website/assets/images/user_icon_black.svg') }}"
                                                alt="user" class="img-fluid w-100">
                                        </span>
                                        {{ $post->author_name ?? ($post->author->name ?? 'Admin') }}
                                    </li>
                                    @if ($post->published_at)
                                        <li>
                                            <span>
                                                <img src="{{ asset('website/assets/images/calender.png') }}"
                                                    alt="date" class="img-fluid w-100">
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
                @endforeach
            </div>
        </div>
    </section>
@endif
