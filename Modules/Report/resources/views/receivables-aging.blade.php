@extends('core::layouts.master')

@section('title', __('Receivables Aging Report'))
@section('page-title', __('Receivables Aging Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Receivables Aging</span>
@endsection

@section('page-actions')
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Aging Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Current</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($totals['current']['total'] ?? 0, 0, '.', ',') }}</div>
                    <div class="fs-11 text-muted">{{ $totals['current']['count'] ?? 0 }} invoices</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">1-30 Days</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($totals['1_30']['total'] ?? 0, 0, '.', ',') }}</div>
                    <div class="fs-11 text-muted">{{ $totals['1_30']['count'] ?? 0 }} invoices</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-calendar-week"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">31-60 Days</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($totals['31_60']['total'] ?? 0, 0, '.', ',') }}</div>
                    <div class="fs-11 text-muted">{{ $totals['31_60']['count'] ?? 0 }} invoices</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-calendar-xmark"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">61-90 Days</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($totals['61_90']['total'] ?? 0, 0, '.', ',') }}</div>
                    <div class="fs-11 text-muted">{{ $totals['61_90']['count'] ?? 0 }} invoices</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">90+ Days</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($totals['90_plus']['total'] ?? 0, 0, '.', ',') }}</div>
                    <div class="fs-11 text-muted">{{ $totals['90_plus']['count'] ?? 0 }} invoices</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grand Total -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-700">Total Receivables</h5>
                <h4 class="mb-0 fw-800 bp-badge bp-badge-danger">{{ currency_symbol() }}
                    {{ number_format($grand_total, 0, '.', ',') }}</h4>
            </div>
        </div>
    </div>

    <!-- Aging Buckets -->
    @php
        $bucketLabels = [
            'current' => 'Current (Not Yet Due)',
            '1_30' => '1-30 Days Overdue',
            '31_60' => '31-60 Days Overdue',
            '61_90' => '61-90 Days Overdue',
            '90_plus' => '90+ Days Overdue',
        ];
        $bucketBadges = [
            'current' => 'bp-badge-success',
            '1_30' => 'bp-badge-info',
            '31_60' => 'bp-badge-warning',
            '61_90' => 'bp-badge-danger',
            '90_plus' => 'bp-badge-danger',
        ];
    @endphp

    @foreach ($bucketLabels as $key => $label)
        @if (isset($buckets[$key]) && count($buckets[$key]) > 0)
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title">
                        <span class="bp-badge {{ $bucketBadges[$key] }} me-2">{{ $label }}</span>
                        <span class="fs-12 text-muted">{{ count($buckets[$key]) }} invoices</span>
                    </h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Invoice#</th>
                                    <th>Sale Date</th>
                                    <th>Due Date</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Due</th>
                                    <th class="text-center">Days Overdue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($buckets[$key] as $row)
                                    <tr>
                                        <td class="fw-700">{{ $row->customer_name }}</td>
                                        <td>{{ $row->invoice_no }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row->sale_date)->format('d M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row->due_date)->format('d M Y') }}</td>
                                        <td class="text-end">{{ currency_symbol() }}
                                            {{ number_format($row->total, 0, '.', ',') }}</td>
                                        <td class="text-end">{{ currency_symbol() }}
                                            {{ number_format($row->paid, 0, '.', ',') }}</td>
                                        <td class="text-end fw-700">{{ currency_symbol() }}
                                            {{ number_format($row->due, 0, '.', ',') }}</td>
                                        <td class="text-center">
                                            <span
                                                class="bp-badge {{ $bucketBadges[$key] }}">{{ $row->days_overdue }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

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
