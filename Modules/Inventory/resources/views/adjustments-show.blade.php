@extends('core::layouts.master')

@section('title', 'Adjustment — ' . $adjustment->adjustment_number)
@section('page-title', 'Adjustment: ' . $adjustment->adjustment_number)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('inventory.index') }}">Inventory</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('inventory.adjustments') }}">Adjustments</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>View</span>
@endsection

@section('page-actions')
    @if ($adjustment->status === 'draft')
        @bpCan('inventory.edit')
            <a href="{{ route('inventory.adjustments.edit', $adjustment) }}" class="bp-btn bp-btn-warning">
                <i class="fa-solid fa-pen me-1"></i> Edit</a>
        @endbpCan
    @endif
    @bpCan('inventory.view')
        <a href="{{ route('inventory.adjustments.print', $adjustment) }}" target="_blank" class="bp-btn bp-btn-success">
            <i class="fa-solid fa-print me-1"></i> Print</a>
    @endbpCan
    @if ($adjustment->status !== 'cancelled')
        @bpCan('inventory.edit')
            <form action="{{ route('inventory.adjustments.cancel', $adjustment) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="bp-btn bp-btn-danger cancel-confirm"
                    data-name="{{ $adjustment->adjustment_number }}"
                    data-approved="{{ $adjustment->status === 'approved' ? '1' : '0' }}">
                    <i class="fa-solid fa-ban me-1"></i> Cancel</button>
            </form>
        @endbpCan
    @endif
    <a href="{{ route('inventory.adjustments') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@section('content')

    @php
        $totalQty = $adjustment->items->sum('quantity');
        $totalValue = $adjustment->items->sum(fn($i) => $i->quantity * (float) $i->unit_cost);
        $qtySign = $adjustment->type === 'addition' ? '+' : '-';
    @endphp

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-list"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Items</div>
                    <div class="bp-stat-value">{{ number_format($adjustment->items->count()) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon {{ $adjustment->type === 'addition' ? 'icon-success' : 'icon-danger' }}">
                    <i class="fa-solid {{ $adjustment->type === 'addition' ? 'fa-plus' : 'fa-minus' }}"></i>
                </div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Quantity</div>
                    <div class="bp-stat-value">{{ $qtySign }}{{ number_format($totalQty) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Stock Value</div>
                    <div class="bp-stat-value">{{ money($totalValue) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-sliders"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Type</div>
                    <div class="bp-stat-value fs-16">{{ ucfirst($adjustment->type) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Left Column -->
        <div class="col-xl-8">

            <!-- Items Table -->
            <x-core::table class="mb-4">
                <x-slot:filters>
                    <h5 class="bp-card-title"><i class="fa-solid fa-list me-2 text-primary"></i>Adjustment Items</h5>
                    <span class="bp-badge bp-badge-info">{{ $adjustment->items->count() }}
                        {{ Str::plural('Item', $adjustment->items->count()) }}</span>
                </x-slot:filters>

                <x-core::table.header>
                    <x-core::table.column>#</x-core::table.column>
                    <x-core::table.column>Product</x-core::table.column>
                    <x-core::table.column>SKU</x-core::table.column>
                    <x-core::table.column align="center">Variant</x-core::table.column>
                    <x-core::table.column align="center">Quantity</x-core::table.column>
                    <x-core::table.column align="center">Unit Cost</x-core::table.column>
                    <x-core::table.column align="center">Total</x-core::table.column>
                    <x-core::table.column>Notes</x-core::table.column>
                </x-core::table.header>

                <tbody>
                    @forelse($adjustment->items as $index => $item)
                        <tr>
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td class="fw-700">{{ $item->product->name ?? '—' }}</td>
                            <td class="text-center fs-11"><code>{{ $item->product->sku ?? '—' }}</code></td>
                            <td class="text-center">{{ $item->variant->variant_name ?? '—' }}</td>
                            <td
                                class="text-center fw-700 {{ $adjustment->type === 'addition' ? 'text-success' : 'text-danger' }}">
                                {{ $qtySign }}{{ $item->quantity }}
                            </td>
                            <td class="text-center">
                                @if ($item->unit_cost)
                                    {{ money($item->unit_cost) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center fw-600">
                                @if ($item->unit_cost)
                                    {{ money($item->quantity * $item->unit_cost) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="fs-12 text-muted">{{ $item->note ?? '—' }}</td>
                        </tr>
                    @empty
                        <x-core::table.empty :colspan="8" icon="fa-list" :title="__('No items found')" />
                    @endforelse
                    @if ($adjustment->items->isNotEmpty())
                        <tr>
                            <td colspan="4" class="text-end fw-700">Totals</td>
                            <td
                                class="text-center fw-700 {{ $adjustment->type === 'addition' ? 'text-success' : 'text-danger' }}">
                                {{ $qtySign }}{{ number_format($totalQty) }}</td>
                            <td></td>
                            <td class="text-center fw-700">{{ money($totalValue) }}</td>
                            <td></td>
                        </tr>
                    @endif
                </tbody>
            </x-core::table>

            <!-- Notes -->
            @if ($adjustment->notes)
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2 text-muted"></i>Notes</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="fs-13">{{ $adjustment->notes }}</div>
                    </div>
                </div>
            @endif

            <!-- Approve Action -->
            @if ($adjustment->status === 'draft')
                @bpCan('inventory.edit')
                    <div class="bp-card mb-4">
                        <div class="bp-card-body d-flex justify-content-end">
                            <form action="{{ route('inventory.adjustments.approve', $adjustment) }}" method="POST">
                                @csrf
                                <button type="submit" class="bp-btn bp-btn-success approve-confirm"
                                    data-name="{{ $adjustment->adjustment_number }}">
                                    <i class="fa-solid fa-check me-1"></i> Approve Adjustment
                                </button>
                            </form>
                        </div>
                    </div>
                @endbpCan
            @endif

        </div>

        <!-- Right Column -->
        <div class="col-xl-4">

            <!-- Adjustment Details -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2 text-primary"></i>Adjustment Details
                    </h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Adjustment #</div>
                        <div class="bp-info-value fw-700">{{ $adjustment->adjustment_number }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Type</div>
                        <div class="bp-info-value">
                            @if ($adjustment->type === 'addition')
                                <span class="bp-badge bp-badge-success">Addition</span>
                            @else
                                <span class="bp-badge bp-badge-danger">Subtraction</span>
                            @endif
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Status</div>
                        <div class="bp-info-value">
                            @if ($adjustment->status === 'approved')
                                <span class="bp-badge bp-badge-success">Approved</span>
                            @elseif ($adjustment->status === 'cancelled')
                                <span class="bp-badge bp-badge-danger">Cancelled</span>
                            @else
                                <span class="bp-badge bp-badge-warning">Draft</span>
                            @endif
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Reason</div>
                        <div class="bp-info-value">{{ ucfirst(str_replace('_', ' ', $adjustment->reason)) }}</div>
                    </div>
                    @if ($adjustment->reference)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Reference</div>
                            <div class="bp-info-value fw-600">{{ $adjustment->reference }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Created By</div>
                        <div class="bp-info-value fw-600">{{ $adjustment->creator->name ?? '—' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Created At</div>
                        <div class="bp-info-value">{{ $adjustment->created_at->format('d M Y, h:i A') }}</div>
                    </div>
                    @if ($adjustment->updated_at && !$adjustment->updated_at->eq($adjustment->created_at))
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Last Updated</div>
                            <div class="bp-info-value">{{ $adjustment->updated_at->format('d M Y, h:i A') }}</div>
                        </div>
                    @endif
                    @if ($adjustment->status === 'approved')
                        @if ($adjustment->approver)
                            <div class="bp-info-row">
                                <div class="bp-info-label bp-info-label-lg">Approved By</div>
                                <div class="bp-info-value fw-600">{{ $adjustment->approver->name }}</div>
                            </div>
                        @endif
                        @if ($adjustment->approved_at)
                            <div class="bp-info-row">
                                <div class="bp-info-label bp-info-label-lg">Approved At</div>
                                <div class="bp-info-value">{{ $adjustment->approved_at->format('d M Y, h:i A') }}</div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            $('.approve-confirm').on('click', function(e) {
                if (!confirm('Are you sure you want to approve adjustment ' + $(this).data('name') +
                        '? This will update stock levels.')) {
                    e.preventDefault();
                }
            });

            $('.cancel-confirm').on('click', function(e) {
                var msg = 'Cancel adjustment ' + $(this).data('name') + '?';
                if ($(this).data('approved') === 1) {
                    msg += ' Stock changes will be reversed.';
                }
                if (!confirm(msg)) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endpush
