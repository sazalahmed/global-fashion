@extends('core::layouts.master')

@section('title', __("Reports"))
@section('page-title', __("Reports & Analytics"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Reports</span>
@endsection

@section('content')

<div class="row g-3">

  <!-- Sales Report -->
  <div class="col-md-4 col-lg-3">
    <a href="{{ route('reports.sales') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-primary mx-auto mb-3"><i class="fa-solid fa-chart-line"></i></div>
          <h6 class="fw-800 mb-1">Sales Report</h6>
          <p class="fs-12 text-muted mb-0">Daily, monthly, by product, customer, branch</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Purchase Report -->
  <div class="col-md-4 col-lg-3">
    <a href="{{ route('reports.purchase') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-secondary mx-auto mb-3"><i class="fa-solid fa-cart-shopping"></i></div>
          <h6 class="fw-800 mb-1">Purchase Report</h6>
          <p class="fs-12 text-muted mb-0">Purchase orders, supplier analysis, trends</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Inventory Report -->
  <div class="col-md-4 col-lg-3">
    <a href="{{ route('reports.inventory') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-warning mx-auto mb-3"><i class="fa-solid fa-boxes-stacked"></i></div>
          <h6 class="fw-800 mb-1">Inventory Report</h6>
          <p class="fs-12 text-muted mb-0">Stock levels, movement, valuation, alerts</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Financial Report -->
  <div class="col-md-4 col-lg-3">
    <a href="{{ route('reports.financial') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-success mx-auto mb-3"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
          <h6 class="fw-800 mb-1">Financial Report</h6>
          <p class="fs-12 text-muted mb-0">Income, expenses, profit summary</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Staff Report -->
  <div class="col-md-4 col-lg-3">
    <a href="{{ route('reports.staff') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-info mx-auto mb-3"><i class="fa-solid fa-user-tie"></i></div>
          <h6 class="fw-800 mb-1">Staff Report</h6>
          <p class="fs-12 text-muted mb-0">Sales performance, attendance, productivity</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Customer Report -->
  <div class="col-md-4 col-lg-3">
    <a href="{{ route('reports.customer') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-primary mx-auto mb-3"><i class="fa-solid fa-users"></i></div>
          <h6 class="fw-800 mb-1">Customer Report</h6>
          <p class="fs-12 text-muted mb-0">Top customers, due balances, loyalty</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Custom Report Builder -->
  <div class="col-md-4 col-lg-3">
    <a href="{{ route('reports.custom') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-dark mx-auto mb-3"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
          <h6 class="fw-800 mb-1">Custom Report</h6>
          <p class="fs-12 text-muted mb-0">Build custom reports with flexible filters</p>
        </div>
      </div>
    </a>
  </div>

</div>

@endsection
