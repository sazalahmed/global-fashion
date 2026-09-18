@extends('core::layouts.master')

@section('title', __('Create Banner — Website'))
@section('page-title', __('Create Banner'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.banners') }}">Banners</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Create</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.banners') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Banners
    </a>
@endsection

@section('content')

    <form action="{{ route('ecommerce.banners.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-xl-8">

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-image me-2"></i>Banner Details</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-12 category_img">
                                <x-core::image-upload name="image" id="bannerImage" label="Banner Image"
                                    hint="Recommended: 1920x600px for Hero, 600x400px for Promo" required />
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">URL</label>
                                <input type="text" class="bp-form-control" name="button_url"
                                    value="{{ old('button_url') }}" placeholder="e.g. /shop or /flash-deals">
                                @error('button_url')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="col-xl-4">

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-sliders me-2"></i>Settings</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="bp-form-label">Position *</label>
                                <select class="bp-form-select w-100" name="position" required>
                                    <option value="hero" {{ old('position', 'hero') === 'hero' ? 'selected' : '' }}>Hero
                                        Slider</option>
                                    <option value="promo_large" {{ old('position') === 'promo_large' ? 'selected' : '' }}>
                                        Promotional Large</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Sort Order</label>
                                <input type="number" class="bp-form-control" name="sort_order"
                                    value="{{ old('sort_order', 0) }}" min="0">
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Status</label>
                                <select class="bp-form-select w-100" name="is_active">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-calendar me-2"></i>Schedule</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="bp-form-label">Starts At</label>
                                <input type="datetime-local" class="bp-form-control" name="starts_at"
                                    value="{{ old('starts_at') }}">
                                <div class="fs-11 text-muted mt-1">Leave empty to show immediately</div>
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Ends At</label>
                                <input type="datetime-local" class="bp-form-control" name="ends_at"
                                    value="{{ old('ends_at') }}">
                                <div class="fs-11 text-muted mt-1">Leave empty to show indefinitely</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="bp-btn bp-btn-primary justify-content-center">
                        <i class="fa-solid fa-save me-2"></i> Create Banner
                    </button>
                    <a href="{{ route('ecommerce.banners') }}"
                        class="bp-btn bp-btn-danger justify-content-center">
                        <i class="fa-solid fa-times me-2"></i> Cancel
                    </a>
                </div>

            </div>
        </div>

    </form>

@endsection
