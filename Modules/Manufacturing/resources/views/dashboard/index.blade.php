@extends('core::layouts.master')

@section('title', __("Manufacturing Dashboard"))
@section('page-title', __("Manufacturing Dashboard"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Dashboard</span>
@endsection

@section('content')

  <!-- Primary Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-clipboard-list"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active Production Orders</div>
          <div class="bp-stat-value">{{ $stats['active_orders'] ?? 0 }}</div>
          <div class="fs-11 text-muted">{{ $stats['total_production_orders'] ?? 0 }} total</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-cart-plus"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Pending RM POs</div>
          <div class="bp-stat-value">{{ $stats['pending_rm_pos'] ?? 0 }}</div>
          <div class="fs-11 text-muted">{{ $stats['total_rm_purchase_orders'] ?? 0 }} total</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Damage Due</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_damage_cost'] - $stats['total_compensation_received'], 0) }}</div>
          <div class="fs-11 text-muted">{{ currency_symbol() }} {{ number_format($stats['total_damage_cost'], 0) }} total damage</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Low Stock Materials</div>
          <div class="bp-stat-value">{{ $stats['low_stock_count'] ?? 0 }}</div>
          <div class="fs-11 text-muted">{{ $stats['total_raw_materials'] ?? 0 }} active materials</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Secondary Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-secondary"><i class="fa-solid fa-scissors"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Fabric Cost (This Month)</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_fabric_cost'], 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-industry"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Making Cost (This Month)</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_making_cost'], 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-recycle"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Waste Cost</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_waste_cost'], 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Compensation Received</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_compensation_received'], 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart Section -->
  <div class="row g-3 mb-4">
    <div class="col-xl-12">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-chart-bar me-2"></i>Production Orders by Month</h5>
        </div>
        <div class="bp-card-body">
          <canvas id="monthlyOrdersChart" height="80"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Tables -->
  <div class="row g-3">
    <!-- Recent Production Orders -->
    <div class="col-xl-7">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-clipboard-list me-2"></i>Recent Production Orders</h5>
          <a href="{{ route('manufacturing.production-orders.index') }}" class="bp-btn bp-btn-sm bp-btn-outline">View All</a>
        </div>
        <div class="bp-card-body p-0">
          <div class="bp-table-wrapper">
            <table class="bp-table">
              <thead>
                <tr>
                  <th>PO#</th>
                  <th>Factory</th>
                  <th>Date</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Good</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentOrders as $order)
                <tr>
                  <td class="fw-600 fs-12">
                    <a href="{{ route('manufacturing.production-orders.show', $order) }}">{{ $order->po_number }}</a>
                  </td>
                  <td>{{ $order->factory->name ?? '' }}</td>
                  <td>{{ $order->order_date ? $order->order_date->format('d M Y') : '' }}</td>
                  <td class="text-end">{{ number_format($order->total_quantity) }}</td>
                  <td class="text-end">{{ number_format($order->good_quantity) }}</td>
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
                <x-core::table.empty colspan="6" icon="fa-solid fa-clipboard-list" title="No production orders yet." />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Damages Requiring Attention -->
    <div class="col-xl-5">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-triangle-exclamation me-2"></i>Pending Compensation</h5>
          <a href="{{ route('manufacturing.damages.index') }}" class="bp-btn bp-btn-sm bp-btn-outline">View All</a>
        </div>
        <div class="bp-card-body p-0">
          <div class="bp-table-wrapper">
            <table class="bp-table">
              <thead>
                <tr>
                  <th>PO#</th>
                  <th>Item</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Cost</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentDamages as $damage)
                <tr>
                  <td class="fw-600 fs-12">{{ $damage->productionOrder->po_number ?? '' }}</td>
                  <td class="fs-12">
                    {{ $damage->catalog->name ?? ($damage->product->name ?? '') }}
                    @if($damage->color)
                      <span class="text-muted">/ {{ $damage->color->name }}</span>
                    @endif
                    @if($damage->size)
                      <span class="text-muted">/ {{ $damage->size->name }}</span>
                    @endif
                  </td>
                  <td class="text-end">{{ $damage->quantity }}</td>
                  <td class="text-end text-danger fw-600">{{ currency_symbol() }} {{ number_format($damage->total_damage_cost, 0) }}</td>
                </tr>
                @empty
                <x-core::table.empty colspan="4" icon="fa-solid fa-triangle-exclamation" title="No pending compensation items." />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
<script>
'use strict';

$(function() {
    var chartData = @json($stats['monthly_orders_chart'] ?? []);
    var labels = chartData.map(function(item) { return item.month; });
    var totalCounts = chartData.map(function(item) { return item.count; });
    var completedCounts = chartData.map(function(item) { return item.completed; });

    var ctx = document.getElementById('monthlyOrdersChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Orders',
                        data: totalCounts,
                        backgroundColor: '#2E86C1',
                        borderRadius: 4,
                        barPercentage: 0.6,
                    },
                    {
                        label: 'Completed',
                        data: completedCounts,
                        backgroundColor: '#1E8449',
                        borderRadius: 4,
                        barPercentage: 0.6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
});
</script>
@endpush
