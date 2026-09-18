@extends('core::layouts.master')

@section('title', __("API Keys"))
@section('page-title', __("API Key Management"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Security</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>API Keys</span>
@endsection

@section('page-actions')
  @bpCan('users.create')
  <a href="{{ route('security.api-keys.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Generate New Key
  </a>
  @endbpCan
@endsection

@section('content')

  @if(session('new_api_key'))
    <div class="alert alert-success alert-dismissible fade show mb-4">
      <i class="fa-solid fa-circle-check me-2"></i>
      <strong>API Key Created!</strong> Copy your key now. It will not be shown again.
      <div class="mt-2">
        <code class="fs-13 user-select-all">{{ session('new_api_key') }}</code>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-key"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total API Keys</div>
          <div class="bp-stat-value">{{ $stats['total'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active Keys</div>
          <div class="bp-stat-value">{{ $stats['active'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-ban"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Revoked</div>
          <div class="bp-stat-value">{{ $stats['revoked'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-clock"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Expired</div>
          <div class="bp-stat-value">{{ $stats['expired'] }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- API Keys Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search API keys...">
        <select class="bp-form-select" name="status">
          <option>All Status</option>
          <option>Active</option>
          <option>Revoked</option>
          <option>Expired</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column :sortable="true" field="name">Key Name</x-core::table.column>
      <x-core::table.column>API Key</x-core::table.column>
      <x-core::table.column :sortable="true" field="created_at">Created</x-core::table.column>
      <x-core::table.column :sortable="true" field="last_used_at">Last Used</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($apiKeys as $key)
        <tr>
          <td>
            <div>
              <span class="fw-700 fs-13">{{ $key->name }}</span>
              @if($key->description)
                <div class="fs-11 text-muted">{{ $key->description }}</div>
              @endif
            </div>
          </td>
          <td>
            <div class="bp-api-key-display">
              <code class="fs-12{{ $key->status !== 'active' ? ' text-muted' : '' }}">{{ $key->key_prefix }}</code>
            </div>
          </td>
          <td class="fs-12 text-muted">{{ $key->created_at->format('d M Y') }}</td>
          <td class="fs-12 text-muted">{{ $key->last_used_at ? $key->last_used_at->format('d M Y, h:i A') : 'Never' }}</td>
          <td>
            @if($key->status === 'active')
              <span class="bp-badge bp-badge-success">Active</span>
            @elseif($key->status === 'revoked')
              <span class="bp-badge bp-badge-danger">Revoked</span>
            @else
              <span class="bp-badge bp-badge-warning">Expired</span>
            @endif
          </td>
          <td>
            @bpCanAny('users.view','users.delete')
            <div class="dropdown">
              <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
              <ul class="dropdown-menu dropdown-menu-end">
                @bpCan('users.delete')
                <li>
                  <form action="{{ route('security.api-keys.destroy', $key->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $key->name }}"><i class="fa-solid fa-trash"></i> Delete</button>
                  </form>
                </li>
                @endbpCan
              </ul>
            </div>
            @endbpCanAny
          </td>
        </tr>
      @empty
        <x-core::table.empty :colspan="6" icon="fa-key" title="No API keys found." description="Generate your first key to get started." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      @if($apiKeys->hasPages())
        <x-core::table.pagination-links :paginator="$apiKeys" />
      @endif
    </x-slot:pagination>
  </x-core::table>

@endsection

@push('scripts')
<script>
  'use strict';

  $(document).ready(function () {
    // Delete confirmation is handled globally by .delete-confirm in app.js
  });
</script>
@endpush
