@extends('core::layouts.master')

@section('title', __('Edit Brand'))
@section('page-title', __('Edit Brand'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('brands.index') }}">Brands</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit Brand</span>
@endsection

@section('page-actions')
    <a href="{{ route('brands.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Brands
    </a>
@endsection

@section('content')

    <form action="{{ route('brands.update', $brand) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Brand Information -->
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2"></i>Brand Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-12 category_img">
                        <x-core::image-upload name="logo" id="brand-logo" label="Logo" :current="$brand->logo ? upload_url($brand->logo) : null" />
                        @if ($brand->logo)
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" name="remove_logo" id="remove-logo"
                                    value="1">
                                <label class="bp-form-label mb-0 fs-12" for="remove-logo">Remove current logo</label>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Brand Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            id="brand-name" value="{{ old('name', $brand->name) }}" required placeholder="Enter brand name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Slug</label>
                        <input type="text" class="bp-form-control @error('slug') is-invalid @enderror" name="slug"
                            id="brand-slug" value="{{ old('slug', $brand->slug) }}" placeholder="Auto-generated from name">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Website URL</label>
                        <input type="url" class="bp-form-control @error('website') is-invalid @enderror" name="website"
                            value="{{ old('website', $brand->website) }}" placeholder="https://example.com">
                        @error('website')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Description</label>
                        <textarea class="bp-form-control @error('description') is-invalid @enderror" name="description" rows="3"
                            placeholder="Brief description of the brand...">{{ old('description', $brand->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Status *</label>
                        <select class="bp-form-select w-100 @error('status') is-invalid @enderror" name="status" required>
                            <option value="active" {{ old('status', $brand->status) === 'active' ? 'selected' : '' }}>
                                Active</option>
                            <option value="inactive" {{ old('status', $brand->status) === 'inactive' ? 'selected' : '' }}>
                                Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Display Position</label>
                        <input type="number" class="bp-form-control @error('sort_order') is-invalid @enderror"
                            name="sort_order" value="{{ old('sort_order', $brand->sort_order) }}" min="0"
                            placeholder="0">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_featured" id="is-featured"
                                value="1" {{ old('is_featured', $brand->is_featured) ? 'checked' : '' }}>
                            <label class="bp-form-label mb-0" for="is-featured">Featured Brand</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO Information -->
        <div class="bp-card mt-3">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>SEO Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="bp-form-label">Meta Title</label>
                        <input type="text" class="bp-form-control @error('meta_title') is-invalid @enderror"
                            name="meta_title" value="{{ old('meta_title', $brand->meta_title) }}"
                            placeholder="SEO title for this brand">
                        @error('meta_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Meta Description</label>
                        <textarea class="bp-form-control @error('meta_description') is-invalid @enderror" name="meta_description"
                            rows="2" placeholder="SEO description for this brand...">{{ old('meta_description', $brand->meta_description) }}</textarea>
                        @error('meta_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex justify-content-between align-items-center">
            <button type="button" class="bp-btn bp-btn-danger" id="deleteBrandBtn">
                <i class="fa-solid fa-trash me-1"></i> Delete Brand
            </button>
            <div>
                <a href="{{ route('brands.index') }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark"></i> Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Update
                    Brand</button>
            </div>
        </div>
    </form>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBrandModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Delete Brand
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong>{{ $brand->name }}</strong>? This action cannot be undone.
                    </p>
                    <p class="text-muted fs-12">All products associated with this brand will be unlinked.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
                    <form action="{{ route('brands.destroy', $brand) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bp-btn bp-btn-danger"><i class="fa-solid fa-trash me-1"></i> Yes,
                            Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(document).ready(function() {
            // Auto-generate slug from brand name
            $('#brand-name').on('input', function() {
                var name = $(this).val();
                var slug = name
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                $('#brand-slug').val(slug);
            });

            // Delete brand confirmation
            $('#deleteBrandBtn').on('click', function() {
                var modal = new bootstrap.Modal(document.getElementById('deleteBrandModal'));
                modal.show();
            });
        });
    </script>
@endpush
