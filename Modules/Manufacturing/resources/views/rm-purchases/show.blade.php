@extends('core::layouts.master')

@section('title', 'RM Purchase Order — ' . $rmPurchase->po_number)
@section('page-title', $rmPurchase->po_number)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Manufacturing</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.rm-purchases.index') }}">RM Purchase Orders</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $rmPurchase->po_number }}</span>
@endsection

@section('page-actions')
@if($rmPurchase->canBeApproved())
  @bpCan('manufacturing.edit')
  <form action="{{ route('manufacturing.rm-purchases.approve', $rmPurchase) }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-check me-1"></i> Approve</button>
  </form>
  @endbpCan
@endif
@if($rmPurchase->canBeReceived())
  @bpCan('manufacturing.edit')
  <a href="{{ route('manufacturing.rm-purchases.receive.create', $rmPurchase) }}" class="bp-btn bp-btn-success"><i class="fa-solid fa-truck-ramp-box me-1"></i> Receive Goods</a>
  @endbpCan
@endif
@if(in_array($rmPurchase->status, ['draft', 'pending']))
  @bpCan('manufacturing.edit')
  <a href="{{ route('manufacturing.rm-purchases.edit', $rmPurchase) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-pen me-1"></i> Edit</a>
  @endbpCan
@endif
<a href="{{ route('manufacturing.rm-purchases.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@php
  $statusLabel = str_replace('_', ' ', ucfirst($rmPurchase->status));
  $payLabel = ucfirst($rmPurchase->payment_status);
@endphp

@section('content')

<!-- PO Hero Banner -->
<div class="bp-invoice-hero mb-0">
  <div class="row align-items-center">
    <div class="col-md-7">
      <div class="inv-number">{{ $rmPurchase->po_number }}</div>
      <div class="inv-meta">
        <span class="me-3"><i class="fa-solid fa-calendar me-1"></i> {{ $rmPurchase->po_date->format('d M Y') }}</span>
        @if($rmPurchase->expected_delivery_date)
          <span class="me-3"><i class="fa-solid fa-clock me-1"></i> Expected: {{ $rmPurchase->expected_delivery_date->format('d M Y') }}</span>
        @endif
      </div>
      <div class="d-flex gap-2 mt-3">
        <span class="bp-badge {{ $rmPurchase->status_badge_class }} bp-badge-hero"><i class="fa-solid fa-truck me-1"></i> {{ $statusLabel }}</span>
        <span class="bp-badge {{ $rmPurchase->payment_status_badge_class }} bp-badge-hero"><i class="fa-solid fa-bangladeshi-taka-sign me-1"></i> {{ $payLabel }}</span>
      </div>
    </div>
    <div class="col-md-5 mt-3 mt-md-0">
      <div class="bp-invoice-hero-stat">
        <div class="stat-label">Grand Total</div>
        <div class="stat-value">{{ currency_symbol() }} {{ number_format($rmPurchase->grand_total, 0) }}</div>
        <div class="stat-sub">
          <i class="fa-solid fa-bangladeshi-taka-sign me-1"></i>
          {{ currency_symbol() }} {{ number_format($rmPurchase->paid_amount, 0) }} paid
          @if($rmPurchase->due_amount > 0)
            &middot; {{ currency_symbol() }} {{ number_format($rmPurchase->due_amount, 0) }} due
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

    <!-- Supplier Info -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-building me-2 text-primary"></i>Supplier</h5>
      </div>
      <div class="bp-card-body">
        @if($rmPurchase->supplier)
          <div class="fw-800 fs-14 mb-1">{{ $rmPurchase->supplier->company_name }}</div>
          @if($rmPurchase->supplier->contact_person)
            <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-user fa-sm me-1"></i> {{ $rmPurchase->supplier->contact_person }}</div>
          @endif
          @if($rmPurchase->supplier->phone)
            <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-phone fa-sm me-1"></i> {{ $rmPurchase->supplier->phone }}</div>
          @endif
          @if($rmPurchase->supplier->email)
            <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-envelope fa-sm me-1"></i> {{ $rmPurchase->supplier->email }}</div>
          @endif
          @if($rmPurchase->supplier->address)
            <div class="fs-13 text-muted"><i class="fa-solid fa-location-dot fa-sm me-1"></i> {{ $rmPurchase->supplier->address }}</div>
          @endif
        @else
          <div class="text-muted">No supplier information</div>
        @endif
      </div>
    </div>

    <!-- Order Items Table -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-list me-2 text-primary"></i>Order Items</h5>
        <span class="bp-badge bp-badge-info">{{ $rmPurchase->items->count() }} Item(s)</span>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Raw Material</th>
                <th class="text-center">Ordered</th>
                <th class="text-center">Received</th>
                <th class="text-center">Remaining</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Discount</th>
                <th class="text-center">Tax</th>
                <th class="text-end">Total</th>
                <th>Attachment</th>
              </tr>
            </thead>
            <tbody>
              @foreach($rmPurchase->items as $index => $item)
                @php
                  $received = (float) $item->received_quantity;
                  $ordered = (float) $item->quantity;
                  $remaining = $ordered - $received;
                  $receivedClass = $received >= $ordered ? 'text-success' : ($received > 0 ? 'text-warning' : 'text-danger');
                  $receivedIcon = $received >= $ordered ? 'fa-check-circle text-success' : ($received > 0 ? 'fa-exclamation-circle text-warning' : 'fa-clock text-danger');
                @endphp
                <tr>
                  <td class="text-muted">{{ $index + 1 }}</td>
                  <td>
                    <div class="fw-700">{{ $item->rawMaterial->name ?? 'Unknown' }}</div>
                    <div class="fs-11 text-muted"><code>{{ $item->rawMaterial->code ?? '' }}</code> &middot; {{ ucfirst($item->rawMaterial->unit ?? '') }}</div>
                  </td>
                  <td class="text-center fw-700">{{ num($ordered) }}</td>
                  <td class="text-center">
                    <span class="fw-700 {{ $receivedClass }}">{{ num($received) }}</span>
                    <i class="fa-solid {{ $receivedIcon }} fs-11 ms-1"></i>
                  </td>
                  <td class="text-center fw-600 text-primary">{{ num($remaining) }}</td>
                  <td class="text-end">{{ currency_symbol() }} {{ number_format($item->unit_price, 0) }}</td>
                  <td class="text-end text-muted">{{ currency_symbol() }} {{ number_format($item->discount_amount, 0) }}</td>
                  <td class="text-center">
                    @if((float) $item->tax_rate > 0)
                      <span class="bp-badge bp-badge-warning">{{ number_format($item->tax_rate, 0) }}%</span>
                    @else
                      <span class="text-muted">--</span>
                    @endif
                  </td>
                  <td class="text-end fw-800">{{ currency_symbol() }} {{ number_format($item->line_total, 0) }}</td>
                  <td>
                    @if($item->attachment_path)
                      <a href="{{ upload_url($item->attachment_path) }}" target="_blank" class="bp-btn bp-btn-sm bp-btn-outline" title="{{ $item->attachment_name }}">
                        <i class="fa-solid fa-paperclip me-1"></i>{{ \Illuminate\Support\Str::limit($item->attachment_name, 15) }}
                      </a>
                    @else
                      <span class="text-muted fs-11">—</span>
                    @endif
                  </td>
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
              <span class="fw-700">{{ currency_symbol() }} {{ number_format($rmPurchase->subtotal, 0) }}</span>
            </div>
            @if($rmPurchase->discount_amount > 0)
              <div class="bp-cart-summary-row">
                <span>Discount</span>
                <span>- {{ currency_symbol() }} {{ number_format($rmPurchase->discount_amount, 0) }}</span>
              </div>
            @endif
            @if($rmPurchase->tax_amount > 0)
              <div class="bp-cart-summary-row">
                <span>Tax</span>
                <span>{{ currency_symbol() }} {{ number_format($rmPurchase->tax_amount, 0) }}</span>
              </div>
            @endif
            @if($rmPurchase->shipping_cost > 0)
              <div class="bp-cart-summary-row">
                <span>Shipping</span>
                <span>{{ currency_symbol() }} {{ number_format($rmPurchase->shipping_cost, 0) }}</span>
              </div>
            @endif
            <div class="bp-cart-summary-row total">
              <span>Grand Total</span>
              <span>{{ currency_symbol() }} {{ number_format($rmPurchase->grand_total, 0) }}</span>
            </div>
            <div class="bp-cart-summary-row bp-cart-summary-paid">
              <span>Total Paid</span>
              <span>{{ currency_symbol() }} {{ number_format($rmPurchase->paid_amount, 0) }}</span>
            </div>
            @if($rmPurchase->due_amount > 0)
              <div class="bp-cart-summary-row">
                <span>Balance Due</span>
                <span class="text-danger fw-800">{{ currency_symbol() }} {{ number_format($rmPurchase->due_amount, 0) }}</span>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Receiving History (GRNs) -->
    @if($rmPurchase->receives->isNotEmpty())
      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2 text-success"></i>Receiving History</h5>
          @if($rmPurchase->canBeReceived())
            @bpCan('manufacturing.edit')
            <a href="{{ route('manufacturing.rm-purchases.receive.create', $rmPurchase) }}" class="bp-btn bp-btn-sm bp-btn-primary"><i class="fa-solid fa-plus me-1"></i> Receive Goods</a>
            @endbpCan
          @endif
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
                @foreach($rmPurchase->receives as $receive)
                  <tr>
                    <td class="fw-700">{{ $receive->receive_number }}</td>
                    <td>{{ $receive->receive_date->format('d M Y') }}</td>
                    <td>
                      @foreach($receive->items as $receiveItem)
                        <div class="fs-12">
                          {{ $receiveItem->rawMaterial->name ?? 'Material' }}
                          &times; {{ num($receiveItem->quantity_received) }}
                          @if((float) $receiveItem->quantity_damaged > 0)
                            <span class="text-danger">({{ num($receiveItem->quantity_damaged) }} damaged)</span>
                          @endif
                        </div>
                      @endforeach
                    </td>
                    <td>{{ $receive->receiver->name ?? 'N/A' }}</td>
                    <td class="fs-12 text-muted">{{ Str::limit($receive->notes, 60) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif

    <!-- Notes -->
    @if($rmPurchase->notes)
      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2 text-muted"></i>Notes</h5>
        </div>
        <div class="bp-card-body">
          <div class="bp-form-control bp-form-control-static">{{ $rmPurchase->notes }}</div>
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
          <div class="bp-info-value fw-700">{{ $rmPurchase->po_number }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">PO Date</div>
          <div class="bp-info-value">{{ $rmPurchase->po_date->format('d M Y') }}</div>
        </div>
        @if($rmPurchase->expected_delivery_date)
          <div class="bp-info-row">
            <div class="bp-info-label bp-info-label-lg">Expected Delivery</div>
            <div class="bp-info-value">{{ $rmPurchase->expected_delivery_date->format('d M Y') }}</div>
          </div>
        @endif
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Created By</div>
          <div class="bp-info-value fw-600">{{ $rmPurchase->creator->name ?? 'N/A' }}</div>
        </div>
        @if($rmPurchase->approver)
          <div class="bp-info-row">
            <div class="bp-info-label bp-info-label-lg">Approved By</div>
            <div class="bp-info-value fw-600">{{ $rmPurchase->approver->name }}</div>
          </div>
        @endif
        @if($rmPurchase->approved_at)
          <div class="bp-info-row">
            <div class="bp-info-label bp-info-label-lg">Approved At</div>
            <div class="bp-info-value">{{ $rmPurchase->approved_at->format('d M Y, h:i A') }}</div>
          </div>
        @endif
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Status</div>
          <div class="bp-info-value"><span class="bp-badge {{ $rmPurchase->status_badge_class }}">{{ $statusLabel }}</span></div>
        </div>
        <div class="bp-info-row bp-info-row-last">
          <div class="bp-info-label bp-info-label-lg">Payment Status</div>
          <div class="bp-info-value"><span class="bp-badge {{ $rmPurchase->payment_status_badge_class }}">{{ $payLabel }}</span></div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="d-flex flex-column gap-2 mb-4">
      @if(in_array($rmPurchase->status, ['draft', 'pending']))
        @bpCan('manufacturing.edit')
        <a href="{{ route('manufacturing.rm-purchases.edit', $rmPurchase) }}" class="bp-btn bp-btn-outline w-100 justify-content-center"><i class="fa-solid fa-pen me-2"></i> Edit Purchase Order</a>
        @endbpCan
      @endif
      @if($rmPurchase->canBeReceived())
        @bpCan('manufacturing.edit')
        <a href="{{ route('manufacturing.rm-purchases.receive.create', $rmPurchase) }}" class="bp-btn bp-btn-success w-100 justify-content-center"><i class="fa-solid fa-truck-ramp-box me-2"></i> Receive Goods</a>
        @endbpCan
      @endif
      @if($rmPurchase->canBeCancelled())
        @bpCan('manufacturing.edit')
        <form action="{{ route('manufacturing.rm-purchases.cancel', $rmPurchase) }}" method="POST">
          @csrf
          <button type="submit" class="bp-btn bp-btn-outline bp-btn-outline-danger w-100 justify-content-center" onclick="return confirm('Are you sure you want to cancel this PO?')"><i class="fa-solid fa-ban me-2"></i> Cancel PO</button>
        </form>
        @endbpCan
      @endif
      @if($rmPurchase->status === 'draft')
        @bpCan('manufacturing.delete')
        <form action="{{ route('manufacturing.rm-purchases.destroy', $rmPurchase) }}" method="POST">
          @csrf @method('DELETE')
          <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center" onclick="return confirm('Delete this purchase order permanently?')"><i class="fa-solid fa-trash me-2"></i> Delete PO</button>
        </form>
        @endbpCan
      @endif
    </div>

  </div>
</div>

@endsection
