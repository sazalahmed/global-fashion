@extends('core::layouts.master')

@section('title', __("Branches"))
@section('page-title', __("Branches"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Branches</span>
@endsection

@section('page-actions')
  <a href="{{ route('branches.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add Branch
  </a>
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-store"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Branches</div>
          <div class="bp-stat-value">{{ $totalBranches ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active</div>
          <div class="bp-stat-value">{{ $activeBranches ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-cash-register"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">POS Enabled</div>
          <div class="bp-stat-value">{{ $posEnabled ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-globe"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">eCom Enabled</div>
          <div class="bp-stat-value">{{ $ecomEnabled ?? 0 }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Branches Table -->
  <x-core::table id="branchesTable">
    <x-slot:filters>
      <x-core::table.filter-bar action="{{ route('branches.index') }}" searchPlaceholder="Search branch name, code..." id="filterForm">
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column :sortable="true" field="name">Branch Name</x-core::table.column>
      <x-core::table.column>Code</x-core::table.column>
      <x-core::table.column>City / District</x-core::table.column>
      <x-core::table.column>Phone</x-core::table.column>
      <x-core::table.column>Manager</x-core::table.column>
      <x-core::table.column>POS</x-core::table.column>
      <x-core::table.column>eCom</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($branches ?? [] as $branch)
      <tr>
        <td>
          <div class="d-flex align-items-center gap-2">
            @if($branch->is_main)
              <span class="bp-role-icon bp-bg-primary-subtle"><i class="fa-solid fa-crown"></i></span>
            @else
              <span class="bp-role-icon bp-bg-success-subtle"><i class="fa-solid fa-store"></i></span>
            @endif
            <div>
              <span class="fw-700 fs-13">{{ $branch->name }}</span>
              @if($branch->is_main)
                <span class="bp-badge bp-badge-primary ms-1">Main</span>
              @endif
            </div>
          </div>
        </td>
        <td><code class="bp-code">{{ $branch->code }}</code></td>
        <td class="fs-13">{{ $branch->city ?? '' }}{{ $branch->district ? ', ' . $branch->district : '' }}</td>
        <td class="fs-13">{{ $branch->phone ?? '' }}</td>
        <td class="fs-13 fw-600">{{ $branch->manager_name ?? '' }}</td>
        <td>
          @if($branch->is_pos_enabled)
            <span class="bp-badge bp-badge-success">Yes</span>
          @else
            <span class="bp-badge bp-badge-danger">No</span>
          @endif
        </td>
        <td>
          @if($branch->is_ecom_enabled)
            <span class="bp-badge bp-badge-success">Yes</span>
          @else
            <span class="bp-badge bp-badge-danger">No</span>
          @endif
        </td>
        <td>
          <x-core::status-toggle :url="route('branches.toggle-status', $branch->id)" :active="$branch->is_active" />
        </td>
        <td>
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="{{ route('branches.show', $branch->id) }}"><i class="fa-solid fa-eye"></i> View</a></li>
              <li><a class="dropdown-item" href="{{ route('branches.edit', $branch->id) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
              @if(!$branch->is_main)
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" class="delete-form">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $branch->name }}">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </form>
              </li>
              @endif
            </ul>
          </div>
        </td>
      </tr>
      @empty
      <x-core::table.empty :colspan="9" icon="fa-store" title="No branches found." />
      @endforelse
    </tbody>

    @if(isset($branches) && method_exists($branches, 'hasPages'))
    <x-slot:pagination>
      <x-core::table.pagination :paginator="$branches" itemLabel="branches" />
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
