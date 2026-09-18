@extends('core::layouts.master')

@section('title', __('New Stock Adjustment'))
@section('page-title', __('New Stock Adjustment'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('inventory.index') }}">Inventory</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('inventory.adjustments') }}">Adjustments</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>New Adjustment</span>
@endsection

@section('page-actions')
    <a href="{{ route('inventory.adjustments') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Adjustments
    </a>
@endsection

@section('content')

    <form action="{{ route('inventory.adjustments.store') }}" method="POST" id="newAdjustmentForm">
        @csrf

        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-sliders me-2"></i>Adjustment Details</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Adjustment Type *</label>
                        <select class="bp-form-select w-100 @error('type') is-invalid @enderror" name="type" required>
                            <option value="">Select Type</option>
                            <option value="addition" {{ old('type') === 'addition' ? 'selected' : '' }}>Addition (+ stock)
                            </option>
                            <option value="subtraction" {{ old('type') === 'subtraction' ? 'selected' : '' }}>Subtraction (−
                                stock)</option>
                        </select>
                        @error('type')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Reason *</label>
                        <select class="bp-form-select w-100 @error('reason_id') is-invalid @enderror" name="reason_id"
                            required>
                            <option value="">Select Reason</option>
                            @foreach ($adjustmentReasons as $adjReason)
                                <option value="{{ $adjReason->id }}"
                                    {{ old('reason_id') == $adjReason->id ? 'selected' : '' }}>{{ $adjReason->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('reason_id')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Reference</label>
                        <input type="text" class="bp-form-control" name="reference"
                            placeholder="Optional reference number" value="{{ old('reference') }}">
                        @error('reference')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Notes</label>
                        <textarea class="bp-form-control" name="notes" rows="3" placeholder="Optional notes about this adjustment...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjustment Items -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Adjustment Items</h5>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="addItemRow">
                    <i class="fa-solid fa-plus me-1"></i> Add Item
                </button>
            </div>
            <div class="bp-card-body p-0">
                <div class="bp-table-wrapper">
                    <table class="bp-table bp-adjust-items" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Product *</th>
                                <th>Variant</th>
                                <th>Quantity *</th>
                                <th>Unit Cost</th>
                                <th>Note</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @if (old('items'))
                                @foreach (old('items') as $i => $oldItem)
                                    <tr class="item-row">
                                        <td>
                                            <select class="bp-form-select w-100 select2-search bp-select2-product"
                                                name="items[{{ $i }}][product_id]" required>
                                                <option value="">Select Product</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}"
                                                        data-type="{{ $product->product_type }}"
                                                        data-cost="{{ $product->cost_price }}"
                                                        {{ ($oldItem['product_id'] ?? '') == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('items.' . $i . '.product_id')
                                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <select class="bp-form-select w-100 select2-search bp-select2-variant"
                                                name="items[{{ $i }}][variant_id]"
                                                data-old-variant="{{ $oldItem['variant_id'] ?? '' }}" disabled>
                                                <option value="">N/A</option>
                                            </select>
                                            @error('items.' . $i . '.variant_id')
                                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" class="bp-form-control"
                                                name="items[{{ $i }}][quantity]" min="1" required
                                                placeholder="Qty" value="{{ $oldItem['quantity'] ?? '' }}">
                                            @error('items.' . $i . '.quantity')
                                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" class="bp-form-control"
                                                name="items[{{ $i }}][unit_cost]" min="0" step="0.01"
                                                placeholder="Cost" value="{{ $oldItem['unit_cost'] ?? '' }}">
                                            @error('items.' . $i . '.unit_cost')
                                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" class="bp-form-control"
                                                name="items[{{ $i }}][note]" placeholder="Optional note"
                                                value="{{ $oldItem['note'] ?? '' }}">
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-item-row"><i
                                                    class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="item-row">
                                    <td>
                                        <select class="bp-form-select w-100 select2-search bp-select2-product"
                                            name="items[0][product_id]" required>
                                            <option value="">Select Product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}"
                                                    data-type="{{ $product->product_type }}"
                                                    data-cost="{{ $product->cost_price }}"
                                                    {{ ($preselectProduct ?? null) == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select class="bp-form-select w-100 select2-search bp-select2-variant"
                                            name="items[0][variant_id]" data-old-variant="{{ $preselectVariant ?? '' }}"
                                            disabled>
                                            <option value="">N/A</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="bp-form-control" name="items[0][quantity]"
                                            min="1" required placeholder="Qty">
                                    </td>
                                    <td>
                                        <input type="number" class="bp-form-control" name="items[0][unit_cost]"
                                            min="0" step="0.01" placeholder="Cost">
                                    </td>
                                    <td>
                                        <input type="text" class="bp-form-control" name="items[0][note]"
                                            placeholder="Optional note">
                                    </td>
                                    <td>
                                        <button type="button"
                                            class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-item-row"><i
                                                class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @error('items')
                <div class="bp-card-body pt-0">
                    <div class="text-danger fs-12">{{ $message }}</div>
                </div>
            @enderror
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('inventory.adjustments') }}" class="bp-btn bp-btn-danger"><i
                    class="fa-solid fa-xmark me-1"></i>Cancel</a>
            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save
                Adjustment</button>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            var itemIndex = {{ old('items') ? count(old('items')) : 1 }};
            // Select2 inits via global helper (select2-search class). For
            // dynamic option swaps (variant) and inserted rows, call
            // window.bpInitSelect2(<scope>) after the DOM mutation.

            // Pre-load variant data keyed by product id
            var productVariantData = {};
            @foreach ($products as $product)
                @if ($product->product_type === 'variable' && $product->variants->isNotEmpty())
                    productVariantData[{{ $product->id }}] = {
                        type: 'variable',
                        variants: [
                            @foreach ($product->variants as $variant)
                                {
                                    id: {{ $variant->id }},
                                    name: '{{ e($variant->variant_name) }}',
                                    cost: {{ $variant->effective_cost_price }}
                                },
                            @endforeach
                        ]
                    };
                @endif
            @endforeach

            var productOptions = '<option value="">Select Product</option>'
            @foreach ($products as $product)
                +
                '<option value="{{ $product->id }}" data-type="{{ $product->product_type }}" data-cost="{{ $product->cost_price }}">{{ addslashes($product->name) }}</option>'
            @endforeach ;

            function handleProductChange($select) {
                var $row = $select.closest('tr');
                var productId = $select.val();
                var $option = $select.find(':selected');
                var $variantSelect = $row.find('.bp-select2-variant');
                var $costField = $row.find('input[name$="[unit_cost]"]');
                var data = productVariantData[productId];

                // Destroy existing variant Select2
                if ($variantSelect.hasClass('select2-hidden-accessible')) {
                    $variantSelect.select2('destroy');
                }

                if (data && data.type === 'variable' && data.variants.length) {
                    // Variable product — populate variant dropdown
                    var html = '<option value="">Select variant...</option>';
                    $.each(data.variants, function(i, v) {
                        html += '<option value="' + v.id + '" data-cost="' + v.cost + '">' + v.name +
                            '</option>';
                    });
                    $variantSelect.html(html).prop('disabled', false);
                    window.bpInitSelect2($variantSelect);
                    $costField.val('');
                } else {
                    // Simple product or no selection
                    $variantSelect.html('<option value="">N/A</option>').prop('disabled', true);
                    window.bpInitSelect2($variantSelect);
                    if (productId) {
                        var cost = $option.data('cost');
                        $costField.val(cost ? parseFloat(cost).toFixed(2) : '');
                    } else {
                        $costField.val('');
                    }
                }
            }

            // Handle product change
            $(document).on('change', '.bp-select2-product', function() {
                handleProductChange($(this));
            });

            // Handle variant change — fill unit cost from variant
            $(document).on('change', '.bp-select2-variant', function() {
                var cost = $(this).find(':selected').data('cost');
                var $costField = $(this).closest('tr').find('input[name$="[unit_cost]"]');
                if (cost) {
                    $costField.val(parseFloat(cost).toFixed(2));
                }
            });

            // Restore variant dropdowns on validation failure (old input)
            @if (old('items') || ($preselectProduct ?? null))
                $('.item-row').each(function() {
                    var $row = $(this);
                    var $productSelect = $row.find('.bp-select2-product');
                    var $variantSelect = $row.find('.bp-select2-variant');
                    var oldVariant = $variantSelect.data('old-variant');

                    if ($productSelect.val()) {
                        handleProductChange($productSelect);
                        if (oldVariant) {
                            $variantSelect.val(oldVariant).trigger('change');
                        }
                    }
                });
            @endif

            // Add new item row
            $('#addItemRow').on('click', function() {
                var row = '<tr class="item-row">' +
                    '<td><select class="bp-form-select w-100 select2-search bp-select2-product" name="items[' +
                    itemIndex + '][product_id]" required>' + productOptions + '</select></td>' +
                    '<td><select class="bp-form-select w-100 select2-search bp-select2-variant" name="items[' +
                    itemIndex + '][variant_id]" disabled><option value="">N/A</option></select></td>' +
                    '<td><input type="number" class="bp-form-control" name="items[' + itemIndex +
                    '][quantity]" min="1" required placeholder="Qty"></td>' +
                    '<td><input type="number" class="bp-form-control" name="items[' + itemIndex +
                    '][unit_cost]" min="0" step="0.01" placeholder="Cost"></td>' +
                    '<td><input type="text" class="bp-form-control" name="items[' + itemIndex +
                    '][note]" placeholder="Optional note"></td>' +
                    '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-item-row"><i class="fa-solid fa-trash"></i></button></td>' +
                    '</tr>';

                var $row = $(row);
                $('#itemsBody').append($row);
                window.bpInitSelect2($row);
                itemIndex++;
            });

            // Remove item row
            $(document).on('click', '.remove-item-row', function() {
                if ($('#itemsBody .item-row').length > 1) {
                    var $row = $(this).closest('tr');
                    if ($row.find('.bp-select2-product').hasClass('select2-hidden-accessible')) {
                        $row.find('.bp-select2-product').select2('destroy');
                    }
                    if ($row.find('.bp-select2-variant').hasClass('select2-hidden-accessible')) {
                        $row.find('.bp-select2-variant').select2('destroy');
                    }
                    $row.remove();
                }
            });
        });
    </script>
@endpush
