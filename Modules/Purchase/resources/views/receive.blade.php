@extends('core::layouts.master')

@section('title', 'Receive Stock — ' . $purchase->po_number)
@section('page-title', __('Receive Stock'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('purchases.index') }}">Purchases</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->po_number }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Receive Stock</span>
@endsection

@section('page-actions')
    <a href="{{ route('purchases.show', $purchase) }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i> Back to PO</a>
@endsection

@section('content')

    <form action="{{ route('purchases.receive.store', $purchase) }}" method="POST" id="grnForm">
        @csrf

        <div class="row g-4">
            <div class="col-xl-8">

                <!-- PO Summary -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i
                                class="fa-solid fa-file-invoice me-2 text-primary"></i>{{ $purchase->po_number }}</h5>
                        @php
                            $statusBadge = match ($purchase->status) {
                                'approved' => 'bp-badge-primary',
                                'partial_received' => 'bp-badge-warning',
                                default => 'bp-badge-secondary',
                            };
                        @endphp
                        <span
                            class="bp-badge {{ $statusBadge }}">{{ str_replace('_', ' ', ucfirst($purchase->status)) }}</span>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="bp-info-label">Supplier</div>
                                <div class="fw-700">{{ $purchase->supplier->company_name ?? 'N/A' }}</div>
                                @if ($purchase->supplier?->phone)
                                    <div class="fs-12 text-muted">{{ $purchase->supplier->phone }}</div>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <div class="bp-info-label">Branch</div>
                                <div class="fw-600">{{ $purchase->branch->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="bp-info-label">PO Date</div>
                                <div>{{ $purchase->po_date->format('d M Y') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="bp-info-label">Grand Total</div>
                                <div class="fw-800 text-primary">{{ currency_symbol() }}
                                    {{ number_format($purchase->grand_total, 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items to Receive -->
                @php
                    $pendingItems = $purchase->items->filter(
                        fn($item) => (float) $item->quantity - (float) $item->received_quantity > 0,
                    );
                    $fullyReceived = $purchase->items->filter(
                        fn($item) => (float) $item->received_quantity >= (float) $item->quantity,
                    );
                    $itemIdx = 0;
                @endphp

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2 text-info"></i>Items to Receive
                        </h5>
                        <span class="bp-badge bp-badge-info">{{ $pendingItems->count() }} pending</span>
                    </div>
                    <div class="bp-card-body p-0">
                        <div class="bp-table-wrapper">
                            <table class="bp-table" id="receiveTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Ordered</th>
                                        <th class="text-center">Received</th>
                                        <th class="text-center">Remaining</th>
                                        <th>Receive Qty</th>
                                        <th>Rejected</th>
                                        <th>Reject Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingItems as $item)
                                        @php
                                            $remaining = (float) $item->quantity - (float) $item->received_quantity;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-700">{{ $item->product->name ?? 'Unknown Product' }}</div>
                                                @if ($item->variant)
                                                    @php $recVariant = $item->variant->attributeValues->map(fn ($v) => trim(($v->attribute?->base_name ? $v->attribute->base_name . ': ' : '') . $v->value))->filter()->implode(', '); @endphp
                                                    @if ($recVariant)
                                                        <div class="fs-11 text-muted">{{ $recVariant }}</div>
                                                    @endif
                                                @endif
                                                @if ($item->product?->model)
                                                    <div class="fs-11 text-muted">{{ __('Model') }}: {{ $item->product->model }}</div>
                                                @endif
                                            </td>
                                            <td class="text-center fw-600">{{ intval($item->quantity) }}</td>
                                            <td class="text-center">
                                                @if ((float) $item->received_quantity > 0)
                                                    <span
                                                        class="fw-600 text-warning">{{ intval($item->received_quantity) }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-center fw-700 text-primary">{{ intval($remaining) }}</td>
                                            <td>
                                                <input type="hidden" name="items[{{ $itemIdx }}][purchase_item_id]"
                                                    value="{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $itemIdx }}][product_id]"
                                                    value="{{ $item->product_id }}">
                                                <input type="hidden" name="items[{{ $itemIdx }}][variant_id]"
                                                    value="{{ $item->variant_id }}">
                                                <input type="number" class="bp-form-control bp-grn-receive-qty"
                                                    name="items[{{ $itemIdx }}][quantity_received]"
                                                    value="{{ intval($remaining) }}" min="0"
                                                    max="{{ $remaining }}" step="1">
                                            </td>
                                            <td>
                                                <input type="number" class="bp-form-control bp-grn-reject-qty"
                                                    name="items[{{ $itemIdx }}][quantity_rejected]" value="0"
                                                    min="0" max="{{ $remaining }}" step="1">
                                            </td>
                                            <td>
                                                <input type="text" class="bp-form-control"
                                                    name="items[{{ $itemIdx }}][reject_reason]"
                                                    placeholder="If rejected...">
                                            </td>
                                        </tr>
                                        @php $itemIdx++; @endphp
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-check-circle text-success fs-3 d-block mb-2"></i>
                                                All items have been fully received.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if ($pendingItems->isNotEmpty())
                        <div class="bp-card-footer d-flex justify-content-between align-items-center">
                            <div class="fs-13 text-muted">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                Set receive qty to 0 for items not in this delivery.
                            </div>
                            <div>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-success me-2" id="receiveAllBtn"><i
                                        class="fa-solid fa-check-double me-1"></i> Receive All</button>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-danger" id="receiveNoneBtn"><i
                                        class="fa-solid fa-xmark me-1"></i> Clear All</button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Already Received Items -->
                @if ($fullyReceived->isNotEmpty())
                    <div class="bp-card mb-4">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-circle-check me-2 text-success"></i>Fully
                                Received</h5>
                            <span class="bp-badge bp-badge-success">{{ $fullyReceived->count() }} item(s)</span>
                        </div>
                        <div class="bp-card-body p-0">
                            <div class="bp-table-wrapper">
                                <table class="bp-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Ordered</th>
                                            <th class="text-center">Received</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($fullyReceived as $item)
                                            <tr class="opacity-75">
                                                <td>
                                                    <div class="fw-600">{{ $item->product->name ?? 'Unknown' }}</div>
                                                    @if ($item->variant)
                                                        @php $recVariant = $item->variant->attributeValues->map(fn ($v) => trim(($v->attribute?->base_name ? $v->attribute->base_name . ': ' : '') . $v->value))->filter()->implode(', '); @endphp
                                                        @if ($recVariant)
                                                            <div class="fs-11 text-muted">{{ $recVariant }}</div>
                                                        @endif
                                                    @endif
                                                    @if ($item->product?->model)
                                                        <div class="fs-11 text-muted">{{ __('Model') }}: {{ $item->product->model }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ intval($item->quantity) }}</td>
                                                <td class="text-center text-success fw-700">
                                                    {{ intval($item->received_quantity) }} <i
                                                        class="fa-solid fa-check-circle fs-11 ms-1"></i>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            <div class="col-xl-4">

                <!-- GRN Details -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-clipboard-check me-2 text-success"></i>GRN Details
                        </h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="bp-form-label">Received Date *</label>
                                <input type="date" class="bp-form-control" name="received_date"
                                    value="{{ old('received_date', now()->format('Y-m-d')) }}" required>
                                @error('received_date')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Notes</label>
                                <textarea class="bp-form-control" name="notes" rows="3" placeholder="Any notes about this delivery...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Receive Summary -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-chart-pie me-2 text-info"></i>Summary</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="bp-info-row bp-info-row-compact">
                            <div class="bp-info-label bp-info-label-md">Total Items in PO</div>
                            <div class="bp-info-value fw-700">{{ $purchase->items->count() }}</div>
                        </div>
                        <div class="bp-info-row bp-info-row-compact">
                            <div class="bp-info-label bp-info-label-md">Fully Received</div>
                            <div class="bp-info-value fw-700 text-success">{{ $fullyReceived->count() }}</div>
                        </div>
                        <div class="bp-info-row bp-info-row-compact">
                            <div class="bp-info-label bp-info-label-md">Pending</div>
                            <div class="bp-info-value fw-700 text-warning">{{ $pendingItems->count() }}</div>
                        </div>
                        <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                            <div class="bp-info-label bp-info-label-md">Receiving Now</div>
                            <div class="bp-info-value fw-800 text-primary" id="receivingNowCount">
                                {{ $pendingItems->count() }}</div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex flex-column gap-2 mt-3">
                    @if ($pendingItems->isNotEmpty())
                        <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
                            <i class="fa-solid fa-check me-1"></i> Confirm & Receive Stock
                        </button>
                    @endif
                    <a href="{{ route('purchases.show', $purchase) }}"
                        class="bp-btn bp-btn-danger w-100 justify-content-center"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</a>
                </div>

            </div>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Validate rejected qty <= received qty
            $(document).on('input', '.bp-grn-receive-qty', function() {
                var $row = $(this).closest('tr');
                var received = parseFloat($(this).val()) || 0;
                var $rejected = $row.find('.bp-grn-reject-qty');
                var rejected = parseFloat($rejected.val()) || 0;

                if (rejected > received) {
                    $rejected.val(0);
                }
                $rejected.attr('max', received);
                updateReceivingSummary();
            });

            $(document).on('input', '.bp-grn-reject-qty', function() {
                var $row = $(this).closest('tr');
                var received = parseFloat($row.find('.bp-grn-receive-qty').val()) || 0;
                var rejected = parseFloat($(this).val()) || 0;

                if (rejected > received) {
                    $(this).val(received);
                }
            });

            // Receive All / Clear All buttons
            $('#receiveAllBtn').on('click', function() {
                $('.bp-grn-receive-qty').each(function() {
                    $(this).val($(this).attr('max'));
                });
                updateReceivingSummary();
            });

            $('#receiveNoneBtn').on('click', function() {
                $('.bp-grn-receive-qty').val(0);
                $('.bp-grn-reject-qty').val(0);
                updateReceivingSummary();
            });

            function updateReceivingSummary() {
                var count = 0;
                $('.bp-grn-receive-qty').each(function() {
                    if (parseFloat($(this).val()) > 0) {
                        count++;
                    }
                });
                $('#receivingNowCount').text(count);
            }
        });
    </script>
@endpush
