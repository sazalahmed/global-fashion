@extends('core::layouts.master')

@section('title', 'Receive Lot — ' . $productionOrder->po_number)
@section('page-title', __("Receive Production Lot"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Manufacturing</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.index') }}">Production Orders</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}">{{ $productionOrder->po_number }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Receive Lot</span>
@endsection

@section('page-actions')
<a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back
</a>
@endsection

@section('content')

<form action="{{ route('manufacturing.production-orders.lots.store', $productionOrder) }}" method="POST" id="receiveLotForm">
  @csrf

  <!-- Lot Details -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-truck-ramp-box me-2"></i>Lot Details for {{ $productionOrder->po_number }}</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="bp-form-label">Delivery Date *</label>
          <input type="date" class="bp-form-control" name="delivery_date" value="{{ date('Y-m-d') }}" required>
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Making Cost</label>
          <input type="number" class="bp-form-control" name="making_cost" step="0.01" min="0" value="0">
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Delivery Charge</label>
          <input type="number" class="bp-form-control" name="delivery_charge" step="0.01" min="0" value="0">
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Other Costs</label>
          <input type="number" class="bp-form-control" name="other_costs" step="0.01" min="0" value="0">
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Other Costs Note</label>
          <input type="text" class="bp-form-control" name="other_costs_note" placeholder="Description">
        </div>
        <div class="col-12">
          <label class="bp-form-label">Notes</label>
          <textarea class="bp-form-control" name="notes" rows="2"></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Items to Receive -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Items to Receive</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Catalog</th>
              <th>Color</th>
              <th>Size</th>
              <th>Order Qty</th>
              <th>Already Received</th>
              <th>Remaining</th>
              <th>Received *</th>
              <th>Damaged</th>
              <th>Good</th>
            </tr>
          </thead>
          <tbody>
            @foreach($productionOrder->items as $i => $item)
            <tr>
              <td class="fw-600">{{ $item->catalog->name ?? '--' }}</td>
              <td>{{ $item->color->name ?? '--' }}</td>
              <td>{{ $item->size->name ?? '--' }}</td>
              <td class="fw-700">{{ $item->quantity }}</td>
              <td>{{ $item->received_quantity }}</td>
              <td class="{{ $item->remaining_quantity > 0 ? 'text-warning fw-700' : 'text-success' }}">{{ $item->remaining_quantity }}</td>
              <td>
                <input type="hidden" name="items[{{ $i }}][production_order_item_id]" value="{{ $item->id }}">
                <input type="number" class="bp-form-control lot-received" name="items[{{ $i }}][quantity_received]" min="0" value="0" max="{{ $item->remaining_quantity }}" required>
              </td>
              <td>
                <input type="number" class="bp-form-control lot-damaged" name="items[{{ $i }}][quantity_damaged]" min="0" value="0">
              </td>
              <td class="fw-700 text-success lot-good">0</td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="6" class="text-end fw-700">Totals:</td>
              <td class="fw-800" id="totalReceived">0</td>
              <td class="fw-800 text-danger" id="totalDamaged">0</td>
              <td class="fw-800 text-success" id="totalGood">0</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Submit -->
  <div class="bp-card">
    <div class="bp-card-body d-flex justify-content-end gap-2">
      <a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
      @bpCan('manufacturing.edit')
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-truck-ramp-box me-1"></i> Receive Lot</button>
      @endbpCan
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
'use strict';

(function() {
  function recalcLot() {
    var totalR = 0, totalD = 0, totalG = 0;
    $('tbody tr').each(function() {
      var received = parseInt($(this).find('.lot-received').val()) || 0;
      var damaged = parseInt($(this).find('.lot-damaged').val()) || 0;
      var good = Math.max(0, received - damaged);
      $(this).find('.lot-good').text(good);
      totalR += received;
      totalD += damaged;
      totalG += good;
    });
    $('#totalReceived').text(totalR);
    $('#totalDamaged').text(totalD);
    $('#totalGood').text(totalG);
  }

  $(document).on('input', '.lot-received, .lot-damaged', recalcLot);
  recalcLot();
})();
</script>
@endpush
