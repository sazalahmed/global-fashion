@extends('core::layouts.master')

@section('title', __('Section Combos — :title', ['title' => $section->title]))
@section('page-title', __('Section Combos — :title', ['title' => $section->title]))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.homepage-sections') }}">Manage Sections</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $section->title }}</span>
@endsection

@section('content')

@php
  $placeholder = asset('website/assets/images/product_placeholder.png');
@endphp

<form action="{{ route('ecommerce.homepage-sections.combos.update', $section) }}" method="POST" id="sectionCombosForm">
  @csrf

  <div class="row g-4">
    <div class="col-xl-8">

      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>Add Combo Packages</h5>
        </div>
        <div class="bp-card-body">
          <div class="d-flex gap-2 flex-wrap">
            <select class="bp-form-select" id="comboPicker">
              <option value="">{{ __('Select a combo to add…') }}</option>
              @foreach($combos as $c)
                <option value="{{ $c->id }}" data-name="{{ $c->name }}">{{ $c->name }}</option>
              @endforeach
            </select>
            <button type="button" class="bp-btn bp-btn-outline" id="addComboBtn"><i class="fa-solid fa-plus me-1"></i>{{ __('Add') }}</button>
          </div>
          @error('combos')
            <div class="text-danger fs-12 mt-2">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="bp-card">
        <div class="bp-card-header d-flex justify-content-between align-items-center">
          <h5 class="bp-card-title"><i class="fa-solid fa-box-open me-2"></i>Curated Combos</h5>
          <div class="d-flex align-items-center gap-2">
            <span class="fs-12 text-muted"><i class="fa-solid fa-arrows-up-down me-1"></i>Drag to reorder</span>
            <span class="bp-badge bp-badge-primary" id="comboCountBadge">{{ $section->combos->count() }}</span>
          </div>
        </div>
        <div class="bp-card-body">
          <div class="text-center text-muted py-3 {{ $section->combos->count() ? 'd-none' : '' }}" id="noCombosMsg">
            {{ __('No combos picked yet — this section stays hidden until you add some.') }}
          </div>

          <div class="bp-table-wrapper {{ $section->combos->count() ? '' : 'd-none' }}" id="sectionCombosTable">
            <table class="bp-table">
              <thead>
                <tr>
                  <th class="bp-reorder-col" title="Drag to reorder">&nbsp;</th>
                  <th>Combo</th>
                  <th width="60">Remove</th>
                </tr>
              </thead>
              <tbody id="sectionCombosBody">
                @foreach($section->combos as $i => $combo)
                  <tr data-combo-id="{{ $combo->id }}" draggable="true">
                    <td class="bp-drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <img src="{{ upload_url($combo->thumbnail, $placeholder) }}" alt="" class="product-thumb" onerror="this.onerror=null;this.src='{{ $placeholder }}'">
                        <div class="fw-700 fs-13">{{ $combo->name }}</div>
                      </div>
                      <input type="hidden" name="combos[{{ $i }}][combo_id]" value="{{ $combo->id }}">
                      <input type="hidden" class="row-sort-order" name="combos[{{ $i }}][sort_order]" value="{{ $i }}">
                    </td>
                    <td>
                      <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-section-combo"><i class="fa-solid fa-times"></i></button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    <div class="col-xl-4">
      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>How It Works</h5>
        </div>
        <div class="bp-card-body">
          <ul class="list-unstyled mb-0 fs-13">
            <li class="mb-2"><i class="fa-solid fa-hand-pointer text-muted me-2"></i>Pick the combo packages to feature in the <strong>{{ $section->title }}</strong> row.</li>
            <li class="mb-2"><i class="fa-solid fa-up-down-left-right text-muted me-2"></i><strong>Drag</strong> a row by its handle to set the display order.</li>
            <li class="mb-0"><i class="fa-solid fa-eye-slash text-muted me-2"></i>An empty list keeps this section hidden on the storefront.</li>
          </ul>
        </div>
      </div>

      <div class="bp-card">
        <div class="bp-card-body d-flex flex-column gap-2">
          <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
            <i class="fa-solid fa-save me-2"></i> {{ __('Save Combos') }}
          </button>
          <a href="{{ route('ecommerce.homepage-sections') }}" class="bp-btn bp-btn-outline w-100 justify-content-center">
            <i class="fa-solid fa-times me-2"></i> {{ __('Back') }}
          </a>
        </div>
      </div>
    </div>
  </div>

</form>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    var comboIndex = {{ $section->combos->count() }};
    var selected = {};
    var placeholder = '{{ $placeholder }}';

    $('#sectionCombosBody tr').each(function () {
        selected[$(this).data('combo-id')] = true;
    });

    // Hide already-curated combos from the picker dropdown.
    function refreshPicker() {
        $('#comboPicker option').each(function () {
            var val = $(this).val();
            if (!val) return;
            $(this).prop('disabled', !!selected[val]).toggle(!selected[val]);
        });
        $('#comboPicker').val('');
    }
    refreshPicker();

    $('#addComboBtn').on('click', function () {
        var opt = $('#comboPicker option:selected');
        var id = opt.val();
        if (!id || selected[id]) return;
        selected[id] = true;

        var name = $('<span>').text(opt.data('name') || '').html();
        var i = comboIndex;
        var row = '<tr data-combo-id="' + id + '" draggable="true">' +
            '<td class="bp-drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></td>' +
            '<td><div class="d-flex align-items-center gap-2">' +
                '<img src="' + placeholder + '" alt="" class="product-thumb">' +
                '<div class="fw-700 fs-13">' + name + '</div>' +
            '</div>' +
            '<input type="hidden" name="combos[' + i + '][combo_id]" value="' + id + '">' +
            '<input type="hidden" class="row-sort-order" name="combos[' + i + '][sort_order]" value="' + i + '"></td>' +
            '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-section-combo"><i class="fa-solid fa-times"></i></button></td>' +
            '</tr>';

        $('#sectionCombosBody').append(row);
        $('#noCombosMsg').addClass('d-none');
        $('#sectionCombosTable').removeClass('d-none');
        comboIndex++;
        refreshPicker();
        updateCount();
    });

    $(document).on('click', '.remove-section-combo', function () {
        var row = $(this).closest('tr');
        delete selected[row.data('combo-id')];
        row.remove();
        if ($('#sectionCombosBody tr').length === 0) {
            $('#noCombosMsg').removeClass('d-none');
            $('#sectionCombosTable').addClass('d-none');
        }
        refreshPicker();
        updateCount();
    });

    function updateCount() {
        $('#comboCountBadge').text($('#sectionCombosBody tr').length);
    }

    // ── Drag-and-drop ordering ──
    var body = document.getElementById('sectionCombosBody');
    var dragEl = null;
    body.addEventListener('dragstart', function (e) {
        var tr = e.target.closest('tr');
        if (!tr) return;
        dragEl = tr;
        tr.classList.add('bp-dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    body.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!dragEl) return;
        var tr = e.target.closest('tr');
        if (!tr || tr === dragEl) return;
        var rect = tr.getBoundingClientRect();
        var after = e.clientY > rect.top + rect.height / 2;
        body.insertBefore(dragEl, after ? tr.nextSibling : tr);
    });
    body.addEventListener('dragend', function () {
        if (dragEl) dragEl.classList.remove('bp-dragging');
        dragEl = null;
        renumber();
    });

    function renumber() {
        $('#sectionCombosBody tr').each(function (idx) {
            $(this).find('.row-sort-order').val(idx);
        });
    }
    $('#sectionCombosForm').on('submit', renumber);
});
</script>
@endpush
