@extends('core::layouts.master')

@section('title', __('Purchase Report'))
@section('page-title', __('Purchase Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Purchase Report</span>
@endsection

@section('page-actions')
    @bpCan('reports.export')
        <x-core::export-dropdown module="report-purchases" />
    @endbpCan
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Date Range & Filters -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form class="row g-3 align-items-end bp-report-filters" method="GET" action="{{ route('reports.purchase') }}">
                <div class="col-md-2">
                    <label class="bp-form-label">From Date</label>
                    <input type="date" class="bp-form-control" name="from_date"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">To Date</label>
                    <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
                </div>
                <div class="col-md-2 d-none">
                    <label class="bp-form-label">Branch</label>
                    <select class="bp-form-select w-100" name="branch_id">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}"
                                {{ ($filters['branch_id'] ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">Report Type</label>
                    <select class="bp-form-select w-100" name="report_type">
                        <option value="daily" {{ $reportType === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="by_supplier" {{ $reportType === 'by_supplier' ? 'selected' : '' }}>By Supplier
                        </option>
                        <option value="by_product" {{ $reportType === 'by_product' ? 'selected' : '' }}>By Product</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('reports.purchase') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
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
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Bills</div>
                    <div class="bp-stat-value">{{ number_format($summary['total_bills']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Purchases</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['total_purchases'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Paid</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['total_paid'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Due</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['total_due'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Trend Chart -->
    @if ($reportType === 'daily' && $data->count() > 0)
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-chart-area me-2"></i>Purchase Trend</h5>
            </div>
            <div class="bp-card-body">
                <canvas id="purchaseTrendChart" height="80"></canvas>
            </div>
        </div>
    @endif

    <!-- Purchase Data Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>
                @if ($reportType === 'daily')
                    Daily Purchases
                @elseif($reportType === 'by_supplier')
                    Purchases by Supplier
                @elseif($reportType === 'by_product')
                    Purchases by Product
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
                                <th class="text-center">Bills</th>
                                <th class="text-end">Total Purchases</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Due</th>
                            </tr>
                        @elseif($reportType === 'by_supplier')
                            <tr>
                                <th>Supplier</th>
                                <th>Phone</th>
                                <th class="text-center">Bills</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Due</th>
                            </tr>
                        @elseif($reportType === 'by_product')
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-center">Qty Purchased</th>
                                <th class="text-center">Bills</th>
                                <th class="text-end">Total Cost</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            @if ($reportType === 'daily')
                                <tr>
                                    <td class="fw-700">{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                                    <td class="text-center">{{ $row->total_bills }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->total_purchases, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_paid, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_due, 0, '.', ',') }}</td>
                                </tr>
                            @elseif($reportType === 'by_supplier')
                                <tr>
                                    <td class="fw-700">{{ $row->supplier_name }}</td>
                                    <td>{{ $row->phone ?? '' }}</td>
                                    <td class="text-center">{{ $row->total_bills }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->total_amount, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_paid, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->total_due, 0, '.', ',') }}</td>
                                </tr>
                            @elseif($reportType === 'by_product')
                                <tr>
                                    <td class="fw-700">{{ $row->name }}</td>
                                    <td class="fs-12 text-muted">{{ $row->sku }}</td>
                                    <td class="text-center">{{ number_format($row->total_quantity) }}</td>
                                    <td class="text-center">{{ $row->bill_count }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->total_cost, 0, '.', ',') }}</td>
                                </tr>
                            @endif
                        @empty
                            <x-core::table.empty :colspan="6" icon="fa-cart-shopping"
                                title="No purchase data found for the selected filters." />
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
                var ctx = document.getElementById('purchaseTrendChart');
                if (ctx) {
                    var chartData = @json($data->reverse()->values());
                    new Chart(ctx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: chartData.map(function(r) {
                                return r.date;
                            }),
                            datasets: [{
                                label: 'Purchase Amount ({{ currency_symbol() }})',
                                data: chartData.map(function(r) {
                                    return parseFloat(r.total_purchases);
                                }),
                                borderColor: '#1B4F72',
                                backgroundColor: 'rgba(27, 79, 114, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: '#1B4F72'
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '{{ currency_symbol() }} ' + value.toLocaleString(
                                                'en-IN');
                                        }
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
