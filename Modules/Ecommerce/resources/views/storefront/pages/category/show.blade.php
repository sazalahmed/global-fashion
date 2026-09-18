@extends('ecommerce::storefront.layouts.master')

@section('title', $category->name)
@section('breadcrumb_title', $category->name)

{{-- Price-range slider lib: loaded only on pages that render the filter (perf) --}}
@push('pageVendorCss')<link rel="stylesheet" href="{{ asset('website/assets/css/range_slider.css') }}">@endpush
@push('pageVendorJs')<script src="{{ asset('website/assets/js/range_slider.js') }}"></script><script src="{{ asset('website/assets/js/sticky_sidebar.js') }}"></script>@endpush

@section('breadcrumb')
    <li><a href="{{ route('storefront.category.index') }}">Category</a></li>
    @if($category->parent)
        <li><a href="{{ route('storefront.category.show', $category->parent->slug) }}">{{ $category->parent->name }}</a></li>
    @endif
    <li><a href="{{ route('storefront.category.show', $category->slug) }}">{{ $category->name }}</a></li>
@endsection

@section('content')
    <!--============================
        SHOP PAGE (Category) START
    =============================-->
    <section class="shop_page mt_100 mb_100">
        <div class="container">
            <div class="row">
                <div class="col-xxl-2 col-lg-4 col-xl-3">
                    <div id="sticky_sidebar">
                        <div class="shop_filter_btn d-lg-none"> Filter </div>
                        <div class="shop_filter_area">
                            {{-- Category Header --}}
                            @if($category->image)
                                <div class="sidebar_category_header mb-3">
                                    <x-webp :src="$category->image" alt="{{ $category->name }}" class="img-fluid w-100 rounded" loading="lazy" />
                                </div>
                            @endif

                            @if($category->description)
                                <div class="sidebar_status mb-3">
                                    <p>{{ $category->description }}</p>
                                </div>
                            @endif

                            {{-- Subcategories --}}
                            @php
                                $subcategories = $category->children->where('status', 'active');
                            @endphp
                            @if($subcategories->isNotEmpty())
                                <div class="sidebar_category">
                                    <h3>Subcategories</h3>
                                    <ul>
                                        @foreach($subcategories as $sub)
                                            <li>
                                                <a href="{{ route('storefront.category.show', $sub->slug) }}">
                                                    {{ $sub->name }}
                                                    @if(isset($sub->products_count))
                                                        <span>{{ str_pad($sub->products_count, 2, '0', STR_PAD_LEFT) }}</span>
                                                    @endif
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Price Range --}}
                            <div class="sidebar_range">
                                <h3>Price Range</h3>
                                <form action="{{ route('storefront.category.show', $category->slug) }}" method="GET">
                                    @foreach(request()->except(['min_price', 'max_price', 'page']) as $key => $value)
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endforeach
                                    <div class="d-flex gap-2 align-items-center mb-2">
                                        <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="{{ request('min_price') }}" min="0">
                                        <span>-</span>
                                        <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="{{ request('max_price') }}" min="0">
                                    </div>
                                    <button type="submit" class="common_btn w-100">Apply</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-10 col-lg-8 col-xl-9">
                    <h1 class="bp-visually-hidden">{{ $category->name }}</h1>
                    <div class="product_page_top">
                        <div class="row">
                            <div class="col-4 col-xl-6 col-md-6">
                                <div class="product_page_top_button">
                                    <nav>
                                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                            <button class="nav-link active" id="nav-grid-tab" data-bs-toggle="tab"
                                                data-bs-target="#nav-grid" type="button" role="tab"
                                                aria-controls="nav-grid" aria-selected="true">
                                                <i class="fas fa-th"></i>
                                            </button>
                                            <button class="nav-link" id="nav-list-tab" data-bs-toggle="tab"
                                                data-bs-target="#nav-list" type="button" role="tab"
                                                aria-controls="nav-list" aria-selected="false">
                                                <i class="fas fa-list-ul"></i>
                                            </button>
                                        </div>
                                    </nav>
                                    @if(isset($products) && $products->total() > 0)
                                        <p>Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }} results</p>
                                    @else
                                        <p>No products found</p>
                                    @endif
                                </div>
                            </div>
                            <div class="col-8 col-xl-6 col-md-6">
                                <ul class="product_page_sorting">
                                    <li>
                                        <select class="select_js" id="categorySortSelect">
                                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Default Sorting</option>
                                            <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Low to High</option>
                                            <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>High to Low</option>
                                            <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
                                            <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name: Z-A</option>
                                        </select>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="nav-tabContent">
                        {{-- Grid View --}}
                        <div class="tab-pane fade show active" id="nav-grid" role="tabpanel"
                            aria-labelledby="nav-grid-tab" tabindex="0">
                            <div class="row">
                                @forelse($products ?? [] as $product)
                                    <div class="col-xxl-3 col-6 col-md-4 col-lg-6 col-xl-4 wow fadeInUp">
                                        @include('ecommerce::storefront.partials.product-card', ['product' => $product])
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="text-center py-5">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <h5>No Products</h5>
                                            <p class="text-muted">No products found in this category.</p>
                                            <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">Browse All Products</a>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- List View --}}
                        <div class="tab-pane fade" id="nav-list" role="tabpanel"
                            aria-labelledby="nav-list-tab" tabindex="0">
                            <div class="row">
                                @forelse($products ?? [] as $product)
                                    @php
                                        $productImage = null;
                                        if ($product->relationLoaded('images') && $product->images->count()) {
                                            $pImg = $product->images->where('is_primary', true)->first();
                                            $productImage = $pImg ? $pImg->image_path : $product->images->first()->image_path;
                                        }
                                        $imgUrl = upload_url($productImage, asset('website/assets/images/product_placeholder.png'));
                                    @endphp
                                    <div class="col-12 wow fadeInUp mb-4">
                                        <div class="product_item_list d-flex">
                                            <div class="product_item_list_img">
                                                <a href="{{ route('storefront.shop.show', $product->slug) }}">
                                                    <x-webp :src="$productImage" :default="asset('website/assets/images/product_placeholder.png')" alt="{{ $product->name }}" class="img-fluid w-100" loading="lazy" />
                                                </a>
                                            </div>
                                            <div class="product_item_list_text">
                                                <a class="title" href="{{ route('storefront.shop.show', $product->slug) }}">{{ $product->name }}</a>
                                                @php $catPrice = $product->displayPrice(); @endphp
                                                <p class="price">
                                                    {{ currency_symbol() }} {{ bd_price($catPrice->effective) }}
                                                    @if($catPrice->has_discount)
                                                        <del>{{ currency_symbol() }} {{ bd_price($catPrice->sell) }}</del>
                                                    @endif
                                                </p>
                                                @if($product->description)
                                                    <p class="description">{{ Str::limit(strip_tags($product->description), 150) }}</p>
                                                @endif
                                                <a class="common_btn" href="{{ route('storefront.shop.show', $product->slug) }}">View Details <i class="fas fa-long-arrow-right"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="text-center py-5">
                                            <h5>No products found</h5>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Pagination --}}
                    @if(isset($products) && $products->hasPages())
                        <div class="row">
                            <div class="pagination_area">
                                {{ $products->links('ecommerce::storefront.partials.pagination') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <!--============================
        SHOP PAGE (Category) END
    =============================-->
@endsection

@push('scripts')
<script>
'use strict';
$(function() {
    $('#categorySortSelect').on('change', function() {
        var url = new URL(window.location.href);
        url.searchParams.set('sort', $(this).val());
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });
});
</script>
@endpush
