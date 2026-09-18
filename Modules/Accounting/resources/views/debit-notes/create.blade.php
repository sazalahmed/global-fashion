@extends('core::layouts.master')

@section('title', __("Create Debit Note"))
@section('page-title', __("Create Debit Note"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.chart-of-accounts') }}">Accounting</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.debit-notes.index') }}">Debit Notes</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Create</span>
@endsection

@section('page-actions')
<a href="{{ route('accounting.debit-notes.index') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Debit Notes
</a>
@endsection

@section('content')

<form action="{{ route('accounting.debit-notes.store') }}" method="POST" id="debitNoteForm">
  @csrf

  <!-- Debit Note Header -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Debit Note Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="bp-form-label">Supplier *</label>
          <select class="bp-form-select w-100" name="supplier_id" id="supplierSelect" required>
            <option value="">Select Supplier</option>
            @foreach($suppliers as $supplier)
              <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                {{ $supplier->company_name }}
              </option>
            @endforeach
          </select>
          @error('supplier_id')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Purchase Reference</label>
          <input type="text" class="bp-form-control" name="purchase_id"
                 value="{{ old('purchase_id') }}" placeholder="e.g. PO-2026-0034">
          @error('purchase_id')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Issue Date *</label>
          <input type="date" class="bp-form-control" name="issue_date"
                 value="{{ old('issue_date', date('Y-m-d')) }}" required>
          @error('issue_date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Reason *</label>
          <input type="text" class="bp-form-control" name="reason"
                 value="{{ old('reason') }}" placeholder="e.g. Goods returned — defective batch" required>
          @error('reason')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-12">
          <label class="bp-form-label">Notes</label>
          <textarea class="bp-form-control" name="notes" rows="2"
                    placeholder="Additional notes or instructions for this debit note...">{{ old('notes') }}</textarea>
          @error('notes')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  <!-- Line Items -->
  <div class="bp-card mb-4">
    <div class="bp-card-header d-flex justify-content-between align-items-center">
      <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Items</h5>
      <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="addDnRowBtn">
        <i class="fa-solid fa-plus me-1"></i> Add Row
      </button>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table" id="dnItemsTable">
          <thead>
            <tr>
              <th class="text-center" style="width: 40px;">#</th>
              <th style="width: 22%;">Product</th>
              <th>Description</th>
              <th class="text-center" style="width: 90px;">Qty</th>
              <th class="text-end" style="width: 140px;">Unit Price ({{ currency_symbol() }})</th>
              <th class="text-end" style="width: 120px;">Tax ({{ currency_symbol() }})</th>
              <th class="text-end" style="width: 140px;">Line Total ({{ currency_symbol() }})</th>
              <th class="text-center" style="width: 50px;"></th>
            </tr>
          </thead>
          <tbody id="dnItemsBody">
            <tr class="dn-item-row" data-row="1">
              <td class="text-center fw-600 dn-row-number">1</td>
              <td>
                <select class="bp-form-select w-100 dn-product-select" name="items[0][product_id]">
                  <option value="">Select Product</option>
                  @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->cost_price ?? 0 }}">
                      {{ $product->name }}
                    </option>
                  @endforeach
                </select>
              </td>
              <td>
                <input type="text" class="bp-form-control" name="items[0][description]"
                       placeholder="Item description...">
              </td>
              <td>
                <input type="number" class="bp-form-control text-center dn-qty"
                       name="items[0][quantity]" value="1" min="0.01" step="0.01">
              </td>
              <td>
                <input type="number" class="bp-form-control text-end dn-price"
                       name="items[0][unit_price]" value="0.00" min="0" step="0.01" placeholder="0.00">
              </td>
              <td>
                <input type="number" class="bp-form-control text-end dn-tax"
                       name="items[0][tax_amount]" value="0.00" min="0" step="0.01" placeholder="0.00">
              </td>
              <td class="text-end fw-700 dn-line-total">{{ currency_symbol() }} 0</td>
              <td class="text-center">
                <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger dn-remove-row" title="Remove">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="bp-table-totals-row">
              <td colspan="5" class="text-end fw-800">Subtotal:</td>
              <td class="text-end fw-700" id="dnTotalTax">{{ currency_symbol() }} 0</td>
              <td class="text-end fw-800" id="dnSubtotal">{{ currency_symbol() }} 0</td>
              <td></td>
            </tr>
            <tr class="bp-table-totals-row">
              <td colspan="6" class="text-end fw-800">Grand Total:</td>
              <td class="text-end fw-800 fs-14" id="dnGrandTotal">{{ currency_symbol() }} 0</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="bp-card">
    <div class="bp-card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('accounting.debit-notes.index') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
      <button type="submit" name="status" value="draft" class="bp-btn bp-btn-outline">
        <i class="fa-solid fa-file-pen me-1"></i> Save as Draft
      </button>
    </div>
  </div>

</form>

@endsection

@push('scripts')
<script>
'use strict';

var dnProductsJson = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'cost_price' => $p->cost_price ?? 0])->values());

$(function () {
    var dnRowIndex = 1;

    function formatBDT(num) {
        num = Math.round(num * 100) / 100;
        var parts = num.toFixed(2).split('.');
        var intPart = parts[0];
        var decPart = parts[1];
        var lastThree = intPart.substring(intPart.length - 3);
        var otherNumbers = intPart.substring(0, intPart.length - 3);
        if (otherNumbers !== '') {
            lastThree = ',' + lastThree;
        }
        var formatted = otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
        return formatted + (decPart === '00' ? '' : '.' + decPart);
    }

    function buildProductOptionsHtml(selectedId) {
        var html = '<option value="">Select Product</option>';
        $.each(dnProductsJson, function (i, p) {
            var sel = (selectedId && p.id == selectedId) ? ' selected' : '';
            html += '<option value="' + p.id + '" data-price="' + p.cost_price + '"' + sel + '>' +
                $('<div>').text(p.name).html() + '</option>';
        });
        return html;
    }

    function recalculateTotals() {
        var subtotal = 0;
        var totalTax = 0;

        $('.dn-item-row').each(function () {
            var qty = parseFloat($(this).find('.dn-qty').val()) || 0;
            var price = parseFloat($(this).find('.dn-price').val()) || 0;
            var tax = parseFloat($(this).find('.dn-tax').val()) || 0;
            var lineTotal = (qty * price) + tax;
            subtotal += (qty * price);
            totalTax += tax;
            $(this).find('.dn-line-total').text('{{ currency_symbol() }} ' + formatBDT(lineTotal));
        });

        var grandTotal = subtotal + totalTax;
        $('#dnSubtotal').text('{{ currency_symbol() }} ' + formatBDT(subtotal));
        $('#dnTotalTax').text('{{ currency_symbol() }} ' + formatBDT(totalTax));
        $('#dnGrandTotal').text('{{ currency_symbol() }} ' + formatBDT(grandTotal));
    }

    function renumberDnRows() {
        $('#dnItemsBody .dn-item-row').each(function (i) {
            $(this).find('.dn-row-number').text(i + 1);
        });
    }

    // Add row
    $('#addDnRowBtn').on('click', function () {
        var productOptions = buildProductOptionsHtml(null);
        var newRow = '<tr class="dn-item-row" data-row="' + (dnRowIndex + 1) + '">' +
            '<td class="text-center fw-600 dn-row-number">' + (dnRowIndex + 1) + '</td>' +
            '<td><select class="bp-form-select w-100 dn-product-select" name="items[' + dnRowIndex + '][product_id]">' + productOptions + '</select></td>' +
            '<td><input type="text" class="bp-form-control" name="items[' + dnRowIndex + '][description]" placeholder="Item description..."></td>' +
            '<td><input type="number" class="bp-form-control text-center dn-qty" name="items[' + dnRowIndex + '][quantity]" value="1" min="0.01" step="0.01"></td>' +
            '<td><input type="number" class="bp-form-control text-end dn-price" name="items[' + dnRowIndex + '][unit_price]" value="0.00" min="0" step="0.01" placeholder="0.00"></td>' +
            '<td><input type="number" class="bp-form-control text-end dn-tax" name="items[' + dnRowIndex + '][tax_amount]" value="0.00" min="0" step="0.01" placeholder="0.00"></td>' +
            '<td class="text-end fw-700 dn-line-total">{{ currency_symbol() }} 0</td>' +
            '<td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger dn-remove-row" title="Remove"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';
        $('#dnItemsBody').append(newRow);
        dnRowIndex++;
        recalculateTotals();
    });

    // Remove row
    $(document).on('click', '.dn-remove-row', function () {
        if ($('#dnItemsBody .dn-item-row').length <= 1) {
            alert('A debit note must have at least one item.');
            return;
        }
        $(this).closest('tr').remove();
        renumberDnRows();
        recalculateTotals();
    });

    // Auto-fill price when product is selected
    $(document).on('change', '.dn-product-select', function () {
        var selectedOption = $(this).find('option:selected');
        var price = parseFloat(selectedOption.data('price')) || 0;
        $(this).closest('tr').find('.dn-price').val(window.numInput(price));
        recalculateTotals();
    });

    // Recalculate on quantity/price/tax change
    $(document).on('input', '.dn-qty, .dn-price, .dn-tax', function () {
        recalculateTotals();
    });

    // Form validation before submit
    $('#debitNoteForm').on('submit', function (e) {
        var hasItem = false;
        $('.dn-item-row').each(function () {
            var qty = parseFloat($(this).find('.dn-qty').val()) || 0;
            var price = parseFloat($(this).find('.dn-price').val()) || 0;
            if (qty > 0 && price > 0) {
                hasItem = true;
            }
        });
        if (!hasItem) {
            e.preventDefault();
            alert('Please add at least one item with a valid quantity and unit price.');
            return false;
        }
    });

    // Initial calculation
    recalculateTotals();
});
</script>
@endpush
