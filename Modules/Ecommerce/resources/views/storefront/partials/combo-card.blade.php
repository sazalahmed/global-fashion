@php
    // Combo card mirrors the product-card structure but for a Combo package.
    // Prices come from the ComboService passed in as $service.
    $summed = $service->summedPrice($combo);
    $effective = $service->effectivePrice($combo);
    $hasSaving = $summed > $effective;
    $discountPercent = $hasSaving && $summed > 0 ? round((($summed - $effective) / $summed) * 100) : 0;
    $inStock = $service->isInStock($combo);
    $itemCount = $combo->items->count();
@endphp

<div class="product_item_2 product_item js-bp-item"
    data-item-id="combo:{{ $combo->id }}"
    data-item-name="{{ $combo->name }}"
    data-price="{{ (float) $effective }}"
    data-discount="{{ $hasSaving ? round((float) $summed - (float) $effective, 2) : 0 }}">
    <div class="product_img">
        <a href="{{ route('storefront.combos.show', $combo->slug) }}" aria-label="{{ $combo->name }}">
            <x-webp :src="$combo->thumbnail" :default="asset('website/assets/images/product_placeholder.png')" alt="{{ $combo->name }}" class="img-fluid w-100" loading="lazy" />
        </a>
        @if($hasSaving && $discountPercent > 0)
            <ul class="discount_list">
                <li class="discount"><b>-</b> {{ $discountPercent }}%</li>
            </ul>
        @endif
    </div>
    <div class="product_text">
        <a class="title" href="{{ route('storefront.combos.show', $combo->slug) }}">{{ $combo->name }}</a>
        <div class="combo_price_row">
            <p class="price">
                {{ currency_symbol() }} {{ bd_price($effective) }}
                @if($hasSaving)
                    <del>{{ currency_symbol() }} {{ bd_price($summed) }}</del>
                @endif
            </p>
            <span class="combo_item_count">
                <i class="fas fa-layer-group"></i> {{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }}
            </span>
        </div>
        <div class="product_actions">
            <a class="common_btn product_order_btn" href="{{ route('storefront.combos.show', $combo->slug) }}">
                Order Now <i class="fas fa-long-arrow-right"></i>
            </a>
            {{-- Add to cart adds the whole combo as one cart line. Size-required
                 combos resolve a default size client-side (first in-stock, else
                 first) instead of forcing a detour to the detail page — mirrors
                 the default-variant behavior on product cards. --}}
            @if($inStock)
                <a class="product_add_cart_btn combo-add-cart-trigger" href="#"
                   data-combo-id="{{ $combo->id }}"
                   data-combo-slug="{{ $combo->slug }}"
                   data-size-required="{{ $combo->size_required ? '1' : '0' }}"
                   aria-label="Add to cart" title="Add to cart">
                    <i class="fas fa-cart-plus"></i>
                </a>
            @endif
        </div>
    </div>
    @unless($inStock)
        <div class="out_of_stock">
            <p>out of stock</p>
        </div>
    @endunless
</div>
