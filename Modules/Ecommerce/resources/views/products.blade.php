@extends('core::layouts.master')

@section('title', __("Product Sync — Website"))
@section('page-title', __("Product Sync"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Products</span>
@endsection

@section('page-actions')
@bpCan('ecommerce.edit')
<button class="bp-btn bp-btn-outline" id="btnBulkSync">
  <i class="fa-solid fa-rotate me-1"></i> Sync All Products
</button>
@endbpCan
<x-core::export-dropdown module="ecommerce-products" />
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-boxes-stacked"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Products</div>
        <div class="bp-stat-value">{{ $products->total() }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-double"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Active</div>
        <div class="bp-stat-value">{{ $products->total() }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Pending Sync</div>
        <div class="bp-stat-value">0</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-circle-exclamation"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Sync Errors</div>
        <div class="bp-stat-value">0</div>
      </div>
    </div>
  </div>
</div>

<!-- Products Table -->
<x-core::table :selectable="true">
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search product, SKU...">
      <select class="bp-form-select" name="sync_status">
        <option>All Sync Status</option>
        <option>Synced</option>
        <option>Pending</option>
        <option>Error</option>
      </select>
      <select class="bp-form-select" name="visibility">
        <option>All Visibility</option>
        <option>Visible Online</option>
        <option>Hidden</option>
      </select>
      <select class="bp-form-select" name="category">
        <option>All Categories</option>
        <option>Electronics</option>
        <option>Fashion</option>
        <option>Groceries</option>
        <option>Home & Living</option>
        <option>Health & Beauty</option>
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-slot:bulkActions>
    <x-core::table.bulk-actions>
      @bpCan('ecommerce.edit')
      <button class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-rotate me-1"></i> Sync Selected</button>
      <button class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-eye me-1"></i> Show Online</button>
      <button class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-eye-slash me-1"></i> Hide Online</button>
      @endbpCan
    </x-core::table.bulk-actions>
  </x-slot:bulkActions>

  <x-core::table.header :selectable="true">
    <x-core::table.column>Product</x-core::table.column>
    <x-core::table.column>SKU</x-core::table.column>
    <x-core::table.column align="end">POS Price</x-core::table.column>
    <x-core::table.column align="end">Online Price</x-core::table.column>
    <x-core::table.column align="center">Stock</x-core::table.column>
    <x-core::table.column>Online</x-core::table.column>
    <x-core::table.column>Sync Status</x-core::table.column>
    <x-core::table.column>Last Synced</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($products as $product)
    <tr>
      <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $product->id }}"></td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div class="bp-pos-item-img bp-pos-item-img-sm"><i class="fa-solid fa-boxes-stacked"></i></div>
          <div>
            <div class="fw-700">{{ $product->name }}</div>
            <div class="fs-11 text-muted">{{ $product->category->name ?? 'Uncategorized' }}</div>
          </div>
        </div>
      </td>
      <td><code class="fs-11">{{ $product->sku }}</code></td>
      <td class="text-end">{{ currency_symbol() }} {{ number_format($product->selling_price) }}</td>
      <td class="text-end">{{ currency_symbol() }} {{ number_format($product->selling_price) }}</td>
      <td class="text-center fw-700">{{ $product->quantity ?? 0 }}</td>
      <td>
        @bpCan('ecommerce.edit')
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" checked>
        </div>
        @endbpCan
      </td>
      <td><span class="bp-badge bp-badge-success">Available</span></td>
      <td class="fs-12 text-muted">{{ $product->updated_at?->format('d M Y') }}</td>
      <td>
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('products.show', $product) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
          </ul>
        </div>
      </td>
    </tr>
    @empty
    <x-core::table.empty colspan="9" icon="fa-solid fa-boxes-stacked" title="No products found" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <x-core::table.pagination :paginator="$products" />
  </x-slot:pagination>
</x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Select all checkbox
    $('.bp-check-all').on('change', function () {
        var isChecked = $(this).prop('checked');
        $('.row-checkbox').prop('checked', isChecked);
    });

    // Bulk sync
    $('#btnBulkSync').on('click', function () {
        if (confirm('Sync all products with online store? This may take a few minutes.')) {
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Syncing...');
            setTimeout(function () {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-rotate me-1"></i> Sync All Products');
            }, 3000);
        }
    });

    // Toggle online visibility
    $('.form-check-input[type="checkbox"]').not('.bp-check-all, .row-checkbox').on('change', function () {
        var productName = $(this).closest('tr').find('.fw-700').first().text();
        var status = $(this).prop('checked') ? 'visible' : 'hidden';
        // Handle visibility toggle
    });
});
</script>
@endpush
