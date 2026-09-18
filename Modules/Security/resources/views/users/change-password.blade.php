@extends('core::layouts.master')

@section('title', __('Change Password'))
@section('page-title', __('Change Password'))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ __('Security') }}</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ __('Change Password') }}</span>
@endsection

@section('page-actions')
  <a href="{{ route('dashboard') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}</a>
@endsection

@section('content')

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-key me-2"></i>{{ __('Change Password') }}</h5>
      </div>
      <form action="{{ route('security.change-password.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="bp-card-body">
          <div class="mb-3">
            <label class="bp-form-label">{{ __('Current Password') }} *</label>
            <input type="password" class="bp-form-control" name="current_password" required autocomplete="current-password">
            @error('current_password')<div class="text-danger fs-12 mt-1">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="bp-form-label">{{ __('New Password') }} *</label>
            <input type="password" class="bp-form-control" name="password" required minlength="8" autocomplete="new-password">
            <small class="text-muted fs-11">{{ __('Minimum 8 characters.') }}</small>
            @error('password')<div class="text-danger fs-12 mt-1">{{ $message }}</div>@enderror
          </div>
          <div class="mb-3">
            <label class="bp-form-label">{{ __('Confirm New Password') }} *</label>
            <input type="password" class="bp-form-control" name="password_confirmation" required minlength="8" autocomplete="new-password">
          </div>
        </div>
        <div class="bp-card-footer text-end">
          <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> {{ __('Update Password') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection
