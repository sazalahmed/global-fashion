@extends('core::layouts.master')

@section('title', 'Edit Salary Structure — ' . $structure->name)
@section('page-title', __("Edit Salary Structure"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>HR</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('payroll.salary-structures') }}">Salary Structures</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('payroll.salary-structures.show', $structure) }}">{{ $structure->name }}</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Edit</span>
@endsection

@section('page-actions')
  <a href="{{ route('payroll.salary-structures.show', $structure) }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back
  </a>
@endsection

@section('content')

  <form action="{{ route('payroll.salary-structures.update', $structure) }}" method="POST" id="structure-form">
    @csrf
    @method('PUT')

    <div class="row g-4">
      <div class="col-xl-8">
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-sitemap me-2"></i>Structure Information</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="bp-form-label">Structure Name *</label>
                <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $structure->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Code *</label>
                <input type="text" class="bp-form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $structure->code) }}" required maxlength="30">
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Status</label>
                <select class="bp-form-select w-100" name="is_active">
                  <option value="1" {{ old('is_active', $structure->is_active) ? 'selected' : '' }}>Active</option>
                  <option value="0" {{ !old('is_active', $structure->is_active) ? 'selected' : '' }}>Inactive</option>
                </select>
              </div>
              <div class="col-12">
                <label class="bp-form-label">Description</label>
                <textarea class="bp-form-control" name="description" rows="2">{{ old('description', $structure->description) }}</textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="bp-card mb-4">
          <div class="bp-card-header d-flex align-items-center justify-content-between">
            <h5 class="bp-card-title"><i class="fa-solid fa-puzzle-piece me-2"></i>Salary Components</h5>
            <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="btn-add-component">
              <i class="fa-solid fa-plus me-1"></i> Add Component
            </button>
          </div>
          <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
              <table class="bp-table" id="components-table">
                <thead>
                  <tr>
                    <th>Component Name</th>
                    <th>Type</th>
                    <th>Calculation</th>
                    <th>Amount / %</th>
                    <th>% Of</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="components-body"></tbody>
              </table>
            </div>
            <div class="p-3 text-center text-muted fs-13" id="no-components-msg" style="display:none">
              <i class="fa-solid fa-info-circle me-1"></i> No components. Click "Add Component" to add.
            </div>
          </div>
        </div>

        <div class="bp-card">
          <div class="bp-card-footer text-end">
            <a href="{{ route('payroll.salary-structures.show', $structure) }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Update Structure</button>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="bp-card bp-card-sticky">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>Preview Calculation</h5>
          </div>
          <div class="bp-card-body">
            <div class="mb-3">
              <label class="bp-form-label">Sample Basic Salary</label>
              <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" class="bp-form-control" id="sample-salary" value="30000" min="0" step="1000">
              </div>
            </div>
            <hr>
            <h6 class="fw-700 fs-12 text-uppercase text-muted mb-2">Earnings</h6>
            <div id="preview-earnings"></div>
            <div class="d-flex justify-content-between fs-13 fw-700 border-top pt-2 mt-2">
              <span>Total Earnings</span>
              <span id="preview-total-earnings">{{ currency_symbol() }} 30,000</span>
            </div>
            <hr>
            <h6 class="fw-700 fs-12 text-uppercase text-muted mb-2">Deductions</h6>
            <div id="preview-deductions"></div>
            <div class="d-flex justify-content-between fs-13 fw-700 border-top pt-2 mt-2 bp-text-danger">
              <span>Total Deductions</span>
              <span id="preview-total-deductions">{{ currency_symbol() }} 0</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fw-800 fs-14">
              <span>Net Salary</span>
              <span class="bp-text-success" id="preview-net">{{ currency_symbol() }} 30,000</span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </form>

@endsection

@push('scripts')
<script>
'use strict';

$(document).ready(function () {
    var componentIndex = 0;

    function formatBDT(amount) {
        var str = Math.round(amount).toString();
        var lastThree = str.substring(str.length - 3);
        var otherNumbers = str.substring(0, str.length - 3);
        if (otherNumbers !== '') { lastThree = ',' + lastThree; }
        return '{{ currency_symbol() }} ' + otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
    }

    function addComponentRow(data) {
        data = data || {};
        var idx = componentIndex;
        var row = '<tr data-index="' + idx + '">' +
            '<td><input type="text" class="bp-form-control bp-form-control-sm" name="components[' + idx + '][name]" value="' + (data.name || '') + '" placeholder="e.g., House Rent" required></td>' +
            '<td><select class="bp-form-select bp-form-select-sm component-type" name="components[' + idx + '][type]">' +
            '<option value="earning"' + (data.type === 'earning' || !data.type ? ' selected' : '') + '>Earning</option>' +
            '<option value="deduction"' + (data.type === 'deduction' ? ' selected' : '') + '>Deduction</option>' +
            '</select></td>' +
            '<td><select class="bp-form-select bp-form-select-sm component-calc" name="components[' + idx + '][calculation_type]">' +
            '<option value="fixed"' + (data.calculation_type === 'fixed' ? ' selected' : '') + '>Fixed</option>' +
            '<option value="percentage"' + (!data.calculation_type || data.calculation_type === 'percentage' ? ' selected' : '') + '>Percentage</option>' +
            '</select></td>' +
            '<td>' +
            '<input type="number" class="bp-form-control bp-form-control-sm component-amount" name="components[' + idx + '][amount]" value="' + (data.amount || '0') + '" min="0" step="0.01">' +
            '<input type="number" class="bp-form-control bp-form-control-sm component-percentage" name="components[' + idx + '][percentage]" value="' + (data.percentage || '0') + '" min="0" max="100" step="0.01">' +
            '</td>' +
            '<td><select class="bp-form-select bp-form-select-sm component-pct-of" name="components[' + idx + '][percentage_of]">' +
            '<option value="basic"' + (!data.percentage_of || data.percentage_of === 'basic' ? ' selected' : '') + '>Basic</option>' +
            '<option value="gross"' + (data.percentage_of === 'gross' ? ' selected' : '') + '>Gross</option>' +
            '</select></td>' +
            '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger btn-remove-component"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';

        $('#components-body').append(row);
        componentIndex++;
        toggleCalcFields();
        toggleNoComponentsMsg();
        updatePreview();
    }

    function toggleCalcFields() {
        $('#components-body tr').each(function () {
            var calcType = $(this).find('.component-calc').val();
            if (calcType === 'fixed') {
                $(this).find('.component-amount').show();
                $(this).find('.component-percentage').hide();
                $(this).find('.component-pct-of').closest('td').find('select').prop('disabled', true);
            } else {
                $(this).find('.component-amount').hide();
                $(this).find('.component-percentage').show();
                $(this).find('.component-pct-of').closest('td').find('select').prop('disabled', false);
            }
        });
    }

    function toggleNoComponentsMsg() {
        $('#no-components-msg').toggle($('#components-body tr').length === 0);
    }

    function updatePreview() {
        var basic = parseInt($('#sample-salary').val()) || 0;
        var earningsHtml = '<div class="d-flex justify-content-between fs-13 mb-1"><span class="text-muted">Basic Salary</span><span class="fw-600">' + formatBDT(basic) + '</span></div>';
        var deductionsHtml = '';
        var totalEarnings = basic;
        var totalDeductions = 0;

        $('#components-body tr').each(function () {
            var name = $(this).find('input[name$="[name]"]').val() || 'Unnamed';
            var type = $(this).find('.component-type').val();
            var calcType = $(this).find('.component-calc').val();
            var pctOf = $(this).find('.component-pct-of').val();
            var compAmount = 0;

            if (calcType === 'fixed') {
                compAmount = parseFloat($(this).find('.component-amount').val()) || 0;
            } else {
                var pct = parseFloat($(this).find('.component-percentage').val()) || 0;
                var base = pctOf === 'gross' ? totalEarnings : basic;
                compAmount = Math.round(base * pct / 100);
            }

            var line = '<div class="d-flex justify-content-between fs-13 mb-1"><span class="text-muted">' + $('<span>').text(name).html() + '</span><span class="fw-600' + (type === 'deduction' ? ' bp-text-danger' : '') + '">' + formatBDT(compAmount) + '</span></div>';

            if (type === 'earning') { earningsHtml += line; totalEarnings += compAmount; }
            else { deductionsHtml += line; totalDeductions += compAmount; }
        });

        $('#preview-earnings').html(earningsHtml);
        $('#preview-total-earnings').text(formatBDT(totalEarnings));
        if (!deductionsHtml) { deductionsHtml = '<div class="d-flex justify-content-between fs-13 mb-1 text-muted"><span>None</span><span>{{ currency_symbol() }} 0</span></div>'; }
        $('#preview-deductions').html(deductionsHtml);
        $('#preview-total-deductions').text(formatBDT(totalDeductions));
        $('#preview-net').text(formatBDT(totalEarnings - totalDeductions));
    }

    $('#btn-add-component').on('click', function () { addComponentRow(); });

    $(document).on('click', '.btn-remove-component', function () {
        $(this).closest('tr').remove();
        toggleNoComponentsMsg();
        updatePreview();
    });

    $(document).on('input change', '#components-body input, #components-body select, #sample-salary', function () {
        toggleCalcFields();
        updatePreview();
    });

    // Load existing components
    var existingComponents = {!! json_encode($structure->components->map(function($c) {
        return ['name' => $c->name, 'type' => $c->type, 'calculation_type' => $c->calculation_type, 'amount' => $c->amount, 'percentage' => $c->percentage, 'percentage_of' => $c->percentage_of];
    })->values()) !!};

    existingComponents.forEach(function (comp) {
        addComponentRow(comp);
    });

    toggleNoComponentsMsg();
    updatePreview();
});
</script>
@endpush
