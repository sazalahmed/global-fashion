@extends('core::layouts.master')

@section('title', __('Customer Ledger'))
@section('page-title', __('Customer Ledger'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('customers.index') }}">Customers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Ledger</span>
@endsection

@section('page-actions')
    @bpCan('customers.export')
        <x-core::export-menu />
    @endbpCan
    <button class="bp-btn bp-btn-success" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

    <!-- Filter Bar -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form method="GET" action="{{ route('customers.ledger') }}" id="ledgerFilterForm">
                @csrf
                <div class="row g-3 align-items-end customer_ledger_search">
                    <div class="col-md">
                        <label class="bp-form-label">Customer</label>
                        <select class="bp-form-select w-100 select2-search" name="customer_id" id="customerSelect">
                            <option value="">Select Customer</option>
                            @foreach ($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md">
                        <label class="bp-form-label">Date From</label>
                        <input type="date" class="bp-form-control" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md">
                        <label class="bp-form-label">Date To</label>
                        <input type="date" class="bp-form-control" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-auto d-flex gap-2">
                        <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i
                                class="fa-solid fa-filter"></i></button>
                        <a href="{{ route('customers.ledger') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i
                                class="fa-solid fa-rotate"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-file-invoice"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Debit</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ $totalDebit }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Credit</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ $totalCredit }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon {{ $currentBalance > 0 ? 'icon-danger' : 'icon-info' }}"><i
                        class="fa-solid fa-scale-balanced"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Net Balance</div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bp-stat-value">{{ currency_symbol() }} {{ $currentBalance }}</div>
                        <div class="fs-11 text-muted">
                            {{ $currentBalance > 0 ? 'Customer owes you' : ($currentBalance < 0 ? 'You owe customer (advance)' : 'Settled') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-wallet"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Advance Balance</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ $advanceBalance ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <x-core::table id="customerLedgerTable">
        <x-slot:filters>
            <h5 class="bp-card-title"><i class="fa-solid fa-book me-2"></i>Customer Ledger Statement</h5>
            <span class="bp-badge bp-badge-warning">{{ $customerName }}</span>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
            <x-core::table.column>Reference #</x-core::table.column>
            <x-core::table.column>Description</x-core::table.column>
            <x-core::table.column align="end">Debit ({{ currency_symbol() }})</x-core::table.column>
            <x-core::table.column align="end">Credit ({{ currency_symbol() }})</x-core::table.column>
            <x-core::table.column align="end">Balance ({{ currency_symbol() }})</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($ledgerEntries as $entry)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</td>
                    <td>
                        @if ($entry->source_type === 'sale')
                            <a href="{{ route('sales.show', $entry->source_id) }}"
                                class="fw-600 text-primary">{{ $entry->reference }}</a>
                        @elseif($entry->source_type === 'payment')
                            <a href="{{ route('payments.show', $entry->source_id) }}"
                                class="fw-600 text-primary">{{ $entry->reference }}</a>
                        @else
                            <span class="fw-600">{{ $entry->reference ?? '' }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($entry->type === 'Advance Received')
                            <span class="bp-badge bp-badge-info">{{ $entry->description }}</span>
                        @elseif($entry->type === 'Advance Adjusted')
                            <span class="bp-badge bp-badge-warning">{{ $entry->description }}</span>
                        @else
                            {{ $entry->description ?? '' }}
                        @endif
                    </td>
                    <td>
                        {{ $entry->debit > 0 ? currency_symbol() . ' ' . number_format($entry->debit) : '' }}</td>
                    <td>
                        {{ $entry->credit > 0 ? currency_symbol() . ' ' . number_format($entry->credit) : '' }}</td>
                    <td class="{{ ($entry->balance ?? 0) < 0 ? 'text-info' : '' }}">
                        {{ currency_symbol() }} {{ number_format($entry->balance ?? 0) }}</td>
                </tr>
            @empty
                <x-core::table.empty colspan="6" icon="fa-solid fa-book" :title="request('customer_id')
                    ? 'No ledger entries found for the selected period.'
                    : 'Select a customer to view their ledger.'" />
            @endforelse
        </tbody>

        @if (isset($ledgerEntries) && method_exists($ledgerEntries, 'hasPages'))
            <x-slot:pagination>
                <x-core::table.pagination :paginator="$ledgerEntries" itemLabel="entries" />
            </x-slot:pagination>
        @else
            <x-slot:pagination>
                <div class="bp-card-footer">
                    <div class="bp-pagination">
                        <span class="page-info">Showing {{ is_countable($ledgerEntries) ? count($ledgerEntries) : 0 }}
                            entries</span>
                    </div>
                </div>
            </x-slot:pagination>
        @endif
    </x-core::table>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Filter form auto-submit on customer change
            $('#customerSelect').on('change', function() {
                if ($(this).val()) {
                    $('#ledgerFilterForm').submit();
                }
            });

            // Print
            $('#btnPrint').on('click', function() {
                window.print();
            });

            // Export PDF
            $('#btnExportPdf').on('click', function() {
                var params = $('#ledgerFilterForm').serialize();
                window.location.href = '{{ route('customers.ledger') }}?' + params + '&export=pdf';
            });

            // Export Excel
            $('#btnExportExcel').on('click', function() {
                var params = $('#ledgerFilterForm').serialize();
                window.location.href = '{{ route('customers.ledger') }}?' + params + '&export=excel';
            });
        });
    </script>
@endpush
