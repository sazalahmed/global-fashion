@extends('core::layouts.master')

@section('title', __('Add Category'))
@section('page-title', __('Add Category'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Products</span>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('categories.index') }}">Categories</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add</span>
@endsection

@section('page-actions')
    <a href="{{ route('categories.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Categories
    </a>
@endsection

@section('content')

    <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Basic Information -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-layer-group me-2"></i>Category Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-12 category_img">
                        <x-core::image-upload name="image" id="category-image" label="Category Image" />
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Category Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            id="category-name" value="{{ old('name') }}" required placeholder="Enter category name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Slug</label>
                        <input type="text" class="bp-form-control @error('slug') is-invalid @enderror" name="slug"
                            id="category-slug" value="{{ old('slug') }}" placeholder="auto-generated-from-name">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Parent Category</label>
                        <select class="bp-form-select w-100 select2-search @error('parent_id') is-invalid @enderror"
                            name="parent_id" data-placeholder="None (Top Level)">
                            <option value="">None (Top Level)</option>
                            @foreach ($parentOptions as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Status</label>
                        <select class="bp-form-select w-100 @error('status') is-invalid @enderror" name="status">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active
                            </option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Display Position</label>
                        <input type="number" class="bp-form-control @error('sort_order') is-invalid @enderror"
                            name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label d-block">Storefront Visibility</label>
                        <div class="form-check form-switch d-inline-flex align-items-center gap-2 me-4">
                            <input class="form-check-input" type="checkbox" id="showInMenu" name="show_in_menu"
                                value="1" {{ old('show_in_menu', true) ? 'checked' : '' }}>
                            <label class="form-check-label fs-13" for="showInMenu">Show in <strong>Browse
                                    Categories</strong> menu <span class="text-muted">(top 9 shown)</span></label>
                        </div>
                        <div class="form-check form-switch d-inline-flex align-items-center gap-2">
                            <input class="form-check-input" type="checkbox" id="showInTop" name="show_in_top" value="1"
                                {{ old('show_in_top', true) ? 'checked' : '' }}>
                            <label class="form-check-label fs-13" for="showInTop">Show in homepage <strong>Top
                                    Categories</strong></label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Description</label>
                        <textarea class="bp-form-control @error('description') is-invalid @enderror" name="description" rows="3"
                            placeholder="Brief description of this category...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO Information -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>SEO Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="bp-form-label">Meta Title</label>
                        <input type="text" class="bp-form-control @error('meta_title') is-invalid @enderror"
                            name="meta_title" data-seo-title value="{{ old('meta_title') }}"
                            placeholder="SEO page title">
                        <small class="fs-11 text-muted" data-seo-title-count></small>
                        @error('meta_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Meta Description</label>
                        <textarea class="bp-form-control @error('meta_description') is-invalid @enderror" name="meta_description"
                            data-seo-desc rows="2" placeholder="SEO meta description for search engines...">{{ old('meta_description') }}</textarea>
                        <small class="fs-11 text-muted" data-seo-desc-count></small>
                        @error('meta_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <div class="bp-seo-snippet" data-seo-preview>
                            <div class="seo-pv-title"></div>
                            <div class="seo-pv-url">{{ url('/') }}/…</div>
                            <div class="seo-pv-desc"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="text-end">
            <a href="{{ route('categories.index') }}" class="bp-btn bp-btn-danger me-2"><i
                    class="fa-solid fa-xmark"></i> Cancel</a>
            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save
                Category</button>
        </div>

    </form>

@endsection

@push('scripts')
    <script src="{{ asset('website/assets/js/seo-snippet.js') }}"></script>
    <script>
        'use strict';

        $(document).ready(function() {
            // Auto-generate slug from category name
            $('#category-name').on('input', function() {
                var name = $(this).val();
                var slug = name
                    .toLowerCase()
                    .trim()
                    .replace(/[&]/g, 'and')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                $('#category-slug').val(slug);
            });
        });
    </script>
@endpush
