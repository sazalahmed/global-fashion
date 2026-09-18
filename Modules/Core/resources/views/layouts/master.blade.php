<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="csrf-refresh-url" content="{{ route('csrf.token') }}">
  <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
  <title>@yield('title', 'Dashboard') - {{ $companyName }}</title>
  <!-- PWA -->
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <meta name="theme-color" content="#1B4F72">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="{{ $companyName }}">
  <link rel="icon" type="image/png" href="{{ $companyFavicon ?? asset('website/assets/images/favicon.png') }}">
  <link rel="apple-touch-icon" href="{{ $companyFavicon ?? asset('images/icons/icon-192x192.png') }}">
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/select2/css/select2.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/tagify/css/tagify.css') }}" rel="stylesheet">
  <link href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}" rel="stylesheet">
  @stack('styles')
</head>
<body>

<!-- ============================================================
     SIDEBAR
     ============================================================ -->
@include('core::partials.sidebar')

<!-- ============================================================
     OVERLAY (Mobile)
     ============================================================ -->
<div class="bp-overlay"></div>

<!-- ============================================================
     HEADER
     ============================================================ -->
@include('core::partials.header')

<!-- ============================================================
     MAIN CONTENT
     ============================================================ -->
<main class="bp-main">

  <!-- Page Header -->
  <div class="bp-page-header">
    <div>
      <h1 class="bp-page-title">@yield('page-title', 'Dashboard')</h1>
      <div class="bp-breadcrumb">
        <a href="{{ route('dashboard') }}">Home</a>
        @yield('breadcrumb')
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      @yield('page-actions')
    </div>
  </div>

  <!-- Flash Messages -->
  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  @if(session('warning'))
  <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
    <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  @if(session('info'))
  <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
    <i class="fa-solid fa-circle-info me-2"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  @if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="fa-solid fa-circle-exclamation me-2"></i>
    <strong>Please fix the following errors:</strong>
    <ul class="mb-0 mt-1">
      @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  <!-- Page Content -->
  @yield('content')

</main>

<!-- Scripts -->
<script>
'use strict';
window.BizPOS = window.BizPOS || {};
window.BizPOS.phone = @json(\App\Helpers\PhoneHelper::getConfig());
window.BizPOS.pusher = {
  enabled: @json(config('broadcasting.default') === 'pusher' && !empty(config('broadcasting.connections.pusher.key'))),
  key:     @json(config('broadcasting.connections.pusher.key')),
  cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
};
// Built from the named routes so the dropdown follows the routes if they move
// again — it broke silently when notifications went under /admin.
window.BizPOS.notifications = {
  index:        @json(route('notifications.index')),
  markAllRead:  @json(route('notifications.markAllRead')),
  read:         @json(route('notifications.read', ['id' => '__ID__'])),
};
</script>
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
'use strict';
// Bootstrap 5 dropped jQuery plugin wrappers. Re-expose Tooltip/Popover as $.fn so legacy code (e.g. summernote-bs5) keeps working.
['Tooltip', 'Popover'].forEach(function (name) {
    if (window.bootstrap && window.bootstrap[name] && window.jQuery && !jQuery.fn[name.toLowerCase()]) {
        jQuery.fn[name.toLowerCase()] = function (config) {
            return this.each(function () {
                var inst = bootstrap[name].getOrCreateInstance(this, typeof config === 'object' ? config : {});
                if (typeof config === 'string' && typeof inst[config] === 'function') inst[config]();
            });
        };
    }
});
</script>
@if(auth()->check() && config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key'))
<script src="{{ asset('vendor/pusher/pusher.min.js') }}"></script>
@endif
@auth
<script src="{{ asset('js/admin-notifications.js') }}?v={{ filemtime(public_path('js/admin-notifications.js')) }}" defer></script>
@endauth
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
<script src="{{ asset('js/global-search.js') }}?v={{ filemtime(public_path('js/global-search.js')) }}"></script>
{{-- Centralized rich-text editor: any page with a .bp-richtext textarea gets TinyMCE (lazy-loaded). --}}
<script>window.BP_TINYMCE_SRC = '{{ asset('vendor/tinymce/tinymce.min.js') }}';</script>
<script src="{{ asset('js/bp-richtext.js') }}?v={{ filemtime(public_path('js/bp-richtext.js')) }}"></script>
@superAdmin
<script src="{{ asset('js/push-notifications.js') }}?v={{ filemtime(public_path('js/push-notifications.js')) }}" defer></script>
@endsuperAdmin
<script src="{{ asset('js/csrf.js') }}?v={{ filemtime(public_path('js/csrf.js')) }}"></script>

{{-- Global Select2 for searchable dropdowns. Auto-targets any select with
     class .select2-search, plus district / thana / area selects by name. --}}
<script src="{{ asset('vendor/select2/js/select2.min.js') }}"></script>
<script>
'use strict';
(function () {
    if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') return;
    var selector = 'select.select2-search,'
        + 'select[name="district"], select[name="district_id"],'
        + 'select[name="thana"], select[name="thana_id"],'
        + 'select[name="area"], select[name="area_id"],'
        + 'select[name="upazila"], select[name="upazila_id"]';
    // Accepts either a container (search descendants) or a single <select>
    // (init it directly). Pages with dynamically inserted rows can call
    // bpInitSelect2($row) or bpInitSelect2($select) after the DOM changes.
    function initSelect2(scope) {
        var $root = $(scope || document);
        var $targets = $root.is(selector) ? $root : $root.find(selector);
        $targets.each(function () {
            if ($(this).hasClass('select2-hidden-accessible') || $(this).attr('data-no-select2')) return;
            var opts = {
                width: '100%',
                placeholder: $(this).find('option:first').text() || 'Select...',
                allowClear: false,
            };
            // Inside a Bootstrap modal the modal traps focus, so select2's search
            // box can't be typed in unless its dropdown is parented to the modal.
            var $modal = $(this).closest('.modal');
            if ($modal.length) opts.dropdownParent = $modal;
            $(this).select2(opts);
        });
    }
    $(function () { initSelect2(); });
    window.bpInitSelect2 = initSelect2; // for AJAX-loaded forms
})();
</script>

{{-- Global date/datetime picker: the display format follows Settings →
     Localization → Date Format; values are still submitted as ISO (Y-m-d).
     Covers both <input type="date"> and <input type="datetime-local">. --}}
@php
    // Map the Localization "Date Format" option (stored as a label like
    // "DD-MM-YYYY (04-03-2026)" or a token like "d M Y") to a flatpickr alt
    // format. Falls back to DD-MM-YYYY.
    $bpRawDateFmt = (string) \Modules\Setting\Models\Setting::get('localization', 'date_format', 'd-m-Y');
    $bpDateFmt = match (true) {
        str_contains($bpRawDateFmt, 'YYYY-MM-DD')  => 'Y-m-d',
        str_contains($bpRawDateFmt, 'MM/DD/YYYY')  => 'm/d/Y',
        str_contains($bpRawDateFmt, 'DD MMM YYYY') => 'd M Y',
        str_contains($bpRawDateFmt, 'DD-MM-YYYY')  => 'd-m-Y',
        // Already a flatpickr/PHP-style token (e.g. the seed default "d M Y").
        preg_match('/^[dDjlNmMnYyFa\/\-\. ]+$/', $bpRawDateFmt) === 1 => $bpRawDateFmt,
        default => 'd-m-Y',
    };
@endphp
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script>
'use strict';
(function () {
    if (typeof flatpickr === 'undefined') return;

    var bpDateFmt = @json($bpDateFmt);

    function initPickers(scope) {
        var root = scope || document;

        // Date-only inputs
        root.querySelectorAll('input[type="date"]:not([data-no-flatpickr]):not(.flatpickr-input)')
            .forEach(function (el) {
                var opts = {
                    dateFormat: 'Y-m-d',
                    altInput:   true,
                    altFormat:  bpDateFmt,
                    allowInput: true,
                };
                // List-page date-range filters (date_from / date_to) prefill
                // with today for convenience — display only, nothing is
                // filtered until the user submits. Only when empty, so an
                // active filter selection is preserved.
                if ((el.name === 'date_from' || el.name === 'date_to') && !el.value) {
                    opts.defaultDate = 'today';
                }
                flatpickr(el, opts);
            });

        // Date + time inputs (campaigns, schedules). Flatpickr replaces the
        // native picker so the display follows Bangladesh format regardless
        // of browser locale.
        root.querySelectorAll('input[type="datetime-local"]:not([data-no-flatpickr]):not(.flatpickr-input)')
            .forEach(function (el) {
                flatpickr(el, {
                    enableTime: true,
                    time_24hr:  false,
                    dateFormat: 'Y-m-d H:i',
                    altInput:   true,
                    altFormat:  bpDateFmt + ' h:i K',
                    allowInput: true,
                });
            });
    }
    document.addEventListener('DOMContentLoaded', function () { initPickers(); });
    window.bpInitDatePickers = initPickers; // for AJAX-loaded forms
})();
</script>

{{-- Global Tagify for comma-separated tag inputs. Auto-targets any input with
     the [data-tagify] attribute (product tags, blog tags, etc.). The original
     input keeps a plain comma-separated value so existing backends — which
     parse tags with explode(',') — work unchanged. --}}
<script src="{{ asset('vendor/tagify/js/tagify.polyfills.min.js') }}"></script>
<script src="{{ asset('vendor/tagify/js/tagify.min.js') }}"></script>
<script>
'use strict';
(function () {
    if (typeof Tagify === 'undefined') return;

    function initTagify(scope) {
        var root = scope || document;
        var selector = 'input[data-tagify]:not([data-no-tagify])';
        var targets = (root.matches && root.matches(selector))
            ? [root]
            : root.querySelectorAll(selector);
        targets.forEach(function (el) {
            if (el._tagify) return; // already initialised
            // Optional comma/pipe-separated whitelist via data-tagify-whitelist.
            var wl = (el.getAttribute('data-tagify-whitelist') || '')
                .split(/[|,]/).map(function (s) { return s.trim(); }).filter(Boolean);
            el._tagify = new Tagify(el, {
                originalInputValueFormat: function (values) {
                    return values.map(function (item) { return item.value; }).join(',');
                },
                whitelist: wl,
                dropdown: { enabled: wl.length ? 0 : false, maxItems: 20 },
                duplicates: false,
                trim: true,
            });
        });
    }
    document.addEventListener('DOMContentLoaded', function () { initTagify(); });
    window.bpInitTagify = initTagify; // for AJAX-loaded forms
})();
</script>

@stack('scripts')
<script>
'use strict';
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function(err) {
        console.error('Service worker registration failed:', err);
    });
}
</script>
</body>
</html>
