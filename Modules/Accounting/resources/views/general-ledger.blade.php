@extends('core::layouts.master')

@section('title', __("General Ledger"))
@section('page-title', __("General Ledger"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>General Ledger</span>
@endsection

@section('page-actions')
<x-core::export-menu />
<button class="bp-btn bp-btn-outline" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" id="glFilterForm">
      <div class="row g-3 align-items-end bp-report-filters">
        <div class="col-md-4">
          <label class="bp-form-label">Account *</label>
          <select class="bp-form-select w-100" name="account_id" id="glAccount">
            <option value="">Select Account</option>
            @foreach($accounts as $type => $typeAccounts)
              <optgroup label="{{ ucfirst($type) }}">
                @foreach($typeAccounts as $acct)
                  <option value="{{ $acct->id }}" {{ request('account_id') == $acct->id ? 'selected' : '' }}>{{ $acct->account_code }} — {{ $acct->account_name }}</option>
                @endforeach
              </optgroup>
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
        <div class="col-md-4 d-flex gap-2">
          <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
          <a href="{{ route('accounting.general-ledger') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

@if($data)

<!-- Account Summary -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-wallet"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Account</div>
        <div class="bp-stat-value fs-13 fw-700">{{ $data['account']->account_code }} — {{ $data['account']->account_name }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Debits</div>
        <div class="bp-stat-value">{{ money($data['total_debit']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Credits</div>
        <div class="bp-stat-value">{{ money($data['total_credit']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-scale-balanced"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Closing Balance</div>
        <div class="bp-stat-value">{{ money($data['closing_balance']) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- General Ledger Table -->
<div class="bp-card">
  <div class="bp-card-header d-flex justify-content-between align-items-center">
    <h5 class="bp-card-title"><i class="fa-solid fa-book-open me-2"></i>General Ledger — {{ $data['account']->account_name }} ({{ $data['account']->account_code }})</h5>
    @if(request('date_from') || request('date_to'))
      <span class="bp-badge bp-badge-info">
        {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d M Y') : '—' }}
        —
        {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d M Y') : '—' }}
      </span>
    @endif
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Entry #</th>
            <th>Description</th>
            <th>Reference</th>
            <th class="text-end">Debit ({{ currency_symbol() }})</th>
            <th class="text-end">Credit ({{ currency_symbol() }})</th>
            <th class="text-end">Balance ({{ currency_symbol() }})</th>
          </tr>
        </thead>
        <tbody>
          <!-- Opening Balance -->
          <tr class="bp-table-group-header">
            <td colspan="6" class="fw-800">Opening Balance ({{ $data['account']->account_name }})</td>
            <td class="text-end fw-800">{{ money($data['opening_balance']) }}</td>
          </tr>
          @foreach($data['transactions'] as $txn)
          <tr>
            <td>{{ $txn['date']->format('d M Y') }}</td>
            <td><a href="{{ route('accounting.journal-entries.show', $txn['entry_id']) }}">{{ $txn['entry_number'] }}</a></td>
            <td>{{ $txn['description'] }}</td>
            <td>@if($txn['reference'])<code class="fs-12">{{ $txn['reference'] }}</code>@else — @endif</td>
            <td class="text-end {{ $txn['debit'] > 0 ? 'fw-700' : 'text-muted' }}">{{ $txn['debit'] > 0 ? currency_symbol() . ' ' . num($txn['debit']) : '—' }}</td>
            <td class="text-end {{ $txn['credit'] > 0 ? 'fw-700' : 'text-muted' }}">{{ $txn['credit'] > 0 ? currency_symbol() . ' ' . num($txn['credit']) : '—' }}</td>
            <td class="text-end fw-700">{{ money($txn['balance']) }}</td>
          </tr>
          @endforeach
          <!-- Closing Balance -->
          <tr class="bp-table-group-header">
            <td colspan="4" class="fw-800">Closing Balance</td>
            <td class="text-end fw-800">{{ money($data['total_debit']) }}</td>
            <td class="text-end fw-800">{{ money($data['total_credit']) }}</td>
            <td class="text-end fw-800">{{ money($data['closing_balance']) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="bp-card-footer">
    <div class="bp-pagination">
      <span class="page-info">Showing {{ count($data['transactions']) }} transaction{{ count($data['transactions']) !== 1 ? 's' : '' }}</span>
    </div>
  </div>
</div>

@else

<!-- No account selected -->
<div class="bp-card">
  <div class="bp-card-body text-center py-5">
    <i class="fa-solid fa-book-open fa-3x text-muted mb-3"></i>
    <p class="text-muted mb-0">Select an account and click <strong>Generate</strong> to view the general ledger.</p>
  </div>
</div>

@endif

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    $('#btnPrint').on('click', function () {
        window.print();
    });

    // Auto-submit when account changes
    $('#glAccount').on('change', function () {
        if ($(this).val()) {
            $('#glFilterForm').submit();
        }
    });
});
</script>
@endpush
