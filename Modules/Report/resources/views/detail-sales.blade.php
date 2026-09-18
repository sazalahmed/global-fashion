@extends('core::layouts.master')

@section('title', __('Detail Sales Report'))
@section('page-title', __('Detail Sales Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Detail Sales</span>
@endsection

@section('page-actions')
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Filters -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form class="row g-3 align-items-end bp-report-filters" method="GET"
                action="{{ route('reports.detail-sales') }}">
                <div class="col-md-3">
                    <label class="bp-form-label">From Date</label>
                    <input type="date" class="bp-form-control" name="from_date"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="bp-form-label">To Date</label>
                    <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="bp-form-label">Customer</label>
                    <select class="bp-form-select w-100" name="customer_id">
                        <option value="">All Customers</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ ($filters['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('reports.detail-sales') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Detail Sales Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>Detail Sales</h5>
            <span class="fs-12 text-muted">{{ $data->count() }} records</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice#</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="text-center">Qty</th>
                            <th>Unit Price</th>
                            <th>Discount</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                                <td>{{ $row->invoice_no }}</td>
                                <td>{{ $row->customer_name ?? 'Walk-in' }}</td>
                                <td>{{ $row->product_name }}</td>
                                <td>{{ $row->sku ?? '' }}</td>
                                <td class="text-center">{{ number_format($row->quantity) }}</td>
                                <td>{{ currency_symbol() }}
                                    {{ number_format($row->unit_price, 0, '.', ',') }}</td>
                                <td>{{ currency_symbol() }}
                                    {{ number_format($row->discount, 0, '.', ',') }}</td>
                                <td>{{ currency_symbol() }}
                                    {{ number_format($row->total, 0, '.', ',') }}</td>
                            </tr>
                        @empty
                            <x-core::table.empty :colspan="9" icon="fa-file-lines"
                                title="No sales data found for the selected filters." />
                        @endforelse
                    </tbody>
                    @if ($data->count() > 0)
                        <tfoot>
                            <tr>
                                <td class="text-end p-2 fw-800" colspan="5">Grand Total</td>
                                <td class="text-center">{{ number_format($data->sum('quantity')) }}</td>
                                <td></td>
                                <td class="p-2 fw-800">{{ currency_symbol() }}
                                    {{ number_format($data->sum('discount'), 0, '.', ',') }}</td>
                                <td class="p-2 fw-800">{{ currency_symbol() }}
                                    {{ number_format($data->sum('total'), 0, '.', ',') }}</td>
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
