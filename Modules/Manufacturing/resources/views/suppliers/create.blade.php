@extends('core::layouts.master')

@section('title', __("Add Raw Material Supplier"))
@section('page-title', __("Add Supplier"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.suppliers.index') }}">Suppliers</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Add Supplier</span>
@endsection

@section('page-actions')
  <a href="{{ route('manufacturing.suppliers.index') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Suppliers
  </a>
@endsection

@section('content')

  <form action="{{ route('manufacturing.suppliers.store') }}" method="POST">
    @csrf
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-truck-field me-2"></i>Supplier Information</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <!-- Basic Info -->
          <div class="col-12">
            <h6 class="fw-700 text-uppercase fs-12 text-muted mb-0">Basic Information</h6>
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Company Name *</label>
            <input type="text" class="bp-form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name') }}" required placeholder="Supplier company name">
            @error('company_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Contact Person</label>
            <input type="text" class="bp-form-control @error('contact_person') is-invalid @enderror" name="contact_person" value="{{ old('contact_person') }}" placeholder="Primary contact name">
            @error('contact_person')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Phone</label>
            <input type="text" class="bp-form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" placeholder="Phone number">
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Email</label>
            <input type="email" class="bp-form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="supplier@email.com">
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-12">
            <label class="bp-form-label">Address</label>
            <textarea class="bp-form-control @error('address') is-invalid @enderror" name="address" rows="2" placeholder="Supplier address">{{ old('address') }}</textarea>
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
            <select class="bp-form-select w-100" name="bank_name">
              <option value="">Select Bank</option>
              @foreach(['DBBL (Dutch-Bangla Bank)', 'BRAC Bank', 'Islami Bank Bangladesh', 'City Bank', 'Eastern Bank (EBL)', 'Standard Chartered BD', 'Sonali Bank', 'Agrani Bank', 'Other'] as $bank)
                <option {{ old('bank_name') == $bank ? 'selected' : '' }}>{{ $bank }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="bp-form-label">Account Number</label>
            <input type="text" class="bp-form-control" name="account_number" value="{{ old('account_number') }}" placeholder="Bank account number">
          </div>
          <div class="col-md-4">
            <label class="bp-form-label">Bank Branch</label>
            <input type="text" class="bp-form-control" name="bank_branch" value="{{ old('bank_branch') }}" placeholder="Branch name">
          </div>

          <!-- Financial -->
          <div class="col-12 mt-4">
            <h6 class="fw-700 text-uppercase fs-12 text-muted mb-0">Financial</h6>
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Payment Terms</label>
            <select class="bp-form-select w-100" name="payment_terms">
              <option value="">Select Payment Terms</option>
              @foreach(['Due on Receipt', 'Net 15', 'Net 30', 'Net 45', 'Net 60'] as $term)
                <option {{ old('payment_terms') == $term ? 'selected' : '' }}>{{ $term }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Opening Balance ({{ currency_symbol() }})</label>
            <input type="number" step="0.01" class="bp-form-control" name="opening_balance" value="{{ old('opening_balance', 0) }}" placeholder="0.00">
            <small class="text-muted fs-11">Payable amount carried forward</small>
          </div>

          <!-- Notes & Status -->
          <div class="col-12 mt-4">
            <label class="bp-form-label">Notes</label>
            <textarea class="bp-form-control" name="notes" rows="2" placeholder="Internal notes about this supplier...">{{ old('notes') }}</textarea>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
              <label class="form-check-label fw-600" for="is_active">Is Active</label>
            </div>
          </div>
        </div>
      </div>
      <div class="bp-card-footer text-end">
        <a href="{{ route('manufacturing.suppliers.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save Supplier</button>
      </div>
    </div>
  </form>

@endsection
