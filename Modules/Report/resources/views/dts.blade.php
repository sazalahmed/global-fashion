@extends('core::layouts.master')

@section('title', __('Daily Transaction Summary'))
@section('page-title', __('Daily Transaction Summary (DTS)'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('reports.index') }}">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>DTS</span>
@endsection

@section('page-actions')
    <form action="{{ route('reports.dts') }}" method="GET" class="d-flex gap-2 align-items-center">
        <input type="date" class="bp-form-control" name="date" value="{{ $date }}"
            onchange="this.form.submit()">
        <button type="button" class="bp-btn bp-btn-primary bp-btn-sm"
            onclick="window.location='{{ route('reports.dts') }}'">Today</button>
    </form>
@endsection

@php
    $s = $summary;
@endphp

@section('content')

    <!-- Date Header -->
    <div class="bp-card mb-4">
        <div class="bp-card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-800 mb-0">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</h5>
                    <span class="text-muted fs-13">Complete financial overview for the day</span>
                </div>
                <div class="text-end">
                    <div class="fs-12 text-muted">Net Cash Position</div>
                    <div class="fw-800 fs-18 {{ $s['net_cash'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ currency_symbol() }} {{ number_format(abs($s['net_cash']), 0) }}
                        @if ($s['net_cash'] < 0)
                            <span class="fs-12">(deficit)</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-chart-line"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Sales</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($s['sales']['total'], 0) }}</div>
                    <div class="bp-stat-change"><span class="text-muted">{{ $s['sales']['count'] }} invoices</span></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-cart-plus"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Purchases</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($s['purchases']['total'], 0) }}
                    </div>
                    <div class="bp-stat-change"><span class="text-muted">{{ $s['purchases']['count'] }} orders</span></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Received</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($s['received']['total'], 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Paid Out + Expenses</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($s['paid_out']['total'] + $s['expenses']['total'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-xl-8">

            <!-- Sales Breakdown -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-chart-line me-2 text-primary"></i>Sales</h5>
                    <span class="bp-badge bp-badge-primary">{{ $s['sales']['count'] }} Sales</span>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label">POS</div>
                                <div class="bp-info-value fw-700">{{ currency_symbol() }}
                                    {{ number_format($s['sales']['pos'], 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label">Store</div>
                                <div class="bp-info-value fw-700">{{ currency_symbol() }}
                                    {{ number_format($s['sales']['store'], 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label">eCommerce</div>
                                <div class="bp-info-value fw-700">{{ currency_symbol() }}
                                    {{ number_format($s['sales']['ecom'], 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label">Due</div>
                                <div class="bp-info-value fw-700 text-danger">{{ currency_symbol() }}
                                    {{ number_format($s['sales']['due'], 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchases -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-cart-plus me-2 text-warning"></i>Purchases</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label">Total</div>
                                <div class="bp-info-value fw-700">{{ currency_symbol() }}
                                    {{ number_format($s['purchases']['total'], 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label">Paid</div>
                                <div class="bp-info-value fw-700">{{ currency_symbol() }}
                                    {{ number_format($s['purchases']['paid'], 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label">Due</div>
                                <div class="bp-info-value fw-700 text-danger">{{ currency_symbol() }}
                                    {{ number_format($s['purchases']['due'], 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenses -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-money-bill-wave me-2 text-danger"></i>Expenses</h5>
                    <span class="bp-badge bp-badge-danger">{{ $s['expenses']['count'] }} Expenses —
                        {{ currency_symbol() }} {{ number_format($s['expenses']['total'], 0) }}</span>
                </div>
                @if (!empty($s['expenses']['by_category']))
                    <div class="bp-card-body p-0">
                        <div class="bp-table-wrapper">
                            <table class="bp-table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($s['expenses']['by_category'] as $cat => $amt)
                                        <tr>
                                            <td class="fw-600">{{ $cat }}</td>
                                            <td class="text-end fw-700">{{ currency_symbol() }}
                                                {{ number_format($amt, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bp-card-body text-center text-muted py-3">No expenses recorded</div>
                @endif
            </div>

            <!-- Returns -->
            @if ($s['returns']['sale_returns'] > 0 || $s['returns']['purchase_returns'] > 0)
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-rotate-left me-2 text-muted"></i>Returns</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="bp-info-row bp-info-row-compact">
                                    <div class="bp-info-label">Sale Returns</div>
                                    <div class="bp-info-value fw-700 text-danger">{{ currency_symbol() }}
                                        {{ number_format($s['returns']['sale_returns'], 0) }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bp-info-row bp-info-row-compact">
                                    <div class="bp-info-label">Purchase Returns</div>
                                    <div class="bp-info-value fw-700 text-success">{{ currency_symbol() }}
                                        {{ number_format($s['returns']['purchase_returns'], 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Column -->
        <div class="col-xl-4">

            <!-- Payments Received -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-arrow-down me-2 text-success"></i>Received</h5>
                    <span class="fw-800 text-success">{{ currency_symbol() }}
                        {{ number_format($s['received']['total'], 0) }}</span>
                </div>
                <div class="bp-card-body">
                    @forelse($s['received']['by_method'] as $method => $amount)
                        <div class="bp-info-row bp-info-row-compact">
                            <div class="bp-info-label">{{ ucfirst(str_replace('_', ' ', $method)) }}</div>
                            <div class="bp-info-value fw-600">{{ currency_symbol() }} {{ number_format($amount, 0) }}
                            </div>
                        </div>
                    @empty
                        <div class="text-muted text-center py-2">No payments received</div>
                    @endforelse
                </div>
            </div>

            <!-- Payments Paid Out -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-arrow-up me-2 text-danger"></i>Paid Out</h5>
                    <span class="fw-800 text-danger">{{ currency_symbol() }}
                        {{ number_format($s['paid_out']['total'], 0) }}</span>
                </div>
                <div class="bp-card-body">
                    @forelse($s['paid_out']['by_type'] as $type => $amount)
                        <div class="bp-info-row bp-info-row-compact">
                            <div class="bp-info-label">{{ ucfirst(str_replace('_', ' ', $type)) }}</div>
                            <div class="bp-info-value fw-600">{{ currency_symbol() }} {{ number_format($amount, 0) }}
                            </div>
                        </div>
                    @empty
                        <div class="text-muted text-center py-2">No payments made</div>
                    @endforelse
                </div>
            </div>

            <!-- Net Cash Summary -->
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2 text-primary"></i>Cash Summary</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-cart-summary-row">
                        <span>Received</span>
                        <span class="fw-700 text-success">+ {{ currency_symbol() }}
                            {{ number_format($s['received']['total'], 0) }}</span>
                    </div>
                    <div class="bp-cart-summary-row">
                        <span>Paid Out</span>
                        <span class="fw-700 text-danger">- {{ currency_symbol() }}
                            {{ number_format($s['paid_out']['total'], 0) }}</span>
                    </div>
                    <div class="bp-cart-summary-row">
                        <span>Expenses</span>
                        <span class="fw-700 text-danger">- {{ currency_symbol() }}
                            {{ number_format($s['expenses']['total'], 0) }}</span>
                    </div>
                    <div class="bp-cart-summary-row total">
                        <span>Net Cash</span>
                        <span class="fw-800 {{ $s['net_cash'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ currency_symbol() }} {{ number_format(abs($s['net_cash']), 0) }}
                            @if ($s['net_cash'] < 0)
                                (deficit)
                            @endif
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection
