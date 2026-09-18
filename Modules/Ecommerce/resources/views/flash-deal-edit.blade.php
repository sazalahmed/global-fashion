@extends('core::layouts.master')

@section('title', __('Edit Flash Deal — Website'))
@section('page-title', __('Edit Flash Deal'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.flash-deals') }}">Flash Deals</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.flash-deals') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Flash Deals
    </a>
@endsection

@section('content')

    <form action="{{ route('ecommerce.flash-deals.update', $flashDeal) }}" method="POST" enctype="multipart/form-data"
        id="flashDealForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-bolt me-2"></i>Deal Details</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-12 category_img">
                                <x-core::image-upload name="banner_image" label="Banner Image" :current="$flashDeal->banner_image ? upload_url($flashDeal->banner_image) : null" removeName="remove_banner_image" />
                            </div>
                            <div class="col-md-12">
                                <label class="bp-form-label">Title *</label>
                                <input type="text" class="bp-form-control" name="title"
                                    value="{{ old('title', $flashDeal->title) }}" required>
                                @error('title')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Start Date & Time *</label>
                                <input type="datetime-local" class="bp-form-control" name="starts_at"
                                    value="{{ old('starts_at', $flashDeal->starts_at->format('Y-m-d\TH:i')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">End Date & Time *</label>
                                <input type="datetime-local" class="bp-form-control" name="ends_at"
                                    value="{{ old('ends_at', $flashDeal->ends_at->format('Y-m-d\TH:i')) }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Deal Products</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="mb-3">
                            @include('core::partials.product-search', [
                                'id' => 'productSearch',
                                'label' => __('Search & Add Products'),
                                'placeholder' => __('Please type product code or name and select...'),
                            ])
                        </div>

                        <div id="dealProductsWrapper">
                            @if ($flashDeal->products->isEmpty())
                                <div class="text-center text-muted py-3" id="noProductsMsg">Add at least one product.</div>
                            @else
                                <div class="text-center text-muted py-3 d-none" id="noProductsMsg">Add at least one product.
                                </div>
                            @endif
                            <div class="bp-table-wrapper {{ $flashDeal->products->isEmpty() ? 'd-none' : '' }}"
                                id="dealProductsTable">
                                <table class="bp-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th width="150">Discount Type</th>
                                            <th width="120">Value</th>
                                            <th width="60">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dealProductsBody">
                                        @foreach ($flashDeal->products as $index => $product)
                                            <tr data-product-id="{{ $product->id }}">
                                                <td>{{ $product->name }}
                                                    <input type="hidden" name="products[{{ $index }}][product_id]"
                                                        value="{{ $product->id }}">
                                                </td>
                                                <td>
                                                    <select class="bp-form-select w-100"
                                                        name="products[{{ $index }}][discount_type]">
                                                        <option value="percentage"
                                                            {{ $product->pivot->discount_type === 'percentage' ? 'selected' : '' }}>
                                                            Percentage</option>
                                                        <option value="fixed"
                                                            {{ $product->pivot->discount_type === 'fixed' ? 'selected' : '' }}>
                                                            Fixed ({{ currency_symbol() }})</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" class="bp-form-control"
                                                        name="products[{{ $index }}][discount_value]"
                                                        value="{{ num_input($product->pivot->discount_value) }}" min="0"
                                                        step="0.01" required>
                                                </td>
                                                <td>
                                                    <button type="button"
                                                        class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-deal-product"><i
                                                            class="fa-solid fa-times"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-xl-4">
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-toggle-on me-2"></i>Status</h5>
                    </div>
                    <div class="bp-card-body">
                        <select class="bp-form-select w-100" name="is_active">
                            <option value="1" {{ old('is_active', $flashDeal->is_active) ? 'selected' : '' }}>Active
                            </option>
                            <option value="0" {{ !old('is_active', $flashDeal->is_active) ? 'selected' : '' }}>
                                Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="bp-btn bp-btn-success justify-content-center">
                        <i class="fa-solid fa-save me-2"></i> Update Flash Deal
                    </button>
                    <a href="{{ route('ecommerce.flash-deals') }}"
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
            var productIndex = {{ $flashDeal->products->count() }};
            var selectedProducts = {};

            $('#dealProductsBody tr').each(function() {
                selectedProducts[$(this).data('product-id')] = true;
            });

            function escAttr(s) { return $('<span>').text(s == null ? '' : s).html(); }

            function addDealProduct(product) {
                var id = product.id;
                if (selectedProducts[id]) return; // already added — ignore
                selectedProducts[id] = true;
                var name = escAttr(product.name);
                var sku = product.sku ? ' <span class="text-muted fs-12">(' + escAttr(product.sku) + ')</span>' : '';
                $('#dealProductsBody').append('<tr data-product-id="' + id + '">' +
                    '<td>' + name + sku + '<input type="hidden" name="products[' + productIndex +
                    '][product_id]" value="' + id + '"></td>' +
                    '<td><select class="bp-form-select w-100" name="products[' + productIndex +
                    '][discount_type]">' +
                    '<option value="percentage">Percentage</option><option value="fixed">Fixed ({{ currency_symbol() }})</option></select></td>' +
                    '<td><input type="number" class="bp-form-control" name="products[' + productIndex +
                    '][discount_value]" min="0" step="0.01" required></td>' +
                    '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-deal-product"><i class="fa-solid fa-times"></i></button></td></tr>'
                );
                $('#noProductsMsg').addClass('d-none');
                $('#dealProductsTable').removeClass('d-none');
                productIndex++;
            }

            // Shared product-search widget — same UX as Purchase/Quotation create (Bug_89).
            window.BpProductSearch.init({
                input: '#productSearch',
                catalog: @json($products),
                showPrice: true,
                currencySymbol: '{{ currency_symbol() }}',
                onSelect: addDealProduct
            });

            $(document).on('click', '.remove-deal-product', function() {
                var row = $(this).closest('tr');
                delete selectedProducts[row.data('product-id')];
                row.remove();
                if ($('#dealProductsBody tr').length === 0) {
                    $('#noProductsMsg').removeClass('d-none');
                    $('#dealProductsTable').addClass('d-none');
                }
            });
        });
    </script>
@endpush
