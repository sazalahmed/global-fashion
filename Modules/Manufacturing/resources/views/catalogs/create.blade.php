@extends('core::layouts.master')

@section('title', __("Add Catalog"))
@section('page-title', __("Add Catalog"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.catalogs.index') }}">Catalogs</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Add Catalog</span>
@endsection

@section('page-actions')
  <a href="{{ route('manufacturing.catalogs.index') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Catalogs
  </a>
@endsection

@section('content')

  <form action="{{ route('manufacturing.catalogs.store') }}" method="POST">
    @csrf
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-book me-2"></i>Catalog Information</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="bp-form-label">Catalog Name *</label>
            <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="Catalog name">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Sort Order</label>
            <input type="number" class="bp-form-control @error('sort_order') is-invalid @enderror" name="sort_order" value="{{ old('sort_order', 0) }}" placeholder="0">
            @error('sort_order')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-12">
            <label class="bp-form-label">Description</label>
            <textarea class="bp-form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Catalog description...">{{ old('description') }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
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
        <a href="{{ route('manufacturing.catalogs.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save Catalog</button>
      </div>
    </div>
  </form>

@endsection
