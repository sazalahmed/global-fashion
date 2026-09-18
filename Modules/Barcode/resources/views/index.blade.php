@extends('core::layouts.master')

@section('title', __('Print Barcodes'))
@section('page-title', __('Print Barcodes'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Barcode Generator</span>
@endsection

@section('page-actions')
    @bpCan('barcode.generate')
    <button class="bp-btn bp-btn-danger" id="clearAllBtn"><i class="fa-solid fa-rotate-left me-1"></i> Clear All</button>
    <button class="bp-btn bp-btn-primary" id="printBarcodesBtn"><i class="fa-solid fa-print me-1"></i> Print Barcodes</button>
    @endbpCan
@endsection

@section('content')

    <div class="row g-4">
        <!-- Left Column: Product Selection & Table -->
        <div class="col-xl-8">

            <!-- Product Search -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>Add Products</h5>
                </div>
                <div class="bp-card-body">
                    <label class="bp-form-label">Search Product (by name, SKU, or barcode)</label>
                    <div class="position-relative">
                        <div class="bp-table-search w-100">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="productSearch"
                                placeholder="Type product name, SKU, or scan barcode..." class="w-100">
                        </div>
                        <div id="searchResults" class="bp-search-dropdown d-none"></div>
                    </div>
                </div>
            </div>

            <!-- Selected Products Table -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Selected Products</h5>
                    <span class="bp-badge bp-badge-info" id="totalLabelsCount">Total: 0 labels</span>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table" id="barcodeProductsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Barcode</th>
                                    <th>Price</th>
                                    <th>Qty (Labels)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($preselected as $product)
                                    @php
                                        $stock = (int) ($product->current_stock ?? 0);
                                        $defaultQty = $stock > 0 ? $stock : 1;
                                    @endphp
                                    <tr data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                        data-sku="{{ $product->sku }}"
                                        data-barcode="{{ $product->barcode ?? $product->sku }}"
                                        data-price="{{ (float) $product->sell_price }}" data-stock="{{ $stock }}">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bp-pos-item-img bp-pos-item-img-sm"><i
                                                        class="fa-solid fa-box"></i></div>
                                                <div class="fw-700">{{ $product->name }}</div>
                                            </div>
                                        </td>
                                        <td><code class="fs-11">{{ $product->sku }}</code></td>
                                        <td><code class="fs-11">{{ $product->barcode ?? '' }}</code></td>
                                        <td class="fw-600">{{ currency_symbol() }} {{ $product->formatted_sell_price }}
                                        </td>
                                        <td>
                                            <input type="number" class="bp-form-control bp-input-narrow barcode-qty"
                                                value="{{ $defaultQty }}" min="1" max="500"
                                                title="{{ __('Stock on hand') }}: {{ $stock }}">
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger btn-remove-barcode"><i
                                                    class="fa-solid fa-xmark"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div id="emptyState" class="{{ $preselected->count() ? 'd-none' : '' }} text-center text-muted py-4">
                        <i class="fa-solid fa-barcode fa-2x mb-2 d-block"></i>
                        <p class="fs-13 mb-0">Search and add products above to generate barcodes.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Settings & Preview -->
        <div class="col-xl-4">

            <!-- Barcode Settings -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-gears me-2"></i>Label Settings</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="bp-form-label">Label Size</label>
                            <select class="bp-form-select w-100" name="label_size" id="labelSize">
                                <option value="38x25">38mm x 25mm (Small)</option>
                                <option value="50x25" selected>50mm x 25mm (Standard)</option>
                                <option value="50x30">50mm x 30mm (Large)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">Barcode Type</label>
                            <select class="bp-form-select w-100" name="barcode_type" id="barcodeType">
                                <option value="code128" selected>Code 128</option>
                                <option value="ean13">EAN-13</option>
                                <option value="upc">UPC-A</option>
                                <option value="qrcode">QR Code</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">Labels Per Row</label>
                            <select class="bp-form-select w-100" name="labels_per_row" id="labelsPerRow">
                                <option value="2">2 Labels</option>
                                <option value="3" selected>3 Labels</option>
                                <option value="4">4 Labels</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked name="show_name" id="showName">
                                <label class="form-check-label fw-600 fs-13" for="showName">Show Product Name</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked name="show_price"
                                    id="showPrice">
                                <label class="form-check-label fw-600 fs-13" for="showPrice">Show Price</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="show_business" id="showBusiness">
                                <label class="form-check-label fw-600 fs-13" for="showBusiness">Show Business Name</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Preview -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-eye me-2"></i>Live Preview</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-label-preview-wrap">
                        <div class="bp-label-preview size-50x25" id="labelPreview">
                            <div class="label-business" id="previewBusiness">
                                {{ \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro') }}</div>
                            <div class="label-name" id="previewName">Sample Product</div>
                            <svg id="previewBarcode"></svg>
                            <div class="label-price" id="previewPrice">{{ currency_symbol() }} 250</div>
                        </div>
                    </div>
                    <div class="fs-12 text-muted mt-2 text-center" id="previewSource">Showing sample — add a product to
                        preview it.</div>
                </div>
            </div>

            <!-- Print Actions -->
            <div class="d-flex flex-column gap-2 mt-3">
                @bpCan('barcode.generate')
                <button type="button" class="bp-btn bp-btn-primary w-100 justify-content-center" id="printBtn"><i
                        class="fa-solid fa-print me-2"></i> Print Barcodes</button>
                @endbpCan
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/jsbarcode/jsbarcode.min.js') }}"></script>
    <script>
        'use strict';

        $(function() {
            var searchTimeout;

            // AJAX product search
            $('#productSearch').on('input', function() {
                var val = $(this).val().trim();
                clearTimeout(searchTimeout);
                if (val.length < 2) {
                    $('#searchResults').addClass('d-none').empty();
                    return;
                }
                searchTimeout = setTimeout(function() {
                    $.get('{{ route('barcode.search') }}', {
                        q: val
                    }, function(data) {
                        var $results = $('#searchResults').empty();
                        if (!data.products || data.products.length === 0) {
                            $results.html(
                                '<div class="p-3 text-center text-muted fs-13">No products found.</div>'
                            );
                        } else {
                            data.products.forEach(function(p) {
                                $results.append(
                                    '<div class="bp-search-item" data-id="' + p
                                    .id + '" data-name="' + escHtml(p.name) +
                                    '" data-sku="' + escHtml(p.sku) +
                                    '" data-barcode="' + escHtml(p.barcode) +
                                    '" data-price="' + p.sell_price +
                                    '" data-stock="' + (p.current_stock || 0) +
                                    '">' +
                                    '<div class="fw-600">' + escHtml(p.name) +
                                    '</div>' +
                                    '<div class="fs-11 text-muted">SKU: ' +
                                    escHtml(p.sku) + ' · Barcode: ' + (p
                                        .barcode || '--') +
                                    ' · {{ currency_symbol() }} ' + formatBDT(p
                                        .sell_price) + ' · Stock: ' + (p
                                        .current_stock || 0) + '</div>' +
                                    '</div>'
                                );
                            });
                        }
                        $results.removeClass('d-none');
                    });
                }, 300);
            });

            // Hide search on click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#productSearch, #searchResults').length) {
                    $('#searchResults').addClass('d-none');
                }
            });

            // Add product from search results
            $(document).on('click', '.bp-search-item', function() {
                var id = $(this).data('id');
                if ($('#barcodeProductsTable tbody tr[data-id="' + id + '"]').length > 0) {
                    alert('Product already added.');
                    return;
                }

                var name = $(this).data('name');
                var sku = $(this).data('sku');
                var barcode = $(this).data('barcode') || '--';
                var price = $(this).data('price');
                var stock = parseInt($(this).data('stock'), 10) || 0;
                var defaultQty = stock > 0 ? stock : 1;

                var row = '<tr data-id="' + id + '" data-name="' + escHtml(name) + '" data-sku="' + escHtml(
                        sku) + '" data-barcode="' + escHtml(barcode) + '" data-price="' + price +
                    '" data-stock="' + stock + '">' +
                    '<td><div class="d-flex align-items-center gap-2"><div class="bp-pos-item-img bp-pos-item-img-sm"><i class="fa-solid fa-box"></i></div><div class="fw-700">' +
                    escHtml(name) + '</div></div></td>' +
                    '<td><code class="fs-11">' + escHtml(sku) + '</code></td>' +
                    '<td><code class="fs-11">' + escHtml(barcode) + '</code></td>' +
                    '<td class="fw-600">{{ currency_symbol() }} ' + formatBDT(price) + '</td>' +
                    '<td><input type="number" class="bp-form-control bp-input-narrow barcode-qty" value="' +
                    defaultQty + '" min="1" max="500" title="Stock on hand: ' + stock + '"></td>' +
                    '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger btn-remove-barcode"><i class="fa-solid fa-xmark"></i></button></td>' +
                    '</tr>';
                $('#barcodeProductsTable tbody').append(row);
                $('#emptyState').addClass('d-none');

                $('#productSearch').val('');
                $('#searchResults').addClass('d-none');
                updateTotalLabels();
                renderPreview();
            });

            // Remove product
            $(document).on('click', '.btn-remove-barcode', function() {
                $(this).closest('tr').remove();
                updateTotalLabels();
                if ($('#barcodeProductsTable tbody tr').length === 0) {
                    $('#emptyState').removeClass('d-none');
                }
                renderPreview();
            });

            // Update total labels on qty change
            $(document).on('input', '.barcode-qty', function() {
                updateTotalLabels();
            });

            function updateTotalLabels() {
                var total = 0;
                $('.barcode-qty').each(function() {
                    total += parseInt($(this).val()) || 0;
                });
                $('#totalLabelsCount').text('Total: ' + total + ' labels');
            }

            // Clear all
            $('#clearAllBtn').on('click', function() {
                if ($('#barcodeProductsTable tbody tr').length === 0) return;
                if (confirm('Remove all selected products?')) {
                    $('#barcodeProductsTable tbody').html('');
                    $('#emptyState').removeClass('d-none');
                    updateTotalLabels();
                    renderPreview();
                }
            });

            // ── Live preview: rebuild whenever settings or the product list change ──
            var barcodeFormatMap = {
                'code128': 'CODE128',
                'ean13': 'EAN13',
                'upc': 'UPC',
                'qrcode': 'CODE128' // QR not in JsBarcode core — fall back to CODE128
            };

            function renderPreview() {
                var $firstRow = $('#barcodeProductsTable tbody tr').first();
                var hasProduct = $firstRow.length > 0;

                var name, sku, barcodeValue, price;
                if (hasProduct) {
                    name = $firstRow.data('name') || '';
                    sku = $firstRow.data('sku') || '';
                    barcodeValue = ($firstRow.data('barcode') && $firstRow.data('barcode') !== '--') ?
                        $firstRow.data('barcode') : (sku || '0000000000');
                    price = parseFloat($firstRow.data('price')) || 0;
                    $('#previewSource').text('Showing: ' + name);
                } else {
                    name = 'Sample Product';
                    barcodeValue = '1234567890';
                    price = 250;
                    $('#previewSource').text('Showing sample — add a product to preview it.');
                }

                var $preview = $('#labelPreview');
                $preview.removeClass('size-38x25 size-50x25 size-50x30')
                    .addClass('size-' + $('#labelSize').val());

                $('#previewName').text(name).toggle($('#showName').is(':checked'));
                $('#previewPrice').text('{{ currency_symbol() }} ' + formatBDT(price)).toggle($('#showPrice').is(
                    ':checked'));
                $('#previewBusiness').toggle($('#showBusiness').is(':checked'));

                // Re-render the barcode SVG
                var $svg = $('#previewBarcode');
                $svg.empty();
                try {
                    JsBarcode('#previewBarcode', String(barcodeValue), {
                        format: barcodeFormatMap[$('#barcodeType').val()] || 'CODE128',
                        width: 1.5,
                        height: 35,
                        displayValue: true,
                        fontSize: 10,
                        font: 'Nunito Sans',
                        fontOptions: '600',
                        margin: 0,
                        textMargin: 1
                    });
                } catch (e) {
                    JsBarcode('#previewBarcode', String(barcodeValue), {
                        format: 'CODE128',
                        width: 1.5,
                        height: 35,
                        displayValue: true,
                        fontSize: 10,
                        font: 'Nunito Sans',
                        margin: 0,
                        textMargin: 1
                    });
                }
            }

            $('#labelSize, #barcodeType, #showName, #showPrice, #showBusiness').on('change input', renderPreview);

            // Print — open in new window via form POST
            $('#printBarcodesBtn, #printBtn').on('click', function() {
                var $rows = $('#barcodeProductsTable tbody tr');
                if ($rows.length === 0) {
                    alert('Please add at least one product.');
                    return;
                }

                var $form = $('<form method="POST" target="_blank"></form>');
                $form.attr('action', '{{ route('barcode.print') }}');
                $form.append('<input type="hidden" name="_token" value="{{ csrf_token() }}">');
                $form.append('<input type="hidden" name="barcode_type" value="' + $('#barcodeType').val() +
                    '">');
                $form.append('<input type="hidden" name="show_name" value="' + ($('#showName').is(
                    ':checked') ? '1' : '0') + '">');
                $form.append('<input type="hidden" name="show_price" value="' + ($('#showPrice').is(
                    ':checked') ? '1' : '0') + '">');
                $form.append('<input type="hidden" name="show_business" value="' + ($('#showBusiness').is(
                    ':checked') ? '1' : '0') + '">');
                $form.append('<input type="hidden" name="label_size" value="' + $('#labelSize').val() +
                    '">');
                $form.append('<input type="hidden" name="labels_per_row" value="' + $('#labelsPerRow')
                    .val() + '">');

                $rows.each(function() {
                    var id = $(this).data('id');
                    var qty = $(this).find('.barcode-qty').val() || 1;
                    $form.append('<input type="hidden" name="product_ids[]" value="' + id + '">');
                    $form.append('<input type="hidden" name="quantities[' + id + ']" value="' +
                        qty + '">');
                });

                $('body').append($form);
                $form.submit();
                $form.remove();
            });

            function escHtml(s) {
                return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(
                    /"/g, '&quot;');
            }

            function formatBDT(num) {
                num = Math.round(num);
                var str = num.toString();
                var lastThree = str.substring(str.length - 3);
                var otherNumbers = str.substring(0, str.length - 3);
                if (otherNumbers !== '') lastThree = ',' + lastThree;
                return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
            }

            // Initial count and preview
            updateTotalLabels();
            renderPreview();
        });
    </script>
@endpush
