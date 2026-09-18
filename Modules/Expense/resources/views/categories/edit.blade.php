@extends('core::layouts.master')

@section('title', __("Edit Expense Category"))
@section('page-title', __("Edit: ") . $category->name)

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('expenses.index') }}">Expenses</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('expense-categories.index') }}">Categories</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ $category->name }}</span>
@endsection

@section('page-actions')
  <a href="{{ route('expense-categories.index') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Categories
  </a>
@endsection

@section('content')

  <form action="{{ route('expense-categories.update', $category) }}" method="POST">
    @csrf @method('PUT')
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-tag me-2"></i>Category Details</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="bp-form-label">Name *</label>
            <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $category->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="bp-form-label">Parent Category</label>
            <select class="bp-form-select w-100" name="parent_id">
              <option value="">None (top-level)</option>
              @foreach($parents as $p)
                <option value="{{ $p->id }}" {{ old('parent_id', $category->parent_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="bp-form-label">Sort Order</label>
            <input type="number" class="bp-form-control" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0">
          </div>

          <div class="col-md-6">
            <label class="bp-form-label">Status</label>
            <select class="bp-form-select w-100" name="is_active">
              <option value="1" {{ old('is_active', $category->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Active</option>
              <option value="0" {{ old('is_active', $category->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>

          <div class="col-12">
            <label class="bp-form-label">Description</label>
            <textarea class="bp-form-control" name="description" rows="2">{{ old('description', $category->description) }}</textarea>
          </div>
        </div>
      </div>
      <div class="bp-card-footer text-end">
        <a href="{{ route('expense-categories.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save</button>
      </div>
    </div>
  </form>

@endsection
