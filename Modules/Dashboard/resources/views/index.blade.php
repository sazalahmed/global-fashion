@extends('core::layouts.master')

@section('title', __('Dashboard'))
@section('page-title', __('Dashboard'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Dashboard</span>
@endsection

@php
    $dToday = now();
    $datePresets = [
        ['label' => 'Today', 'from' => $dToday->copy(), 'to' => $dToday->copy()],
        ['label' => 'Yesterday', 'from' => $dToday->copy()->subDay(), 'to' => $dToday->copy()->subDay()],
        ['label' => 'Last 7 Days', 'from' => $dToday->copy()->subDays(6), 'to' => $dToday->copy()],
        ['label' => 'Last 30 Days', 'from' => $dToday->copy()->subDays(29), 'to' => $dToday->copy()],
        ['label' => 'Last 60 Days', 'from' => $dToday->copy()->subDays(59), 'to' => $dToday->copy()],
        ['label' => 'Last 90 Days', 'from' => $dToday->copy()->subDays(89), 'to' => $dToday->copy()],
        ['label' => 'This Month', 'from' => $dToday->copy()->startOfMonth(), 'to' => $dToday->copy()],
        [
            'label' => 'Last Month',
            'from' => $dToday->copy()->subMonth()->startOfMonth(),
            'to' => $dToday->copy()->subMonth()->endOfMonth(),
        ],
    ];
    $curFrom = $orderCounts['period_start'];
    $curTo = $orderCounts['period_end'];
    $activeDateLabel = 'Custom Range';
    foreach ($datePresets as $p) {
        if ($p['from']->toDateString() === $curFrom && $p['to']->toDateString() === $curTo) {
            $activeDateLabel = $p['label'];
            break;
        }
    }
@endphp

@section('page-actions')
    <div class="bp-date-dropdown" id="dashboardDateDropdown">
        <button type="button" class="bp-date-dropdown-toggle" id="dashboardDateToggle">
            <i class="fa-solid fa-calendar-day"></i>
            <span id="dashboardDateLabel">{{ $activeDateLabel }}</span>
            <i class="fa-solid fa-chevron-down bp-date-dropdown-caret"></i>
        </button>
        <div class="bp-date-dropdown-panel" id="dashboardDatePanel">
            <div class="bp-date-dropdown-heading">Date Range</div>
            @foreach ($datePresets as $p)
                <a href="{{ route('dashboard', ['from_date' => $p['from']->toDateString(), 'to_date' => $p['to']->toDateString()]) }}"
                    class="bp-date-dropdown-item {{ $activeDateLabel === $p['label'] ? 'active' : '' }}">
                    <span class="bp-date-dropdown-item-label">{{ $p['label'] }}</span>
                    <span class="bp-date-dropdown-item-range">
                        @if ($p['from']->isSameDay($p['to']))
                            {{ $p['from']->format('M d, Y') }}
                        @else
                            {{ $p['from']->format('M d') }} - {{ $p['to']->format('M d') }}
                        @endif
                    </span>
                </a>
            @endforeach
            <div class="bp-date-dropdown-divider"></div>
            <form action="{{ route('dashboard') }}" method="GET">
                <div class="bp-date-dropdown-heading">Custom Range</div>
                <div class="bp-date-dropdown-custom">
                    <div class="bp-date-dropdown-custom-field">
                        <label class="bp-form-label">From</label>
                        <input type="date" class="bp-form-control" name="from_date" value="{{ $curFrom }}"
                            max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="bp-date-dropdown-custom-field">
                        <label class="bp-form-label">To</label>
                        <input type="date" class="bp-form-control" name="to_date" value="{{ $curTo }}"
                            max="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <button type="submit" class="bp-btn bp-btn-primary w-100 justify-content-center mt-2">Apply</button>
            </form>
        </div>
    </div>
@endsection

@section('content')

    <!-- Order Count Cards — scoped to the selected date range -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card bp-stat-fill bp-stat-fill-c1 bp-animate-in">
                <div class="bp-stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Orders</div>
                    <div class="bp-stat-value">{{ number_format($orderCounts['total'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card bp-stat-fill bp-stat-fill-c5 bp-animate-in">
                <div class="bp-stat-icon"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Pending Orders</div>
                    <div class="bp-stat-value">{{ number_format($orderCounts['pending'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card bp-stat-fill bp-stat-fill-c2 bp-animate-in">
                <div class="bp-stat-icon"><i class="fa-solid fa-gears"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Processing Orders</div>
                    <div class="bp-stat-value">{{ number_format($orderCounts['processing'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card bp-stat-fill bp-stat-fill-c4 bp-animate-in">
                <div class="bp-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Delivered Orders</div>
                    <div class="bp-stat-value">{{ number_format($orderCounts['delivered'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card bp-stat-fill bp-stat-fill-c7 bp-animate-in">
                <div class="bp-stat-icon"><i class="fa-solid fa-pause"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Hold Orders</div>
                    <div class="bp-stat-value">{{ number_format($orderCounts['hold'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card bp-stat-fill bp-stat-fill-c3 bp-animate-in">
                <div class="bp-stat-icon"><i class="fa-solid fa-ban"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Cancelled Orders</div>
                    <div class="bp-stat-value">{{ number_format($orderCounts['cancelled'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <!-- Sales Trend Chart -->
        <div class="col-xl-8">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-chart-line me-2 text-primary"></i>Sales Trend (Last 30
                        Days)</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-chart-container bp-chart-container-lg">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-xl-4">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-wallet me-2 text-success"></i>Payment Methods</h5>
                    <span class="bp-badge bp-badge-info">{{ $activeDateLabel }}</span>
                </div>
                <div class="bp-card-body">
                    <div class="bp-chart-container bp-chart-container-lg">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row g-3 mb-4">
        <!-- Top Products -->
        <div class="col-xl-6">
            <div class="bp-card h-100">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-ranking-star me-2 text-warning"></i>Top Selling
                        Products</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="bp-badge bp-badge-info">{{ $activeDateLabel }}</span>
                        <a href="{{ route('products.index') }}" class="bp-btn bp-btn-sm bp-btn-outline">View All <i
                                class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Qty Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $index => $product)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="fw-600">{{ $product->name }}</td>
                                        <td>{{ $product->sku }}</td>
                                        <td>{{ number_format($product->total_qty, 0) }}</td>
                                        <td class="fw-800">{{ currency_symbol() }}
                                            {{ number_format($product->total_revenue, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No sales data for this period</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Expenses -->
        <div class="col-xl-6">
            <div class="bp-card h-100">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-money-bill-wave me-2 text-danger"></i>Recent Expenses
                    </h5>
                    <a href="{{ route('expenses.index') }}" class="bp-btn bp-btn-sm bp-btn-outline">View All <i
                            class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Expense #</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentExpenses as $expense)
                                    <tr>
                                        <td>{{ $expense->expense_number }}</td>
                                        <td>{{ $expense->category_name ?? 'Uncategorized' }}</td>
                                        <td class="fw-800">{{ currency_symbol() }}
                                            {{ number_format($expense->amount, 0) }}</td>
                                        <td>
                                            @if ($expense->status === 'approved')
                                                <span class="bp-badge bp-badge-success">Approved</span>
                                            @elseif($expense->status === 'pending')
                                                <span class="bp-badge bp-badge-warning">Pending</span>
                                            @else
                                                <span
                                                    class="bp-badge bp-badge-secondary">{{ ucfirst($expense->status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No expenses found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row g-3 mb-4">
        <!-- Recent Sales -->
        <div class="col-xl-7">
            <div class="bp-card h-100">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2 text-primary"></i>Recent Sales</h5>
                    <a href="{{ route('sales.index') }}" class="bp-btn bp-btn-sm bp-btn-outline">View All <i
                            class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                    <tr>
                                        <td><a
                                                href="{{ route('sales.show', $sale->id) }}">{{ $sale->invoice_number }}</a>
                                        </td>
                                        <td>{{ $sale->customer_name ?? 'Walk-in Customer' }}</td>
                                        <td class="fw-800">{{ currency_symbol() }}
                                            {{ number_format($sale->grand_total, 0) }}</td>
                                        <td>
                                            @if ($sale->payment_status === 'paid')
                                                <span class="bp-badge bp-badge-success">Paid</span>
                                            @elseif($sale->payment_status === 'partial')
                                                <span class="bp-badge bp-badge-warning">Partial</span>
                                            @elseif($sale->payment_status === 'unpaid')
                                                <span class="bp-badge bp-badge-danger">Unpaid</span>
                                            @else
                                                <span
                                                    class="bp-badge bp-badge-secondary">{{ ucfirst($sale->payment_status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No sales found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Alerts -->
        <div class="col-xl-5">
            <div class="bp-card h-100">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>Low Stock
                        Alerts</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="bp-badge bp-badge-danger">{{ $lowStockAlerts->count() }} Items</span>
                        <a href="{{ route('products.index') }}" class="bp-btn bp-btn-sm bp-btn-outline">View All <i
                                class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Stock</th>
                                    <th>Reorder Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockAlerts as $alert)
                                    <tr>
                                        <td class="fw-600">{{ $alert->name }}</td>
                                        <td class="fs-12 text-muted">{{ $alert->sku }}</td>
                                        <td>
                                            @if ($alert->quantity <= 0)
                                                <span class="bp-badge bp-badge-danger">{{ $alert->quantity }}</span>
                                            @else
                                                <span class="bp-badge bp-badge-warning">{{ $alert->quantity }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $alert->reorder_level }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No low stock alerts</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';
        // ── Date-range dropdown toggle ──
        $(function() {
            var $dd = $('#dashboardDateDropdown');
            $('#dashboardDateToggle').on('click', function(e) {
                e.stopPropagation();
                $dd.toggleClass('open');
            });
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#dashboardDateDropdown').length) {
                    $dd.removeClass('open');
                }
            });
        });
    </script>
    <script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
    <script>
        'use strict';

        // ============================================================
        // Color variables from CSS
        // ============================================================
        var primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--bp-primary').trim() || '#1B4F72';
        var primaryLightColor = getComputedStyle(document.documentElement).getPropertyValue('--bp-primary-light').trim() ||
            '#2E86C1';
        var secondaryColor = getComputedStyle(document.documentElement).getPropertyValue('--bp-secondary').trim() ||
            '#117A65';
        var accentColor = getComputedStyle(document.documentElement).getPropertyValue('--bp-accent').trim() || '#D4AC0D';
        var dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--bp-danger').trim() || '#C0392B';
        var warningColor = getComputedStyle(document.documentElement).getPropertyValue('--bp-warning').trim() || '#E67E22';
        var successColor = getComputedStyle(document.documentElement).getPropertyValue('--bp-success').trim() || '#1E8449';
        var infoColor = getComputedStyle(document.documentElement).getPropertyValue('--bp-info').trim() || '#2E86C1';

        // ============================================================
        // Data from server
        // ============================================================
        var salesTrendData = @json($salesTrend);
        var paymentData = @json($paymentBreakdown);

        // ============================================================
        // Sales Trend (Line Chart — Last 30 Days)
        // ============================================================
        (function() {
            var ctx = document.getElementById('salesChart');
            if (!ctx) return;

            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: salesTrendData.labels,
                    datasets: [{
                        label: 'Sales',
                        data: salesTrendData.data,
                        borderColor: primaryColor,
                        backgroundColor: 'rgba(27, 79, 114, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: primaryColor,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: 'Nunito Sans',
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var value = context.parsed.y;
                                    return context.dataset.label + ': {{ currency_symbol() }} ' + value
                                        .toLocaleString('en-IN');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '{{ currency_symbol() }} ' + (value / 1000).toLocaleString(
                                        'en-IN') + 'K';
                                },
                                font: {
                                    family: 'Nunito Sans',
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    family: 'Nunito Sans',
                                    size: 11
                                },
                                maxTicksLimit: 15
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        })();

        // ============================================================
        // Payment Methods (Doughnut Chart)
        // ============================================================
        (function() {
            var ctx = document.getElementById('paymentChart');
            if (!ctx) return;

            var chartColors = [successColor, dangerColor, warningColor, infoColor, secondaryColor, accentColor,
                primaryColor, primaryLightColor
            ];

            if (paymentData.labels.length === 0) {
                paymentData.labels = ['No Data'];
                paymentData.data = [1];
                chartColors = ['#ccc'];
            }

            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: paymentData.labels,
                    datasets: [{
                        data: paymentData.data,
                        backgroundColor: chartColors.slice(0, paymentData.labels.length),
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 16,
                                font: {
                                    family: 'Nunito Sans',
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var value = context.parsed;
                                    return context.label + ': {{ currency_symbol() }} ' + value
                                        .toLocaleString('en-IN');
                                }
                            }
                        }
                    }
                }
            });
        })();
    </script>
@endpush
