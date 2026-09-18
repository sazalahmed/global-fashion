@extends('core::layouts.master')

@section('title', __("Create Credit Note"))
@section('page-title', __("Create Credit Note"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.credit-notes.index') }}">Credit Notes</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Create</span>
@endsection

@section('page-actions')
<a href="{{ route('accounting.credit-notes.index') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Credit Notes
</a>
@endsection

@section('content')

<form action="{{ route('accounting.credit-notes.store') }}" method="POST" id="creditNoteForm">
    @csrf

    {{-- Credit Note Header --}}
    <div class="bp-card mb-4">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Credit Note Details</h5>
        </div>
        <div class="bp-card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="bp-form-label">Customer</label>
                    <input type="text" class="bp-form-control" name="customer_id"
                           value="{{ old('customer_id') }}"
                           placeholder="Enter customer name or ID">
                    @error('customer_id')
                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="bp-form-label">Related Sale / Invoice</label>
                    <input type="text" class="bp-form-control" name="sale_id"
                           value="{{ old('sale_id') }}"
                           placeholder="Sale or invoice reference (optional)">
                    @error('sale_id')
                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="bp-form-label">Issue Date *</label>
                    <input type="date" class="bp-form-control" name="issue_date"
                           value="{{ old('issue_date', date('Y-m-d')) }}" required>
                    @error('issue_date')
                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="bp-form-label">Reason *</label>
                    <textarea class="bp-form-control" name="reason" rows="3"
                              placeholder="Describe the reason for issuing this credit note..." required>{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="bp-form-label">Additional Notes</label>
                    <textarea class="bp-form-control" name="notes" rows="3"
                              placeholder="Any additional notes (optional)...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="bp-card mb-4">
        <div class="bp-card-header d-flex justify-content-between align-items-center">
            <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Items</h5>
            <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="addItemRowBtn">
                <i class="fa-solid fa-plus me-1"></i> Add Row
            </button>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th class="text-center cn-col-num">#</th>
                            <th class="cn-col-product">Product *</th>
                            <th class="cn-col-desc">Description</th>
                            <th class="text-end cn-col-qty">Qty *</th>
                            <th class="text-end cn-col-price">Unit Price *</th>
                            <th class="text-end cn-col-tax">Tax ({{ currency_symbol() }})</th>
                            <th class="text-end cn-col-total">Total ({{ currency_symbol() }})</th>
                            <th class="text-center cn-col-action"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr class="cn-item-row" data-row="0">
                            <td class="text-center fw-600 row-number">1</td>
                            <td>
                                <select class="bp-form-select w-100 product-select" name="items[0][product_id]" required>
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}"
                                            data-price="{{ $product->sell_price }}">
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" class="bp-form-control item-description"
                                       name="items[0][description]" placeholder="Line description...">
                            </td>
                            <td>
                                <input type="number" class="bp-form-control text-end qty-input"
                                       name="items[0][quantity]" value="1"
                                       min="0.01" step="0.01" required>
                            </td>
                            <td>
                                <input type="number" class="bp-form-control text-end price-input"
                                       name="items[0][unit_price]" placeholder="0.00"
                                       min="0" step="0.01" required>
                            </td>
                            <td>
                                <input type="number" class="bp-form-control text-end tax-input"
                                       name="items[0][tax_amount]" placeholder="0.00"
                                       min="0" step="0.01" value="0">
                            </td>
                            <td class="text-end fw-700 line-total">{{ currency_symbol() }} 0.00</td>
                            <td class="text-center">
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-item-row" title="Remove Row">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bp-table-totals-row">
                            <td colspan="6" class="text-end fw-700">Subtotal:</td>
                            <td class="text-end fw-700" id="grandSubtotal">{{ currency_symbol() }} 0.00</td>
                            <td></td>
                        </tr>
                        <tr class="bp-table-totals-row">
                            <td colspan="6" class="text-end fw-700">Total Tax:</td>
                            <td class="text-end fw-700" id="grandTax">{{ currency_symbol() }} 0.00</td>
                            <td></td>
                        </tr>
                        <tr class="bp-table-totals-row">
                            <td colspan="6" class="text-end fw-800">Grand Total:</td>
                            <td class="text-end fw-800" id="grandTotal">{{ currency_symbol() }} 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="bp-card">
        <div class="bp-card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('accounting.credit-notes.index') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
            <button type="submit" name="status" value="draft" class="bp-btn bp-btn-success">
                <i class="fa-solid fa-file-pen me-1"></i> Save as Draft
            </button>
        </div>
    </div>

</form>

@endsection

@push('scripts')
<script>
'use strict';

var productsData = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sell_price' => $p->sell_price]));

$(function () {
    var rowIndex = 1;

    function formatBDT(num) {
        num = Math.round(num * 100) / 100;
        var parts = num.toFixed(2).split('.');
        var intPart = parts[0];
        var decPart = parts[1];
        var lastThree = intPart.substring(intPart.length - 3);
        var otherNums = intPart.substring(0, intPart.length - 3);
        if (otherNums !== '') {
            lastThree = ',' + lastThree;
        }
        var formatted = otherNums.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
        return '{{ currency_symbol() }} ' + formatted + (decPart === '00' ? '' : '.' + decPart);
    }

    function buildProductOptions(selectedId) {
        var html = '<option value="">Select Product</option>';
        $.each(productsData, function (i, p) {
            var sel = (selectedId && String(p.id) === String(selectedId)) ? ' selected' : '';
            html += '<option value="' + p.id + '" data-price="' + p.sell_price + '"' + sel + '>' + $('<div>').text(p.name).html() + '</option>';
        });
        return html;
    }

    function recalculateTotals() {
        var subtotal = 0;
        var totalTax = 0;

        $('.cn-item-row').each(function () {
            var qty   = parseFloat($(this).find('.qty-input').val()) || 0;
            var price = parseFloat($(this).find('.price-input').val()) || 0;
            var tax   = parseFloat($(this).find('.tax-input').val()) || 0;
            var lineTotal = qty * price + tax;
            subtotal  += qty * price;
            totalTax  += tax;
            $(this).find('.line-total').text(formatBDT(lineTotal));
        });

        $('#grandSubtotal').text(formatBDT(subtotal));
        $('#grandTax').text(formatBDT(totalTax));
        $('#grandTotal').text(formatBDT(subtotal + totalTax));
    }

    function renumberRows() {
        $('#itemsBody .cn-item-row').each(function (i) {
            $(this).find('.row-number').text(i + 1);
        });
    }

    // Add new item row
    $('#addItemRowBtn').on('click', function () {
        var optionsHtml = buildProductOptions(null);
        var newRow = '<tr class="cn-item-row" data-row="' + rowIndex + '">' +
            '<td class="text-center fw-600 row-number">' + (rowIndex + 1) + '</td>' +
            '<td><select class="bp-form-select w-100 product-select" name="items[' + rowIndex + '][product_id]" required>' + optionsHtml + '</select></td>' +
            '<td><input type="text" class="bp-form-control item-description" name="items[' + rowIndex + '][description]" placeholder="Line description..."></td>' +
            '<td><input type="number" class="bp-form-control text-end qty-input" name="items[' + rowIndex + '][quantity]" value="1" min="0.01" step="0.01" required></td>' +
            '<td><input type="number" class="bp-form-control text-end price-input" name="items[' + rowIndex + '][unit_price]" placeholder="0.00" min="0" step="0.01" required></td>' +
            '<td><input type="number" class="bp-form-control text-end tax-input" name="items[' + rowIndex + '][tax_amount]" placeholder="0.00" min="0" step="0.01" value="0"></td>' +
            '<td class="text-end fw-700 line-total">{{ currency_symbol() }} 0.00</td>' +
            '<td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-item-row" title="Remove Row"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';
        $('#itemsBody').append(newRow);
        rowIndex++;
        renumberRows();
    });

    // Remove item row
    $(document).on('click', '.remove-item-row', function () {
        if ($('#itemsBody .cn-item-row').length <= 1) {
            alert('A credit note must have at least one item.');
            return;
        }
        $(this).closest('tr').remove();
        renumberRows();
        recalculateTotals();
    });

    // Auto-fill price when product is selected
    $(document).on('change', '.product-select', function () {
        var price = $(this).find(':selected').data('price') || '';
        $(this).closest('tr').find('.price-input').val(price);
        recalculateTotals();
    });

    // Recalculate on qty/price/tax change
    $(document).on('input', '.qty-input, .price-input, .tax-input', function () {
        recalculateTotals();
    });

    // Initial calculation
    recalculateTotals();
});
</script>
@endpush
