@extends('core::layouts.master')

@section('title', __('Category-wise Sales Report'))
@section('page-title', __('Category-wise Sales Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Category-wise</span>
@endsection

@section('page-actions')
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Date Range Filter -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form class="row g-3 align-items-end bp-report-filters" method="GET" action="{{ route('reports.category-wise') }}">
                <div class="col-md-3">
                    <label class="bp-form-label">From Date</label>
                    <input type="date" class="bp-form-control" name="from_date"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="bp-form-label">To Date</label>
                    <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('reports.category-wise') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-layer-group"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Categories</div>
                    <div class="bp-stat-value">{{ $data->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Items Sold</div>
                    <div class="bp-stat-value">{{ number_format($data->sum('items_sold')) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Revenue</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($data->sum('revenue'), 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-chart-pie"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Profit</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($data->sum('profit'), 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category-wise Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>Category-wise Sales</h5>
            <span class="fs-12 text-muted">{{ $data->count() }} categories</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-center">Items Sold</th>
                            <th class="text-end">Revenue ({{ currency_symbol() }})</th>
                            <th class="text-end">Cost ({{ currency_symbol() }})</th>
                            <th class="text-end">Profit ({{ currency_symbol() }})</th>
                            <th class="text-end">Margin %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr>
                                <td class="fw-700">{{ $row->category }}</td>
                                <td class="text-center">{{ number_format($row->items_sold) }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($row->revenue, 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }} {{ number_format($row->cost, 0, '.', ',') }}
                                </td>
                                <td class="text-end fw-700">{{ currency_symbol() }}
                                    {{ number_format($row->profit, 0, '.', ',') }}</td>
                                <td class="text-end">
                                    @php
                                        $margin = $row->revenue > 0 ? ($row->profit / $row->revenue) * 100 : 0;
                                    @endphp
                                    <span
                                        class="bp-badge {{ $margin >= 20 ? 'bp-badge-success' : ($margin >= 10 ? 'bp-badge-warning' : 'bp-badge-danger') }}">
                                        {{ number_format($margin, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty :colspan="6" icon="fa-layer-group"
                                title="No category data found for the selected period." />
                        @endforelse
                    </tbody>
                    @if ($data->count() > 0)
                        <tfoot>
                            <tr class="fw-700">
                                <td>Grand Total</td>
                                <td class="text-center">{{ number_format($data->sum('items_sold')) }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('revenue'), 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('cost'), 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('profit'), 0, '.', ',') }}</td>
                                <td class="text-end">
                                    @php
                                        $totalRevenue = $data->sum('revenue');
                                        $totalMargin =
                                            $totalRevenue > 0 ? ($data->sum('profit') / $totalRevenue) * 100 : 0;
                                    @endphp
                                    {{ number_format($totalMargin, 1) }}%
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            $('#btnPrintReport').on('click', function() {
                window.print();
            });
        });
    </script>
@endpush
