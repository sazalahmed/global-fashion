@extends('core::layouts.master')

@section('title', 'Edit Production Order — ' . $productionOrder->po_number)
@section('page-title', 'Edit ' . $productionOrder->po_number)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Manufacturing</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.production-orders.index') }}">Production Orders</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit</span>
@endsection

@section('page-actions')
<a href="{{ route('manufacturing.production-orders.show', $productionOrder) }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back
</a>
@endsection

@section('content')

<form action="{{ route('manufacturing.production-orders.update', $productionOrder) }}" method="POST" id="productionOrderForm">
  @csrf
  @method('PUT')

  <!-- Order Details -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-clipboard-list me-2"></i>Order Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="bp-form-label">Factory *</label>
          <select class="bp-form-select w-100" name="factory_id" required>
            <option value="">Select Factory</option>
            @foreach($factories as $factory)
              <option value="{{ $factory->id }}" {{ old('factory_id', $productionOrder->factory_id) == $factory->id ? 'selected' : '' }}>{{ $factory->name }} ({{ $factory->code }})</option>
            @endforeach
          </select>
          @error('factory_id')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Order Date *</label>
          <input type="date" class="bp-form-control" name="order_date" value="{{ old('order_date', $productionOrder->order_date->format('Y-m-d')) }}" required>
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Expected Delivery</label>
          <input type="date" class="bp-form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date', $productionOrder->expected_delivery_date?->format('Y-m-d')) }}">
        </div>
        <div class="col-12">
          <label class="bp-form-label">Notes</label>
          <textarea class="bp-form-control" name="notes" rows="2">{{ old('notes', $productionOrder->notes) }}</textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Order Items -->
  <div class="bp-card mb-4">
    <div class="bp-card-header d-flex justify-content-between align-items-center">
      <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Order Items</h5>
      <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="addItemBtn">
        <i class="fa-solid fa-plus me-1"></i> Add Item
      </button>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table" id="itemsTable">
          <thead>
            <tr>
              <th>Catalog *</th>
              <th>Color *</th>
              <th>Size *</th>
              <th>Quantity *</th>
              <th>Making Cost/Unit</th>
              <th>Product</th>
              <th>Variant</th>
              <th class="text-center" style="width: 50px;"></th>
            </tr>
          </thead>
          <tbody id="itemsBody">
            @foreach($productionOrder->items as $i => $item)
            <tr class="item-row" data-index="{{ $i }}">
              <td>
                <select class="bp-form-select w-100" name="items[{{ $i }}][catalog_id]" required>
                  <option value="">Select</option>
                  @foreach($catalogs as $catalog)
                    <option value="{{ $catalog->id }}" {{ $item->catalog_id == $catalog->id ? 'selected' : '' }}>{{ $catalog->name }}</option>
                  @endforeach
                </select>
              </td>
              <td>
                <select class="bp-form-select w-100" name="items[{{ $i }}][color_id]" required>
                  <option value="">Select</option>
                  @foreach($colors as $color)
                    <option value="{{ $color->id }}" {{ $item->color_id == $color->id ? 'selected' : '' }}>{{ $color->name }}</option>
                  @endforeach
                </select>
              </td>
              <td>
                <select class="bp-form-select w-100" name="items[{{ $i }}][size_id]" required>
                  <option value="">Select</option>
                  @foreach($sizes as $size)
                    <option value="{{ $size->id }}" {{ $item->size_id == $size->id ? 'selected' : '' }}>{{ $size->name }}</option>
                  @endforeach
                </select>
              </td>
              <td><input type="number" class="bp-form-control item-qty" name="items[{{ $i }}][quantity]" min="1" value="{{ $item->quantity }}" required></td>
              <td><input type="number" class="bp-form-control" name="items[{{ $i }}][making_cost_per_unit]" step="0.01" min="0" value="{{ num_input($item->making_cost_per_unit) }}"></td>
              <td><input type="number" class="bp-form-control" name="items[{{ $i }}][product_id]" min="0" value="{{ $item->product_id }}"></td>
              <td><input type="number" class="bp-form-control" name="items[{{ $i }}][variant_id]" min="0" value="{{ $item->variant_id }}"></td>
              <td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-item-btn"><i class="fa-solid fa-trash"></i></button></td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="text-end fw-700">Total Quantity:</td>
              <td class="fw-800" id="totalQty">{{ $productionOrder->items->sum('quantity') }}</td>
              <td colspan="4"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- BOM -->
  <div class="bp-card mb-4">
    <div class="bp-card-header d-flex justify-content-between align-items-center">
      <h5 class="bp-card-title"><i class="fa-solid fa-scissors me-2"></i>Bill of Materials (BOM)</h5>
      <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="addMaterialBtn">
        <i class="fa-solid fa-plus me-1"></i> Add Material
      </button>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table" id="materialsTable">
          <thead>
            <tr>
              <th>Raw Material *</th>
              <th>Planned Quantity *</th>
              <th>Expected Return Qty</th>
              <th>Unit Cost</th>
              <th>Est. Net Cost</th>
              <th class="text-center" style="width: 50px;"></th>
            </tr>
          </thead>
          <tbody id="materialsBody">
            @foreach($productionOrder->materials as $m => $mat)
            <tr class="material-row" data-index="{{ $m }}">
              <td>
                <select class="bp-form-select w-100" name="materials[{{ $m }}][raw_material_id]" required>
                  <option value="">Select</option>
                  @foreach($rawMaterials as $rm)
                    <option value="{{ $rm->id }}" {{ $mat->raw_material_id == $rm->id ? 'selected' : '' }}>{{ $rm->name }} ({{ $rm->code }})</option>
                  @endforeach
                </select>
              </td>
              <td><input type="number" class="bp-form-control mat-planned" name="materials[{{ $m }}][planned_quantity]" step="0.0001" min="0" value="{{ num_input($mat->planned_quantity) }}" required></td>
              <td><input type="number" class="bp-form-control mat-return" name="materials[{{ $m }}][expected_return_quantity]" step="0.0001" min="0" value="{{ num_input($mat->expected_return_quantity) }}"></td>
              <td><input type="number" class="bp-form-control mat-cost" name="materials[{{ $m }}][unit_cost]" step="0.01" min="0" value="{{ num_input($mat->unit_cost) }}"></td>
              <td class="fw-700 mat-net-cost">{{ currency_symbol() }} {{ number_format(max(0, (float)$mat->planned_quantity - (float)$mat->expected_return_quantity) * (float)$mat->unit_cost) }}</td>
              <td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-material-btn"><i class="fa-solid fa-trash"></i></button></td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="text-end fw-700">Total Estimated Material Cost:</td>
              <td class="fw-800" id="totalMaterialCost">{{ currency_symbol() }} 0</td>
              <td></td>
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
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Update Order</button>
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
'use strict';

(function() {
  var itemIndex = {{ $productionOrder->items->count() }};
  var materialIndex = {{ $productionOrder->materials->count() }};

  var catalogOptions = '{!! $catalogs->map(fn($c) => "<option value=\"{$c->id}\">{$c->name}</option>")->implode("") !!}';
  var colorOptions = '{!! $colors->map(fn($c) => "<option value=\"{$c->id}\">{$c->name}</option>")->implode("") !!}';
  var sizeOptions = '{!! $sizes->map(fn($s) => "<option value=\"{$s->id}\">{$s->name}</option>")->implode("") !!}';
  var materialOptions = '{!! $rawMaterials->map(fn($m) => "<option value=\"{$m->id}\">{$m->name} ({$m->code})</option>")->implode("") !!}';

  $('#addItemBtn').on('click', function() {
    var row = '<tr class="item-row" data-index="' + itemIndex + '">' +
      '<td><select class="bp-form-select w-100" name="items[' + itemIndex + '][catalog_id]" required><option value="">Select</option>' + catalogOptions + '</select></td>' +
      '<td><select class="bp-form-select w-100" name="items[' + itemIndex + '][color_id]" required><option value="">Select</option>' + colorOptions + '</select></td>' +
      '<td><select class="bp-form-select w-100" name="items[' + itemIndex + '][size_id]" required><option value="">Select</option>' + sizeOptions + '</select></td>' +
      '<td><input type="number" class="bp-form-control item-qty" name="items[' + itemIndex + '][quantity]" min="1" value="1" required></td>' +
      '<td><input type="number" class="bp-form-control" name="items[' + itemIndex + '][making_cost_per_unit]" step="0.01" min="0" value="0"></td>' +
      '<td><input type="number" class="bp-form-control" name="items[' + itemIndex + '][product_id]" min="0" placeholder="Product ID"></td>' +
      '<td><input type="number" class="bp-form-control" name="items[' + itemIndex + '][variant_id]" min="0" placeholder="Variant ID"></td>' +
      '<td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-item-btn"><i class="fa-solid fa-trash"></i></button></td>' +
      '</tr>';
    $('#itemsBody').append(row);
    itemIndex++;
    recalcTotals();
  });

  $('#addMaterialBtn').on('click', function() {
    var row = '<tr class="material-row" data-index="' + materialIndex + '">' +
      '<td><select class="bp-form-select w-100" name="materials[' + materialIndex + '][raw_material_id]" required><option value="">Select</option>' + materialOptions + '</select></td>' +
      '<td><input type="number" class="bp-form-control mat-planned" name="materials[' + materialIndex + '][planned_quantity]" step="0.0001" min="0" value="0" required></td>' +
      '<td><input type="number" class="bp-form-control mat-return" name="materials[' + materialIndex + '][expected_return_quantity]" step="0.0001" min="0" value="0"></td>' +
      '<td><input type="number" class="bp-form-control mat-cost" name="materials[' + materialIndex + '][unit_cost]" step="0.01" min="0" value="0"></td>' +
      '<td class="fw-700 mat-net-cost">{{ currency_symbol() }} 0</td>' +
      '<td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-material-btn"><i class="fa-solid fa-trash"></i></button></td>' +
      '</tr>';
    $('#materialsBody').append(row);
    materialIndex++;
  });

  $(document).on('click', '.remove-item-btn', function() {
    if ($('#itemsBody .item-row').length > 1) {
      $(this).closest('tr').remove();
      recalcTotals();
    }
  });

  $(document).on('click', '.remove-material-btn', function() {
    $(this).closest('tr').remove();
    recalcMaterialCosts();
  });

  $(document).on('input', '.item-qty', function() { recalcTotals(); });
  $(document).on('input', '.mat-planned, .mat-return, .mat-cost', function() {
    var $row = $(this).closest('tr');
    var planned = parseFloat($row.find('.mat-planned').val()) || 0;
    var returnQty = parseFloat($row.find('.mat-return').val()) || 0;
    var cost = parseFloat($row.find('.mat-cost').val()) || 0;
    var net = Math.max(0, planned - returnQty) * cost;
    $row.find('.mat-net-cost').text('{{ currency_symbol() }} ' + Math.round(net).toLocaleString());
    recalcMaterialCosts();
  });

  function recalcTotals() {
    var total = 0;
    $('.item-qty').each(function() { total += parseInt($(this).val()) || 0; });
    $('#totalQty').text(total.toLocaleString());
  }

  function recalcMaterialCosts() {
    var total = 0;
    $('.material-row').each(function() {
      var planned = parseFloat($(this).find('.mat-planned').val()) || 0;
      var returnQty = parseFloat($(this).find('.mat-return').val()) || 0;
      var cost = parseFloat($(this).find('.mat-cost').val()) || 0;
      total += Math.max(0, planned - returnQty) * cost;
    });
    $('#totalMaterialCost').text('{{ currency_symbol() }} ' + Math.round(total).toLocaleString());
  }

  recalcMaterialCosts();
})();
</script>
@endpush
