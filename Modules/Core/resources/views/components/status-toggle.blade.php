{{-- Inline active/inactive status switch for list pages.
     PATCHes :url (no body) via the global `.bp-status-toggle` handler in
     public/js/app.js, which expects a JSON response { is_active, message }.

     Usage:
       <x-core::status-toggle :url="route('brands.toggle-status', $brand->id)"
                              :active="$brand->status === 'active'" /> --}}
@props([
    'url',
    'active' => false,
    'activeText' => null,
    'inactiveText' => null,
])
@php
    $activeText = $activeText ?? __('Active');
    $inactiveText = $inactiveText ?? __('Inactive');
@endphp
<label class="form-check form-switch mb-0 bp-status-switch" title="{{ __('Toggle active status') }}">
    <input class="form-check-input bp-status-toggle" type="checkbox" role="switch"
           data-url="{{ $url }}"
           data-active-text="{{ $activeText }}"
           data-inactive-text="{{ $inactiveText }}"
           {{ $active ? 'checked' : '' }}>
    <span class="bp-status-switch-label fs-12 fw-600">{{ $active ? $activeText : $inactiveText }}</span>
</label>
