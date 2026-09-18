@extends('core::layouts.master')

@section('title', 'Purchase Order — ' . $purchase->po_number)
@section('page-title', $purchase->po_number)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('purchases.index') }}">Purchases</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $purchase->po_number }}</span>
@endsection

@section('page-actions')
    @bpCan('payments.create')
        @if (
            $purchase->supplier_id &&
                (float) $purchase->due_amount > 0 &&
                in_array($purchase->status, ['approved', 'partial_received', 'received']))
            <a href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'supplier', 'party_id' => $purchase->supplier_id, 'amount' => (int) $purchase->due_amount]) }}"
                class="bp-btn bp-btn-info"><i class="fa-solid fa-bangladeshi-taka-sign me-1"></i> Record Payment</a>
        @endif
    @endbpCan
    <a href="{{ route('purchases.print', $purchase) }}" target="_blank" class="bp-btn bp-btn-success"><i
            class="fa-solid fa-print me-1"></i> Print Invoice</a>
    @bpCan('purchases.approve')
        @if ($purchase->canBeApproved())
            <form action="{{ route('purchases.approve', $purchase) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-check me-1"></i> Approve</button>
            </form>
        @endif
    @endbpCan
    <a href="{{ route('purchases.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@php
    $statusBadge = match ($purchase->status) {
        'draft' => 'bp-badge-dark',
        'pending' => 'bp-badge-warning',
        'approved' => 'bp-badge-primary',
        'partial_received' => 'bp-badge-info',
        'received' => 'bp-badge-success',
        'cancelled' => 'bp-badge-danger',
        default => 'bp-badge-secondary',
    };
    $statusLabel = str_replace('_', ' ', ucfirst($purchase->status));

    $payBadge = match ($purchase->payment_status) {
        'paid' => 'bp-badge-success',
        'partial' => 'bp-badge-warning',
        default => 'bp-badge-danger',
    };
    $payLabel = ucfirst($purchase->payment_status);
@endphp

@section('content')

    <!-- PO Hero Banner -->
    <div class="bp-invoice-hero mb-0">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="inv-number">{{ $purchase->po_number }}</div>
                <div class="inv-meta">
                    <span class="me-3"><i class="fa-solid fa-calendar me-1"></i>
                        {{ $purchase->po_date->format('d M Y') }}</span>
                    @if ($purchase->expected_delivery)
                        <span class="me-3"><i class="fa-solid fa-clock me-1"></i> Expected:
                            {{ $purchase->expected_delivery->format('d M Y') }}</span>
                    @endif
                    @if ($purchase->branch)
                        <span><i class="fa-solid fa-code-branch me-1"></i> {{ $purchase->branch->name }}</span>
                    @endif
                </div>
                <div class="d-flex gap-2 mt-3">
                    <span class="bp-badge {{ $statusBadge }} bp-badge-hero"><i class="fa-solid fa-truck me-1"></i>
                        {{ $statusLabel }}</span>
                    <span class="bp-badge {{ $payBadge }} bp-badge-hero"><i
                            class="fa-solid fa-bangladeshi-taka-sign me-1"></i> {{ $payLabel }}</span>
                </div>
            </div>
            <div class="col-md-5 mt-3 mt-md-0">
                <div class="bp-invoice-hero-stat">
                    <div class="stat-label">Grand Total</div>
                    <div class="stat-value">{{ currency_symbol() }} {{ number_format($purchase->grand_total, 0) }}</div>
                    <div class="stat-sub">
                        <i class="fa-solid fa-bangladeshi-taka-sign me-1"></i>
                        {{ currency_symbol() }} {{ number_format($purchase->paid_amount, 0) }} paid
                        @if ($purchase->due_amount > 0)
                            &middot; {{ currency_symbol() }} {{ number_format($purchase->due_amount, 0) }} due
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-4 mt-0">

        <!-- Left Column -->
        <div class="col-xl-8">

            <!-- Supplier -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="bp-card h-100">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-building me-2 text-primary"></i>Supplier</h5>
                        </div>
                        <div class="bp-card-body">
                            @if ($purchase->supplier)
                                <div class="fw-800 fs-14 mb-1">{{ $purchase->supplier->company_name }}</div>
                                @if ($purchase->supplier->contact_person)
                                    <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-user fa-sm me-1"></i>
                                        {{ $purchase->supplier->contact_person }}</div>
                                @endif
                                @if ($purchase->supplier->phone)
                                    <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-phone fa-sm me-1"></i>
                                        {{ $purchase->supplier->phone }}</div>
                                @endif
                                @if ($purchase->supplier->email)
                                    <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-envelope fa-sm me-1"></i>
                                        {{ $purchase->supplier->email }}</div>
                                @endif
                                @if ($purchase->supplier->address)
                                    <div class="fs-13 text-muted"><i class="fa-solid fa-location-dot fa-sm me-1"></i>
                                        {{ $purchase->supplier->address }}</div>
                                @endif
                            @else
                                <div class="text-muted">No supplier information</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items Table -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-list me-2 text-primary"></i>Order Items</h5>
                    <span class="bp-badge bp-badge-info">{{ $purchase->items->count() }} Item(s)</span>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center">Ordered</th>
                                    <th class="text-center">Received</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-center">VAT</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchase->items as $index => $item)
                                    @php
                                        $received = (float) $item->received_quantity;
                                        $ordered = (float) $item->quantity;
                                        $receivedClass =
                                            $received >= $ordered
                                                ? 'text-success'
                                                : ($received > 0
                                                    ? 'text-warning'
                                                    : 'text-danger');
                                        $receivedIcon =
                                            $received >= $ordered
                                                ? 'fa-check-circle text-success'
                                                : ($received > 0
                                                    ? 'fa-exclamation-circle text-warning'
                                                    : 'fa-clock text-danger');
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-700">{{ $item->product->name ?? 'Unknown Product' }}</div>
                                            @if ($item->variant)
                                                <div class="fs-11 text-muted">{{ $item->variant->variant_name }}</div>
                                            @endif
                                            <div class="fs-11 text-muted">
                                                <code>{{ $item->product->sku ?? '' }}{{ $item->variant ? ' / ' . $item->variant->sku : '' }}</code>
                                            </div>
                                        </td>
                                        <td class="text-center fw-700">{{ intval($ordered) }}</td>
                                        <td class="text-center">
                                            <span class="fw-700 {{ $receivedClass }}">{{ intval($received) }}</span>
                                            <i class="fa-solid {{ $receivedIcon }} fs-11 ms-1"></i>
                                        </td>
                                        <td class="text-end">{{ currency_symbol() }}
                                            {{ number_format($item->unit_price, 0) }}</td>
                                        <td class="text-end text-muted">{{ currency_symbol() }}
                                            {{ number_format($item->discount_amount, 0) }}</td>
                                        <td class="text-center"><span
                                                class="bp-badge bp-badge-warning">{{ number_format($item->tax_rate, 0) }}%</span>
                                        </td>
                                        <td class="text-end fw-800">{{ currency_symbol() }}
                                            {{ number_format($item->line_total, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bp-card-footer">
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="bp-cart-summary-row">
                                <span>Subtotal</span>
                                <span class="fw-700">{{ currency_symbol() }}
                                    {{ number_format($purchase->subtotal, 0) }}</span>
                            </div>
                            @if ($purchase->discount_amount > 0)
                                <div class="bp-cart-summary-row">
                                    <span>Discount</span>
                                    <span>- {{ currency_symbol() }}
                                        {{ number_format($purchase->discount_amount, 0) }}</span>
                                </div>
                            @endif
                            <div class="bp-cart-summary-row">
                                <span>VAT / Tax</span>
                                <span>{{ currency_symbol() }} {{ number_format($purchase->tax_amount, 0) }}</span>
                            </div>
                            @if ($purchase->shipping_cost > 0)
                                <div class="bp-cart-summary-row">
                                    <span>Shipping</span>
                                    <span>{{ currency_symbol() }} {{ number_format($purchase->shipping_cost, 0) }}</span>
                                </div>
                            @endif
                            <div class="bp-cart-summary-row total">
                                <span>Grand Total</span>
                                <span>{{ currency_symbol() }} {{ number_format($purchase->grand_total, 0) }}</span>
                            </div>
                            <div class="bp-cart-summary-row bp-cart-summary-paid">
                                <span>Total Paid</span>
                                <span>{{ currency_symbol() }} {{ number_format($purchase->paid_amount, 0) }}</span>
                            </div>
                            @if ($purchase->due_amount > 0)
                                <div class="bp-cart-summary-row">
                                    <span>Balance Due</span>
                                    <span class="text-danger fw-800">{{ currency_symbol() }}
                                        {{ number_format($purchase->due_amount, 0) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receiving History (GRNs) -->
            @if ($purchase->grns->isNotEmpty())
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2 text-success"></i>Receiving
                            History</h5>
                    </div>
                    <div class="bp-card-body p-0">
                        <div class="bp-table-wrapper">
                            <table class="bp-table">
                                <thead>
                                    <tr>
                                        <th>GRN #</th>
                                        <th>Date</th>
                                        <th>Items Received</th>
                                        <th>Received By</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchase->grns as $grn)
                                        <tr>
                                            <td class="fw-700">{{ $grn->grn_number }}</td>
                                            <td>{{ $grn->received_date->format('d M Y') }}</td>
                                            <td>
                                                @foreach ($grn->items as $grnItem)
                                                    <div class="fs-12">{{ $grnItem->product->name ?? 'Product' }}
                                                        @if ($grnItem->variant)
                                                            <span
                                                                class="text-muted">({{ $grnItem->variant->variant_name }})</span>
                                                        @endif &times;
                                                        {{ intval($grnItem->quantity_accepted) }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>{{ $grn->receivedBy->name ?? 'N/A' }}</td>
                                            <td class="fs-12 text-muted">{{ Str::limit($grn->notes, 60) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Notes -->
            @if ($purchase->notes || $purchase->internal_notes)
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2 text-muted"></i>Notes</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            @if ($purchase->notes)
                                <div class="{{ $purchase->internal_notes ? 'col-md-6' : 'col-12' }}">
                                    <label class="bp-form-label">Supplier Instructions</label>
                                    <div class="bp-form-control bp-form-control-static">{{ $purchase->notes }}</div>
                                </div>
                            @endif
                            @if ($purchase->internal_notes)
                                <div class="{{ $purchase->notes ? 'col-md-6' : 'col-12' }}">
                                    <label class="bp-form-label">Internal Note</label>
                                    <div class="bp-form-control bp-form-control-static">{{ $purchase->internal_notes }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Column -->
        <div class="col-xl-4">

            <!-- PO Metadata -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2 text-primary"></i>PO Details</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">PO Number</div>
                        <div class="bp-info-value fw-700">{{ $purchase->po_number }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">PO Date</div>
                        <div class="bp-info-value">{{ $purchase->po_date->format('d M Y') }}</div>
                    </div>
                    @if ($purchase->expected_delivery)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Expected Delivery</div>
                            <div class="bp-info-value">{{ $purchase->expected_delivery->format('d M Y') }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Payment Terms</div>
                        <div class="bp-info-value">{{ str_replace('_', ' ', ucfirst($purchase->payment_terms ?? 'N/A')) }}
                        </div>
                    </div>
                    @if ($purchase->supplier_invoice_ref)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Supplier Ref</div>
                            <div class="bp-info-value fw-600">{{ $purchase->supplier_invoice_ref }}</div>
                        </div>
                    @endif
                    @if ($purchase->branch)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Branch</div>
                            <div class="bp-info-value">{{ $purchase->branch->name }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Created By</div>
                        <div class="bp-info-value fw-600">{{ $purchase->createdBy->name ?? 'N/A' }}</div>
                    </div>
                    @if ($purchase->approvedBy)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Approved By</div>
                            <div class="bp-info-value fw-600">{{ $purchase->approvedBy->name }}</div>
                        </div>
                    @endif
                    @if ($purchase->approved_at)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Approved At</div>
                            <div class="bp-info-value">
                                {{ \Carbon\Carbon::parse($purchase->approved_at)->format('d M Y, h:i A') }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Status</div>
                        <div class="bp-info-value"><span class="bp-badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                        </div>
                    </div>
                    <div class="bp-info-row bp-info-row-last">
                        <div class="bp-info-label bp-info-label-lg">Payment Status</div>
                        <div class="bp-info-value"><span class="bp-badge {{ $payBadge }}">{{ $payLabel }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2 text-success"></i>Payment
                        History</h5>
                </div>
                <div class="bp-card-body">
                    @if ($purchase->payments->isNotEmpty())
                        <div class="bp-timeline">
                            @foreach ($purchase->payments->sortByDesc('payment_date') as $payment)
                                <div class="bp-timeline-item">
                                    <div class="bp-timeline-dot success"></div>
                                    <div class="bp-timeline-title">{{ currency_symbol() }}
                                        {{ number_format($payment->amount, 0) }} &mdash;
                                        {{ str_replace('_', ' ', ucfirst($payment->payment_method)) }}</div>
                                    @if ($payment->reference)
                                        <div class="bp-timeline-desc">Ref: {{ $payment->reference }}</div>
                                    @endif
                                    <div class="bp-timeline-time">
                                        {{ $payment->payment_date->format('d M Y') }}{{ $payment->creator ? ' · by ' . $payment->creator->name : '' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fa-solid fa-receipt fs-3 d-block mb-2 opacity-50"></i>
                            <p class="fs-13 mb-0">No payments recorded</p>
                        </div>
                    @endif
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between fs-13 mb-1">
                            <span class="text-muted">Grand Total</span>
                            <span class="fw-700">{{ currency_symbol() }}
                                {{ number_format($purchase->grand_total, 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between fs-13 mb-1">
                            <span class="text-muted">Total Paid</span>
                            <span class="fw-700 text-success">{{ currency_symbol() }}
                                {{ number_format($purchase->paid_amount, 0) }}</span>
                        </div>
                        @if ($purchase->due_amount > 0)
                            <div class="d-flex justify-content-between fs-14 fw-800 mt-2 pt-2 border-top">
                                <span>Balance Due</span>
                                <span class="text-danger">{{ currency_symbol() }}
                                    {{ number_format($purchase->due_amount, 0) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            @php
                $canEdit = in_array($purchase->status, ['draft', 'pending']);
                $canDelete = auth()->user()?->can('purchases.delete') ?? false;
                $hasActions = $purchase->canBeReceived() || $canEdit || $purchase->canBeCancelled() || $canDelete;
            @endphp
            @if ($hasActions)
                <div class="d-flex flex-column gap-2 mb-4">
                    @if ($purchase->canBeReceived())
                        <a href="{{ route('purchases.receive.create', $purchase) }}"
                            class="bp-btn bp-btn-success w-100 justify-content-center"><i
                                class="fa-solid fa-truck-ramp-box me-2"></i> Receive Stock</a>
                    @endif
                    @if ($canEdit)
                        @bpCan('purchases.edit')
                            <a href="{{ route('purchases.edit', $purchase) }}"
                                class="bp-btn bp-btn-info w-100 justify-content-center"><i class="fa-solid fa-pen me-2"></i>
                                Edit Purchase Order</a>
                        @endbpCan
                    @endif
                    @if ($purchase->canBeCancelled())
                        @bpCan('purchases.edit')
                            <form action="{{ route('purchases.cancel', $purchase) }}" method="POST">
                                @csrf
                                <button type="submit" class="bp-btn bp-btn-warning w-100 justify-content-center"
                                    onclick="return confirm('Are you sure you want to cancel this PO?')"><i
                                        class="fa-solid fa-ban me-2"></i> Cancel PO</button>
                            </form>
                        @endbpCan
                    @endif
                    @bpCan('purchases.delete')
                        @php
                            $deleteWarning = in_array($purchase->status, ['partial_received', 'received'])
                                ? 'This PO has received stock. Deleting it will reverse the received stock (only what is still on hand), void its accounting entries and payments, and remove it permanently. Continue?'
                                : 'Delete this purchase order permanently?';
                        @endphp
                        <form action="{{ route('purchases.destroy', $purchase) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center"
                                onclick="return confirm('{{ $deleteWarning }}')"><i class="fa-solid fa-trash me-2"></i>
                                Delete PO</button>
                        </form>
                    @endbpCan
                </div>
            @endif

        </div>
    </div>

@endsection
