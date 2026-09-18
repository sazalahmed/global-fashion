{{-- Customer Dashboard Sidebar (Zenis Style) --}}
@php
    $customer = auth('customer')->user();
@endphp
<div class="dashboard_sidebar">
    <div class="dashboard_sidebar_area">
        <div class="dashboard_sidebar_user">
            <div class="img">
                @if($customer->photo)
                    <img src="{{ upload_url($customer->photo) }}" alt="{{ $customer->name }}" class="img-fluid w-100">
                @else
                    <img src="{{ asset('website/assets/images/dashboard_user_img.jpg') }}" alt="{{ $customer->name }}" class="img-fluid w-100">
                @endif
            </div>
            <h3>{{ $customer->name }}</h3>
            <p>{{ $customer->phone }}</p>
        </div>
        <div class="dashboard_sidebar_menu">
            <ul>
                <li>
                    <p>Dashboard</p>
                </li>
                <li>
                    <a class="{{ request()->routeIs('storefront.customer.profile') && !request()->routeIs('storefront.customer.profile.edit') ? 'active' : '' }}" href="{{ route('storefront.customer.profile') }}">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                            </svg>
                        </span>
                        Overview
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('storefront.customer.orders') ? 'active' : '' }}" href="{{ route('storefront.customer.orders') }}">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </span>
                        Orders
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('storefront.customer.wishlist') ? 'active' : '' }}" href="{{ route('storefront.customer.wishlist') }}">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                            </svg>
                        </span>
                        Wishlist
                    </a>
                </li>
                <li>
                    <p>Account Settings</p>
                </li>
                <li>
                    <a class="{{ request()->routeIs('storefront.customer.profile.edit') ? 'active' : '' }}" href="{{ route('storefront.customer.profile.edit') }}">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </span>
                        Personal Info
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('storefront.customer.password') ? 'active' : '' }}" href="{{ route('storefront.customer.password') }}">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </span>
                        Change Password
                    </a>
                </li>
                <li>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('dashboard-logout-form').submit();">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                            </svg>
                        </span>
                        Logout
                    </a>
                    <form id="dashboard-logout-form" action="{{ route('storefront.customer.logout') }}" method="POST" class="d-none">@csrf</form>
                </li>
            </ul>
        </div>
    </div>
</div>
