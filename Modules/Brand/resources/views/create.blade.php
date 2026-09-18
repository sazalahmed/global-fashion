@extends('core::layouts.master')

@section('title', __('Add Brand'))
@section('page-title', __('Add Brand'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('brands.index') }}">Brands</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add Brand</span>
@endsection

@section('page-actions')
    <a href="{{ route('brands.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Brands
    </a>
@endsection

@section('content')

    <form action="{{ route('brands.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Brand Information -->
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2"></i>Brand Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-12 category_img">
                        <x-core::image-upload name="logo" id="brand-logo" label="Logo" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Brand Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            id="brand-name" value="{{ old('name') }}" required placeholder="Enter brand name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Slug</label>
                        <input type="text" class="bp-form-control @error('slug') is-invalid @enderror" name="slug"
                            id="brand-slug" value="{{ old('slug') }}" placeholder="Auto-generated from name">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Website URL</label>
                        <input type="url" class="bp-form-control @error('website') is-invalid @enderror" name="website"
                            value="{{ old('website') }}" placeholder="https://example.com">
                        @error('website')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Description</label>
                        <textarea class="bp-form-control @error('description') is-invalid @enderror" name="description" rows="3"
                            placeholder="Brief description of the brand...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Status *</label>
                        <select class="bp-form-select w-100 @error('status') is-invalid @enderror" name="status" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active
                            </option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Display Position</label>
                        <input type="number" class="bp-form-control @error('sort_order') is-invalid @enderror"
                            name="sort_order" value="{{ old('sort_order', 0) }}" min="0" placeholder="0">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_featured" id="is-featured"
                                value="1" {{ old('is_featured') ? 'checked' : '' }}>
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
                            name="meta_title" value="{{ old('meta_title') }}" placeholder="SEO title for this brand">
                        @error('meta_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Meta Description</label>
                        <textarea class="bp-form-control @error('meta_description') is-invalid @enderror" name="meta_description"
                            rows="2" placeholder="SEO description for this brand...">{{ old('meta_description') }}</textarea>
                        @error('meta_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-4">
            <a href="{{ route('brands.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark"></i>
                Cancel</a>
            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save
                Brand</button>
        </div>
    </form>

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
        });
    </script>
@endpush
