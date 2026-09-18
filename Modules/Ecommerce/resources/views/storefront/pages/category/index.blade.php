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
                    <div class="row">
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
            {{-- Pagination --}}
            @if (isset($categories) && method_exists($categories, 'hasPages') && $categories->hasPages())
                <div class="row">
                    <div class="pagination_area">
                        {{ $categories->links('ecommerce::storefront.partials.pagination') }}
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!--============================ CATEGORY PAGE END =============================-->
@endsection
