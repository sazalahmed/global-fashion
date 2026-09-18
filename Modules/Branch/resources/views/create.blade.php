@extends('core::layouts.master')

@section('title', isset($branch) ? 'Edit Branch' : 'Add Branch')
@section('page-title', isset($branch) ? 'Edit Branch' : 'Add Branch')

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('branches.index') }}">Branches</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ isset($branch) ? 'Edit' : 'Add New' }}</span>
@endsection

@section('page-actions')
  <a href="{{ route('branches.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
  <button class="bp-btn bp-btn-success" form="branchForm" type="submit"><i class="fa-solid fa-check me-1"></i> {{ isset($branch) ? 'Update Branch' : 'Save Branch' }}</button>
@endsection

@section('content')

  <form id="branchForm" action="{{ isset($branch) ? route('branches.update', $branch->id) : route('branches.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($branch))
      @method('PUT')
    @endif

    <div class="row g-4">
      <!-- Left Column -->
      <div class="col-xl-8">

        <!-- Basic Information -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-store me-2 text-primary"></i>Branch Information</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="bp-form-label">Branch Name *</label>
                <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $branch->name ?? '') }}" required placeholder="e.g., Dhaka Main Branch">
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Branch Code *</label>
                <input type="text" class="bp-form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $branch->code ?? '') }}" required placeholder="e.g., DHK-01" maxlength="20">
                @error('code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Phone</label>
                <input type="text" class="bp-form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $branch->phone ?? '') }}" data-phone>
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Email</label>
                <input type="email" class="bp-form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $branch->email ?? '') }}" placeholder="branch@bizpos.test">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label class="bp-form-label">Address</label>
                <textarea class="bp-form-control @error('address') is-invalid @enderror" name="address" rows="2" placeholder="Full street address">{{ old('address', $branch->address ?? '') }}</textarea>
                @error('address')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">City</label>
                <input type="text" class="bp-form-control" name="city" value="{{ old('city', $branch->city ?? '') }}" placeholder="e.g., Dhaka">
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">District</label>
                <input type="text" class="bp-form-control" name="district" value="{{ old('district', $branch->district ?? '') }}" placeholder="e.g., Dhaka">
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Zip Code</label>
                <input type="text" class="bp-form-control" name="zip_code" value="{{ old('zip_code', $branch->zip_code ?? '') }}" placeholder="e.g., 1205" maxlength="10">
              </div>
            </div>
          </div>
        </div>

        <!-- Manager & Operations -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-user-tie me-2 text-success"></i>Manager & Operations</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="bp-form-label">Manager Name</label>
                <input type="text" class="bp-form-control" name="manager_name" value="{{ old('manager_name', $branch->manager_name ?? '') }}" placeholder="Branch manager name">
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Manager Phone</label>
                <input type="text" class="bp-form-control" name="manager_phone" value="{{ old('manager_phone', $branch->manager_phone ?? '') }}" data-phone>
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Opening Time</label>
                <input type="time" class="bp-form-control" name="opening_time" value="{{ old('opening_time', $branch->opening_time ?? '') }}">
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Closing Time</label>
                <input type="time" class="bp-form-control" name="closing_time" value="{{ old('closing_time', $branch->closing_time ?? '') }}">
              </div>
              <div class="col-12">
                <label class="bp-form-label">Notes</label>
                <textarea class="bp-form-control" name="notes" rows="2" placeholder="Internal notes about this branch">{{ old('notes', $branch->notes ?? '') }}</textarea>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column -->
      <div class="col-xl-4">

        <!-- Status & Toggles -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-sliders me-2 text-warning"></i>Settings</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-12">
                <div class="bp-toggle-row">
                  <label class="bp-form-label mb-0">Active</label>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $branch->is_active ?? true) ? 'checked' : '' }}>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="bp-toggle-row">
                  <label class="bp-form-label mb-0">Main Branch</label>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_main" value="1" {{ old('is_main', $branch->is_main ?? false) ? 'checked' : '' }}>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="bp-toggle-row">
                  <label class="bp-form-label mb-0">POS Enabled</label>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_pos_enabled" value="1" {{ old('is_pos_enabled', $branch->is_pos_enabled ?? true) ? 'checked' : '' }}>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="bp-toggle-row">
                  <label class="bp-form-label mb-0">eCommerce Enabled</label>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_ecom_enabled" value="1" {{ old('is_ecom_enabled', $branch->is_ecom_enabled ?? false) ? 'checked' : '' }}>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Branch Logo -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-image me-2 text-info"></i>Branch Logo</h5>
          </div>
          <div class="bp-card-body">
            <x-core::image-upload name="logo" label="Logo" hint="JPG, PNG, WEBP. Max 2MB." />
          </div>
        </div>

        <!-- Save Actions -->
        <div class="d-flex flex-column gap-2 mt-3">
          <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
            <i class="fa-solid fa-check me-1"></i> {{ isset($branch) ? 'Update Branch' : 'Create Branch' }}
          </button>
          <a href="{{ route('branches.index') }}" class="bp-btn bp-btn-danger w-100 justify-content-center"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        </div>

      </div>
    </div>

  </form>

@endsection
