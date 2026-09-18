{{-- Storefront Footer (Zenis footer_2 Style) --}}
@php
    // Admin-managed footer background (Settings → eCommerce → Footer). Falls
    // back to the bundled theme asset when no image has been uploaded.
    $footerBg = \Modules\Ecommerce\Models\EcommerceSetting::get('footer_bg_image') ?: 'website/assets/images/footer_2_bg_2.jpg';
@endphp
<footer class="footer_2 pt_70" style="{!! bg_image_set($footerBg) !!}">
    <div class="container">
        <div class="row justify-content-between">
            <div class="col-xl-3 col-md-6 col-lg-3">
                <div class="footer_2_logo_area">
                    <a class="footer_logo" href="{{ route('storefront.home') }}">
                        <img src="{{ $companyLogo ?? asset('website/assets/images/footer_logo_2.png') }}"
                            alt="{{ $companyName }}" class="img-fluid w-100">
                    </a>
                    <p>{{ config('ecommerce.footer_description', 'Your trusted online shop for quality products at the best prices in Bangladesh.') }}
                    </p>
                    @php
                        $socialIcons = [
                            'facebook' => 'fa-facebook-f',
                            'instagram' => 'fa-instagram',
                            'youtube' => 'fa-youtube',
                            'twitter' => 'fa-x-twitter',
                            'linkedin' => 'fa-linkedin-in',
                            'tiktok' => 'fa-tiktok',
                            'whatsapp' => 'fa-whatsapp',
                        ];
                    @endphp
                    @if (!empty($companySocial))
                        <ul>
                            <li><span>Follow :</span></li>
                            @foreach ($socialIcons as $key => $icon)
                                @if (!empty($companySocial[$key]))
                                    <li><a href="{{ $companySocial[$key] }}" target="_blank" rel="noopener"><i
                                                class="fab {{ $icon }}"></i></a></li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            @php $footerCols = !empty($footerMenu) ? collect($footerMenu)->where('type', 'heading')->values() : collect(); @endphp

            @if ($footerCols->isNotEmpty())
                {{-- Admin-managed link columns (one per heading item) --}}
                @foreach ($footerCols as $i => $col)
                    <div class="col-xl-2 col-sm-6 col-md-4 col-lg-2">
                        <div class="footer_link">
                            <h3>{{ $col['label'] }}</h3>
                            <ul>
                                @foreach ($col['children'] as $child)
                                    @if ($child['type'] === 'categories_dropdown')
                                        @php $lim = (int) ($child['settings']['limit'] ?? 5); @endphp
                                        @if (isset($footerCategories) && $footerCategories->count())
                                            @foreach ($footerCategories->take($lim) as $category)
                                                <li><a
                                                        href="{{ route('storefront.category.show', $category->slug) }}">{{ $category->name }}</a>
                                                </li>
                                            @endforeach
                                        @endif
                                    @else
                                        <li><a href="{{ $child['url'] }}"
                                                @if (($child['target'] ?? '_self') !== '_self') target="{{ $child['target'] }}" @endif>{{ $child['label'] }}</a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            @else
                {{-- ── Fallback: original hardcoded columns ── --}}
                <div class="col-xl-2 col-sm-6 col-md-4 col-lg-2">
                    <div class="footer_link">
                        <h3>Company</h3>
                        <ul>
                            <li><a href="{{ route('storefront.shop.index') }}">Shop</a></li>
                            <li><a href="{{ route('storefront.category.index') }}">Categories</a></li>
                            <li><a href="{{ route('storefront.blog.index') }}">Blog</a></li>
                            @if ($customerAuthEnabled ?? false)
                                @guest('customer')
                                    <li><a href="{{ route('storefront.customer.login') }}">Login</a></li>
                                @else
                                    <li><a href="{{ route('storefront.customer.profile') }}">My Account</a></li>
                                @endguest
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="col-xl-2 col-sm-6 col-md-4 col-lg-2">
                    <div class="footer_link">
                        <h3>Category</h3>
                        <ul>
                            @if (isset($footerCategories) && $footerCategories->count())
                                @foreach ($footerCategories->take(5) as $category)
                                    <li><a
                                            href="{{ route('storefront.category.show', $category->slug) }}">{{ $category->name }}</a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="col-xl-2 col-sm-6 col-md-4 col-lg-2">
                    <div class="footer_link">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="{{ route('storefront.flash-deals') }}">Flash Deals</a></li>
                            <li><a href="{{ route('storefront.wishlist.index') }}">Wishlist</a></li>
                            <li><a href="{{ route('storefront.cart.index') }}">Cart</a></li>
                            <li><a href="{{ route('storefront.faq.index') }}">FAQ's</a></li>
                        </ul>
                    </div>
                </div>
            @endif

            <div class="col-xl-3 col-sm-6 col-md-4 col-lg-3">
                <div class="footer_link footer_logo_area">
                    <h3>Contact Us</h3>
                    @if ($companyAddress ?? false)
                        <span>
                            <b><img src="{{ asset('website/assets/images/location_icon_white.png') }}" alt="Map"
                                    class="img-fluid"></b>
                            {{ $companyAddress }}
                        </span>
                    @endif
                    @if ($companyPhone ?? false)
                        @php $footerPhone = \App\Helpers\PhoneHelper::formatIntl($companyPhone); @endphp
                        <span>
                            <b><img src="{{ asset('website/assets/images/phone_icon_white.png') }}" alt="Call"
                                    class="img-fluid"></b>
                            <a href="tel:{{ $footerPhone }}">{{ $footerPhone }}</a>
                        </span>
                    @endif
                    @if ($companyEmail ?? false)
                        <span>
                            <b><img src="{{ asset('website/assets/images/mail_icon_white.png') }}" alt="Mail"
                                    class="img-fluid"></b>
                            <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a>
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                @php
                    $paymentIcon = \Modules\Ecommerce\Models\EcommerceSetting::get('payment_icon');
                @endphp
                <div class="footer_copyright mt_60{{ empty($paymentIcon) ? ' justify-content-center' : '' }}">
                    <p>Copyright @ <b>{{ config('app.name', 'BizPOS Pro') }}</b> {{ date('Y') }}. All right
                        reserved.</p>
                    @if (!empty($paymentIcon))
                        <ul class="payment">
                            <li>Payment by :</li>
                            <li><img src="{{ upload_url($paymentIcon) }}" alt="payment" class="img-fluid w-100"></li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</footer>
