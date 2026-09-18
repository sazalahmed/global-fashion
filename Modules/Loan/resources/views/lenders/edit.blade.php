@extends('core::layouts.master')

@section('title', __('Edit Lender'))
@section('page-title', __('Edit Lender'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('loans.index') }}">Loans</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('lenders.index') }}">Lenders</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit Lender</span>
@endsection

@section('page-actions')
    <a href="{{ route('lenders.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Lenders
    </a>
@endsection

@section('content')

    <form action="{{ route('lenders.update', $lender) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-user-tie me-2"></i>Lender Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <!-- Photo -->
                    <div class="col-12 category_img">
                        <x-core::image-upload name="photo" label="Photo"
                            accept="image/jpg,image/jpeg,image/png,image/webp" :current="$lender->photo ? upload_url($lender->photo) : null" />
                        @error('photo')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- Basic Info -->
                    <div class="col-12">
                        <h6 class="fw-700 text-uppercase fs-12 text-muted mb-0">Basic Information</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            value="{{ old('name', $lender->name) }}" required placeholder="Lender name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Company Name</label>
                        <input type="text" class="bp-form-control @error('company_name') is-invalid @enderror"
                            name="company_name" value="{{ old('company_name', $lender->company_name) }}"
                            placeholder="Company or organization">
                        @error('company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Phone</label>
                        <input type="text" class="bp-form-control @error('phone') is-invalid @enderror" name="phone"
                            value="{{ old('phone', $lender->phone) }}" data-phone placeholder="Phone number">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Email</label>
                        <input type="email" class="bp-form-control @error('email') is-invalid @enderror" name="email"
                            value="{{ old('email', $lender->email) }}" placeholder="lender@email.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Address</label>
                        <textarea class="bp-form-control @error('address') is-invalid @enderror" name="address" rows="2"
                            placeholder="Full address">{{ old('address', $lender->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Bank Details -->
                    <div class="col-12 mt-4">
                        <h6 class="fw-700 text-uppercase fs-12 text-muted mb-0">Bank Details</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Bank Name</label>
                        <input type="text" class="bp-form-control @error('bank_name') is-invalid @enderror"
                            name="bank_name" value="{{ old('bank_name', $lender->bank_name) }}" placeholder="Bank name">
                        @error('bank_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Account Number</label>
                        <input type="text" class="bp-form-control @error('account_number') is-invalid @enderror"
                            name="account_number" value="{{ old('account_number', $lender->account_number) }}"
                            placeholder="Bank account number">
                        @error('account_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Branch</label>
                        <input type="text" class="bp-form-control @error('bank_branch') is-invalid @enderror"
                            name="bank_branch" value="{{ old('bank_branch', $lender->bank_branch) }}"
                            placeholder="Branch name">
                        @error('bank_branch')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Financial -->
                    <div class="col-12 mt-4">
                        <h6 class="fw-700 text-uppercase fs-12 text-muted mb-0">Financial</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Opening Balance ({{ currency_symbol() }})</label>
                        <input type="number" class="bp-form-control @error('opening_balance') is-invalid @enderror"
                            name="opening_balance"
                            value="{{ old('opening_balance', num_input($lender->opening_balance)) }}" placeholder="0.00">
                        <small class="text-muted fs-11">Outstanding amount carried forward</small>
                        @error('opening_balance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="col-12">
                        <label class="bp-form-label">Notes</label>
                        <textarea class="bp-form-control @error('notes') is-invalid @enderror" name="notes" rows="2"
                            placeholder="Internal notes about this lender...">{{ old('notes', $lender->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="bp-card-footer text-end">
                <a href="{{ route('lenders.index') }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Update
                    Lender</button>
            </div>
        </div>
    </form>

@endsection
