@extends('core::layouts.master')

@section('title', __("Expense Ledger"))
@section('page-title', __("Expense Ledger"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('expenses.index') }}">Expenses</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Ledger</span>
@endsection

@section('page-actions')
@bpCan('finance.export')
<x-core::export-dropdown module="expense-ledger" />
@endbpCan
@endsection

@section('content')

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" action="{{ route('expenses.ledger') }}" id="expenseLedgerFilterForm">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="bp-form-label">Category</label>
          <select class="bp-form-select w-100" name="category">
            <option value="">All Categories</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-none">
          <label class="bp-form-label">Branch</label>
          <select class="bp-form-select w-100" name="branch">
            <option value="">All Branches</option>
            @foreach($branches ?? [] as $branch)
              <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @endforeach
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
          <a href="{{ route('expenses.ledger') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-xl-4 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-money-bill-trend-up"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Expenses (This Period)</div>
        <div class="bp-stat-value">{{ money($ledgerStats['total_expenses']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-chart-pie"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Top Categories</div>
        <div class="bp-stat-value fs-13 fw-600">
          {{ $ledgerStats['top_categories'] }}
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-sm-12">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-calculator"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Monthly Average</div>
        <div class="bp-stat-value">{{ money($ledgerStats['monthly_average']) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Expense Ledger Table -->
<x-core::table id="expenseLedgerTable">
  <x-slot:filters>
    <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2"></i>Expense Ledger</h5>
    <span class="bp-badge bp-badge-info">{{ $ledgerStats['period_label'] }}</span>
  </x-slot:filters>

  <x-core::table.header>
    <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
    <x-core::table.column>Reference #</x-core::table.column>
    <x-core::table.column>Category</x-core::table.column>
    <x-core::table.column>Description</x-core::table.column>
    <x-core::table.column align="end">Amount ({{ currency_symbol() }})</x-core::table.column>
    <x-core::table.column>Payment Method</x-core::table.column>
    <th class="d-none">Branch</th>
    <x-core::table.column>Approved By</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($expenseEntries as $entry)
    <tr>
      <td>{{ $entry->expense_date->format('d M Y') }}</td>
      <td><a href="{{ route('expenses.show', $entry) }}">{{ $entry->expense_number }}</a></td>
      <td>
        @if($entry->category)
          <span class="bp-badge bp-badge-primary">{{ $entry->category->name }}</span>
        @else
          <span class="bp-badge bp-badge-dark">Uncategorized</span>
        @endif
      </td>
      <td>{{ $entry->description }}</td>
      <td class="text-end fw-700">{{ money($entry->total_amount) }}</td>
      <td>{{ $entry->payment_method ?? 'N/A' }}</td>
      <td class="d-none">{{ $entry->branch->name ?? 'N/A' }}</td>
      <td>{{ $entry->approver->name ?? 'N/A' }}</td>
    </tr>
    @empty
    <x-core::table.empty colspan="8" icon="fa-solid fa-receipt" title="No expense entries found for the selected filters." />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <x-core::table.pagination :paginator="$expenseEntries" itemLabel="expenses" />
  </x-slot:pagination>
</x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function() {
    // Filter form auto-submit on select change
    $('#expenseLedgerFilterForm select').on('change', function() {
        $('#expenseLedgerFilterForm').submit();
    });
    // PDF / Excel / CSV / Print are handled by the centralized export
    // dropdown component which links to the /export/expense-ledger route.
});
</script>
@endpush
