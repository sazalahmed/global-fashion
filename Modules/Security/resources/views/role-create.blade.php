@extends('core::layouts.master')

@section('title', __("Create Role"))
@section('page-title', __("Create Role"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Security</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('security.roles') }}">Roles &amp; Permissions</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Create</span>
@endsection

@section('page-actions')
  <a href="{{ route('security.roles') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Roles
  </a>
@endsection

@section('content')

  <form action="{{ route('security.roles.store') }}" method="POST">
    @csrf

    <!-- Role Information -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-shield-halved me-2"></i>Role Information</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="bp-form-label">Role Name *</label>
            <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="e.g., Branch Manager">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Description</label>
            <input type="text" class="bp-form-control @error('description') is-invalid @enderror" name="description" value="{{ old('description') }}" placeholder="Brief description of this role">
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>

    <!-- Permissions Matrix -->
    <div class="bp-card mb-4">
      <div class="bp-card-header d-flex align-items-center justify-content-between">
        <h5 class="bp-card-title"><i class="fa-solid fa-key me-2"></i>Permissions</h5>
        <div class="d-flex gap-2">
          <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="btn-select-all">
            <i class="fa-solid fa-check-double me-1"></i> Select All
          </button>
          <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="btn-deselect-all">
            <i class="fa-solid fa-xmark me-1"></i> Deselect All
          </button>
        </div>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table bp-permissions-table">
            <thead>
              <tr>
                <th>Module</th>
                <th class="text-center">View</th>
                <th class="text-center">Create</th>
                <th class="text-center">Edit</th>
                <th class="text-center">Delete</th>
                <th class="text-center">Export</th>
                <th class="text-center">Other</th>
                <th class="text-center">All</th>
              </tr>
            </thead>
            <tbody>
              @php
                $standardActions = ['view', 'create', 'edit', 'delete', 'export'];
              @endphp
              @foreach($permissionGroups ?? [] as $group => $permissions)
              <tr data-module="{{ $group }}">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-600 fs-13 text-capitalize">{{ str_replace('_', ' ', $group) }}</span>
                  </div>
                </td>
                @foreach($standardActions as $action)
                  <td class="text-center">
                    @php $permName = $group . '.' . $action; @endphp
                    @if(in_array($permName, $permissions))
                      <input type="checkbox" class="bp-form-check perm-check" name="permissions[]" value="{{ $permName }}" {{ in_array($permName, old('permissions', [])) ? 'checked' : '' }}>
                    @else
                      <span class="text-muted">--</span>
                    @endif
                  </td>
                @endforeach
                <td class="text-center">
                  @php
                    $otherPerms = array_filter($permissions, function($p) use ($group, $standardActions) {
                      $action = str_replace($group . '.', '', $p);
                      return !in_array($action, $standardActions);
                    });
                  @endphp
                  @foreach($otherPerms as $perm)
                    <div class="d-inline-block me-1">
                      <input type="checkbox" class="bp-form-check perm-check" name="permissions[]" value="{{ $perm }}" id="perm_{{ str_replace('.', '_', $perm) }}" {{ in_array($perm, old('permissions', [])) ? 'checked' : '' }}>
                      <label class="fs-11 text-muted" for="perm_{{ str_replace('.', '_', $perm) }}">{{ ucfirst(str_replace($group . '.', '', $perm)) }}</label>
                    </div>
                  @endforeach
                  @if(empty($otherPerms))
                    <span class="text-muted">--</span>
                  @endif
                </td>
                <td class="text-center"><input type="checkbox" class="bp-form-check module-select-all"></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Footer Actions -->
    <div class="bp-card">
      <div class="bp-card-footer text-end">
        <a href="{{ route('security.roles') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save Role</button>
      </div>
    </div>

  </form>

@endsection

@push('scripts')
<script>
  'use strict';

  $(document).ready(function () {
    $('#btn-select-all').on('click', function () {
      $('.perm-check, .module-select-all').prop('checked', true);
    });

    $('#btn-deselect-all').on('click', function () {
      $('.perm-check, .module-select-all').prop('checked', false);
    });

    $('.module-select-all').on('change', function () {
      var $row = $(this).closest('tr');
      $row.find('.perm-check').prop('checked', $(this).prop('checked'));
    });

    $('.perm-check').on('change', function () {
      var $row = $(this).closest('tr');
      var totalChecks = $row.find('.perm-check').length;
      var checkedChecks = $row.find('.perm-check:checked').length;
      $row.find('.module-select-all').prop('checked', totalChecks === checkedChecks);
    });
  });
</script>
@endpush
