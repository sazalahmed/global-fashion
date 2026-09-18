@extends('core::layouts.master')

@section('title', __('Edit Unit'))
@section('page-title', __('Edit Unit'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('units.index') }}">Units</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit Unit</span>
@endsection

@section('page-actions')
    <a href="{{ route('units.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Units
    </a>
@endsection

@section('content')

    <form action="{{ route('units.update', $unit) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-ruler-combined me-2"></i>Unit Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Unit Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            id="unitName" value="{{ old('name', $unit->name) }}" required placeholder="e.g., Kilogram">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Short Name *</label>
                        <input type="text" class="bp-form-control @error('short_name') is-invalid @enderror"
                            name="short_name" id="unitShortName" value="{{ old('short_name', $unit->short_name) }}" required
                            placeholder="e.g., Kg">
                        @error('short_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Status</label>
                        <select class="bp-form-select w-100 @error('status') is-invalid @enderror" name="status">
                            <option value="active" {{ old('status', $unit->status) == 'active' ? 'selected' : '' }}>Active
                            </option>
                            <option value="inactive" {{ old('status', $unit->status) == 'inactive' ? 'selected' : '' }}>
                                Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Unit Type *</label>
                        <div class="d-flex gap-4 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="unit_type" id="unitTypeBase"
                                    value="base" {{ old('unit_type', $unit->unit_type) == 'base' ? 'checked' : '' }}>
                                <label class="form-check-label fw-600 fs-13" for="unitTypeBase">Base Unit</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="unit_type" id="unitTypeSub"
                                    value="sub" {{ old('unit_type', $unit->unit_type) == 'sub' ? 'checked' : '' }}>
                                <label class="form-check-label fw-600 fs-13" for="unitTypeSub">Sub Unit</label>
                            </div>
                        </div>
                        @error('unit_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sub Unit Fields (hidden by default) -->
                    <div class="col-md-6 col-lg-4 bp-sub-unit-fields">
                        <label class="bp-form-label">Base Unit *</label>
                        <select class="bp-form-select w-100 @error('base_unit_id') is-invalid @enderror" name="base_unit_id"
                            id="baseUnitSelect">
                            <option value="">Select Base Unit</option>
                            @foreach ($baseUnits as $baseUnit)
                                <option value="{{ $baseUnit->id }}" data-short="{{ $baseUnit->short_name }}"
                                    {{ old('base_unit_id', $unit->base_unit_id) == $baseUnit->id ? 'selected' : '' }}>
                                    {{ $baseUnit->name }} ({{ $baseUnit->short_name }})</option>
                            @endforeach
                        </select>
                        @error('base_unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4 bp-sub-unit-fields">
                        <label class="bp-form-label">Conversion *</label>
                        <div class="bp-conversion-builder">
                            <span class="bp-conv-label">1</span>
                            <span class="bp-conv-label fw-700 text-primary"
                                id="convSubLabel">{{ old('short_name', $unit->short_name ?? '?') }}</span>
                            <span class="bp-conv-label">=</span>
                            <span class="bp-conv-label">1</span>
                            <span class="bp-conv-label fw-700 text-primary" id="convBaseLabel">base</span>
                            @php
                                $existingFactor = old('conversion_factor', $unit->conversion_factor);
                                $initOp = 'divide';
                                $initVal = '';
                                if ($existingFactor && $existingFactor > 0) {
                                    if ($existingFactor < 1) {
                                        $initOp = 'divide';
                                        $initVal = round(1 / $existingFactor, 6);
                                    } else {
                                        $initOp = 'multiply';
                                        $initVal = $existingFactor;
                                    }
                                }
                            @endphp
                            <select class="bp-form-select bp-conv-operator" id="convOperator">
                                <option value="divide" {{ $initOp === 'divide' ? 'selected' : '' }}>÷</option>
                                <option value="multiply" {{ $initOp === 'multiply' ? 'selected' : '' }}>×</option>
                            </select>
                            <input type="number" class="bp-form-control bp-conv-value" id="convValue" min="1"
                                step="any" placeholder="e.g., 1000" value="{{ $initVal }}">
                        </div>
                        <input type="hidden" name="conversion_factor" id="conversionFactor"
                            value="{{ old('conversion_factor', $unit->conversion_factor) }}">
                        <div class="fs-12 mt-2 p-2 rounded bp-conversion-preview" id="conversionPreview" hidden>
                            <i class="fa-solid fa-check-circle me-1 text-success"></i>
                            <span id="conversionText"></span>
                        </div>
                        @error('conversion_factor')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap mt-4 justify-content-between align-items-center">
            <button type="button" class="bp-btn bp-btn-danger" id="deleteUnitBtn">
                <i class="fa-solid fa-trash me-1"></i> Delete Unit
            </button>
            <div>
                <a href="{{ route('units.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark"></i>
                    Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Update
                    Unit</button>
            </div>
        </div>
    </form>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUnitModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Delete Unit
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong>{{ $unit->name }}</strong>? This action cannot be undone.
                    </p>
                    <p class="text-muted fs-12">Products using this unit will need to be reassigned.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
                    <form action="{{ route('units.destroy', $unit) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bp-btn bp-btn-danger"><i class="fa-solid fa-trash me-1"></i> Yes,
                            Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            var $subFields = $('.bp-sub-unit-fields');
            var $unitTypeRadios = $('input[name="unit_type"]');

            function toggleSubUnitFields() {
                var selectedType = $('input[name="unit_type"]:checked').val();
                if (selectedType === 'sub') {
                    $subFields.show();
                    $subFields.find('select, input').prop('required', true);
                } else {
                    $subFields.hide();
                    $subFields.find('select, input').prop('required', false);
                }
            }

            $unitTypeRadios.on('change', toggleSubUnitFields);
            toggleSubUnitFields();

            function updateConversionPreview() {
                var subName = $('#unitShortName').val().trim() || '?';
                var baseOpt = $('#baseUnitSelect option:selected');
                var baseName = baseOpt.data('short') || 'base';
                var op = $('#convOperator').val();
                var val = parseFloat($('#convValue').val());

                $('#convSubLabel').text(subName);
                $('#convBaseLabel').text(baseName);

                if (val > 0 && baseOpt.val()) {
                    var factor = (op === 'divide') ? (1 / val) : val;
                    $('#conversionFactor').val(factor);
                    $('#conversionPreview').removeAttr('hidden');

                    var safeSubName = $('<span>').text(subName).html();
                    var safeBaseName = $('<span>').text(baseName).html();
                    var displayFactor = factor < 1 ? factor.toFixed(6).replace(/0+$/, '').replace(/\.$/, '') :
                        factor;
                    $('#conversionText').html(
                        '<strong>1 ' + safeSubName + '</strong> = <strong>' + displayFactor + ' ' +
                        safeBaseName + '</strong>'
                    );
                } else {
                    $('#conversionFactor').val('');
                    $('#conversionPreview').attr('hidden', true);
                }
            }

            $('#baseUnitSelect, #convOperator, #convValue, #unitShortName').on('change input',
                updateConversionPreview);
            updateConversionPreview();

            // Delete unit confirmation
            $('#deleteUnitBtn').on('click', function() {
                var modal = new bootstrap.Modal(document.getElementById('deleteUnitModal'));
                modal.show();
            });
        });
    </script>
@endpush
