{{--
  Reusable product search box — matches the Sale create "Select Product" design.
  Pair with window.BpProductSearch.init() in the page's @push('scripts') block.

  Params (all optional):
    $id          — input id (default: 'productSearch'); results div is "{id}Results"
    $label       — field label (default: "Search Product"); pass null/false to hide
    $placeholder — input placeholder
    $barcode     — show the barcode prefix button (default: true)
--}}
@php
    $id          = $id ?? 'productSearch';
    $label       = ($label ?? __('Search Product'));
    $placeholder = $placeholder ?? __('Please type product code or name and select...');
    $barcode     = $barcode ?? true;
@endphp
<div class="bp-product-search-wrapper">
    @if($label)
        <label class="bp-form-label">{{ $label }}</label>
    @endif
    <div class="bp-product-search">
        @if($barcode)
            <button type="button" class="bp-barcode-btn" data-bp-barcode-for="{{ $id }}" title="{{ __('Scan barcode') }}">
                <i class="fa-solid fa-barcode"></i>
            </button>
        @endif
        <input type="text" id="{{ $id }}" class="bp-form-control bp-product-search-input"
               placeholder="{{ $placeholder }}" autocomplete="off">
    </div>
    <div class="bp-search-dropdown bp-product-search-results" id="{{ $id }}Results"></div>
</div>
