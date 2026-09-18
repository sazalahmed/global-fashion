@extends('core::layouts.master')

@section('title', __("Cash Flow Statement"))
@section('page-title', __("Cash Flow Statement"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Cash Flow</span>
@endsection

@section('page-actions')
<x-core::export-menu />
<button class="bp-btn bp-btn-outline" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" id="cfFilterForm">
      <div class="row g-3 align-items-end bp-report-filters">
        <div class="col-md-3">
          <label class="bp-form-label">Period</label>
          <select class="bp-form-select w-100" name="period" id="cfPeriodSelect">
            <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>This Month</option>
            <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Last Month</option>
            <option value="this_quarter" {{ $period === 'this_quarter' ? 'selected' : '' }}>This Quarter ({{ now()->startOfQuarter()->format('M') }} &mdash; {{ now()->endOfQuarter()->format('M Y') }})</option>
            <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>This Financial Year ({{ now()->month >= 7 ? 'Jul ' . now()->year . ' — Jun ' . (now()->year + 1) : 'Jul ' . (now()->year - 1) . ' — Jun ' . now()->year }})</option>
            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Range</option>
          </select>
        </div>
        <div class="col-md-2 {{ $period !== 'custom' ? 'd-none' : '' }}" id="cfDateFromCol">
          <label class="bp-form-label">Date From</label>
          <input type="date" class="bp-form-control" name="date_from" value="{{ $from->format('Y-m-d') }}">
        </div>
        <div class="col-md-2 {{ $period !== 'custom' ? 'd-none' : '' }}" id="cfDateToCol">
          <label class="bp-form-label">Date To</label>
          <input type="date" class="bp-form-control" name="date_to" value="{{ $to->format('Y-m-d') }}">
        </div>
        <div class="col-md-2 d-none">
          <label class="bp-form-label">Branch</label>
          <select class="bp-form-select w-100" name="branch">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
              <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
          <a href="{{ route('accounting.cash-flow') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-wallet"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Opening Balance</div>
        <div class="bp-stat-value">{{ money($data['opening_cash']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Cash In</div>
        <div class="bp-stat-value">{{ money($data['total_cash_in']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Cash Out</div>
        <div class="bp-stat-value">{{ money($data['total_cash_out']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon {{ $data['net_change'] >= 0 ? 'icon-success' : 'icon-danger' }}"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Closing Balance</div>
        <div class="bp-stat-value">{{ money($data['closing_cash']) }}</div>
        <div class="bp-stat-change {{ $data['net_change'] >= 0 ? 'up' : 'down' }}">
          <i class="fa-solid fa-arrow-{{ $data['net_change'] >= 0 ? 'up' : 'down' }}"></i>
          {{ money(abs($data['net_change'])) }} net {{ $data['net_change'] >= 0 ? 'inflow' : 'outflow' }}
        </div>
      </div>
    </div>
  </div>
</div>

@php
  $maxRows = max(count($data['cash_in_items']), count($data['cash_out_items']));
@endphp

<!-- Cash Flow Table — Cash In vs Cash Out -->
<div class="bp-card">
  <div class="bp-card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="bp-card-title"><i class="fa-solid fa-money-bill-wave me-2"></i>Cash Flow Statement</h5>
      <div class="fs-12 text-muted mt-1">{{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</div>
    </div>
    @if($data['net_change'] >= 0)
      <span class="bp-badge bp-badge-success"><i class="fa-solid fa-arrow-trend-up me-1"></i> Positive Cash Flow</span>
    @else
      <span class="bp-badge bp-badge-danger"><i class="fa-solid fa-arrow-trend-down me-1"></i> Negative Cash Flow</span>
    @endif
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table bp-cashflow-table">
        <thead>
          <tr>
            <th colspan="2" class="bp-cf-header-in text-center">
              <i class="fa-solid fa-arrow-down me-1"></i> Cash In
            </th>
            <th colspan="2" class="bp-cf-header-out text-center">
              <i class="fa-solid fa-arrow-up me-1"></i> Cash Out
            </th>
          </tr>
          <tr>
            <th>Description</th>
            <th class="text-end bp-col-amount">Amount</th>
            <th>Description</th>
            <th class="text-end bp-col-amount">Amount</th>
          </tr>
        </thead>
        <tbody>
          @for($i = 0; $i < $maxRows; $i++)
          <tr>
            @if(isset($data['cash_in_items'][$i]))
              <td class="fw-600">
                <i class="fa-solid {{ $data['cash_in_items'][$i]['icon'] }} bp-cf-icon text-success"></i>
                {{ $data['cash_in_items'][$i]['label'] }}
              </td>
              <td class="text-end fw-700">{{ money($data['cash_in_items'][$i]['amount']) }}</td>
            @else
              <td></td>
              <td></td>
            @endif
            @if(isset($data['cash_out_items'][$i]))
              <td class="fw-600">
                <i class="fa-solid {{ $data['cash_out_items'][$i]['icon'] }} bp-cf-icon text-danger"></i>
                {{ $data['cash_out_items'][$i]['label'] }}
              </td>
              <td class="text-end fw-700">{{ money($data['cash_out_items'][$i]['amount']) }}</td>
            @else
              <td></td>
              <td></td>
            @endif
          </tr>
          @endfor

          @if($maxRows === 0)
          <x-core::table.empty colspan="4" icon="fa-solid fa-inbox" title="No cash flow transactions in this period" />
          @endif
        </tbody>
        <tfoot>
          <!-- Totals Row -->
          <tr class="bp-cf-total-row">
            <td class="fw-800">Total Cash In</td>
            <td class="text-end fw-800 text-success">{{ money($data['total_cash_in']) }}</td>
            <td class="fw-800">Total Cash Out</td>
            <td class="text-end fw-800 text-danger">{{ money($data['total_cash_out']) }}</td>
          </tr>

          <!-- Opening Balance -->
          <tr class="bp-cf-summary-row">
            <td colspan="3" class="text-end fw-700">Opening Balance =</td>
            <td class="text-end fw-700">{{ money($data['opening_cash']) }}</td>
          </tr>

          <!-- Net Cash Flow -->
          <tr class="bp-cf-summary-row">
            <td colspan="3" class="text-end fw-700">Net Cash Flow =</td>
            <td class="text-end fw-700 {{ $data['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">
              {{ $data['net_change'] < 0 ? '(' : '' }}{{ money(abs($data['net_change'])) }}{{ $data['net_change'] < 0 ? ')' : '' }}
              <span class="fs-11 text-muted d-block">(Cash In - Cash Out)</span>
            </td>
          </tr>

          <!-- Closing Balance -->
          <tr class="bp-cf-closing-row">
            <td colspan="3" class="text-end fw-800 fs-14">
              <i class="fa-solid fa-bangladeshi-taka-sign me-1"></i>Closing Balance =
            </td>
            <td class="text-end fw-800 fs-14 {{ $data['closing_cash'] >= 0 ? 'text-success' : 'text-danger' }}">
              {{ money($data['closing_cash']) }}
              <span class="fs-11 text-muted d-block">(Opening + Cash In - Cash Out)</span>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <div class="bp-card-footer">
    <div class="d-flex justify-content-between align-items-center">
      <span class="text-muted fs-12">Generated on {{ now()->format('d M Y') }}. All amounts in BDT.</span>
      <span class="fw-700 fs-12">Cash includes: Cash in Hand, bKash, Nagad, Bank accounts</span>
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
        var params = $('#cfFilterForm').serialize();
        window.location.href = '{{ route("accounting.cash-flow") }}?' + params + '&export=pdf';
    });

    $('#btnExportExcel').on('click', function () {
        var params = $('#cfFilterForm').serialize();
        window.location.href = '{{ route("accounting.cash-flow") }}?' + params + '&export=excel';
    });

    $('#cfPeriodSelect').on('change', function () {
        var isCustom = $(this).val() === 'custom';
        if (isCustom) {
            $('#cfDateFromCol, #cfDateToCol').removeClass('d-none');
        } else {
            $('#cfDateFromCol, #cfDateToCol').addClass('d-none');
            $('#cfFilterForm').trigger('submit');
        }
    });
});
</script>
@endpush
