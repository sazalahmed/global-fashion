@extends('core::layouts.master')

@section('title', __("Security"))
@section('page-title', __("Security & Access"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Security</span>
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Users</div>
        <div class="bp-stat-value">{{ $stats['users'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-user-check"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Active Users</div>
        <div class="bp-stat-value">{{ $stats['active_users'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-shield-halved"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Roles</div>
        <div class="bp-stat-value">{{ $stats['roles'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-info"><i class="fa-solid fa-key"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">API Keys</div>
        <div class="bp-stat-value">{{ $stats['api_keys'] }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Links -->
<div class="row g-3">

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('security.users.index') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-primary mx-auto mb-3"><i class="fa-solid fa-users"></i></div>
          <h6 class="fw-800 mb-1">Users</h6>
          <p class="fs-12 text-muted mb-0">Manage user accounts & status</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('security.roles') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-warning mx-auto mb-3"><i class="fa-solid fa-shield-halved"></i></div>
          <h6 class="fw-800 mb-1">Roles & Permissions</h6>
          <p class="fs-12 text-muted mb-0">Define roles and assign permissions</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('security.api-keys') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-info mx-auto mb-3"><i class="fa-solid fa-key"></i></div>
          <h6 class="fw-800 mb-1">API Keys</h6>
          <p class="fs-12 text-muted mb-0">Manage API access tokens</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('security.backup') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-danger mx-auto mb-3"><i class="fa-solid fa-database"></i></div>
          <h6 class="fw-800 mb-1">Backup</h6>
          <p class="fs-12 text-muted mb-0">Database backup & restore</p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4 col-lg-3">
    <a href="{{ route('activities.index') }}" class="text-decoration-none">
      <div class="bp-card h-100 bp-card-hover">
        <div class="bp-card-body text-center py-4">
          <div class="bp-stat-icon icon-dark mx-auto mb-3"><i class="fa-solid fa-clock-rotate-left"></i></div>
          <h6 class="fw-800 mb-1">Activity Log</h6>
          <p class="fs-12 text-muted mb-0">View system activity history</p>
        </div>
      </div>
    </a>
  </div>

</div>

@endsection
