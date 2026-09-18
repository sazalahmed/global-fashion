@extends('core::layouts.master')

@section('title', __("Flash Deals — Website"))
@section('page-title', __("Flash Deals"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Flash Deals</span>
@endsection

@section('page-actions')
@bpCan('ecommerce.create')
<a href="{{ route('ecommerce.flash-deals.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> New Flash Deal
</a>
@endbpCan
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bolt"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Deals</div>
        <div class="bp-stat-value">{{ number_format($stats['total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-play"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Running</div>
        <div class="bp-stat-value">{{ number_format($stats['running']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Upcoming</div>
        <div class="bp-stat-value">{{ number_format($stats['upcoming']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-flag-checkered"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Expired</div>
        <div class="bp-stat-value">{{ number_format($stats['expired']) }}</div>
      </div>
    </div>
  </div>
</div>

<x-core::table>
  <x-core::table.header>
    <x-core::table.column>Title</x-core::table.column>
    <x-core::table.column>Start</x-core::table.column>
    <x-core::table.column>End</x-core::table.column>
    <x-core::table.column align="center">Products</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($flashDeals as $deal)
    <tr>
      <td class="fw-700">{{ $deal->title }}</td>
      <td>{{ $deal->starts_at->format('d M Y, h:i A') }}</td>
      <td>{{ $deal->ends_at->format('d M Y, h:i A') }}</td>
      <td class="text-center fw-700">{{ number_format($deal->products_count) }}</td>
      <td>
        @if(!$deal->is_active)
          <span class="bp-badge bp-badge-dark">Disabled</span>
        @elseif($deal->is_running)
          <span class="bp-badge bp-badge-success">Running</span>
        @elseif($deal->starts_at->isFuture())
          <span class="bp-badge bp-badge-warning">Upcoming</span>
        @else
          <span class="bp-badge bp-badge-danger">Expired</span>
        @endif
      </td>
      <td>
        @bpCanAny('ecommerce.edit','ecommerce.delete')
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            @bpCan('ecommerce.edit')
            <li><a href="{{ route('ecommerce.flash-deals.edit', $deal) }}" class="dropdown-item"><i class="fa-solid fa-pen"></i> Edit</a></li>
            @endbpCan
            @bpCan('ecommerce.delete')
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="{{ route('ecommerce.flash-deals.destroy', $deal) }}" method="POST" class="delete-form">
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
    <x-core::table.empty colspan="6" icon="fa-solid fa-bolt" title="No flash deals found" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">Showing {{ $flashDeals->firstItem() ?? 0 }}-{{ $flashDeals->lastItem() ?? 0 }} of {{ number_format($flashDeals->total()) }} deals</span>
        <nav>{{ $flashDeals->appends(request()->query())->onEachSide(1)->links('core::components.table.pagination-links') }}</nav>
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
