@extends('core::layouts.master')

@section('title', __('Inventory'))
@section('page-title', __('Inventory'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Inventory</span>
@endsection

@section('page-actions')
    @bpCan('inventory.export')
    <x-core::export-dropdown module="stock" />
    @endbpCan
    @bpCan('inventory.create')
    <a href="{{ route('inventory.adjustments') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-sliders"></i> Stock Adjustment
    </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Products</div>
                    <div class="bp-stat-value">{{ number_format($stats['total_products']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Stock Value</div>
                    <div class="bp-stat-value">{{ money($stats['total_stock_value']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Low Stock Items</div>
                    <div class="bp-stat-value">{{ number_format($stats['low_stock_count']) }}</div>
                    {{-- <div class="bp-stat-change"><span class="text-warning">Needs reorder</span></div> --}}
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search product name, SKU, barcode...">
                <select class="bp-form-select" name="stock_status">
                    <option value="">All Stock Status</option>
                    <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock
                    </option>
                    <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock
                    </option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of
                        Stock</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column :sortable="true" field="product">Product</x-core::table.column>
            <x-core::table.column>Model</x-core::table.column>
            <x-core::table.column>Variant</x-core::table.column>
            <x-core::table.column :sortable="true" field="current_stock">Current
                Stock</x-core::table.column>
            <x-core::table.column>Reserved</x-core::table.column>
            <x-core::table.column>Reorder Level</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($stockLevels as $stock)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div>
                                <div class="fw-700">{{ $stock->product->name ?? 'Unknown' }}</div>
                                <div class="fs-12 text-muted">SKU: {{ $stock->product->sku ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="fs-13">
                        @if ($stock->product?->model)
                            {{ $stock->product->model }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($stock->variant)
                            <div class="fw-600 fs-13">{{ $stock->variant->variant_name }}</div>
                            <div class="fs-11 text-muted">{{ $stock->variant->sku }}</div>
                        @endif
                    </td>
                    <td class="fw-700">{{ number_format($stock->quantity) }}</td>
                    <td>{{ number_format($stock->reserved_quantity) }}</td>
                    <td>{{ number_format($stock->reorder_level) }}</td>
                    <td>
                        @if ($stock->quantity == 0)
                            <span class="bp-badge bp-badge-danger">Out of Stock</span>
                        @elseif($stock->reorder_level > 0 && $stock->quantity <= $stock->reorder_level)
                            <span class="bp-badge bp-badge-danger">Low Stock</span>
                        @else
                            <span class="bp-badge bp-badge-success">In Stock</span>
                        @endif
                    </td>
                    <td>
                        @bpCanAny('inventory.create','inventory.view')
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @bpCan('inventory.create')
                                <li><a class="dropdown-item"
                                            href="{{ route('inventory.adjustments.create', array_filter(['product_id' => $stock->product?->id, 'variant_id' => $stock->variant?->id])) }}"><i
                                                class="fa-solid fa-sliders"></i> Adjust</a>
                                </li>
                                @endbpCan
                                @bpCan('inventory.view')
                                @if ($stock->product)
                                    <li><a class="dropdown-item"
                                            href="{{ route('inventory.ledger', ['product_id' => $stock->product->id]) }}"><i
                                                class="fa-solid fa-clock-rotate-left"></i> History</a></li>
                                @endif
                                @endbpCan
                            </ul>
                        </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty :colspan="8" icon="fa-boxes-stacked" :title="__('No stock records found')" />
            @endforelse
        </tbody>

        @if ($stockLevels->total() > 0)
            {{-- Totals cover every row matching the filters, not just this page. --}}
            <tfoot>
                <tr class="bp-table-total-row">
                    <td colspan="3" class="text-end fw-800">{{ __('Total (filtered)') }}</td>
                    <td class="fw-800">{{ number_format($stockTotals['quantity']) }}</td>
                    <td colspan="4" class="fw-800">
                        {{ __('Stock value (at cost)') }}: {{ money($stockTotals['value']) }}
                    </td>
                </tr>
            </tfoot>
        @endif

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$stockLevels" itemLabel="products" />
        </x-slot:pagination>
    </x-core::table>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Reset filter handler
            $('.bp-filter-reset').on('click', function() {
                // Clear all filters — go to the clean path (no query string).
                window.location.href = window.location.pathname;
            });
        });
    </script>
@endpush
