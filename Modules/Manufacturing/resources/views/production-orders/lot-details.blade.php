@extends('core::layouts.master')

@section('title', 'Lot Details — ' . $lot->lot_number)
@section('page-title', $lot->lot_number)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Manufacturing</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.index') }}">Production Orders</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}">{{ $productionOrder->po_number }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $lot->lot_number }}</span>
@endsection

@section('page-actions')
<a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Order
</a>
@endsection

@section('content')

<!-- Lot Info -->
<div class="row g-4">
  <div class="col-xl-8">

    <!-- Lot Summary -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-truck-ramp-box me-2 text-primary"></i>Lot Summary</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-sm-3 text-center">
            <div class="fs-11 text-muted text-uppercase fw-700">Total Received</div>
            <div class="fw-800 fs-5">{{ $lot->total_received }}</div>
          </div>
          <div class="col-sm-3 text-center">
            <div class="fs-11 text-muted text-uppercase fw-700">Damaged</div>
            <div class="fw-800 fs-5 text-danger">{{ $lot->total_damaged }}</div>
          </div>
          <div class="col-sm-3 text-center">
            <div class="fs-11 text-muted text-uppercase fw-700">Good</div>
            <div class="fw-800 fs-5 text-success">{{ $lot->total_good }}</div>
          </div>
          <div class="col-sm-3 text-center">
            <div class="fs-11 text-muted text-uppercase fw-700">Cost/Unit</div>
            <div class="fw-800 fs-5">{{ money($lot->provisional_cost_per_unit) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lot Items -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Lot Items</h5>
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
                <th>Received</th>
                <th>Damaged</th>
                <th>Good</th>
                <th>Unit Cost</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lot->items as $i => $item)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td class="fw-600">{{ $item->catalog->name ?? '--' }}</td>
                <td>{{ $item->color->name ?? '--' }}</td>
                <td>{{ $item->size->name ?? '--' }}</td>
                <td>{{ $item->quantity_received }}</td>
                <td class="text-danger">{{ $item->quantity_damaged }}</td>
                <td class="text-success fw-700">{{ $item->quantity_good }}</td>
                <td>{{ money($item->unit_cost) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Damages for this Lot -->
    @if($lot->damages->count() > 0)
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
                <th>Qty</th>
                <th>Cost</th>
                <th>Type</th>
                <th>Responsibility</th>
                <th>Compensation</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lot->damages as $damage)
              <tr>
                <td>{{ $damage->damage_date?->format('d M Y') }}</td>
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

    <!-- RM Wastes for this Lot -->
    @if($lot->rmWastes->count() > 0)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-recycle me-2 text-warning"></i>RM Wastes</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Material</th>
                <th>Qty</th>
                <th>Cost</th>
                <th>Type</th>
                <th>Normal?</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lot->rmWastes as $waste)
              <tr>
                <td class="fw-600">{{ $waste->rawMaterial->name ?? '--' }}</td>
                <td>{{ num($waste->quantity_wasted) }}</td>
                <td class="fw-700">{{ currency_symbol() }} {{ number_format($waste->total_cost) }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($waste->waste_type)) }}</td>
                <td>
                  <span class="bp-badge {{ $waste->is_normal ? 'bp-badge-success' : 'bp-badge-danger' }}">{{ $waste->is_normal ? 'Normal' : 'Abnormal' }}</span>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif

    <!-- Product Wastes for this Lot -->
    @if($lot->productWastes->count() > 0)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-recycle me-2 text-warning"></i>Product Wastes</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Qty</th>
                <th>Cost</th>
                <th>Type</th>
                <th>Normal?</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lot->productWastes as $waste)
              <tr>
                <td>{{ $waste->quantity_wasted }}</td>
                <td class="fw-700">{{ currency_symbol() }} {{ number_format($waste->total_cost) }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($waste->waste_type)) }}</td>
                <td>
                  <span class="bp-badge {{ $waste->is_normal ? 'bp-badge-success' : 'bp-badge-danger' }}">{{ $waste->is_normal ? 'Normal' : 'Abnormal' }}</span>
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

  <!-- Right Column -->
  <div class="col-xl-4">
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-info-circle me-2 text-primary"></i>Lot Info</h5>
      </div>
      <div class="bp-card-body">
        <table class="w-100">
          <tr><td class="py-1 text-muted">Lot Number</td><td class="py-1 text-end fw-700">{{ $lot->lot_number }}</td></tr>
          <tr><td class="py-1 text-muted">Order</td><td class="py-1 text-end"><a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}" class="fw-700">{{ $productionOrder->po_number }}</a></td></tr>
          <tr><td class="py-1 text-muted">Factory</td><td class="py-1 text-end fw-600">{{ $lot->productionOrder->factory->name ?? '--' }}</td></tr>
          <tr><td class="py-1 text-muted">Delivery Date</td><td class="py-1 text-end">{{ $lot->delivery_date?->format('d M Y') }}</td></tr>
          <tr><td class="py-1 text-muted">Received By</td><td class="py-1 text-end">{{ $lot->receiver->name ?? '--' }}</td></tr>
          <tr><td colspan="2"><hr class="my-2"></td></tr>
          <tr><td class="py-1 text-muted">Making Cost</td><td class="py-1 text-end fw-600">{{ currency_symbol() }} {{ number_format($lot->making_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Delivery Charge</td><td class="py-1 text-end fw-600">{{ currency_symbol() }} {{ number_format($lot->delivery_charge) }}</td></tr>
          <tr><td class="py-1 text-muted">Other Costs</td><td class="py-1 text-end fw-600">{{ currency_symbol() }} {{ number_format($lot->other_costs) }}</td></tr>
          @if($lot->other_costs_note)
            <tr><td colspan="2" class="text-muted fs-12">{{ $lot->other_costs_note }}</td></tr>
          @endif
          <tr><td colspan="2"><hr class="my-2"></td></tr>
          <tr><td class="py-1 text-muted">RM Waste Cost</td><td class="py-1 text-end">{{ currency_symbol() }} {{ number_format($lot->rm_waste_cost) }}</td></tr>
          <tr><td class="py-1 text-muted">Product Waste Cost</td><td class="py-1 text-end">{{ currency_symbol() }} {{ number_format($lot->product_waste_cost) }}</td></tr>
          <tr><td colspan="2"><hr class="my-2"></td></tr>
          <tr><td class="py-1 fw-700">Prov. Cost/Unit</td><td class="py-1 text-end fw-800">{{ money($lot->provisional_cost_per_unit) }}</td></tr>
        </table>
      </div>
    </div>

    @if($lot->notes)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-sticky-note me-2 text-warning"></i>Notes</h5>
      </div>
      <div class="bp-card-body">
        <p class="mb-0 text-muted">{{ $lot->notes }}</p>
      </div>
    </div>
    @endif
  </div>
</div>

@endsection
