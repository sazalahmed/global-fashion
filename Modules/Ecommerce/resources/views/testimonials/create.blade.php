@extends('core::layouts.master')

@section('title', __('Add Testimonial'))
@section('page-title', __('Add Testimonial'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.testimonials.index') }}">Testimonials</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.testimonials.index') }}" class="bp-btn bp-btn-light">
        <i class="fa-solid fa-arrow-left"></i> Back to List
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="bp-card">
                <div class="bp-card-body">
                    <form action="{{ route('ecommerce.testimonials.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation') }}">
                                @error('designation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="4" required>{{ old('message') }}</textarea>
                                @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rating <span class="text-danger">*</span></label>
                                <select name="rating" class="form-select @error('rating') is-invalid @enderror" required>
                                    <option value="5" {{ old('rating', 5) == 5 ? 'selected' : '' }}>5 Stars</option>
                                    <option value="4" {{ old('rating') == 4 ? 'selected' : '' }}>4 Stars</option>
                                    <option value="3" {{ old('rating') == 3 ? 'selected' : '' }}>3 Stars</option>
                                    <option value="2" {{ old('rating') == 2 ? 'selected' : '' }}>2 Stars</option>
                                    <option value="1" {{ old('rating') == 1 ? 'selected' : '' }}>1 Star</option>
                                </select>
                                @error('rating') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Image</label>
                                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="bp-btn bp-btn-primary">
                                <i class="fa-solid fa-save"></i> Save Testimonial
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
