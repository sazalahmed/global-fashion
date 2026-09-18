@extends('core::layouts.master')

@section('title', 'Edit Sale Return — ' . $return->return_number)
@section('page-title', __('Edit Sale Return'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('sale-returns.index') }}">Sale Returns</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('sale-returns.show', $return) }}">{{ $return->return_number }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit</span>
@endsection

@section('page-actions')
    <a href="{{ route('sale-returns.show', $return) }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Return
    </a>
@endsection

@section('content')

    <form action="{{ route('sale-returns.update', $return) }}" method="POST" id="saleReturnForm">
        @csrf
        @method('PUT')

        <!-- Return Details -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-undo me-2"></i>Return Details</h5>
                @switch($return->status)
                    @case('draft')
                        <span class="bp-badge bp-badge-warning">Draft</span>
                    @break

                    @case('approved')
                        <span class="bp-badge bp-badge-info">Approved</span>
                    @break

                    @case('completed')
                        <span class="bp-badge bp-badge-success">Completed</span>
                    @break

                    @case('cancelled')
                        <span class="bp-badge bp-badge-danger">Cancelled</span>
                    @break
                @endswitch
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="bp-form-label">Sales</label>
                        <input type="text" class="bp-form-control"
                            value="{{ $return->sale ? $return->sale->invoice_number . ' — ' . $return->customer_display_name : '--' }}"
                            readonly>
                        <input type="hidden" name="sale_id" value="{{ $return->sale_id }}">
                    </div>
                    <div class="col-md-2">
                        <label class="bp-form-label">Return Date</label>
                        <input type="date" class="bp-form-control" name="return_date"
                            value="{{ old('return_date', $return->return_date->format('Y-m-d')) }}">
                        @error('return_date')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Return Reason *</label>
                        <select class="bp-form-select w-100" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="defective" {{ old('reason', $return->reason) == 'defective' ? 'selected' : '' }}>
                                Defective Product</option>
                            <option value="wrong_item"
                                {{ old('reason', $return->reason) == 'wrong_item' ? 'selected' : '' }}>Wrong Item Delivered
                            </option>
                            <option value="customer_request"
                                {{ old('reason', $return->reason) == 'customer_request' ? 'selected' : '' }}>Customer
                                Request</option>
                            <option value="damaged" {{ old('reason', $return->reason) == 'damaged' ? 'selected' : '' }}>
                                Damaged in Transit</option>
                            <option value="other" {{ old('reason', $return->reason) == 'other' ? 'selected' : '' }}>Other
                            </option>
                        </select>
                        @error('reason')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Refund Method *</label>
                        <select class="bp-form-select w-100" name="refund_method" required>
                            <option value="">Select Method</option>
                            <x-payment::account-options :selected="old('refund_method', $return->refund_method)" />
                        </select>
                        @error('refund_method')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Invoice Info -->
                @if ($return->sale)
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <div class="bp-form-control bp-form-control-static">
                                <div class="d-flex gap-4 flex-wrap">
                                    <div><span class="text-muted fs-12">Customer:</span> <span
                                            class="fw-700">{{ $return->customer_display_name }}</span></div>
                                    <div><span class="text-muted fs-12">Invoice Date:</span> <span
                                            class="fw-700">{{ $return->sale->created_at ? $return->sale->created_at->format('d M Y') : '--' }}</span>
                                    </div>
                                    <div><span class="text-muted fs-12">Invoice Total:</span> <span
                                            class="fw-700">{{ currency_symbol() }}
                                            {{ number_format($return->sale->grand_total, 0) }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Return Items -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Return Items</h5>
            </div>
            <div class="bp-card-body p-0">
                <div class="bp-table-wrapper">
                    <table class="bp-table" id="returnItemsTable">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="checkAllItems"></th>
                                <th>Product</th>
                                <th>Return Qty</th>
                                <th>Unit Price</th>
                                <th>Condition</th>
                                <th>Return Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($return->items as $index => $item)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input item-check"
                                            name="items[{{ $index }}][selected]" value="1" checked></td>
                                    <td>
                                        <div class="fw-700">{{ $item->product->name ?? 'Unknown Product' }}</div>
                                        @if ($item->variant)
                                            <div class="fs-11 text-muted">{{ $item->variant->variant_name }}</div>
                                        @endif
                                        @if ($item->variant && $item->variant->sku)
                                            <code class="fs-11">{{ $item->variant->sku }}</code>
                                        @elseif($item->product && $item->product->sku)
                                            <code class="fs-11">{{ $item->product->sku }}</code>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="number" class="bp-form-control bp-return-qty"
                                            name="items[{{ $index }}][quantity]"
                                            value="{{ old('items.' . $index . '.quantity', $item->quantity) }}"
                                            min="0">
                                        <input type="hidden" name="items[{{ $index }}][product_id]"
                                            value="{{ $item->product_id }}">
                                        <input type="hidden" name="items[{{ $index }}][variant_id]"
                                            value="{{ $item->variant_id }}">
                                        <input type="hidden" name="items[{{ $index }}][sale_item_id]"
                                            value="{{ $item->sale_item_id }}">
                                        <input type="hidden" name="items[{{ $index }}][unit_price]"
                                            value="{{ num_input($item->unit_price) }}">
                                        <input type="hidden" name="items[{{ $index }}][tax_amount]"
                                            value="{{ num_input($item->tax_amount) }}">
                                    </td>
                                    <td class="text-end fw-600">{{ currency_symbol() }}
                                        {{ number_format($item->unit_price, 0) }}</td>
                                    <td>
                                        <select class="bp-form-select bp-form-select-sm"
                                            name="items[{{ $index }}][condition]">
                                            <option value="good"
                                                {{ old('items.' . $index . '.condition', $item->condition) == 'good' ? 'selected' : '' }}>
                                                Good</option>
                                            <option value="damaged"
                                                {{ old('items.' . $index . '.condition', $item->condition) == 'damaged' ? 'selected' : '' }}>
                                                Damaged</option>
                                            <option value="defective"
                                                {{ old('items.' . $index . '.condition', $item->condition) == 'defective' ? 'selected' : '' }}>
                                                Defective</option>
                                            <option value="opened"
                                                {{ old('items.' . $index . '.condition', $item->condition) == 'opened' ? 'selected' : '' }}>
                                                Opened/Used</option>
                                        </select>
                                    </td>
                                    <td class="text-end fw-800 return-item-total">{{ currency_symbol() }}
                                        {{ number_format($item->subtotal, 0) }}</td>
                                </tr>
                            @endforeach
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
                            <textarea class="bp-form-control" name="notes" rows="3">{{ old('notes', $return->notes) }}</textarea>
                            @error('notes')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="bp-form-label">Condition of Returned Items</label>
                            <textarea class="bp-form-control" name="condition_notes" rows="3">{{ old('condition_notes', $return->condition_notes) }}</textarea>
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
                                id="returnItemCount">{{ $return->items->count() }}
                                {{ $return->items->count() === 1 ? 'item' : 'items' }}</span></div>
                        <div class="bp-cart-summary-row"><span>Return Subtotal</span><span class="fw-700"
                                id="returnSubtotal">{{ currency_symbol() }}
                                {{ number_format($return->subtotal, 0) }}</span></div>
                        <div class="bp-cart-summary-row"><span>VAT (15%)</span><span
                                id="returnVat">{{ currency_symbol() }} {{ number_format($return->tax_amount, 0) }}</span>
                        </div>
                        <div class="bp-cart-summary-row total"><span>Total Refund</span><span
                                id="returnGrandTotal">{{ currency_symbol() }}
                                {{ number_format($return->total_amount, 0) }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('sale-returns.show', $return) }}" class="bp-btn bp-btn-danger"><i
                    class="fa-solid fa-xmark me-1"></i>Cancel</a>
            <button type="submit" class="bp-btn bp-btn-warning" name="action" value="draft"><i
                    class="fa-solid fa-save me-1"></i> Update as Draft</button>
            <button type="submit" class="bp-btn bp-btn-success" name="action" value="approve"><i
                    class="fa-solid fa-check me-1"></i> Approve & Process</button>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
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
                $('#returnVat').text('{{ currency_symbol() }} 0');
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
        });
    </script>
@endpush
