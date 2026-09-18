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
    <x-core::export-dropdown module="stock-ledger" />
    @endbpCan
    <button class="bp-btn bp-btn-warning" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Ledger Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search product name, SKU...">
                <select class="bp-form-select" name="product_id">
                    <option value="">Select Product</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}"
                            {{ isset($product) && $product && $product->id == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->sku }})</option>
                    @endforeach
                </select>
                <input type="date" class="bp-form-control bp-filter-date" name="date_from"
                    value="{{ request('date_from') }}">
                <span class="text-muted">to</span>
                <input type="date" class="bp-form-control bp-filter-date" name="date_to"
                    value="{{ request('date_to') }}">
                <select class="bp-form-select" name="type">
                    <option value="">All Types</option>
                    <option value="sale" {{ request('type') === 'sale' ? 'selected' : '' }}>Sale</option>
                    <option value="purchase" {{ request('type') === 'purchase' ? 'selected' : '' }}>Purchase</option>
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                    <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    <option value="return" {{ request('type') === 'return' ? 'selected' : '' }}>Return</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
            <x-core::table.column>Reference #</x-core::table.column>
            <x-core::table.column>Type</x-core::table.column>
            <x-core::table.column>Description</x-core::table.column>
            <x-core::table.column>Variant</x-core::table.column>
            <x-core::table.column align="center">Qty Change</x-core::table.column>
            <x-core::table.column align="center">Balance</x-core::table.column>
            <x-core::table.column>Unit Cost</x-core::table.column>
            <x-core::table.column>By</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @if (is_null($history))
                <x-core::table.empty :colspan="9" icon="fa-magnifying-glass" :title="__('Select a product to view its stock ledger')" />
            @else
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
                                    'transfer' => 'bp-badge-secondary',
                                    'return' => 'bp-badge-danger',
                                    default => 'bp-badge-dark',
                                };
                            @endphp
                            <span class="bp-badge {{ $typeBadge }}">{{ ucfirst($entry->source_type) }}</span>
                        </td>
                        <td>{{ $entry->description }}</td>
                        {{-- Variant as "Attribute: value" (e.g. "Size: M"); fall back to the raw variant name --}}
                        @php($variantText = $entry->variant ? $entry->variant->attributeValues->map(fn($v) => trim(($v->attribute?->base_name ? $v->attribute->base_name . ': ' : '') . $v->value))->filter()->implode(', ') ?: $entry->variant->variant_name : '')
                        <td class="fs-12">{{ $variantText ?: '—' }}</td>
                        <td class="text-center fw-700 {{ $entry->quantity_change > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $entry->quantity_change > 0 ? '+' : '' }}{{ $entry->quantity_change }}
                        </td>
                        <td class="text-center fw-800">{{ number_format($entry->quantity_after) }}</td>
                        <td>
                            {{ $entry->unit_cost ? currency_symbol() . ' ' . num($entry->unit_cost) : '' }}</td>
                        <td>{{ $entry->creator->name ?? '' }}</td>
                    </tr>
                @empty
                    <x-core::table.empty :colspan="9" icon="fa-clock-rotate-left" :title="__('No ledger entries found for this product')" />
                @endforelse
            @endif
        </tbody>

        <x-slot:pagination>
            @if ($history)
                <x-core::table.pagination :paginator="$history" itemLabel="entries" />
            @endif
        </x-slot:pagination>
    </x-core::table>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Reset filter handler
            $('.bp-filter-reset').on('click', function() {
                // Clear all filters — go to the clean path (no query string).
                window.location.href = window.location.pathname;
            });
        });
    </script>
@endpush
