@extends('core::layouts.master')

@section('title', 'Investment')
@section('page-title', 'Investment')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Investment</span>
@endsection

@section('page-actions')
    @bpCan('accounting.create')
        <a href="{{ route('investment.investors.create') }}" class="bp-btn bp-btn-primary"><i
                class="fa-solid fa-user-plus me-1"></i>Add Investor</a>
        <a href="{{ route('investment.capital.create') }}" class="bp-btn bp-btn-warning"><i
                class="fa-solid fa-arrow-down me-1"></i>Record Capital</a>
        <a href="{{ route('investment.distributions.create') }}" class="bp-btn bp-btn-success"><i
                class="fa-solid fa-percent me-1"></i>Distribute Profit</a>
    @endbpCan
@endsection

@section('content')

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-people-group"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Shareholders</div>
                    <div class="bp-stat-value">{{ $shareholderCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Shareholder Capital</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalShareholderCapital, 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-user-tie"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Investors</div>
                    <div class="bp-stat-value">{{ $investorCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-coins"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Investor Capital</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalInvestorCapital, 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-clock-rotate-left me-2"></i>Recently Added Investors
                    </h5>
                    <a href="{{ route('investment.investors.index') }}" class="bp-btn bp-btn-sm bp-btn-outline">View all</a>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentInvestors as $i)
                                    <tr>
                                        <td><a href="{{ route('investment.investors.show', $i) }}"
                                                class="fw-600">{{ $i->name }}</a></td>
                                        <td><span
                                                class="bp-badge {{ $i->type === 'shareholder' ? 'bp-badge-primary' : 'bp-badge-info' }}">{{ ucfirst($i->type) }}</span>
                                        </td>
                                        <td class="fs-12 text-muted">{{ $i->join_date?->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="3" icon="fa-solid fa-users" title="No investors yet" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-percent me-2"></i>Recent Distributions</h5>
                    <a href="{{ route('investment.distributions.index') }}" class="bp-btn bp-btn-sm bp-btn-outline">View
                        all</a>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table recent_distribution_area">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Investor</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentDistributions as $d)
                                    <tr>
                                        <td class="fs-12">{{ $d->distribution_date->format('d M Y') }}</td>
                                        <td>{{ $d->investor?->name }}</td>
                                        <td class="fw-600">{{ money($d->distribution_amount) }}</td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="3" icon="fa-solid fa-percent"
                                        title="No distributions yet" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bp-card mt-3">
        <div class="bp-card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-12 text-muted">Total Profit Distributed (all time)</div>
                    <div class="fs-22 fw-800">{{ money($totalDistributed) }}</div>
                </div>
                <i class="fa-solid fa-chart-pie fs-32 text-muted"></i>
            </div>
        </div>
    </div>

@endsection
