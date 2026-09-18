@extends('core::layouts.master')

@section('title', __("Ad Spend Analytics"))
@section('page-title', __("Ad Spend Analytics"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('adspend.index') }}">Ad Spend</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Analytics</span>
@endsection

@section('content')

<!-- Date Range Filter -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" action="{{ route('adspend.analytics') }}" class="d-flex align-items-end gap-3 flex-wrap">
      <div>
        <label class="bp-form-label">From</label>
        <input type="date" class="bp-form-control" name="from" value="{{ $from }}">
      </div>
      <div>
        <label class="bp-form-label">To</label>
        <input type="date" class="bp-form-control" name="to" value="{{ $to }}">
      </div>
      <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
      <a href="{{ route('adspend.analytics') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
    </form>
  </div>
</div>

<!-- Period Comparison -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-calendar"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">This Month</div>
        <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($periodComparison['current'], 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-calendar-minus"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Last Month</div>
        <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($periodComparison['previous'], 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="bp-stat-card">
      <div class="bp-stat-icon {{ $periodComparison['change'] >= 0 ? 'icon-danger' : 'icon-success' }}"><i class="fa-solid fa-{{ $periodComparison['change'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Change</div>
        <div class="bp-stat-value">{{ $periodComparison['change'] >= 0 ? '+' : '' }}{{ $periodComparison['change'] }}%</div>
      </div>
    </div>
  </div>
</div>

<!-- Spend Trend Chart -->
<div class="bp-card mb-4">
  <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-chart-line me-2"></i>Spend Trend (30 Days)</h5></div>
  <div class="bp-card-body"><canvas id="analyticsSpendChart" height="80"></canvas></div>
</div>

<!-- Platform Comparison -->
<div class="bp-card mb-4">
  <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>Platform Comparison</h5></div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Platform</th>
            <th class="text-end">Spend</th>
            <th class="text-end">Impressions</th>
            <th class="text-end">Clicks</th>
            <th class="text-end">CPC</th>
            <th class="text-end">CPM</th>
            <th class="text-end">CTR</th>
            <th class="text-end">Conversions</th>
          </tr>
        </thead>
        <tbody>
          @php $grandSpend = 0; $grandImp = 0; $grandClicks = 0; $grandConv = 0; @endphp
          @forelse($platformBreakdown as $pb)
          @php
            $grandSpend += $pb->total_spent;
            $grandImp += $pb->total_impressions;
            $grandClicks += $pb->total_clicks;
            $grandConv += $pb->total_conversions;
            $cpc = $pb->total_clicks > 0 ? $pb->total_spent / $pb->total_clicks : 0;
            $cpm = $pb->total_impressions > 0 ? ($pb->total_spent / $pb->total_impressions) * 1000 : 0;
            $ctr = $pb->total_impressions > 0 ? ($pb->total_clicks / $pb->total_impressions) * 100 : 0;
          @endphp
          <tr>
            <td><i class="{{ $pb->icon }}" style="color: {{ $pb->color }}"></i> <span class="fw-700">{{ $pb->name }}</span> <span class="fs-11 text-muted">({{ $pb->campaign_count }})</span></td>
            <td class="text-end fw-700">{{ currency_symbol() }} {{ number_format($pb->total_spent, 0) }}</td>
            <td class="text-end">{{ number_format($pb->total_impressions) }}</td>
            <td class="text-end">{{ number_format($pb->total_clicks) }}</td>
            <td class="text-end">{{ $cpc > 0 ? currency_symbol() . ' ' . num($cpc) : '—' }}</td>
            <td class="text-end">{{ $cpm > 0 ? currency_symbol() . ' ' . num($cpm) : '—' }}</td>
            <td class="text-end">{{ $ctr > 0 ? num($ctr) . '%' : '—' }}</td>
            <td class="text-end">{{ number_format($pb->total_conversions) }}</td>
          </tr>
          @empty
          <x-core::table.empty colspan="8" icon="fa-chart-pie" title="No data for this period." />
          @endforelse
          @if($platformBreakdown->isNotEmpty())
          <tr class="fw-800" style="background: #f8f9fa">
            <td>TOTAL</td>
            <td class="text-end">{{ currency_symbol() }} {{ number_format($grandSpend, 0) }}</td>
            <td class="text-end">{{ number_format($grandImp) }}</td>
            <td class="text-end">{{ number_format($grandClicks) }}</td>
            <td class="text-end">{{ $grandClicks > 0 ? currency_symbol() . ' ' . num($grandSpend / $grandClicks) : '—' }}</td>
            <td class="text-end">{{ $grandImp > 0 ? currency_symbol() . ' ' . num(($grandSpend / $grandImp) * 1000) : '—' }}</td>
            <td class="text-end">{{ $grandImp > 0 ? num(($grandClicks / $grandImp) * 100) . '%' : '—' }}</td>
            <td class="text-end">{{ number_format($grandConv) }}</td>
          </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Top Campaigns -->
<div class="bp-card">
  <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-trophy me-2"></i>Top 10 Campaigns by Spend</h5></div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead><tr><th>#</th><th>Campaign</th><th>Platform</th><th class="text-end">Spend</th><th class="text-end">Clicks</th><th class="text-end">Conversions</th><th class="text-end">CPC</th></tr></thead>
        <tbody>
          @forelse($topCampaigns as $idx => $tc)
          <tr>
            <td class="fw-800 text-muted">{{ $idx + 1 }}</td>
            <td><a href="{{ route('adspend.show', $tc) }}" class="fw-700">{{ $tc->campaign_name }}</a></td>
            <td><i class="{{ $tc->platform->icon }}" style="color: {{ $tc->platform->color }}"></i> {{ $tc->platform->name }}</td>
            <td class="text-end fw-700">{{ currency_symbol() }} {{ number_format($tc->total_amount, 0) }}</td>
            <td class="text-end">{{ number_format($tc->clicks) }}</td>
            <td class="text-end">{{ number_format($tc->conversions) }}</td>
            <td class="text-end">{{ $tc->cpc !== null ? currency_symbol() . ' ' . num($tc->cpc) : '—' }}</td>
          </tr>
          @empty
          <x-core::table.empty colspan="7" icon="fa-bullhorn" title="No campaigns found." />
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
<script>
'use strict';
$(function () {
    var trendData = @json($spendTrend);
    new Chart(document.getElementById('analyticsSpendChart'), {
        type: 'line',
        data: {
            labels: trendData.labels,
            datasets: [{
                label: 'Daily Spend ({{ currency_symbol() }})',
                data: trendData.data,
                borderColor: '#C0392B',
                backgroundColor: 'rgba(192,57,43,0.08)',
                fill: true, tension: 0.3, pointRadius: 2,
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>
@endpush
