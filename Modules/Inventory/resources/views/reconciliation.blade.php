@extends('core::layouts.master')

@section('title', __("Stock Reconciliation"))
@section('page-title', __("Stock Reconciliation"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('inventory.index') }}">Stock</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Reconciliation</span>
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-cubes-stacked"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Products Checked</div>
        <div class="bp-stat-value">{{ number_format($stats['total_products_checked']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Discrepancies</div>
        <div class="bp-stat-value">{{ number_format($stats['discrepancies_found']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-arrow-up"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Over Count</div>
        <div class="bp-stat-value">{{ number_format($stats['over_count']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-down"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Under Count</div>
        <div class="bp-stat-value">{{ number_format($stats['under_count']) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Filter & Run -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form action="{{ route('inventory.reconciliation') }}" method="GET" class="d-flex gap-3 align-items-end flex-wrap">
      <input type="hidden" name="run" value="1">
      <button type="submit" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-magnifying-glass me-1"></i> Run Reconciliation
      </button>
    </form>
  </div>
</div>

<!-- Results -->
@if($discrepancies->isNotEmpty())
<div class="bp-card">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>Discrepancies Found ({{ $discrepancies->count() }})</h5>
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>SKU</th>
            <th class="text-center">Stock Table Qty</th>
            <th class="text-center">Ledger Qty</th>
            <th class="text-center">Discrepancy</th>
          </tr>
        </thead>
        <tbody>
          @foreach($discrepancies as $row)
            <tr>
              <td class="fw-700">{{ $row->product_name }}</td>
              <td><code class="fs-11">{{ $row->product_sku }}</code></td>
              <td class="text-center fw-600">{{ $row->stored_qty }}</td>
              <td class="text-center fw-600">{{ $row->ledger_qty }}</td>
              <td class="text-center fw-800 {{ $row->discrepancy > 0 ? 'text-warning' : 'text-danger' }}">
                {{ $row->discrepancy > 0 ? '+' : '' }}{{ $row->discrepancy }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@elseif(request()->has('run'))
<div class="bp-card">
  <div class="bp-card-body text-center py-5">
    <i class="fa-solid fa-circle-check fa-3x text-success mb-3 d-block"></i>
    <h5 class="fw-700">All Clear</h5>
    <p class="text-muted">No discrepancies found. Stock table and ledger are in sync.</p>
  </div>
</div>
@else
<div class="bp-card">
  <div class="bp-card-body text-center py-5 text-muted">
    <i class="fa-solid fa-cubes-stacked fa-3x mb-3 d-block opacity-50"></i>
    <p>Click <strong>Run Reconciliation</strong> to compare stock table quantities against the stock ledger.</p>
  </div>
</div>
@endif

@endsection
