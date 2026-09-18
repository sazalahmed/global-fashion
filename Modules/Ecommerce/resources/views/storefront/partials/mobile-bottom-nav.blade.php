{{-- Mobile App-Style Bottom Navigation (visible on mobile only) --}}
@php
    $cartCount = session('cart') ? count(session('cart')) : 0;

    // Active-state for a bottom-nav link item (route type).
    $bottomActive = function (array $item) {
        if (($item['type'] ?? null) === 'route' && !empty($item['value'])) {
            $patterns = [$item['value']];
            if (str_ends_with($item['value'], '.index')) {
                $patterns[] = substr($item['value'], 0, -6) . '.*';
            }
            return request()->routeIs(...$patterns);
        }
        $url = $item['url'] ?? null;
        return $url && $url !== '#' && request()->url() === $url;
    };
@endphp
<nav class="bp-mobile-bottom-nav d-lg-none" aria-label="Mobile navigation">
    @if(!empty($bottomNav))
        @foreach($bottomNav as $item)
            @if($item['type'] === 'widget')
                @include('ecommerce::storefront.partials.menu-widget', ['widget' => $item['value'], 'item' => $item, 'style' => 'bottom'])
            @else
                <a href="{{ $item['url'] ?? '#' }}" class="bp-mbn-item {{ $bottomActive($item) ? 'active' : '' }}" @if(($item['target'] ?? '_self') !== '_self') target="{{ $item['target'] }}" @endif>
                    <span class="bp-mbn-icon"><i class="{{ $item['icon'] ?: 'fas fa-circle' }}"></i></span>
                    <span class="bp-mbn-label">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    @else
        {{-- ── Fallback: original hardcoded bar ── --}}
        @php
            $isHome = request()->routeIs('storefront.home');
            $isCategories = request()->routeIs('storefront.category.*');
            $isCart = request()->routeIs('storefront.cart.*');
            $isProfile = request()->routeIs('storefront.customer.*');
        @endphp
        <a href="{{ route('storefront.home') }}" class="bp-mbn-item {{ $isHome ? 'active' : '' }}">
            <span class="bp-mbn-icon"><i class="fas fa-home"></i></span>
            <span class="bp-mbn-label">Home</span>
        </a>
        <a href="{{ route('storefront.category.index') }}" class="bp-mbn-item {{ $isCategories ? 'active' : '' }}">
            <span class="bp-mbn-icon"><i class="fas fa-th-large"></i></span>
            <span class="bp-mbn-label">Categories</span>
        </a>
        <button type="button" class="bp-mbn-item bp-mbn-btn" data-bs-toggle="modal" data-bs-target="#bpSearchModal" aria-label="Open search">
            <span class="bp-mbn-icon"><i class="fas fa-search"></i></span>
            <span class="bp-mbn-label">Search</span>
        </button>
        <a href="{{ route('storefront.cart.index') }}" class="bp-mbn-item {{ $isCart ? 'active' : '' }}">
            <span class="bp-mbn-icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="bp-mbn-badge cart-count cart-badge {{ $cartCount > 0 ? '' : 'd-none' }}">{{ $cartCount }}</span>
            </span>
            <span class="bp-mbn-label">Cart</span>
        </a>
        @auth('customer')
            <a href="{{ route('storefront.customer.profile') }}" class="bp-mbn-item {{ $isProfile ? 'active' : '' }}">
                <span class="bp-mbn-icon"><i class="fas fa-user"></i></span>
                <span class="bp-mbn-label">Profile</span>
            </a>
        @else
            <a href="{{ route('storefront.customer.login') }}" class="bp-mbn-item {{ $isProfile ? 'active' : '' }}">
                <span class="bp-mbn-icon"><i class="fas fa-user"></i></span>
                <span class="bp-mbn-label">Profile</span>
            </a>
        @endauth
    @endif
</nav>

{{-- Search Popup Modal --}}
@push('scripts')
<script>
'use strict';
(function () {
    var modalEl = document.getElementById('bpSearchModal');
    if (!modalEl) return;
    modalEl.addEventListener('shown.bs.modal', function () {
        var input = modalEl.querySelector('.bp-search-input');
        if (input) {
            input.focus();
            var v = input.value;
            input.value = '';
            input.value = v;
        }
    });
})();
</script>
@endpush
<div class="modal fade bp-search-modal" id="bpSearchModal" tabindex="-1" aria-labelledby="bpSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bpSearchModalLabel">Search Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="bp-search-form bp-livesearch-form" action="{{ route('storefront.shop.index') }}" method="GET"
                    data-suggest-url="{{ route('storefront.search.suggest') }}"
                    data-currency="{{ currency_symbol() }}"
                    data-placeholder="{{ asset('website/assets/images/product_placeholder.png') }}">
                    <div class="bp-search-input-wrap">
                        <i class="fas fa-search bp-search-input-icon"></i>
                        <input type="text" name="q" class="bp-search-input bp-livesearch-input" placeholder="Search products..." value="{{ request('q') }}" autocomplete="off">
                    </div>
                    <div class="bp-livesearch-results" hidden></div>
                    @if(isset($menuCategories) && $menuCategories->count())
                        <label class="bp-search-label">Category</label>
                        <select name="category" class="bp-search-select">
                            <option value="">All Categories</option>
                            @foreach($menuCategories as $cat)
                                <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button type="submit" class="bp-search-submit"><i class="fas fa-search me-2"></i>Search</button>
                </form>
            </div>
        </div>
    </div>
</div>
