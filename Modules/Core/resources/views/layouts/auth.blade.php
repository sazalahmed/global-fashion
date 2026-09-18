<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="csrf-refresh-url" content="{{ route('csrf.token') }}">
  <title>@yield('title', 'Login') - {{ $companyName }}</title>
  <link rel="icon" type="image/png" href="{{ $companyFavicon ?? asset('website/assets/images/favicon.png') }}">
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}" rel="stylesheet">
  @stack('styles')
</head>
<body>

<div class="bp-auth-wrapper">

  <!-- ═══════════════ LEFT PANEL ═══════════════ -->
  <div class="bp-auth-left">

    <!-- Logo -->
    <div>
      <div class="bp-auth-logo">{{ $companyName }}</div>
      <div class="bp-auth-logo-tagline">POS &bull; Accounting &bull; eCommerce</div>
    </div>

    <!-- Body -->
    <div class="bp-auth-left-body">
      <div class="bp-auth-tagline">@yield('left-tagline', 'All-in-one business<br>management system')</div>
      <div class="bp-auth-tagline-sub">
        @yield('left-tagline-sub', __('Built for Bangladeshi SMBs — manage your store, stock, accounts, and online orders from one place.'))
      </div>

      <ul class="bp-auth-feat-list">
        @yield('left-features')
      </ul>
    </div>

    <!-- Left footer -->
    <div class="bp-auth-left-footer">
      &copy; {{ date('Y') }} {{ $companyName }}
    </div>
  </div>

  <!-- ═══════════════ RIGHT PANEL ═══════════════ -->
  <div class="bp-auth-right">

    <!-- Dark mode toggle -->
    <button class="bp-auth-theme-btn" id="themeToggle" title="Toggle dark mode">
      <i class="fa-solid fa-moon" id="themeIcon"></i>
    </button>

    <div class="bp-auth-form-box">

      <!-- Flash Messages -->
      @if(session('success'))
      <div class="bp-auth-alert bp-auth-alert-success" role="alert">
        <i class="fa-solid fa-check-circle"></i>
        <span>{{ session('success') }}</span>
      </div>
      @endif

      @if(session('error'))
      <div class="bp-auth-alert bp-auth-alert-error" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span>{{ session('error') }}</span>
      </div>
      @endif

      <!-- Auth Content -->
      @yield('content')

    </div>
  </div><!-- /right -->

</div><!-- /wrapper -->

<!-- Scripts -->
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
<script src="{{ asset('js/csrf.js') }}?v={{ filemtime(public_path('js/csrf.js')) }}"></script>
<script>
'use strict';

/* ── Dark mode ── */
(function () {
    'use strict';
    applyTheme(localStorage.getItem('bpTheme') || '{{ session('theme', 'light') }}');
    $('#themeToggle').on('click', function () {
        var t = $('html').attr('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(t);
        localStorage.setItem('bpTheme', t);
    });
    function applyTheme(t) {
        $('html').attr('data-theme', t);
        $('#themeIcon').toggleClass('fa-moon', t === 'light').toggleClass('fa-sun', t === 'dark');
    }
})();
</script>
@stack('scripts')
</body>
</html>
