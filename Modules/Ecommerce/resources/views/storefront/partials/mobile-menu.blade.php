{{-- Storefront Mobile Menu (Zenis) --}}
<div class="mobile_menu_area">
    <div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1" id="offcanvasWithBothOptions">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"><i
                class="fas fa-times"></i></button>
        <div class="offcanvas-body">

            {{-- Tabs: Categories | Menu --}}
            <div class="mobile_menu_item_area">
                <ul class="nav nav-pills" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
                            aria-selected="true">Categories</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile"
                            aria-selected="false">Menu</button>
                    </li>
                </ul>
                <div class="tab-content" id="pills-tabContent">
                    {{-- Categories Tab (always live) --}}
                    <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                        aria-labelledby="pills-home-tab">
                        <ul class="main_mobile_menu">
                            @if (isset($menuCategories) && $menuCategories->count())
                                @foreach ($menuCategories as $category)
                                    <li
                                        class="{{ $category->children && $category->children->count() ? 'mobile_dropdown' : '' }}">
                                        <a
                                            href="{{ route('storefront.category.show', $category->slug) }}">{{ $category->name }}</a>
                                        @if ($category->children && $category->children->count())
                                            <span class="mobile_cat_toggle" role="button" aria-label="{{ __('Expand') }}" tabindex="0"></span>
                                            <ul class="inner_menu">
                                                @foreach ($category->children as $child)
                                                    <li><a
                                                            href="{{ route('storefront.category.show', $child->slug) }}">{{ $child->name }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>

                    {{-- Menu Tab (admin-managed, with fallback) --}}
                    <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
                        {{-- Mirror the desktop primary nav ($mainMenu) so the mobile
                             "Menu" tab is identical to the desktop menu. Widgets and
                             the categories dropdown are excluded (categories have their
                             own tab; widget actions live in the bottom nav). --}}
                        <ul class="main_mobile_menu">
                            @if (!empty($mainMenu))
                                @foreach ($mainMenu as $item)
                                    @continue(in_array($item['type'], ['widget', 'heading', 'categories_dropdown'], true))
                                    <li class="{{ !empty($item['children']) ? 'mobile_dropdown' : '' }}">
                                        <a href="{{ $item['url'] ?? '#' }}"
                                            @if (($item['target'] ?? '_self') !== '_self') target="{{ $item['target'] }}" @endif>{{ $item['label'] }}</a>
                                        @if (!empty($item['children']))
                                            <span class="mobile_cat_toggle" role="button" aria-label="{{ __('Expand') }}" tabindex="0"></span>
                                            <ul class="inner_menu">
                                                @foreach ($item['children'] as $child)
                                                    <li><a href="{{ $child['url'] ?? '#' }}"
                                                            @if (($child['target'] ?? '_self') !== '_self') target="{{ $child['target'] }}" @endif>{{ $child['label'] }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            @else
                                <li><a href="{{ route('storefront.home') }}">Home</a></li>
                                <li><a href="{{ route('storefront.shop.index') }}">Shop</a></li>
                                <li><a href="{{ route('storefront.category.index') }}">Categories</a></li>
                                <li><a href="{{ route('storefront.flash-deals') }}">Flash Deals</a></li>
                                <li><a href="{{ route('storefront.blog.index') }}">Blog</a></li>
                                <li><a href="{{ route('storefront.contact') }}">Contact</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
