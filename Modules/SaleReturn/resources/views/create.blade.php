@extends('core::layouts.master')

@section('title', __('Create Sale Return'))
@section('page-title', __('Create Sale Return'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('sale-returns.index') }}">Sale Returns</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Create Sale Return</span>
@endsection

@section('page-actions')
    <a href="{{ route('sale-returns.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Sale Returns
    </a>
@endsection

@section('content')

    <form action="{{ route('sale-returns.store') }}" method="POST" id="saleReturnForm">
        @csrf

        <!-- Return Details -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-undo me-2"></i>Return Details</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="bp-form-label">Sales *</label>
                        <select class="bp-form-select select2-search w-100" name="sale_id" id="saleSelect" required>
                            <option value="">Search invoice...</option>
                            @foreach ($sales as $sale)
                                <option value="{{ $sale->id }}" data-customer="{{ $sale->customer_display_name }}"
                                    data-date="{{ $sale->created_at ? $sale->created_at->format('d M Y') : '' }}"
                                    data-total="{{ $sale->grand_total }}"
                                    {{ old('sale_id', $selectedSaleId ?? '') == $sale->id ? 'selected' : '' }}>
                                    {{ $sale->invoice_number }} &mdash; {{ $sale->customer_display_name }} &mdash;
                                    {{ currency_symbol() }} {{ number_format($sale->grand_total, 0) }}
                                </option>
                            @endforeach
                        </select>
                        @error('sale_id')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="bp-form-label">Return Date</label>
                        <input type="date" class="bp-form-control" name="return_date"
                            value="{{ old('return_date', date('Y-m-d')) }}">
                        @error('return_date')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Return Reason *</label>
                        <select class="bp-form-select w-100" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="defective" {{ old('reason') == 'defective' ? 'selected' : '' }}>Defective
                                Product</option>
                            <option value="wrong_item" {{ old('reason') == 'wrong_item' ? 'selected' : '' }}>Wrong Item
                                Delivered</option>
                            <option value="customer_request" {{ old('reason') == 'customer_request' ? 'selected' : '' }}>
                                Customer Request</option>
                            <option value="damaged" {{ old('reason') == 'damaged' ? 'selected' : '' }}>Damaged in Transit
                            </option>
                            <option value="other" {{ old('reason') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('reason')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 d-none">
                        <label class="bp-form-label">Branch *</label>
                        <select class="bp-form-select w-100" name="branch_id" required>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ old('branch_id', auth()->user()->branch_id) == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Refund Method *</label>
                        <select class="bp-form-select w-100" name="refund_method" required>
                            <option value="">Select Method</option>
                            <x-payment::account-options :selected="old('refund_method')" />
                        </select>
                        @error('refund_method')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Invoice Info (shown after selection) -->
                <div class="row g-3 mt-2 d-none" id="invoiceInfoPanel">
                    <div class="col-12">
                        <div class="bp-form-control bp-form-control-static">
                            <div class="d-flex gap-4 flex-wrap pt-2">
                                <div><span class="text-muted fs-12">Customer:</span> <span class="fw-700"
                                        id="infoCustomer">-</span></div>
                                <div><span class="text-muted fs-12">Invoice Date:</span> <span class="fw-700"
                                        id="infoDate">-</span></div>
                                <div><span class="text-muted fs-12">Invoice Total:</span> <span class="fw-700"
                                        id="infoTotal">-</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return Items -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Return Items</h5>
                <span class="fs-12 text-muted">Select items and specify return quantity</span>
            </div>
            <div class="bp-card-body p-0">
                <div class="bp-table-wrapper">
                    <table class="bp-table" id="returnItemsTable">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="checkAllItems"></th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Sold Qty</th>
                                <th>Return Qty</th>
                                <th>Unit Price</th>
                                <th>Condition</th>
                                <th>Return Amount</th>
                            </tr>
                        </thead>
                        <tbody id="saleItemsBody">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Select an invoice to load items</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notes & Summary -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="bp-card h-100">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2"></i>Notes</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="mb-3">
                            <label class="bp-form-label">Return Notes</label>
                            <textarea class="bp-form-control" name="notes" rows="3" placeholder="Reason for return, condition details...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="bp-form-label">Condition of Returned Items</label>
                            <textarea class="bp-form-control" name="condition_notes" rows="3"
                                placeholder="Describe the physical condition of returned items...">{{ old('condition_notes') }}</textarea>
                            @error('condition_notes')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bp-card h-100">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>Refund Summary</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="bp-cart-summary-row"><span>Return Items</span><span class="fw-700"
                                id="returnItemCount">0 items</span></div>
                        <div class="bp-cart-summary-row"><span>Return Subtotal</span><span class="fw-700"
                                id="returnSubtotal">{{ currency_symbol() }} 0</span></div>
                        <div class="bp-cart-summary-row total"><span>Total Refund</span><span
                                id="returnGrandTotal">{{ currency_symbol() }} 0</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('sale-returns.index') }}" class="bp-btn bp-btn-danger"><i
                    class="fa-solid fa-xmark me-1"></i>Cancel</a>
            <button type="submit" class="bp-btn bp-btn-warning" name="action" value="draft"><i
                    class="fa-solid fa-save me-1"></i> Save as Draft</button>
            <button type="submit" class="bp-btn bp-btn-success" name="action" value="approve"><i
                    class="fa-solid fa-check me-1"></i> Approve & Process</button>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Show invoice info on selection and load sale items via AJAX
            $('#saleSelect').on('change', function() {
                var selected = $(this).find(':selected');
                if (selected.val()) {
                    $('#invoiceInfoPanel').removeClass('d-none');
                    $('#infoCustomer').text(selected.data('customer'));
                    $('#infoDate').text(selected.data('date'));
                    $('#infoTotal').text('{{ currency_symbol() }} ' + formatBDT(selected.data('total')));

                    // Load sale items via AJAX
                    $.get('{{ url('admin/sale-returns/sale-items') }}/' + encodeURIComponent(selected
                        .val()), function(response) {
                        var tbody = $('#saleItemsBody');
                        tbody.empty();

                        var items = response.data || response || [];
                        if (!Array.isArray(items)) items = [];

                        if (items.length > 0) {
                            $.each(items, function(i, item) {
                                var productName = item.product_name || (item.product && item
                                    .product.name) || 'Unknown';
                                var variantName = item.variant_name || '';
                                var sku = item.sku || (item.product && item.product.sku) ||
                                    '--';
                                var variantHtml = variantName ?
                                    '<div class="fs-11 text-muted">' + $('<span>').text(
                                        variantName).html() + '</div>' : '';
                                var row = '<tr>' +
                                    '<td><input type="checkbox" class="form-check-input item-check" name="items[' +
                                    i + '][selected]" value="1" checked></td>' +
                                    '<td><div class="fw-700">' + $('<span>').text(
                                        productName).html() + '</div>' + variantHtml +
                                    '</td>' +
                                    '<td><code class="fs-11">' + $('<span>').text(sku)
                                    .html() + '</code></td>' +
                                    '<td class="text-center fw-700">' + item.quantity +
                                    '</td>' +
                                    '<td>' +
                                    '<input type="number" class="bp-form-control bp-return-qty" name="items[' +
                                    i + '][quantity]" value="' + item.quantity +
                                    '" min="0" max="' + item.quantity + '">' +
                                    '<input type="hidden" name="items[' + i +
                                    '][product_id]" value="' + item.product_id + '">' +
                                    '<input type="hidden" name="items[' + i +
                                    '][variant_id]" value="' + (item.variant_id || '') +
                                    '">' +
                                    '<input type="hidden" name="items[' + i +
                                    '][sale_item_id]" value="' + (item.sale_item_id || '') +
                                    '">' +
                                    '<input type="hidden" name="items[' + i +
                                    '][unit_price]" value="' + item.unit_price + '">' +
                                    '<input type="hidden" name="items[' + i +
                                    '][tax_amount]" value="0">' +
                                    '</td>' +
                                    '<td class="text-end fw-600">{{ currency_symbol() }} ' +
                                    formatBDT(item.unit_price) + '</td>' +
                                    '<td>' +
                                    '<select class="bp-form-select bp-form-select-sm" name="items[' +
                                    i + '][condition]">' +
                                    '<option value="good">Good</option>' +
                                    '<option value="damaged">Damaged</option>' +
                                    '<option value="defective">Defective</option>' +
                                    '<option value="opened">Opened/Used</option>' +
                                    '</select>' +
                                    '</td>' +
                                    '<td class="text-end fw-800 return-item-total">{{ currency_symbol() }} 0</td>' +
                                    '</tr>';
                                tbody.append(row);
                            });
                        } else {
                            tbody.html(
                                '<tr><td colspan="8" class="text-center text-muted py-4">No items found for this invoice</td></tr>'
                            );
                        }

                        recalculate();
                    });
                } else {
                    $('#invoiceInfoPanel').addClass('d-none');
                    $('#saleItemsBody').html(
                        '<tr><td colspan="8" class="text-center text-muted py-4">Select an invoice to load items</td></tr>'
                    );
                    recalculate();
                }
            });

            // Check all items
            $('#checkAllItems').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('.item-check').prop('checked', isChecked);
                recalculate();
            });

            // Recalculate on qty or checkbox change
            $(document).on('input', '.bp-return-qty', function() {
                recalculate();
            });

            $(document).on('change', '.item-check', function() {
                recalculate();
            });

            function recalculate() {
                var totalReturn = 0;
                var itemCount = 0;

                $('#returnItemsTable tbody tr').each(function() {
                    var isChecked = $(this).find('.item-check').prop('checked');
                    var qty = parseFloat($(this).find('.bp-return-qty').val()) || 0;
                    var unitPrice = parseFloat($(this).find('input[name$="[unit_price]"]').val()) || 0;
                    var lineTotal = 0;

                    if (isChecked && qty > 0) {
                        lineTotal = qty * unitPrice;
                        itemCount++;
                    }

                    totalReturn += lineTotal;
                    $(this).find('.return-item-total').text('{{ currency_symbol() }} ' + formatBDT(
                        lineTotal));
                });

                $('#returnItemCount').text(itemCount + (itemCount === 1 ? ' item' : ' items'));
                $('#returnSubtotal').text('{{ currency_symbol() }} ' + formatBDT(totalReturn));
                $('#returnGrandTotal').text('{{ currency_symbol() }} ' + formatBDT(totalReturn));
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

            // Auto-trigger if sale is pre-selected via query param
            if ($('#saleSelect').val()) {
                $('#saleSelect').trigger('change');
            }
        });
    </script>
@endpush
