@extends('core::layouts.master')

@section('title', 'Add Investor')
@section('page-title', 'Add Investor')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('investment.dashboard') }}">Investment</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('investment.investors.index') }}">Investors</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>New</span>
@endsection

@section('page-actions')
    <a href="{{ route('investment.investors.index') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i>Back to Investors</a>
@endsection

@section('content')

    <form method="POST" action="{{ route('investment.investors.store') }}">
        @csrf
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-user-plus me-2"></i>Investor Details</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="bp-form-label">Type *</label>
                        <select name="type" id="investorType"
                            class="bp-form-select w-100 @error('type') is-invalid @enderror" required>
                            <option value="shareholder" @selected(old('type') === 'shareholder')>Shareholder (equity owner)</option>
                            <option value="investor" @selected(old('type') === 'investor')>Investor (profit share only)</option>
                        </select>
                        <div class="fs-11 text-muted mt-1">
                            <strong>Shareholder</strong>: owns equity, dividends based on shares.<br>
                            <strong>Investor</strong>: contractual profit-share %, no equity.
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="bp-form-label">Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Join Date *</label>
                        <input type="date" class="bp-form-control" name="join_date"
                            value="{{ old('join_date', now()->toDateString()) }}" required>
                    </div>

                    <div class="col-md-3" id="sharesField">
                        <label class="bp-form-label">Shares Owned *</label>
                        <input type="number" step="0.01" min="0" class="bp-form-control" name="shares_owned"
                            value="{{ old('shares_owned') }}">
                        <div class="fs-11 text-muted mt-1">Their % = shares / total shares issued.</div>
                    </div>
                    <div class="col-md-3 d-none" id="pctField">
                        <label class="bp-form-label">Profit Share % *</label>
                        <input type="number" step="0.01" min="0" max="100" class="bp-form-control"
                            name="profit_share_pct" value="{{ old('profit_share_pct') }}">
                        <div class="fs-11 text-muted mt-1">Of net profit, off the top.</div>
                    </div>

                    <div class="col-md-3">
                        <label class="bp-form-label">Phone</label>
                        <input type="text" class="bp-form-control" name="phone" value="{{ old('phone') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Email</label>
                        <input type="email" class="bp-form-control" name="email" value="{{ old('email') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">NID / TIN</label>
                        <input type="text" class="bp-form-control" name="nid_or_tin" value="{{ old('nid_or_tin') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="bp-form-label">Address</label>
                        <input type="text" class="bp-form-control" name="address" value="{{ old('address') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="bp-form-label">Notes</label>
                        <textarea class="bp-form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1"
                                {{ old('is_active', true) ? 'checked' : '' }}> Active
                        </label>
                    </div>
                </div>
            </div>
            <div class="bp-card-footer text-end">
                <a href="{{ route('investment.investors.index') }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i>Save Investor</button>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            function toggleFields() {
                var type = $('#investorType').val();
                if (type === 'shareholder') {
                    $('#sharesField').removeClass('d-none');
                    $('#pctField').addClass('d-none');
                    $('input[name=profit_share_pct]').val('');
                } else {
                    $('#sharesField').addClass('d-none');
                    $('#pctField').removeClass('d-none');
                    $('input[name=shares_owned]').val('');
                }
            }
            $('#investorType').on('change', toggleFields);
            toggleFields();
        });
    </script>
@endpush
