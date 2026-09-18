@extends('core::layouts.master')

@section('title', __('Profit & Loss'))
@section('page-title', __('Profit & Loss'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('reports.index') }}">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Profit & Loss</span>
@endsection

@section('page-actions')
    <a href="{{ route('reports.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Reports
    </a>
@endsection

@section('content')

    <div class="bp-card mb-3">
        <div class="bp-card-body">
            <form action="{{ route('reports.profit-loss') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="bp-form-label">From</label>
                    <input type="date" class="bp-form-control" name="from_date" value="{{ $filters['from_date'] }}">
                </div>
                <div class="col-md-4">
                    <label class="bp-form-label">To</label>
                    <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
                    <a href="{{ route('reports.profit-loss') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-chart-line"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Sales</div>
                    <div class="bp-stat-value">{{ money($report['total_sales']) }}</div>
                    <div class="bp-stat-change text-muted fs-11">Revenue from confirmed sales</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Gross Profit</div>
                    <div class="bp-stat-value">{{ money($report['gross_profit']) }}</div>
                    <div class="bp-stat-change text-muted fs-11">Sales − COGS ({{ currency_symbol() }}
                        {{ number_format($report['total_cogs'], 0, '.', ',') }})</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Expenses</div>
                    <div class="bp-stat-value">{{ money($report['total_expenses']) }}</div>
                    <div class="bp-stat-change text-muted fs-11">Approved expenses in range</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="bp-stat-card">
                <div class="bp-stat-icon {{ $report['is_loss'] ? 'icon-danger' : 'icon-success' }}">
                    <i class="fa-solid {{ $report['is_loss'] ? 'fa-arrow-trend-down' : 'fa-arrow-trend-up' }}"></i>
                </div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">{{ $report['is_loss'] ? 'Net Loss' : 'Net Profit' }}</div>
                    <div class="bp-stat-value">{{ money(abs($report['net_profit'])) }}</div>
                    <div class="bp-stat-change {{ $report['is_loss'] ? 'down' : 'up' }} fs-11">
                        <i class="fa-solid {{ $report['is_loss'] ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                        {{ num($report['margin_percent']) }}% margin
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table-list me-2"></i>P&amp;L Statement</h5>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <tbody>
                        <tr>
                            <td class="fw-600">Total Sales (Revenue)</td>
                            <td class="text-end fw-700 fs-15">{{ money($report['total_sales']) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-4">Less: Cost of Goods Sold (COGS)</td>
                            <td class="text-end text-muted">({{ money($report['total_cogs']) }})</td>
                        </tr>
                        <tr class="bg-light">
                            <td class="fw-700">Gross Profit</td>
                            <td class="text-end fw-800 fs-15 text-success">{{ money($report['gross_profit']) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-4">Less: Operating Expenses</td>
                            <td class="text-end text-muted">({{ money($report['total_expenses']) }})</td>
                        </tr>
                        <tr class="bg-light">
                            <td class="fw-800 fs-15">{{ $report['is_loss'] ? 'Net Loss' : 'Net Profit' }}</td>
                            <td class="text-end fw-800 fs-18 {{ $report['is_loss'] ? 'text-danger' : 'text-success' }}">
                                {{ money(abs($report['net_profit'])) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fs-13">Net Margin</td>
                            <td class="text-end fs-13 {{ $report['is_loss'] ? 'text-danger' : 'text-success' }}">
                                {{ num($report['margin_percent']) }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bp-card-footer">
            <div class="fs-11 text-muted">
                <i class="fa-solid fa-circle-info me-1"></i>
                COGS calculated as <code>SUM(sale_items.quantity × products.cost_price)</code> at the current cost price.
                Excludes cancelled sales and unapproved expenses. Period:
                {{ \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') }} →
                {{ \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') }}.
            </div>
        </div>
    </div>

@endsection
