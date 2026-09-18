{{-- Shared blog category form fields. Expects optional $category (null on create). --}}
@php($category = $category ?? null)
<div class="row g-4">
  <div class="col-xl-8">
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2"></i>Category Details</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="bp-form-label">Name *</label>
            <input type="text" class="bp-form-control" name="name" value="{{ old('name', $category->name ?? '') }}" placeholder="e.g. Fashion" required>
            @error('name')
              <div class="text-danger fs-12 mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Slug</label>
            <input type="text" class="bp-form-control" name="slug" value="{{ old('slug', $category->slug ?? '') }}" placeholder="Auto-generated from name if left blank">
            @error('slug')
              <div class="text-danger fs-12 mt-1">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-toggle-on me-2"></i>Status</h5>
      </div>
      <div class="bp-card-body">
        <label class="bp-form-label">Status</label>
        <select class="bp-form-select w-100" name="is_active">
          <option value="1" {{ old('is_active', $category->is_active ?? true) ? 'selected' : '' }}>Active</option>
          <option value="0" {{ !old('is_active', $category->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
        </select>
      </div>
    </div>

    <div class="bp-card">
      <div class="bp-card-body d-flex flex-column gap-2">
        <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
          <i class="fa-solid fa-save me-2"></i> {{ $submitLabel ?? 'Save Category' }}
        </button>
        <a href="{{ route('ecommerce.blog-categories') }}" class="bp-btn bp-btn-danger w-100 justify-content-center">
          <i class="fa-solid fa-times me-2"></i> Cancel
        </a>
      </div>
    </div>
  </div>
</div>
