@extends('core::layouts.master')

@section('title', __('Monthly Sales Summary'))
@section('page-title', __('Monthly Sales Summary'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Monthly Summary</span>
@endsection

@section('page-actions')
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Year Filter -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form class="row g-3 align-items-end bp-report-filters" method="GET" action="{{ route('reports.monthly-summary') }}">
                <div class="col-md-3">
                    <label class="bp-form-label">Year</label>
                    <select class="bp-form-select w-100" name="year">
                        @for ($y = date('Y'); $y >= 2024; $y--)
                            <option value="{{ $y }}"
                                {{ ($filters['year'] ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('reports.monthly-summary') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
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
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-chart-line"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Sales Count</div>
                    <div class="bp-stat-value">{{ number_format($data->sum('count')) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Sales</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($data->sum('total_sales'), 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-receipt"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Paid</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($data->sum('total_paid'), 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Due</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($data->sum('total_due'), 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Summary Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>Monthly Sales Summary -
                {{ $filters['year'] ?? date('Y') }}</h5>
            <span class="fs-12 text-muted">{{ $data->count() }} months</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-center">Sales Count</th>
                            <th class="text-end">Total Sales ({{ currency_symbol() }})</th>
                            <th class="text-end">Total Paid ({{ currency_symbol() }})</th>
                            <th class="text-end">Total Due ({{ currency_symbol() }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr>
                                <td class="fw-700">{{ $row['month_name'] }}</td>
                                <td class="text-center">{{ number_format($row['count']) }}</td>
                                <td class="text-end fw-700">{{ currency_symbol() }}
                                    {{ number_format($row['total_sales'], 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($row['total_paid'], 0, '.', ',') }}</td>
                                <td class="text-end">
                                    @if ($row['total_due'] > 0)
                                        <span class="bp-badge bp-badge-danger">{{ currency_symbol() }}
                                            {{ number_format($row['total_due'], 0, '.', ',') }}</span>
                                    @else
                                        <span class="bp-badge bp-badge-success">{{ currency_symbol() }} 0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty :colspan="5" icon="fa-calendar"
                                title="No sales data found for the selected year." />
                        @endforelse
                    </tbody>
                    @if ($data->count() > 0)
                        <tfoot>
                            <tr class="fw-700">
                                <td>Grand Total</td>
                                <td class="text-center">{{ number_format($data->sum('count')) }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('total_sales'), 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('total_paid'), 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('total_due'), 0, '.', ',') }}</td>
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
