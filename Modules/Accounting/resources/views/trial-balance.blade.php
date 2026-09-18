@extends('core::layouts.master')

@section('title', __("Trial Balance"))
@section('page-title', __("Trial Balance"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Trial Balance</span>
@endsection

@section('page-actions')
<x-core::export-menu />
<button class="bp-btn bp-btn-outline" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" id="tbFilterForm">
      <div class="row g-3 align-items-end bp-report-filters">
        <div class="col-md-3">
          <label class="bp-form-label">As of Date *</label>
          <input type="date" class="bp-form-control" name="as_of_date" value="{{ $asOfDate->format('Y-m-d') }}">
        </div>
        <div class="col-md-3 d-none">
          <label class="bp-form-label">Branch</label>
          <select class="bp-form-select w-100" name="branch">
            <option value="">All Branches</option>
            <option>Dhaka Main</option>
            <option>Chittagong</option>
            <option>Sylhet</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Show Zero Balances</label>
          <select class="bp-form-select w-100" name="show_zero">
            <option value="0" {{ !$showZero ? 'selected' : '' }}>Hide Zero Balances</option>
            <option value="1" {{ $showZero ? 'selected' : '' }}>Show All Accounts</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
          <a href="{{ route('accounting.trial-balance') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Trial Balance Report -->
<div class="bp-card">
  <div class="bp-card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="bp-card-title"><i class="fa-solid fa-scale-balanced me-2"></i>Trial Balance</h5>
      <div class="fs-12 text-muted mt-1">{{ $companyName }} — As of {{ $asOfDate->format('d M Y') }}</div>
    </div>
    @if($data['is_balanced'])
      <span class="bp-badge bp-badge-success"><i class="fa-solid fa-check me-1"></i> Balanced</span>
    @else
      <span class="bp-badge bp-badge-danger"><i class="fa-solid fa-times me-1"></i> Not Balanced</span>
    @endif
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Account Code</th>
            <th>Account Name</th>
            <th>Type</th>
            <th class="text-end">Debit ({{ currency_symbol() }})</th>
            <th class="text-end">Credit ({{ currency_symbol() }})</th>
          </tr>
        </thead>
        <tbody>
          @foreach($data['rows'] as $row)
          <tr>
            <td><code class="fs-12">{{ $row['account']->account_code }}</code></td>
            <td class="fw-600">{{ $row['account']->account_name }}</td>
            <td>
              @php $badgeMap = ['asset' => 'primary', 'liability' => 'danger', 'equity' => 'warning', 'revenue' => 'success', 'expense' => 'secondary']; @endphp
              <span class="bp-badge bp-badge-{{ $badgeMap[$row['account']->account_type] ?? 'dark' }}">{{ ucfirst($row['account']->account_type) }}</span>
            </td>
            <td class="text-end {{ $row['debit'] > 0 ? 'fw-700' : 'text-muted' }}">{{ $row['debit'] > 0 ? currency_symbol() . ' ' . num($row['debit']) : '—' }}</td>
            <td class="text-end {{ $row['credit'] > 0 ? 'fw-700' : 'text-muted' }}">{{ $row['credit'] > 0 ? currency_symbol() . ' ' . num($row['credit']) : '—' }}</td>
          </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr class="bp-table-totals-row">
            <td colspan="3" class="text-end fw-800 fs-14">TOTALS:</td>
            <td class="text-end fw-800 fs-14">{{ money($data['total_debit']) }}</td>
            <td class="text-end fw-800 fs-14">{{ money($data['total_credit']) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <div class="bp-card-footer">
    <div class="bp-pagination">
      <span class="page-info">Showing {{ count($data['rows']) }} accounts with non-zero balances</span>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    $('#btnPrint').on('click', function () {
        window.print();
    });

    $('#btnExportPdf').on('click', function () {
        var params = $('#tbFilterForm').serialize();
        window.location.href = '{{ route("accounting.trial-balance") }}?' + params + '&export=pdf';
    });

    $('#btnExportExcel').on('click', function () {
        var params = $('#tbFilterForm').serialize();
        window.location.href = '{{ route("accounting.trial-balance") }}?' + params + '&export=excel';
    });
});
</script>
@endpush
