@extends('core::layouts.master')

@section('title', 'Edit Raw Material — ' . $rawMaterial->name)
@section('page-title', __("Edit Raw Material"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.raw-materials.index') }}">Raw Materials</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.raw-materials.show', $rawMaterial) }}">{{ $rawMaterial->name }}</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Edit</span>
@endsection

@section('page-actions')
  <a href="{{ route('manufacturing.raw-materials.show', $rawMaterial) }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Material
  </a>
@endsection

@section('content')

  <form action="{{ route('manufacturing.raw-materials.update', $rawMaterial) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-cubes me-2"></i>Raw Material Information</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="bp-form-label">Material Name *</label>
            <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $rawMaterial->name) }}" required placeholder="Material name">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Category *</label>
            <select class="bp-form-select w-100 @error('category') is-invalid @enderror" name="category" required>
              <option value="">Select Category</option>
              @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ old('category', $rawMaterial->category) == $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            @error('category')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Unit *</label>
            <select class="bp-form-select w-100 @error('unit') is-invalid @enderror" name="unit" required>
              <option value="">Select Unit</option>
              @foreach($units as $key => $label)
                <option value="{{ $key }}" {{ old('unit', $rawMaterial->unit) == $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            @error('unit')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Cost Price ({{ currency_symbol() }})</label>
            <input type="number" step="0.01" class="bp-form-control @error('cost_price') is-invalid @enderror" name="cost_price" value="{{ old('cost_price', num_input($rawMaterial->cost_price)) }}" placeholder="0.00">
            @error('cost_price')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Reorder Level</label>
            <input type="number" step="0.01" class="bp-form-control @error('reorder_level') is-invalid @enderror" name="reorder_level" value="{{ old('reorder_level', num_input($rawMaterial->reorder_level)) }}" placeholder="Minimum stock level">
            @error('reorder_level')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $rawMaterial->is_active) ? 'checked' : '' }}>
              <label class="form-check-label fw-600" for="is_active">Is Active</label>
            </div>
          </div>
          <div class="col-12">
            <label class="bp-form-label">Notes</label>
            <textarea class="bp-form-control" name="notes" rows="2" placeholder="Internal notes about this material...">{{ old('notes', $rawMaterial->notes) }}</textarea>
          </div>
        </div>
      </div>
      <div class="bp-card-footer text-end">
        <a href="{{ route('manufacturing.raw-materials.show', $rawMaterial) }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Update Raw Material</button>
      </div>
    </div>
  </form>

@endsection
