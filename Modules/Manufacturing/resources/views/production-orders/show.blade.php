@extends('core::layouts.master')

@section('title', 'Production Order — ' . $order->po_number)
@section('page-title', $order->po_number)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Manufacturing</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.index') }}">Production Orders</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $order->po_number }}</span>
@endsection

@section('page-actions')
@if($order->canBeApproved())
  @bpCan('manufacturing.edit')
  <form action="{{ route('manufacturing.production-orders.approve', $order) }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-check me-1"></i> Approve</button>
  </form>
  @endbpCan
@endif
@if($order->canReceiveLot())
  @bpCan('manufacturing.edit')
  <a href="{{ route('manufacturing.production-orders.issue-fabric.create', $order) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-scissors me-1"></i> Issue Fabric</a>
  <a href="{{ route('manufacturing.production-orders.return-fabric.create', $order) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-rotate-left me-1"></i> Return Fabric</a>
  <a href="{{ route('manufacturing.production-orders.lots.create', $order) }}" class="bp-btn bp-btn-success"><i class="fa-solid fa-truck-ramp-box me-1"></i> Receive Lot</a>
  @endbpCan
@endif
@if($order->canBeCompleted())
  @bpCan('manufacturing.edit')
  <form action="{{ route('manufacturing.production-orders.complete', $order) }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="bp-btn bp-btn-success" onclick="return confirm('Mark this order as completed?')"><i class="fa-solid fa-flag-checkered me-1"></i> Complete</button>
  </form>
  @endbpCan
@endif
@if($order->canBeCancelled())
  @bpCan('manufacturing.edit')
  <form action="{{ route('manufacturing.production-orders.cancel', $order) }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="bp-btn bp-btn-danger" onclick="return confirm('Cancel this order?')"><i class="fa-solid fa-ban me-1"></i> Cancel</button>
  </form>
  @endbpCan
@endif
@if($order->status === 'draft')
  @bpCan('manufacturing.edit')
  <a href="{{ route('manufacturing.production-orders.edit', $order) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-pen me-1"></i> Edit</a>
  @endbpCan
@endif
<a href="{{ route('manufacturing.production-orders.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@php
  $statusLabel = str_replace('_', ' ', ucfirst($order->status));
  $payLabel = ucfirst($order->payment_status);
  $statusBadge = match($order->status) {
    'draft' => 'bp-badge-dark',
    'approved' => 'bp-badge-primary',
    'in_progress' => 'bp-badge-info',
    'partial_delivered' => 'bp-badge-warning',
    'completed' => 'bp-badge-success',
    'cancelled' => 'bp-badge-danger',
    default => 'bp-badge-secondary',
  };
  $payBadge = match($order->payment_status) {
    'unpaid' => 'bp-badge-danger',
    'partial' => 'bp-badge-warning',
    'paid' => 'bp-badge-success',
    default => 'bp-badge-secondary',
  };
@endphp

@section('content')

<!-- Hero Banner -->
<div class="bp-invoice-hero mb-0">
  <div class="row align-items-center">
    <div class="col-md-7">
      <div class="inv-number">{{ $order->po_number }}</div>
      <div class="inv-meta">
        <span class="me-3"><i class="fa-solid fa-calendar me-1"></i> {{ $order->order_date->format('d M Y') }}</span>
        @if($order->expected_delivery_date)
          <span class="me-3"><i class="fa-solid fa-clock me-1"></i> Expected: {{ $order->expected_delivery_date->format('d M Y') }}</span>
        @endif
        <span><i class="fa-solid fa-industry me-1"></i> {{ $order->factory->name ?? '--' }}</span>
      </div>
      <div class="d-flex gap-2 mt-3">
        <span class="bp-badge {{ $statusBadge }} bp-badge-hero"><i class="fa-solid fa-clipboard-list me-1"></i> {{ $statusLabel }}</span>
        <span class="bp-badge {{ $payBadge }} bp-badge-hero"><i class="fa-solid fa-bangladeshi-taka-sign me-1"></i> {{ $payLabel }}</span>
      </div>
    </div>
    <div class="col-md-5 mt-3 mt-md-0">
      <div class="bp-invoice-hero-stat">
        <div class="stat-label">Grand Total</div>
        <div class="stat-value">{{ currency_symbol() }} {{ number_format($order->grand_total, 0) }}</div>
        <div class="stat-sub">
          <i class="fa-solid fa-bangladeshi-taka-sign me-1"></i>
          {{ currency_symbol() }} {{ number_format($order->paid_amount, 0) }} paid
          @if($order->due_amount > 0)
            &middot; {{ currency_symbol() }} {{ number_format($order->due_amount, 0) }} due
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="row g-4 mt-0">

  <!-- Left Column -->
  <div class="col-xl-8">

    <!-- Quantity Summary -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-chart-bar me-2 text-primary"></i>Quantity Summary</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-sm-3 text-center">
            <div class="fs-11 text-muted text-uppercase fw-700">Total Ordered</div>
            <div class="fw-800 fs-5">{{ number_format($order->total_quantity) }}</div>
          </div>
          <div class="col-sm-3 text-center">
            <div class="fs-11 text-muted text-uppercase fw-700">Received</div>
            <div class="fw-800 fs-5 text-primary">{{ number_format($order->received_quantity) }}</div>
          </div>
          <div class="col-sm-3 text-center">
            <div class="fs-11 text-muted text-uppercase fw-700">Good</div>
            <div class="fw-800 fs-5 text-success">{{ number_format($order->good_quantity) }}</div>
          </div>
          <div class="col-sm-3 text-center">
            <div class="fs-11 text-muted text-uppercase fw-700">Damaged</div>
            <div class="fw-800 fs-5 text-danger">{{ number_format($order->damaged_quantity) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Items -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-list me-2 text-primary"></i>Order Items</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Catalog</th>
                <th>Color</th>
                <th>Size</th>
                <th>Qty</th>
                <th>Received</th>
                <th>Damaged</th>
                <th>Remaining</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->items as $i => $item)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td class="fw-600">{{ $item->catalog->name ?? '--' }}</td>
                <td>{{ $item->color->name ?? '--' }}</td>
                <td>{{ $item->size->name ?? '--' }}</td>
                <td class="fw-700">{{ $item->quantity }}</td>
                <td class="text-primary">{{ $item->received_quantity }}</td>
                <td class="text-danger">{{ $item->damaged_quantity }}</td>
                <td class="{{ $item->remaining_quantity > 0 ? 'text-warning fw-700' : 'text-success' }}">{{ $item->remaining_quantity }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- BOM Status -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-scissors me-2 text-primary"></i>BOM Status</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Raw Material</th>
                <th>Planned</th>
                <th>Issued</th>
                <th>Returned</th>
                <th>Net Consumed</th>
                <th>Remaining to Issue</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->materials as $mat)
              @php
                $netConsumed = (float)$mat->actual_issued_quantity - (float)$mat->actual_returned_quantity;
              @endphp
              <tr>
                <td class="fw-600">{{ $mat->rawMaterial->name ?? '--' }}</td>
                <td>{{ num($mat->planned_quantity) }}</td>
                <td class="text-primary">{{ num($mat->actual_issued_quantity) }}</td>
                <td class="text-success">{{ num($mat->actual_returned_quantity) }}</td>
                <td class="fw-700">{{ num($netConsumed) }}</td>
                <td class="{{ $mat->remaining_to_issue > 0 ? 'text-warning fw-700' : 'text-success' }}">{{ num($mat->remaining_to_issue) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Lots History -->
    @if($order->lots->count() > 0)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-truck-ramp-box me-2 text-primary"></i>Production Lots</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Lot #</th>
                <th>Delivery Date</th>
                <th>Received</th>
                <th>Damaged</th>
                <th>Good</th>
                <th>Making Cost</th>
                <th>Cost/Unit</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->lots as $lot)
              <tr>
                <td class="fw-700">{{ $lot->lot_number }}</td>
                <td>{{ $lot->delivery_date?->format('d M Y') }}</td>
                <td>{{ $lot->total_received }}</td>
                <td class="text-danger">{{ $lot->total_damaged }}</td>
                <td class="text-success fw-600">{{ $lot->total_good }}</td>
                <td>{{ currency_symbol() }} {{ number_format($lot->making_cost) }}</td>
                <td class="fw-700">{{ money($lot->provisional_cost_per_unit) }}</td>
                <td>
                  <div class="dropdown">
                    <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li><a class="dropdown-item" href="{{ route('manufacturing.production-orders.lots.show', [$order, $lot]) }}"><i class="fa-solid fa-eye me-2"></i> View Lot</a></li>
                    </ul>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif

    <!-- Fabric Issuances -->
    @if($order->fabricIssuances->count() > 0)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-arrow-right-from-bracket me-2 text-primary"></i>Fabric Issuances</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Issuance #</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total Cost</th>
                <th>Issued By</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->fabricIssuances as $issuance)
              <tr>
                <td class="fw-700">{{ $issuance->issuance_number }}</td>
                <td>{{ $issuance->issuance_date?->format('d M Y') }}</td>
                <td>{{ $issuance->items->count() }} materials</td>
                <td class="fw-700">{{ currency_symbol() }} {{ number_format($issuance->total_cost) }}</td>
                <td>{{ $issuance->issuer->name ?? '--' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif

    <!-- Fabric Returns -->
    @if($order->fabricReturns->count() > 0)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-rotate-left me-2 text-primary"></i>Fabric Returns</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Return #</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total Cost</th>
                <th>Received By</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->fabricReturns as $return)
              <tr>
                <td class="fw-700">{{ $return->return_number }}</td>
                <td>{{ $return->return_date?->format('d M Y') }}</td>
                <td>{{ $return->items->count() }} materials</td>
                <td class="fw-700 text-success">{{ currency_symbol() }} {{ number_format($return->total_cost) }}</td>
                <td>{{ $return->receiver->name ?? '--' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif

    <!-- Damages -->
    @if($order->damages->count() > 0)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>Damages</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Catalog</th>
                <th>Qty</th>
                <th>Cost</th>
                <th>Type</th>
                <th>Responsibility</th>
                <th>Compensation</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->damages as $damage)
              <tr>
                <td>{{ $damage->damage_date?->format('d M Y') }}</td>
                <td>{{ $damage->catalog->name ?? '--' }}</td>
                <td>{{ $damage->quantity }}</td>
                <td class="text-danger fw-700">{{ currency_symbol() }} {{ number_format($damage->total_damage_cost) }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($damage->damage_type)) }}</td>
                <td>{{ ucfirst($damage->responsibility) }}</td>
                <td>
                  @php
                    $compBadge = match($damage->compensation_status) {
                      'pending' => 'bp-badge-warning',
                      'partial' => 'bp-badge-info',
                      'received' => 'bp-badge-success',
                      'written_off' => 'bp-badge-dark',
                      'deducted' => 'bp-badge-primary',
                      default => 'bp-badge-secondary',
                    };
                  @endphp
                  <span class="bp-badge {{ $compBadge }}">{{ ucfirst($damage->compensation_status) }}</span>
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

  <!-- Right Column - Financial Summary -->
  <div class="col-xl-4">

    <!-- Cost Breakdown -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2 text-primary"></i>Cost Breakdown</h5>
      </div>
      <div class="bp-card-body">
        <table class="w-100">
          <tr><td class="py-1 text-muted">Fabric Cost</td><td class="py-1 text-end fw-600">{{ currency_symbol() }} {{ number_format($order->total_fabric_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Fabric Returned</td><td class="py-1 text-end fw-600 text-success">- {{ currency_symbol() }} {{ number_format($order->total_fabric_returned_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Making Cost</td><td class="py-1 text-end fw-600">{{ currency_symbol() }} {{ number_format($order->total_making_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Delivery Cost</td><td class="py-1 text-end fw-600">{{ currency_symbol() }} {{ number_format($order->total_delivery_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Other Costs</td><td class="py-1 text-end fw-600">{{ currency_symbol() }} {{ number_format($order->total_other_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Damage Cost</td><td class="py-1 text-end fw-600 text-danger">{{ currency_symbol() }} {{ number_format($order->total_damage_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Compensation</td><td class="py-1 text-end fw-600 text-success">- {{ currency_symbol() }} {{ number_format($order->total_compensation) }}</td></tr>
          <tr><td colspan="2"><hr class="my-2"></td></tr>
          <tr><td class="py-1 fw-700">Grand Total</td><td class="py-1 text-end fw-800 fs-5">{{ currency_symbol() }} {{ number_format($order->grand_total) }}</td></tr>
          <tr><td class="py-1 text-muted">Paid</td><td class="py-1 text-end fw-600 text-success">{{ currency_symbol() }} {{ number_format($order->paid_amount) }}</td></tr>
          <tr><td class="py-1 text-muted">Due</td><td class="py-1 text-end fw-700 text-danger">{{ currency_symbol() }} {{ number_format($order->due_amount) }}</td></tr>
        </table>
      </div>
    </div>

    <!-- Per-Unit Cost -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2 text-primary"></i>Per-Unit Cost</h5>
      </div>
      <div class="bp-card-body">
        <table class="w-100">
          <tr><td class="py-1 text-muted">Estimated</td><td class="py-1 text-end fw-600">{{ money($order->estimated_making_cost_per_unit) }}</td></tr>
          <tr><td class="py-1 text-muted">Actual (Running)</td><td class="py-1 text-end fw-800">{{ money($order->actual_making_cost_per_unit) }}</td></tr>
          @if($order->final_cost_per_unit > 0)
            <tr><td class="py-1 text-muted">Final</td><td class="py-1 text-end fw-800 text-success">{{ money($order->final_cost_per_unit) }}</td></tr>
          @endif
        </table>
      </div>
    </div>

    <!-- Waste Summary -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-recycle me-2 text-warning"></i>Waste Summary</h5>
      </div>
      <div class="bp-card-body">
        <table class="w-100">
          <tr><td class="py-1 text-muted">RM Waste Cost</td><td class="py-1 text-end fw-600">{{ currency_symbol() }} {{ number_format($order->total_rm_waste_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">RM Abnormal Waste</td><td class="py-1 text-end fw-600 text-danger">{{ currency_symbol() }} {{ number_format($order->total_rm_waste_abnormal_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Product Waste Cost</td><td class="py-1 text-end fw-600">{{ currency_symbol() }} {{ number_format($order->total_product_waste_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Product Abnormal Waste</td><td class="py-1 text-end fw-600 text-danger">{{ currency_symbol() }} {{ number_format($order->total_product_waste_abnormal_cost) }}</td></tr>
        </table>
      </div>
    </div>

    <!-- Order Info -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-info-circle me-2 text-primary"></i>Order Info</h5>
      </div>
      <div class="bp-card-body">
        <table class="w-100">
          <tr><td class="py-1 text-muted">Created By</td><td class="py-1 text-end fw-600">{{ $order->creator->name ?? '--' }}</td></tr>
          @if($order->approved_at)
            <tr><td class="py-1 text-muted">Approved By</td><td class="py-1 text-end fw-600">{{ $order->approver->name ?? '--' }}</td></tr>
            <tr><td class="py-1 text-muted">Approved At</td><td class="py-1 text-end">{{ $order->approved_at->format('d M Y H:i') }}</td></tr>
          @endif
          @if($order->completed_at)
            <tr><td class="py-1 text-muted">Completed At</td><td class="py-1 text-end">{{ $order->completed_at->format('d M Y H:i') }}</td></tr>
          @endif
          @if($order->notes)
            <tr><td colspan="2" class="pt-2"><div class="text-muted fs-12">{{ $order->notes }}</div></td></tr>
          @endif
        </table>
      </div>
    </div>

  </div>
</div>

@endsection
