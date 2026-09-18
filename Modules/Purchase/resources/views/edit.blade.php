@extends('core::layouts.master')

@section('title', 'Edit Purchase Order — ' . $purchase->po_number)
@section('page-title', __("Edit Purchase Order"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('purchases.index') }}">Purchases</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->po_number }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit</span>
@endsection

@section('page-actions')
<a href="{{ route('purchases.show', $purchase) }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to PO
</a>
@endsection

@section('content')

@if($purchase->grns->isNotEmpty())
  <div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>This purchase already has received stock. Saving will <strong>reverse the received stock</strong> and set the order back to <strong>Approved</strong> — you'll need to receive the goods again with the updated quantities.</div>
  </div>
@endif

<form action="{{ route('purchases.update', $purchase) }}" method="POST" id="newPurchaseForm">
  @csrf
  @method('PUT')

  <!-- PO Details -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-cart-plus me-2"></i>Purchase Order Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="bp-form-label">Supplier *</label>
          <div class="d-flex gap-2">
            <div class="flex-grow-1">
              <select class="bp-form-select w-100 select2-search bp-select2-supplier" id="poSupplier" name="supplier_id" required>
                <option value="">Select Supplier</option>
                @foreach($suppliers as $supplier)
                  <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchase->supplier_id) == $supplier->id)>{{ $supplier->company_name }}</option>
                @endforeach
              </select>
            </div>
            <button type="button" class="bp-btn bp-btn-outline bp-btn-icon" data-bs-toggle="modal" data-bs-target="#quickAddSupplierModal" title="Add New Supplier">
              <i class="fa-solid fa-plus"></i>
            </button>
          </div>
          @error('supplier_id')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">PO Date</label>
          <input type="date" class="bp-form-control" name="po_date" value="{{ old('po_date', $purchase->po_date->format('Y-m-d')) }}">
          @error('po_date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Expected Delivery</label>
          <input type="date" class="bp-form-control" name="expected_delivery" value="{{ old('expected_delivery', $purchase->expected_delivery?->format('Y-m-d')) }}">
          @error('expected_delivery')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-2 d-none">
          <label class="bp-form-label">Branch{{ count($branches) > 1 ? ' *' : '' }}</label>
          @if(count($branches) <= 1)
            <input type="hidden" name="branch_id" value="{{ $branches->first()?->id }}">
            <input type="text" class="bp-form-control" value="{{ $branches->first()?->name ?? 'N/A' }}" disabled>
          @else
            <select class="bp-form-select w-100" name="branch_id">
              @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $purchase->branch_id) == $branch->id)>{{ $branch->name }}</option>
              @endforeach
            </select>
          @endif
          @error('branch_id')
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
    <!-- Product Search -->
    <div class="bp-card-body pb-0">
      @include('core::partials.product-search', ['id' => 'poProductSearch', 'label' => __('Search Product')])
    </div>

    @php
      $oldItems = old('items');
      if (!$oldItems) {
          $oldItems = $purchase->items->map(fn($item) => [
              'product_id'   => $item->product_id,
              'variant_id'   => $item->variant_id,
              'variant_name' => $item->variant?->variant_name,
              'qty'          => intval($item->quantity),
              'unit_price'   => $item->unit_price,
              'discount'     => $item->discount_amount,
          ])->values()->toArray();
      }
    @endphp

    <div class="bp-card-body p-0 mt-3">
      <div class="bp-table-wrapper">
        <table class="bp-table" id="purchaseItemsTable">
          <thead>
            <tr>
              <th>Product</th>
              <th>Variant</th>
              <th>Qty</th>
              <th>Unit Price</th>
              <th>Discount</th>
              <th>Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="bp-empty-items text-center text-muted py-4" id="poEmptyItems">
        <i class="fa-solid fa-box-open fa-2x mb-2 d-block opacity-50"></i>
        {{ __('Search and add products above') }}
      </div>
    </div>
    <div class="bp-card-footer">
      <a href="{{ route('products.create') }}" target="_blank" class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-box me-1"></i> Create Product</a>
    </div>
  </div>

  <!-- Payment Terms, Shipping, Notes & Summary -->
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2"></i>Additional Details</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="bp-form-label">Payment Terms</label>
              <select class="bp-form-select w-100" name="payment_terms">
                <option value="due_on_receipt" @selected(old('payment_terms', $purchase->payment_terms) == 'due_on_receipt')>Due on Receipt</option>
                <option value="net_7" @selected(old('payment_terms', $purchase->payment_terms) == 'net_7')>Net 7</option>
                <option value="net_15" @selected(old('payment_terms', $purchase->payment_terms) == 'net_15')>Net 15</option>
                <option value="net_30" @selected(old('payment_terms', $purchase->payment_terms) == 'net_30')>Net 30</option>
              </select>
              @error('payment_terms')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Order Discount ({{ currency_symbol() }})</label>
              <input type="number" class="bp-form-control" name="order_discount" id="poOrderDiscount" value="{{ old('order_discount', num_input($purchase->order_discount)) }}" placeholder="0.00" min="0" step="0.01">
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Order VAT / Tax</label>
              <div class="d-flex gap-1">
                <select class="bp-form-select" name="order_tax_mode" id="poOrderTaxMode" style="max-width:90px">
                  <option value="percent" {{ old('order_tax_mode', $purchase->order_tax_mode) === 'percent' ? 'selected' : '' }}>%</option>
                  <option value="flat" {{ old('order_tax_mode', $purchase->order_tax_mode) === 'flat' ? 'selected' : '' }}>Flat</option>
                </select>
                <input type="number" class="bp-form-control {{ ($purchase->order_tax_mode === 'flat') ? 'd-none' : '' }}" name="order_tax_rate" id="poOrderTaxRate" value="{{ old('order_tax_rate', num_input($purchase->order_tax_rate)) }}" placeholder="0" min="0" max="100" step="0.01">
                <input type="number" class="bp-form-control {{ ($purchase->order_tax_mode === 'flat') ? '' : 'd-none' }}" name="order_tax_amount" id="poOrderTaxAmount" value="{{ old('order_tax_amount', num_input($purchase->order_tax_amount)) }}" placeholder="0.00" min="0" step="0.01">
              </div>
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Shipping Cost ({{ currency_symbol() }})</label>
              <input type="number" class="bp-form-control" name="shipping_cost" id="poShippingCost" value="{{ old('shipping_cost', num_input($purchase->shipping_cost)) }}" placeholder="0.00" min="0" step="0.01">
              @error('shipping_cost')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12">
              <label class="bp-form-label">Notes / Instructions</label>
              <textarea class="bp-form-control" name="notes" rows="4" placeholder="Special instructions for supplier...">{{ old('notes', $purchase->notes) }}</textarea>
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
          <div class="bp-cart-summary-row"><span>Subtotal</span><span class="fw-700" id="poSubtotal">{{ currency_symbol() }} 0</span></div>
          <div class="bp-cart-summary-row"><span>Line Discount</span><span id="poDiscount">{{ currency_symbol() }} 0</span></div>
          <div class="bp-cart-summary-row"><span>Order Discount</span><span id="poOrderDiscountView">{{ currency_symbol() }} 0</span></div>
          <div class="bp-cart-summary-row"><span>Tax / VAT</span><span id="poTaxView">{{ currency_symbol() }} 0</span></div>
          <div class="bp-cart-summary-row"><span>Shipping</span><span id="poShipping">{{ currency_symbol() }} 0</span></div>
          <div class="bp-cart-summary-row total"><span>Grand Total</span><span id="poGrandTotal">{{ currency_symbol() }} 0</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment to Supplier -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-money-bill-wave me-2"></i>Payment to Supplier</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3 align-items-start">
        <div class="col-lg-8">
          <div id="poPaymentRows">
            <div class="bp-split-payment-row" data-index="0">
              <div class="bp-pay-amount-col">
                <input type="number" class="bp-form-control po-pay-amount" name="payments[0][amount]" placeholder="Amount" step="0.01" min="0">
              </div>
              <div class="bp-pay-method-col">
                <select class="bp-form-select po-pay-account" name="payments[0][payment_account_id]">
                  <option value="">Select Account</option>
                  @foreach ($paymentAccounts as $pa)
                    <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="bp-pay-ref-col">
                <input type="text" class="bp-form-control" name="payments[0][reference]" placeholder="Ref / TXN ID">
              </div>
              <button type="button" class="remove-split d-none" title="Remove"><i class="fa-solid fa-times"></i></button>
            </div>
          </div>
          <button type="button" class="bp-btn bp-btn-sm bp-btn-outline mt-2" id="poAddPayment"><i class="fa-solid fa-plus me-1"></i> Add Split Payment</button>
          @php
            $poPaymentError = collect($errors->messages())->filter(fn ($m, $k) => str_starts_with($k, 'payments.'))->flatten()->first();
          @endphp
          @if ($poPaymentError)
            <div class="text-danger fs-12 mt-2">{{ $poPaymentError }}</div>
          @endif
          <div class="fs-11 text-muted mt-2">
            <i class="fa-solid fa-circle-info me-1"></i>Record an additional payment to the supplier. Already paid: <span class="fw-700">{{ currency_symbol() }} {{ number_format((float) $purchase->paid_amount, 2) }}</span>.
          </div>
        </div>
        <div class="col-lg-4">
          <div class="bp-advance-summary">
            <div class="bp-adv-row"><span>Paid</span><span class="bp-adv-advance" id="poPaidTotal">{{ currency_symbol() }} 0</span></div>
            <div class="bp-adv-row"><span>Due</span><span id="poDueDisplay">{{ currency_symbol() }} 0</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="d-flex justify-content-end gap-2 mt-3">
    <a href="{{ route('purchases.show', $purchase) }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
    <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Update Purchase Order</button>
  </div>

</form>

@include('purchase::components.variant-picker-modal')
@include('purchase::components.quick-add-supplier-modal')

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // ── Server data: products + their variants ──
    var productData = {};
    @foreach($products as $product)
    productData[{{ $product->id }}] = {
        id: {{ $product->id }},
        name: @json($product->name),
        model: @json($product->model ?? ''),
        sku: @json($product->sku ?? ''),
        barcode: @json($product->barcode ?? ''),
        type: @json($product->product_type),
        cost: {{ (float) $product->cost_price }},
        sell: {{ (float) $product->sell_price }},
        variants: [
            @foreach($product->variants as $variant)
            { id: {{ $variant->id }}, sku: @json($variant->sku), name: @json($variant->variant_name), cost: {{ (float) $variant->effective_cost_price }}, is_active: {{ $variant->is_active ? 'true' : 'false' }} },
            @endforeach
        ]
    };
    @endforeach

    var curr = '{{ currency_symbol() }}';
    var poItemIndex = 0;
    var modalProductId = null;

    var $variantModal = $('#poVariantModal');
    var bsVariantModal = new bootstrap.Modal(document.getElementById('poVariantModal'));

    function esc(s) {
        if (s === null || s === undefined) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s)));
        return d.innerHTML;
    }

    // ── Add a line-item row (simple product or a chosen variant) ──
    function addRow(product, variant, opts) {
        opts = opts || {};
        var variantId = variant ? variant.id : '';

        if (!opts.allowDup) {
            var $existing = $('#purchaseItemsTable tbody tr[data-product-id="' + product.id + '"][data-variant-id="' + (variantId || '') + '"]');
            if ($existing.length) {
                var $q = $existing.find('.bp-po-qty');
                $q.val((parseFloat($q.val()) || 0) + (opts.qty || 1));
                recalculatePO();
                return $existing;
            }
        }

        var idx = poItemIndex++;
        var cost = opts.cost != null ? opts.cost : (variant ? (variant.cost || product.cost) : product.cost);
        var qty = opts.qty != null ? opts.qty : 1;
        var discount = opts.discount != null ? opts.discount : 0;
        var variantName = variant ? variant.name : '—';
        var meta = product.sku ? ('SKU: ' + esc(product.sku)) : (product.model ? esc(product.model) : '');

        var row = '<tr data-product-id="' + product.id + '" data-variant-id="' + (variantId || '') + '">' +
            '<td>' +
              '<div class="fw-600">' + esc(product.name) + '</div>' +
              (meta ? '<div class="fs-11 text-muted">' + meta + '</div>' : '') +
              '<input type="hidden" name="items[' + idx + '][product_id]" value="' + product.id + '">' +
              '<input type="hidden" name="items[' + idx + '][tax_rate]" value="0">' +
            '</td>' +
            '<td>' +
              '<input type="hidden" class="bp-po-variant-id" name="items[' + idx + '][variant_id]" value="' + (variantId || '') + '">' +
              '<span class="bp-po-variant-name ' + (variant ? '' : 'text-muted') + ' fs-13">' + esc(variantName) + '</span>' +
            '</td>' +
            '<td><input type="number" class="bp-form-control bp-input-narrow bp-po-qty" name="items[' + idx + '][qty]" value="' + qty + '" min="1"></td>' +
            '<td><input type="number" class="bp-form-control bp-input-medium bp-po-price" name="items[' + idx + '][unit_price]" value="' + cost + '" min="0" step="0.01"></td>' +
            '<td><input type="number" class="bp-form-control bp-input-narrow bp-po-discount" name="items[' + idx + '][discount]" value="' + discount + '" min="0" step="0.01"></td>' +
            '<td class="fw-700 po-line-total">' + curr + ' 0</td>' +
            '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger btn-remove-po-item"><i class="fa-solid fa-xmark"></i></button></td>' +
            '</tr>';

        $('#purchaseItemsTable tbody').append(row);
        $('#poEmptyItems').hide();
        recalculatePO();
        return $('#purchaseItemsTable tbody tr').last();
    }

    // ── Product search (shared widget) ──
    var catalog = Object.keys(productData).map(function (k) {
        var p = productData[k];
        return { id: p.id, name: p.name, model: p.model, sku: p.sku, barcode: p.barcode, price: p.cost, type: p.type, variants: p.variants };
    });

    window.BpProductSearch.init({
        input: '#poProductSearch',
        catalog: catalog,
        currencySymbol: curr,
        showPrice: true,
        onSelect: function (sel) {
            var p = productData[sel.id];
            if (!p) return;
            if (p.type === 'variable' && p.variants && p.variants.length) {
                openVariantPicker(p);
            } else {
                addRow(p, null);
            }
        }
    });

    // ── Variant modal ──
    function openVariantPicker(product) {
        modalProductId = product.id;
        $('#poVariantModalProduct').text(product.name + (product.model ? ' — ' + product.model : ''));
        renderVariantTable();
        resetAddVariant();
        bsVariantModal.show();
    }

    function renderVariantTable() {
        var p = productData[modalProductId];
        var html = '';
        p.variants.forEach(function (v) {
            var inactive = !v.is_active;
            var dis = inactive ? ' disabled' : '';
            var nameCell = esc(v.name);
            if (inactive) {
                nameCell += ' <span class="bp-badge bp-badge-danger fs-11 ms-1">Inactive</span>' +
                    '<button type="button" class="bp-btn bp-btn-sm bp-btn-success pv-activate ms-2">' +
                    '<i class="fa-solid fa-check me-1"></i>Activate</button>';
            }
            html += '<tr class="' + (inactive ? 'pv-inactive' : '') + '" data-variant-id="' + v.id + '" data-product-id="' + p.id + '">' +
                '<td class="fw-600">' + nameCell + '</td>' +
                '<td class="fs-12 text-muted">' + esc(v.sku || '') + '</td>' +
                '<td><input type="number" class="bp-form-control bp-input-narrow pv-qty" value="0" min="0" step="1"' + dis + '></td>' +
                '<td><input type="number" class="bp-form-control bp-input-medium pv-cost" value="' + (v.cost || p.cost || 0) + '" min="0" step="0.01"' + dis + '></td>' +
                '</tr>';
        });
        $('#poVariantRows').html(html);
    }

    // Activate an inactive variant inline (reuses the product variant-active endpoint).
    $('#poVariantRows').on('click', '.pv-activate', function () {
        var $btn = $(this);
        var $tr = $btn.closest('tr');
        var vid = parseInt($tr.attr('data-variant-id'), 10);
        var pid = parseInt($tr.attr('data-product-id'), 10);
        $btn.prop('disabled', true);
        $.ajax({
            url: '{{ route('products.variant-active', [':pid', ':vid']) }}'.replace(':pid', pid).replace(':vid', vid),
            method: 'PATCH',
            data: { is_active: 1 },
            success: function () {
                var prod = productData[pid];
                var v = prod && prod.variants.find(function (x) { return x.id === vid; });
                if (v) v.is_active = true;
                $tr.removeClass('pv-inactive');
                $tr.find('.bp-badge, .pv-activate').remove();
                $tr.find('.pv-qty, .pv-cost').prop('disabled', false);
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#poVariantError').removeClass('d-none').text('Could not activate the variant. Please try again.');
            }
        });
    });

    $('#poVariantConfirm').on('click', function () {
        var p = productData[modalProductId];
        if (!p) return;
        var chosen = [];
        $('#poVariantRows tr').each(function () {
            var qty = parseInt($(this).find('.pv-qty').val(), 10) || 0;
            if (qty > 0) {
                var vid = parseInt($(this).attr('data-variant-id'), 10);
                var variant = p.variants.find(function (x) { return x.id === vid; });
                chosen.push({ variant: variant, qty: qty, cost: parseFloat($(this).find('.pv-cost').val()) || 0 });
            }
        });
        if (!chosen.length) {
            $('#poVariantError').removeClass('d-none').text('Enter a quantity for at least one variant.');
            return;
        }
        chosen.forEach(function (c) {
            addRow(p, c.variant, { qty: c.qty, cost: c.cost });
        });
        bsVariantModal.hide();
        recalculatePO();
    });

    // ── Reset the in-modal error when the picker opens ──
    function resetAddVariant() {
        $('#poVariantError').addClass('d-none').text('');
    }

    // Variant modal dismissed — clear the active product reference.
    $variantModal.on('hidden.bs.modal', function () {
        modalProductId = null;
    });

    // ── Load existing PO items (or old input after a validation error) ──
    var poRestoreItems = @json($oldItems);
    (poRestoreItems || []).forEach(function (it) {
        var p = productData[it.product_id];
        if (!p) return;
        var variant = null;
        if (it.variant_id) {
            variant = (p.variants || []).find(function (v) { return String(v.id) === String(it.variant_id); })
                || { id: it.variant_id, name: it.variant_name || ('#' + it.variant_id), cost: parseFloat(it.unit_price) || p.cost };
        }
        addRow(p, variant, {
            qty: it.qty != null ? parseFloat(it.qty) : 1,
            cost: it.unit_price != null ? parseFloat(it.unit_price) : (variant ? (variant.cost || p.cost) : p.cost),
            discount: it.discount != null ? parseFloat(it.discount) : 0,
            allowDup: true
        });
    });
    if (!$('#purchaseItemsTable tbody tr').length) $('#poEmptyItems').show();

    // ── Remove rows ──
    $(document).on('click', '.btn-remove-po-item', function () {
        $(this).closest('tr').remove();
        if (!$('#purchaseItemsTable tbody tr').length) $('#poEmptyItems').show();
        recalculatePO();
    });

    $(document).on('input', '.bp-po-qty, .bp-po-price, .bp-po-discount, #poShippingCost', function () {
        recalculatePO();
    });

    // Require at least one item before submitting.
    $('#newPurchaseForm').on('submit', function (e) {
        if (!$('#purchaseItemsTable tbody tr').length) {
            e.preventDefault();
            alert('{{ __("Please add at least one product. Search a product name or code above to add it.") }}');
            $('#poProductSearch').trigger('focus');
        }
    });

    function recalculatePO() {
        var subtotal = 0, totalDiscount = 0;
        $('#purchaseItemsTable tbody tr').each(function () {
            var qty = parseFloat($(this).find('.bp-po-qty').val()) || 0;
            var price = parseFloat($(this).find('.bp-po-price').val()) || 0;
            var discount = parseFloat($(this).find('.bp-po-discount').val()) || 0;
            var lineTotal = (qty * price) - discount;
            subtotal += (qty * price);
            totalDiscount += discount;
            $(this).find('.po-line-total').text('{{ currency_symbol() }} ' + formatBDT(lineTotal));
        });
        var shipping = parseFloat($('#poShippingCost').val()) || 0;
        var orderDiscount = parseFloat($('#poOrderDiscount').val()) || 0;
        var taxable = Math.max(0, subtotal - totalDiscount - orderDiscount);
        var orderTax = $('#poOrderTaxMode').val() === 'flat'
            ? (parseFloat($('#poOrderTaxAmount').val()) || 0)
            : taxable * (parseFloat($('#poOrderTaxRate').val()) || 0) / 100;
        var grandTotal = (subtotal - totalDiscount) - orderDiscount + orderTax + shipping;
        $('#poSubtotal').text('{{ currency_symbol() }} ' + formatBDT(subtotal));
        $('#poDiscount').text('{{ currency_symbol() }} ' + formatBDT(totalDiscount));
        $('#poOrderDiscountView').text('{{ currency_symbol() }} ' + formatBDT(orderDiscount));
        $('#poTaxView').text('{{ currency_symbol() }} ' + formatBDT(orderTax));
        $('#poShipping').text('{{ currency_symbol() }} ' + formatBDT(shipping));
        $('#poGrandTotal').text('{{ currency_symbol() }} ' + formatBDT(grandTotal));
        poGrandTotal = grandTotal;
        recalcPayments();
    }

    $(document).on('input change', '#poOrderDiscount, #poOrderTaxRate, #poOrderTaxAmount', recalculatePO);
    $(document).on('change', '#poOrderTaxMode', function () {
        var flat = $(this).val() === 'flat';
        $('#poOrderTaxAmount').toggleClass('d-none', !flat);
        $('#poOrderTaxRate').toggleClass('d-none', flat);
        recalculatePO();
    });

    // ── Supplier payments (split) ──
    var poGrandTotal = 0;
    var poPayIndex = 1;
    var poAlreadyPaid = {{ (float) $purchase->paid_amount }};
    var poPayAccountOptions = $('#poPaymentRows .po-pay-account').first().html();

    function recalcPayments() {
        var newPaid = 0;
        $('#poPaymentRows .po-pay-amount').each(function () {
            newPaid += parseFloat($(this).val()) || 0;
        });
        var paid = poAlreadyPaid + newPaid;
        var due = Math.max(0, poGrandTotal - paid);
        $('#poPaidTotal').text('{{ currency_symbol() }} ' + formatBDT(paid));
        $('#poDueDisplay').text('{{ currency_symbol() }} ' + formatBDT(due));
    }

    $('#poAddPayment').on('click', function () {
        var idx = poPayIndex++;
        var newPaid = 0;
        $('#poPaymentRows .po-pay-amount').each(function () {
            newPaid += parseFloat($(this).val()) || 0;
        });
        var remaining = Math.max(0, poGrandTotal - poAlreadyPaid - newPaid);
        var html = '<div class="bp-split-payment-row" data-index="' + idx + '">' +
            '<div class="bp-pay-amount-col"><input type="number" class="bp-form-control po-pay-amount" name="payments[' + idx + '][amount]" placeholder="Amount" step="0.01" min="0" value="' + (remaining > 0 ? remaining : '') + '"></div>' +
            '<div class="bp-pay-method-col"><select class="bp-form-select po-pay-account" name="payments[' + idx + '][payment_account_id]">' + poPayAccountOptions + '</select></div>' +
            '<div class="bp-pay-ref-col"><input type="text" class="bp-form-control" name="payments[' + idx + '][reference]" placeholder="Ref / TXN ID"></div>' +
            '<button type="button" class="remove-split" title="Remove"><i class="fa-solid fa-times"></i></button>' +
            '</div>';
        $('#poPaymentRows').append(html);
        $('#poPaymentRows .remove-split').removeClass('d-none');
        recalcPayments();
    });

    $(document).on('click', '#poPaymentRows .remove-split', function () {
        $(this).closest('.bp-split-payment-row').remove();
        var $rows = $('#poPaymentRows .bp-split-payment-row');
        if ($rows.length === 1) $rows.find('.remove-split').addClass('d-none');
        recalcPayments();
    });

    $(document).on('input', '.po-pay-amount', function () {
        recalcPayments();
    });

    function formatBDT(num) {
        num = Math.round(num);
        var str = num.toString();
        var lastThree = str.substring(str.length - 3);
        var otherNumbers = str.substring(0, str.length - 3);
        if (otherNumbers !== '') { lastThree = ',' + lastThree; }
        return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
    }

    recalculatePO();

    // ── Quick add supplier ──
    $('#saveNewSupplier').on('click', function () {
        var $btn = $(this);
        var company = $('#newSupplierCompany').val().trim();
        var phone = $('#newSupplierPhone').val().trim();
        if (!company || !phone) {
            if (!company) $('#newSupplierCompany').addClass('is-invalid');
            if (!phone) $('#newSupplierPhone').addClass('is-invalid');
            return;
        }
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');
        $.post('{{ route("purchases.quick-add-supplier") }}', {
            company_name: company,
            contact_person: $('#newSupplierContact').val().trim() || null,
            phone: phone,
            email: $('#newSupplierEmail').val().trim() || null,
            address: $('#newSupplierAddress').val().trim() || null
        }, function (data) {
            var $select = $('#poSupplier');
            $select.append('<option value="' + data.supplier.id + '">' + data.supplier.company_name + '</option>');
            $select.val(data.supplier.id).trigger('change');
            $('#newSupplierCompany, #newSupplierContact, #newSupplierPhone, #newSupplierEmail, #newSupplierAddress').val('').removeClass('is-invalid');
            $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Save & Select');
            bootstrap.Modal.getInstance('#quickAddSupplierModal').hide();
        }).fail(function (xhr) {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Save & Select');
            alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to add supplier');
        });
    });

    $(document).on('input', '#newSupplierCompany, #newSupplierPhone', function () {
        $(this).removeClass('is-invalid');
    });
});
</script>
@endpush
