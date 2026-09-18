@extends('core::layouts.master')

@section('title', __("Stock Adjustments"))
@section('page-title', __("Stock Adjustments"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('inventory.index') }}">Inventory</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Adjustments</span>
@endsection

@section('page-actions')
@bpCan('inventory.export')
<x-core::export-dropdown module="stock-adjustments" />
@endbpCan
@bpCan('inventory.create')
<a href="{{ route('inventory.adjustments.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> New Adjustment
</a>
@endbpCan
@endsection

@section('content')

<!-- Adjustments Table -->
<x-core::table :selectable="true">
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search reference, product...">
      <input type="date" class="bp-form-control bp-filter-date" name="date_from" value="{{ request('date_from') }}">
      <span class="text-muted">to</span>
      <input type="date" class="bp-form-control bp-filter-date" name="date_to" value="{{ request('date_to') }}">
      <select class="bp-form-select" name="status">
        <option value="">All Status</option>
        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
      </select>
      <select class="bp-form-select" name="type">
        <option value="">All Types</option>
        <option value="addition" {{ request('type') == 'addition' ? 'selected' : '' }}>Addition</option>
        <option value="subtraction" {{ request('type') == 'subtraction' ? 'selected' : '' }}>Subtraction</option>
      </select>
      <select class="bp-form-select" name="reason">
        <option value="">All Reasons</option>
        <option value="damage" {{ request('reason') == 'damage' ? 'selected' : '' }}>Damage</option>
        <option value="expired" {{ request('reason') == 'expired' ? 'selected' : '' }}>Expired</option>
        <option value="lost" {{ request('reason') == 'lost' ? 'selected' : '' }}>Lost</option>
        <option value="correction" {{ request('reason') == 'correction' ? 'selected' : '' }}>Correction</option>
        <option value="opening_stock" {{ request('reason') == 'opening_stock' ? 'selected' : '' }}>Opening Stock</option>
        <option value="other" {{ request('reason') == 'other' ? 'selected' : '' }}>Other</option>
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-slot:bulkActions>
    <x-core::table.bulk-actions>
      @bpCan('inventory.edit')
      <button class="bp-btn bp-btn-sm bp-btn-outline" data-bulk-action="status" data-bulk-status="approved"><i class="fa-solid fa-check me-1"></i> Approve</button>
      <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="status" data-bulk-status="cancelled"><i class="fa-solid fa-ban me-1"></i> Cancel</button>
      @endbpCan
      @bpCan('inventory.delete')
      <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="delete"><i class="fa-solid fa-trash me-1"></i> Delete</button>
      @endbpCan
    </x-core::table.bulk-actions>
  </x-slot:bulkActions>

  <x-core::table.header :selectable="true">
    <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
    <x-core::table.column :sortable="true" field="adjustment_number">Reference #</x-core::table.column>
    <x-core::table.column>Type</x-core::table.column>
    <x-core::table.column>Items</x-core::table.column>
    <x-core::table.column>Reason</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Adjusted By</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($adjustments as $adjustment)
      <tr>
        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $adjustment->id }}"></td>
        <td>{{ $adjustment->created_at->format('d M Y') }}</td>
        <td class="fw-700">{{ $adjustment->adjustment_number }}</td>
        <td>
          @if($adjustment->type === 'addition')
            <span class="bp-badge bp-badge-success">Addition</span>
          @else
            <span class="bp-badge bp-badge-danger">Subtraction</span>
          @endif
        </td>
        <td class="fw-600">
          @if($adjustment->items->count() === 1)
            {{ $adjustment->items->first()->product->name ?? '' }}
          @else
            {{ $adjustment->items->count() }} {{ Str::plural('item', $adjustment->items->count()) }}
          @endif
        </td>
        <td>{{ ucfirst(str_replace('_', ' ', $adjustment->reason)) }}</td>
        <td>
          @if($adjustment->status === 'approved')
            <span class="bp-badge bp-badge-success">Approved</span>
          @elseif($adjustment->status === 'cancelled')
            <span class="bp-badge bp-badge-danger">Cancelled</span>
          @else
            <span class="bp-badge bp-badge-warning">Draft</span>
          @endif
        </td>
        <td>{{ $adjustment->creator->name ?? '' }}</td>
        <td>
          @bpCanAny('inventory.view')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('inventory.view')
              <li><a class="dropdown-item" href="{{ route('inventory.adjustments.show', $adjustment) }}"><i class="fa-solid fa-eye"></i> View</a></li>
              <li><a class="dropdown-item" href="{{ route('inventory.adjustments.print', $adjustment) }}" target="_blank"><i class="fa-solid fa-print"></i> Print</a></li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
    @empty
      <x-core::table.empty :colspan="9" icon="fa-sliders" :title="__('No stock adjustments found')" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <x-core::table.pagination :paginator="$adjustments" itemLabel="adjustments" />
  </x-slot:pagination>
</x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Reset filter handler
    $('.bp-filter-reset').on('click', function () {
        // Clear all filters — go to the clean path (no query string).
        window.location.href = window.location.pathname;
    });
});
</script>
@endpush
