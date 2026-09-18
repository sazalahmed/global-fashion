<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="csrf-refresh-url" content="{{ route('csrf.token') }}">
  <title>POS Terminal - {{ $companyName }}</title>
  <link rel="icon" type="image/png" href="{{ $companyFavicon ?? asset('website/assets/images/favicon.png') }}">
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}" rel="stylesheet">
  @stack('styles')
</head>
<body class="bp-pos-page">

@include('core::partials.sidebar')

<div class="bp-overlay"></div>

@include('core::partials.header')

<!-- Offline Indicator -->
<div class="bp-pos-offline-bar" id="offlineBar">
  <i class="fa-solid fa-wifi-slash"></i>
  You are offline. Sales will be queued and synced when connection is restored.
  <span class="bp-pos-queued-badge d-none" id="offlineQueueBadge">0</span>
</div>

<!-- POS Layout -->
<div class="bp-pos-layout">
  @yield('content')
</div>

<!-- Keyboard Shortcuts Info -->
<div class="bp-pos-shortcuts d-none d-lg-block">
  <div class="d-flex gap-3">
    <span><kbd>F1</kbd> Search</span>
    <span><kbd>F2</kbd> Hold</span>
    <span><kbd>F3</kbd> Pay</span>
    <span><kbd>F4</kbd> Recall</span>
    <span><kbd>F8</kbd> Clear</span>
    <span><kbd>Esc</kbd> Cancel</span>
  </div>
</div>

<!-- Scripts -->
<script>
'use strict';
window.BizPOS = window.BizPOS || {};
window.BizPOS.phone = @json(\App\Helpers\PhoneHelper::getConfig());
</script>
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
<script src="{{ asset('js/csrf.js') }}?v={{ filemtime(public_path('js/csrf.js')) }}"></script>
<script src="{{ asset('js/pos-offline.js') }}"></script>
@stack('scripts')
</body>
</html>
