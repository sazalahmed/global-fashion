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
    <div class="d-flex align-items-center gap-2">
        <form action="{{ route('reports.profit-loss') }}" method="GET" class="mb-0">
            <input type="hidden" name="from_date" value="{{ $filters['from_date'] }}">
            <input type="hidden" name="to_date" value="{{ $filters['to_date'] }}">
            <input type="hidden" name="all_sales" value="1"> 
            
            @if(!empty($filters['only_delivered']))
                <!-- Button is active, clicking it will remove the only_delivered filter -->
                <button type="submit" class="bp-btn bp-btn-success" title="Click to show all sales">
                    <i class="fa-solid fa-toggle-on me-1"></i> Only Delivered
                </button>
            @else
                <!-- Button is inactive, clicking it will add the only_delivered filter -->
                <input type="hidden" name="only_delivered" value="1">
                <button type="submit" class="bp-btn bp-btn-outline" title="Click to show only delivered">
                    <i class="fa-solid fa-toggle-off me-1"></i> Only Delivered
                </button>
            @endif
        </form>
        <a href="{{ route('reports.index') }}" class="bp-btn bp-btn-primary">
            <i class="fa-solid fa-arrow-left"></i> Back to Reports
        </a>
    </div>
@endsection

@section('content')

    <div class="bp-card mb-3">
        <div class="bp-card-body">
            <form action="{{ route('reports.profit-loss') }}" method="GET" class="row g-3 align-items-end">
                @if(!empty($filters['only_delivered']))
                    <input type="hidden" name="only_delivered" value="1">
                @else
                    <input type="hidden" name="all_sales" value="1">
                @endif
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
                        @if(!isset($report['manual_expenses']))
                            <tr>
                                <td class="text-muted ps-4">Less: Cost of Goods Sold (COGS)</td>
                                <td class="text-end text-muted">({{ money($report['total_cogs']) }})</td>
                            </tr>
                        @endif
                        <tr class="bg-light">
                            <td class="fw-700">Gross Profit</td>
                            <td class="text-end fw-800 fs-15 text-success">{{ money($report['gross_profit']) }}</td>
                        </tr>
                        @if(isset($report['manual_expenses']))
                            <tr>
                                <td class="text-muted ps-4">Less: Total Expense</td>
                                <td class="text-end text-muted">({{ money($report['manual_expenses']) }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4">Less: Total Salary given</td>
                                <td class="text-end text-muted">({{ money($report['salary_given']) }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4">Less: COD Charge</td>
                                <td class="text-end text-muted">({{ money($report['cod_charge']) }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4">Less: Courier Delivery charges</td>
                                <td class="text-end text-muted">({{ money($report['courier_delivery']) }})</td>
                            </tr>
                            <tr class="bg-light">
                                <td class="fw-700">Total Deductions</td>
                                <td class="text-end fw-700 text-muted">({{ money($report['total_expenses']) }})</td>
                            </tr>
                        @else
                            <tr>
                                <td class="text-muted ps-4">Less: Operating Expenses</td>
                                <td class="text-end text-muted">({{ money($report['total_expenses']) }})</td>
                            </tr>
                        @endif
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
                @if(isset($report['manual_expenses']))
                    Net Profit is calculated as <code>Delivered Sales - (Total Expense + Salary + COD Charge + Courier)</code> based on Cashflow data. Product costs (COGS) are excluded.
                @else
                    <strong>Standard Calculation:</strong> Net Profit is calculated as <code>Total Sales - (Cost of Goods Sold + All Approved Operating Expenses)</code> based on standard accounting rules. Cost of Goods Sold (COGS) is determined by multiplying the quantity of each item sold by its cost price. Cancelled sales are excluded.
                @endif
                Period:
                {{ \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') }} →
                {{ \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') }}.
            </div>
        </div>
    </div>

@endsection
