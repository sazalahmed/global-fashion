@extends('core::layouts.master')

@section('title', __('Edit Variant Attribute'))
@section('page-title', __('Edit Variant Attribute'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('variants.index') }}">Variants</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit</span>
@endsection

@section('page-actions')
    <a href="{{ route('variants.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Variants
    </a>
@endsection

@section('content')

    <form action="{{ route('variants.update', $attribute) }}" method="POST">
        @csrf
        @method('PUT')

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
                            value="{{ old('name', $attribute->name) }}" required placeholder="e.g., Color, Size, Material">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Display Type *</label>
                        <select class="bp-form-select w-100 @error('display_type') is-invalid @enderror" name="display_type"
                            id="displayType" required>
                            {{-- Legacy 'dropdown'/'radio' attributes fall back to Button (the
                                 default), matching how the storefront already renders them. --}}
                            <option value="button"
                                {{ old('display_type', $attribute->display_type) !== 'color_swatch' ? 'selected' : '' }}>
                                Button
                            </option>
                            <option value="color_swatch"
                                {{ old('display_type', $attribute->display_type) == 'color_swatch' ? 'selected' : '' }}>
                                Color Swatch</option>
                        </select>
                        @error('display_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Status *</label>
                        <select class="bp-form-select w-100 @error('status') is-invalid @enderror" name="status" required>
                            <option value="active" {{ old('status', $attribute->status) == 'active' ? 'selected' : '' }}>
                                Active</option>
                            <option value="inactive"
                                {{ old('status', $attribute->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                <th class="bp-color-col">Color Code</th>
                                <th>Sort Order</th>
                                <th>Status</th>
                                <th>delete</th>
                            </tr>
                        </thead>
                        <tbody id="valuesBody">
                            @foreach ($attribute->values as $index => $value)
                                <tr class="bp-value-row">
                                    <td>
                                        <input type="hidden" name="values[{{ $index }}][id]"
                                            value="{{ $value->id }}">
                                        <input type="text" class="bp-form-control"
                                            name="values[{{ $index }}][name]"
                                            value="{{ old('values.' . $index . '.name', $value->value) }}">
                                    </td>
                                    <td class="bp-color-col">
                                        <input type="color" class="bp-form-control bp-form-color"
                                            name="values[{{ $index }}][color_code]"
                                            value="{{ old('values.' . $index . '.color_code', $value->color_code ?? '#000000') }}">
                                    </td>
                                    <td>
                                        <input type="number" class="bp-form-control"
                                            name="values[{{ $index }}][sort_order]"
                                            value="{{ old('values.' . $index . '.sort_order', $value->sort_order) }}"
                                            min="0">
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-flex justify-content-center mb-0">
                                            <input class="form-check-input bp-value-active-cb" type="checkbox"
                                                role="switch" data-value-id="{{ $value->id }}"
                                                {{ $value->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button"
                                            class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger bp-remove-value"><i
                                                class="fa-solid fa-xmark"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{-- Measurement Chart (used by storefront Size Guide for Size attribute) --}}
        <div class="bp-card mt-4">
            <div class="bp-card-header d-flex align-items-center justify-content-between">
                <h5 class="bp-card-title mb-0"><i class="fa-solid fa-table-list me-2"></i>Measurement Chart</h5>
                <span class="fs-12 text-muted">Columns are the attribute values above. Rows are measurements (Chest, Waist,
                    …).
                    Cells save as you type. Shown on storefront as <strong>Size Guide</strong> when this is the Size
                    attribute.</span>
            </div>
            <div class="bp-card-body">
                @if ($attribute->values->isEmpty())
                    <div class="alert alert-warning mb-0">Add at least one attribute value above first.</div>
                @else
                    <div class="bp-size-chart-wrap table-responsive">
                        <table class="bp-size-chart-table" id="bp-chart-table">
                            <thead>
                                <tr>
                                    <th class="bp-sc-row-head">Measurement</th>
                                    @foreach ($attribute->values as $val)
                                        <th data-value-id="{{ $val->id }}" class="bp-sc-size-head">
                                            {{ $val->value }}
                                        </th>
                                    @endforeach
                                    <th class="bp-sc-row-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($chartRows as $row)
                                    <tr data-row-id="{{ $row->id }}"
                                        class="{{ $row->is_active ? '' : 'is-disabled' }}">
                                        <th class="bp-sc-row-head">
                                            <i class="fa-solid fa-grip-vertical bp-sc-row-drag"></i>
                                            <input type="text" class="bp-sc-row-label-input"
                                                value="{{ $row->label }}" maxlength="64">
                                        </th>
                                        @foreach ($attribute->values as $val)
                                            <td>
                                                <input type="text" class="bp-sc-cell-input"
                                                    data-row-id="{{ $row->id }}"
                                                    data-value-id="{{ $val->id }}"
                                                    value="{{ $chartCells[$row->id][$val->id] ?? '' }}" placeholder="—"
                                                    maxlength="64">
                                            </td>
                                        @endforeach
                                        <td class="bp-sc-row-actions">
                                            <label class="bp-size-toggle" title="Enable / disable">
                                                <input type="checkbox" class="bp-sc-row-active-cb"
                                                    {{ $row->is_active ? 'checked' : '' }}>
                                                <span class="bp-size-toggle-track"></span>
                                            </label>
                                            <button type="button" class="bp-sc-row-remove" title="Delete row"><i
                                                    class="fa-solid fa-xmark"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bp-sc-empty-row">
                                        <td colspan="{{ $attribute->values->count() + 2 }}"
                                            class="text-center text-muted py-3">No measurement rows yet — add one below.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <input type="text" id="bp-new-row-label" class="bp-form-control"
                            placeholder="e.g. Chest, Waist, Length" maxlength="64" style="max-width:280px;">
                        <button type="button" id="bp-add-row-btn" class="bp-btn bp-btn-primary bp-btn-sm"><i
                                class="fa-solid fa-plus me-1"></i> Add Measurement</button>
                    </div>
                @endif
            </div>
        </div>
        <div class="mt-4 text-end">
            <a href="{{ route('variants.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark"></i>
                Cancel</a>
            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Update
                Attribute</button>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            var rowIndex = {{ $attribute->values->count() }};

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
                    '<td><div class="form-check form-switch d-inline-flex justify-content-center mb-0"><input class="form-check-input" type="checkbox" role="switch" checked disabled></div></td>' +
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

            // ── Measurement chart editor ─────────────────────────────────────
            var urls = {
                rowsStore: '{{ route('variants.chart.rows.store', $attribute) }}',
                rowsUpdate: '{{ route('variants.chart.rows.update', [$attribute, '__ID__']) }}',
                rowsDestroy: '{{ route('variants.chart.rows.destroy', [$attribute, '__ID__']) }}',
                rowsReorder: '{{ route('variants.chart.rows.reorder', $attribute) }}',
                valuesUpsert: '{{ route('variants.chart.values.upsert', $attribute) }}',
            };

            function url(t, id) {
                return t.replace('__ID__', id);
            }

            function ajaxErr(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not save.';
                if (typeof window.bpToast === 'function') window.bpToast(msg, 'error');
                else alert(msg);
            }

            var $tableBody = $('#bp-chart-table tbody');

            function appendRow(r) {
                $tableBody.find('.bp-sc-empty-row').remove();
                var valueIds = $('#bp-chart-table thead th[data-value-id]').map(function() {
                    return $(this).data('value-id');
                }).get();
                var html = '<tr data-row-id="' + r.id + '" class="' + (r.is_active ? '' : 'is-disabled') + '">' +
                    '<th class="bp-sc-row-head">' +
                    '<i class="fa-solid fa-grip-vertical bp-sc-row-drag"></i>' +
                    '<input type="text" class="bp-sc-row-label-input" value="' + $('<div>').text(r.label).html() +
                    '" maxlength="64">' +
                    '</th>';
                valueIds.forEach(function(vid) {
                    html += '<td><input type="text" class="bp-sc-cell-input" data-row-id="' + r.id +
                        '" data-value-id="' + vid + '" value="" placeholder="—" maxlength="64"></td>';
                });
                html += '<td class="bp-sc-row-actions">' +
                    '<label class="bp-size-toggle" title="Enable / disable">' +
                    '<input type="checkbox" class="bp-sc-row-active-cb" checked>' +
                    '<span class="bp-size-toggle-track"></span>' +
                    '</label>' +
                    '<button type="button" class="bp-sc-row-remove" title="Delete row"><i class="fa-solid fa-xmark"></i></button>' +
                    '</td></tr>';
                // Note: leave draggable=false here. Handle-only DnD arms draggable on
                // mousedown over the grip icon and disarms it on dragend.
                $tableBody.append($(html));
            }

            $('#bp-add-row-btn').on('click', function() {
                var label = ($('#bp-new-row-label').val() || '').trim();
                if (!label) return;
                $.post(urls.rowsStore, {
                        label: label
                    })
                    .done(function(r) {
                        $('#bp-new-row-label').val('');
                        appendRow(r);
                    })
                    .fail(ajaxErr);
            });
            $('#bp-new-row-label').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#bp-add-row-btn').trigger('click');
                }
            });

            $tableBody.on('change', '.bp-sc-row-label-input', function() {
                var $tr = $(this).closest('tr');
                var id = $tr.data('row-id');
                var label = $(this).val().trim();
                if (!label) return;
                $.ajax({
                    url: url(urls.rowsUpdate, id),
                    method: 'PUT',
                    data: {
                        label: label
                    }
                }).fail(ajaxErr);
            });

            $tableBody.on('change', '.bp-sc-row-active-cb', function() {
                var $tr = $(this).closest('tr');
                var id = $tr.data('row-id');
                var active = $(this).is(':checked') ? 1 : 0;
                $tr.toggleClass('is-disabled', !active);
                $.ajax({
                    url: url(urls.rowsUpdate, id),
                    method: 'PUT',
                    data: {
                        is_active: active
                    }
                }).fail(ajaxErr);
            });

            $tableBody.on('click', '.bp-sc-row-remove', function() {
                var $tr = $(this).closest('tr');
                if (!confirm('Delete this measurement row?')) return;
                var id = $tr.data('row-id');
                $.ajax({
                        url: url(urls.rowsDestroy, id),
                        method: 'DELETE'
                    })
                    .done(function() {
                        $tr.remove();
                        if ($tableBody.find('tr').length === 0) {
                            var cols = $('#bp-chart-table thead th[data-value-id]').length + 2;
                            $tableBody.append('<tr class="bp-sc-empty-row"><td colspan="' + cols +
                                '" class="text-center text-muted py-3">No measurement rows yet — add one below.</td></tr>'
                            );
                        }
                    })
                    .fail(ajaxErr);
            });

            (function() {
                var dragEl = null;
                var armedTr = null;

                $tableBody.on('mousedown', '.bp-sc-row-drag', function() {
                    armedTr = $(this).closest('tr')[0];
                    if (armedTr) armedTr.draggable = true;
                });

                // If the user mousedowns the handle but mouseups without dragging,
                // disarm so the row doesn't stay draggable and steal subsequent clicks.
                $(document).on('mouseup', function() {
                    if (armedTr && armedTr !== dragEl) {
                        armedTr.draggable = false;
                        armedTr = null;
                    }
                });

                $tableBody.on('dragstart', 'tr[data-row-id]', function(e) {
                    dragEl = this;
                    $(this).addClass('is-dragging');
                    if (e.originalEvent && e.originalEvent.dataTransfer) {
                        e.originalEvent.dataTransfer.effectAllowed = 'move';
                        e.originalEvent.dataTransfer.setData('text/plain', '');
                    }
                });

                $tableBody.on('dragover', 'tr[data-row-id]', function(e) {
                    if (!dragEl || dragEl === this) return;
                    e.preventDefault();
                    var rect = this.getBoundingClientRect();
                    var before = (e.originalEvent.clientY || e.clientY) < rect.top + rect.height / 2;
                    // Native DOM insert — jQuery's $(parent).insertBefore(child, target)
                    // is NOT the same as Node.insertBefore and throws HierarchyRequestError.
                    if (before) this.parentNode.insertBefore(dragEl, this);
                    else this.parentNode.insertBefore(dragEl, this.nextSibling);
                });

                $tableBody.on('dragend', 'tr[data-row-id]', function() {
                    $(this).removeClass('is-dragging');
                    this.draggable = false;
                    var moved = (dragEl === this);
                    dragEl = null;
                    armedTr = null;
                    if (!moved) return;
                    var ids = $tableBody.find('tr[data-row-id]').map(function() {
                        return $(this).data('row-id');
                    }).get();
                    $.post(urls.rowsReorder, {
                        ordered_ids: ids
                    }).fail(ajaxErr);
                });
            })();

            // Cell value upsert on blur / Enter.
            var savingCells = {};

            function saveCell($input) {
                var rowId = $input.data('row-id');
                var valueId = $input.data('value-id');
                var key = rowId + ':' + valueId;
                var value = $input.val();
                if (savingCells[key] === value) return;
                savingCells[key] = value;
                $.post(urls.valuesUpsert, {
                        row_id: rowId,
                        value_id: valueId,
                        value: value
                    })
                    .fail(function(xhr) {
                        delete savingCells[key];
                        ajaxErr(xhr);
                    });
            }
            $tableBody.on('blur', '.bp-sc-cell-input', function() {
                saveCell($(this));
            });
            $tableBody.on('keydown', '.bp-sc-cell-input', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $(this).trigger('blur');
                }
            });

            // Per-value active/inactive toggle
            $(document).on('change', '.bp-value-active-cb', function() {
                var $cb = $(this);
                var id = $cb.data('value-id');
                $.ajax({
                    url: '{{ route('variants.values.active', ['value' => ':vid']) }}'.replace(
                        ':vid', id),
                    method: 'PATCH',
                    data: {
                        is_active: this.checked ? 1 : 0
                    },
                    error: function() {
                        alert('Failed to update value status.');
                        $cb.prop('checked', !$cb.prop('checked'));
                    }
                });
            });
        });
    </script>
@endpush
