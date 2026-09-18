{{-- Renders a single menu "widget" item (cart/wishlist/compare/account/search)
     using the theme's existing markup so styles + cart.js count-updates keep
     working unchanged. Params: $widget (key), $item (tree node), $style. --}}
@php
    $widget        = $widget ?? ($item['value'] ?? null);
    $style         = $style ?? 'main';
    $label         = $item['label'] ?? ucfirst((string) $widget);
    $icon          = $item['icon'] ?? null;
    $cartCount     = count((array) session('cart', []));
    $wishlistCount = count((array) session('wishlist', [])) + count((array) session('wishlist_combos', []));
    $compareCount  = count((array) session('compare', [])) + count((array) session('compare_combos', []));
@endphp

@if ($style === 'main')
    @switch($widget)
        @case('wishlist')
            <li><a href="{{ route('storefront.wishlist.index') }}"><b><img src="{{ asset('website/assets/images/love_black.svg') }}" alt="Wishlist" class="img-fluid"></b><span class="wishlist-count">{{ $wishlistCount }}</span></a></li>
            @break
        @case('compare')
            <li><a href="{{ route('storefront.compare.index') }}"><b><img src="{{ asset('website/assets/images/compare_black.svg') }}" alt="Compare" class="img-fluid"></b><span class="compare-count">{{ $compareCount }}</span></a></li>
            @break
        @case('cart')
            <li><a data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><b><img src="{{ asset('website/assets/images/cart_black.svg') }}" alt="Cart" class="img-fluid"></b><span class="cart-count">{{ $cartCount }}</span></a></li>
            @break
        @case('account')
            @auth('customer')
                <li>
                    <a class="user" href="{{ route('storefront.customer.profile') }}">
                        <b><img src="{{ asset('website/assets/images/user_icon_black.svg') }}" alt="Account" class="img-fluid"></b>
                    </a>
                    <ul class="user_dropdown">
                        <li><a href="{{ route('storefront.customer.profile') }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"></path></svg> Dashboard</a></li>
                        <li><a href="{{ route('storefront.customer.profile.edit') }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path></svg> My Account</a></li>
                        <li><a href="{{ route('storefront.customer.orders') }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"></path></svg> My Orders</a></li>
                        <li><a href="{{ route('storefront.wishlist.index') }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"></path></svg> Wishlist</a></li>
                        <li>
                            <a href="#" onclick="event.preventDefault(); document.getElementById('navbar-logout-form').submit();"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"></path></svg> Logout</a>
                            <form id="navbar-logout-form" action="{{ route('storefront.customer.logout') }}" method="POST" class="d-none">@csrf</form>
                        </li>
                    </ul>
                </li>
            @else
                <li><a class="user" href="{{ route('storefront.customer.login') }}"><b><img src="{{ asset('website/assets/images/user_icon_black.svg') }}" alt="Login" class="img-fluid"></b></a></li>
            @endauth
            @break
    @endswitch

@elseif ($style === 'mobile')
    @switch($widget)
        @case('wishlist')
            <li><a href="{{ route('storefront.wishlist.index') }}"><b><img src="{{ asset('website/assets/images/love_black.svg') }}" alt="Wishlist"></b><span class="wishlist-count">{{ $wishlistCount }}</span></a></li>
            @break
        @case('compare')
            <li><a href="{{ route('storefront.compare.index') }}"><b><img src="{{ asset('website/assets/images/compare_black.svg') }}" alt="Compare"></b><span class="compare-count">{{ $compareCount }}</span></a></li>
            @break
        @case('cart')
            <li><a href="{{ route('storefront.cart.index') }}"><b><img src="{{ asset('website/assets/images/cart_black.svg') }}" alt="Cart"></b><span class="cart-count">{{ $cartCount }}</span></a></li>
            @break
    @endswitch

@elseif ($style === 'bottom')
    @switch($widget)
        @case('search')
            <button type="button" class="bp-mbn-item bp-mbn-btn" data-bs-toggle="modal" data-bs-target="#bpSearchModal" aria-label="{{ $label }}">
                <span class="bp-mbn-icon"><i class="{{ $icon ?: 'fas fa-search' }}"></i></span>
                <span class="bp-mbn-label">{{ $label }}</span>
            </button>
            @break
        @case('cart')
            <a href="{{ route('storefront.cart.index') }}" class="bp-mbn-item {{ request()->routeIs('storefront.cart.*') ? 'active' : '' }}">
                <span class="bp-mbn-icon"><i class="{{ $icon ?: 'fas fa-shopping-cart' }}"></i><span class="bp-mbn-badge cart-count cart-badge {{ $cartCount > 0 ? '' : 'd-none' }}">{{ $cartCount }}</span></span>
                <span class="bp-mbn-label">{{ $label }}</span>
            </a>
            @break
        @case('wishlist')
            <a href="{{ route('storefront.wishlist.index') }}" class="bp-mbn-item {{ request()->routeIs('storefront.wishlist.*') ? 'active' : '' }}">
                <span class="bp-mbn-icon"><i class="{{ $icon ?: 'fas fa-heart' }}"></i><span class="bp-mbn-badge wishlist-count {{ $wishlistCount > 0 ? '' : 'd-none' }}">{{ $wishlistCount }}</span></span>
                <span class="bp-mbn-label">{{ $label }}</span>
            </a>
            @break
        @case('account')
            @auth('customer')
                <a href="{{ route('storefront.customer.profile') }}" class="bp-mbn-item {{ request()->routeIs('storefront.customer.*') ? 'active' : '' }}">
                    <span class="bp-mbn-icon"><i class="{{ $icon ?: 'fas fa-user' }}"></i></span>
                    <span class="bp-mbn-label">{{ $label }}</span>
                </a>
            @else
                <a href="{{ route('storefront.customer.login') }}" class="bp-mbn-item {{ request()->routeIs('storefront.customer.*') ? 'active' : '' }}">
                    <span class="bp-mbn-icon"><i class="{{ $icon ?: 'fas fa-user' }}"></i></span>
                    <span class="bp-mbn-label">{{ $label }}</span>
                </a>
            @endauth
            @break
    @endswitch
@endif
