@extends('core::layouts.master')

@section('title', __("Products"))
@section('page-title', __("Products"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Products</span>
@endsection

@section('page-actions')
  @bpCan('products.export')
  <x-core::export-dropdown module="products" />
  <button class="bp-btn bp-btn-outline" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fa-solid fa-file-import me-1"></i> Import</button>
  @endbpCan
  @bpCan('products.create')
  <div class="dropdown d-inline-block">
    <button class="bp-btn bp-btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="fa-solid fa-plus"></i> Add New
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
      <li><a class="dropdown-item" href="{{ route('products.create') }}"><i class="fa-solid fa-box me-2"></i>Create Product</a></li>
      <li><a class="dropdown-item" href="{{ route('products.combos.create') }}"><i class="fa-solid fa-layer-group me-2"></i>Create Combo</a></li>
    </ul>
  </div>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Products</div>
          <div class="bp-stat-value">{{ $totalProducts ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active</div>
          <div class="bp-stat-value">{{ $activeProducts ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Low Stock</div>
          <div class="bp-stat-value">{{ $lowStockProducts ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-ban"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Out of Stock</div>
          <div class="bp-stat-value">{{ $outOfStockProducts ?? 0 }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Products Table -->
  <x-core::table id="productsTable">
    <x-slot:filters>
      <x-core::table.filter-bar action="{{ route('products.index') }}" searchPlaceholder="Search name, SKU, barcode..." id="filterForm">
        <select class="bp-form-select select2-search" name="category" data-category-select>
          <option value="">All Categories</option>
          @foreach($categories ?? [] as $category)
            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="type">
          <option value="">All Types</option>
          <option value="product" {{ request('type') == 'product' ? 'selected' : '' }}>Products</option>
          <option value="combo" {{ request('type') == 'combo' ? 'selected' : '' }}>Combos</option>
        </select>
        <select class="bp-form-select" name="brand">
          <option value="">All Brands</option>
          @foreach($brands ?? [] as $brand)
            <option value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <select class="bp-form-select" name="stock">
          <option value="">All Stock</option>
          <option value="in_stock" {{ request('stock') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
          <option value="low_stock" {{ request('stock') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
          <option value="out_of_stock" {{ request('stock') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
        </select>
        <select class="bp-form-select" name="per_page" title="{{ __('Products per page') }}">
          @foreach($perPageOptions ?? [15, 25, 50, 100] as $option)
            <option value="{{ $option }}" {{ (int) ($perPage ?? 15) === $option ? 'selected' : '' }}>{{ $option }} / page</option>
          @endforeach
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-slot:bulkActions>
      <x-core::table.bulk-actions id="bulkActionBar">
        @bpCan('products.edit')
        <button class="bp-btn bp-btn-sm bp-btn-outline" type="button" data-bulk-action="status" data-bulk-status="active">Set Active</button>
        <button class="bp-btn bp-btn-sm bp-btn-outline" type="button" data-bulk-action="status" data-bulk-status="inactive">Set Inactive</button>
        @endbpCan
        @bpCan('products.delete')
        <button class="bp-btn bp-btn-sm bp-btn-danger" type="button" data-bulk-action="delete"><i class="fa-solid fa-trash me-1"></i>Delete Selected</button>
        @endbpCan
      </x-core::table.bulk-actions>
    </x-slot:bulkActions>

    <x-core::table.header :selectable="true">
      <x-core::table.column class="bp-reorder-col" title="Drag to reorder">&nbsp;</x-core::table.column>
      <x-core::table.column>Image</x-core::table.column>
      <x-core::table.column :sortable="true" field="name">Product Name</x-core::table.column>
      <x-core::table.column :sortable="true" field="sku">SKU</x-core::table.column>
      <x-core::table.column>Category</x-core::table.column>
      <x-core::table.column>Brand</x-core::table.column>
      <x-core::table.column :sortable="true" field="cost_price">Cost Price</x-core::table.column>
      <x-core::table.column :sortable="true" field="sell_price">Sell Price</x-core::table.column>
      <x-core::table.column :sortable="true" field="stock">Stock</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody class="bp-reorderable" data-reorder-url="{{ route('products.catalog.reorder') }}" data-reorder-mode="catalog">
      @forelse($products ?? [] as $item)
      @if(($item->catalog_type ?? 'product') === 'combo')
      @php($comboService = app(\Modules\Ecommerce\Services\ComboService::class))
      <tr data-id="{{ $item->id }}" data-type="combo">
        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $item->id }}"></td>
        <td class="bp-drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></td>
        <td>
          <div class="bp-product-thumb-sm">
            @if($item->thumbnail)
              <x-webp :src="$item->thumbnail" :sm="true" alt="{{ $item->name }}" loading="lazy" />
            @else
              <i class="fa-solid fa-layer-group"></i>
            @endif
          </div>
        </td>
        <td class="fw-600">
          <a href="{{ route('products.combos.edit', $item->id) }}">{{ $item->name }}</a>
          <span class="bp-badge bp-badge-secondary ms-1">Combo</span>
        </td>
        <td>{{ $item->items->count() }} {{ __('items') }}</td>
        <td>
          @foreach($item->categories as $c)
            <span class="bp-badge bp-badge-primary">{{ $c->name }}</span>
          @endforeach
        </td>
        <td>—</td>
        <td>—</td>
        <td class="fw-700">{{ currency_symbol() }} {{ number_format((float) $item->combo_price, 2) }}</td>
        <td>
          @php($comboStock = $comboService->availableStock($item))
          @if($comboStock === null)
            <span class="bp-badge bp-badge-success">&infin;</span>
          @elseif($comboStock <= 0)
            <span class="bp-badge bp-badge-danger">{{ $comboStock }}</span>
          @else
            <span class="bp-badge bp-badge-success">{{ $comboStock }}</span>
          @endif
        </td>
        <td>
          <label class="form-check form-switch mb-0 bp-status-switch" title="{{ __('Toggle active status') }}">
            <input class="form-check-input bp-status-toggle" type="checkbox" role="switch"
                   data-url="{{ route('products.combos.toggle-status', $item->id) }}"
                   data-active-text="{{ __('Active') }}"
                   data-inactive-text="{{ __('Inactive') }}"
                   {{ $item->is_active ? 'checked' : '' }}>
            <span class="bp-status-switch-label fs-12 fw-600">{{ $item->is_active ? __('Active') : __('Inactive') }}</span>
          </label>
        </td>
        <td>
          @bpCanAny('products.view','products.edit','products.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('products.view')
              @if($item->slug)
                <li><a class="dropdown-item" href="{{ route('storefront.combos.show', $item->slug) }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Public View</a></li>
              @endif
              @endbpCan
              @bpCan('products.edit')
              <li><a class="dropdown-item" href="{{ route('products.combos.edit', $item->id) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
              @endbpCan
              @bpCan('products.delete')
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="{{ route('products.combos.destroy', $item->id) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $item->name }}">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </form>
              </li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @else
      @php($product = $item)
      <tr data-id="{{ $product->id }}" data-type="product">
        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $product->id }}"></td>
        <td class="bp-drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></td>
        <td>
          <div class="bp-product-thumb-sm">
            @php($thumb = $product->thumbnail ?: $product->image)
            @if($thumb)
              <x-webp :src="$thumb" :sm="true" alt="{{ $product->name }}" loading="lazy" />
            @else
              <i class="fa-solid fa-box"></i>
            @endif
          </div>
        </td>
        <td class="fw-600"><a href="{{ route('products.show', $product->id) }}">{{ $product->name }}</a></td>
        <td>{{ $product->sku }}</td>
        <td>
          @if($product->category)
            <span class="bp-badge bp-badge-primary">{{ $product->category->name }}</span>
          @endif
        </td>
        <td>{{ $product->brand->name ?? '' }}</td>
        <td>{{ currency_symbol() }} {{ $product->formatted_cost_price ?? '0' }}</td>
        <td class="fw-700">{{ currency_symbol() }} {{ $product->formatted_sell_price ?? '0' }}</td>
        <td>
          @if(($product->total_stock ?? 0) <= 0)
            <span class="bp-badge bp-badge-danger">{{ $product->total_stock ?? 0 }}</span>
          @elseif(($product->total_stock ?? 0) <= ($product->min_stock_alert ?? 10))
            <span class="bp-badge bp-badge-warning">{{ $product->total_stock ?? 0 }}</span>
          @else
            <span class="bp-badge bp-badge-success">{{ $product->total_stock ?? 0 }}</span>
          @endif
        </td>
        <td>
          {{-- Uses the shared `.bp-status-toggle` handler in public/js/app.js.
               The page-level duplicate handler was removed to stop a double PATCH (Bug_78). --}}
          <label class="form-check form-switch mb-0 bp-status-switch" title="{{ __('Toggle active status') }}">
            <input class="form-check-input bp-status-toggle" type="checkbox" role="switch"
                   data-url="{{ route('products.toggle-status', $product->id) }}"
                   data-active-text="{{ __('Active') }}"
                   data-inactive-text="{{ __('Inactive') }}"
                   {{ $product->is_active ? 'checked' : '' }}>
            <span class="bp-status-switch-label fs-12 fw-600">{{ $product->is_active ? __('Active') : __('Inactive') }}</span>
          </label>
        </td>
        <td>
          @bpCanAny('products.view','products.create','products.edit','products.delete','barcode.view','inventory.view')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('products.view')
              <li><a class="dropdown-item" href="{{ route('products.show', $product->id) }}"><i class="fa-solid fa-eye"></i> View</a></li>
              @endbpCan
              @bpCan('products.view')
              @if($product->slug)
                <li><a class="dropdown-item" href="{{ route('storefront.shop.show', $product->slug) }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Public View</a></li>
              @endif
              @endbpCan
              @bpCan('products.edit')
              <li><a class="dropdown-item" href="{{ route('products.edit', $product->id) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
              @endbpCan
              @bpCan('products.create')
              <li>
                <form action="{{ route('products.duplicate', $product->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="dropdown-item"><i class="fa-solid fa-copy"></i> Duplicate</button>
                </form>
              </li>
              @endbpCan
              @bpCan('barcode.view')
              @if($product->barcode)
              <li><a class="dropdown-item" href="{{ route('barcode.index', ['product_id' => $product->id]) }}"><i class="fa-solid fa-barcode"></i> Barcode</a></li>
              @endif
              @endbpCan
              @bpCan('inventory.view')
              <li><a class="dropdown-item" href="{{ route('inventory.stock-ledger', ['product_id' => $product->id]) }}"><i class="fa-solid fa-clock-rotate-left"></i> Stock History</a></li>
              @endbpCan
              <li><hr class="dropdown-divider"></li>
              @bpCan('products.delete')
              <li>
                <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $product->name }}">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </form>
              </li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @endif
      @empty
      <x-core::table.empty :colspan="12" icon="fa-box-open" :title="__('No products or combos found')" />
      @endforelse
    </tbody>

    @if(isset($products) && method_exists($products, 'hasPages'))
    <x-slot:pagination>
      <x-core::table.pagination :paginator="$products" itemLabel="items" />
    </x-slot:pagination>
    @endif
  </x-core::table>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('import', 'products') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-700"><i class="fa-solid fa-file-import me-2"></i>Import Products</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="bp-form-label">Upload File (Excel/CSV) *</label>
            <input type="file" class="bp-form-control" name="file" accept=".xlsx,.xls,.csv" required>
            <small class="text-muted fs-11">Max 5MB. Accepted: .xlsx, .xls, .csv</small>
          </div>
          <div class="bp-card bg-light p-3">
            <div class="fw-700 fs-12 mb-2">Required Columns:</div>
            <code class="fs-11">name</code> (required), <code class="fs-11">sku</code>, <code class="fs-11">barcode</code>, <code class="fs-11">cost_price</code>, <code class="fs-11">sell_price</code>, <code class="fs-11">vat_rate</code>, <code class="fs-11">description</code>, <code class="fs-11">status</code>
            <div class="text-muted fs-11 mt-2">Products with duplicate SKUs will be skipped.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
          <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-upload me-1"></i> Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function() {
    // Filter form auto-submit on select change
    $('#filterForm select').on('change', function() {
        $('#filterForm').submit();
    });

    // Select all checkbox
    $(document).on('change', '.bp-check-all', function() {
        var checked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', checked);
        updateBulkActions();
    });

    // Individual checkbox
    $(document).on('change', '.row-checkbox', function() {
        updateBulkActions();
        var total = $('.row-checkbox').length;
        var checked = $('.row-checkbox:checked').length;
        $('.bp-check-all').prop('checked', total === checked);
    });

    function updateBulkActions() {
        var count = $('.row-checkbox:checked').length;
        $('#bulkActionBar .count').text(count);
        if (count > 0) {
            $('#bulkActionBar').removeAttr('hidden');
        } else {
            $('#bulkActionBar').attr('hidden', true);
        }
    }

    // Status toggle is handled globally by the shared `.bp-status-toggle`
    // handler in public/js/app.js. Do NOT bind another handler here — a
    // duplicate binding fires the PATCH twice, flipping the status back to
    // its original value (Bug_78).

});
</script>
@endpush
