@extends('core::layouts.master')

@section('title', __("Production Report"))
@section('page-title', __("Production Report"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Reports</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Production</span>
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
      <form method="GET" action="{{ route('manufacturing.reports.production') }}">
        <div class="row g-3 align-items-end bp-report-filters">
          <div class="col-md-3">
            <label class="bp-form-label">Factory</label>
            <select class="bp-form-select w-100" name="factory_id">
              <option value="">All Factories</option>
              @foreach($factories as $factory)
                <option value="{{ $factory->id }}" {{ ($filters['factory_id'] ?? '') == $factory->id ? 'selected' : '' }}>{{ $factory->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="bp-form-label">Status</label>
            <select class="bp-form-select w-100" name="status">
              <option value="">All Status</option>
              @foreach($statuses as $key => $label)
                <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="bp-form-label">From Date</label>
            <input type="date" class="bp-form-control" name="from_date" value="{{ $filters['from_date'] ?? '' }}">
          </div>
          <div class="col-md-2">
            <label class="bp-form-label">To Date</label>
            <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
          </div>
          <div class="col-md-3">
            <button type="submit" class="bp-btn bp-btn-primary me-2" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
            <a href="{{ route('manufacturing.reports.production') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Summary Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-2 col-sm-4">
      <div class="bp-stat-card">
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Ordered</div>
          <div class="bp-stat-value">{{ number_format($summary['total_ordered']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-sm-4">
      <div class="bp-stat-card">
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Received</div>
          <div class="bp-stat-value">{{ number_format($summary['total_received']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-sm-4">
      <div class="bp-stat-card">
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Good</div>
          <div class="bp-stat-value text-success">{{ number_format($summary['total_good']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-sm-4">
      <div class="bp-stat-card">
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Damaged</div>
          <div class="bp-stat-value text-danger">{{ number_format($summary['total_damaged']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-sm-4">
      <div class="bp-stat-card">
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Wasted</div>
          <div class="bp-stat-value text-warning">{{ number_format($summary['total_wasted']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-sm-4">
      <div class="bp-stat-card">
        <div class="bp-stat-content">
          <div class="bp-stat-label">Avg Damage Rate</div>
          <div class="bp-stat-value">{{ $summary['avg_damage_rate'] }}%</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Production Table -->
  <div class="bp-card">
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>PO#</th>
              <th>Factory</th>
              <th>Date</th>
              <th class="text-end">Total Qty</th>
              <th class="text-end">Received</th>
              <th class="text-end">Good</th>
              <th class="text-end">Damaged</th>
              <th class="text-end">Wasted</th>
              <th class="text-end">Damage Rate</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($orders as $order)
            <tr>
              <td class="fw-600 fs-12">
                <a href="{{ route('manufacturing.production-orders.show', $order->id) }}">{{ $order->po_number }}</a>
              </td>
              <td>{{ $order->factory_name }}</td>
              <td>{{ $order->order_date ? $order->order_date->format('d M Y') : '' }}</td>
              <td class="text-end">{{ number_format($order->total_quantity) }}</td>
              <td class="text-end">{{ number_format($order->received_quantity) }}</td>
              <td class="text-end text-success fw-600">{{ number_format($order->good_quantity) }}</td>
              <td class="text-end text-danger">{{ number_format($order->damaged_quantity) }}</td>
              <td class="text-end text-warning">{{ number_format($order->wasted_quantity) }}</td>
              <td class="text-end">
                @if($order->damage_rate > 5)
                  <span class="text-danger fw-700">{{ $order->damage_rate }}%</span>
                @elseif($order->damage_rate > 0)
                  <span class="text-warning fw-600">{{ $order->damage_rate }}%</span>
                @else
                  <span class="text-success">{{ $order->damage_rate }}%</span>
                @endif
              </td>
              <td>
                @php
                  $statusClass = match($order->status) {
                    'draft' => 'bp-badge-dark',
                    'approved' => 'bp-badge-primary',
                    'in_progress' => 'bp-badge-info',
                    'partial_delivered' => 'bp-badge-warning',
                    'completed' => 'bp-badge-success',
                    'cancelled' => 'bp-badge-danger',
                    default => 'bp-badge-secondary',
                  };
                @endphp
                <span class="bp-badge {{ $statusClass }}">{{ ucwords(str_replace('_', ' ', $order->status)) }}</span>
              </td>
            </tr>
            @empty
            <x-core::table.empty colspan="10" icon="fa-solid fa-clipboard-list" title="No production orders found." />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

@endsection
