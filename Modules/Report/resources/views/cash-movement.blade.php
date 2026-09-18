@extends('core::layouts.master')

@section('title', __('Cash Movement Report'))
@section('page-title', __('Cash Movement Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Cash Movement</span>
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
            <form class="row g-3 align-items-end bp-report-filters" method="GET" action="{{ route('reports.cash-movement') }}">
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
                    <a href="{{ route('reports.cash-movement') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Received & Paid Out -->
    <div class="row g-3 mb-4">
        <!-- Received -->
        <div class="col-md-6">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-arrow-down me-2"></i>Received</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th class="text-end">Total ({{ currency_symbol() }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($received as $method => $total)
                                    <tr>
                                        <td class="fw-600">{{ $method }}</td>
                                        <td class="text-end fw-700">{{ currency_symbol() }}
                                            {{ number_format($total, 0, '.', ',') }}</td>
                                    </tr>
                                @empty
                                    <x-core::table.empty :colspan="2" icon="fa-arrow-down"
                                        title="No received payments found." />
                                @endforelse
                            </tbody>
                            @if (count($received) > 0)
                                <tfoot>
                                    <tr class="fw-700">
                                        <td>Total Received</td>
                                        <td class="text-end">{{ currency_symbol() }}
                                            {{ number_format($total_received, 0, '.', ',') }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paid Out -->
        <div class="col-md-6">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-arrow-up me-2"></i>Paid Out</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Payment Type</th>
                                    <th class="text-end">Total ({{ currency_symbol() }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paid as $type => $total)
                                    <tr>
                                        <td class="fw-600">{{ $type }}</td>
                                        <td class="text-end fw-700">{{ currency_symbol() }}
                                            {{ number_format($total, 0, '.', ',') }}</td>
                                    </tr>
                                @empty
                                    <x-core::table.empty :colspan="2" icon="fa-arrow-up"
                                        title="No paid out records found." />
                                @endforelse
                            </tbody>
                            @if (count($paid) > 0)
                                <tfoot>
                                    <tr class="fw-700">
                                        <td>Total Paid Out</td>
                                        <td class="text-end">{{ currency_symbol() }}
                                            {{ number_format($total_paid, 0, '.', ',') }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Cash Position -->
    <div class="bp-card">
        <div class="bp-card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-700"><i class="fa-solid fa-scale-balanced me-2"></i>Net Cash Position</h5>
                <h4 class="mb-0 fw-800">
                    <span class="bp-badge {{ $net >= 0 ? 'bp-badge-success' : 'bp-badge-danger' }}">
                        {{ currency_symbol() }} {{ number_format(abs($net), 0, '.', ',') }}
                        @if ($net < 0)
                            (Deficit)
                        @endif
                    </span>
                </h4>
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
