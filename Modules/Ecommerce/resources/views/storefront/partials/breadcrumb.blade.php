{{-- Page Banner / Breadcrumb Section (Small Version) --}}
<div class="bp-simple-breadcrumb py-3">
    <div class="container">
        <ul class="d-flex align-items-center flex-wrap m-0 p-0">
            <li><a href="{{ route('storefront.home') }}" class="text-muted text-decoration-none">Home</a></li>
            @yield('breadcrumb')
        </ul>
    </div>
</div>

@push('styles')
<style>
    .bp-simple-breadcrumb {
        background: #fff;
        border-bottom: 1px solid #eee;
        margin-bottom: 30px;
    }
    .bp-simple-breadcrumb ul {
        list-style: none;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
    }
    .bp-simple-breadcrumb ul li {
        display: inline-flex;
        align-items: center;
        color: #6c757d;
    }
    .bp-simple-breadcrumb ul li a {
        color: #6c757d;
        text-decoration: none;
        transition: color 0.3s;
    }
    .bp-simple-breadcrumb ul li a:hover {
        color: var(--themeColorTwo, #e32c2b);
    }
    .bp-simple-breadcrumb ul li + li::before {
        content: "\f105"; /* FontAwesome angle-right */
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        margin-right: 8px;
        color: #999;
        font-size: 12px;
    }
    .bp-simple-breadcrumb ul li:last-child {
        color: var(--themeColorTwo, #e32c2b);
    }
    .bp-simple-breadcrumb ul li:last-child a {
        color: var(--themeColorTwo, #e32c2b);
        border-bottom: 1px solid var(--themeColorTwo, #e32c2b);
        padding-bottom: 2px;
    }
</style>
@endpush
