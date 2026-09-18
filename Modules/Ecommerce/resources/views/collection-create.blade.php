@extends('core::layouts.master')

@section('title', __('Create Collection — Website'))
@section('page-title', __('Create Collection'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.collections') }}">Collections</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Create</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.collections') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Collections
    </a>
@endsection

@section('content')

    <form action="{{ route('ecommerce.collections.store') }}" method="POST" id="collectionForm">
        @csrf

        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-xl-8">

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-layer-group me-2"></i>Collection Details</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Name *</label>
                                <input type="text" class="bp-form-control" name="name" value="{{ old('name') }}"
                                    placeholder="e.g. Customer Favorites" required>
                                @error('name')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Collection Type *</label>
                                <select class="bp-form-select w-100" name="type" id="collectionType" required>
                                    <option value="manual" {{ old('type', 'manual') === 'manual' ? 'selected' : '' }}>Manual
                                        (Pick Products)</option>
                                    <option value="auto" {{ old('type') === 'auto' ? 'selected' : '' }}>Auto (Filter
                                        Rules)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Description</label>
                                <input type="text" class="bp-form-control" name="description"
                                    value="{{ old('description') }}" placeholder="Short description of this collection">
                                @error('description')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Product Selection -->
                <div class="bp-card mb-4" id="manualSection">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Select Products</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="mb-3">
                            <label class="bp-form-label">Search & Add Products</label>
                            <input type="text" class="bp-form-control" id="productSearch"
                                placeholder="Search by name or SKU...">
                        </div>
                        <div id="searchResults" class="mb-3 d-none">
                            <div class="bp-table-wrapper">
                                <table class="bp-table" id="searchResultsTable">
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="selectedProducts">
                            <div class="text-center text-muted py-3" id="noProductsMsg">No products selected yet.</div>
                            <div class="bp-table-wrapper d-none" id="selectedProductsTable">
                                <table class="bp-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th align="end">Price</th>
                                            <th width="60">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedProductsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Auto Filter Rules -->
                <div class="bp-card mb-4 d-none" id="autoSection">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-filter me-2"></i>Filter Rules</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Categories</label>
                                <select class="bp-form-select w-100" name="filter_rules[category_ids][]" multiple
                                    size="5">
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <div class="fs-11 text-muted mt-1">Hold Ctrl to select multiple</div>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Brands</label>
                                <select class="bp-form-select w-100" name="filter_rules[brand_ids][]" multiple
                                    size="5">
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                <div class="fs-11 text-muted mt-1">Hold Ctrl to select multiple</div>
                            </div>
                            <div class="col-md-4">
                                <label class="bp-form-label">Min Price ({{ currency_symbol() }})</label>
                                <input type="number" class="bp-form-control" name="filter_rules[price_min]"
                                    value="{{ old('filter_rules.price_min') }}" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="bp-form-label">Max Price ({{ currency_symbol() }})</label>
                                <input type="number" class="bp-form-control" name="filter_rules[price_max]"
                                    value="{{ old('filter_rules.price_max') }}" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="bp-form-label">Sort By</label>
                                <select class="bp-form-select w-100" name="filter_rules[sort_by]">
                                    <option value="latest">Latest</option>
                                    <option value="price_low">Price: Low to High</option>
                                    <option value="price_high">Price: High to Low</option>
                                    <option value="name_asc">Name: A-Z</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="bp-form-label">Max Products</label>
                                <input type="number" class="bp-form-control" name="filter_rules[limit]"
                                    value="{{ old('filter_rules.limit', 12) }}" min="1" max="50">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="col-xl-4">
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-toggle-on me-2"></i>Status</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="bp-form-label">Status</label>
                                <select class="bp-form-select w-100" name="is_active">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive
                                    </option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Sort Order</label>
                                <input type="number" class="bp-form-control" name="sort_order"
                                    value="{{ old('sort_order', 0) }}" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="bp-btn bp-btn-primary justify-content-center">
                        <i class="fa-solid fa-save me-2"></i> Create Collection
                    </button>
                    <a href="{{ route('ecommerce.collections') }}"
                        class="bp-btn bp-btn-danger justify-content-center">
                        <i class="fa-solid fa-times me-2"></i> Cancel
                    </a>
                </div>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            var selectedProducts = {};
            var searchTimeout = null;

            // Toggle manual/auto sections
            $('#collectionType').on('change', function() {
                var isManual = $(this).val() === 'manual';
                $('#manualSection').toggleClass('d-none', !isManual);
                $('#autoSection').toggleClass('d-none', isManual);
            });

            // Product search
            $('#productSearch').on('input', function() {
                var q = $(this).val().trim();
                clearTimeout(searchTimeout);

                if (q.length < 2) {
                    $('#searchResults').addClass('d-none');
                    return;
                }

                searchTimeout = setTimeout(function() {
                    $.get('{{ route('ecommerce.api.products.search') }}', {
                        q: q
                    }, function(data) {
                        var html = '';
                        $.each(data.results, function(i, product) {
                            if (!selectedProducts[product.id]) {
                                html += '<tr>' +
                                    '<td>' + $('<span>').text(product.name).html() +
                                    '</td>' +
                                    '<td>' + $('<span>').text(product.sku).html() +
                                    '</td>' +
                                    '<td class="text-end">{{ currency_symbol() }} ' +
                                    Number(product.price).toLocaleString() +
                                    '</td>' +
                                    '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-success add-product" ' +
                                    'data-id="' + product.id + '" data-name="' + $(
                                        '<span>').text(product.name).html() + '" ' +
                                    'data-sku="' + $('<span>').text(product.sku)
                                    .html() + '" data-price="' + product.price +
                                    '">' +
                                    '<i class="fa-solid fa-plus"></i></button></td></tr>';
                            }
                        });
                        if (html) {
                            $('#searchResultsTable tbody').html(html);
                            $('#searchResults').removeClass('d-none');
                        } else {
                            $('#searchResults').addClass('d-none');
                        }
                    });
                }, 300);
            });

            // Add product
            $(document).on('click', '.add-product', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var sku = $(this).data('sku');
                var price = $(this).data('price');

                if (selectedProducts[id]) return;
                selectedProducts[id] = true;

                var row = '<tr data-product-id="' + id + '">' +
                    '<td>' + name + '<input type="hidden" name="product_ids[]" value="' + id + '"></td>' +
                    '<td><code>' + sku + '</code></td>' +
                    '<td class="text-end">{{ currency_symbol() }} ' + Number(price).toLocaleString() +
                    '</td>' +
                    '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-product"><i class="fa-solid fa-times"></i></button></td>' +
                    '</tr>';

                $('#selectedProductsBody').append(row);
                $('#noProductsMsg').addClass('d-none');
                $('#selectedProductsTable').removeClass('d-none');
                $(this).closest('tr').remove();
            });

            // Remove product
            $(document).on('click', '.remove-product', function() {
                var row = $(this).closest('tr');
                var id = row.data('product-id');
                delete selectedProducts[id];
                row.remove();

                if ($('#selectedProductsBody tr').length === 0) {
                    $('#noProductsMsg').removeClass('d-none');
                    $('#selectedProductsTable').addClass('d-none');
                }
            });
        });
    </script>
@endpush
