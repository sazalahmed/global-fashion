@extends('core::layouts.master')

@section('title', __("Create Flash Deal — Website"))
@section('page-title', __("Create Flash Deal"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.flash-deals') }}">Flash Deals</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Create</span>
@endsection

@section('page-actions')
<a href="{{ route('ecommerce.flash-deals') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Flash Deals
</a>
@endsection

@section('content')

<form action="{{ route('ecommerce.flash-deals.store') }}" method="POST" enctype="multipart/form-data" id="flashDealForm">
  @csrf

  <div class="row g-4">
    <div class="col-xl-8">

      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-bolt me-2"></i>Deal Details</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="bp-form-label">Title *</label>
              <input type="text" class="bp-form-control" name="title" value="{{ old('title') }}" placeholder="e.g. Eid Flash Sale" required>
              @error('title')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">Start Date & Time *</label>
              <input type="datetime-local" class="bp-form-control" name="starts_at" value="{{ old('starts_at') }}" required>
              @error('starts_at')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">End Date & Time *</label>
              <input type="datetime-local" class="bp-form-control" name="ends_at" value="{{ old('ends_at') }}" required>
              @error('ends_at')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12">
              <x-core::image-upload
                name="banner_image"
                label="Banner Image"
                hint="Optional promotional banner for this deal" />
            </div>
          </div>
        </div>
      </div>

      <!-- Products -->
      <div class="bp-card mb-4">
        <div class="bp-card-header d-flex justify-content-between align-items-center">
          <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Deal Products</h5>
        </div>
        <div class="bp-card-body">
          <div class="mb-3">
            @include('core::partials.product-search', [
                'id' => 'productSearch',
                'label' => __('Search & Add Products'),
                'placeholder' => __('Please type product code or name and select...'),
            ])
          </div>

          @error('products')
            <div class="text-danger fs-12 mb-2">{{ $message }}</div>
          @enderror

          <div id="dealProductsWrapper">
            <div class="text-center text-muted py-3" id="noProductsMsg">Add at least one product to this deal.</div>
            <div class="bp-table-wrapper d-none" id="dealProductsTable">
              <table class="bp-table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th width="150">Discount Type</th>
                    <th width="120">Value</th>
                    <th width="60">Remove</th>
                  </tr>
                </thead>
                <tbody id="dealProductsBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="col-xl-4">
      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-toggle-on me-2"></i>Status</h5>
        </div>
        <div class="bp-card-body">
          <select class="bp-form-select w-100" name="is_active">
            <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
          </select>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="submit" class="bp-btn bp-btn-primary justify-content-center">
          <i class="fa-solid fa-save me-2"></i> Create Flash Deal
        </button>
        <a href="{{ route('ecommerce.flash-deals') }}" class="bp-btn bp-btn-danger justify-content-center">
          <i class="fa-solid fa-times me-2"></i> Cancel
        </a>
      </div>
    </div>
  </div>

</form>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    var productIndex = 0;
    var selectedProducts = {};

    function escAttr(s) { return $('<span>').text(s == null ? '' : s).html(); }

    function addDealProduct(product) {
        var id = product.id;
        if (selectedProducts[id]) return; // already added — ignore
        selectedProducts[id] = true;

        var name = escAttr(product.name);
        var sku = product.sku ? ' <span class="text-muted fs-12">(' + escAttr(product.sku) + ')</span>' : '';
        var row = '<tr data-product-id="' + id + '">' +
            '<td>' + name + sku +
            '<input type="hidden" name="products[' + productIndex + '][product_id]" value="' + id + '"></td>' +
            '<td><select class="bp-form-select w-100" name="products[' + productIndex + '][discount_type]">' +
            '<option value="percentage">Percentage</option><option value="fixed">Fixed ({{ currency_symbol() }})</option></select></td>' +
            '<td><input type="number" class="bp-form-control" name="products[' + productIndex + '][discount_value]" placeholder="0" min="0" step="0.01" required></td>' +
            '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-deal-product"><i class="fa-solid fa-times"></i></button></td></tr>';

        $('#dealProductsBody').append(row);
        $('#noProductsMsg').addClass('d-none');
        $('#dealProductsTable').removeClass('d-none');
        productIndex++;
    }

    // Shared product-search widget — same UX as Purchase/Quotation create (Bug_89).
    window.BpProductSearch.init({
        input: '#productSearch',
        catalog: @json($products),
        showPrice: true,
        currencySymbol: '{{ currency_symbol() }}',
        onSelect: addDealProduct
    });

    $(document).on('click', '.remove-deal-product', function () {
        var row = $(this).closest('tr');
        delete selectedProducts[row.data('product-id')];
        row.remove();
        if ($('#dealProductsBody tr').length === 0) {
            $('#noProductsMsg').removeClass('d-none');
            $('#dealProductsTable').addClass('d-none');
        }
    });
});
</script>
@endpush
