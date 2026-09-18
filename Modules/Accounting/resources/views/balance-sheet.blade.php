@extends('core::layouts.master')

@section('title', __("Balance Sheet"))
@section('page-title', __("Balance Sheet"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Balance Sheet</span>
@endsection

@section('page-actions')
<x-core::export-menu />
<button class="bp-btn bp-btn-outline" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" id="bsFilterForm">
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
          <label class="bp-form-label">Comparison</label>
          <select class="bp-form-select w-100" name="comparison">
            <option value="">No Comparison</option>
            <option value="prev_month">Previous Month</option>
            <option value="prev_quarter">Previous Quarter</option>
            <option value="prev_year">Previous Year</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
          <a href="{{ route('accounting.balance-sheet') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-4 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-building-columns"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Assets</div>
        <div class="bp-stat-value">{{ money($data['total_assets']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-file-invoice"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Liabilities</div>
        <div class="bp-stat-value">{{ money($data['total_liabilities']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-sm-12">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-scale-balanced"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Equity</div>
        <div class="bp-stat-value">{{ money($data['total_equity']) }}</div>
        <div class="bp-stat-change"><span class="bp-badge {{ $data['is_balanced'] ? 'bp-badge-success' : 'bp-badge-danger' }}">{{ $data['is_balanced'] ? 'Balanced' : 'Not Balanced' }}</span></div>
      </div>
    </div>
  </div>
</div>

<!-- Balance Sheet -->
<div class="row g-4">

  <!-- Assets Column -->
  <div class="col-xl-6">
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-building-columns me-2"></i>Assets</h5>
        <span class="fs-12 text-muted">As of {{ $asOfDate->format('d M Y') }}</span>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Account</th>
                <th class="text-end">Amount ({{ currency_symbol() }})</th>
              </tr>
            </thead>
            <tbody>
              @php $subTypeLabels = ['current_asset' => 'Current Assets', 'fixed_asset' => 'Fixed Assets', 'other_asset' => 'Other Assets']; @endphp
              @foreach($data['assets'] as $subType => $items)
                @if(count($items) > 0)
                <tr class="bp-table-group-header">
                  <td colspan="2" class="fw-800 text-uppercase fs-12">{{ $subTypeLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}</td>
                </tr>
                @foreach($items as $item)
                <tr>
                  <td class="ps-4 fw-600">{{ $item['account']->account_name }}</td>
                  <td class="text-end fw-700">{{ money($item['balance']) }}</td>
                </tr>
                @endforeach
                <tr class="bp-table-subtotal-row">
                  <td class="fw-800 ps-4">Total {{ $subTypeLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}</td>
                  <td class="text-end fw-800">{{ money(collect($items)->sum('balance')) }}</td>
                </tr>
                @endif
              @endforeach
            </tbody>
            <tfoot>
              <tr class="bp-table-highlight-row">
                <td class="fw-800 fs-14"><i class="fa-solid fa-building-columns me-2"></i>TOTAL ASSETS</td>
                <td class="text-end fw-800 fs-14">{{ money($data['total_assets']) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Liabilities & Equity Column -->
  <div class="col-xl-6">
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2"></i>Liabilities</h5>
        <span class="fs-12 text-muted">As of {{ $asOfDate->format('d M Y') }}</span>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Account</th>
                <th class="text-end">Amount ({{ currency_symbol() }})</th>
              </tr>
            </thead>
            <tbody>
              @php $liabilityLabels = ['current_liability' => 'Current Liabilities', 'long_term_liability' => 'Long-term Liabilities']; @endphp
              @foreach($data['liabilities'] as $subType => $items)
                @if(count($items) > 0)
                <tr class="bp-table-group-header">
                  <td colspan="2" class="fw-800 text-uppercase fs-12">{{ $liabilityLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}</td>
                </tr>
                @foreach($items as $item)
                <tr>
                  <td class="ps-4 fw-600">{{ $item['account']->account_name }}</td>
                  <td class="text-end fw-700">{{ money($item['balance']) }}</td>
                </tr>
                @endforeach
                <tr class="bp-table-subtotal-row">
                  <td class="fw-800 ps-4">Total {{ $liabilityLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}</td>
                  <td class="text-end fw-800">{{ money(collect($items)->sum('balance')) }}</td>
                </tr>
                @endif
              @endforeach
            </tbody>
            <tfoot>
              <tr class="bp-table-highlight-row">
                <td class="fw-800 fs-14"><i class="fa-solid fa-file-invoice me-2"></i>TOTAL LIABILITIES</td>
                <td class="text-end fw-800 fs-14">{{ money($data['total_liabilities']) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-scale-balanced me-2"></i>Equity</h5>
        <span class="fs-12 text-muted">As of {{ $asOfDate->format('d M Y') }}</span>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Account</th>
                <th class="text-end">Amount ({{ currency_symbol() }})</th>
              </tr>
            </thead>
            <tbody>
              @php $equityLabels = ['owner_equity' => 'Owner\'s Equity', 'retained_earnings' => 'Retained Earnings']; @endphp
              @foreach($data['equity'] as $subType => $items)
                @if(count($items) > 0)
                <tr class="bp-table-group-header">
                  <td colspan="2" class="fw-800 text-uppercase fs-12">{{ $equityLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}</td>
                </tr>
                @foreach($items as $item)
                <tr>
                  <td class="ps-4 fw-600">{{ $item['account']->account_name }}</td>
                  <td class="text-end fw-700">{{ money($item['balance']) }}</td>
                </tr>
                @endforeach
                <tr class="bp-table-subtotal-row">
                  <td class="fw-800 ps-4">Total {{ $equityLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}</td>
                  <td class="text-end fw-800">{{ money(collect($items)->sum('balance')) }}</td>
                </tr>
                @endif
              @endforeach
            </tbody>
            <tfoot>
              <tr class="bp-table-highlight-row">
                <td class="fw-800 fs-14"><i class="fa-solid fa-scale-balanced me-2"></i>TOTAL EQUITY</td>
                <td class="text-end fw-800 fs-14">{{ money($data['total_equity']) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Balance Check -->
    <div class="bp-card mt-4">
      <div class="bp-card-body">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <tbody>
              <tr class="bp-table-highlight-row">
                <td class="fw-800 fs-14"><i class="fa-solid fa-file-invoice me-2"></i>TOTAL LIABILITIES + EQUITY</td>
                <td class="text-end fw-800 fs-14">{{ money($data['total_liabilities'] + $data['total_equity']) }}</td>
              </tr>
              <tr>
                <td class="fw-700">Difference (Assets - Liabilities - Equity)</td>
                <td class="text-end fw-800 {{ $data['is_balanced'] ? 'text-success' : 'text-danger' }}">{{ money($data['total_assets'] - $data['total_liabilities'] - $data['total_equity']) }}@if($data['is_balanced']) <i class="fa-solid fa-check-circle ms-1"></i>@endif</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
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
        var params = $('#bsFilterForm').serialize();
        window.location.href = '{{ route("accounting.balance-sheet") }}?' + params + '&export=pdf';
    });

    $('#btnExportExcel').on('click', function () {
        var params = $('#bsFilterForm').serialize();
        window.location.href = '{{ route("accounting.balance-sheet") }}?' + params + '&export=excel';
    });
});
</script>
@endpush
