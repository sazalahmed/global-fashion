@extends('core::layouts.master')

@section('title', __("Journal Entries"))
@section('page-title', __("Journal Entries"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Journal Entries</span>
@endsection

@section('page-actions')
<x-core::export-dropdown module="journal-entries" />
@bpCan('accounting.create')
<a href="{{ route('accounting.journal-entries.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus me-1"></i> New Journal Entry
</a>
@endbpCan
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-book"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Entries</div>
        <div class="bp-stat-value">{{ $stats['total'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Posted</div>
        <div class="bp-stat-value">{{ $stats['posted'] }}</div>
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
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Debits (This Month)</div>
        <div class="bp-stat-value">{{ money($stats['total_debits_this_month']) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" id="journalFilterForm">
      <div class="row g-3 align-items-end bp-report-filters">
        <div class="col-md">
          <label class="bp-form-label">Date From</label>
          <input type="date" class="bp-form-control" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="col-md">
          <label class="bp-form-label">Date To</label>
          <input type="date" class="bp-form-control" name="date_to" value="{{ request('date_to') }}">
        </div>
        <div class="col-md">
          <label class="bp-form-label">Status</label>
          <select class="bp-form-select w-100" name="status">
            <option value="">All Status</option>
            <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
          </select>
        </div>
        <div class="col-md">
          <label class="bp-form-label">Source</label>
          <select class="bp-form-select w-100" name="source_type">
            <option value="">All Sources</option>
            <option value="sale" {{ request('source_type') == 'sale' ? 'selected' : '' }}>Sale</option>
            <option value="purchase" {{ request('source_type') == 'purchase' ? 'selected' : '' }}>Purchase</option>
            <option value="payment" {{ request('source_type') == 'payment' ? 'selected' : '' }}>Payment</option>
            <option value="expense" {{ request('source_type') == 'expense' ? 'selected' : '' }}>Expense</option>
            <option value="payroll" {{ request('source_type') == 'payroll' ? 'selected' : '' }}>Payroll</option>
            <option value="sale_return" {{ request('source_type') == 'sale_return' ? 'selected' : '' }}>Sale Return</option>
            <option value="purchase_return" {{ request('source_type') == 'purchase_return' ? 'selected' : '' }}>Purchase Return</option>
            <option value="manual" {{ request('source_type') == 'manual' ? 'selected' : '' }}>Manual</option>
            <option value="void_reversal" {{ request('source_type') == 'void_reversal' ? 'selected' : '' }}>Void Reversal</option>
          </select>
        </div>
        <div class="col-md">
          <label class="bp-form-label">Search</label>
          <input type="text" class="bp-form-control" name="search" value="{{ request('search') }}" placeholder="Entry #, description...">
        </div>
        <div class="col-md-auto d-flex gap-2">
          <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
          <a href="{{ route('accounting.journal-entries') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Journal Entries Table -->
<x-core::table id="journalEntriesTable" :selectable="true">
  <x-slot:filters>
    <h5 class="bp-card-title"><i class="fa-solid fa-book me-2"></i>Journal Entries</h5>
    <span class="bp-badge bp-badge-info">Mar 2026</span>
  </x-slot:filters>

  <x-slot:bulkActions>
    <x-core::table.bulk-actions>
      @bpCan('accounting.edit')
      <button class="bp-btn bp-btn-sm bp-btn-outline" data-bulk-action="status" data-bulk-status="voided"><i class="fa-solid fa-ban me-1"></i> Void</button>
      @endbpCan
    </x-core::table.bulk-actions>
  </x-slot:bulkActions>

  <x-core::table.header :selectable="true">
    <x-core::table.column :sortable="true" field="entry_no">Entry #</x-core::table.column>
    <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
    <x-core::table.column>Reference</x-core::table.column>
    <x-core::table.column>Description</x-core::table.column>
    <x-core::table.column :sortable="true" field="debit" align="right">Debit ({{ currency_symbol() }})</x-core::table.column>
    <x-core::table.column :sortable="true" field="credit" align="right">Credit ({{ currency_symbol() }})</x-core::table.column>
    <x-core::table.column align="center">Status</x-core::table.column>
    <x-core::table.column>Created By</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($entries as $entry)
    @php
      $statusBadge = match($entry->status) {
        'posted' => 'bp-badge-success',
        'draft'  => 'bp-badge-warning',
        'voided' => 'bp-badge-danger',
        default  => 'bp-badge-secondary',
      };
    @endphp
    <tr>
      <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $entry->id }}"></td>
      <td><a href="{{ route('accounting.journal-entries.show', $entry) }}" class="fw-700">{{ $entry->entry_number }}</a></td>
      <td>{{ $entry->entry_date->format('d M Y') }}</td>
      <td>@if($entry->reference)<code class="fs-12">{{ $entry->reference }}</code>@else —@endif</td>
      <td>{{ \Illuminate\Support\Str::limit($entry->description, 50) }}</td>
      <td class="text-end fw-700">{{ money($entry->totalDebit()) }}</td>
      <td class="text-end fw-700">{{ money($entry->totalCredit()) }}</td>
      <td class="text-center"><span class="bp-badge {{ $statusBadge }}">{{ ucfirst($entry->status) }}</span></td>
      <td>{{ $entry->creator?->name ?? '—' }}</td>
      <td>
        @bpCanAny('accounting.view','accounting.edit','accounting.delete')
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            @bpCan('accounting.view')
            <li><a class="dropdown-item" href="{{ route('accounting.journal-entries.show', $entry) }}"><i class="fa-solid fa-eye me-2"></i>View</a></li>
            @endbpCan
            @if($entry->status === 'draft')
            @bpCan('accounting.edit')
            <li><a class="dropdown-item" href="{{ route('accounting.journal-entries.show', $entry) }}"><i class="fa-solid fa-pen me-2"></i>Edit</a></li>
            @endbpCan
            @bpCan('accounting.delete')
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="POST" action="{{ route('accounting.journal-entries.destroy', $entry) }}" onsubmit="return confirm('Delete this journal entry?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash me-2"></i>Delete</button>
              </form>
            </li>
            @endbpCan
            @else
            @bpCan('accounting.view')
            <li><a class="dropdown-item" href="#" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Print</a></li>
            @endbpCan
            @endif
          </ul>
        </div>
        @endbpCanAny
      </td>
    </tr>
    @empty
    <x-core::table.empty colspan="10" icon="fa-solid fa-book" title="No journal entries found." />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <x-core::table.pagination :paginator="$entries" itemLabel="entries" />
  </x-slot:pagination>
</x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Auto-submit filter on select change
    $('#journalFilterForm select').on('change', function () {
        $('#journalFilterForm').submit();
    });
});
</script>
@endpush
