@extends('core::layouts.master')

@section('title', __('Edit Profile'))
@section('page-title', __('Edit Profile'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ __('Security') }}</span>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('security.users.show', $user->id) }}">{{ __('My Profile') }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ __('Edit') }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('security.users.show', $user->id) }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}</a>
    <a href="{{ route('security.change-password') }}" class="bp-btn bp-btn-warning"><i class="fa-solid fa-key me-1"></i>
        {{ __('Change Password') }}</a>
@endsection

@section('content')

    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-user-pen me-2"></i>{{ __('Edit Profile') }}</h5>
                </div>
                <form action="{{ route('security.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="bp-card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="text-center mb-4">
                                    <div class="bp-user-avatar-lg mb-2">
                                        @if ($user->image)
                                            <img src="{{ upload_url($user->image) }}" alt="{{ $user->name }}">
                                        @else
                                            <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="bp-form-label">{{ __('Full Name') }} *</label>
                                    <input type="text" class="bp-form-control" name="name"
                                        value="{{ old('name', $user->name) }}" required>
                                    @error('name')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="bp-form-label">{{ __('Phone') }}</label>
                                    <input type="text" class="bp-form-control" name="phone"
                                        value="{{ old('phone', $user->phone) }}">
                                    @error('phone')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="bp-form-label">{{ __('Email') }} *</label>
                                    <input type="email" class="bp-form-control" name="email"
                                        value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-1">
                                    <x-core::image-upload name="image" id="profilePhotoInput" :label="__('Profile Photo')"
                                        :hint="__('JPG, PNG or WEBP. Max 2MB.')" :current="$user->image ? upload_url($user->image) : null" remove-name="remove_image" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bp-card-footer text-end">
                        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>
                            {{ __('Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
