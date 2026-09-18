@extends('core::layouts.master')

@section('title', __("Cost Analysis Report"))
@section('page-title', __("Cost Analysis Report"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Reports</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Cost Analysis</span>
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
      <form method="GET" action="{{ route('manufacturing.reports.cost-analysis') }}">
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
            <a href="{{ route('manufacturing.reports.cost-analysis') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Factory Comparison -->
  @if($factoryComparison->count() > 0)
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-scale-balanced me-2"></i>Factory Comparison</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Factory</th>
              <th class="text-end">Total Orders</th>
              <th class="text-end">Total Good Qty</th>
              <th class="text-end">Total Cost</th>
              <th class="text-end">Avg Cost/Unit</th>
            </tr>
          </thead>
          <tbody>
            @foreach($factoryComparison as $comparison)
            <tr>
              <td class="fw-700">{{ $comparison->factory_name }}</td>
              <td class="text-end">{{ $comparison->total_orders }}</td>
              <td class="text-end">{{ number_format($comparison->total_good_qty) }}</td>
              <td class="text-end">{{ currency_symbol() }} {{ number_format($comparison->total_cost, 0) }}</td>
              <td class="text-end fw-700">{{ money($comparison->avg_cost_per_unit) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  <!-- Detailed Cost Table -->
  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-chart-pie me-2"></i>Order-wise Cost Breakdown</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>PO#</th>
              <th>Factory</th>
              <th class="text-end">Total Qty</th>
              <th class="text-end">Good Qty</th>
              <th class="text-end">Fabric Cost</th>
              <th class="text-end">Making Cost</th>
              <th class="text-end">Delivery</th>
              <th class="text-end">Other</th>
              <th class="text-end">Damage (Net)</th>
              <th class="text-end">Waste</th>
              <th class="text-end">Cost/Unit</th>
            </tr>
          </thead>
          <tbody>
            @forelse($orders as $order)
            <tr>
              <td class="fw-600 fs-12">
                <a href="{{ route('manufacturing.production-orders.show', $order->id) }}">{{ $order->po_number }}</a>
              </td>
              <td>{{ $order->factory_name }}</td>
              <td class="text-end">{{ number_format($order->total_quantity) }}</td>
              <td class="text-end">{{ number_format($order->good_quantity) }}</td>
              <td class="text-end">{{ currency_symbol() }} {{ number_format($order->total_fabric_cost, 0) }}</td>
              <td class="text-end">{{ currency_symbol() }} {{ number_format($order->total_making_cost, 0) }}</td>
              <td class="text-end">{{ currency_symbol() }} {{ number_format($order->total_delivery_cost, 0) }}</td>
              <td class="text-end">{{ currency_symbol() }} {{ number_format($order->total_other_cost, 0) }}</td>
              <td class="text-end {{ $order->net_damage_cost > 0 ? 'text-danger' : 'text-success' }}">
                {{ currency_symbol() }} {{ number_format($order->net_damage_cost, 0) }}
              </td>
              <td class="text-end text-warning">{{ currency_symbol() }} {{ number_format($order->total_waste_cost, 0) }}</td>
              <td class="text-end fw-700">{{ money($order->final_cost_per_unit) }}</td>
            </tr>
            @empty
            <x-core::table.empty colspan="11" icon="fa-solid fa-chart-pie" title="No completed production orders found." />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

@endsection
