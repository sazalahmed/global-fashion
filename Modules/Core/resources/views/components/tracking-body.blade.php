{{-- GTM noscript — include immediately after <body> tag --}}
@php
    $gtmEnabled = \Modules\Setting\Models\Setting::get('tracking', 'gtm_enabled', false);
    $gtmId = \Modules\Setting\Models\Setting::get('tracking', 'gtm_container_id');
@endphp

@if($gtmEnabled && $gtmId)
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
@endif
