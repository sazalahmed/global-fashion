{{-- GTM + Facebook Pixel head scripts — include in <head> of all public-facing layouts --}}
@php
    $gtmEnabled = \Modules\Setting\Models\Setting::get('tracking', 'gtm_enabled', false);
    $gtmId = \Modules\Setting\Models\Setting::get('tracking', 'gtm_container_id');
    $fbEnabled = \Modules\Setting\Models\Setting::get('tracking', 'fbpixel_enabled', false);
    $fbId = \Modules\Setting\Models\Setting::get('tracking', 'fbpixel_id');
    $ga4Enabled = \Modules\Setting\Models\Setting::get('tracking', 'ga4_enabled', false);
    $ga4Id = \Modules\Setting\Models\Setting::get('tracking', 'ga4_measurement_id');
@endphp

<script>
    'use strict';

    window.BizPOSTracking = window.BizPOSTracking || {};
    window.BizPOSTracking.fbPixelEnabled = {{ $fbEnabled && $fbId ? 'true' : 'false' }};
</script>

@if ($gtmEnabled && $gtmId)
    <!-- Google Tag Manager -->
    <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', '{{ $gtmId }}');
    </script>
    <!-- End Google Tag Manager -->
@endif

@if ($fbEnabled && $fbId)
    <!-- Facebook Pixel Code -->
    <script>
        ! function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window,
            document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $fbId }}');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $fbId }}&ev=PageView&noscript=1" /></noscript>
    <!-- End Facebook Pixel Code -->
@endif

@if ($ga4Enabled && $ga4Id)
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', '{{ $ga4Id }}');
    </script>
@endif
