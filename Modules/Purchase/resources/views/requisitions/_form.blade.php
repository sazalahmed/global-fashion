{{-- Shared requisition form. $requisition may be null (create).
     Product + variant selection mirrors the Purchase create page:
     shared search widget → variant picker modal for variable products. --}}
<div class="row g-4">
  <div class="col-lg-8">
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Requested Items</h5>
      </div>

      {{-- Product search (shared widget, same as Purchase create) --}}
      <div class="bp-card-body pb-0">
        @include('core::partials.product-search', [
            'id' => 'reqProductSearch',
            'label' => __('Search Product'),
        ])
      </div>

      <div class="bp-card-body p-0 mt-3">
        <div class="bp-table-wrapper">
          <table class="bp-table" id="reqItemsTable">
            <thead>
              <tr><th>Product</th><th>Variant</th><th style="width:140px">Quantity</th><th>Note</th><th style="width:50px"></th></tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="bp-empty-items text-center text-muted py-4" id="reqEmptyItems">
          <i class="fa-solid fa-box-open fa-2x mb-2 d-block opacity-50"></i>
          {{ __('Search and add products above') }}
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="bp-card mb-4">
      <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Details</h5></div>
      <div class="bp-card-body">
        <div class="mb-3">
          <label class="bp-form-label">Department</label>
          <input type="text" class="bp-form-control" name="department" value="{{ old('department', $requisition->department ?? '') }}" placeholder="e.g. Sales / Warehouse">
        </div>
        <div class="mb-3">
          <label class="bp-form-label">Required Date</label>
          <input type="date" class="bp-form-control" name="required_date" value="{{ old('required_date', optional($requisition->required_date ?? null)->format('Y-m-d')) }}">
        </div>
        <div class="mb-3">
          <label class="bp-form-label">Priority</label>
          <select class="bp-form-select w-100" name="priority">
            @foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $val => $lbl)
              <option value="{{ $val }}" {{ old('priority', $requisition->priority ?? 'normal') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="bp-form-label">Note</label>
          <textarea class="bp-form-control" name="note" rows="3" placeholder="Reason / details...">{{ old('note', $requisition->note ?? '') }}</textarea>
        </div>
      </div>
      <div class="bp-card-footer text-end">
        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> {{ isset($requisition) ? 'Update' : 'Submit' }} Requisition</button>
      </div>
    </div>
  </div>
</div>

@include('purchase::components.variant-picker-modal', [
    'showCost'     => false,
    'confirmLabel' => __('Add to Requisition'),
])

@push('scripts')
<script>
'use strict';
$(function () {
    // ── Server data: products + their variants (same shape as purchase create) ──
    var productData = {};
    @foreach ($products as $product)
        productData[{{ $product->id }}] = {
            id: {{ $product->id }},
            name: @json($product->name),
            model: @json($product->model ?? ''),
            sku: @json($product->sku ?? ''),
            barcode: @json($product->barcode ?? ''),
            type: @json($product->product_type),
            variants: [
                @foreach ($product->variants as $variant)
                    {
                        id: {{ $variant->id }},
                        sku: @json($variant->sku),
                        name: @json($variant->variant_name),
                        is_active: {{ $variant->is_active ? 'true' : 'false' }}
                    },
                @endforeach
            ]
        };
    @endforeach

    var reqItemIndex = 0;
    var modalProductId = null; // product being configured in the variant modal
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

        // Same product + variant already present → bump quantity instead of duplicating.
        if (!opts.allowDup) {
            var $existing = $('#reqItemsTable tbody tr[data-product-id="' + product.id +
                '"][data-variant-id="' + (variantId || '') + '"]');
            if ($existing.length) {
                var $q = $existing.find('.bp-req-qty');
                $q.val((parseFloat($q.val()) || 0) + (opts.qty || 1));
                return $existing;
            }
        }

        var idx = reqItemIndex++;
        var qty = opts.qty != null ? opts.qty : 1;
        var variantName = variant ? variant.name : '—';
        var meta = product.sku ? ('SKU: ' + esc(product.sku)) : (product.model ? esc(product.model) : '');

        var row = '<tr data-product-id="' + product.id + '" data-variant-id="' + (variantId || '') + '">' +
            '<td>' +
            '<div class="fw-600">' + esc(product.name) + '</div>' +
            (meta ? '<div class="fs-11 text-muted">' + meta + '</div>' : '') +
            '<input type="hidden" name="items[' + idx + '][product_id]" value="' + product.id + '">' +
            '</td>' +
            '<td>' +
            '<input type="hidden" name="items[' + idx + '][variant_id]" value="' + (variantId || '') + '">' +
            '<span class="' + (variant ? '' : 'text-muted') + ' fs-13">' + esc(variantName) + '</span>' +
            '</td>' +
            '<td><input type="number" class="bp-form-control bp-form-control-sm bp-req-qty" name="items[' + idx + '][quantity]" value="' + qty + '" min="0.01" step="0.01" required></td>' +
            '<td><input type="text" class="bp-form-control bp-form-control-sm" name="items[' + idx + '][note]" value="' + esc(opts.note || '') + '" placeholder="Optional"></td>' +
            '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-danger req-remove"><i class="fa-solid fa-times"></i></button></td>' +
            '</tr>';

        $('#reqItemsTable tbody').append(row);
        $('#reqEmptyItems').hide();
        return $('#reqItemsTable tbody tr').last();
    }

    // ── Product search (shared widget) ──
    var catalog = Object.keys(productData).map(function (k) {
        var p = productData[k];
        return {
            id: p.id,
            name: p.name,
            model: p.model,
            sku: p.sku,
            barcode: p.barcode,
            type: p.type,
            variants: p.variants
        };
    });

    window.BpProductSearch.init({
        input: '#reqProductSearch',
        catalog: catalog,
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

    // ── Variant modal (same flow as purchase create, minus cost column) ──
    function openVariantPicker(product) {
        modalProductId = product.id;
        $('#poVariantModalProduct').text(product.name + (product.model ? ' — ' + product.model : ''));
        renderVariantTable();
        $('#poVariantError').addClass('d-none');
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
            html += '<tr class="' + (inactive ? 'pv-inactive' : '') + '" data-variant-id="' + v.id +
                '" data-product-id="' + p.id + '">' +
                '<td class="fw-600">' + nameCell + '</td>' +
                '<td class="fs-12 text-muted">' + esc(v.sku || '') + '</td>' +
                '<td><input type="number" class="bp-form-control bp-input-narrow pv-qty" value="0" min="0" step="1"' + dis + '></td>' +
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
                $tr.find('.pv-qty').prop('disabled', false);
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
            var qty = parseFloat($(this).find('.pv-qty').val()) || 0;
            if (qty > 0) {
                var vid = parseInt($(this).attr('data-variant-id'), 10);
                var variant = p.variants.find(function (x) { return x.id === vid; });
                chosen.push({ variant: variant, qty: qty });
            }
        });
        if (!chosen.length) {
            $('#poVariantError').removeClass('d-none').text('Enter a quantity for at least one variant.');
            return;
        }

        chosen.forEach(function (c) { addRow(p, c.variant, { qty: c.qty }); });
        bsVariantModal.hide();
    });

    $(document).on('click', '.req-remove', function () {
        $(this).closest('tr').remove();
        if (!$('#reqItemsTable tbody tr').length) $('#reqEmptyItems').show();
    });

    // Prefill existing items on edit — resolve the variant from productData,
    // same as the purchase create requisition-conversion prefill.
    @isset($requisition)
        @php
            $existingItems = ($requisition->items ?? collect())->map(fn ($i) => [
                'product_id' => $i->product_id,
                'variant_id' => $i->variant_id,
                'qty'        => (float) $i->quantity,
                'note'       => $i->note,
            ])->values();
        @endphp
        var existing = @json($existingItems);
        existing.forEach(function (it) {
            var p = productData[it.product_id];
            if (!p) return;
            var variant = null;
            if (it.variant_id && p.variants) {
                variant = p.variants.filter(function (v) { return v.id == it.variant_id; })[0] || null;
            }
            addRow(p, variant, { qty: it.qty, note: it.note, allowDup: true });
        });
    @endisset

    // Block submit with no items.
    $('form').on('submit', function (e) {
        if (!$('#reqItemsTable tbody tr').length) {
            e.preventDefault();
            alert('{{ __("Please add at least one product.") }}');
        }
    });
});
</script>
@endpush
