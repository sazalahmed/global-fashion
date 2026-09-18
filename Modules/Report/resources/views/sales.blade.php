@extends('core::layouts.master')

@section('title', __('Sales Report'))
@section('page-title', __('Sales Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Sales Report</span>
@endsection

@section('page-actions')
    @bpCan('reports.export')
        <x-core::export-dropdown module="report-sales" />
    @endbpCan
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Date Range & Filters -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form class="row g-3 align-items-end bp-report-filters" method="GET" action="{{ route('reports.sales') }}">
                <div class="col-md-3">
                    <label class="bp-form-label">From Date</label>
                    <input type="date" class="bp-form-control" name="from_date"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="bp-form-label">To Date</label>
                    <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="bp-form-label">Report Type</label>
                    <select class="bp-form-select w-100" name="report_type">
                        <option value="daily" {{ $reportType === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="monthly" {{ $reportType === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="by_product" {{ $reportType === 'by_product' ? 'selected' : '' }}>By Product</option>
                        <option value="by_customer" {{ $reportType === 'by_customer' ? 'selected' : '' }}>By Customer
                        </option>
                        <option value="by_branch" {{ $reportType === 'by_branch' ? 'selected' : '' }}>By Branch</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('reports.sales') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
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
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-chart-line"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Invoices</div>
                    <div class="bp-stat-value">{{ number_format($summary['total_invoices']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Sales</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['total_sales'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-receipt"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Collected</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['total_collected'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Due</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['total_due'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    @if ($reportType === 'daily' && $data->count() > 0)
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-chart-bar me-2"></i>Daily Sales Trend</h5>
            </div>
            <div class="bp-card-body">
                <canvas id="salesTrendChart" height="80"></canvas>
            </div>
        </div>
    @endif

    <!-- Sales Data Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>
                @if ($reportType === 'daily')
                    Daily Sales
                @elseif($reportType === 'monthly')
                    Monthly Sales
                @elseif($reportType === 'by_product')
                    Sales by Product
                @elseif($reportType === 'by_customer')
                    Sales by Customer
                @elseif($reportType === 'by_branch')
                    Sales by Branch
                @endif
            </h5>
            <span class="fs-12 text-muted">{{ $data->count() }} records</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        @if ($reportType === 'daily')
                            <tr>
                                <th>Date</th>
                                <th class="text-center">Invoices</th>
                                <th class="text-end">Total Sales</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Collected</th>
                                <th class="text-end">Due</th>
                            </tr>
                        @elseif($reportType === 'monthly')
                            <tr>
                                <th>Month</th>
                                <th class="text-center">Invoices</th>
                                <th class="text-end">Total Sales</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Collected</th>
                                <th class="text-end">Due</th>
                            </tr>
                        @elseif($reportType === 'by_product')
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-center">Qty Sold</th>
                                <th class="text-center">Invoices</th>
                                <th class="text-end">Total Revenue</th>
                            </tr>
                        @elseif($reportType === 'by_customer')
                            <tr>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th class="text-center">Invoices</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Due</th>
                            </tr>
                        @elseif($reportType === 'by_branch')
                            <tr>
                                <th>Branch</th>
                                <th class="text-center">Invoices</th>
                                <th class="text-end">Total Sales</th>
                                <th class="text-end">Collected</th>
                                <th class="text-end">Due</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            @if ($reportType === 'daily')
                                <tr>
                                    <td class="fw-700">{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                                    <td class="text-center">{{ $row->total_invoices }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->total_sales, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_discount, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_tax, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_collected, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_due, 0, '.', ',') }}</td>
                                </tr>
                            @elseif($reportType === 'monthly')
                                <tr>
                                    <td class="fw-700">{{ \Carbon\Carbon::parse($row->month . '-01')->format('M Y') }}
                                    </td>
                                    <td class="text-center">{{ $row->total_invoices }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->total_sales, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_discount, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_tax, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_collected, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_due, 0, '.', ',') }}</td>
                                </tr>
                            @elseif($reportType === 'by_product')
                                <tr>
                                    <td class="fw-700">{{ $row->name }}</td>
                                    <td class="fs-12 text-muted">{{ $row->sku }}</td>
                                    <td class="text-center">{{ number_format($row->total_quantity) }}</td>
                                    <td class="text-center">{{ $row->invoice_count }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->total_revenue, 0, '.', ',') }}</td>
                                </tr>
                            @elseif($reportType === 'by_customer')
                                <tr>
                                    <td class="fw-700">{{ $row->customer_name }}</td>
                                    <td>{{ $row->phone ?? '' }}</td>
                                    <td class="text-center">{{ $row->total_invoices }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->total_amount, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_paid, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_due, 0, '.', ',') }}</td>
                                </tr>
                            @elseif($reportType === 'by_branch')
                                <tr>
                                    <td class="fw-700">{{ $row->branch_name }}</td>
                                    <td class="text-center">{{ $row->total_invoices }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->total_sales, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_collected, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_due, 0, '.', ',') }}</td>
                                </tr>
                            @endif
                        @empty
                            <x-core::table.empty :colspan="7" icon="fa-chart-line"
                                title="No sales data found for the selected filters." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
    <script>
        'use strict';

        $(function() {
            @if ($reportType === 'daily' && $data->count() > 0)
                var ctx = document.getElementById('salesTrendChart');
                if (ctx) {
                    var chartData = @json($data->reverse()->values());
                    new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: chartData.map(function(r) {
                                return r.date;
                            }),
                            datasets: [{
                                label: 'Sales Amount ({{ currency_symbol() }})',
                                data: chartData.map(function(r) {
                                    return parseFloat(r.total_sales);
                                }),
                                backgroundColor: 'rgba(27, 79, 114, 0.7)',
                                borderColor: '#1B4F72',
                                borderWidth: 1,
                                borderRadius: 4
                            }, {
                                label: 'Number of Invoices',
                                data: chartData.map(function(r) {
                                    return r.total_invoices;
                                }),
                                type: 'line',
                                borderColor: '#D4AC0D',
                                backgroundColor: 'transparent',
                                borderWidth: 2,
                                pointBackgroundColor: '#D4AC0D',
                                yAxisID: 'y1'
                            }]
                        },
                        options: {
                            responsive: true,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '{{ currency_symbol() }} ' + value.toLocaleString(
                                                'en-IN');
                                        }
                                    }
                                },
                                y1: {
                                    position: 'right',
                                    beginAtZero: true,
                                    grid: {
                                        drawOnChartArea: false
                                    },
                                    ticks: {
                                        stepSize: 5
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            @endif

            // Print report
            $('#btnPrintReport').on('click', function() {
                window.print();
            });
        });
    </script>
@endpush
