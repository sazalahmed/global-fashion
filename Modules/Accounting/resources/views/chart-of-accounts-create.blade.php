@extends('core::layouts.master')

@section('title', __("Add Account"))
@section('page-title', __("Add Account"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.chart-of-accounts') }}">Chart of Accounts</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Add Account</span>
@endsection

@section('page-actions')
<a href="{{ route('accounting.chart-of-accounts') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Chart of Accounts
</a>
@endsection

@section('content')

<form action="{{ route('accounting.chart-of-accounts.store') }}" method="POST" id="accountForm">
  @csrf

  <!-- Account Information -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Account Information</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="bp-form-label">Account Code *</label>
          <input type="text" class="bp-form-control" name="account_code" value="{{ old('account_code') }}" placeholder="e.g. 1001" required>
          @error('account_code')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
          <div class="fs-11 text-muted mt-1">Unique code for this account</div>
        </div>
        <div class="col-md-5">
          <label class="bp-form-label">Account Name *</label>
          <input type="text" class="bp-form-control" name="account_name" value="{{ old('account_name') }}" placeholder="e.g. Cash in Hand" required>
          @error('account_name')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Account Type *</label>
          <select class="bp-form-select w-100" name="account_type" id="accountType" required>
            <option value="">Select Type</option>
            <option value="asset" {{ old('account_type') === 'asset' ? 'selected' : '' }}>Asset</option>
            <option value="liability" {{ old('account_type') === 'liability' ? 'selected' : '' }}>Liability</option>
            <option value="equity" {{ old('account_type') === 'equity' ? 'selected' : '' }}>Equity</option>
            <option value="revenue" {{ old('account_type') === 'revenue' ? 'selected' : '' }}>Revenue</option>
            <option value="expense" {{ old('account_type') === 'expense' ? 'selected' : '' }}>Expense</option>
          </select>
          @error('account_type')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Sub-Type *</label>
          <select class="bp-form-select w-100" name="sub_type" id="subType" required>
            <option value="">Select Sub-Type</option>
          </select>
          @error('sub_type')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Parent Account</label>
          <select class="bp-form-select w-100" name="parent_account" id="parentAccount">
            <option value="">None (Top-level Account)</option>
            <optgroup label="Assets">
              <option value="1000" {{ old('parent_account') === '1000' ? 'selected' : '' }}>1000 — Current Assets</option>
              <option value="1500" {{ old('parent_account') === '1500' ? 'selected' : '' }}>1500 — Fixed Assets</option>
            </optgroup>
            <optgroup label="Liabilities">
              <option value="2000" {{ old('parent_account') === '2000' ? 'selected' : '' }}>2000 — Current Liabilities</option>
              <option value="2500" {{ old('parent_account') === '2500' ? 'selected' : '' }}>2500 — Long-term Liabilities</option>
            </optgroup>
            <optgroup label="Equity">
              <option value="3000" {{ old('parent_account') === '3000' ? 'selected' : '' }}>3000 — Equity</option>
            </optgroup>
            <optgroup label="Revenue">
              <option value="4000" {{ old('parent_account') === '4000' ? 'selected' : '' }}>4000 — Revenue</option>
            </optgroup>
            <optgroup label="Expenses">
              <option value="5000" {{ old('parent_account') === '5000' ? 'selected' : '' }}>5000 — Operating Expenses</option>
              <option value="5500" {{ old('parent_account') === '5500' ? 'selected' : '' }}>5500 — Non-operating Expenses</option>
            </optgroup>
          </select>
          @error('parent_account')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Status *</label>
          <select class="bp-form-select w-100" name="status" required>
            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
          </select>
          @error('status')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  <!-- Opening Balance -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Opening Balance</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="bp-form-label">Opening Balance ({{ currency_symbol() }})</label>
          <input type="number" class="bp-form-control" name="opening_balance" value="{{ old('opening_balance', 0) }}" placeholder="0" min="0" step="0.01">
          @error('opening_balance')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
          <div class="fs-11 text-muted mt-1">Enter the starting balance for this account</div>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Balance Type</label>
          <select class="bp-form-select w-100" name="balance_type" id="balanceType">
            <option value="debit" {{ old('balance_type', 'debit') === 'debit' ? 'selected' : '' }}>Debit</option>
            <option value="credit" {{ old('balance_type') === 'credit' ? 'selected' : '' }}>Credit</option>
          </select>
          @error('balance_type')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
          <div class="fs-11 text-muted mt-1" id="balanceHint">Assets &amp; Expenses are normally Debit; Liabilities, Equity &amp; Revenue are normally Credit</div>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">As of Date</label>
          <input type="date" class="bp-form-control" name="as_of_date" value="{{ old('as_of_date', date('Y-m-d')) }}">
          @error('as_of_date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  <!-- Description -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-align-left me-2"></i>Additional Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-12">
          <label class="bp-form-label">Description / Notes</label>
          <textarea class="bp-form-control" name="description" rows="3" placeholder="Optional description for this account...">{{ old('description') }}</textarea>
          @error('description')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="d-flex justify-content-end gap-2 mt-3">
    <a href="{{ route('accounting.chart-of-accounts') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
    <button type="submit" name="action" value="save_add" class="bp-btn bp-btn-outline"><i class="fa-solid fa-plus me-1"></i> Save &amp; Add Another</button>
    <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Account</button>
  </div>

</form>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    var subTypeOptions = {
        asset: [
            { value: 'current_asset', label: 'Current Asset' },
            { value: 'fixed_asset', label: 'Fixed Asset' },
            { value: 'other_asset', label: 'Other Asset' }
        ],
        liability: [
            { value: 'current_liability', label: 'Current Liability' },
            { value: 'long_term_liability', label: 'Long-term Liability' },
            { value: 'other_liability', label: 'Other Liability' }
        ],
        equity: [
            { value: 'equity', label: 'Equity' },
            { value: 'contra_equity', label: 'Contra Equity' }
        ],
        revenue: [
            { value: 'operating_revenue', label: 'Operating Revenue' },
            { value: 'contra_revenue', label: 'Contra Revenue' },
            { value: 'other_income', label: 'Other Income' }
        ],
        expense: [
            { value: 'direct_cost', label: 'Direct Cost (COGS)' },
            { value: 'operating_expense', label: 'Operating Expense' },
            { value: 'non_operating_expense', label: 'Non-operating Expense' }
        ]
    };

    var defaultBalanceType = {
        asset: 'debit',
        liability: 'credit',
        equity: 'credit',
        revenue: 'credit',
        expense: 'debit'
    };

    // Update sub-type options when account type changes
    $('#accountType').on('change', function () {
        var type = $(this).val();
        var $subType = $('#subType');
        $subType.html('<option value="">Select Sub-Type</option>');

        if (type && subTypeOptions[type]) {
            $.each(subTypeOptions[type], function (i, opt) {
                $subType.append('<option value="' + opt.value + '">' + opt.label + '</option>');
            });
        }

        // Auto-set balance type
        if (type && defaultBalanceType[type]) {
            $('#balanceType').val(defaultBalanceType[type]);
        }
    });

    // Restore sub-type on page load if old value exists
    var oldType = '{{ old('account_type', '') }}';
    var oldSubType = '{{ old('sub_type', '') }}';
    if (oldType) {
        $('#accountType').trigger('change');
        if (oldSubType) {
            $('#subType').val(oldSubType);
        }
    }
});
</script>
@endpush
