@extends('core::layouts.master')

@section('title', 'Edit Supplier — ' . $supplier->company_name)
@section('page-title', __('Edit Supplier'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('supplier.index') }}">Suppliers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('supplier.show', $supplier) }}">{{ $supplier->company_name }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit</span>
@endsection

@section('page-actions')
    <a href="{{ route('supplier.show', $supplier) }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Supplier
    </a>
@endsection

@section('content')

    <form action="{{ route('supplier.update', $supplier) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-truck-field me-2"></i>Supplier Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <!-- Basic Info -->
                    <div class="col-12">
                        <h6 class="fw-600 text-uppercase mb-0">Basic Information</h6>
                    </div>
                    <div class="col-md-12">
                        <label class="bp-form-label">Company Name *</label>
                        <input type="text" class="bp-form-control @error('company_name') is-invalid @enderror"
                            name="company_name" value="{{ old('company_name', $supplier->company_name) }}" required
                            placeholder="Supplier company name">
                        @error('company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Contact Person</label>
                        <input type="text" class="bp-form-control @error('contact_person') is-invalid @enderror"
                            name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}"
                            placeholder="Primary contact name">
                        @error('contact_person')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Phone *</label>
                        <input type="text" class="bp-form-control @error('phone') is-invalid @enderror" name="phone"
                            value="{{ old('phone', $supplier->phone) }}" required data-phone>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Email</label>
                        <input type="email" class="bp-form-control @error('email') is-invalid @enderror" name="email"
                            value="{{ old('email', $supplier->email) }}" placeholder="supplier@email.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Supplier Group</label>
                        <select class="bp-form-select w-100" name="supplier_group_id">
                            <option value="">Select Group</option>
                            @foreach (\Modules\Supplier\Models\SupplierGroup::active()->orderBy('name')->get() as $group)
                                <option value="{{ $group->id }}"
                                    {{ old('supplier_group_id', $supplier->supplier_group_id) == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Address -->
                    <div class="col-12 mt-4">
                        <h6 class="fw-600 text-uppercase mb-0">Address</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Division</label>
                        <select class="bp-form-select w-100" name="division">
                            <option value="">Select Division</option>
                            @foreach (['Dhaka', 'Chittagong', 'Rajshahi', 'Khulna', 'Barisal', 'Sylhet', 'Rangpur', 'Mymensingh'] as $div)
                                <option {{ old('division', $supplier->division) == $div ? 'selected' : '' }}>
                                    {{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">District</label>
                        <select class="bp-form-select w-100" name="district">
                            <option value="">Select District</option>
                            @foreach (['Dhaka', 'Gazipur', 'Narayanganj', 'Chittagong'] as $dist)
                                <option {{ old('district', $supplier->district) == $dist ? 'selected' : '' }}>
                                    {{ $dist }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Area</label>
                        <input type="text" class="bp-form-control" name="area"
                            value="{{ old('area', $supplier->area) }}" placeholder="Area / Thana">
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Street Address</label>
                        <input type="text" class="bp-form-control" name="address"
                            value="{{ old('address', $supplier->address) }}" placeholder="House, Road, Area">
                    </div>

                    <!-- Bank Details -->
                    <div class="col-12 mt-4">
                        <h6 class="fw-600 text-uppercase mb-0">Bank Details</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Bank Name</label>
                        <select class="bp-form-select w-100" name="bank_name">
                            <option value="">Select Bank</option>
                            @foreach (['DBBL (Dutch-Bangla Bank)', 'BRAC Bank', 'Islami Bank Bangladesh', 'City Bank', 'Eastern Bank (EBL)', 'Standard Chartered BD', 'Sonali Bank', 'Agrani Bank', 'Other'] as $bank)
                                <option {{ old('bank_name', $supplier->bank_name) == $bank ? 'selected' : '' }}>
                                    {{ $bank }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Account Number</label>
                        <input type="text" class="bp-form-control" name="account_number"
                            value="{{ old('account_number', $supplier->account_number) }}"
                            placeholder="Bank account number">
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Branch</label>
                        <input type="text" class="bp-form-control" name="bank_branch"
                            value="{{ old('bank_branch', $supplier->bank_branch) }}" placeholder="Branch name">
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Routing Number</label>
                        <input type="text" class="bp-form-control" name="routing_number"
                            value="{{ old('routing_number', $supplier->routing_number) }}"
                            placeholder="9-digit routing number">
                    </div>

                    <!-- Tax & License -->
                    <div class="col-12 mt-4">
                        <h6 class="fw-600 text-uppercase mb-0">Tax &amp; License</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">TIN Number</label>
                        <input type="text" class="bp-form-control" name="tin"
                            value="{{ old('tin', $supplier->tin) }}" placeholder="Tax Identification Number">
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">BIN Number</label>
                        <input type="text" class="bp-form-control" name="bin"
                            value="{{ old('bin', $supplier->bin) }}" placeholder="Business Identification Number">
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Trade License</label>
                        <input type="text" class="bp-form-control" name="trade_license"
                            value="{{ old('trade_license', $supplier->trade_license) }}"
                            placeholder="Trade license number">
                    </div>

                    <!-- Financial -->
                    <div class="col-12 mt-4">
                        <h6 class="fw-600 text-uppercase mb-0">Financial</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Credit Limit ({{ currency_symbol() }})</label>
                        <input type="number" class="bp-form-control" name="credit_limit"
                            value="{{ old('credit_limit', num_input($supplier->credit_limit)) }}" placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Payment Terms</label>
                        <select class="bp-form-select w-100" name="payment_terms">
                            @foreach (['Due on Receipt', 'Net 15', 'Net 30', 'Net 45', 'Net 60'] as $term)
                                <option {{ old('payment_terms', $supplier->payment_terms) == $term ? 'selected' : '' }}>
                                    {{ $term }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="col-12">
                        <label class="bp-form-label">Notes</label>
                        <textarea class="bp-form-control" name="notes" rows="2" placeholder="Internal notes about this supplier...">{{ old('notes', $supplier->notes) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="bp-card-footer text-end">
                <a href="{{ route('supplier.show', $supplier) }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Update
                    Supplier</button>
            </div>
        </div>
    </form>

@endsection
