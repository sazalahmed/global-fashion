@extends('core::layouts.master')

@section('title', 'Sale Detail — ' . $sale->invoice_number)
@section('page-title', $sale->invoice_number)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('sales.index') }}">Sales</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $sale->invoice_number }}</span>
@endsection

@section('page-actions')
    @if ($sale->customer_id && (float) $sale->due_amount > 0)
        <a href="{{ route('payments.create', ['direction' => 'receive', 'party_type' => 'customer', 'party_id' => $sale->customer_id, 'amount' => (int) $sale->due_amount]) }}"
            class="bp-btn bp-btn-success"><i class="fa-solid fa-bangladeshi-taka-sign me-1"></i> Record Payment</a>
    @endif
    @bpCan('sales.edit')
        @if (!in_array($sale->status, ['cancelled', 'returned']))
            <a href="{{ route('sales.edit', $sale) }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-pen me-1"></i> Edit</a>
        @endif
    @endbpCan
    <a href="{{ route('sales.print', $sale) }}" target="_blank" class="bp-btn bp-btn-info"><i
            class="fa-solid fa-print me-1"></i> Print</a>
    <a href="{{ route('sales.pdf', $sale) }}" target="_blank" class="bp-btn bp-btn-warning"><i
            class="fa-solid fa-download me-1"></i> PDF</a>
    <a href="{{ route('sales.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

    <!-- Invoice Hero Banner -->
    <div class="bp-invoice-hero mb-0">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="inv-number">{{ $sale->invoice_number }}</div>
                <div class="inv-meta">
                    <span class="me-3"><i class="fa-solid fa-calendar me-1"></i>
                        {{ $sale->sale_date->format('d M Y') }}</span>
                </div>
                <div class="d-flex gap-2 mt-3">
                    @switch($sale->payment_status)
                        @case('paid')
                            <span class="bp-badge bp-badge-success bp-badge-hero"><i class="fa-solid fa-check-circle me-1"></i>
                                Paid</span>
                        @break

                        @case('partial')
                            <span class="bp-badge bp-badge-warning bp-badge-hero"><i class="fa-solid fa-clock me-1"></i>
                                Partial</span>
                        @break

                        @default
                            <span class="bp-badge bp-badge-danger bp-badge-hero"><i class="fa-solid fa-times-circle me-1"></i>
                                Unpaid</span>
                    @endswitch

                    @switch($sale->source)
                        @case('pos')
                            <span class="bp-badge bp-badge-primary bp-badge-hero"><i class="fa-solid fa-cash-register me-1"></i>
                                POS</span>
                        @break

                        @case('store')
                            <span class="bp-badge bp-badge-info bp-badge-hero"><i class="fa-solid fa-file-invoice me-1"></i>
                                Invoice</span>
                        @break

                        @case('ecommerce')
                            <span class="bp-badge bp-badge-secondary bp-badge-hero"><i class="fa-solid fa-globe me-1"></i>
                                {{ $sale->source_label }}</span>
                        @break
                    @endswitch
                </div>
            </div>
            <div class="col-md-5 mt-3 mt-md-0">
                <div class="bp-invoice-hero-stat">
                    <div class="stat-label">Grand Total</div>
                    <div class="stat-value">{{ money($sale->grand_total) }}</div>
                    <div class="stat-sub">
                        <i class="fa-solid fa-money-bill-wave me-1"></i>
                        Paid: {{ money($sale->paid_amount) }}
                        @if ($sale->due_amount > 0)
                            &middot; Due: {{ money($sale->due_amount) }}
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

            <!-- Bill To -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-user me-2 text-primary"></i>Bill To</h5>
                </div>
                <div class="bp-card-body">
                    @if ($sale->customer)
                        <div class="fw-800 fs-14 mb-1">{{ $sale->customer->name }}</div>
                        @if ($sale->customer->phone)
                            <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-phone fa-sm me-1"></i>
                                {{ $sale->customer->phone }}</div>
                        @endif
                        @if ($sale->customer->email)
                            <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-envelope fa-sm me-1"></i>
                                {{ $sale->customer->email }}</div>
                        @endif
                        @if ($sale->customer->address)
                            <div class="fs-13 text-muted"><i class="fa-solid fa-location-dot fa-sm me-1"></i>
                                {{ $sale->customer->address }}</div>
                        @endif
                    @else
                        <div class="fw-800 fs-14 mb-1">{{ $sale->customer_display_name }}</div>
                        @if ($sale->customer_phone_snapshot)
                            <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-phone fa-sm me-1"></i>
                                {{ $sale->customer_phone_snapshot }}</div>
                        @endif
                        @if ($sale->customer_address)
                            <div class="fs-13 text-muted"><i class="fa-solid fa-location-dot fa-sm me-1"></i>
                                {{ $sale->customer_address }}</div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Line Items Table -->
            <x-core::table class="mb-4">
                <x-slot:filters>
                    <h5 class="bp-card-title"><i class="fa-solid fa-list me-2 text-primary"></i>Order Items</h5>
                    <span class="bp-badge bp-badge-info">{{ $sale->items->count() }}
                        {{ Str::plural('Item', $sale->items->count()) }}</span>
                </x-slot:filters>

                <x-core::table.header>
                    <x-core::table.column>#</x-core::table.column>
                    <x-core::table.column>Product</x-core::table.column>
                    <x-core::table.column align="center">Qty</x-core::table.column>
                    <x-core::table.column align="center">Unit Price</x-core::table.column>
                    <x-core::table.column align="center">Discount</x-core::table.column>
                    <x-core::table.column align="center">Total</x-core::table.column>
                </x-core::table.header>

                <tbody>
                    @foreach ($sale->items as $index => $item)
                        @php
                            // product_name may already carry " — Variant"; show the base name
                            // on top and the variant on its own muted line (no duplication).
                            $itemName = $item->product_name;
                            if (
                                $item->variant_label &&
                                \Illuminate\Support\Str::endsWith($itemName, ' — ' . $item->variant_label)
                            ) {
                                $itemName = \Illuminate\Support\Str::beforeLast(
                                    $itemName,
                                    ' — ' . $item->variant_label,
                                );
                            }
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div>{{ $itemName }}</div>
                                @if ($item->variant_label)
                                    <div class="fs-12 text-muted">{{ $item->variant_label }}</div>
                                @endif
                            </td>
                            <td class="text-center fw-700">{{ $item->quantity }}</td>
                            <td class="text-center fw-700">{{ money($item->unit_price) }}</td>
                            <td class="text-center fw-700">{{ money($item->discount_amount) }}</td>
                            <td class="text-center fw-700">{{ money($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <!-- Totals Summary -->
                <x-slot:pagination>
                    <div class="bp-card-footer">
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="bp-cart-summary-row">
                                    <span>Subtotal</span>
                                    <span class="fw-700">{{ money($sale->subtotal) }}</span>
                                </div>
                                <div class="bp-cart-summary-row">
                                    <span>Discount</span>
                                    <span>{{ money($sale->discount_amount) }}</span>
                                </div>
                                <div class="bp-cart-summary-row">
                                    <span>Shipping</span>
                                    <span>{{ money($sale->shipping_charge) }}</span>
                                </div>
                                <div class="bp-cart-summary-row total">
                                    <span>Grand Total</span>
                                    <span>{{ money($sale->grand_total) }}</span>
                                </div>
                                <div class="bp-cart-summary-row bp-cart-summary-paid">
                                    <span>Total Paid</span>
                                    <span>{{ money($sale->paid_amount) }}</span>
                                </div>
                                <div class="bp-cart-summary-row">
                                    <span>Balance Due</span>
                                    <span
                                        class="{{ $sale->due_amount > 0 ? 'text-danger fw-700' : 'text-muted' }}">{{ money($sale->due_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot:pagination>
            </x-core::table>

            <!-- Notes -->
            @if ($sale->notes)
                <div class="bp-card mb-4 mt-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2 text-muted"></i>Notes</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="fs-13">{{ $sale->notes }}</div>
                    </div>
                </div>
            @endif

            <!-- Courier Tracking Timeline -->
            @if ($sale->courier_name || $sale->trackingEvents->isNotEmpty() || true)
                <div class="bp-card mb-4">
                    <div class="bp-card-header d-flex justify-content-between align-items-center">
                        <h5 class="bp-card-title">
                            <i class="fa-solid fa-truck-fast me-2 text-primary"></i>Courier Tracking
                            @if ($sale->courier_name)
                                <span class="text-muted fs-13 ms-2">— {{ $sale->courier_name }}</span>
                            @endif
                        </h5>
                        <div class="d-flex gap-2">
                            @if (strtolower((string) $sale->courier_name) === 'steadfast')
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="refreshCourierBtn"
                                    data-action="{{ route('sales.refresh-courier-status', $sale) }}">
                                    <i class="fa-solid fa-rotate me-1"></i> Refresh status
                                </button>
                            @endif
                            @if ($sale->courier_tracking_url)
                                <a href="{{ $sale->courier_tracking_url }}" target="_blank" rel="noopener"
                                    class="bp-btn bp-btn-sm bp-btn-outline">
                                    <i class="fa-solid fa-up-right-from-square me-1"></i> Courier portal
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="bp-card-body">

                        {{-- ── Link / Fetch Consignment ID form ─────────────────── --}}
                        @bpCan('sales.edit')
                        <form id="linkConsignmentForm" class="mb-3"
                              data-action="{{ route('sales.link-consignment', $sale) }}">
                            @csrf
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                {{-- Courier Name --}}
                                <div style="min-width:160px; max-width:200px;">
                                    <select id="courierNameSelect" class="bp-form-control">
                                        @foreach ($couriers as $courier)
                                            @if($courier->name == 'Steadfast')
                                                <option value="{{ $courier->name }}"
                                                    {{ $sale->courier_name === $courier->name ? 'selected' : '' }}>
                                                    {{ $courier->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                        {{-- Fallback if no couriers in DB --}}
                                        @if ($couriers->isEmpty())
                                            <option value="Steadfast" selected>Steadfast Courier</option>
                                        @endif
                                    </select>
                                </div>
                                {{-- Consignment ID --}}
                                <div style="flex:1; min-width:160px; max-width:260px;">
                                    <input type="text" id="consignmentIdInput"
                                        class="bp-form-control"
                                        placeholder="Consignment ID"
                                        value="{{ $sale->courier_consignment_id }}"
                                        maxlength="100">
                                </div>
                                <button type="submit" id="fetchConsignmentBtn" class="bp-btn bp-btn-primary bp-btn-sm">
                                    <i class="fa-solid fa-cloud-arrow-down me-1"></i> Assign Consignment
                                </button>
                            </div>
                            <div id="linkConsignmentMsg" class="mt-2 fs-12" style="display:none;"></div>
                        </form>
                        @endbpCan

                        @if ($sale->courier_consignment_id || $sale->courier_status)
                            <div class="mb-3 fs-12">
                                @if ($sale->courier_consignment_id)
                                    <span class="bp-badge bp-badge-secondary me-2">CID:
                                        {{ $sale->courier_consignment_id }}</span>
                                @endif
                                @if ($sale->courier_status)
                                    <span class="bp-badge bp-badge-info">{{ $sale->courier_status }}</span>
                                @endif
                                @if ($sale->courier_status_updated_at)
                                    <span class="text-muted ms-2">Updated
                                        {{ $sale->courier_status_updated_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        @endif

                        @if ($sale->trackingEvents->isNotEmpty())
                            <div class="bp-timeline">
                                @foreach ($sale->trackingEvents as $event)
                                    @php
                                        $isDelivery = $event->event_type === 'delivery_status';
                                        $statusLower = strtolower((string) $event->status);
                                        $dotClass = match (true) {
                                            $statusLower === 'delivered' => 'success',
                                            $statusLower === 'cancelled' => 'danger',
                                            $statusLower === 'partial_delivered' => 'warning',
                                            $isDelivery => 'primary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <div class="bp-timeline-item">
                                        <div class="bp-timeline-dot {{ $dotClass }}"></div>
                                        <div class="bp-timeline-title">
                                            @if ($event->status)
                                                <span
                                                    class="text-capitalize">{{ str_replace('_', ' ', $event->status) }}</span>
                                            @else
                                                Tracking update
                                            @endif
                                            <span
                                                class="bp-badge bp-badge-secondary ms-2 fs-11">{{ ucfirst($event->courier_provider) }}</span>
                                        </div>
                                        @if ($event->message)
                                            <div class="bp-timeline-desc">{{ $event->message }}</div>
                                        @endif
                                        <div class="bp-timeline-time">
                                            {{ $event->occurred_at->format('d M Y, h:i A') }} ·
                                            {{ $event->occurred_at->diffForHumans() }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="fa-solid fa-route fs-3 d-block mb-2 opacity-50"></i>
                                <p class="fs-13 mb-0">No tracking events received yet. Updates will appear here as the
                                    courier reports them.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Column -->
        <div class="col-xl-4">

            <!-- Invoice Details -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2 text-primary"></i>Invoice Details
                    </h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Invoice #</div>
                        <div class="bp-info-value fw-700">{{ $sale->invoice_number }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Date</div>
                        <div class="bp-info-value">{{ $sale->sale_date->format('d M Y') }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Source</div>
                        <div class="bp-info-value">
                            @switch($sale->source)
                                @case('pos')
                                    <span class="bp-badge bp-badge-primary">POS</span>
                                @break

                                @case('store')
                                    <span class="bp-badge bp-badge-info">Invoice</span>
                                @break

                                @case('ecommerce')
                                    <span class="bp-badge bp-badge-secondary">{{ $sale->source_label }}</span>
                                @break

                                @default
                                    <span class="bp-badge bp-badge-dark">{{ $sale->source_label }}</span>
                            @endswitch
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Status</div>
                        <div class="bp-info-value">
                            @switch($sale->status)
                                @case('draft')
                                    <span class="bp-badge bp-badge-dark">Draft</span>
                                @break

                                @case('confirmed')
                                    <span class="bp-badge bp-badge-primary">Confirmed</span>
                                @break

                                @case('delivered')
                                    <span class="bp-badge bp-badge-success">Delivered</span>
                                @break

                                @case('cancelled')
                                    <span class="bp-badge bp-badge-danger">Cancelled</span>
                                @break

                                @default
                                    <span class="bp-badge bp-badge-dark">{{ ucfirst($sale->status ?? 'Unknown') }}</span>
                            @endswitch
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Payment</div>
                        <div class="bp-info-value">
                            @switch($sale->payment_status)
                                @case('paid')
                                    <span class="bp-badge bp-badge-success">Paid</span>
                                @break

                                @case('partial')
                                    <span class="bp-badge bp-badge-warning">Partial</span>
                                @break

                                @default
                                    <span class="bp-badge bp-badge-danger">Unpaid</span>
                            @endswitch
                        </div>
                    </div>
                    <div class="bp-info-row bp-info-row-last">
                        <div class="bp-info-label bp-info-label-lg">Created By</div>
                        <div class="bp-info-value fw-600">{{ $sale->creator->name ?? '—' }}</div>
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
                    @if ($sale->allocations->isNotEmpty())
                        <div class="bp-timeline">
                            @foreach ($sale->allocations->sortByDesc(fn($a) => $a->payment->payment_date) as $allocation)
                                @php $payment = $allocation->payment; @endphp
                                <div class="bp-timeline-item">
                                    <div class="bp-timeline-dot success"></div>
                                    <div class="bp-timeline-title">{{ currency_symbol() }}
                                        {{ number_format($payment->amount, 0) }} &mdash;
                                        {{ str_replace('_', ' ', ucfirst($payment->payment_method)) }}{{ $payment->paymentAccount ? ' (' . $payment->paymentAccount->name . ')' : '' }}
                                    </div>
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
                </div>
            </div>

            <!-- Danger Zone -->
            @bpCan('sales.delete')
                <div class="d-flex flex-column gap-2 mb-4">
                    <form action="{{ route('sales.destroy', $sale) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center delete-confirm"
                            data-name="{{ $sale->invoice_number }}"><i class="fa-solid fa-ban me-2"></i> Cancel Sale</button>
                    </form>
                </div>
            @endbpCan

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {

            /* ── Refresh existing courier status ── */
            $('#refreshCourierBtn').on('click', function() {
                var $btn = $(this);
                var oldHtml = $btn.html();
                $btn.prop('disabled', true).html(
                    '<i class="fa-solid fa-spinner fa-spin me-1"></i>Refreshing...');
                $.ajax({
                    url: $btn.data('action'),
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') }
                }).done(function() {
                    location.reload();
                }).fail(function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Refresh failed.';
                    alert(msg);
                    $btn.prop('disabled', false).html(oldHtml);
                });
            });

            /* ── Link / Fetch consignment ID from Steadfast ── */
            $('#linkConsignmentForm').on('submit', function(e) {
                e.preventDefault();

                var cid         = $.trim($('#consignmentIdInput').val());
                var courierName = $('#courierNameSelect').val();
                if (!cid) {
                    showLinkMsg('Please enter a Consignment ID.', 'danger');
                    return;
                }

                var $btn    = $('#fetchConsignmentBtn');
                var oldHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Fetching...');
                hideLinkMsg();

                $.ajax({
                    url    : $(this).data('action'),
                    method : 'POST',
                    data   : {
                        _token         : $('meta[name="csrf-token"]').attr('content'),
                        consignment_id : cid,
                        courier_name   : courierName
                    }
                }).done(function(res) {
                    showLinkMsg(
                        '<i class="fa-solid fa-circle-check me-1"></i>' + (res.message || 'Linked successfully.'),
                        'success'
                    );
                    // Reload after a short delay so the user sees the success message.
                    setTimeout(function() { location.reload(); }, 1200);
                }).fail(function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to fetch consignment data.';
                    showLinkMsg('<i class="fa-solid fa-circle-xmark me-1"></i>' + msg, 'danger');
                    $btn.prop('disabled', false).html(oldHtml);
                });
            });

            function showLinkMsg(html, type) {
                $('#linkConsignmentMsg')
                    .removeClass('text-success text-danger')
                    .addClass(type === 'success' ? 'text-success' : 'text-danger')
                    .html(html)
                    .show();
            }

            function hideLinkMsg() {
                $('#linkConsignmentMsg').hide().html('');
            }
        });
    </script>
@endpush
