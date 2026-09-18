@extends('core::layouts.master')

@section('title', __("Ecommerce"))
@section('page-title', __("Ecommerce Dashboard"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Ecommerce</span>
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-cart-shopping"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Orders</div>
        <div class="bp-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Pending</div>
        <div class="bp-stat-value">{{ number_format($stats['pending'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-info"><i class="fa-solid fa-truck"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Processing</div>
        <div class="bp-stat-value">{{ number_format($stats['processing'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Completed</div>
        <div class="bp-stat-value">{{ number_format($stats['completed'] ?? 0) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Links -->
<div class="row g-3">

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('sales.index', ['source' => 'ecommerce']) }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-primary mx-auto mb-3"><i class="fa-solid fa-cart-shopping"></i></div>
          <h6 class="fw-800 mb-1">Orders</h6>
          <p class="fs-12 text-muted mb-0">Manage in the Sales list</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('ecommerce.products') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-secondary mx-auto mb-3"><i class="fa-solid fa-boxes-stacked"></i></div>
          <h6 class="fw-800 mb-1">Product Sync</h6>
          <p class="fs-12 text-muted mb-0">Sync products to storefront</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('ecommerce.shipping') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-warning mx-auto mb-3"><i class="fa-solid fa-truck-fast"></i></div>
          <h6 class="fw-800 mb-1">Shipping Zones</h6>
          <p class="fs-12 text-muted mb-0">Configure shipping rates</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('ecommerce.coupons') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-danger mx-auto mb-3"><i class="fa-solid fa-ticket"></i></div>
          <h6 class="fw-800 mb-1">Coupons</h6>
          <p class="fs-12 text-muted mb-0">Manage discount coupons</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('ecommerce.banners') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-info mx-auto mb-3"><i class="fa-solid fa-images"></i></div>
          <h6 class="fw-800 mb-1">Banners</h6>
          <p class="fs-12 text-muted mb-0">Homepage banners & sliders</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('ecommerce.homepage-sections') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-primary mx-auto mb-3"><i class="fa-solid fa-layer-group"></i></div>
          <h6 class="fw-800 mb-1">Manage Sections</h6>
          <p class="fs-12 text-muted mb-0">Customize storefront sections</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('ecommerce.collections') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-secondary mx-auto mb-3"><i class="fa-solid fa-object-group"></i></div>
          <h6 class="fw-800 mb-1">Collections</h6>
          <p class="fs-12 text-muted mb-0">Curated product collections</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('ecommerce.flash-deals') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-danger mx-auto mb-3"><i class="fa-solid fa-bolt"></i></div>
          <h6 class="fw-800 mb-1">Flash Deals</h6>
          <p class="fs-12 text-muted mb-0">Time-limited promotions</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('ecommerce.courier-providers') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-dark mx-auto mb-3"><i class="fa-solid fa-truck-ramp-box"></i></div>
          <h6 class="fw-800 mb-1">Courier Providers</h6>
          <p class="fs-12 text-muted mb-0">Configure Pathao, Steadfast, Redx</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('ecommerce.settings') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-dark mx-auto mb-3"><i class="fa-solid fa-gears"></i></div>
          <h6 class="fw-800 mb-1">Settings</h6>
          <p class="fs-12 text-muted mb-0">Storefront & payment config</p>
        </div>
      </div>
    </a>
  </div>

</div>

@endsection
