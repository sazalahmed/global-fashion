<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 — Page Not Found - {{ $companyName ?? \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro') }}</title>
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>
<body>

<div class="bp-admin-error-page">
  <div class="bp-admin-error-container">
    <div class="bp-admin-error-brand">
      <h4><span>Biz</span><span class="brand-accent">POS Pro</span></h4>
    </div>

    <div class="bp-admin-error-illustration">
      <div class="bp-admin-error-code-bg">404</div>
      <div class="bp-admin-error-icon">
        <i class="fa-solid fa-map-signs"></i>
      </div>
    </div>

    <h2 class="bp-admin-error-title">Page Not Found</h2>
    <p class="bp-admin-error-desc">The page you are looking for doesn't exist or has been moved. Check the URL or navigate back to safety.</p>

    <div class="bp-admin-error-actions">
      <a href="{{ route('dashboard') }}" class="bp-btn bp-btn-primary bp-btn-lg">
        <i class="fa-solid fa-gauge-high me-2"></i> Go to Dashboard
      </a>
      <button onclick="history.back()" class="bp-btn bp-btn-outline bp-btn-lg">
        <i class="fa-solid fa-arrow-left me-2"></i> Go Back
      </button>
    </div>

    <div class="bp-admin-error-links">
      <a href="{{ route('sales.index') }}"><i class="fa-solid fa-chart-line me-1"></i> Sales</a>
      <a href="{{ route('products.index') }}"><i class="fa-solid fa-boxes-stacked me-1"></i> Products</a>
      <a href="{{ route('customers.index') }}"><i class="fa-solid fa-users me-1"></i> Customers</a>
    </div>

    <div class="bp-admin-error-footer">
      <span>{{ $companyName ?? \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro') }}</span> &middot; <span class="text-muted">Requested: {{ request()->path() }}</span>
    </div>
  </div>
</div>

</body>
</html>
