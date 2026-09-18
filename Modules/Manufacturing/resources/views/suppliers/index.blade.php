@extends('core::layouts.master')

@section('title', __("Raw Material Suppliers"))
@section('page-title', __("Raw Material Suppliers"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Suppliers</span>
@endsection

@section('page-actions')
  @bpCan('manufacturing.create')
  <a href="{{ route('manufacturing.suppliers.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add Supplier
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-truck-field"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Suppliers</div>
          <div class="bp-stat-value">{{ $stats['total'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active</div>
          <div class="bp-stat-value">{{ $stats['active'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Due</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_due'] ?? 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Suppliers Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search company, contact, phone...">
        <select class="bp-form-select" name="is_active">
          <option value="">All Status</option>
          <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
          <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
        </select>
        <select class="bp-form-select" name="payment_status">
          <option value="">All Payment Status</option>
          <option value="has_due" {{ request('payment_status') == 'has_due' ? 'selected' : '' }}>Has Due</option>
          <option value="no_due" {{ request('payment_status') == 'no_due' ? 'selected' : '' }}>No Due</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column>Company</x-core::table.column>
      <x-core::table.column>Contact</x-core::table.column>
      <x-core::table.column>Phone</x-core::table.column>
      <x-core::table.column align="end">Due Balance</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($suppliers as $supplier)
      <tr>
        <td class="fw-700 fs-13">{{ $supplier->company_name }}</td>
        <td>{{ $supplier->contact_person ?? '' }}</td>
        <td>{{ $supplier->phone ?? '' }}</td>
        <td class="text-end {{ $supplier->due_balance > 0 ? 'text-danger fw-700' : '' }}">{{ currency_symbol() }} {{ number_format($supplier->due_balance, 0) }}</td>
        <td>
          <x-core::status-toggle :url="route('manufacturing.suppliers.toggle-status', $supplier->id)" :active="$supplier->is_active" />
        </td>
        <td>
          @bpCanAny('manufacturing.view', 'manufacturing.edit', 'manufacturing.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('manufacturing.view')
              <li><a class="dropdown-item" href="{{ route('manufacturing.suppliers.show', $supplier) }}"><i class="fa-solid fa-eye"></i> View</a></li>
              @endbpCan
              @bpCan('manufacturing.edit')
              <li><a class="dropdown-item" href="{{ route('manufacturing.suppliers.edit', $supplier) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
              @endbpCan
              @bpCan('manufacturing.delete')
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="{{ route('manufacturing.suppliers.destroy', $supplier) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete {{ $supplier->company_name }}?')"><i class="fa-solid fa-trash"></i> Delete</button>
                </form>
              </li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="6" icon="fa-solid fa-truck-field" title="No suppliers found." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$suppliers" itemLabel="suppliers" />
    </x-slot:pagination>
  </x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function() {
    $('.bp-filter-reset').on('click', function() {
        // Clear all filters — go to the clean path (no query string).
        window.location.href = window.location.pathname;
    });
});
</script>
@endpush
