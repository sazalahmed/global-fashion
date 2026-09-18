@extends('core::layouts.master')

@section('title', __('Stock Ledger'))
@section('page-title', __('Stock Ledger'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('inventory.index') }}">Inventory</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Stock Ledger</span>
@endsection

@section('page-actions')
    @bpCan('inventory.export')
    <x-core::export-menu />
    @endbpCan
    <button class="bp-btn bp-btn-success" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

    <!-- Filter Bar -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form method="GET" action="{{ route('inventory.stock-ledger') }}" id="stockLedgerFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="bp-form-label">Product</label>
                        <select class="bp-form-select select2-search w-100" name="product_id" id="productSelect">
                            <option value="">Select Product</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="bp-form-label">Date From</label>
                        <input type="date" class="bp-form-control" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="bp-form-label">Date To</label>
                        <input type="date" class="bp-form-control" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="bp-form-label">Movement Type</label>
                        <select class="bp-form-select w-100" name="movement_type">
                            <option value="">All Types</option>
                            <option value="sale" {{ request('movement_type') == 'sale' ? 'selected' : '' }}>Sale</option>
                            <option value="purchase" {{ request('movement_type') == 'purchase' ? 'selected' : '' }}>Purchase
                            </option>
                            <option value="adjustment" {{ request('movement_type') == 'adjustment' ? 'selected' : '' }}>
                                Adjustment</option>
                            <option value="transfer_in" {{ request('movement_type') == 'transfer_in' ? 'selected' : '' }}>
                                Transfer In</option>
                            <option value="transfer_out"
                                {{ request('movement_type') == 'transfer_out' ? 'selected' : '' }}>Transfer Out</option>
                            <option value="return" {{ request('movement_type') == 'return' ? 'selected' : '' }}>Return
                            </option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex gap-2">
                        <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
                        <a href="{{ route('inventory.stock-ledger') }}" class="bp-btn bp-btn-danger"
                            title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($product)
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="bp-stat-card">
                    <div class="bp-stat-icon icon-info"><i class="fa-solid fa-box-open"></i></div>
                    <div class="bp-stat-content">
                        <div class="bp-stat-label">Opening Stock</div>
                        <div class="bp-stat-value">{{ $openingStock ?? 0 }} pcs</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="bp-stat-card">
                    <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down"></i></div>
                    <div class="bp-stat-content">
                        <div class="bp-stat-label">Total In</div>
                        <div class="bp-stat-value">{{ $totalIn ?? 0 }} pcs</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="bp-stat-card">
                    <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up"></i></div>
                    <div class="bp-stat-content">
                        <div class="bp-stat-label">Total Out</div>
                        <div class="bp-stat-value">{{ $totalOut ?? 0 }} pcs</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="bp-stat-card">
                    <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="bp-stat-content">
                        <div class="bp-stat-label">Closing Stock</div>
                        <div class="bp-stat-value">{{ $closingStock ?? 0 }} pcs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Ledger Table -->
        <x-core::table id="stockLedgerTable">
            <x-slot:filters>
                <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Stock Movement History</h5>
                <span class="bp-badge bp-badge-info">{{ $product->name }}</span>
            </x-slot:filters>

            <x-core::table.header>
                <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
                <x-core::table.column>Reference #</x-core::table.column>
                <x-core::table.column>Type</x-core::table.column>
                <x-core::table.column>Description</x-core::table.column>
                <x-core::table.column>Variant</x-core::table.column>
                <x-core::table.column align="center">Qty Change</x-core::table.column>
                <x-core::table.column align="center">Balance</x-core::table.column>
                <x-core::table.column>By</x-core::table.column>
            </x-core::table.header>

            <tbody>
                @forelse($history as $entry)
                    <tr>
                        <td>{{ $entry->created_at->format('d M Y') }}</td>
                        <td class="fw-700">{{ $entry->source_type }}-{{ $entry->source_id }}</td>
                        <td>
                            @php
                                $typeBadge = match (strtolower($entry->source_type)) {
                                    'sale' => 'bp-badge-primary',
                                    'purchase' => 'bp-badge-info',
                                    'adjustment' => 'bp-badge-warning',
                                    'transfer_in' => 'bp-badge-success',
                                    'transfer_out' => 'bp-badge-secondary',
                                    'return' => 'bp-badge-danger',
                                    default => 'bp-badge-dark',
                                };
                                $typeLabel = match (strtolower($entry->source_type)) {
                                    'transfer_in' => 'Transfer In',
                                    'transfer_out' => 'Transfer Out',
                                    default => ucfirst($entry->source_type),
                                };
                            @endphp
                            <span class="bp-badge {{ $typeBadge }}">{{ $typeLabel }}</span>
                        </td>
                        <td>{{ $entry->description }}</td>
                        {{-- Variant as "Attribute: value" (e.g. "Size: M"); fall back to the raw variant name --}}
                        @php($variantText = $entry->variant ? $entry->variant->attributeValues->map(fn($v) => trim(($v->attribute?->base_name ? $v->attribute->base_name . ': ' : '') . $v->value))->filter()->implode(', ') ?: $entry->variant->variant_name : '')
                        <td class="fs-12">{{ $variantText ?: '—' }}</td>
                        <td class="text-center fw-700 {{ $entry->quantity_change > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $entry->quantity_change > 0 ? '+' . $entry->quantity_change : $entry->quantity_change }}
                        </td>
                        <td class="text-center fw-700">{{ number_format($entry->quantity_after) }}</td>
                        <td>{{ $entry->creator->name ?? '' }}</td>
                    </tr>
                @empty
                    <x-core::table.empty :colspan="8" icon="fa-clock-rotate-left" :title="__('No stock movement entries found')" />
                @endforelse
            </tbody>

            <x-slot:pagination>
                @if ($history->hasPages())
                    <x-core::table.pagination :paginator="$history" itemLabel="movements" />
                @else
                    <div class="bp-card-footer">
                        <div class="bp-pagination">
                            <span class="page-info">Showing {{ $history->count() }} of
                                {{ number_format($history->total()) }} movements</span>
                        </div>
                    </div>
                @endif
            </x-slot:pagination>
        </x-core::table>
    @else
        <!-- No product selected -->
        <div class="bp-card">
            <div class="bp-card-body text-center py-5">
                <i class="fa-solid fa-magnifying-glass fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Select a product to view its stock ledger</h5>
                <p class="text-muted fs-13">Use the product dropdown above to choose a product and see its movement
                    history.</p>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Filter form auto-submit on product change
            $('#productSelect').on('change', function() {
                if ($(this).val()) {
                    $('#stockLedgerFilterForm').submit();
                }
            });

            // Auto-submit on movement type change
            $('select[name="movement_type"]').on('change', function() {
                $('#stockLedgerFilterForm').submit();
            });

            // Print
            $('#btnPrint').on('click', function() {
                window.print();
            });

            // Export PDF
            $('#btnExportPdf').on('click', function() {
                var params = $('#stockLedgerFilterForm').serialize();
                window.location.href = '{{ route('inventory.stock-ledger') }}?' + params + '&export=pdf';
            });

            // Export Excel
            $('#btnExportExcel').on('click', function() {
                var params = $('#stockLedgerFilterForm').serialize();
                window.location.href = '{{ route('inventory.stock-ledger') }}?' + params + '&export=excel';
            });
        });
    </script>
@endpush
