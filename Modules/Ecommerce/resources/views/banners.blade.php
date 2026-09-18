@extends('core::layouts.master')

@section('title', __("Banners — Website"))
@section('page-title', __("Banners"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Banners</span>
@endsection

@section('page-actions')
@bpCan('ecommerce.create')
<a href="{{ route('ecommerce.banners.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> New Banner
</a>
@endbpCan
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-images"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Banners</div>
        <div class="bp-stat-value">{{ number_format($stats['total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-desktop"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Active Hero</div>
        <div class="bp-stat-value">{{ number_format($stats['active_hero']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-info"><i class="fa-solid fa-rectangle-ad"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Active Promo</div>
        <div class="bp-stat-value">{{ number_format($stats['active_promo']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Scheduled</div>
        <div class="bp-stat-value">{{ number_format($stats['scheduled']) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Banners Table -->
<x-core::table>
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search banners...">
      <select class="bp-form-select" name="position" onchange="this.form.submit()">
        <option value="">All Positions</option>
        <option value="hero" {{ request('position') === 'hero' ? 'selected' : '' }}>Hero</option>
        <option value="promo_large" {{ request('position') === 'promo_large' ? 'selected' : '' }}>Promo Large</option>
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-core::table.header>
    <x-core::table.column>Image</x-core::table.column>
    <x-core::table.column>Title</x-core::table.column>
    <x-core::table.column>Position</x-core::table.column>
    <x-core::table.column align="center">Order</x-core::table.column>
    <x-core::table.column>Schedule</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($banners as $banner)
    <tr>
      <td>
        <img src="{{ upload_url($banner->image) }}" alt="{{ $banner->title }}" class="bp-table-thumb">
      </td>
      <td>
        <div class="fw-700">{{ $banner->title }}</div>
        @if($banner->subtitle)
          <div class="fs-12 text-muted">{{ Str::limit($banner->subtitle, 40) }}</div>
        @endif
      </td>
      <td>
        @if($banner->position === 'hero')
          <span class="bp-badge bp-badge-primary">Hero</span>
        @else
          <span class="bp-badge bp-badge-info">Promo Large</span>
        @endif
      </td>
      <td class="text-center">{{ $banner->sort_order }}</td>
      <td>
        @if($banner->starts_at || $banner->ends_at)
          <div class="fs-12">{{ $banner->starts_at ? $banner->starts_at->format('d M Y') : '' }}</div>
          <div class="fs-12 text-muted">to {{ $banner->ends_at ? $banner->ends_at->format('d M Y') : '' }}</div>
        @else
          <span class="text-muted">Always</span>
        @endif
      </td>
      <td>
        @bpCan('ecommerce.edit')
        <x-core::status-toggle :url="route('ecommerce.banners.toggle-status', $banner->id)" :active="$banner->is_active" />
        @endbpCan
      </td>
      <td>
        @bpCanAny('ecommerce.edit','ecommerce.delete')
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            @bpCan('ecommerce.edit')
            <li><a href="{{ route('ecommerce.banners.edit', $banner) }}" class="dropdown-item"><i class="fa-solid fa-pen"></i> Edit</a></li>
            @endbpCan
            @bpCan('ecommerce.delete')
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="{{ route('ecommerce.banners.destroy', $banner) }}" method="POST" class="delete-form">
                @csrf @method('DELETE')
                <button type="submit" class="dropdown-item text-danger delete-confirm"><i class="fa-solid fa-trash"></i> Delete</button>
              </form>
            </li>
            @endbpCan
          </ul>
        </div>
        @endbpCanAny
      </td>
    </tr>
    @empty
    <x-core::table.empty colspan="7" icon="fa-solid fa-image" title="No banners found" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">Showing {{ $banners->firstItem() ?? 0 }}-{{ $banners->lastItem() ?? 0 }} of {{ number_format($banners->total()) }} banners</span>
        <nav>{{ $banners->appends(request()->query())->onEachSide(1)->links('core::components.table.pagination-links') }}</nav>
      </div>
    </div>
  </x-slot:pagination>
</x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Delete confirmation is handled globally by .delete-confirm in app.js
});
</script>
@endpush
