@extends('core::layouts.master')

@section('title', __("Raw Material Stock Report"))
@section('page-title', __("Raw Material Stock Report"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Reports</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>RM Stock</span>
@endsection

@section('page-actions')
  <button class="bp-btn bp-btn-outline" onclick="window.print()">
    <i class="fa-solid fa-print me-1"></i> Print
  </button>
@endsection

@section('content')

  <!-- Filters -->
  <div class="bp-card mb-4">
    <div class="bp-card-body">
      <form method="GET" action="{{ route('manufacturing.reports.rm-stock') }}">
        <div class="row g-3 align-items-end bp-report-filters">
          <div class="col-md-3">
            <label class="bp-form-label">Category</label>
            <select class="bp-form-select w-100" name="category">
              <option value="">All Categories</option>
              @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ ($filters['category'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <div class="form-check mt-4">
              <input type="checkbox" class="form-check-input" name="low_stock_only" value="1" id="lowStockOnly" {{ !empty($filters['low_stock_only']) ? 'checked' : '' }}>
              <label class="form-check-label" for="lowStockOnly">Show Low Stock Only</label>
            </div>
          </div>
          <div class="col-md-3">
            <button type="submit" class="bp-btn bp-btn-primary me-2" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
            <a href="{{ route('manufacturing.reports.rm-stock') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Summary -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Stock Value</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalStockValue, 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Items</div>
          <div class="bp-stat-value">{{ $stocks->count() }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Stock Table -->
  <div class="bp-card">
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Material Name</th>
              <th>Category</th>
              <th>Unit</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Avg Cost</th>
              <th class="text-end">Last Cost</th>
              <th class="text-end">Total Value</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($stocks as $stock)
            <tr>
              <td class="fw-600 fs-12">{{ $stock->material_code }}</td>
              <td class="fw-700 fs-13">{{ $stock->material_name }}</td>
              <td>{{ ucfirst($stock->category) }}</td>
              <td>{{ ucfirst($stock->unit) }}</td>
              <td class="text-end">{{ num($stock->quantity) }}</td>
              <td class="text-end">{{ money($stock->avg_cost) }}</td>
              <td class="text-end">{{ money($stock->last_cost) }}</td>
              <td class="text-end fw-600">{{ currency_symbol() }} {{ number_format($stock->total_value, 0) }}</td>
              <td>
                @if($stock->is_low_stock)
                  <span class="bp-badge bp-badge-danger">Low Stock</span>
                @else
                  <span class="bp-badge bp-badge-success">OK</span>
                @endif
              </td>
            </tr>
            @empty
            <x-core::table.empty colspan="9" icon="fa-solid fa-boxes-stacked" title="No stock records found." />
            @endforelse
          </tbody>
          @if($stocks->count() > 0)
          <tfoot>
            <tr class="fw-700">
              <td colspan="7" class="text-end">Total Stock Value:</td>
              <td class="text-end">{{ currency_symbol() }} {{ number_format($totalStockValue, 0) }}</td>
              <td></td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>

@endsection
