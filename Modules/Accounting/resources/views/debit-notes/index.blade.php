@extends('core::layouts.master')

@section('title', __("Debit Notes"))
@section('page-title', __("Debit Notes"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.chart-of-accounts') }}">Accounting</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Debit Notes</span>
@endsection

@section('page-actions')
<x-core::export-dropdown module="debit-notes" />
@bpCan('accounting.create')
<a href="{{ route('accounting.debit-notes.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus me-1"></i> Create Debit Note
</a>
@endbpCan
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-file-invoice"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Debit Notes</div>
        <div class="bp-stat-value">{{ $stats['total'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-file-pen"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Draft</div>
        <div class="bp-stat-value">{{ $stats['draft'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Issued</div>
        <div class="bp-stat-value">{{ $stats['issued'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Amount</div>
        <div class="bp-stat-value">{{ money($stats['total_amount']) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" id="debitNoteFilterForm">
      <div class="row g-3 align-items-end bp-report-filters">
        <div class="col-md-3">
          <label class="bp-form-label">Search</label>
          <div class="bp-table-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="bp-form-control" name="search" value="{{ request('search') }}" placeholder="DN number, supplier...">
          </div>
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Status</label>
          <select class="bp-form-select w-100" name="status">
            <option value="">All Status</option>
            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="issued" {{ request('status') == 'issued' ? 'selected' : '' }}>Issued</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Date From</label>
          <input type="date" class="bp-form-control" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Date To</label>
          <input type="date" class="bp-form-control" name="date_to" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
          <a href="{{ route('accounting.debit-notes.index') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Debit Notes Table -->
<x-core::table id="debitNotesTable" :selectable="true">
  <x-slot:filters>
    <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2"></i>Debit Notes</h5>
    @if($debitNotes->total())
      <span class="bp-badge bp-badge-info">{{ $debitNotes->total() }} Records</span>
    @endif
  </x-slot:filters>

  <x-slot:bulkActions>
    <x-core::table.bulk-actions>
      @bpCan('accounting.edit')
      <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="status" data-bulk-status="cancelled"><i class="fa-solid fa-ban me-1"></i> Cancel</button>
      @endbpCan
    </x-core::table.bulk-actions>
  </x-slot:bulkActions>

  <x-core::table.header :selectable="true">
    <x-core::table.column :sortable="true" field="dn_number">DN Number</x-core::table.column>
    <x-core::table.column :sortable="true" field="issue_date">Issue Date</x-core::table.column>
    <x-core::table.column>Supplier</x-core::table.column>
    <x-core::table.column>Reason</x-core::table.column>
    <x-core::table.column :sortable="true" field="total_amount" align="right">Total Amount</x-core::table.column>
    <x-core::table.column align="center">Status</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($debitNotes as $debitNote)
    @php
      $statusBadge = match($debitNote->status) {
        'issued'    => 'bp-badge-success',
        'draft'     => 'bp-badge-warning',
        'cancelled' => 'bp-badge-danger',
        default     => 'bp-badge-secondary',
      };
    @endphp
    <tr>
      <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $debitNote->id }}"></td>
      <td>
        <a href="{{ route('accounting.debit-notes.show', $debitNote) }}" class="fw-700">
          {{ $debitNote->dn_number }}
        </a>
      </td>
      <td>{{ $debitNote->issue_date->format('d M Y') }}</td>
      <td>
        <div class="fw-600">{{ $debitNote->supplier?->company_name ?? '' }}</div>
        @if($debitNote->supplier?->phone)
          <div class="fs-11 text-muted">{{ $debitNote->supplier->phone }}</div>
        @endif
      </td>
      <td>{{ \Illuminate\Support\Str::limit($debitNote->reason, 45) }}</td>
      <td class="text-end fw-700">{{ money($debitNote->total_amount) }}</td>
      <td class="text-center">
        <span class="bp-badge {{ $statusBadge }}">{{ ucfirst($debitNote->status) }}</span>
      </td>
      <td>
        @bpCanAny('accounting.view','accounting.edit','accounting.delete')
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            @bpCan('accounting.view')
            <li>
              <a class="dropdown-item" href="{{ route('accounting.debit-notes.show', $debitNote) }}">
                <i class="fa-solid fa-eye me-2"></i>View
              </a>
            </li>
            @endbpCan
            @if($debitNote->status === 'draft')
            @bpCan('accounting.edit')
            <li>
              <a class="dropdown-item" href="{{ route('accounting.debit-notes.show', $debitNote) }}">
                <i class="fa-solid fa-pen me-2"></i>Edit
              </a>
            </li>
            @endbpCan
            @endif
            @if($debitNote->status === 'issued')
            @bpCan('accounting.view')
            <li>
              <a class="dropdown-item" href="{{ route('accounting.debit-notes.print', $debitNote) }}" target="_blank">
                <i class="fa-solid fa-print me-2"></i>Print
              </a>
            </li>
            @endbpCan
            @endif
            @if($debitNote->status === 'draft')
            @bpCan('accounting.delete')
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="POST" action="{{ route('accounting.debit-notes.destroy', $debitNote) }}"
                    onsubmit="return confirm('Delete debit note {{ $debitNote->dn_number }}? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item text-danger">
                  <i class="fa-solid fa-trash me-2"></i>Delete
                </button>
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
    <x-core::table.empty colspan="8" icon="fa-solid fa-file-invoice" title="No debit notes found.">
      <a href="{{ route('accounting.debit-notes.create') }}">Create the first one.</a>
    </x-core::table.empty>
    @endforelse
  </tbody>

  <x-slot:pagination>
    <x-core::table.pagination :paginator="$debitNotes" itemLabel="debit notes" />
  </x-slot:pagination>
</x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Auto-submit on status change
    $('#debitNoteFilterForm select[name="status"]').on('change', function () {
        $('#debitNoteFilterForm').submit();
    });
});
</script>
@endpush
