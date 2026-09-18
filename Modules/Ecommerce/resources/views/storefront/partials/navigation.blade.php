{{-- Storefront Main Navigation (Zenis main_menu_2) --}}
<nav class="main_menu_2 main_menu d-none d-lg-block">
    <div class="container">
        <div class="row">
            <div class="col-12 d-flex flex-wrap">
                <div class="main_menu_area">

                    {{-- Sticky Logo (shown on scroll via JS) — must match the header logo --}}
                    <a href="{{ route('storefront.home') }}" class="menu_logo d-none">
                        <img src="{{ $companyLogo ?? asset('website/assets/images/logo_2.png') }}" alt="{{ $companyName }}">
                    </a>

                    @if (!empty($mainMenu))
                        @php
                            $items    = collect($mainMenu);
                            $dropdown = $items->firstWhere('type', 'categories_dropdown');
                            $links    = $items->whereIn('type', ['route', 'category', 'url']);
                            $widgets  = $items->where('type', 'widget');
                            $catLimit = (int) ($dropdown['settings']['limit'] ?? 10);

                            $menuActive = function (array $item) {
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

                        {{-- Browse Categories Dropdown (live categories) --}}
                        @if ($dropdown)
                            <div class="menu_category_area">
                                <div class="menu_category_bar">
                                    <p>
                                        <span><img src="{{ asset('website/assets/images/bar_icon_white.svg') }}" alt="menu"></span>
                                        {{ $dropdown['label'] }}
                                    </p>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <ul class="menu_cat_item bp-cat-menu-clean">
                                    @if (isset($menuCategories) && $menuCategories->count())
                                        @foreach ($menuCategories->take($catLimit) as $category)
                                            <li>
                                                <a href="{{ route('storefront.category.show', $category->slug) }}">{{ $category->name }}</a>
                                                @if ($category->children && $category->children->count())
                                                    <ul class="menu_cat_droapdown">
                                                        @foreach ($category->children as $child)
                                                            <li>
                                                                <a href="{{ route('storefront.category.show', $child->slug) }}">{{ $child->name }}@if ($child->children && $child->children->count())<i class="fas fa-angle-right"></i>@endif</a>
                                                                @if ($child->children && $child->children->count())
                                                                    <ul class="sub_category">
                                                                        @foreach ($child->children as $grandChild)
                                                                            <li><a href="{{ route('storefront.category.show', $grandChild->slug) }}">{{ $grandChild->name }}</a></li>
                                                                        @endforeach
                                                                    </ul>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </li>
                                        @endforeach
                                    @endif
                                    <li class="all_category">
                                        <a href="{{ route('storefront.category.index') }}">View All Categories <i class="fas fa-angle-right"></i></a>
                                    </li>
                                </ul>
                            </div>
                        @endif

                        {{-- Main Menu Items --}}
                        <ul class="menu_item">
                            @foreach ($links as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" @if (($item['target'] ?? '_self') !== '_self') target="{{ $item['target'] }}" @endif class="{{ $menuActive($item) ? 'active' : '' }} {{ $item['css_class'] }}">{{ $item['label'] }}</a>
                                    @if (!empty($item['children']))
                                        <ul class="menu_cat_droapdown">
                                            @foreach ($item['children'] as $child)
                                                <li><a href="{{ $child['url'] }}" @if (($child['target'] ?? '_self') !== '_self') target="{{ $child['target'] }}" @endif>{{ $child['label'] }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        {{-- Right Side Icons (widgets) --}}
                        <ul class="menu_icon">
                            @foreach ($widgets as $w)
                                @include('ecommerce::storefront.partials.menu-widget', ['widget' => $w['value'], 'item' => $w, 'style' => 'main'])
                            @endforeach
                        </ul>

                    @else
                        {{-- ── Fallback: original hardcoded markup (used until a menu is seeded) ── --}}
                        <div class="menu_category_area">
                            <div class="menu_category_bar">
                                <p>
                                    <span><img src="{{ asset('website/assets/images/bar_icon_white.svg') }}" alt="menu"></span>
                                    Browse Categories
                                </p>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <ul class="menu_cat_item bp-cat-menu-clean">
                                @if (isset($menuCategories) && $menuCategories->count())
                                    @foreach ($menuCategories->take(10) as $category)
                                        <li>
                                            <a href="{{ route('storefront.category.show', $category->slug) }}">{{ $category->name }}</a>
                                            @if ($category->children && $category->children->count())
                                                <ul class="menu_cat_droapdown">
                                                    @foreach ($category->children as $child)
                                                        <li>
                                                            <a href="{{ route('storefront.category.show', $child->slug) }}">{{ $child->name }}@if ($child->children && $child->children->count())<i class="fas fa-angle-right"></i>@endif</a>
                                                            @if ($child->children && $child->children->count())
                                                                <ul class="sub_category">
                                                                    @foreach ($child->children as $grandChild)
                                                                        <li><a href="{{ route('storefront.category.show', $grandChild->slug) }}">{{ $grandChild->name }}</a></li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                @endif
                                <li class="all_category">
                                    <a href="{{ route('storefront.category.index') }}">View All Categories <i class="fas fa-angle-right"></i></a>
                                </li>
                            </ul>
                        </div>

                        <ul class="menu_item">
                            <li><a href="{{ route('storefront.home') }}" class="{{ request()->routeIs('storefront.home') ? 'active' : '' }}">Home</a></li>
                            <li><a href="{{ route('storefront.shop.index') }}" class="{{ request()->routeIs('storefront.shop.*') ? 'active' : '' }}">Shop</a></li>
                            <li><a href="{{ route('storefront.category.index') }}" class="{{ request()->routeIs('storefront.category.*') ? 'active' : '' }}">Categories</a></li>
                            <li><a href="{{ route('storefront.flash-deals') }}" class="{{ request()->routeIs('storefront.flash-deals') ? 'active' : '' }}">Flash Deals</a></li>
                            <li><a href="{{ route('storefront.blog.index') }}" class="{{ request()->routeIs('storefront.blog.*') ? 'active' : '' }}">Blog</a></li>
                            <li><a href="{{ route('storefront.contact') }}" class="{{ request()->routeIs('storefront.contact') ? 'active' : '' }}">Contact</a></li>
                        </ul>

                        <ul class="menu_icon">
                            @include('ecommerce::storefront.partials.menu-widget', ['widget' => 'wishlist', 'item' => [], 'style' => 'main'])
                            @include('ecommerce::storefront.partials.menu-widget', ['widget' => 'compare', 'item' => [], 'style' => 'main'])
                            @include('ecommerce::storefront.partials.menu-widget', ['widget' => 'cart', 'item' => [], 'style' => 'main'])
                            @include('ecommerce::storefront.partials.menu-widget', ['widget' => 'account', 'item' => [], 'style' => 'main'])
                        </ul>
                    @endif

                </div>
            </div>
        </div>
    </div>
</nav>
