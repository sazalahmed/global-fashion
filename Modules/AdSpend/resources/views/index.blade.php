@extends('core::layouts.master')

@section('title', __('Ad Spend'))
@section('page-title', __('Ad Spend'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('marketing.index') }}">Marketing</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Ad Spend</span>
@endsection

@section('page-actions')
    <a href="{{ route('adspend.analytics') }}" class="bp-btn bp-btn-success"><i class="fa-solid fa-chart-pie me-1"></i>
        Analytics</a>
    @bpCan('marketing.edit')
        <form action="{{ route('adspend.meta.sync') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="bp-btn bp-btn-warning" title="Pull campaigns + spend from Meta Ads">
                <i class="fa-brands fa-meta me-1"></i> Sync from Meta
            </button>
        </form>
        <a href="{{ route('adspend.meta.settings') }}" class="bp-btn bp-btn-info" title="Meta API settings"><i
                class="fa-solid fa-plug me-1"></i> Meta Settings</a>
    @endbpCan
    @bpCan('marketing.create')
        <a href="{{ route('adspend.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i> Record
            Spend</a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-rectangle-ad"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Spent</div>
                    <div class="bp-stat-value">{{ '$ ' . num($stats['total_spent'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Daily Average</div>
                    <div class="bp-stat-value">{{ '$ ' . num($stats['daily_average'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bullhorn"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Campaigns</div>
                    <div class="bp-stat-value">{{ $stats['campaign_count'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-pointer"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Avg CPC</div>
                    <div class="bp-stat-value">{{ '$ ' . num($stats['avg_cpc'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mb-4">
        <div class="col-md-9">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-chart-line me-2"></i>Spend Trend (30 Days)</h5>
                </div>
                <div class="bp-card-body"><canvas id="spendTrendChart" height="100"></canvas></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-chart-pie me-2"></i>Platform Breakdown</h5>
                </div>
                <div class="bp-card-body"><canvas id="platformChart" height="200"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Filter + Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar action="{{ route('adspend.index') }}" searchPlaceholder="Search campaigns...">
                <select class="bp-form-select" name="platform_id">
                    <option value="">All Platforms</option>
                    @foreach ($platforms as $p)
                        <option value="{{ $p->id }}" {{ request('platform_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}</option>
                    @endforeach
                </select>
                <input type="date" class="bp-form-control" name="date_from" value="{{ request('date_from') }}"
                    placeholder="From">
                <input type="date" class="bp-form-control" name="date_to" value="{{ request('date_to') }}"
                    placeholder="To">
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Date</x-core::table.column>
            <x-core::table.column>Platform</x-core::table.column>
            <x-core::table.column>Campaign</x-core::table.column>
            <x-core::table.column align="left">Results</x-core::table.column>
            <x-core::table.column>Type</x-core::table.column>
            <x-core::table.column align="left">Amount</x-core::table.column>
            <x-core::table.column align="left">Impressions</x-core::table.column>
            <x-core::table.column align="left">Clicks</x-core::table.column>
            <x-core::table.column align="left">CPC</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column align="left">Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($campaigns as $c)
                <tr>
                    <td>{{ $c->spend_date->format('d M Y') }}</td>
                    <td>
                        <span class="d-inline-flex align-items-center gap-1">
                            <i class="{{ $c->platform->icon }}" style="color: {{ $c->platform->color }}"></i>
                            <span class="fw-600 fs-12">{{ $c->platform->name }}</span>
                        </span>
                    </td>
                    <td>
                        @if ($c->meta_url)
                            <a href="{{ $c->meta_url }}" target="_blank" rel="noopener"
                                class="fw-700 d-inline-flex align-items-center gap-1"
                                title="View / edit on Meta Ads Manager">{{ $c->campaign_name }} <i
                                    class="fa-brands fa-meta fs-12" style="color:#1877F2"></i></a>
                        @else
                            <a href="{{ route('adspend.show', $c) }}" class="fw-700">{{ $c->campaign_name }}</a>
                        @endif
                    </td>
                    <td class="text-start">
                        @if ($c->result)
                            <span class="fw-700">{{ number_format($c->result['count']) }}</span>
                            <span class="fs-11 text-muted d-block">{{ $c->result['label'] }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($c->campaign_type_label)
                            <span class="bp-badge bp-badge-secondary">{{ $c->campaign_type_label }}</span>
                        @endif
                    </td>
                    <td class="text-start fw-700">{{ '$' }} {{ num($c->total_amount) }}</td>
                    <td class="text-start">{{ number_format($c->impressions) }}</td>
                    <td class="text-start">{{ number_format($c->clicks) }}</td>
                    <td class="text-start fw-600">{{ $c->cpc !== null ? '$' . ' ' . num($c->cpc) : '—' }}</td>
                    <td>
                        @if ($c->status === 'active')
                            <span class="bp-badge bp-badge-success">Active</span>
                        @elseif($c->status === 'paused')
                            <span class="bp-badge bp-badge-warning">Paused</span>
                        @else
                            <span class="bp-badge bp-badge-dark">Completed</span>
                        @endif
                    </td>
                    <td class="text-start">
                        @bpCanAny('marketing.view', 'marketing.edit', 'marketing.delete')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('marketing.view')
                                    <li><a class="dropdown-item" href="{{ route('adspend.show', $c) }}"><i
                                                class="fa-solid fa-eye me-2"></i>View</a></li>
                                    @endbpCan
                                    @bpCan('marketing.edit')
                                        <li><a class="dropdown-item" href="{{ route('adspend.edit', $c) }}"><i
                                                    class="fa-solid fa-pen me-2"></i>Edit</a></li>
                                    @endbpCan
                                    @bpCan('marketing.delete')
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <form action="{{ route('adspend.destroy', $c) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $c->campaign_name }}"><i
                                                        class="fa-solid fa-trash me-2"></i>Delete</button>
                                            </form>
                                        </li>
                                    @endbpCan
                                </ul>
                            </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="11" icon="fa-bullhorn" title="No ad spend records found." />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$campaigns" itemLabel="campaigns" />
        </x-slot:pagination>
    </x-core::table>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
    <script>
        'use strict';
        $(function() {
            // Spend Trend Line Chart
            var trendData = @json($spendTrend);
            new Chart(document.getElementById('spendTrendChart'), {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [{
                        label: 'Daily Spend ({{ '$' }})',
                        data: trendData.data,
                        borderColor: '#C0392B',
                        backgroundColor: 'rgba(192, 57, 43, 0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Platform Doughnut Chart
            var pbData = @json($platformBreakdown);
            if (pbData.length > 0) {
                new Chart(document.getElementById('platformChart'), {
                    type: 'doughnut',
                    data: {
                        labels: pbData.map(function(p) {
                            return p.name;
                        }),
                        datasets: [{
                            data: pbData.map(function(p) {
                                return p.total_spent;
                            }),
                            backgroundColor: pbData.map(function(p) {
                                return p.color;
                            }),
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
