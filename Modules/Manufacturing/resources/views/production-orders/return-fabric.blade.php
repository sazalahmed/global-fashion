@extends('core::layouts.master')

@section('title', 'Return Fabric — ' . $productionOrder->po_number)
@section('page-title', __("Return Fabric"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Manufacturing</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.index') }}">Production Orders</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}">{{ $productionOrder->po_number }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Return Fabric</span>
@endsection

@section('page-actions')
<a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back
</a>
@endsection

@section('content')

<form action="{{ route('manufacturing.production-orders.return-fabric.store', $productionOrder) }}" method="POST">
  @csrf

  <!-- Return Details -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-rotate-left me-2"></i>Fabric Return for {{ $productionOrder->po_number }}</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="bp-form-label">Factory</label>
          <input type="text" class="bp-form-control" value="{{ $productionOrder->factory->name ?? '--' }}" readonly>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Return Date *</label>
          <input type="date" class="bp-form-control" name="return_date" value="{{ date('Y-m-d') }}" required>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Notes</label>
          <input type="text" class="bp-form-control" name="notes" placeholder="Optional notes">
        </div>
      </div>
    </div>
  </div>

  <!-- Materials to Return -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-arrow-left me-2"></i>Materials to Return</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Raw Material</th>
              <th>Total Issued</th>
              <th>Already Returned</th>
              <th>Remaining</th>
              <th>Qty to Return *</th>
              <th>Condition *</th>
              <th>Unit Cost *</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            @foreach($productionOrder->materials as $i => $mat)
            @php
              $remainingReturn = (float)$mat->actual_issued_quantity - (float)$mat->actual_returned_quantity;
            @endphp
            <tr>
              <td class="fw-600">{{ $mat->rawMaterial->name ?? '--' }}</td>
              <td>{{ num($mat->actual_issued_quantity) }}</td>
              <td>{{ num($mat->actual_returned_quantity) }}</td>
              <td class="{{ $remainingReturn > 0 ? 'text-warning fw-700' : 'text-success' }}">{{ num(max(0, $remainingReturn)) }}</td>
              <td>
                <input type="hidden" name="items[{{ $i }}][raw_material_id]" value="{{ $mat->raw_material_id }}">
                <input type="hidden" name="items[{{ $i }}][bom_item_id]" value="{{ $mat->id }}">
                <input type="number" class="bp-form-control" name="items[{{ $i }}][quantity_returned]" step="0.0001" min="0" value="0"></td>
              <td>
                <select class="bp-form-select w-100" name="items[{{ $i }}][condition]" required>
                  @foreach(\Modules\Manufacturing\Models\FabricReturnItem::getConditions() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                  @endforeach
                </select>
              </td>
              <td><input type="number" class="bp-form-control" name="items[{{ $i }}][unit_cost]" step="0.01" min="0" value="{{ num_input($mat->unit_cost) }}"></td>
              <td><input type="text" class="bp-form-control" name="items[{{ $i }}][notes]" placeholder="Notes"></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Submit -->
  <div class="bp-card">
    <div class="bp-card-body d-flex justify-content-end gap-2">
      <a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
      @bpCan('manufacturing.edit')
      <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-rotate-left me-1"></i> Record Return</button>
      @endbpCan
    </div>
  </div>
</form>

@endsection
