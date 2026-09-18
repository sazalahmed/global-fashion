@extends('core::layouts.master')

@section('title', isset($user) ? 'Edit User' : 'Add User')
@section('page-title', isset($user) ? 'Edit User' : 'Add User')

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Security</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('security.users.index') }}">Users</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ isset($user) ? 'Edit' : 'Add New' }}</span>
@endsection

@section('page-actions')
  <a href="{{ route('security.users.index') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back
  </a>
@endsection

@section('content')

  <form action="{{ isset($user) ? route('security.users.update', $user->id) : route('security.users.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($user))
      @method('PUT')
    @endif

    <div class="row g-4">
      <!-- Left Column -->
      <div class="col-xl-8">

        <!-- Basic Information -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-user me-2 text-primary"></i>User Information</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-6 col-lg-4">
                <label class="bp-form-label">Full Name *</label>
                <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name ?? '') }}" required placeholder="Enter full name">
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6 col-lg-4">
                <label class="bp-form-label">Email Address *</label>
                <input type="email" class="bp-form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email ?? '') }}" required placeholder="user@example.com">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6 col-lg-4">
                <label class="bp-form-label">Phone Number</label>
                <input type="text" class="bp-form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone ?? '') }}" placeholder="01XXXXXXXXX">
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <x-core::image-upload name="image" label="Profile Image" :current="isset($user) && $user->image ? upload_url($user->image) : null" />
              </div>
            </div>
          </div>
        </div>

        <!-- Password -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-lock me-2 text-warning"></i>Password</h5>
            @if(isset($user))
              <span class="fs-12 text-muted">Leave blank to keep current password</span>
            @endif
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="bp-form-label">{{ isset($user) ? 'New Password' : 'Password *' }}</label>
                <input type="password" class="bp-form-control @error('password') is-invalid @enderror" name="password" {{ isset($user) ? '' : 'required' }} placeholder="Min 8 characters" autocomplete="new-password">
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">{{ isset($user) ? 'Confirm New Password' : 'Confirm Password *' }}</label>
                <input type="password" class="bp-form-control" name="password_confirmation" {{ isset($user) ? '' : 'required' }} placeholder="Re-enter password" autocomplete="new-password">
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column -->
      <div class="col-xl-4">

        <!-- Role & Status -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-shield-halved me-2 text-success"></i>Role & Status</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="bp-form-label">Role *</label>
                <select class="bp-form-select w-100 @error('role') is-invalid @enderror" name="role" required>
                  <option value="">Select Role</option>
                  @foreach($roles ?? [] as $role)
                    <option value="{{ $role->name }}" {{ old('role', isset($user) && $user->roles->first() ? $user->roles->first()->name : '') == $role->name ? 'selected' : '' }}>
                      {{ $role->name }}
                    </option>
                  @endforeach
                </select>
                @error('role')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label class="bp-form-label">Status *</label>
                <select class="bp-form-select w-100 @error('status') is-invalid @enderror" name="status" required>
                  <option value="active" {{ old('status', $user->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                  <option value="inactive" {{ old('status', $user->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12 d-none">
                <label class="bp-form-label">Branch</label>
                <select class="bp-form-select w-100" name="branch_id">
                  <option value="">All Branches</option>
                  {{-- TODO: Populate from branches table --}}
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Save Actions -->
        <div class="bp-card">
          <div class="bp-card-body d-flex flex-column gap-2">
            <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
              <i class="fa-solid fa-check me-1"></i> {{ isset($user) ? 'Update User' : 'Create User' }}
            </button>
            <a href="{{ route('security.users.index') }}" class="bp-btn bp-btn-danger w-100 justify-content-center"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
          </div>
        </div>

      </div>
    </div>

  </form>

@isset($user)
  @if($user->hasTwoFactorEnabled())
  <div class="bp-card mt-3">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-mobile-screen-button me-2"></i>{{ __('Two-Factor Authentication') }}</h5>
    </div>
    <div class="bp-card-body">
      <p class="fs-13 text-muted">{{ __('This user has Authenticator enabled. If they lost their device, reset it so they can recover via SMS and re-enroll.') }}</p>
      <form method="POST" action="{{ route('security.users.reset-two-factor', $user->id) }}"
            onsubmit="return confirm('{{ __('Reset Two-Factor for this user?') }}');">
        @csrf
        @method('PATCH')
        <button type="submit" class="bp-btn bp-btn-danger bp-btn-sm"><i class="fa-solid fa-rotate-left me-1"></i>{{ __('Reset Two-Factor') }}</button>
      </form>
    </div>
  </div>
  @endif
@endisset

@endsection
