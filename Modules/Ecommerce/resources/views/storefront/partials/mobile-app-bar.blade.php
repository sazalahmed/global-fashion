{{-- Mobile App-Style Top Bar (visible on mobile only) --}}
<div class="bp-mobile-app-bar d-lg-none">
    <div class="bp-mab-top">
        <a href="{{ route('storefront.home') }}" class="bp-mab-logo" aria-label="{{ $companyName }}">
            @if(!empty($companyLogo))
                <img src="{{ $companyLogo }}" alt="{{ $companyName }}">
            @else
                <img src="{{ asset('website/assets/images/logo_2.png') }}" alt="{{ $companyName }}">
            @endif
        </a>
        @if($companyPhone ?? false)
            <a href="tel:{{ $companyPhone }}" class="bp-mab-call" aria-label="Call {{ $companyPhone }}">
                <i class="fas fa-phone-alt"></i>
            </a>
        @endif
        <button type="button" class="bp-mab-menu" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions"
                aria-label="Open menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</div>
