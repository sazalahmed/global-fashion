@extends('core::layouts.master')

@section('title', __("Payment Receipts"))
@section('page-title', __("Payment Receipts"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Receipts</span>
@endsection

@section('page-actions')
@bpCan('payments.export')
<x-core::export-dropdown module="payments" />
@endbpCan
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-receipt"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Receipts</div>
        <div class="bp-stat-value">{{ $stats['total'] ?? $receipts->total() }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down-to-bracket"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Payments Received</div>
        <div class="bp-stat-value">{{ $stats['received_count'] ?? '—' }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up-from-bracket"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Payments Made</div>
        <div class="bp-stat-value">{{ $stats['made_count'] ?? '—' }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Net Amount (This Month)</div>
        <div class="bp-stat-value">{{ currency_symbol() }} {{ isset($stats['net_amount']) ? num($stats['net_amount']) : '0.00' }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" id="receiptFilterForm">
      <div class="row g-3 align-items-end bp-report-filters">
        <div class="col-md-3">
          <label class="bp-form-label">Search</label>
          <div class="bp-table-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="bp-form-control" name="search" value="{{ request('search') }}" placeholder="Receipt #, party name…">
          </div>
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Receipt Type</label>
          <select class="bp-form-select w-100" name="receipt_type">
            <option value="">All Types</option>
            <option value="payment_received" {{ request('receipt_type') === 'payment_received' ? 'selected' : '' }}>Payment Received</option>
            <option value="payment_made" {{ request('receipt_type') === 'payment_made' ? 'selected' : '' }}>Payment Made</option>
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
          <a href="{{ route('accounting.receipts.index') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Receipts Table -->
<div class="bp-card">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2"></i>Payment Receipts</h5>
    @if(request()->hasAny(['search', 'receipt_type', 'date_from', 'date_to']))
      <span class="bp-badge bp-badge-info">Filtered</span>
    @endif
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Receipt Number</th>
            <th>Receipt Date</th>
            <th>Party Name</th>
            <th class="text-center">Type</th>
            <th class="text-end">Amount ({{ currency_symbol() }})</th>
            <th>Payment Method</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($receipts as $receipt)
          @php
            $typeBadge = $receipt->receipt_type === 'payment_received' ? 'bp-badge-success' : 'bp-badge-danger';
            $typeLabel = $receipt->receipt_type === 'payment_received' ? 'Received' : 'Made';
          @endphp
          <tr>
            <td>
              <a href="{{ route('accounting.receipts.show', $receipt) }}" class="fw-700">
                {{ $receipt->receipt_number }}
              </a>
            </td>
            <td>{{ $receipt->receipt_date->format('d M Y') }}</td>
            <td class="fw-600">{{ $receipt->party_name }}</td>
            <td class="text-center">
              <span class="bp-badge {{ $typeBadge }}">{{ $typeLabel }}</span>
            </td>
            <td class="text-end fw-700">{{ money($receipt->amount) }}</td>
            <td>{{ $receipt->payment_method ?? '' }}</td>
            <td class="text-center">
              <div class="dropdown">
                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="{{ route('accounting.receipts.show', $receipt) }}"><i class="fa-solid fa-eye me-2"></i> View Receipt</a></li>
                  <li><a class="dropdown-item" href="{{ route('accounting.receipts.print', $receipt) }}" target="_blank"><i class="fa-solid fa-print me-2"></i> Print Receipt</a></li>
                </ul>
              </div>
            </td>
          </tr>
          @empty
          <x-core::table.empty colspan="7" icon="fa-solid fa-receipt" title="No payment receipts found." />
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <x-core::table.pagination :paginator="$receipts" itemLabel="receipts" />
</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Auto-submit filter on type select change
    $('#receiptFilterForm select[name="receipt_type"]').on('change', function () {
        $('#receiptFilterForm').submit();
    });
});
</script>
@endpush
