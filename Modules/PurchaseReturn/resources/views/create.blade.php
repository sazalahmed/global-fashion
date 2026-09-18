@extends('core::layouts.master')

@section('title', __('Create Purchase Return'))
@section('page-title', __('Create Purchase Return'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('purchase-returns.index') }}">Purchase Returns</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Create Purchase Return</span>
@endsection

@section('page-actions')
    <a href="{{ route('purchase-returns.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Purchase Returns
    </a>
@endsection

@section('content')

    <form action="{{ route('purchase-returns.store') }}" method="POST" id="purchaseReturnForm">
        @csrf

        <!-- Return Details -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-undo me-2"></i>Return Details</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-12 col-lg-6">
                        <label class="bp-form-label">Original Purchase Order *</label>
                        <select class="bp-form-select w-100" name="purchase_id" id="purchaseSelect" required>
                            <option value="">Search purchase order...</option>
                            @foreach ($purchases as $po)
                                <option value="{{ $po->id }}"
                                    data-supplier="{{ $po->supplier->company_name ?? 'N/A' }}"
                                    data-supplier-id="{{ $po->supplier_id }}"
                                    data-date="{{ $po->po_date ? $po->po_date->format('d M Y') : '' }}"
                                    data-total="{{ $po->grand_total }}"
                                    {{ old('purchase_id', $selectedPurchaseId ?? '') == $po->id ? 'selected' : '' }}>
                                    {{ $po->po_number }} &mdash; {{ $po->supplier->company_name ?? 'N/A' }} &mdash;
                                    {{ currency_symbol() }} {{ number_format($po->grand_total, 0) }}
                                </option>
                            @endforeach
                        </select>
                        @error('purchase_id')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="bp-form-label">Return Date</label>
                        <input type="date" class="bp-form-control" name="return_date"
                            value="{{ old('return_date', date('Y-m-d')) }}">
                        @error('return_date')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="bp-form-label">Return Reason *</label>
                        <select class="bp-form-select w-100" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="defective" {{ old('reason') == 'defective' ? 'selected' : '' }}>Defective
                                Product</option>
                            <option value="wrong_item" {{ old('reason') == 'wrong_item' ? 'selected' : '' }}>Wrong Item
                                Received</option>
                            <option value="damaged" {{ old('reason') == 'damaged' ? 'selected' : '' }}>Damaged in Transit
                            </option>
                            <option value="quality_issue" {{ old('reason') == 'quality_issue' ? 'selected' : '' }}>Quality
                                Issue</option>
                            <option value="other" {{ old('reason') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('reason')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- PO Info (shown after selection) -->
                <div class="row g-3 mt-2" id="poInfoPanel" style="display: none;">
                    <div class="col-12">
                        <div class="bp-form-control bp-form-control-static">
                            <div class="d-flex gap-4 flex-wrap pt-2">
                                <div><span class="text-muted fs-12">Supplier:</span> <span class="fw-700"
                                        id="infoSupplier">-</span></div>
                                <div><span class="text-muted fs-12">PO Date:</span> <span class="fw-700"
                                        id="infoDate">-</span></div>
                                <div><span class="text-muted fs-12">PO Total:</span> <span class="fw-700"
                                        id="infoTotal">-</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supplier ID (set via JS when PO is selected) -->
        <input type="hidden" name="supplier_id" id="supplierIdInput" value="{{ old('supplier_id') }}">

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
                                <th>Purchased Qty</th>
                                <th>Return Qty</th>
                                <th>Unit Cost</th>
                                <th>Reason</th>
                                <th>Return Amount</th>
                            </tr>
                        </thead>
                        <tbody id="returnItemsBody">
                            <tr id="noItemsRow">
                                <td colspan="8" class="text-muted py-4">
                                    <i class="fa-solid fa-info-circle me-1"></i> Select a purchase order to load items
                                </td>
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
                            <textarea class="bp-form-control" name="notes" rows="3"
                                placeholder="Describe the issue and reason for return...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="bp-form-label">Supplier Communication</label>
                            <textarea class="bp-form-control" name="supplier_notes" rows="3"
                                placeholder="Notes for supplier communication...">{{ old('supplier_notes') }}</textarea>
                            @error('supplier_notes')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bp-card h-100">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>Return Summary</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="bp-cart-summary-row"><span>Return Items</span><span class="fw-700"
                                id="returnItemCount">0 items</span></div>
                        <div class="bp-cart-summary-row"><span>Return Subtotal</span><span class="fw-700"
                                id="returnSubtotal">{{ currency_symbol() }} 0</span></div>
                        <div class="bp-cart-summary-row total"><span>Total Return Value</span><span
                                id="returnGrandTotal">{{ currency_symbol() }} 0</span></div>

                        <div id="refundSection" class="mt-3 pt-3 border-top">
                            <label class="bp-form-label"><i class="fa-solid fa-money-bill-wave me-1"></i>Refund Received
                                from Supplier <span class="text-muted fw-400">(optional)</span></label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="bp-form-control" name="refunded_amount"
                                        id="refundAmount" placeholder="Amount" step="0.01" min="0"
                                        value="{{ old('refunded_amount') }}">
                                </div>
                                <div class="col-6">
                                    <select class="bp-form-select w-100" name="refund_account_id" id="refundAccount">
                                        <option value="">Account</option>
                                        @foreach ($paymentAccounts as $pa)
                                            <option value="{{ $pa->id }}"
                                                {{ old('refund_account_id') == $pa->id ? 'selected' : '' }}>
                                                {{ $pa->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('refund_account_id')
                                    <div class="col-12 text-danger fs-12">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="fs-11 text-muted mt-2"><i class="fa-solid fa-circle-info me-1"></i>Enter an amount
                                only if the supplier refunded cash (Dr {{ __('Cash/Bank') }} on completion). Leave blank
                                for a credit note against the payable.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('purchase-returns.index') }}" class="bp-btn bp-btn-danger"><i
                    class="fa-solid fa-xmark me-1"></i>Cancel</a>
            <button type="submit" class="bp-btn bp-btn-success" name="action" value="approve"><i
                    class="fa-solid fa-check me-1"></i> Save</button>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            var purchaseItemsUrl = '{{ route('purchase-returns.purchase-items', ':id') }}';

            // Load purchase items when PO is selected
            $('#purchaseSelect').on('change', function() {
                var selected = $(this).find(':selected');
                var purchaseId = selected.val();

                if (purchaseId) {
                    $('#poInfoPanel').show();
                    $('#infoSupplier').text(selected.data('supplier'));
                    $('#infoDate').text(selected.data('date'));
                    $('#infoTotal').text('{{ currency_symbol() }} ' + formatBDT(selected.data('total')));
                    $('#supplierIdInput').val(selected.data('supplier-id'));

                    loadPurchaseItems(purchaseId);
                } else {
                    $('#poInfoPanel').hide();
                    $('#supplierIdInput').val('');
                    showNoItems();
                }
            });

            function loadPurchaseItems(purchaseId) {
                var url = purchaseItemsUrl.replace(':id', purchaseId);
                $('#returnItemsBody').html(
                    '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Loading items...</td></tr>'
                );

                $.get(url, function(response) {
                    if (response.success && response.data.length > 0) {
                        renderItems(response.data);
                    } else {
                        showNoItems('No items found for this purchase order.');
                    }
                }).fail(function() {
                    showNoItems('Failed to load purchase items.');
                });
            }

            function renderItems(items) {
                var html = '';

                $.each(items, function(index, item) {
                    var variantInfo = item.variant_name ? '<div class="fs-11 text-muted">' + escapeHtml(item
                        .variant_name) + '</div>' : '';
                    var receivedQty = item.received_qty > 0 ? item.received_qty : item.quantity;

                    html += '<tr data-unit-price="' + item.unit_price + '">';
                    html += '<td><input type="checkbox" class="form-check-input item-check" checked></td>';
                    html += '<td>';
                    html += '<div class="d-flex align-items-center gap-2">';
                    html +=
                        '<div class="bp-pos-item-img bp-pos-item-img-sm"><i class="fa-solid fa-box"></i></div>';
                    html += '<div>';
                    html += '<div class="fw-700">' + escapeHtml(item.product_name) + '</div>';
                    html += variantInfo;
                    html += '</div></div></td>';
                    html += '<td><code class="fs-11">' + escapeHtml(item.sku) + '</code></td>';
                    html += '<td class="text-center fw-700">' + receivedQty + '</td>';
                    html += '<td>';
                    html += '<input type="number" class="bp-form-control bp-return-qty" name="items[' +
                        index + '][quantity]" value="0" min="0" max="' + receivedQty + '" step="any">';
                    html += '<input type="hidden" name="items[' + index + '][product_id]" value="' + item
                        .product_id + '">';
                    html += '<input type="hidden" name="items[' + index + '][variant_id]" value="' + (item
                        .variant_id || '') + '">';
                    html += '<input type="hidden" name="items[' + index + '][unit_price]" value="' + item
                        .unit_price + '">';
                    html += '</td>';
                    html += '<td class="text-end fw-600">{{ currency_symbol() }} ' + formatBDT(item
                        .unit_price) + '</td>';
                    html += '<td>';
                    html += '<select class="bp-form-select bp-form-select-sm" name="items[' + index +
                        '][reason]">';
                    html += '<option value="">None</option>';
                    html += '<option value="defective">Defective</option>';
                    html += '<option value="damaged">Damaged</option>';
                    html += '<option value="wrong_item">Wrong Item</option>';
                    html += '<option value="quality_issue">Quality Issue</option>';
                    html += '<option value="other">Other</option>';
                    html += '</select></td>';
                    html += '<td class="text-end fw-800 return-item-total">{{ currency_symbol() }} 0</td>';
                    html += '</tr>';
                });

                $('#returnItemsBody').html(html);
                recalculate();
            }

            function showNoItems(message) {
                message = message || 'Select a purchase order to load items';
                $('#returnItemsBody').html(
                    '<tr id="noItemsRow"><td colspan="8" class="text-center text-muted py-4"><i class="fa-solid fa-info-circle me-1"></i> ' +
                    escapeHtml(message) + '</td></tr>'
                );
                recalculate();
            }

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

            // Form submit — remove unchecked / zero-qty items
            $('#purchaseReturnForm').on('submit', function() {
                $('#returnItemsBody tr').each(function() {
                    var isChecked = $(this).find('.item-check').prop('checked');
                    var qty = parseFloat($(this).find('.bp-return-qty').val()) || 0;
                    if (!isChecked || qty <= 0) {
                        $(this).find('input, select').removeAttr('name');
                    }
                });

                // Re-index items so items[0], items[1], etc. are sequential
                var idx = 0;
                $('#returnItemsBody tr').each(function() {
                    var isChecked = $(this).find('.item-check').prop('checked');
                    var qty = parseFloat($(this).find('.bp-return-qty').val()) || 0;
                    if (isChecked && qty > 0) {
                        $(this).find('[name]').each(function() {
                            var name = $(this).attr('name');
                            if (name) {
                                $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' +
                                    idx + ']'));
                            }
                        });
                        idx++;
                    }
                });
            });

            function recalculate() {
                var totalReturn = 0;
                var itemCount = 0;

                $('#returnItemsBody tr').each(function() {
                    if ($(this).attr('id') === 'noItemsRow') return;

                    var isChecked = $(this).find('.item-check').prop('checked');
                    var qty = parseFloat($(this).find('.bp-return-qty').val()) || 0;
                    var unitPrice = parseFloat($(this).data('unit-price')) || 0;
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

            function escapeHtml(text) {
                if (!text) return '';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(text));
                return div.innerHTML;
            }

            // Auto-load if a purchase is pre-selected
            if ($('#purchaseSelect').val()) {
                $('#purchaseSelect').trigger('change');
            }
        });
    </script>
@endpush
