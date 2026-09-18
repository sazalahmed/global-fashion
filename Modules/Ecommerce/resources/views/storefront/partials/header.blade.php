{{-- Storefront Header (Zenis header_2) --}}
<header class="header_2">
    <div class="container">
        <div class="row align-items-center justify-content-between">
            <div class="col-xxl-2 col-xl-3 col-lg-3">
                <div class="header_logo_area">
                    <a href="{{ route('storefront.home') }}" class="header_logo">
                        <img src="{{ $companyLogo ?? asset('website/assets/images/logo_2.png') }}"
                            alt="{{ $companyName }}" class="img-fluid w-100">
                    </a>
                    <div class="mobile_menu_icon d-block d-lg-none" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">
                        <span class="mobile_menu_icon"><i class="fas fa-stream menu_icon_bar"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xxl-7 col-xl-6 col-lg-6 d-none d-lg-block">
                <form action="{{ route('storefront.shop.index') }}" method="GET"
                    class="bp-livesearch-form"
                    data-suggest-url="{{ route('storefront.search.suggest') }}"
                    data-currency="{{ currency_symbol() }}"
                    data-placeholder="{{ asset('website/assets/images/product_placeholder.png') }}">
                    <select name="category" class="select_2">
                        <option value="">All Categories</option>
                        @if (isset($menuCategories))
                            @foreach ($menuCategories as $cat)
                                <option value="{{ $cat->slug }}"
                                    {{ request('category') == $cat->slug ? 'selected' : '' }}>{{ $cat->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <div class="input">
                        <input type="text" name="q" placeholder="Search your product..."
                            value="{{ request('q') }}" autocomplete="off" class="bp-livesearch-input">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="bp-livesearch-results" hidden></div>
                </form>
            </div>
            <div class="col-xxl-2 col-xl-3 col-lg-3 d-none d-lg-flex justify-content-end">
                <div class="header_support_user d-flex flex-wrap">
                    <div class="header_support">
                        <span class="icon">
                            <i class="fas fa-phone-alt"></i>
                        </span>
                        @if ($companyPhone ?? false)
                            @php $hotlinePhone = \App\Helpers\PhoneHelper::formatIntl($companyPhone); @endphp
                            <h3>
                                Hotline:
                                <a href="tel:{{ $hotlinePhone }}">
                                    <span>{{ $hotlinePhone }}</span>
                                </a>
                            </h3>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
