@extends('core::layouts.master')

@section('title', __('Inventory Report'))
@section('page-title', __('Inventory Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Inventory Report</span>
@endsection

@section('page-actions')
    @bpCan('reports.export')
        <x-core::export-dropdown module="report-inventory" />
    @endbpCan
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Filters -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form class="row g-3 align-items-end bp-report-filters" method="GET"
                action="{{ route('reports.inventory') }}">
                <div class="col-md-3">
                    <label class="bp-form-label">Report Type</label>
                    <select class="bp-form-select w-100" name="report_type">
                        <option value="summary" {{ $reportType === 'summary' ? 'selected' : '' }}>Stock Summary</option>
                        <option value="movement" {{ $reportType === 'movement' ? 'selected' : '' }}>Stock Movement</option>
                        <option value="low_stock" {{ $reportType === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                    </select>
                </div>
                @if ($reportType === 'movement')
                    <div class="col-md-2">
                        <label class="bp-form-label">From Date</label>
                        <input type="date" class="bp-form-control" name="from_date"
                            value="{{ $filters['from_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="bp-form-label">To Date</label>
                        <input type="date" class="bp-form-control" name="to_date"
                            value="{{ $filters['to_date'] ?? '' }}">
                    </div>
                @endif
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('reports.inventory') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Products</div>
                    <div class="bp-stat-value">{{ number_format($summary['total_products']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Stock Value</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['total_stock_value'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Low Stock Count</div>
                    <div class="bp-stat-value">{{ number_format($summary['low_stock_count']) }}</div>
                    <div class="bp-stat-change"><span class="text-warning">Needs reorder</span></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-box-open"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Out of Stock</div>
                    <div class="bp-stat-value">{{ number_format($summary['out_of_stock']) }}</div>
                    <div class="bp-stat-change"><span class="text-danger">Immediate action needed</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>
                @if ($reportType === 'summary')
                    Stock Summary
                @elseif($reportType === 'movement')
                    Stock Movement
                @elseif($reportType === 'low_stock')
                    Low Stock Alert
                @endif
            </h5>
            <span class="fs-12 text-muted">{{ $data->count() }} records</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        @if ($reportType === 'summary')
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Reorder Level</th>
                                <th class="text-end">Stock Value</th>
                                <th class="text-center">Status</th>
                            </tr>
                        @elseif($reportType === 'movement')
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-center">Qty In</th>
                                <th class="text-center">Qty Out</th>
                                <th>Source</th>
                            </tr>
                        @elseif($reportType === 'low_stock')
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-center">Current Qty</th>
                                <th class="text-center">Reorder Level</th>
                                <th class="text-center">Deficit</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            @if ($reportType === 'summary')
                                <tr>
                                    <td class="fw-700">{{ $row->name }}</td>
                                    <td class="fs-12 text-muted">{{ $row->sku }}</td>
                                    <td class="text-center fw-700">{{ number_format($row->quantity) }}</td>
                                    <td class="text-center">{{ number_format($row->reorder_level) }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->stock_value, 0, '.', ',') }}</td>
                                    <td class="text-center">
                                        @if ($row->quantity <= 0)
                                            <span class="bp-badge bp-badge-danger">Out of Stock</span>
                                        @elseif($row->reorder_level > 0 && $row->quantity <= $row->reorder_level)
                                            <span class="bp-badge bp-badge-warning">Low Stock</span>
                                        @else
                                            <span class="bp-badge bp-badge-success">In Stock</span>
                                        @endif
                                    </td>
                                </tr>
                            @elseif($reportType === 'movement')
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') }}</td>
                                    <td class="fw-700">{{ $row->product_name }}</td>
                                    <td class="fs-12 text-muted">{{ $row->sku }}</td>
                                    <td class="text-center">{{ $row->qty_in ?? 0 }}</td>
                                    <td class="text-center">{{ $row->qty_out ?? 0 }}</td>
                                    <td>{{ $row->source_type ?? '' }}</td>
                                </tr>
                            @elseif($reportType === 'low_stock')
                                <tr>
                                    <td class="fw-700">{{ $row->name }}</td>
                                    <td class="fs-12 text-muted">{{ $row->sku }}</td>
                                    <td class="text-center fw-700 text-danger">{{ number_format($row->quantity) }}</td>
                                    <td class="text-center">{{ number_format($row->reorder_level) }}</td>
                                    <td class="text-center fw-700 text-danger">{{ number_format($row->deficit) }}</td>
                                </tr>
                            @endif
                        @empty
                            <x-core::table.empty :colspan="6" icon="fa-boxes-stacked"
                                title="No inventory data found for the selected filters." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Print report
            $('#btnPrintReport').on('click', function() {
                window.print();
            });
        });
    </script>
@endpush
