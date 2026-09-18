@extends('core::layouts.master')

@section('title', __("User Management"))
@section('page-title', __("User Management"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Security</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Users</span>
@endsection

@section('page-actions')
  @bpCan('users.create')
  <a href="{{ route('security.users.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add User
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Users</div>
          <div class="bp-stat-value">{{ $totalUsers ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-user-check"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active Users</div>
          <div class="bp-stat-value">{{ $activeUsers ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Roles</div>
          <div class="bp-stat-value">{{ $totalRoles ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-user-plus"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">New This Month</div>
          <div class="bp-stat-value">{{ $newThisMonth ?? 0 }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Users Table -->
  <x-core::table id="usersTable">
    <x-slot:filters>
      <x-core::table.filter-bar action="{{ route('security.users.index') }}" searchPlaceholder="Search name, email, phone..." id="filterForm">
        <select class="bp-form-select" name="role">
          <option value="">All Roles</option>
          @foreach($roles ?? [] as $role)
            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column>User</x-core::table.column>
      <x-core::table.column>Email</x-core::table.column>
      <x-core::table.column>Phone</x-core::table.column>
      <x-core::table.column>Role</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column :sortable="true" field="last_login_at">Last Login</x-core::table.column>
      <x-core::table.column :sortable="true" field="created_at">Created</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($users ?? [] as $user)
      <tr>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="bp-user-avatar-sm">
              @if($user->image)
                <img src="{{ upload_url($user->image) }}" alt="{{ $user->name }}">
              @else
                <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
              @endif
            </div>
            <span class="fw-700 fs-13">{{ $user->name }}</span>
          </div>
        </td>
        <td class="fs-13">{{ $user->email }}</td>
        <td class="fs-13">{{ $user->phone ?? '' }}</td>
        <td>
          @foreach($user->roles as $role)
            @if($role->name === 'Super Admin')
              <span class="bp-badge bp-badge-danger">{{ $role->name }}</span>
            @elseif($role->name === 'Manager')
              <span class="bp-badge bp-badge-success">{{ $role->name }}</span>
            @elseif($role->name === 'Cashier')
              <span class="bp-badge bp-badge-warning">{{ $role->name }}</span>
            @else
              <span class="bp-badge bp-badge-primary">{{ $role->name }}</span>
            @endif
          @endforeach
        </td>
        <td>
          @if($user->status === 'active')
            <span class="bp-badge bp-badge-success">Active</span>
          @else
            <span class="bp-badge bp-badge-danger">Inactive</span>
          @endif
        </td>
        <td class="fs-12 text-muted">{{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : 'Never' }}</td>
        <td class="fs-12 text-muted">{{ $user->created_at->format('d M Y') }}</td>
        <td>
          @bpCanAny('users.view','users.edit','users.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('users.view')
              <li><a class="dropdown-item" href="{{ route('security.users.show', $user->id) }}"><i class="fa-solid fa-eye"></i> View</a></li>
              @endbpCan
              @bpCan('users.edit')
              <li><a class="dropdown-item" href="{{ route('security.users.edit', $user->id) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
              @endbpCan
              @bpCan('users.delete')
              @if($user->id !== auth()->id())
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="{{ route('security.users.destroy', $user->id) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $user->name }}">
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
      <x-core::table.empty :colspan="8" icon="fa-users" title="No users found." />
      @endforelse
    </tbody>

    @if(isset($users) && method_exists($users, 'hasPages'))
    <x-slot:pagination>
      <x-core::table.pagination :paginator="$users" itemLabel="users" />
    </x-slot:pagination>
    @endif
  </x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function() {
    $('#filterForm select').on('change', function() {
        $('#filterForm').submit();
    });
    // Delete confirmation is handled globally by .delete-confirm in app.js
});
</script>
@endpush
