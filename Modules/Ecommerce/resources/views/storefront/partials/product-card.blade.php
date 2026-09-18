@php
    // The product card shows the dedicated thumbnail; a homepage-section
    // curated override (when set) wins, then the product thumbnail, then the
    // primary gallery image, then the placeholder.
    $productImage = $product->homepage_thumbnail ?? $product->thumbnail;
    if (!$productImage && $product->relationLoaded('images') && $product->images->count()) {
        $primaryImage = $product->images->where('is_primary', true)->first();
        $productImage = $primaryImage ? $primaryImage->image_path : $product->images->first()->image_path;
    }
    $imageUrl = upload_url($productImage, asset('website/assets/images/product_placeholder.png'));

    $price = $product->displayPrice();
    $sellPrice          = $price->sell;
    $effectivePrice     = $price->effective;
    $hasDiscount        = $price->has_discount;
    $discountPercentage = $price->discount_percent;
    $campaignBadge      = $price->campaign_badge;
    // The "new" badge is reserved for products in the New Arrivals section.
    // The section passes isNewArrival => true; everywhere else it stays off.
    $isNew              = $isNewArrival ?? false;
    $inStock            = $product->is_in_stock;
@endphp

<div class="product_item_2 product_item js-bp-item"
    data-item-id="{{ $product->id }}"
    data-item-name="{{ $product->name }}"
    data-price="{{ (float) $effectivePrice }}"
    data-discount="{{ $hasDiscount ? round((float) $sellPrice - (float) $effectivePrice, 2) : 0 }}"
    data-category="{{ optional($product->category)->name }}">
    <div class="product_img">
        <a href="{{ route('storefront.shop.show', $product->slug) }}" aria-label="{{ $product->name }}">
            <x-webp :src="$productImage" :default="asset('website/assets/images/product_placeholder.png')" alt="{{ $product->name }}" class="img-fluid w-100" loading="lazy" />
        </a>
        @if($hasDiscount || $isNew || $campaignBadge)
        <ul class="discount_list">
            @if($hasDiscount && $discountPercentage > 0)
                <li class="discount"><b>-</b> {{ round($discountPercentage) }}%</li>
            @endif
            @if($campaignBadge)
                <li class="new">{{ Str::limit($campaignBadge, 18) }}</li>
            @elseif($isNew)
                <li class="new">new</li>
            @endif
        </ul>
        @endif
        <ul class="btn_list">
            <li>
                <a href="#" class="add-to-wishlist{{ in_array($product->id, (array) session('wishlist', []), true) ? ' active' : '' }}"
                   data-product-id="{{ $product->id }}"
                   data-id="{{ $product->id }}"
                   data-sku="{{ $product->sku }}"
                   data-name="{{ $product->name }}"
                   data-price="{{ (float) $effectivePrice }}"
                   data-category="{{ optional($product->category)->name }}"
                   data-brand="{{ optional($product->brand)->name }}"
                   aria-label="Add to wishlist" title="Add to wishlist">
                    <img src="{{ asset('website/assets/images/love_icon_white.svg') }}" alt="Wishlist" class="img-fluid">
                </a>
            </li>
            <li>
                <a href="#" class="add-to-compare{{ in_array($product->id, (array) session('compare', []), true) ? ' active' : '' }}"
                   data-product-id="{{ $product->id }}"
                   data-id="{{ $product->id }}"
                   data-sku="{{ $product->sku }}"
                   data-name="{{ $product->name }}"
                   data-price="{{ (float) $effectivePrice }}"
                   data-category="{{ optional($product->category)->name }}"
                   data-brand="{{ optional($product->brand)->name }}"
                   aria-label="Add to compare" title="Compare">
                    <img src="{{ asset('website/assets/images/compare_icon_white.svg') }}" alt="Compare" class="img-fluid">
                </a>
            </li>
        </ul>
    </div>
    <div class="product_text">
        <a class="title" href="{{ route('storefront.shop.show', $product->slug) }}">{{ $product->name }}</a>
        <p class="price">
            {{ currency_symbol() }} {{ bd_price($effectivePrice) }}
            @if($hasDiscount)
                <del>{{ currency_symbol() }} {{ bd_price($sellPrice) }}</del>
            @endif
        </p>
        @php
            $hasVariants = $product->isVariable() && $product->relationLoaded('variants')
                ? $product->variants->where('is_active', true)->isNotEmpty()
                : ($product->isVariable() && $product->variants()->where('is_active', true)->exists());
        @endphp
        <div class="product_actions">
            <a class="common_btn product_order_btn buy-now-trigger"
               href="{{ route('storefront.shop.show', $product->slug) }}"
               data-product-id="{{ $product->id }}"
               data-product-slug="{{ $product->slug }}"
               data-has-variants="{{ $hasVariants ? '1' : '0' }}"
               data-mode="checkout"
               data-id="{{ $product->id }}"
               data-sku="{{ $product->sku }}"
               data-name="{{ $product->name }}"
               data-price="{{ (float) $effectivePrice }}"
               data-category="{{ optional($product->category)->name }}"
               data-brand="{{ optional($product->brand)->name }}">
                Order Now <i class="fas fa-long-arrow-right"></i>
            </a>
            {{-- Add to cart shares the Order Now trigger, in "cart" mode
                 (adds to cart instead of going to checkout). --}}
            <a class="buy-now-trigger product_add_cart_btn" href="#"
               data-product-id="{{ $product->id }}"
               data-product-slug="{{ $product->slug }}"
               data-has-variants="{{ $hasVariants ? '1' : '0' }}"
               data-mode="cart"
               data-id="{{ $product->id }}"
               data-sku="{{ $product->sku }}"
               data-name="{{ $product->name }}"
               data-price="{{ (float) $effectivePrice }}"
               data-category="{{ optional($product->category)->name }}"
               data-brand="{{ optional($product->brand)->name }}"
               aria-label="Add to cart" title="Add to cart">
                <i class="fas fa-cart-plus"></i>
            </a>
        </div>
    </div>
    @unless($inStock)
        <div class="out_of_stock">
            <p>out of stock</p>
        </div>
    @endunless
</div>
