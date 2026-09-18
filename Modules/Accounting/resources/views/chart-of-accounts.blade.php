@extends('core::layouts.master')

@section('title', __("Chart of Accounts"))
@section('page-title', __("Chart of Accounts"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Chart of Accounts</span>
@endsection

@section('page-actions')
<x-core::export-dropdown module="chart-of-accounts" />
@bpCan('accounting.create')
<a href="{{ route('accounting.chart-of-accounts.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus me-1"></i> Add Account
</a>
@endbpCan
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-sitemap"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Accounts</div>
        <div class="bp-stat-value">{{ $stats['total_accounts'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-building-columns"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Assets</div>
        <div class="bp-stat-value">{{ money($stats['total_assets']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-file-invoice"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Liabilities</div>
        <div class="bp-stat-value">{{ money($stats['total_liabilities']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-scale-balanced"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Equity</div>
        <div class="bp-stat-value">{{ money($stats['total_equity']) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Chart of Accounts Table -->
<x-core::table id="chartOfAccountsTable">
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search account code, name..." :searchValue="$filters['search'] ?? ''">
      <select class="bp-form-select" name="account_type">
        <option value="">All Types</option>
        <option value="asset" {{ ($filters['account_type'] ?? '') === 'asset' ? 'selected' : '' }}>Asset</option>
        <option value="liability" {{ ($filters['account_type'] ?? '') === 'liability' ? 'selected' : '' }}>Liability</option>
        <option value="equity" {{ ($filters['account_type'] ?? '') === 'equity' ? 'selected' : '' }}>Equity</option>
        <option value="revenue" {{ ($filters['account_type'] ?? '') === 'revenue' ? 'selected' : '' }}>Revenue</option>
        <option value="expense" {{ ($filters['account_type'] ?? '') === 'expense' ? 'selected' : '' }}>Expense</option>
      </select>
      <select class="bp-form-select" name="status">
        <option value="">All Status</option>
        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-core::table.header>
    <x-core::table.column :sortable="true" field="code">Account Code</x-core::table.column>
    <x-core::table.column :sortable="true" field="name">Account Name</x-core::table.column>
    <x-core::table.column>Type</x-core::table.column>
    <x-core::table.column>Sub-Type</x-core::table.column>
    <x-core::table.column :sortable="true" field="balance" align="right">Balance ({{ currency_symbol() }})</x-core::table.column>
    <x-core::table.column align="center">Status</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @php
      $typeIcons = [
        'asset'     => 'fa-building-columns',
        'liability' => 'fa-file-invoice',
        'equity'    => 'fa-scale-balanced',
        'revenue'   => 'fa-chart-line',
        'expense'   => 'fa-receipt',
      ];
      $typeBadges = [
        'asset'     => 'bp-badge-primary',
        'liability' => 'bp-badge-danger',
        'equity'    => 'bp-badge-warning',
        'revenue'   => 'bp-badge-success',
        'expense'   => 'bp-badge-secondary',
      ];
    @endphp

    @forelse($accountsByType as $type => $subTypes)
      <tr class="bp-table-group-header">
        <td colspan="7" class="fw-800 text-uppercase fs-12">
          <i class="fa-solid {{ $typeIcons[$type] ?? 'fa-folder' }} me-2"></i>{{ ucfirst($type) }}
        </td>
      </tr>

      @foreach($subTypes as $subType => $accounts)
        <tr class="bp-table-subgroup-header">
          <td colspan="7" class="fw-700 fs-12 ps-4">{{ ucfirst(str_replace('_', ' ', $subType)) }}</td>
        </tr>

        @forelse($accounts as $account)
          @php
            $typeBadgeClass = $typeBadges[$account->account_type] ?? 'bp-badge-dark';
            $statusBadgeClass = $account->status === 'active' ? 'bp-badge-success' : 'bp-badge-danger';
          @endphp
          <tr>
            <td class="ps-5"><code class="fs-12">{{ $account->account_code }}</code></td>
            <td class="fw-600 ps-5">{{ $account->account_name }}</td>
            <td><span class="bp-badge {{ $typeBadgeClass }}">{{ ucfirst($account->account_type) }}</span></td>
            <td>{{ ucfirst(str_replace('_', ' ', $subType)) }}</td>
            <td class="text-end fw-700">{{ money($account->balance) }}</td>
            <td class="text-center"><x-core::status-toggle :url="route('accounting.chart-of-accounts.toggle-status', $account->id)" :active="$account->status === 'active'" /></td>
            <td>
              @bpCanAny('accounting.view','accounting.edit','accounting.delete')
              <div class="dropdown">
                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                <ul class="dropdown-menu dropdown-menu-end">
                  @bpCan('accounting.view')
                  <li><a class="dropdown-item" href="{{ route('accounting.general-ledger', ['account_id' => $account->id]) }}"><i class="fa-solid fa-eye me-2"></i>View Ledger</a></li>
                  @endbpCan
                  @bpCan('accounting.edit')
                  <li><a class="dropdown-item" href="{{ route('accounting.chart-of-accounts.edit', $account) }}"><i class="fa-solid fa-pen me-2"></i>Edit</a></li>
                  @endbpCan
                  @bpCan('accounting.delete')
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <form action="{{ route('accounting.chart-of-accounts.destroy', $account) }}" method="POST" class="bp-delete-form">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash me-2"></i>Delete</button>
                    </form>
                  </li>
                  @endbpCan
                </ul>
              </div>
              @endbpCanAny
            </td>
          </tr>
        @empty
          <x-core::table.empty colspan="7" icon="fa-solid fa-folder-open" title="No accounts in this sub-type." />
        @endforelse
      @endforeach
    @empty
      <x-core::table.empty colspan="7" icon="fa-solid fa-sitemap" title="No accounts found." />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">Showing {{ $stats['total_accounts'] }} account{{ $stats['total_accounts'] !== 1 ? 's' : '' }}</span>
      </div>
    </div>
  </x-slot:pagination>
</x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Filter reset
    $('.bp-filter-reset').on('click', function () {
        // Clear all filters — go to the clean path (no query string).
        window.location.href = window.location.pathname;
    });

    // Toggle account type groups
    $('.bp-table-group-header').on('click', function () {
        var $header = $(this);
        var $rows = $header.nextUntil('.bp-table-group-header');
        $rows.toggleClass('d-none');
        $header.toggleClass('bp-collapsed');
    });

    // Delete confirmation
    $(document).on('submit', '.bp-delete-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        if (window.confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
            $form.off('submit').submit();
        }
    });
});
</script>
@endpush
