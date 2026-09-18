@extends('core::layouts.master')

@section('title', __('Add Variant Attribute'))
@section('page-title', __('Add Variant Attribute'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('variants.index') }}">Variants</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add</span>
@endsection

@section('page-actions')
    <a href="{{ route('variants.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Variants
    </a>
@endsection

@section('content')

    <form action="{{ route('variants.store') }}" method="POST">
        @csrf

        <!-- Attribute Info Card -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-swatchbook me-2"></i>Attribute Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="bp-form-label">Attribute Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            value="{{ old('name') }}" required placeholder="e.g., Color, Size, Material">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Display Type *</label>
                        <select class="bp-form-select w-100 @error('display_type') is-invalid @enderror" name="display_type"
                            id="displayType" required>
                            <option value="button" {{ old('display_type', 'button') == 'button' ? 'selected' : '' }}>Button</option>
                            <option value="color_swatch" {{ old('display_type') == 'color_swatch' ? 'selected' : '' }}>Color
                                Swatch</option>
                        </select>
                        @error('display_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Status *</label>
                        <select class="bp-form-select w-100 @error('status') is-invalid @enderror" name="status" required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active
                            </option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Attribute Values Card -->
        <div class="bp-card">
            <div class="bp-card-header d-flex justify-content-between align-items-center">
                <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2"></i>Attribute Values</h5>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="addValueRow">
                    <i class="fa-solid fa-plus me-1"></i> Add Value
                </button>
            </div>
            <div class="bp-card-body">
                <div class="bp-table-wrapper">
                    <table class="bp-table" id="valuesTable">
                        <thead>
                            <tr>
                                <th>Value Name *</th>
                                <th class="bp-color-col" style="display: none;">Color Code</th>
                                <th>Sort Order</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="valuesBody">
                            <tr class="bp-value-row">
                                <td>
                                    <input type="text" class="bp-form-control" name="values[0][name]"
                                        placeholder="e.g., Red, Large, 128GB">
                                </td>
                                <td class="bp-color-col" style="display: none;">
                                    <input type="color" class="bp-form-control bp-form-color" name="values[0][color_code]"
                                        value="#000000">
                                </td>
                                <td>
                                    <input type="number" class="bp-form-control" name="values[0][sort_order]"
                                        value="1" min="0">
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-flex justify-content-center mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" checked disabled>
                                    </div>
                                </td>
                                <td>
                                    <button type="button"
                                        class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger bp-remove-value"><i
                                            class="fa-solid fa-xmark"></i></button>
                                </td>
                            </tr>
                            <tr class="bp-value-row">
                                <td>
                                    <input type="text" class="bp-form-control" name="values[1][name]"
                                        placeholder="e.g., Red, Large, 128GB">
                                </td>
                                <td class="bp-color-col" style="display: none;">
                                    <input type="color" class="bp-form-control bp-form-color" name="values[1][color_code]"
                                        value="#000000">
                                </td>
                                <td>
                                    <input type="number" class="bp-form-control" name="values[1][sort_order]"
                                        value="2" min="0">
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-flex justify-content-center mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" checked disabled>
                                    </div>
                                </td>
                                <td>
                                    <button type="button"
                                        class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger bp-remove-value"><i
                                            class="fa-solid fa-xmark"></i></button>
                                </td>
                            </tr>
                            <tr class="bp-value-row">
                                <td>
                                    <input type="text" class="bp-form-control" name="values[2][name]"
                                        placeholder="e.g., Red, Large, 128GB">
                                </td>
                                <td class="bp-color-col" style="display: none;">
                                    <input type="color" class="bp-form-control bp-form-color"
                                        name="values[2][color_code]" value="#000000">
                                </td>
                                <td>
                                    <input type="number" class="bp-form-control" name="values[2][sort_order]"
                                        value="3" min="0">
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-flex justify-content-center mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" checked disabled>
                                    </div>
                                </td>
                                <td>
                                    <button type="button"
                                        class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger bp-remove-value"><i
                                            class="fa-solid fa-xmark"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-4 text-end">
            <a href="{{ route('variants.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark"></i>
                Cancel</a>
            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save
                Attribute</button>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            var rowIndex = 3;

            // Toggle color column based on display type
            function toggleColorColumn() {
                var isColorSwatch = $('#displayType').val() === 'color_swatch';
                if (isColorSwatch) {
                    $('.bp-color-col').show();
                } else {
                    $('.bp-color-col').hide();
                }
            }

            $('#displayType').on('change', function() {
                toggleColorColumn();
            });

            // Initialize on load
            toggleColorColumn();

            // Add new value row
            $('#addValueRow').on('click', function() {
                var isColorSwatch = $('#displayType').val() === 'color_swatch';
                var colorDisplay = isColorSwatch ? '' : 'display: none;';
                var newRow = '<tr class="bp-value-row">' +
                    '<td><input type="text" class="bp-form-control" name="values[' + rowIndex +
                    '][name]" placeholder="e.g., Red, Large, 128GB"></td>' +
                    '<td class="bp-color-col" style="' + colorDisplay +
                    '"><input type="color" class="bp-form-control bp-form-color" name="values[' + rowIndex +
                    '][color_code]" value="#000000"></td>' +
                    '<td><input type="number" class="bp-form-control" name="values[' + rowIndex +
                    '][sort_order]" value="' + (rowIndex + 1) + '" min="0"></td>' +
                    '<td class="text-center"><div class="form-check form-switch d-inline-flex justify-content-center mb-0"><input class="form-check-input" type="checkbox" role="switch" checked disabled></div></td>' +
                    '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger bp-remove-value"><i class="fa-solid fa-xmark"></i></button></td>' +
                    '</tr>';
                $('#valuesBody').append(newRow);
                rowIndex++;
            });

            // Remove value row
            $(document).on('click', '.bp-remove-value', function() {
                var tbody = $('#valuesBody');
                if (tbody.find('.bp-value-row').length > 1) {
                    $(this).closest('.bp-value-row').remove();
                }
            });
        });
    </script>
@endpush
