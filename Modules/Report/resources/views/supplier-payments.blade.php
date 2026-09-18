@extends('core::layouts.master')

@section('title', __('Supplier Payment Report'))
@section('page-title', __('Supplier Payment Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Supplier Payments</span>
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
            <form class="row g-3 align-items-end bp-report-filters" method="GET" action="{{ route('reports.supplier-payments') }}">
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
                    <a href="{{ route('reports.supplier-payments') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
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
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-truck-field"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Suppliers</div>
                    <div class="bp-stat-value">{{ $data->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-cart-plus"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Purchases</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($data->sum('total_purchase'), 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">All-Time Paid</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($data->sum('all_time_paid'), 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Due Balance</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($data->sum('due_balance'), 0, '.', ',') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Payment Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>Supplier Payments</h5>
            <span class="fs-12 text-muted">{{ $data->count() }} suppliers</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Contact</th>
                            <th>Phone</th>
                            <th class="text-end">Total Purchase</th>
                            <th class="text-end">All-Time Paid</th>
                            <th class="text-end">Due Balance</th>
                            <th class="text-end">Period Paid</th>
                            <th>Last Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr>
                                <td class="fw-700">{{ $row->company_name }}</td>
                                <td>{{ $row->contact_person ?? '' }}</td>
                                <td>{{ $row->phone ?? '' }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($row->total_purchase, 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($row->all_time_paid, 0, '.', ',') }}</td>
                                <td class="text-end">
                                    @if ($row->due_balance > 0)
                                        <span class="bp-badge bp-badge-danger">{{ currency_symbol() }}
                                            {{ number_format($row->due_balance, 0, '.', ',') }}</span>
                                    @else
                                        <span class="bp-badge bp-badge-success">{{ currency_symbol() }} 0</span>
                                    @endif
                                </td>
                                <td class="text-end fw-700">{{ currency_symbol() }}
                                    {{ number_format($row->period_paid, 0, '.', ',') }}</td>
                                <td>{{ $row->last_payment_date ? \Carbon\Carbon::parse($row->last_payment_date)->format('d M Y') : '' }}
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty :colspan="8" icon="fa-truck-field"
                                title="No supplier payment data found for the selected period." />
                        @endforelse
                    </tbody>
                    @if ($data->count() > 0)
                        <tfoot>
                            <tr class="fw-700">
                                <td colspan="3">Grand Total</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('total_purchase'), 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('all_time_paid'), 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('due_balance'), 0, '.', ',') }}</td>
                                <td class="text-end">{{ currency_symbol() }}
                                    {{ number_format($data->sum('period_paid'), 0, '.', ',') }}</td>
                                <td></td>
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
