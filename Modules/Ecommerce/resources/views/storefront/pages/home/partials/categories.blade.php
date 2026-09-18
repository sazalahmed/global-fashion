{{-- Categories Section — Zenis Style Category 2 --}}
@php
    $categories = $topCategories ?? collect();
@endphp

<section class="category category_2 mt_45">
    <div class="container">
        <div class="row category_2_slider">
            @forelse($categories as $category)
                <div class="col-2 wow fadeInUp">
                    <a href="{{ route('storefront.category.show', $category->slug) }}" class="category_item">
                        <div class="img">
                            <x-webp :src="$category->image" :default="asset('website/assets/images/category_img_1.png')" alt="{{ $category->name }}" class="img-fluid w-100" loading="lazy" />
                        </div>
                        <h3>{{ $category->name }}</h3>
                    </a>
                </div>
            @empty
                @for ($i = 1; $i <= 7; $i++)
                    <div class="col-2 wow fadeInUp">
                        <a href="{{ route('storefront.shop.index') }}" class="category_item">
                            <div class="img">
                                <x-webp :src="'website/assets/images/category_img_' . $i . '.png'" alt="Category" class="img-fluid w-100" loading="lazy" />
                            </div>
                            <h3>Category {{ $i }}</h3>
                        </a>
                    </div>
                @endfor
            @endforelse
        </div>
    </div>
</section>
