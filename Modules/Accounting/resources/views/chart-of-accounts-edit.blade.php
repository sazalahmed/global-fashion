@extends('core::layouts.master')

@section('title', __("Edit Account"))
@section('page-title', __("Edit Account"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.chart-of-accounts') }}">Chart of Accounts</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit Account</span>
@endsection

@section('page-actions')
<a href="{{ route('accounting.chart-of-accounts') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Chart of Accounts
</a>
@endsection

@section('content')

<form action="{{ route('accounting.chart-of-accounts.update', $account) }}" method="POST" id="accountForm">
  @csrf
  @method('PUT')

  <!-- Account Information -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Account Information</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="bp-form-label">Account Code *</label>
          <input type="text" class="bp-form-control" name="account_code" value="{{ old('account_code', $account->account_code) }}" readonly>
          @error('account_code')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
          <div class="fs-11 text-muted mt-1">Account code cannot be changed</div>
        </div>
        <div class="col-md-5">
          <label class="bp-form-label">Account Name *</label>
          <input type="text" class="bp-form-control" name="account_name" value="{{ old('account_name', $account->account_name) }}" required>
          @error('account_name')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Account Type *</label>
          <select class="bp-form-select w-100" name="account_type" id="accountType" required>
            <option value="">Select Type</option>
            @foreach(['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense'] as $typeValue => $typeLabel)
              <option value="{{ $typeValue }}" {{ old('account_type', $account->account_type) === $typeValue ? 'selected' : '' }}>{{ $typeLabel }}</option>
            @endforeach
          </select>
          @error('account_type')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Sub-Type *</label>
          <select class="bp-form-select w-100" name="sub_type" id="subType" required>
            <option value="">Select Sub-Type</option>
            @php $currentType = old('account_type', $account->account_type); @endphp
            @if($currentType && isset($subTypes[$currentType]))
              @foreach($subTypes[$currentType] as $subValue => $subLabel)
                <option value="{{ $subValue }}" {{ old('sub_type', $account->sub_type) === $subValue ? 'selected' : '' }}>{{ $subLabel }}</option>
              @endforeach
            @endif
          </select>
          @error('sub_type')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Parent Account</label>
          <select class="bp-form-select w-100" name="parent_id" id="parentAccount">
            <option value="">None (Top-level Account)</option>
            @foreach($parentAccounts as $groupType => $accounts)
              <optgroup label="{{ ucfirst($groupType) }}">
                @foreach($accounts as $parentAccount)
                  @if($parentAccount->id !== $account->id)
                    <option value="{{ $parentAccount->id }}" {{ old('parent_id', $account->parent_id) == $parentAccount->id ? 'selected' : '' }}>
                      {{ $parentAccount->account_code }} — {{ $parentAccount->account_name }}
                    </option>
                  @endif
                @endforeach
              </optgroup>
            @endforeach
          </select>
          @error('parent_id')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Status *</label>
          <select class="bp-form-select w-100" name="status" required>
            <option value="active" {{ old('status', $account->status) === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $account->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
          <input type="number" class="bp-form-control" name="opening_balance" value="{{ old('opening_balance', num_input($account->opening_balance)) }}" placeholder="0" min="0" step="0.01">
          @error('opening_balance')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
          <div class="fs-11 text-muted mt-1">Current opening balance: {{ $account->formatted_balance }}</div>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Balance Type</label>
          <select class="bp-form-select w-100" name="opening_balance_type" id="balanceType">
            <option value="debit" {{ old('opening_balance_type', $account->opening_balance_type) === 'debit' ? 'selected' : '' }}>Debit</option>
            <option value="credit" {{ old('opening_balance_type', $account->opening_balance_type) === 'credit' ? 'selected' : '' }}>Credit</option>
          </select>
          @error('opening_balance_type')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
          <div class="fs-11 text-muted mt-1" id="balanceHint">Assets &amp; Expenses are normally Debit; Liabilities, Equity &amp; Revenue are normally Credit</div>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">As of Date</label>
          <input type="date" class="bp-form-control" name="opening_balance_date" value="{{ old('opening_balance_date', $account->opening_balance_date?->format('Y-m-d')) }}">
          @error('opening_balance_date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  <!-- Bank Account Details -->
  <div class="bp-card mb-4" id="bankDetailsCard">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-building-columns me-2"></i>Bank Account Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-12">
          <div class="bp-form-check">
            <input type="hidden" name="is_bank_account" value="0">
            <input type="checkbox" class="bp-form-check-input" name="is_bank_account" id="isBankAccount" value="1" {{ old('is_bank_account', $account->is_bank_account) ? 'checked' : '' }}>
            <label class="bp-form-check-label" for="isBankAccount">This is a bank / mobile banking account</label>
          </div>
        </div>
        <div id="bankFields" class="{{ old('is_bank_account', $account->is_bank_account) ? '' : 'd-none' }}">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="bp-form-label">Bank Name</label>
              <input type="text" class="bp-form-control" name="bank_name" value="{{ old('bank_name', $account->bank_name) }}" placeholder="e.g. BRAC Bank">
              @error('bank_name')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Account Number</label>
              <input type="text" class="bp-form-control" name="bank_account_number" value="{{ old('bank_account_number', $account->bank_account_number) }}" placeholder="e.g. 1234567890">
              @error('bank_account_number')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Branch</label>
              <input type="text" class="bp-form-control" name="bank_branch" value="{{ old('bank_branch', $account->bank_branch) }}" placeholder="e.g. Dhanmondi Branch">
              @error('bank_branch')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
          </div>
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
          <textarea class="bp-form-control" name="description" rows="3" placeholder="Optional description for this account...">{{ old('description', $account->description) }}</textarea>
          @error('description')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="bp-card">
    <div class="bp-card-footer d-flex justify-content-between">
      <button type="button" class="bp-btn bp-btn-danger" id="deleteAccountBtn">
        <i class="fa-solid fa-trash me-1"></i> Delete Account
      </button>
      <div class="d-flex gap-2">
        <a href="{{ route('accounting.chart-of-accounts') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Update Account</button>
      </div>
    </div>
  </div>

</form>

<!-- Delete Form (hidden) -->
<form action="{{ route('accounting.chart-of-accounts.destroy', $account) }}" method="POST" id="deleteAccountForm">
  @csrf
  @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    var subTypeOptions = @json($subTypes);

    var defaultBalanceType = {
        asset: 'debit',
        liability: 'credit',
        equity: 'credit',
        revenue: 'credit',
        expense: 'debit'
    };

    function populateSubTypes(type, selectedValue) {
        var $subType = $('#subType');
        $subType.html('<option value="">Select Sub-Type</option>');

        if (type && subTypeOptions[type]) {
            $.each(subTypeOptions[type], function (value, label) {
                var isSelected = value === selectedValue ? ' selected' : '';
                $subType.append('<option value="' + value + '"' + isSelected + '>' + label + '</option>');
            });
        }
    }

    // Update sub-type options when account type changes
    $('#accountType').on('change', function () {
        var type = $(this).val();
        populateSubTypes(type, '');

        // Auto-set balance type
        if (type && defaultBalanceType[type]) {
            $('#balanceType').val(defaultBalanceType[type]);
        }
    });

    // Populate sub-types on page load with existing values (server-rendered selected state is replaced here)
    var currentType = '{{ old('account_type', $account->account_type) }}';
    var currentSubType = '{{ old('sub_type', $account->sub_type) }}';
    if (currentType) {
        populateSubTypes(currentType, currentSubType);
    }

    // Toggle bank account fields
    $('#isBankAccount').on('change', function () {
        if ($(this).is(':checked')) {
            $('#bankFields').removeClass('d-none');
        } else {
            $('#bankFields').addClass('d-none');
        }
    });

    // Delete account confirmation
    $('#deleteAccountBtn').on('click', function () {
        if (confirm('Are you sure you want to delete this account?\n\nThis action cannot be undone. All related journal entries and transactions will be affected.')) {
            $('#deleteAccountForm').submit();
        }
    });
});
</script>
@endpush
