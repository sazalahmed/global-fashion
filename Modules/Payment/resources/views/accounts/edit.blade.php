@extends('core::layouts.master')

@section('title', __('Edit Payment Account'))
@section('page-title', __('Edit Payment Account'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('payment-accounts.index') }}">Payment Accounts</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit</span>
@endsection

@section('content')

    <form action="{{ route('payment-accounts.update', $paymentAccount) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-4 justify-content-center"">
            <div class="col-xl-8">
                <div class="bp-card">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-wallet me-2"></i>Account Information</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Payment Account Type *</label>
                                <select class="bp-form-select w-100" name="account_type" id="accountType" required>
                                    <option value="cash"
                                        {{ old('account_type', $paymentAccount->account_type) === 'cash' ? 'selected' : '' }}>
                                        Cash</option>
                                    <option value="mobile_banking"
                                        {{ old('account_type', $paymentAccount->account_type) === 'mobile_banking' ? 'selected' : '' }}>
                                        Mobile Banking</option>
                                    <option value="bank"
                                        {{ old('account_type', $paymentAccount->account_type) === 'bank' ? 'selected' : '' }}>
                                        Bank Account</option>
                                    <option value="card"
                                        {{ old('account_type', $paymentAccount->account_type) === 'card' ? 'selected' : '' }}>
                                        Card</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Account Name *</label>
                                <input type="text" class="bp-form-control @error('name') is-invalid @enderror"
                                    name="name" value="{{ old('name', $paymentAccount->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Mobile Banking Fields -->
                            <div class="col-md-6 bp-field-mobile_banking d-none">
                                <label class="bp-form-label d-flex justify-content-between align-items-center">
                                    Mobile Bank Name *
                                    <a href="{{ route('payment-accounts.mobile-banks') }}" target="_blank"
                                        class="fs-11 text-muted"><i class="fa-solid fa-gear me-1"></i>Manage</a>
                                </label>
                                <select class="bp-form-select w-100" name="mobile_bank_name">
                                    <option value="">Select Provider</option>
                                    @foreach ($mobileBanks as $mb)
                                        <option value="{{ $mb->name }}"
                                            {{ old('mobile_bank_name', $paymentAccount->mobile_bank_name) === $mb->name ? 'selected' : '' }}>
                                            {{ $mb->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 bp-field-mobile_banking d-none">
                                <label class="bp-form-label">Mobile Number *</label>
                                <input type="text" class="bp-form-control" name="mobile_number"
                                    value="{{ old('mobile_number', $paymentAccount->mobile_number) }}"
                                    placeholder="01712345678">
                            </div>

                            <!-- Bank Fields -->
                            <div class="col-md-6 bp-field-bank d-none">
                                <label class="bp-form-label d-flex justify-content-between align-items-center">
                                    Bank Name *
                                    <a href="{{ route('payment-accounts.banks') }}" target="_blank"
                                        class="fs-11 text-muted"><i class="fa-solid fa-gear me-1"></i>Manage</a>
                                </label>
                                <select class="bp-form-select w-100" name="bank_id">
                                    <option value="">Select Bank</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}"
                                            {{ old('bank_id', $paymentAccount->bank_id) == $bank->id ? 'selected' : '' }}>
                                            {{ $bank->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 bp-field-bank d-none">
                                <label class="bp-form-label">Account Number *</label>
                                <input type="text" class="bp-form-control" name="bank_account_number"
                                    value="{{ old('bank_account_number', $paymentAccount->bank_account_number) }}">
                            </div>
                            <div class="col-md-6 bp-field-bank d-none">
                                <label class="bp-form-label">Account Type</label>
                                <select class="bp-form-select w-100" name="bank_account_type">
                                    <option value="">Select</option>
                                    @foreach (['Savings', 'Current'] as $t)
                                        <option value="{{ $t }}"
                                            {{ old('bank_account_type', $paymentAccount->bank_account_type) === $t ? 'selected' : '' }}>
                                            {{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 bp-field-bank d-none">
                                <label class="bp-form-label">Branch</label>
                                <input type="text" class="bp-form-control" name="bank_branch"
                                    value="{{ old('bank_branch', $paymentAccount->bank_branch) }}">
                            </div>

                            <!-- Card Fields -->
                            <div class="col-md-4 bp-field-card d-none">
                                <label class="bp-form-label">Card Type *</label>
                                <select class="bp-form-select w-100" name="card_type">
                                    <option value="">Select</option>
                                    @foreach (['Visa', 'MasterCard', 'American Express'] as $ct)
                                        <option value="{{ $ct }}"
                                            {{ old('card_type', $paymentAccount->card_type) === $ct ? 'selected' : '' }}>
                                            {{ $ct }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 bp-field-card d-none">
                                <label class="bp-form-label">Card Holder Name</label>
                                <input type="text" class="bp-form-control" name="card_holder_name"
                                    value="{{ old('card_holder_name', $paymentAccount->card_holder_name) }}">
                            </div>
                            <div class="col-md-4 bp-field-card d-none">
                                <label class="bp-form-label">Card Number</label>
                                <input type="text" class="bp-form-control" name="card_number"
                                    value="{{ old('card_number', $paymentAccount->card_number) }}" maxlength="30">
                            </div>

                            <!-- Common -->
                            <div class="col-md-4">
                                <label class="bp-form-label">Service Charge (%)</label>
                                <input type="number" class="bp-form-control" name="service_charge"
                                    value="{{ old('service_charge', num_input($paymentAccount->service_charge)) }}" step="0.01"
                                    min="0" max="100">
                            </div>
                            <div class="col-md-4">
                                <label class="bp-form-label">Opening Balance ({{ currency_symbol() }})</label>
                                <input type="number" class="bp-form-control" name="opening_balance"
                                    value="{{ old('opening_balance', num_input($paymentAccount->opening_balance)) }}" step="0.01"
                                    min="0">
                            </div>
                            <div class="col-md-4 d-flex align-items-end gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1"
                                        id="isDefault"
                                        {{ old('is_default', $paymentAccount->is_default) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-600 fs-13" for="isDefault">Default</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        id="isActive"
                                        {{ old('is_active', $paymentAccount->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-600 fs-13" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bp-card-footer text-end">
                        <a href="{{ route('payment-accounts.index') }}" class="bp-btn bp-btn-danger me-2"><i
                                class="fa-solid fa-xmark me-1"></i>Cancel</a>
                        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i>
                            Update Account</button>
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
