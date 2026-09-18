@extends('core::layouts.master')

@section('title', __("Waste Report"))
@section('page-title', __("Waste Report"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Reports</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Waste</span>
@endsection

@section('page-actions')
  <button class="bp-btn bp-btn-outline" onclick="window.print()">
    <i class="fa-solid fa-print me-1"></i> Print
  </button>
@endsection

@section('content')

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Normal Waste (Absorbed)</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($summary['total_normal_waste'], 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Abnormal Waste (Expensed)</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($summary['total_abnormal_waste'], 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-scissors"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">RM Waste</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($summary['total_normal_rm'] + $summary['total_abnormal_rm'], 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-box"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Product Waste</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($summary['total_normal_product'] + $summary['total_abnormal_product'], 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="bp-card mb-4">
    <div class="bp-card-body">
      <form method="GET" action="{{ route('manufacturing.reports.waste') }}">
        <input type="hidden" name="tab" value="{{ $filters['tab'] ?? 'rm' }}">
        <div class="row g-3 align-items-end bp-report-filters">
          <div class="col-md-3">
            <label class="bp-form-label">From Date</label>
            <input type="date" class="bp-form-control" name="from_date" value="{{ $filters['from_date'] ?? '' }}">
          </div>
          <div class="col-md-3">
            <label class="bp-form-label">To Date</label>
            <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
          </div>
          <div class="col-md-3">
            <button type="submit" class="bp-btn bp-btn-primary me-2" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
            <a href="{{ route('manufacturing.reports.waste') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <a class="nav-link {{ ($filters['tab'] ?? 'rm') === 'rm' ? 'active' : '' }}" href="{{ route('manufacturing.reports.waste', array_merge($filters, ['tab' => 'rm'])) }}">
        <i class="fa-solid fa-scissors me-1"></i> RM Waste
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ ($filters['tab'] ?? '') === 'product' ? 'active' : '' }}" href="{{ route('manufacturing.reports.waste', array_merge($filters, ['tab' => 'product'])) }}">
        <i class="fa-solid fa-box me-1"></i> Product Waste
      </a>
    </li>
  </ul>

  @if(($filters['tab'] ?? 'rm') === 'rm')
  <!-- RM Waste Table -->
  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-scissors me-2"></i>Raw Material Waste</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>PO#</th>
              <th>Material</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Cost</th>
              <th>Type</th>
              <th>Classification</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rmWastes as $waste)
            <tr>
              <td class="fs-12">{{ $waste->waste_date ? $waste->waste_date->format('d M Y') : '' }}</td>
              <td class="fw-600 fs-12">{{ $waste->productionOrder->po_number ?? '' }}</td>
              <td>{{ $waste->rawMaterial->name ?? '' }}</td>
              <td class="text-end">{{ num($waste->quantity_wasted) }} {{ $waste->rawMaterial->unit ?? '' }}</td>
              <td class="text-end fw-600">{{ currency_symbol() }} {{ number_format($waste->total_cost, 0) }}</td>
              <td><span class="bp-badge bp-badge-info">{{ ucwords(str_replace('_', ' ', $waste->waste_type)) }}</span></td>
              <td>
                @if($waste->is_normal)
                  <span class="bp-badge bp-badge-success">Normal</span>
                @else
                  <span class="bp-badge bp-badge-danger">Abnormal</span>
                @endif
              </td>
            </tr>
            @empty
            <x-core::table.empty colspan="7" icon="fa-solid fa-recycle" title="No RM waste records found." />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @else
  <!-- Product Waste Table -->
  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-box me-2"></i>Product Waste</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>PO#</th>
              <th>Item</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Cost</th>
              <th>Type</th>
              <th>Classification</th>
            </tr>
          </thead>
          <tbody>
            @forelse($productWastes as $waste)
            <tr>
              <td class="fs-12">{{ $waste->waste_date ? $waste->waste_date->format('d M Y') : '' }}</td>
              <td class="fw-600 fs-12">{{ $waste->productionOrder->po_number ?? '' }}</td>
              <td>
                {{ $waste->catalog->name ?? '' }}
                @if($waste->color)
                  <span class="text-muted">/ {{ $waste->color->name }}</span>
                @endif
                @if($waste->size)
                  <span class="text-muted">/ {{ $waste->size->name }}</span>
                @endif
              </td>
              <td class="text-end">{{ number_format($waste->quantity_wasted) }}</td>
              <td class="text-end fw-600">{{ currency_symbol() }} {{ number_format($waste->total_cost, 0) }}</td>
              <td><span class="bp-badge bp-badge-info">{{ ucwords(str_replace('_', ' ', $waste->waste_type)) }}</span></td>
              <td>
                @if($waste->is_normal)
                  <span class="bp-badge bp-badge-success">Normal</span>
                @else
                  <span class="bp-badge bp-badge-danger">Abnormal</span>
                @endif
              </td>
            </tr>
            @empty
            <x-core::table.empty colspan="7" icon="fa-solid fa-recycle" title="No product waste records found." />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

@endsection
