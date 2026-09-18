@extends('core::layouts.master')

@section('title', __("Roles & Permissions"))
@section('page-title', __("Roles & Permissions"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Security</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Roles &amp; Permissions</span>
@endsection

@section('page-actions')
  @bpCan('roles.create')
  <a href="{{ route('security.roles.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add Role
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Roles</div>
          <div class="bp-stat-value">{{ $totalRoles ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-users"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Users</div>
          <div class="bp-stat-value">{{ $totalUsers ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-key"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Permissions</div>
          <div class="bp-stat-value">{{ $totalPermissions ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-user-shield"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Super Admins</div>
          <div class="bp-stat-value">{{ $superAdmins ?? 0 }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Roles Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search roles...">
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column :sortable="true" field="name">Role Name</x-core::table.column>
      <x-core::table.column>Description</x-core::table.column>
      <x-core::table.column :sortable="true" field="users_count">Users</x-core::table.column>
      <x-core::table.column :sortable="true" field="permissions_count">Permissions</x-core::table.column>
      <x-core::table.column :sortable="true" field="created_at">Created</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($roles ?? [] as $role)
      <tr>
        <td>
          <div class="d-flex align-items-center gap-2">
            @if($role->name === 'Super Admin')
              <span class="bp-role-icon bp-bg-primary-subtle"><i class="fa-solid fa-crown"></i></span>
            @elseif($role->name === 'Manager')
              <span class="bp-role-icon bp-bg-success-subtle"><i class="fa-solid fa-user-tie"></i></span>
            @elseif($role->name === 'Cashier')
              <span class="bp-role-icon bp-bg-accent-subtle"><i class="fa-solid fa-cash-register"></i></span>
            @elseif($role->name === 'Accountant')
              <span class="bp-role-icon bp-bg-info-subtle"><i class="fa-solid fa-calculator"></i></span>
            @elseif($role->name === 'Inventory Staff')
              <span class="bp-role-icon bp-bg-warning-subtle"><i class="fa-solid fa-boxes-stacked"></i></span>
            @else
              <span class="bp-role-icon bp-bg-secondary-subtle"><i class="fa-solid fa-shield-halved"></i></span>
            @endif
            <span class="fw-700 fs-13">{{ $role->name }}</span>
          </div>
        </td>
        <td class="fs-13 text-muted">{{ $role->description ?? '' }}</td>
        <td class="fw-700">{{ $role->users_count ?? 0 }}</td>
        <td>
          @if($role->name === 'Super Admin')
            <span class="bp-badge bp-badge-primary">All ({{ $role->permissions_count ?? 0 }})</span>
          @else
            <span class="bp-badge bp-badge-info">{{ $role->permissions_count ?? 0 }}</span>
          @endif
        </td>
        <td class="fs-12 text-muted">{{ $role->created_at->format('d M Y') }}</td>
        <td>
          @bpCanAny('roles.view','roles.edit','roles.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('roles.edit')
              <li><a class="dropdown-item" href="{{ route('security.roles.edit', $role->id) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
              @endbpCan
              @bpCan('roles.delete')
              @if($role->name !== 'Super Admin')
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="{{ route('security.roles.destroy', $role->id) }}" method="POST">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $role->name }}">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </form>
              </li>
              @endif
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @empty
      <x-core::table.empty :colspan="6" icon="fa-shield-halved" title="No roles found." />
      @endforelse
    </tbody>
  </x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function() {
    // Delete confirmation is handled globally by .delete-confirm in app.js
});
</script>
@endpush
