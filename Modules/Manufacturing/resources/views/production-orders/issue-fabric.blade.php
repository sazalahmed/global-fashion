@extends('core::layouts.master')

@section('title', 'Issue Fabric — ' . $productionOrder->po_number)
@section('page-title', __("Issue Fabric"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Manufacturing</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.index') }}">Production Orders</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}">{{ $productionOrder->po_number }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Issue Fabric</span>
@endsection

@section('page-actions')
<a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back
</a>
@endsection

@section('content')

<form action="{{ route('manufacturing.production-orders.issue-fabric.store', $productionOrder) }}" method="POST">
  @csrf

  <!-- Issuance Details -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-scissors me-2"></i>Fabric Issuance for {{ $productionOrder->po_number }}</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="bp-form-label">Factory</label>
          <input type="text" class="bp-form-control" value="{{ $productionOrder->factory->name ?? '--' }}" readonly>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Issuance Date *</label>
          <input type="date" class="bp-form-control" name="issuance_date" value="{{ date('Y-m-d') }}" required>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Notes</label>
          <input type="text" class="bp-form-control" name="notes" placeholder="Optional notes">
        </div>
      </div>
    </div>
  </div>

  <!-- BOM Items to Issue -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Materials to Issue</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Raw Material</th>
              <th>BOM Planned</th>
              <th>Already Issued</th>
              <th>Remaining</th>
              <th>Qty to Issue *</th>
              <th>Unit Cost *</th>
              <th>Line Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach($productionOrder->materials as $i => $mat)
            <tr>
              <td class="fw-600">{{ $mat->rawMaterial->name ?? '--' }}</td>
              <td>{{ num($mat->planned_quantity) }}</td>
              <td>{{ num($mat->actual_issued_quantity) }}</td>
              <td class="{{ $mat->remaining_to_issue > 0 ? 'text-warning fw-700' : 'text-success' }}">{{ num($mat->remaining_to_issue) }}</td>
              <td>
                <input type="hidden" name="items[{{ $i }}][raw_material_id]" value="{{ $mat->raw_material_id }}">
                <input type="hidden" name="items[{{ $i }}][bom_item_id]" value="{{ $mat->id }}">
                <input type="number" class="bp-form-control issue-qty" name="items[{{ $i }}][quantity_issued]" step="0.0001" min="0" value="{{ max(0, $mat->remaining_to_issue) }}"></td>
              <td><input type="number" class="bp-form-control issue-cost" name="items[{{ $i }}][unit_cost]" step="0.01" min="0" value="{{ num_input($mat->unit_cost) }}"></td>
              <td class="fw-700 issue-line-total">{{ currency_symbol() }} 0</td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="6" class="text-end fw-700">Total Issuance Cost:</td>
              <td class="fw-800" id="totalIssuanceCost">{{ currency_symbol() }} 0</td>
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
      <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-paper-plane me-1"></i> Issue Fabric</button>
      @endbpCan
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
'use strict';

(function() {
  function recalcIssuance() {
    var total = 0;
    $('tbody tr').each(function() {
      var qty = parseFloat($(this).find('.issue-qty').val()) || 0;
      var cost = parseFloat($(this).find('.issue-cost').val()) || 0;
      var line = qty * cost;
      $(this).find('.issue-line-total').text('{{ currency_symbol() }} ' + Math.round(line).toLocaleString());
      total += line;
    });
    $('#totalIssuanceCost').text('{{ currency_symbol() }} ' + Math.round(total).toLocaleString());
  }

  $(document).on('input', '.issue-qty, .issue-cost', recalcIssuance);
  recalcIssuance();
})();
</script>
@endpush
