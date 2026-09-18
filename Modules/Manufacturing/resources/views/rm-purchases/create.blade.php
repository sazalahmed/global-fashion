@extends('core::layouts.master')

@section('title', __("Create RM Purchase Order"))
@section('page-title', __("Create RM Purchase Order"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Manufacturing</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('manufacturing.rm-purchases.index') }}">RM Purchase Orders</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Create</span>
@endsection

@section('page-actions')
<a href="{{ route('manufacturing.rm-purchases.index') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back
</a>
@endsection

@section('content')

<form action="{{ route('manufacturing.rm-purchases.store') }}" method="POST" id="rmPurchaseForm" enctype="multipart/form-data">
  @csrf

  <!-- PO Details -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2"></i>Purchase Order Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="bp-form-label">Supplier *</label>
          <select class="bp-form-select w-100 select2-search" id="rmPoSupplier" name="supplier_id" required>
            <option value="">Select Supplier</option>
            @foreach($suppliers as $supplier)
              <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->company_name }}</option>
            @endforeach
          </select>
          @error('supplier_id')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">PO Date *</label>
          <input type="date" class="bp-form-control" name="po_date" value="{{ old('po_date', date('Y-m-d')) }}" required>
          @error('po_date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Expected Delivery</label>
          <input type="date" class="bp-form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date', date('Y-m-d', strtotime('+7 days'))) }}">
          @error('expected_delivery_date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  <!-- Order Items -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Order Items</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table" id="rmItemsTable">
          <thead>
            <tr>
              <th>Raw Material *</th>
              <th>Qty *</th>
              <th>Unit Price *</th>
              <th>Discount</th>
              <th>Tax %</th>
              <th>Attachment</th>
              <th>Line Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @php $oldItems = old('items', [['raw_material_id' => '', 'qty' => 1, 'unit_price' => 0, 'discount' => 0, 'tax_rate' => 0]]); @endphp
            @foreach($oldItems as $idx => $oldItem)
            <tr>
              <td>
                <select class="bp-form-select w-100 select2-search rm-select2-material" name="items[{{ $idx }}][raw_material_id]" required>
                  <option value="">Select Material</option>
                  @foreach($rawMaterials as $material)
                    <option value="{{ $material->id }}" data-cost="{{ $material->cost_price }}" data-unit="{{ $material->unit }}" {{ ($oldItem['raw_material_id'] ?? '') == $material->id ? 'selected' : '' }}>{{ $material->name }} ({{ $material->code }})</option>
                  @endforeach
                </select>
              </td>
              <td><input type="number" class="bp-form-control bp-input-narrow rm-po-qty" name="items[{{ $idx }}][qty]" value="{{ $oldItem['qty'] ?? 1 }}" min="0.0001" step="any" required></td>
              <td><input type="number" class="bp-form-control bp-input-medium rm-po-price" name="items[{{ $idx }}][unit_price]" value="{{ $oldItem['unit_price'] ?? 0 }}" min="0" step="0.01" required></td>
              <td><input type="number" class="bp-form-control bp-input-narrow rm-po-discount" name="items[{{ $idx }}][discount]" value="{{ $oldItem['discount'] ?? 0 }}" min="0" step="0.01"></td>
              <td><input type="number" class="bp-form-control bp-input-narrow rm-po-tax" name="items[{{ $idx }}][tax_rate]" value="{{ $oldItem['tax_rate'] ?? 0 }}" min="0" max="100" step="0.01"></td>
              <td><input type="file" class="bp-form-control bp-input-narrow" name="items[{{ $idx }}][attachment]" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx" style="min-width:140px"></td>
              <td class="fw-700 rm-line-total">{{ currency_symbol() }} 0</td>
              <td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger btn-remove-rm-item"><i class="fa-solid fa-xmark"></i></button></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    <div class="bp-card-footer">
      <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="addRmItem"><i class="fa-solid fa-plus me-1"></i> Add Item</button>
    </div>
  </div>

  <!-- Notes & Summary -->
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2"></i>Additional Details</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="bp-form-label">Shipping Cost ({{ currency_symbol() }})</label>
              <input type="number" class="bp-form-control" name="shipping_cost" id="rmPoShipping" value="{{ old('shipping_cost', 0) }}" min="0" step="0.01">
              @error('shipping_cost')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12">
              <label class="bp-form-label">Notes / Instructions</label>
              <textarea class="bp-form-control" name="notes" rows="4" placeholder="Special instructions for supplier...">{{ old('notes') }}</textarea>
              @error('notes')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>Summary</h5>
        </div>
        <div class="bp-card-body">
          <div class="bp-cart-summary-row"><span>Subtotal</span><span class="fw-700" id="rmSubtotal">{{ currency_symbol() }} 0</span></div>
          <div class="bp-cart-summary-row"><span>Discount</span><span id="rmDiscount">{{ currency_symbol() }} 0</span></div>
          <div class="bp-cart-summary-row"><span>Tax</span><span id="rmTax">{{ currency_symbol() }} 0</span></div>
          <div class="bp-cart-summary-row"><span>Shipping</span><span id="rmShippingDisplay">{{ currency_symbol() }} 0</span></div>
          <div class="bp-cart-summary-row total"><span>Grand Total</span><span id="rmGrandTotal">{{ currency_symbol() }} 0</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="bp-card">
    <div class="bp-card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('manufacturing.rm-purchases.index') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
      <button type="submit" name="action" value="draft" class="bp-btn bp-btn-outline"><i class="fa-solid fa-save me-1"></i> Save Draft</button>
      <button type="submit" name="action" value="submit" class="bp-btn bp-btn-warning"><i class="fa-solid fa-paper-plane me-1"></i> Submit for Approval</button>
      <button type="submit" name="action" value="approve" class="bp-btn bp-btn-primary"><i class="fa-solid fa-check me-1"></i> Approve & Create</button>
    </div>
  </div>

</form>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Select2 inits via the global helper (select2-search class). For
    // rows appended dynamically we call window.bpInitSelect2($row).

    // Build material options from server data
    var materialOptions = '<option value="">Select Material</option>';
    @foreach($rawMaterials as $material)
    materialOptions += '<option value="{{ $material->id }}" data-cost="{{ $material->cost_price }}" data-unit="{{ $material->unit }}">{{ e($material->name) }} ({{ e($material->code) }})</option>';
    @endforeach

    // Handle material selection — set cost price
    $(document).on('change', '.rm-select2-material', function () {
        var $row = $(this).closest('tr');
        var cost = $(this).find(':selected').data('cost');
        if (cost !== undefined && cost !== '') {
            $row.find('.rm-po-price').val(cost);
        }
        recalculateRmPo();
    });

    // Add item row
    var rmItemIndex = {{ count($oldItems) }};
    $('#addRmItem').on('click', function () {
        var newRow = '<tr>' +
            '<td><select class="bp-form-select w-100 select2-search rm-select2-material" name="items[' + rmItemIndex + '][raw_material_id]" required>' + materialOptions + '</select></td>' +
            '<td><input type="number" class="bp-form-control bp-input-narrow rm-po-qty" name="items[' + rmItemIndex + '][qty]" value="1" min="0.0001" step="any" required></td>' +
            '<td><input type="number" class="bp-form-control bp-input-medium rm-po-price" name="items[' + rmItemIndex + '][unit_price]" value="0" min="0" step="0.01" required></td>' +
            '<td><input type="number" class="bp-form-control bp-input-narrow rm-po-discount" name="items[' + rmItemIndex + '][discount]" value="0" min="0" step="0.01"></td>' +
            '<td><input type="number" class="bp-form-control bp-input-narrow rm-po-tax" name="items[' + rmItemIndex + '][tax_rate]" value="0" min="0" max="100" step="0.01"></td>' +
            '<td><input type="file" class="bp-form-control bp-input-narrow" name="items[' + rmItemIndex + '][attachment]" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx" style="min-width:140px"></td>' +
            '<td class="fw-700 rm-line-total">{{ currency_symbol() }} 0</td>' +
            '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger btn-remove-rm-item"><i class="fa-solid fa-xmark"></i></button></td>' +
            '</tr>';
        var $row = $(newRow);
        $('#rmItemsTable tbody').append($row);
        window.bpInitSelect2($row);
        rmItemIndex++;
    });

    // Remove item row
    $(document).on('click', '.btn-remove-rm-item', function () {
        if ($('#rmItemsTable tbody tr').length > 1) {
            var $row = $(this).closest('tr');
            if ($row.find('.rm-select2-material').hasClass('select2-hidden-accessible')) {
                $row.find('.rm-select2-material').select2('destroy');
            }
            $row.remove();
            recalculateRmPo();
        }
    });

    // Recalculate on input change
    $(document).on('input', '.rm-po-qty, .rm-po-price, .rm-po-discount, .rm-po-tax, #rmPoShipping', function () {
        recalculateRmPo();
    });

    function recalculateRmPo() {
        var subtotal = 0;
        var totalDiscount = 0;
        var totalTax = 0;

        $('#rmItemsTable tbody tr').each(function () {
            var qty = parseFloat($(this).find('.rm-po-qty').val()) || 0;
            var price = parseFloat($(this).find('.rm-po-price').val()) || 0;
            var discount = parseFloat($(this).find('.rm-po-discount').val()) || 0;
            var taxRate = parseFloat($(this).find('.rm-po-tax').val()) || 0;

            var lineSubtotal = (qty * price);
            var taxableAmount = lineSubtotal - discount;
            var lineTax = taxableAmount > 0 ? taxableAmount * (taxRate / 100) : 0;
            var lineTotal = taxableAmount + lineTax;

            subtotal += lineSubtotal;
            totalDiscount += discount;
            totalTax += lineTax;

            $(this).find('.rm-line-total').text('{{ currency_symbol() }} ' + formatBDT(lineTotal));
        });

        var shipping = parseFloat($('#rmPoShipping').val()) || 0;
        var grandTotal = subtotal - totalDiscount + totalTax + shipping;

        $('#rmSubtotal').text('{{ currency_symbol() }} ' + formatBDT(subtotal));
        $('#rmDiscount').text('{{ currency_symbol() }} ' + formatBDT(totalDiscount));
        $('#rmTax').text('{{ currency_symbol() }} ' + formatBDT(totalTax));
        $('#rmShippingDisplay').text('{{ currency_symbol() }} ' + formatBDT(shipping));
        $('#rmGrandTotal').text('{{ currency_symbol() }} ' + formatBDT(grandTotal));
    }

    function formatBDT(num) {
        num = Math.round(num);
        var str = num.toString();
        var lastThree = str.substring(str.length - 3);
        var otherNumbers = str.substring(0, str.length - 3);
        if (otherNumbers !== '') {
            lastThree = ',' + lastThree;
        }
        return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
    }

    // Initial calculation
    recalculateRmPo();
});
</script>
@endpush
