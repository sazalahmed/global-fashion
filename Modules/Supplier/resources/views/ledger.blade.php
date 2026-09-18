@extends('core::layouts.master')

@section('title', __('Supplier Ledger'))
@section('page-title', __('Supplier Ledger'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('supplier.index') }}">Suppliers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Ledger</span>
@endsection

@section('page-actions')
    <x-core::export-menu />
    <button class="bp-btn bp-btn-success" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

    <!-- Filter Bar -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form method="GET" action="{{ route('supplier.ledger', $supplier) }}" id="ledgerFilterForm">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="bp-form-label">Supplier</label>
                        <select class="bp-form-select w-100" name="supplier_id" id="supplierSelect"
                            data-url-template="{{ route('supplier.ledger', '__ID__') }}">
                            <option value="">Select Supplier</option>
                            @foreach ($suppliers ?? [] as $sup)
                                <option value="{{ $sup->id }}"
                                    {{ (int) ($supplier->id ?? 0) === (int) $sup->id ? 'selected' : '' }}>
                                    {{ $sup->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Date From</label>
                        <input type="date" class="bp-form-control" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Date To</label>
                        <input type="date" class="bp-form-control" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
                        <a href="{{ route('supplier.ledger', $supplier) }}" class="bp-btn bp-btn-danger"
                            title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Purchases (Debit)</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalDebit ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Paid (Credit)</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalCredit ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-12">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-scale-balanced"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Current Balance (Net Due)</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($currentBalance ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <x-core::table id="supplierLedgerTable">
        <x-slot:filters>
            <h5 class="bp-card-title"><i class="fa-solid fa-book me-2"></i>Supplier Ledger Statement</h5>
            <span class="bp-badge bp-badge-info">{{ $supplier->company_name ?? $supplier->name ?? '' }}</span>
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
            @forelse($ledgerEntries ?? [] as $entry)
                <tr
                    class="{{ $entry->is_opening ?? false ? 'fw-700' : '' }} {{ $entry->is_closing ?? false ? 'fw-700' : '' }}">
                    <td>{{ $entry->date ?? '' }}</td>
                    <td><a href="{{ $entry->reference_url ?? '#' }}">{{ $entry->reference ?? '' }}</a></td>
                    <td>{{ $entry->description ?? '' }}</td>
                    <td class="text-end">{{ $entry->debit ?? '' }}</td>
                    <td class="text-end">{{ $entry->credit ?? '' }}</td>
                    <td class="text-end fw-700">{{ $entry->balance ?? '' }}</td>
                </tr>
            @empty
                <x-core::table.empty colspan="6" icon="fa-solid fa-file-invoice" title="No ledger entries found for this supplier." />
            @endforelse
        </tbody>

        <x-slot:pagination>
            @if ($ledgerEntries instanceof \Illuminate\Contracts\Pagination\Paginator)
                <x-core::table.pagination :paginator="$ledgerEntries" itemLabel="entries" />
            @else
                <div class="bp-card-footer">
                    <div class="bp-pagination">
                        <span class="page-info">Showing {{ is_countable($ledgerEntries) ? count($ledgerEntries) : 0 }}
                            entries</span>
                    </div>
                </div>
            @endif
        </x-slot:pagination>
    </x-core::table>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Switch supplier: navigate to the chosen supplier's ledger route.
            // (The ledger is keyed by the {supplier} route param, not supplier_id,
            // so submitting the filter form would just reload the same supplier.)
            $('#supplierSelect').on('change', function() {
                var id = $(this).val();
                if (id) {
                    var tpl = $(this).data('url-template');
                    window.location.href = tpl.replace('__ID__', id);
                }
            });

            // Print
            $('#btnPrint').on('click', function() {
                window.print();
            });

            // Export PDF
            $('#btnExportPdf').on('click', function() {
                var params = $('#ledgerFilterForm').serialize();
                window.location.href = '{{ route('supplier.ledger', $supplier) }}?' + params +
                    '&export=pdf';
            });

            // Export Excel
            $('#btnExportExcel').on('click', function() {
                var params = $('#ledgerFilterForm').serialize();
                window.location.href = '{{ route('supplier.ledger', $supplier) }}?' + params +
                    '&export=excel';
            });
        });
    </script>
@endpush
