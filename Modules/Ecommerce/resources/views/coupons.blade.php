@extends('core::layouts.master')

@section('title', __("Coupons — Website"))
@section('page-title', __("Coupons"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Coupons</span>
@endsection

@section('page-actions')
@bpCan('ecommerce.create')
<a href="{{ route('ecommerce.coupons.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> New Coupon
</a>
@endbpCan
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-ticket"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Coupons</div>
        <div class="bp-stat-value">{{ number_format($coupons->total()) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Active</div>
        <div class="bp-stat-value">{{ $coupons->where('is_active', true)->count() }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-info"><i class="fa-solid fa-chart-bar"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Used</div>
        <div class="bp-stat-value">{{ number_format($coupons->sum('used_count')) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Expired</div>
        <div class="bp-stat-value">{{ $coupons->filter(fn($c) => $c->end_date && $c->end_date->isPast())->count() }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Coupons Table -->
<x-core::table>
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search coupon code...">
      <select class="bp-form-select" name="type">
        <option>All Types</option>
        <option>Percentage</option>
        <option>Fixed Amount</option>
      </select>
      <select class="bp-form-select" name="status">
        <option>All Status</option>
        <option>Active</option>
        <option>Expired</option>
        <option>Disabled</option>
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-core::table.header>
    <x-core::table.column>Code</x-core::table.column>
    <x-core::table.column>Description</x-core::table.column>
    <x-core::table.column>Type</x-core::table.column>
    <x-core::table.column align="end">Value</x-core::table.column>
    <x-core::table.column align="end">Min Order</x-core::table.column>
    <x-core::table.column align="center">Usage</x-core::table.column>
    <x-core::table.column>Valid From</x-core::table.column>
    <x-core::table.column>Valid To</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($coupons as $coupon)
    <tr>
      <td><code class="fw-800 fs-13">{{ $coupon->code }}</code></td>
      <td>{{ $coupon->name }}</td>
      <td>
        @if($coupon->type === 'percentage')
          <span class="bp-badge bp-badge-info">Percentage</span>
        @else
          <span class="bp-badge bp-badge-primary">Fixed Amount</span>
        @endif
      </td>
      <td class="text-end fw-700">
        @if($coupon->type === 'percentage')
          {{ number_format($coupon->value, 0) }}%
        @else
          {{ currency_symbol() }} {{ number_format($coupon->value, 0) }}
        @endif
      </td>
      <td class="text-end">{{ currency_symbol() }} {{ number_format($coupon->min_order_amount, 0) }}</td>
      <td class="text-center">
        <span class="fw-700">{{ number_format($coupon->used_count) }}</span>
        <span class="text-muted fs-11">/ {{ $coupon->usage_limit ? number_format($coupon->usage_limit) : 'Unlimited' }}</span>
      </td>
      <td>{{ $coupon->start_date ? $coupon->start_date->format('d M Y') : '' }}</td>
      <td>{{ $coupon->end_date ? $coupon->end_date->format('d M Y') : '' }}</td>
      <td>
        @if(!$coupon->is_active)
          <span class="bp-badge bp-badge-dark">Disabled</span>
        @elseif($coupon->end_date && $coupon->end_date->isPast())
          <span class="bp-badge bp-badge-danger">Expired</span>
        @elseif($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit)
          <span class="bp-badge bp-badge-warning">Exhausted</span>
        @else
          <span class="bp-badge bp-badge-success">Active</span>
        @endif
      </td>
      <td>
        @bpCanAny('ecommerce.edit','ecommerce.delete')
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            @bpCan('ecommerce.edit')
            <li>
              <a href="{{ route('ecommerce.coupons.edit', $coupon) }}" class="dropdown-item">
                <i class="fa-solid fa-pen"></i> Edit
              </a>
            </li>
            @endbpCan
            @bpCan('ecommerce.delete')
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="{{ route('ecommerce.coupons.destroy', $coupon->id) }}" method="POST" class="delete-form">
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
    <x-core::table.empty colspan="10" icon="fa-solid fa-ticket" title="No coupons found" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">Showing {{ $coupons->firstItem() ?? 0 }}-{{ $coupons->lastItem() ?? 0 }} of {{ number_format($coupons->total()) }} coupons</span>
        <nav>{{ $coupons->appends(request()->query())->onEachSide(1)->links('core::components.table.pagination-links') }}</nav>
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
