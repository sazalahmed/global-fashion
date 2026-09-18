@extends('core::layouts.master')

@section('title', __('Stock Alerts'))
@section('page-title', __('Stock Alerts'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('inventory.index') }}">Inventory</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Stock Alerts</span>
@endsection

@section('page-actions')
    @bpCan('inventory.export')
    <x-core::export-dropdown module="stock" />
    @endbpCan
    @bpCan('inventory.create')
    <a href="{{ route('inventory.adjustments.create') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-sliders"></i> Adjust Stock
    </a>
    @endbpCan
@endsection

@section('content')

    <!-- Alert Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-box-open"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Out of Stock</div>
                    <div class="bp-stat-value">{{ number_format($outOfStockItems->total()) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Low Stock</div>
                    <div class="bp-stat-value">{{ number_format($belowReorderItems->total()) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-list-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Alerts</div>
                    <div class="bp-stat-value">{{ number_format($totalAlerts) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Out of Stock -->
    @if ($outOfStockItems->total() > 0)
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-circle-exclamation me-2 text-danger"></i>Out of Stock Items
                </h5>
                <span class="bp-badge bp-badge-danger">{{ number_format($outOfStockItems->total()) }}
                    {{ Str::plural('item', $outOfStockItems->total()) }}</span>
            </div>
            <div class="bp-card-body p-0">
                <div class="bp-table-wrapper">
                    <table class="bp-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Variant</th>
                                <th>Alert Level</th>
                                <th>Reserved</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($outOfStockItems as $stock)
                                <tr>
                                    <td class="fw-700">{{ $stock->product->name }}</td>
                                    <td><span class="fs-12 text-muted">{{ $stock->product->sku }}</span></td>
                                    <td>{{ $stock->variant->name ?? '--' }}</td>
                                    <td>{{ number_format($stock->alert_level) }}</td>
                                    <td>{{ number_format($stock->reserved_quantity) }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('purchases.create') }}"><i class="fa-solid fa-cart-plus me-2"></i> Reorder</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <x-core::table.pagination :paginator="$outOfStockItems" itemLabel="out-of-stock items" />
        </div>
    @endif

    <!-- Low Stock -->
    @if ($belowReorderItems->total() > 0)
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Low Stock Items
                </h5>
                <span class="bp-badge bp-badge-warning">{{ number_format($belowReorderItems->total()) }}
                    {{ Str::plural('item', $belowReorderItems->total()) }}</span>
            </div>
            <div class="bp-card-body p-0">
                <div class="bp-table-wrapper">
                    <table class="bp-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Variant</th>
                                <th class="text-center">Current</th>
                                <th class="text-center">Alert Level</th>
                                <th class="text-center">Deficit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($belowReorderItems as $stock)
                                <tr>
                                    <td class="fw-700">{{ $stock->product->name }}</td>
                                    <td><span class="fs-12 text-muted">{{ $stock->product->sku }}</span></td>
                                    <td>{{ $stock->variant->name ?? '--' }}</td>
                                    <td class="text-center fw-700 text-warning">{{ number_format($stock->quantity) }}</td>
                                    <td class="text-center">{{ number_format($stock->alert_level) }}</td>
                                    <td class="text-center text-danger fw-700">
                                        -{{ number_format(max(0, $stock->alert_level - $stock->quantity)) }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('purchases.create') }}"><i class="fa-solid fa-cart-plus me-2"></i> Reorder</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <x-core::table.pagination :paginator="$belowReorderItems" itemLabel="low-stock items" />
        </div>
    @endif

    @if ($totalAlerts === 0)
        <div class="bp-card">
            <div class="bp-card-body text-center py-5">
                <i class="fa-solid fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="text-muted">No stock alerts</h5>
                <p class="text-muted fs-13">All products are above their reorder levels. No action needed.</p>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Export handler
            $('[data-export]').on('click', function(e) {
                e.preventDefault();
                var format = $(this).data('export');
                window.location.href = '{{ route('inventory.alerts') }}' + '?export=' + format;
            });
        });
    </script>
@endpush
