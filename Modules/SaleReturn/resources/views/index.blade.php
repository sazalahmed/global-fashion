@extends('core::layouts.master')

@section('title', __("Sale Returns"))
@section('page-title', __("Sale Returns"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Sale Returns</span>
@endsection

@section('page-actions')
@bpCan('sales.export')
<x-core::export-dropdown module="sale-returns" />
@endbpCan
@bpCan('sales.create')
<a href="{{ route('sale-returns.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> New Sale Return
</a>
@endbpCan
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-undo"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Returns</div>
        <div class="bp-stat-value">{{ number_format($stats['total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Pending</div>
        <div class="bp-stat-value">{{ number_format($stats['pending']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Completed</div>
        <div class="bp-stat-value">{{ number_format($stats['completed']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Return Value</div>
        <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_value'], 0) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Sale Returns Table -->
<x-core::table :selectable="true">
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search return #, invoice, customer...">
      <input type="date" class="bp-form-control bp-filter-date" name="date_from" value="{{ request('date_from') }}">
      <span class="text-muted">to</span>
      <input type="date" class="bp-form-control bp-filter-date" name="date_to" value="{{ request('date_to') }}">
      <select class="bp-form-select" name="status">
        <option value="">All Status</option>
        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
      </select>
      <select class="bp-form-select" name="reason">
        <option value="">All Reasons</option>
        <option value="defective" {{ request('reason') === 'defective' ? 'selected' : '' }}>Defective</option>
        <option value="wrong_item" {{ request('reason') === 'wrong_item' ? 'selected' : '' }}>Wrong Item</option>
        <option value="customer_request" {{ request('reason') === 'customer_request' ? 'selected' : '' }}>Customer Request</option>
        <option value="damaged" {{ request('reason') === 'damaged' ? 'selected' : '' }}>Damaged</option>
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-slot:bulkActions>
    <x-core::table.bulk-actions>
      <button class="bp-btn bp-btn-sm bp-btn-outline" data-bulk-action="status" data-bulk-status="approved"><i class="fa-solid fa-check me-1"></i> Approve</button>
      <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="status" data-bulk-status="cancelled"><i class="fa-solid fa-ban me-1"></i> Cancel</button>
      @bpCan('sales.delete')
      <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="delete"><i class="fa-solid fa-trash me-1"></i> Delete</button>
      @endbpCan
    </x-core::table.bulk-actions>
  </x-slot:bulkActions>

  <x-core::table.header :selectable="true">
    <x-core::table.column :sortable="true" field="return_number">Return #</x-core::table.column>
    <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
    <x-core::table.column>Original Invoice</x-core::table.column>
    <x-core::table.column>Customer</x-core::table.column>
    <x-core::table.column :sortable="true" field="amount" align="end">Return Amount</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Refund Method</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($returns as $return)
      <tr>
        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $return->id }}"></td>
        <td><a href="{{ route('sale-returns.show', $return) }}" class="fw-700">{{ $return->return_number }}</a></td>
        <td>{{ $return->return_date->format('d M Y') }}</td>
        <td>
          @if($return->sale)
            <a href="{{ route('sales.show', $return->sale) }}" class="fw-600">{{ $return->sale->invoice_number }}</a>
          @endif
        </td>
        <td class="fw-600">{{ $return->customer->name ?? '' }}</td>
        <td class="text-end fw-800">{{ currency_symbol() }} {{ number_format($return->total_amount, 0) }}</td>
        <td>
          @switch($return->status)
            @case('draft')
              <span class="bp-badge bp-badge-warning">Draft</span>
              @break
            @case('approved')
              <span class="bp-badge bp-badge-info">Approved</span>
              @break
            @case('completed')
              <span class="bp-badge bp-badge-success">Completed</span>
              @break
            @case('cancelled')
              <span class="bp-badge bp-badge-danger">Cancelled</span>
              @break
            @default
              <span class="bp-badge bp-badge-dark">{{ ucfirst($return->status) }}</span>
          @endswitch
        </td>
        <td class="fw-600">{{ ucfirst(str_replace('_', ' ', $return->refund_method ?? '')) }}</td>
        <td>
          @bpCanAny('sales.view','sales.edit','sales.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('sales.view')
              <li><a class="dropdown-item" href="{{ route('sale-returns.show', $return) }}"><i class="fa-solid fa-eye"></i> View</a></li>
              @endbpCan
              @if($return->isEditable())
                @bpCan('sales.edit')
                <li><a class="dropdown-item" href="{{ route('sale-returns.edit', $return) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
                @endbpCan
                @bpCan('sales.edit')
                <li>
                  <form action="{{ route('sale-returns.approve', $return) }}" method="POST">
                    @csrf
                    <button type="submit" class="dropdown-item"><i class="fa-solid fa-check"></i> Approve</button>
                  </form>
                </li>
                @endbpCan
              @endif
              @if($return->isCompletable())
                @bpCan('sales.edit')
                <li>
                  <form action="{{ route('sale-returns.complete', $return) }}" method="POST">
                    @csrf
                    <button type="submit" class="dropdown-item"><i class="fa-solid fa-bangladeshi-taka-sign"></i> Process Refund</button>
                  </form>
                </li>
                @endbpCan
              @endif
              @bpCan('sales.view')
              <li><a class="dropdown-item" href="{{ route('sale-returns.print', $return) }}" target="_blank"><i class="fa-solid fa-print"></i> Print</a></li>
              @endbpCan
              @if($return->status === 'draft' || $return->status === 'approved')
                @bpCan('sales.delete')
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form action="{{ route('sale-returns.destroy', $return) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $return->return_number }}"><i class="fa-solid fa-trash"></i> Delete</button>
                  </form>
                </li>
                @endbpCan
              @endif
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
    @empty
      <x-core::table.empty colspan="9" icon="fa-solid fa-undo" title="No sale returns found." />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <x-core::table.pagination :paginator="$returns" itemLabel="sale returns" />
  </x-slot:pagination>
</x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    $('.bp-check-all').on('change', function () {
        var isChecked = $(this).prop('checked');
        $('.row-checkbox').prop('checked', isChecked);
    });
});
</script>
@endpush
