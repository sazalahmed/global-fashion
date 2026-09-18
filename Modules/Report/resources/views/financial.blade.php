@extends('core::layouts.master')

@section('title', __('Financial Report'))
@section('page-title', __('Financial Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Financial Report</span>
@endsection

@section('page-actions')
    @bpCan('reports.export')
        <x-core::export-dropdown module="report-financial" />
    @endbpCan
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Date Range & Filters -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form class="row g-3 align-items-end bp-report-filters" method="GET" action="{{ route('reports.financial') }}">
                <div class="col-md-3">
                    <label class="bp-form-label">From Date</label>
                    <input type="date" class="bp-form-control" name="from_date"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="bp-form-label">To Date</label>
                    <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="bp-form-label">Year</label>
                    <select class="bp-form-select w-100" name="year">
                        @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}"
                                {{ ($filters['year'] ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('reports.financial') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats -->
    @php
        $profitMargin = $summary['total_income'] > 0 ? ($summary['net_profit'] / $summary['total_income']) * 100 : 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Revenue</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['total_income'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-trend-down"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Expenses</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['total_expenses'] + $summary['total_purchases'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Net Profit</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($summary['net_profit'], 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-percent"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Profit Margin</div>
                    <div class="bp-stat-value">{{ number_format($profitMargin, 1) }}%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue vs Expense Chart -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-chart-bar me-2"></i>Monthly Revenue vs Expenses</h5>
                </div>
                <div class="bp-card-body">
                    <canvas id="revenueExpenseChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-chart-pie me-2"></i>Income vs Expense Split</h5>
                </div>
                <div class="bp-card-body">
                    <canvas id="expenseBreakdownChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly P&L Comparison Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table-columns me-2"></i>Monthly P&L Comparison</h5>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-end">Sales</th>
                            <th class="text-end">Purchases</th>
                            <th class="text-end">Expenses</th>
                            <th class="text-end">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($profitTrend as $row)
                            @if ($row['sales'] > 0 || $row['purchases'] > 0 || $row['expenses'] > 0)
                                <tr>
                                    <td class="fw-700">{{ $row['month'] }} {{ $filters['year'] ?? date('Y') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row['sales'], 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row['purchases'], 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row['expenses'], 0, '.', ',') }}</td>
                                    <td class="text-end fw-700 {{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ currency_symbol() }} {{ number_format($row['profit'], 0, '.', ',') }}</td>
                                </tr>
                            @endif
                        @empty
                            <x-core::table.empty :colspan="5" icon="fa-chart-bar"
                                title="No financial data found for the selected period." />
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
            var profitTrend = @json($profitTrend);

            // Revenue vs Expense Chart
            var reCtx = document.getElementById('revenueExpenseChart');
            if (reCtx) {
                new Chart(reCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: profitTrend.map(function(r) {
                            return r.month;
                        }),
                        datasets: [{
                            label: 'Sales',
                            data: profitTrend.map(function(r) {
                                return r.sales;
                            }),
                            backgroundColor: 'rgba(30, 132, 73, 0.7)',
                            borderColor: '#1E8449',
                            borderWidth: 1,
                            borderRadius: 4
                        }, {
                            label: 'Purchases + Expenses',
                            data: profitTrend.map(function(r) {
                                return r.purchases + r.expenses;
                            }),
                            backgroundColor: 'rgba(192, 57, 43, 0.7)',
                            borderColor: '#C0392B',
                            borderWidth: 1,
                            borderRadius: 4
                        }, {
                            label: 'Net Profit',
                            type: 'line',
                            data: profitTrend.map(function(r) {
                                return r.profit;
                            }),
                            borderColor: '#D4AC0D',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointBackgroundColor: '#D4AC0D'
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
                                        return '{{ currency_symbol() }} ' + (value / 100000).toFixed(
                                            1) + 'L';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': {{ currency_symbol() }} ' +
                                            context.raw.toLocaleString('en-IN');
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Income vs Expense Split
            var expCtx = document.getElementById('expenseBreakdownChart');
            if (expCtx) {
                var summary = @json($summary);
                new Chart(expCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Purchases', 'Operating Expenses', 'Net Profit'],
                        datasets: [{
                            data: [
                                parseFloat(summary.total_purchases),
                                parseFloat(summary.total_expenses),
                                parseFloat(summary.net_profit) > 0 ? parseFloat(summary
                                    .net_profit) : 0
                            ],
                            backgroundColor: ['#1B4F72', '#E67E22', '#1E8449'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': {{ currency_symbol() }} ' + context
                                            .raw.toLocaleString('en-IN');
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Print report
            $('#btnPrintReport').on('click', function() {
                window.print();
            });
        });
    </script>
@endpush
