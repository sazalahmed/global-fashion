@extends('core::layouts.master')

@section('title', __('Add Payment Account'))
@section('page-title', __('Add Payment Account'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('payment-accounts.index') }}">Payment Accounts</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add Account</span>
@endsection

@section('content')

    <form action="{{ route('payment-accounts.store') }}" method="POST">
        @csrf
        <div class="row g-4 justify-content-center">
            <div class="col-xl-8">
                <div class="bp-card">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-wallet me-2"></i>Account Information</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Payment Account Type *</label>
                                <select class="bp-form-select w-100 @error('account_type') is-invalid @enderror"
                                    name="account_type" id="accountType" required>
                                    <option value="">Select Type</option>
                                    <option value="cash" {{ old('account_type') === 'cash' ? 'selected' : '' }}>Cash
                                    </option>
                                    <option value="mobile_banking"
                                        {{ old('account_type') === 'mobile_banking' ? 'selected' : '' }}>Mobile Banking
                                    </option>
                                    <option value="bank" {{ old('account_type') === 'bank' ? 'selected' : '' }}>Bank
                                        Account</option>
                                    <option value="card" {{ old('account_type') === 'card' ? 'selected' : '' }}>Card
                                    </option>
                                </select>
                                @error('account_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Account Name *</label>
                                <input type="text" class="bp-form-control @error('name') is-invalid @enderror"
                                    name="name" value="{{ old('name') }}" required
                                    placeholder="e.g., Main Cash, bKash - 01712...">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Mobile Banking Fields -->
                            <div class="col-md-6 bp-field-mobile_banking d-none">
                                <label class="bp-form-label d-flex justify-content-between align-items-center">
                                    Mobile Bank Name *
                                    <a href="{{ route('payment-accounts.mobile-banks') }}" target="_blank"
                                        class="fs-11 text-muted" title="Manage providers"><i
                                            class="fa-solid fa-gear me-1"></i>Manage</a>
                                </label>
                                <select class="bp-form-select w-100" name="mobile_bank_name">
                                    <option value="">Select Provider</option>
                                    @foreach ($mobileBanks as $mb)
                                        <option value="{{ $mb->name }}"
                                            {{ old('mobile_bank_name') === $mb->name ? 'selected' : '' }}>
                                            {{ $mb->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 bp-field-mobile_banking d-none">
                                <label class="bp-form-label">Mobile Number *</label>
                                <input type="text" class="bp-form-control" name="mobile_number"
                                    value="{{ old('mobile_number') }}" placeholder="e.g., 01712345678">
                            </div>

                            <!-- Bank Fields -->
                            <div class="col-md-6 bp-field-bank d-none">
                                <label class="bp-form-label d-flex justify-content-between align-items-center">
                                    Bank Name *
                                    <a href="{{ route('payment-accounts.banks') }}" target="_blank"
                                        class="fs-11 text-muted" title="Manage banks"><i
                                            class="fa-solid fa-gear me-1"></i>Manage</a>
                                </label>
                                <select class="bp-form-select w-100" name="bank_id">
                                    <option value="">Select Bank</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}"
                                            {{ old('bank_id') == $bank->id ? 'selected' : '' }}>{{ $bank->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 bp-field-bank d-none">
                                <label class="bp-form-label">Account Number *</label>
                                <input type="text" class="bp-form-control" name="bank_account_number"
                                    value="{{ old('bank_account_number') }}" placeholder="e.g., 1234567890">
                            </div>
                            <div class="col-md-6 bp-field-bank d-none">
                                <label class="bp-form-label">Account Type</label>
                                <select class="bp-form-select w-100" name="bank_account_type">
                                    <option value="">Select</option>
                                    <option value="Savings" {{ old('bank_account_type') === 'Savings' ? 'selected' : '' }}>
                                        Savings</option>
                                    <option value="Current" {{ old('bank_account_type') === 'Current' ? 'selected' : '' }}>
                                        Current</option>
                                </select>
                            </div>
                            <div class="col-md-6 bp-field-bank d-none">
                                <label class="bp-form-label">Branch</label>
                                <input type="text" class="bp-form-control" name="bank_branch"
                                    value="{{ old('bank_branch') }}" placeholder="e.g., Gulshan Branch">
                            </div>

                            <!-- Card Fields -->
                            <div class="col-md-4 bp-field-card d-none">
                                <label class="bp-form-label">Card Type *</label>
                                <select class="bp-form-select w-100" name="card_type">
                                    <option value="">Select</option>
                                    <option value="Visa" {{ old('card_type') === 'Visa' ? 'selected' : '' }}>Visa
                                    </option>
                                    <option value="MasterCard" {{ old('card_type') === 'MasterCard' ? 'selected' : '' }}>
                                        MasterCard</option>
                                    <option value="American Express"
                                        {{ old('card_type') === 'American Express' ? 'selected' : '' }}>American Express
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4 bp-field-card d-none">
                                <label class="bp-form-label">Card Holder Name</label>
                                <input type="text" class="bp-form-control" name="card_holder_name"
                                    value="{{ old('card_holder_name') }}" placeholder="Name on card">
                            </div>
                            <div class="col-md-4 bp-field-card d-none">
                                <label class="bp-form-label">Card Number (last 4)</label>
                                <input type="text" class="bp-form-control" name="card_number"
                                    value="{{ old('card_number') }}" placeholder="e.g., **** 1234" maxlength="30">
                            </div>

                            <!-- Common Fields -->
                            <div class="col-md-4">
                                <label class="bp-form-label">Service Charge (%)</label>
                                <input type="number" class="bp-form-control" name="service_charge"
                                    value="{{ old('service_charge', 0) }}" step="0.01" min="0" max="100"
                                    placeholder="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="bp-form-label">Opening Balance ({{ currency_symbol() }})</label>
                                <input type="number" class="bp-form-control" name="opening_balance"
                                    value="{{ old('opening_balance', 0) }}" step="0.01" min="0"
                                    placeholder="0.00">
                            </div>
                            <div class="col-md-4 d-flex align-items-end gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1"
                                        id="isDefault" {{ old('is_default') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-600 fs-13" for="isDefault">Default</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        id="isActive" {{ old('is_active', '1') ? 'checked' : '' }} checked>
                                    <label class="form-check-label fw-600 fs-13" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bp-card-footer text-end">
                        <a href="{{ route('payment-accounts.index') }}" class="bp-btn bp-btn-danger me-2"><i
                                class="fa-solid fa-xmark me-1"></i>Cancel</a>
                        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save
                            Account</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            function toggleFields() {
                var type = $('#accountType').val();
                $('.bp-field-mobile_banking, .bp-field-bank, .bp-field-card').addClass('d-none');
                if (type) {
                    $('.bp-field-' + type).removeClass('d-none');
                }
            }
            $('#accountType').on('change', toggleFields);
            toggleFields();
        });
    </script>
@endpush
