@extends('core::layouts.master')

@section('title', 'Edit Quotation — ' . $quotation->quotation_number)
@section('page-title', __('Edit Quotation'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('quotations.index') }}">Quotations</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('quotations.show', $quotation) }}">{{ $quotation->quotation_number }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit</span>
@endsection

@section('page-actions')
    <a href="{{ route('quotations.show', $quotation) }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Quotation
    </a>
@endsection

@php
    $statusMap = [
        'draft' => 'bp-badge-dark',
        'pending' => 'bp-badge-warning',
        'accepted' => 'bp-badge-success',
        'rejected' => 'bp-badge-danger',
        'expired' => 'bp-badge-danger',
        'converted' => 'bp-badge-info',
    ];
    $badgeClass = $statusMap[$quotation->status] ?? 'bp-badge-dark';
@endphp

@push('styles')
    <link href="{{ asset('vendor/venobox/venobox.min.css') }}" rel="stylesheet">
@endpush

@section('content')

    <form action="{{ route('quotations.update', $quotation) }}" method="POST" id="quotationForm">
        @csrf
        @method('PUT')

        <!-- Quotation Details -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2"></i>Quotation Details</h5>
                <span class="bp-badge {{ $badgeClass }}">{{ ucfirst($quotation->status) }}</span>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label d-flex justify-content-between align-items-center">
                            <span>Customer *</span>
                            <a href="#" id="btnNewCustomer" class="fs-12 text-primary fw-600 text-decoration-none"
                                title="{{ __('Create new customer') }}">
                                <i class="fa-solid fa-plus me-1"></i>{{ __('New Customer') }}
                            </a>
                        </label>
                        <x-core::select2 name="customer_id" placeholder="Select Customer" required>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" data-due="{{ $previousDueMap[$customer->id] ?? 0 }}"
                                    data-billing="{{ $customer->address ?? '' }}"
                                    data-shipping="{{ $customer->shipping_address ?? '' }}" @selected(old('customer_id', $quotation->customer_id) == $customer->id)>
                                    {{ $customer->name }}
                                    ({{ $customer->phone }})
                                </option>
                            @endforeach
                        </x-core::select2>
                        @error('customer_id')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="bp-form-label">Quotation Date</label>
                        <input type="date" class="bp-form-control" name="quotation_date"
                            value="{{ old('quotation_date', $quotation->quotation_date->format('Y-m-d')) }}">
                        @error('quotation_date')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="bp-form-label">Valid Until *</label>
                        <input type="date" class="bp-form-control" name="valid_until"
                            value="{{ old('valid_until', $quotation->valid_until->format('Y-m-d')) }}" required>
                        @error('valid_until')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="bp-form-label">Reference</label>
                        <input type="text" class="bp-form-control" name="reference"
                            value="{{ old('reference', $quotation->reference) }}">
                        @error('reference')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="bp-form-label">Price Type</label>
                        <select class="bp-form-select w-100" name="price_type" id="quotationPriceType">
                            <option value="regular" {{ old('price_type', 'regular') === 'regular' ? 'selected' : '' }}>
                                Regular Price</option>
                            <option value="wholesale" {{ old('price_type') === 'wholesale' ? 'selected' : '' }}>Wholesale
                                Price</option>
                            <option value="resell" {{ old('price_type') === 'resell' ? 'selected' : '' }}>Reseller
                                Price</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-none">
                        <label class="bp-form-label">Branch</label>
                        <select class="bp-form-select w-100" name="branch_id">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ old('branch_id', $quotation->branch_id) == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Address quick view (read-only cards) + Edit toggle --}}
                    <x-core::address-quickview />

                    {{-- Editable address fields — revealed by the Edit button --}}
                    <div class="col-12 d-none" id="addrEditFields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">{{ __('Billing Address') }}</label>
                                <textarea class="bp-form-control" name="billing_address" id="billingAddress" rows="3"
                                    placeholder="{{ __('Billing address') }}">{{ old('billing_address', $quotation->billing_address) }}</textarea>
                                @error('billing_address')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">{{ __('Shipping Address') }}</label>
                                <textarea class="bp-form-control" name="shipping_address" id="shippingAddress" rows="3"
                                    placeholder="{{ __('Delivery address') }}">{{ old('shipping_address', $quotation->shipping_address) }}</textarea>
                                @error('shipping_address')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Search & Line Items -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Line Items</h5>
            </div>

            <!-- Product Search -->
            <div class="bp-card-body pb-0">
                @include('core::partials.product-search', [
                    'id' => 'productSearch',
                    'label' => __('Search Product'),
                ])
            </div>

            <div class="bp-card-body p-0 mt-3">
                <div class="bp-table-wrapper">
                    <table class="bp-table bp-has-variant-col" id="lineItemsTable">
                        <thead>
                            <tr>
                                <th class="bp-col-img">Image</th>
                                <th class="bp-col-product">Product</th>
                                <th class="bp-col-variants">Variant Details</th>
                                <th>Total Quantity</th>
                                <th>Discount</th>
                                <th>Subtotal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quotation->items as $index => $item)
                                @php
                                    // Prefer the persisted per-variant breakdown (name/qty/price).
                                    // Fall back to the legacy variant names kept in custom_note for
                                    // older items saved before the breakdown column existed.
                                    $breakdown = is_array($item->variant_breakdown) ? $item->variant_breakdown : [];
                                    $variantNote = trim(
                                        (string) old('items.' . $index . '.custom_note', $item->custom_note),
                                    );
                                    $variantParts =
                                        !count($breakdown) && $variantNote !== ''
                                            ? array_filter(array_map('trim', explode(',', $variantNote)))
                                            : [];
                                    $hasVariantInfo = count($breakdown) || count($variantParts);
                                @endphp
                                <tr data-product-id="{{ $item->product_id }}" data-variant-id="{{ $item->variant_id }}"
                                    @if (count($breakdown)) data-breakdown="{{ json_encode($breakdown) }}" @endif>
                                    <td class="bp-col-img">
                                        @php $itemImg = $item->product?->display_image; @endphp
                                        @if ($itemImg)
                                            <a class="venobox" data-gall="qline" href="{{ upload_url($itemImg) }}"><img
                                                    src="{{ upload_url($itemImg) }}" class="bp-line-item-img"
                                                    alt=""></a>
                                        @else
                                            <div class="bp-line-item-img bp-line-item-img-ph"><i
                                                    class="fa-solid fa-box"></i></div>
                                        @endif
                                    </td>
                                    <td class="bp-col-product">
                                        <div class="fw-600">{{ $item->product_name }}</div>
                                        @if ($hasVariantInfo)
                                            <input type="hidden" class="bp-line-item-note"
                                                name="items[{{ $index }}][custom_note]"
                                                value="{{ $variantNote }}">
                                            @if (count($breakdown))
                                                <input type="hidden"
                                                    name="items[{{ $index }}][variant_breakdown]"
                                                    value="{{ json_encode($breakdown) }}">
                                            @endif
                                        @else
                                            <input type="text" class="bp-form-control fs-12 mt-1 bp-line-item-note"
                                                name="items[{{ $index }}][custom_note]"
                                                value="{{ old('items.' . $index . '.custom_note', $item->custom_note) }}"
                                                placeholder="{{ __('Custom note (optional)') }}" maxlength="500">
                                        @endif
                                        <input type="hidden" name="items[{{ $index }}][product_id]"
                                            value="{{ $item->product_id }}">
                                        <input type="hidden" name="items[{{ $index }}][variant_id]"
                                            value="{{ $item->variant_id }}">
                                    </td>
                                    <td class="bp-col-variants">
                                        @if (count($breakdown))
                                            <div class="bp-qline-variants">
                                                @foreach ($breakdown as $b)
                                                    <div class="bp-qline-v-row">
                                                        <span class="bp-qline-v-name">{{ $b['name'] ?? '' }}</span>
                                                        <span class="bp-qline-v-qty">{{ $b['qty'] ?? 0 }} pcs</span>
                                                        <span class="bp-qline-v-price">{{ currency_symbol() }}
                                                            {{ number_format($b['price'] ?? 0, 0) }}<span
                                                                class="bp-qline-v-unit">/pcs</span></span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif(count($variantParts))
                                            <div class="bp-qline-variants">
                                                @foreach ($variantParts as $part)
                                                    <div class="bp-qline-v-row"><span
                                                            class="bp-qline-v-name">{{ $part }}</span></div>
                                                @endforeach
                                            </div>
                                        @elseif($item->variant)
                                            <div class="bp-qline-variants">
                                                <div class="bp-qline-v-row">
                                                    <span
                                                        class="bp-qline-v-name">{{ $item->variant->variant_name ?? $item->variant->name }}</span>
                                                    <span class="bp-qline-v-qty">{{ $item->quantity }} pcs</span>
                                                    <span class="bp-qline-v-price">{{ currency_symbol() }}
                                                        {{ number_format($item->unit_price, 0) }}<span
                                                            class="bp-qline-v-unit">/pcs</span></span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><input type="number" class="bp-form-control bp-input-narrow bp-line-item-qty"
                                            name="items[{{ $index }}][quantity]"
                                            value="{{ old('items.' . $index . '.quantity', $item->quantity) }}"
                                            min="1"
                                            @if (count($breakdown)) readonly title="{{ __('Total of variant quantities') }}" @endif><input
                                            type="hidden" class="bp-line-item-price"
                                            name="items[{{ $index }}][unit_price]"
                                            value="{{ old('items.' . $index . '.unit_price', num_input($item->unit_price)) }}">
                                    </td>
                                    <td>
                                        <div class="bp-line-discount">
                                            <select class="bp-form-select bp-line-item-discount-type">
                                                <option value="fixed">{{ currency_symbol() }}</option>
                                                <option value="percentage">%</option>
                                            </select>
                                            <input type="number" class="bp-form-control bp-line-item-discount-input"
                                                value="{{ old('items.' . $index . '.discount_amount', num_input($item->discount_amount)) }}"
                                                min="0" step="0.01">
                                        </div>
                                        <input type="hidden" class="bp-line-item-discount"
                                            name="items[{{ $index }}][discount_amount]"
                                            value="{{ old('items.' . $index . '.discount_amount', $item->discount_amount) }}">
                                    </td>
                                    <td class="fw-700 line-item-total text-end text-nowrap">{{ currency_symbol() }}
                                        {{ number_format($item->subtotal, 0) }}</td>
                                    <td class="text-nowrap">
                                        {{-- Show the variant editor for any breakdown line, and also for legacy
                       variable-product lines saved before the breakdown column existed —
                       lets staff re-enter per-variant qty/price and persist a breakdown. --}}
                                        @if (count($breakdown) || optional($item->product)->product_type === 'variable')
                                            <button type="button"
                                                class="bp-btn bp-btn-sm bp-btn-icon bp-btn-success btn-edit-variant me-1"
                                                title="{{ __('Edit variants') }}"><i
                                                    class="fa-solid fa-pen"></i></button>
                                        @endif
                                        <button type="button"
                                            class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger btn-remove-item"><i
                                                class="fa-solid fa-xmark"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bp-empty-items text-center text-muted py-4 {{ $quotation->items->count() ? 'd-none' : '' }}"
                    id="emptyItemsMsg">
                    <i class="fa-solid fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                    Search and add products above
                </div>
            </div>
        </div>

        <!-- Notes & Summary -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="bp-card h-100">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2"></i>Notes & Terms</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="mb-3">
                            <label class="bp-form-label">Customer Notes</label>
                            <textarea class="bp-form-control" name="notes" rows="3">{{ old('notes', $quotation->notes) }}</textarea>
                            @error('notes')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="bp-form-label">Terms & Conditions</label>
                            <textarea class="bp-form-control" name="terms" rows="3">{{ old('terms', $quotation->terms) }}</textarea>
                            @error('terms')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bp-card h-100">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>Summary</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="bp-cart-summary-row">
                            <span>Subtotal</span>
                            <span class="fw-700" id="quotationSubtotal">{{ currency_symbol() }} 0</span>
                        </div>
                        <div class="bp-cart-summary-row">
                            <span>Discount</span>
                            <span id="quotationDiscount">{{ currency_symbol() }} 0</span>
                        </div>
                        <div class="bp-cart-summary-row">
                            <span>Delivery Charge ({{ currency_symbol() }})</span>
                            <input type="number" class="bp-form-control bp-input-narrow text-end" id="quotationShipping"
                                name="shipping_charge"
                                value="{{ old('shipping_charge', num_input($quotation->shipping_charge)) }}"
                                min="0" step="0.01">
                        </div>
                        @error('shipping_charge')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                        <div class="bp-cart-summary-row total">
                            <span>Grand Total</span>
                            <span id="quotationGrandTotal">{{ currency_symbol() }} 0</span>
                        </div>
                        <div class="bp-cart-summary-row bp-adv-prevdue d-none" id="quotationPrevDueRow">
                            <span>{{ __('Previous Due') }}</span>
                            <span id="quotationPrevDue">{{ currency_symbol() }} 0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('quotations.show', $quotation) }}" class="bp-btn bp-btn-danger"><i
                    class="fa-solid fa-xmark me-1"></i>Cancel</a>
            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Update
                Quotation</button>
        </div>

    </form>

    @include('quotation::components.variant-picker-modal')
    @include('quotation::partials.new-customer-modal')

@endsection

@push('scripts')
    <script src="{{ asset('vendor/venobox/venobox.min.js') }}"></script>
    <script>
        'use strict';

        $(function() {
            var itemIndex = {{ $quotation->items->count() }};
            var productCatalog = @json($products);

            // ── Price-type helper (regular / wholesale) ──
            function currentPriceType() {
                return $('#quotationPriceType').val() || 'regular';
            }

            function priceFor(src) {
                src = src || {};
                var type = currentPriceType();
                if (type === 'resell') {
                    return src.resell_price != null ? src.resell_price :
                        (src.wholesale_price != null ? src.wholesale_price : (src.sell_price || 0));
                }
                if (type === 'wholesale') {
                    return src.wholesale_price != null ? src.wholesale_price : (src.sell_price || 0);
                }
                return src.sell_price || 0;
            }

            // Open line-item product images in a VenoBox lightbox. Re-bind per new row.
            function bindVenobox($scope) {
                var $links = ($scope || $('#lineItemsTable')).find('a.venobox');
                if ($links.length && $.fn.venobox) $links.venobox();
            }
            bindVenobox(); // existing (server-rendered) rows

            // ── Product Search (shared widget — matches Sale create design) ──
            var search = window.BpProductSearch.init({
                input: '#productSearch',
                catalog: productCatalog,
                minChars: 0,
                currencySymbol: '{{ currency_symbol() }}',
                onSelect: function(product) {
                    if (product.product_type === 'variable' && product.variants && product.variants
                        .length) {
                        openVariantPicker(product); // pick variants in a modal → one combined line
                        return;
                    }
                    // Simple product → add a line directly.
                    appendRow({
                        productId: product.id,
                        productName: product.name,
                        sku: product.sku || '',
                        image: product.image || '',
                        variantId: '',
                        variantLabel: '-',
                        quantity: 1,
                        unitPrice: product.sell_price,
                        note: ''
                    });
                }
            });

            // ── Add a line-item row ──
            // `d.breakdown` (optional) is an array of {name, qty, price} for variable
            // products — rendered in the Variant Details column, with the row
            // quantity/price locked to the combined totals.
            function appendRow(d) {
                var i = itemIndex++;
                var hasBreakdown = Array.isArray(d.breakdown) && d.breakdown.length > 0;
                var qty = d.quantity || 1;
                var price = d.unitPrice || 0;
                var imgCell = d.image ?
                    '<a class="venobox" data-gall="qline" href="' + d.image + '"><img src="' + d.image +
                    '" class="bp-line-item-img" alt=""></a>' :
                    '<div class="bp-line-item-img bp-line-item-img-ph"><i class="fa-solid fa-box"></i></div>';

                var productCell = '<div class="fw-600">' + escapeHtml(d.productName) + '</div>';

                var variantCell = '<span class="text-muted">—</span>';
                if (hasBreakdown) {
                    var rowsHtml = '';
                    d.breakdown.forEach(function(b) {
                        rowsHtml += '<div class="bp-qline-v-row">' +
                            '<span class="bp-qline-v-name">' + escapeHtml(b.name) + '</span>' +
                            '<span class="bp-qline-v-qty">' + b.qty + ' pcs</span>' +
                            '<span class="bp-qline-v-price">{{ currency_symbol() }} ' + formatBDT(b
                                .price) + '<span class="bp-qline-v-unit">/pcs</span></span>' +
                            '</div>';
                    });
                    variantCell = '<div class="bp-qline-variants">' + rowsHtml + '</div>';
                    // Empty hidden inputs — values are assigned via the DOM below so the
                    // breakdown JSON (and any quotes in the note) can't corrupt markup.
                    productCell +=
                        '<input type="hidden" name="items[' + i + '][custom_note]" class="bp-line-item-note">' +
                        '<input type="hidden" name="items[' + i +
                        '][variant_breakdown]" class="bp-line-item-breakdown">';
                } else {
                    productCell +=
                        '<input type="text" class="bp-form-control fs-12 mt-1 bp-line-item-note" name="items[' + i +
                        '][custom_note]" placeholder="{{ __('Custom note (optional)') }}" maxlength="500">';
                }
                productCell +=
                    '<input type="hidden" name="items[' + i + '][product_id]" value="' + d.productId + '">' +
                    '<input type="hidden" name="items[' + i + '][variant_id]" value="' + (d.variantId || '') + '">';

                var qtyAttr = hasBreakdown ? ' readonly title="{{ __('Total of variant quantities') }}"' : '';

                var editBtn = hasBreakdown ?
                    '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-success btn-edit-variant me-1" title="{{ __('Edit variants') }}"><i class="fa-solid fa-pen"></i></button>' :
                    '';

                var row = '<tr data-product-id="' + d.productId + '" data-variant-id="' + (d.variantId || '') +
                    '">' +
                    '<td class="bp-col-img">' + imgCell + '</td>' +
                    '<td class="bp-col-product">' + productCell + '</td>' +
                    '<td class="bp-col-variants">' + variantCell + '</td>' +
                    '<td><input type="number" class="bp-form-control bp-input-narrow bp-line-item-qty" name="items[' +
                    i + '][quantity]" value="' + qty + '" min="1"' + qtyAttr + '>' +
                    '<input type="hidden" class="bp-line-item-price" name="items[' + i + '][unit_price]" value="' +
                    price + '"></td>' +
                    '<td>' +
                    '<div class="bp-line-discount">' +
                    '<select class="bp-form-select bp-line-item-discount-type">' +
                    '<option value="fixed">{{ currency_symbol() }}</option>' +
                    '<option value="percentage">%</option>' +
                    '</select>' +
                    '<input type="number" class="bp-form-control bp-line-item-discount-input" value="0" min="0" step="0.01">' +
                    '</div>' +
                    '<input type="hidden" class="bp-line-item-discount" name="items[' + i +
                    '][discount_amount]" value="0">' +
                    '</td>' +
                    '<td class="fw-700 line-item-total text-end text-nowrap">{{ currency_symbol() }} ' + formatBDT(
                        qty * price) + '</td>' +
                    '<td class="text-nowrap">' + editBtn +
                    '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger btn-remove-item"><i class="fa-solid fa-xmark"></i></button></td>' +
                    '</tr>';
                var $row = $(row);
                if (hasBreakdown) {
                    // Assign variant data through the DOM — never via interpolated HTML —
                    // so values with quotes/special chars are stored & submitted intact.
                    $row.attr('data-breakdown', JSON.stringify(d.breakdown));
                    $row.find('.bp-line-item-note').val(d.note || '');
                    $row.find('.bp-line-item-breakdown').val(JSON.stringify(d.breakdown));
                }
                if (d.replaceRow && d.replaceRow.length) {
                    d.replaceRow.replaceWith($row);
                } else {
                    // Newest product on top, above the existing (server-rendered) rows.
                    $row.prependTo('#lineItemsTable tbody');
                }
                bindVenobox($row);
                $('#emptyItemsMsg').addClass('d-none');
                recalculate();
            }

            // ── Variant picker modal (variable products) ──
            var qvModal = new bootstrap.Modal(document.getElementById('qvVariantModal'));
            var qvProduct = null;
            var qvEditingRow = null; // set when editing an existing line

            // `prefill` (optional) is a breakdown array [{name, qty, price}] used to
            // restore quantities/prices when editing an existing variant line.
            function openVariantPicker(product, prefill, editingRow) {
                qvProduct = product;
                qvEditingRow = editingRow || null;
                var prefillMap = {};
                (prefill || []).forEach(function(b) {
                    prefillMap[b.name] = b;
                });

                $('#qvModalProduct').text(product.name);
                $('#qvError').addClass('d-none').text('');
                var html = '';
                (product.variants || []).forEach(function(v, idx) {
                    var pre = prefillMap[v.name];
                    var qVal = pre ? pre.qty : 0;
                    var pVal = pre ? pre.price : priceFor(v);
                    // Out-of-stock size/color: flag the row red (still selectable).
                    var stock = parseInt(v.stock, 10) || 0;
                    var oos = stock <= 0;
                    var stockHtml = oos ?
                        '<span class="bp-badge bp-badge-danger fs-11">{{ __('Out of stock') }}</span>' :
                        '<span class="fs-11 text-muted">{{ __('Stock') }}: ' + stock + '</span>';
                    html += '<tr data-idx="' + idx + '"' + (oos ? ' class="bp-variant-oos"' : '') + '>' +
                        '<td class="fw-600">' + escapeHtml(v.name) + '<div class="mt-1">' + stockHtml +
                        '</div></td>' +
                        '<td class="fs-12 text-muted">' + escapeHtml(v.sku || '') + '</td>' +
                        '<td><input type="number" class="bp-form-control bp-input-narrow qv-qty" value="' +
                        qVal + '" min="0" step="1"></td>' +
                        '<td><input type="number" class="bp-form-control bp-input-medium qv-price" value="' +
                        pVal + '" min="0" step="0.01"></td>' +
                        '</tr>';
                });
                $('#qvVariantRows').html(html);
                $('#qvCustomNote').val(qvEditingRow ? (qvEditingRow.find('.bp-line-item-note').val() || '') : '');
                qvModal.show();
            }

            // ── Edit an existing variant line — re-open the picker pre-filled ──
            $(document).on('click', '.btn-edit-variant', function() {
                var $row = $(this).closest('tr');
                var pid = parseInt($row.attr('data-product-id'), 10);
                var product = productCatalog.find(function(p) {
                    return p.id === pid;
                });
                if (!product) return;
                var breakdown = [];
                try {
                    breakdown = JSON.parse($row.attr('data-breakdown') || '[]');
                } catch (e) {
                    breakdown = [];
                }
                openVariantPicker(product, breakdown, $row);
            });

            // Auto-fill the note from variants that have a quantity (admin can still edit).
            $(document).on('input', '#qvVariantRows .qv-qty', function() {
                if (!qvProduct) return;
                var names = [];
                $('#qvVariantRows tr').each(function(i) {
                    if ((parseInt($(this).find('.qv-qty').val(), 10) || 0) > 0) names.push(qvProduct
                        .variants[i].name);
                });
                $('#qvCustomNote').val(names.join(', '));
            });

            $('#qvConfirm').on('click', function() {
                if (!qvProduct) return;
                var chosen = [];
                $('#qvVariantRows tr').each(function(i) {
                    var qty = parseInt($(this).find('.qv-qty').val(), 10) || 0;
                    var price = parseFloat($(this).find('.qv-price').val()) || 0;
                    if (qty > 0) chosen.push({
                        name: qvProduct.variants[i].name,
                        qty: qty,
                        price: price
                    });
                });
                if (!chosen.length) {
                    $('#qvError').removeClass('d-none').text(
                        '{{ __('Enter a quantity for at least one variant.') }}');
                    return;
                }
                var totalQty = 0,
                    totalAmount = 0;
                chosen.forEach(function(c) {
                    totalQty += c.qty;
                    totalAmount += c.qty * c.price;
                });
                var firstPrice = chosen[0].price;
                var allSame = chosen.every(function(c) {
                    return c.price === firstPrice;
                });
                var unitPrice = allSame ? firstPrice : Math.round((totalAmount / totalQty) * 100) / 100;
                var note = ($('#qvCustomNote').val() || '').trim() ||
                    chosen.map(function(c) {
                        return c.name + ': ' + c.qty + ' × ' + c.price;
                    }).join(', ');
                appendRow({
                    productId: qvProduct.id,
                    productName: qvProduct.name,
                    sku: qvProduct.sku || '',
                    image: qvProduct.image || '',
                    variantId: '',
                    variantLabel: 'Multiple',
                    quantity: totalQty,
                    unitPrice: unitPrice,
                    note: note,
                    breakdown: chosen,
                    replaceRow: qvEditingRow
                });
                qvEditingRow = null;
                qvModal.hide();
            });

            // ── Remove Line Item ──
            $(document).on('click', '.btn-remove-item', function() {
                $(this).closest('tr').remove();
                if ($('#lineItemsTable tbody tr').length === 0) {
                    $('#emptyItemsMsg').removeClass('d-none');
                }
                recalculate();
            });

            // ── Recalculate ──
            $(document).on('input',
                '.bp-line-item-qty, .bp-line-item-price, .bp-line-item-discount-input, .bp-line-item-tax',
                function() {
                    recalculate();
                });
            $(document).on('change', '.bp-line-item-discount-type', function() {
                recalculate();
            });
            $(document).on('input', '#quotationShipping', function() {
                recalculate();
            });

            // ── Customer's previous due (display-only) ──
            function updatePrevDue() {
                var due = parseFloat($('#customer_id').find('option:selected').data('due')) || 0;
                if (due > 0) {
                    $('#quotationPrevDue').text('{{ currency_symbol() }} ' + formatBDT(due));
                    $('#quotationPrevDueRow').removeClass('d-none');
                } else {
                    $('#quotationPrevDueRow').addClass('d-none');
                }
            }

            // ── Customer change: auto-fill billing/shipping address ──
            // Prefill only when the target field is currently empty — never clobber a
            // value the user already typed or edited.
            function prefillCustomerAddresses() {
                var $opt = $('#customer_id').find('option:selected');
                var billing = $opt.data('billing') || '';
                var shipping = $opt.data('shipping') || billing;
                if (billing && !$('#billingAddress').val()) {
                    $('#billingAddress').val(billing);
                }
                if (shipping && !$('#shippingAddress').val()) {
                    $('#shippingAddress').val(shipping);
                }
            }

            // Render the read-only address cards from the editable field values.
            function renderAddrCards() {
                var billing = ($('#billingAddress').val() || '').trim();
                var shipping = ($('#shippingAddress').val() || '').trim();
                $('#billingCardText').text(billing || '—');
                if (!shipping || shipping === billing) {
                    $('#shipSameBadge').removeClass('d-none');
                    $('#shippingCardText').text(billing || '—');
                } else {
                    $('#shipSameBadge').addClass('d-none');
                    $('#shippingCardText').text(shipping);
                }
            }

            // Edit toggle: reveal/hide the editable fields; cards stay as preview.
            $('#addrEditToggle').on('click', function() {
                var opening = $('#addrEditFields').hasClass('d-none');
                $('#addrEditFields').toggleClass('d-none', !opening);
                $(this).html(opening ?
                    '<i class="fa-solid fa-check me-1"></i>{{ __('Done') }}' :
                    '<i class="fa-solid fa-pen me-1"></i>{{ __('Edit') }}');
                if (!opening) renderAddrCards();
            });
            $('#billingAddress, #shippingAddress').on('input', renderAddrCards);

            $('#customer_id').on('change', function() {
                updatePrevDue();
                prefillCustomerAddresses();
                renderAddrCards();
            });
            updatePrevDue();
            prefillCustomerAddresses();
            renderAddrCards();

            // Price type change: re-apply regular/wholesale price to simple-product rows.
            $('#quotationPriceType').on('change', function() {
                $('#lineItemsTable tbody tr').each(function() {
                    var $row = $(this);
                    if ($row.find('.bp-qline-variants').length)
                        return; // skip variant breakdown rows
                    var pid = parseInt($row.attr('data-product-id'), 10);
                    var product = productCatalog.find(function(p) {
                        return p.id === pid;
                    });
                    if (product) $row.find('.bp-line-item-price').val(priceFor(product));
                });
                recalculate();
            });

            function recalculate() {
                var subtotal = 0;
                var totalDiscount = 0;
                var totalTax = 0;

                $('#lineItemsTable tbody tr').each(function() {
                    var $row = $(this);
                    var qty = parseFloat($row.find('.bp-line-item-qty').val()) || 0;
                    var price = parseFloat($row.find('.bp-line-item-price').val()) || 0;
                    var gross = qty * price;

                    // Per-line discount: flat amount or percentage of the line gross.
                    var discType = $row.find('.bp-line-item-discount-type').val();
                    var discInput = parseFloat($row.find('.bp-line-item-discount-input').val()) || 0;
                    var discount = discType === 'percentage' ? gross * (discInput / 100) : discInput;
                    discount = Math.min(gross, Math.max(0, discount));
                    $row.find('.bp-line-item-discount').val(window.numInput(discount));

                    var tax = parseFloat($row.find('.bp-line-item-tax').val()) || 0;
                    var lineTotal = gross - discount + tax;

                    subtotal += gross;
                    totalDiscount += discount;
                    totalTax += tax;

                    $row.find('.line-item-total').text('{{ currency_symbol() }} ' + formatBDT(lineTotal));
                });

                var shipping = parseFloat($('#quotationShipping').val()) || 0;
                var grandTotal = subtotal - totalDiscount + totalTax + shipping;

                $('#quotationSubtotal').text('{{ currency_symbol() }} ' + formatBDT(subtotal));
                $('#quotationDiscount').text('{{ currency_symbol() }} ' + formatBDT(totalDiscount));
                $('#quotationGrandTotal').text('{{ currency_symbol() }} ' + formatBDT(grandTotal));
            }

            // ── Helpers ──
            function formatBDT(num) {
                num = Math.round(num);
                var str = Math.abs(num).toString();
                var lastThree = str.substring(str.length - 3);
                var otherNumbers = str.substring(0, str.length - 3);
                if (otherNumbers !== '') {
                    lastThree = ',' + lastThree;
                }
                var formatted = otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
                return num < 0 ? '-' + formatted : formatted;
            }

            // Escape for safe insertion into HTML, including inside double-quoted
            // attribute values. Must escape quotes too — JSON breakdown payloads are
            // full of `"`, and a textNode/innerHTML approach leaves quotes raw, which
            // truncates `value="..."` and corrupts the submitted field.
            function escapeHtml(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            // Validate at least one item before submit
            $('#quotationForm').on('submit', function(e) {
                if ($('#lineItemsTable tbody tr').length === 0) {
                    e.preventDefault();
                    alert('Please add at least one product.');
                }
            });

            // Trigger initial calculation for existing items
            recalculate();
        });
    </script>
@endpush
