@extends('core::layouts.master')

@section('title', __('New Asset Category'))
@section('page-title', __('New Asset Category'))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('assets.index') }}">Assets</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('asset-categories.index') }}">Categories</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>New</span>
@endsection

@section('page-actions')
  <a href="{{ route('asset-categories.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back to Categories</a>
@endsection

@section('content')

<form action="{{ route('asset-categories.store') }}" method="POST">
  @csrf
  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-layer-group me-2"></i>Category Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="bp-form-label">Name *</label>
          <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="e.g. Furniture, Vehicles, Electronics">
          @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="bp-form-label">Depreciation Method</label>
          <select class="bp-form-select w-100" name="depreciation_method">
            <option value="straight_line" {{ old('depreciation_method', 'straight_line') === 'straight_line' ? 'selected' : '' }}>Straight Line</option>
            <option value="declining_balance" {{ old('depreciation_method') === 'declining_balance' ? 'selected' : '' }}>Declining Balance</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="bp-form-label">Useful Life (years)</label>
          <input type="number" class="bp-form-control" name="useful_life_years" value="{{ old('useful_life_years', 5) }}" min="1" max="100" placeholder="e.g. 5">
        </div>

        <div class="col-md-6">
          <label class="bp-form-label">Depreciation Rate (%)</label>
          <input type="number" step="0.01" class="bp-form-control" name="depreciation_rate" value="{{ old('depreciation_rate', 20) }}" min="0" max="100" placeholder="e.g. 20.00">
          <div class="fs-11 text-muted mt-1">Used for the declining-balance method.</div>
        </div>
      </div>
    </div>
    <div class="bp-card-footer text-end">
      <a href="{{ route('asset-categories.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Category</button>
    </div>
  </div>
</form>

@endsection
