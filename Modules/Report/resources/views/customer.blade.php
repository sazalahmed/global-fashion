@extends('core::layouts.master')

@section('title', __('Customer Report'))
@section('page-title', __('Customer Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Customer Report</span>
@endsection

@section('page-actions')
    @bpCan('reports.export')
        <x-core::export-dropdown module="report-customers" />
    @endbpCan
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Date Range & Filters -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form class="row g-3 align-items-end bp-report-filters" method="GET" action="{{ route('reports.customer') }}">
                <div class="col-md-2">
                    <label class="bp-form-label">From Date</label>
                    <input type="date" class="bp-form-control" name="from_date"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">To Date</label>
                    <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">Report Type</label>
                    <select class="bp-form-select w-100" name="report_type" id="customerReportType">
                        <option value="top" {{ $reportType === 'top' ? 'selected' : '' }}>Top Customers</option>
                        <option value="ledger" {{ $reportType === 'ledger' ? 'selected' : '' }}>Customer Ledger</option>
                        <option value="aging" {{ $reportType === 'aging' ? 'selected' : '' }}>Aging Report</option>
                    </select>
                </div>
                <div class="col-md-3" id="customerSelectWrapper">
                    <label class="bp-form-label">Customer</label>
                    <select class="bp-form-select w-100" name="customer_id">
                        <option value="">Select Customer</option>
                        @foreach ($customers as $cust)
                            <option value="{{ $cust->id }}"
                                {{ ($filters['customer_id'] ?? '') == $cust->id ? 'selected' : '' }}>{{ $cust->name }}
                                ({{ $cust->phone }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('reports.customer') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>
                @if ($reportType === 'top')
                    Top Customers by Revenue
                @elseif($reportType === 'ledger')
                    Customer Ledger
                @elseif($reportType === 'aging')
                    Accounts Receivable Aging
                @endif
            </h5>
            <span class="fs-12 text-muted">{{ $data->count() }} records</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        @if ($reportType === 'top')
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th class="text-center">Orders</th>
                                <th class="text-end">Total Spent</th>
                                <th class="text-end">Due</th>
                            </tr>
                        @elseif($reportType === 'ledger')
                            <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Due</th>
                            </tr>
                        @elseif($reportType === 'aging')
                            <tr>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th class="text-end">Current (0-30d)</th>
                                <th class="text-end">31-60 Days</th>
                                <th class="text-end">61-90 Days</th>
                                <th class="text-end">Over 90 Days</th>
                                <th class="text-end">Total Due</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($data as $index => $row)
                            @if ($reportType === 'top')
                                <tr>
                                    <td class="fw-700">{{ $index + 1 }}</td>
                                    <td class="fw-700">{{ $row->name }}</td>
                                    <td>{{ $row->phone ?? '' }}</td>
                                    <td class="text-center">{{ $row->total_orders }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->total_spent, 0, '.', ',') }}</td>
                                    <td class="text-end {{ $row->total_due > 0 ? 'text-danger fw-700' : '' }}">
                                        {{ currency_symbol() }} {{ number_format($row->total_due, 0, '.', ',') }}</td>
                                </tr>
                            @elseif($reportType === 'ledger')
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                                    <td class="fw-700">{{ $row->invoice_number }}</td>
                                    <td><span class="bp-badge bp-badge-primary">{{ ucfirst($row->type) }}</span></td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($row->amount, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->paid_amount, 0, '.', ',') }}</td>
                                    <td class="text-end {{ $row->due_amount > 0 ? 'text-danger fw-700' : '' }}">
                                        {{ currency_symbol() }} {{ number_format($row->due_amount, 0, '.', ',') }}</td>
                                </tr>
                            @elseif($reportType === 'aging')
                                <tr>
                                    <td class="fw-700">{{ $row->name }}</td>
                                    <td>{{ $row->phone ?? '' }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->current_due, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->days_31_60, 0, '.', ',') }}</td>
                                    <td class="text-end">{{ currency_symbol() }}
                                        {{ number_format($row->days_61_90, 0, '.', ',') }}</td>
                                    <td class="text-end {{ $row->over_90 > 0 ? 'text-danger fw-700' : '' }}">
                                        {{ currency_symbol() }} {{ number_format($row->over_90, 0, '.', ',') }}</td>
                                    <td class="text-end fw-700 text-danger">{{ currency_symbol() }}
                                        {{ number_format($row->total_due, 0, '.', ',') }}</td>
                                </tr>
                            @endif
                        @empty
                            <x-core::table.empty :colspan="7" icon="fa-users" :title="$reportType === 'ledger' && empty($filters['customer_id']) ? 'Please select a customer to view their ledger.' : 'No customer data found for the selected filters.'" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Print report
            $('#btnPrintReport').on('click', function() {
                window.print();
            });
        });
    </script>
@endpush
