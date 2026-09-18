{{-- Mini-cart body content. Reused on initial render AND returned as HTML
     from /cart/add, /cart/update, /cart/remove so the offcanvas drawer
     reflects the latest session('cart') without a full page reload. --}}
@php
    $cartItems = $cartItems ?? session('cart', []);
    $subtotal = collect($cartItems)->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1));
@endphp

<ul id="mini-cart-items">
    @forelse($cartItems as $key => $item)
        @php
            $isCombo = ($item['type'] ?? 'product') === 'combo';
            $itemUrl = $isCombo
                ? route('storefront.combos.show', $item['slug'] ?? '#')
                : route('storefront.shop.show', $item['slug'] ?? '#');
            $itemImg = upload_url($isCombo ? ($item['thumbnail'] ?? null) : ($item['image'] ?? null), asset('website/assets/images/product_placeholder.png'));
        @endphp
        <li class="mini-cart-item" data-cart-key="{{ $key }}"
            data-item-id="{{ $isCombo ? 'combo:' . ($item['combo_id'] ?? '') : ($item['product_id'] ?? '') }}"
            data-item-name="{{ $item['name'] ?? '' }}"
            data-price="{{ (float) ($item['price'] ?? 0) }}"
            data-quantity="{{ (int) ($item['quantity'] ?? 1) }}"
            data-variant="{{ $item['variant_name'] ?? ($item['size'] ?? '') }}"
            data-discount="{{ max(0, round((float) ($item['sell_price'] ?? 0) - (float) ($item['price'] ?? 0), 2)) }}">
            <a href="{{ $itemUrl }}" class="cart_img">
                <img src="{{ $itemImg }}"
                    alt="{{ $item['name'] ?? 'Product' }}" class="img-fluid w-100">
            </a>
            <div class="cart_text">
                <a class="cart_title"
                    href="{{ $itemUrl }}">{{ $item['name'] ?? 'Product' }}</a>
                <p class="mini-cart-price">
                    {{ currency_symbol() }} {{ bd_price($item['price'] ?? 0) }}
                    @if (isset($item['sell_price']) && $item['sell_price'] > ($item['price'] ?? 0))
                        <del>{{ currency_symbol() }} {{ bd_price($item['sell_price']) }}</del>
                    @endif
                </p>
                {{-- Combos show just name / price / (chosen size) / qty — no
                     per-product breakdown. Products still show their variant lines. --}}
                @if ($isCombo && !empty($item['size']))
                    <small class="text-muted d-block"><b>Size:</b> {{ $item['size'] }}</small>
                @endif
                @unless ($isCombo)
                    @if (!empty($item['variant_attributes']))
                        @foreach ($item['variant_attributes'] as $attr)
                            <small class="text-muted d-block"><b>{{ $attr['label'] }}:</b> {{ $attr['value'] }}</small>
                        @endforeach
                    @elseif (!empty($item['variant_name']))
                        <small class="text-muted d-block">{{ $item['variant_name'] }}</small>
                    @endif
                @endunless
                <span><b>Qty:</b> {{ $item['quantity'] ?? 1 }}</span>
            </div>
            <a class="del_icon remove-cart-item mini-cart-remove" href="#" data-key="{{ $key }}"
                data-cart-key="{{ $key }}">
                <i class="fas fa-times"></i>
            </a>
        </li>
    @empty
        <li class="mini-cart-empty text-center py-5">
            <p class="text-muted mb-3 d-block"> <i class="fas fa-shopping-cart"></i> Your cart is empty </p>
            <a href="{{ route('storefront.shop.index') }}" class="common_btn mini-cart-btn">Start Shopping <i
                    class="fas fa-long-arrow-right"></i></a>
        </li>
    @endforelse
</ul>

@if (count($cartItems) > 0)
    <h5 id="mini-cart-subtotal">Sub Total <span>{{ currency_symbol() }} {{ bd_price($subtotal) }}</span></h5>
    <div class="minicart_btn_area mini-cart-actions" id="mini-cart-footer">
        <a class="common_btn mini-cart-btn" href="{{ route('storefront.cart.index') }}">View Cart</a>
        <a class="common_btn mini-cart-btn buy_now" href="{{ route('storefront.checkout.index') }}">Checkout</a>
    </div>
@endif
