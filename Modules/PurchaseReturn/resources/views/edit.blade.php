@extends('core::layouts.master')

@section('title', 'Edit Purchase Return — ' . $purchaseReturn->return_number)
@section('page-title', __("Edit Purchase Return"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('purchase-returns.index') }}">Purchase Returns</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('purchase-returns.show', $purchaseReturn) }}">{{ $purchaseReturn->return_number }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit</span>
@endsection

@section('page-actions')
<a href="{{ route('purchase-returns.show', $purchaseReturn) }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Return
</a>
@endsection

@section('content')

<form action="{{ route('purchase-returns.update', $purchaseReturn) }}" method="POST" id="purchaseReturnForm">
  @csrf
  @method('PUT')

  <!-- Return Details -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-undo me-2"></i>Return Details</h5>
      <span class="bp-badge bp-badge-warning">Pending</span>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="bp-form-label">Original Purchase Order</label>
          @if($purchaseReturn->purchase)
            <input type="text" class="bp-form-control" value="{{ $purchaseReturn->purchase->po_number }} — {{ $purchaseReturn->supplier->company_name ?? 'N/A' }}" readonly>
          @else
            <input type="text" class="bp-form-control" value="No linked purchase" readonly>
          @endif
          <input type="hidden" name="purchase_id" value="{{ $purchaseReturn->purchase_id }}">
          <input type="hidden" name="supplier_id" value="{{ $purchaseReturn->supplier_id }}">
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Return Date</label>
          <input type="date" class="bp-form-control" name="return_date" value="{{ old('return_date', $purchaseReturn->return_date?->format('Y-m-d')) }}">
          @error('return_date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Return Reason *</label>
          <select class="bp-form-select w-100" name="reason" required>
            <option value="">Select Reason</option>
            @foreach(['defective' => 'Defective Product', 'wrong_item' => 'Wrong Item Received', 'damaged' => 'Damaged in Transit', 'quality_issue' => 'Quality Issue', 'other' => 'Other'] as $val => $label)
              <option value="{{ $val }}" {{ old('reason', $purchaseReturn->reason) == $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('reason')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <!-- PO Info -->
      @if($purchaseReturn->purchase)
      <div class="row g-3 mt-2">
        <div class="col-12">
          <div class="bp-form-control bp-form-control-static">
            <div class="d-flex gap-4 flex-wrap">
              <div><span class="text-muted fs-12">Supplier:</span> <span class="fw-700">{{ $purchaseReturn->supplier->company_name ?? 'N/A' }}</span></div>
              <div><span class="text-muted fs-12">PO Date:</span> <span class="fw-700">{{ $purchaseReturn->purchase->po_date?->format('d M Y') ?? '—' }}</span></div>
              <div><span class="text-muted fs-12">PO Total:</span> <span class="fw-700">{{ currency_symbol() }} {{ number_format($purchaseReturn->purchase->grand_total, 0) }}</span></div>
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
              <th><input type="checkbox" class="form-check-input" id="checkAllItems" checked></th>
              <th>Product</th>
              <th>SKU</th>
              <th>Return Qty</th>
              <th>Unit Cost</th>
              <th>Reason</th>
              <th>Return Amount</th>
            </tr>
          </thead>
          <tbody id="returnItemsBody">
            @forelse($purchaseReturn->items as $index => $item)
              <tr data-unit-price="{{ $item->unit_price }}">
                <td><input type="checkbox" class="form-check-input item-check" checked></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="bp-pos-item-img bp-pos-item-img-sm"><i class="fa-solid fa-box"></i></div>
                    <div>
                      <div class="fw-700">{{ $item->product->name ?? 'Unknown' }}</div>
                      @if($item->variant)
                        <div class="fs-11 text-muted">{{ $item->variant->variant_name }}</div>
                      @endif
                    </div>
                  </div>
                </td>
                <td><code class="fs-11">{{ $item->variant->sku ?? $item->product->sku ?? '' }}</code></td>
                <td>
                  <input type="number" class="bp-form-control bp-return-qty" name="items[{{ $index }}][quantity]" value="{{ old("items.{$index}.quantity", (float) $item->quantity) }}" min="0" step="any">
                  <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                  <input type="hidden" name="items[{{ $index }}][variant_id]" value="{{ $item->variant_id }}">
                  <input type="hidden" name="items[{{ $index }}][unit_price]" value="{{ num_input($item->unit_price) }}">
                </td>
                <td class="text-end fw-600">{{ currency_symbol() }} {{ number_format($item->unit_price, 0) }}</td>
                <td>
                  <select class="bp-form-select bp-form-select-sm" name="items[{{ $index }}][reason]">
                    <option value="">None</option>
                    @foreach(['defective' => 'Defective', 'damaged' => 'Damaged', 'wrong_item' => 'Wrong Item', 'quality_issue' => 'Quality Issue', 'other' => 'Other'] as $val => $label)
                      <option value="{{ $val }}" {{ old("items.{$index}.reason", $item->reason) == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </td>
                <td class="text-end fw-800 return-item-total">{{ currency_symbol() }} {{ number_format($item->line_total, 0) }}</td>
              </tr>
            @empty
              <tr id="noItemsRow">
                <td colspan="7" class="text-center text-muted py-4">
                  <i class="fa-solid fa-info-circle me-1"></i> No items in this return
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Add more items from the original purchase -->
  @if($purchaseReturn->purchase_id)
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-plus me-2"></i>Add More Items from Purchase</h5>
      <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="loadPurchaseItemsBtn">
        <i class="fa-solid fa-rotate me-1"></i> Load Available Items
      </button>
    </div>
    <div class="bp-card-body p-0" id="availableItemsContainer" style="display: none;">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Variant</th>
              <th>SKU</th>
              <th>Purchased Qty</th>
              <th>Unit Cost</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="availableItemsBody"></tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

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
            <textarea class="bp-form-control" name="notes" rows="3">{{ old('notes', $purchaseReturn->notes) }}</textarea>
            @error('notes')
              <div class="text-danger fs-12 mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div>
            <label class="bp-form-label">Supplier Communication</label>
            <textarea class="bp-form-control" name="supplier_notes" rows="3">{{ old('supplier_notes', $purchaseReturn->supplier_notes ?? '') }}</textarea>
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
          <div class="bp-cart-summary-row"><span>Return Items</span><span class="fw-700" id="returnItemCount">{{ $purchaseReturn->items->count() }} items</span></div>
          <div class="bp-cart-summary-row"><span>Return Subtotal</span><span class="fw-700" id="returnSubtotal">{{ currency_symbol() }} {{ number_format($purchaseReturn->subtotal, 0) }}</span></div>
          <div class="bp-cart-summary-row total"><span>Total Return Value</span><span id="returnGrandTotal">{{ currency_symbol() }} {{ number_format($purchaseReturn->total, 0) }}</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="d-flex justify-content-end gap-2 mt-3">
    <a href="{{ route('purchase-returns.show', $purchaseReturn) }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
    <button type="submit" class="bp-btn bp-btn-outline" name="action" value="draft"><i class="fa-solid fa-save me-1"></i> Update as Pending</button>
    <button type="submit" class="bp-btn bp-btn-success" name="action" value="approve"><i class="fa-solid fa-check me-1"></i> Approve & Submit</button>
  </div>

</form>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    var purchaseId = '{{ $purchaseReturn->purchase_id }}';
    var purchaseItemsUrl = '{{ route("purchase-returns.purchase-items", ":id") }}';

    // Existing return item product+variant combos (to exclude from "add more" list)
    var existingItems = {};
    @foreach($purchaseReturn->items as $item)
      existingItems['{{ $item->product_id }}_{{ $item->variant_id ?? "null" }}'] = true;
    @endforeach

    // Check all items
    $('#checkAllItems').on('change', function () {
        var isChecked = $(this).prop('checked');
        $('.item-check').prop('checked', isChecked);
        recalculate();
    });

    // Recalculate on qty or checkbox change
    $(document).on('input', '.bp-return-qty', function () {
        recalculate();
    });

    $(document).on('change', '.item-check', function () {
        recalculate();
    });

    // Load available items from the original purchase
    $('#loadPurchaseItemsBtn').on('click', function () {
        var url = purchaseItemsUrl.replace(':id', purchaseId);
        var $container = $('#availableItemsContainer');
        var $body = $('#availableItemsBody');

        $body.html('<tr><td colspan="6" class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Loading...</td></tr>');
        $container.show();

        $.get(url, function (response) {
            if (!response.success || !response.data.length) {
                $body.html('<tr><td colspan="6" class="text-center text-muted py-3">No items available</td></tr>');
                return;
            }

            var html = '';
            var hasAvailable = false;

            $.each(response.data, function (i, item) {
                var key = item.product_id + '_' + (item.variant_id || 'null');
                if (existingItems[key]) return; // Skip already added items

                hasAvailable = true;
                html += '<tr data-product-id="' + item.product_id + '" data-variant-id="' + (item.variant_id || '') + '" data-unit-price="' + item.unit_price + '" data-sku="' + escapeHtml(item.sku) + '" data-product-name="' + escapeHtml(item.product_name) + '" data-variant-name="' + escapeHtml(item.variant_name || '') + '">';
                html += '<td class="fw-700">' + escapeHtml(item.product_name) + '</td>';
                html += '<td class="fs-12 text-muted">' + escapeHtml(item.variant_name || '—') + '</td>';
                html += '<td><code class="fs-11">' + escapeHtml(item.sku) + '</code></td>';
                html += '<td class="text-center fw-700">' + (item.received_qty > 0 ? item.received_qty : item.quantity) + '</td>';
                html += '<td class="text-end fw-600">{{ currency_symbol() }} ' + formatBDT(item.unit_price) + '</td>';
                html += '<td class="text-end"><button type="button" class="bp-btn bp-btn-sm bp-btn-primary add-item-btn"><i class="fa-solid fa-plus me-1"></i> Add</button></td>';
                html += '</tr>';
            });

            if (!hasAvailable) {
                html = '<tr><td colspan="6" class="text-center text-muted py-3">All purchase items are already added</td></tr>';
            }

            $body.html(html);
        }).fail(function () {
            $body.html('<tr><td colspan="6" class="text-center text-muted py-3">Failed to load items</td></tr>');
        });
    });

    // Add item from available list to return items
    $(document).on('click', '.add-item-btn', function () {
        var $row = $(this).closest('tr');
        var productId = $row.data('product-id');
        var variantId = $row.data('variant-id');
        var unitPrice = $row.data('unit-price');
        var sku = $row.data('sku');
        var productName = $row.data('product-name');
        var variantName = $row.data('variant-name');

        // Remove "no items" row if present
        $('#noItemsRow').remove();

        var index = $('#returnItemsBody tr').length;
        var variantInfo = variantName ? '<div class="fs-11 text-muted">' + escapeHtml(variantName) + '</div>' : '';

        var html = '<tr data-unit-price="' + unitPrice + '">';
        html += '<td><input type="checkbox" class="form-check-input item-check" checked></td>';
        html += '<td>';
        html += '<div class="d-flex align-items-center gap-2">';
        html += '<div class="bp-pos-item-img bp-pos-item-img-sm"><i class="fa-solid fa-box"></i></div>';
        html += '<div>';
        html += '<div class="fw-700">' + escapeHtml(productName) + '</div>';
        html += variantInfo;
        html += '</div></div></td>';
        html += '<td><code class="fs-11">' + escapeHtml(sku) + '</code></td>';
        html += '<td>';
        html += '<input type="number" class="bp-form-control bp-return-qty" name="items[' + index + '][quantity]" value="1" min="0" step="any">';
        html += '<input type="hidden" name="items[' + index + '][product_id]" value="' + productId + '">';
        html += '<input type="hidden" name="items[' + index + '][variant_id]" value="' + (variantId || '') + '">';
        html += '<input type="hidden" name="items[' + index + '][unit_price]" value="' + unitPrice + '">';
        html += '</td>';
        html += '<td class="text-end fw-600">{{ currency_symbol() }} ' + formatBDT(unitPrice) + '</td>';
        html += '<td>';
        html += '<select class="bp-form-select bp-form-select-sm" name="items[' + index + '][reason]">';
        html += '<option value="">None</option>';
        html += '<option value="defective">Defective</option>';
        html += '<option value="damaged">Damaged</option>';
        html += '<option value="wrong_item">Wrong Item</option>';
        html += '<option value="quality_issue">Quality Issue</option>';
        html += '<option value="other">Other</option>';
        html += '</select></td>';
        html += '<td class="text-end fw-800 return-item-total">{{ currency_symbol() }} ' + formatBDT(unitPrice) + '</td>';
        html += '</tr>';

        $('#returnItemsBody').append(html);

        // Mark as existing so it can't be added again
        existingItems[productId + '_' + (variantId || 'null')] = true;
        $row.remove();

        recalculate();
    });

    // Form submit — remove unchecked / zero-qty items and re-index
    $('#purchaseReturnForm').on('submit', function () {
        $('#returnItemsBody tr').each(function () {
            var isChecked = $(this).find('.item-check').prop('checked');
            var qty = parseFloat($(this).find('.bp-return-qty').val()) || 0;
            if (!isChecked || qty <= 0) {
                $(this).find('input, select').removeAttr('name');
            }
        });

        var idx = 0;
        $('#returnItemsBody tr').each(function () {
            var isChecked = $(this).find('.item-check').prop('checked');
            var qty = parseFloat($(this).find('.bp-return-qty').val()) || 0;
            if (isChecked && qty > 0) {
                $(this).find('[name]').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + idx + ']'));
                    }
                });
                idx++;
            }
        });
    });

    function recalculate() {
        var totalReturn = 0;
        var itemCount = 0;

        $('#returnItemsBody tr').each(function () {
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
            $(this).find('.return-item-total').text('{{ currency_symbol() }} ' + formatBDT(lineTotal));
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

    // Run initial calculation
    recalculate();
});
</script>
@endpush
