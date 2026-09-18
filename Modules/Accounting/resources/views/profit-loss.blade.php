@extends('core::layouts.master')

@section('title', __("Profit & Loss Statement"))
@section('page-title', __("Profit & Loss Statement"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Profit &amp; Loss</span>
@endsection

@section('page-actions')
<x-core::export-menu />
<button class="bp-btn bp-btn-outline" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" id="plFilterForm">
      <div class="row g-3 align-items-end bp-report-filters">
        <div class="col-md-3">
          <label class="bp-form-label">Period</label>
          <select class="bp-form-select w-100" name="period" id="periodSelect">
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="this_quarter">This Quarter (Jan — Mar 2026)</option>
            <option value="this_year" selected>This Financial Year (Jul 2025 — Jun 2026)</option>
            <option value="custom">Custom Range</option>
          </select>
        </div>
        <div class="col-md-2" id="dateFromCol">
          <label class="bp-form-label">Date From</label>
          <input type="date" class="bp-form-control" name="date_from" value="{{ $from->format('Y-m-d') }}">
        </div>
        <div class="col-md-2" id="dateToCol">
          <label class="bp-form-label">Date To</label>
          <input type="date" class="bp-form-control" name="date_to" value="{{ $to->format('Y-m-d') }}">
        </div>
        <div class="col-md-2 d-none">
          <label class="bp-form-label">Branch</label>
          <select class="bp-form-select w-100" name="branch">
            <option value="">All Branches</option>
            <option>Dhaka Main</option>
            <option>Chittagong</option>
            <option>Sylhet</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
          <a href="{{ route('accounting.profit-loss') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-chart-line"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Revenue</div>
        <div class="bp-stat-value">{{ money($data['total_revenue']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-receipt"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Expenses</div>
        <div class="bp-stat-value">{{ money($data['total_cogs'] + $data['total_operating_expenses']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-coins"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Gross Profit</div>
        <div class="bp-stat-value">{{ money($data['gross_profit']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Net Profit</div>
        <div class="bp-stat-value">{{ money($data['net_profit']) }}</div>
        <div class="bp-stat-change {{ $data['net_profit'] >= 0 ? 'up' : 'down' }}"><i class="fa-solid fa-arrow-{{ $data['net_profit'] >= 0 ? 'up' : 'down' }}"></i> {{ number_format($data['net_margin'], 1) }}% margin</div>
      </div>
    </div>
  </div>
</div>

<!-- Profit & Loss Statement -->
<div class="bp-card">
  <div class="bp-card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="bp-card-title"><i class="fa-solid fa-chart-pie me-2"></i>Profit &amp; Loss Statement</h5>
      <div class="fs-12 text-muted mt-1">{{ $companyName }} — {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</div>
    </div>
    @if($data['net_profit'] >= 0)
    <span class="bp-badge bp-badge-success"><i class="fa-solid fa-arrow-trend-up me-1"></i> Profitable</span>
    @else
    <span class="bp-badge bp-badge-danger"><i class="fa-solid fa-arrow-trend-down me-1"></i> Loss</span>
    @endif
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Particulars</th>
            <th class="text-end" style="width: 180px;">Amount ({{ currency_symbol() }})</th>
            <th class="text-end" style="width: 180px;">Total ({{ currency_symbol() }})</th>
          </tr>
        </thead>
        <tbody>
          <!-- Revenue Section -->
          <tr class="bp-table-group-header">
            <td colspan="3" class="fw-800 text-uppercase fs-12">
              <i class="fa-solid fa-chart-line me-2"></i>Revenue
            </td>
          </tr>
          @if(isset($data['revenue']['operating_revenue']))
            @foreach($data['revenue']['operating_revenue'] as $item)
            <tr>
              <td class="ps-4 fw-600">{{ $item['account']->account_name }}</td>
              <td class="text-end fw-700">{{ money($item['amount']) }}</td>
              <td class="text-end"></td>
            </tr>
            @endforeach
          @endif
          <tr class="bp-table-subtotal-row">
            <td class="fw-800 ps-4">Total Revenue</td>
            <td class="text-end"></td>
            <td class="text-end fw-800">{{ money($data['total_revenue']) }}</td>
          </tr>

          <!-- COGS Section -->
          <tr class="bp-table-group-header">
            <td colspan="3" class="fw-800 text-uppercase fs-12">
              <i class="fa-solid fa-box me-2"></i>Cost of Goods Sold
            </td>
          </tr>
          @if(isset($data['expenses']['cost_of_sales']))
            @foreach($data['expenses']['cost_of_sales'] as $item)
            <tr>
              <td class="ps-4 fw-600">{{ $item['account']->account_name }}</td>
              <td class="text-end fw-700">{{ money($item['amount']) }}</td>
              <td class="text-end"></td>
            </tr>
            @endforeach
          @endif
          <tr class="bp-table-subtotal-row">
            <td class="fw-800 ps-4">Total COGS</td>
            <td class="text-end"></td>
            <td class="text-end fw-800 text-danger">({{ money($data['total_cogs']) }})</td>
          </tr>

          <!-- Gross Profit -->
          <tr class="bp-table-highlight-row">
            <td class="fw-800 fs-14"><i class="fa-solid fa-coins me-2"></i>Gross Profit</td>
            <td class="text-end"></td>
            <td class="text-end fw-800 fs-14">{{ money($data['gross_profit']) }}</td>
          </tr>

          <!-- Operating Expenses Section -->
          <tr class="bp-table-group-header">
            <td colspan="3" class="fw-800 text-uppercase fs-12">
              <i class="fa-solid fa-receipt me-2"></i>Operating Expenses
            </td>
          </tr>
          @if(isset($data['expenses']['operating_expense']))
            @foreach($data['expenses']['operating_expense'] as $item)
            <tr>
              <td class="ps-4 fw-600">{{ $item['account']->account_name }}</td>
              <td class="text-end fw-700">{{ money($item['amount']) }}</td>
              <td class="text-end"></td>
            </tr>
            @endforeach
          @endif
          <tr class="bp-table-subtotal-row">
            <td class="fw-800 ps-4">Total Operating Expenses</td>
            <td class="text-end"></td>
            <td class="text-end fw-800 text-danger">({{ money($data['total_operating_expenses']) }})</td>
          </tr>

          <!-- Operating Profit -->
          <tr class="bp-table-highlight-row">
            <td class="fw-800 fs-14"><i class="fa-solid fa-chart-bar me-2"></i>Operating Profit</td>
            <td class="text-end"></td>
            <td class="text-end fw-800 fs-14">{{ money($data['operating_profit']) }}</td>
          </tr>

          <!-- Non-Operating Items -->
          @php
            $hasOtherRevenue = !empty($data['revenue']['other_revenue']);
            $hasOtherExpense = !empty($data['expenses']['other_expense']);
            $nonOpNet = 0;
            if ($hasOtherRevenue) {
                foreach ($data['revenue']['other_revenue'] as $item) { $nonOpNet += $item['amount']; }
            }
            if ($hasOtherExpense) {
                foreach ($data['expenses']['other_expense'] as $item) { $nonOpNet -= $item['amount']; }
            }
          @endphp
          @if($hasOtherRevenue || $hasOtherExpense)
          <tr class="bp-table-group-header">
            <td colspan="3" class="fw-800 text-uppercase fs-12">
              <i class="fa-solid fa-building-columns me-2"></i>Non-Operating Items
            </td>
          </tr>
          @if($hasOtherRevenue)
            @foreach($data['revenue']['other_revenue'] as $item)
            <tr>
              <td class="ps-4 fw-600">{{ $item['account']->account_name }}</td>
              <td class="text-end fw-700">{{ money($item['amount']) }}</td>
              <td class="text-end"></td>
            </tr>
            @endforeach
          @endif
          @if($hasOtherExpense)
            @foreach($data['expenses']['other_expense'] as $item)
            <tr>
              <td class="ps-4 fw-600">{{ $item['account']->account_name }}</td>
              <td class="text-end fw-700 text-danger">({{ money($item['amount']) }})</td>
              <td class="text-end"></td>
            </tr>
            @endforeach
          @endif
          <tr class="bp-table-subtotal-row">
            <td class="fw-800 ps-4">Net Non-Operating Income</td>
            <td class="text-end"></td>
            <td class="text-end fw-800 {{ $nonOpNet >= 0 ? '' : 'text-danger' }}">
              @if($nonOpNet < 0)({{ money(abs($nonOpNet)) }})@else {{ money($nonOpNet) }}@endif
            </td>
          </tr>
          @endif

          <!-- Net Profit -->
          <tr class="bp-table-highlight-row bp-bg-success-light">
            <td class="fw-800 fs-14"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Net Profit</td>
            <td class="text-end"></td>
            <td class="text-end fw-800 fs-14 {{ $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ money($data['net_profit']) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="bp-card-footer">
    <div class="d-flex justify-content-between align-items-center">
      <span class="text-muted fs-12">Generated on {{ now()->format('d M Y') }}. All amounts in BDT (Bangladeshi Taka).</span>
      <span class="fw-700 fs-12">Net Profit Margin: {{ number_format($data['net_margin'], 1) }}%</span>
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
        var params = $('#plFilterForm').serialize();
        window.location.href = '{{ route("accounting.profit-loss") }}?' + params + '&export=pdf';
    });

    $('#btnExportExcel').on('click', function () {
        var params = $('#plFilterForm').serialize();
        window.location.href = '{{ route("accounting.profit-loss") }}?' + params + '&export=excel';
    });

    // Toggle custom date fields visibility
    $('#periodSelect').on('change', function () {
        var isCustom = $(this).val() === 'custom';
        $('#dateFromCol, #dateToCol').toggle(isCustom);
    });
});
</script>
@endpush
