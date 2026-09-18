@extends('core::layouts.master')

@section('title', $investor->name)
@section('page-title', $investor->name)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('investment.dashboard') }}">Investment</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('investment.investors.index') }}">Investors</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $investor->name }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('investment.investors.index') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i>Back to Investors</a>
    @bpCan('accounting.create')
        <a href="{{ route('investment.capital.create', ['investor_id' => $investor->id]) }}" class="bp-btn bp-btn-warning"><i
                class="fa-solid fa-arrow-down me-1"></i>Record Capital</a>
    @endbpCan
    @bpCan('accounting.edit')
        <a href="{{ route('investment.investors.edit', $investor) }}" class="bp-btn bp-btn-success"><i
                class="fa-solid fa-pen me-1"></i>Edit</a>
    @endbpCan
@endsection

@section('content')

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-user"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Type</div>
                    <div class="bp-stat-value">{{ ucfirst($investor->type) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-percent"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">{{ $investor->type === 'shareholder' ? 'Share %' : 'Profit Share %' }}</div>
                    <div class="bp-stat-value">{{ num($totals['share_pct']) }}%</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Net Capital</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totals['net_capital'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-coins"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Distributed</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totals['distributed'], 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-id-card me-2"></i>Contact</h5>
                </div>
                <div class="bp-card-body">
                    @if ($investor->phone)
                        <div class="mb-2"><i class="fa-solid fa-phone me-2 text-muted"></i>{{ $investor->phone }}</div>
                    @endif
                    @if ($investor->email)
                        <div class="mb-2"><i class="fa-solid fa-envelope me-2 text-muted"></i>{{ $investor->email }}
                        </div>
                    @endif
                    @if ($investor->address)
                        <div class="mb-2"><i
                                class="fa-solid fa-location-dot me-2 text-muted"></i>{{ $investor->address }}</div>
                    @endif
                    @if ($investor->nid_or_tin)
                        <div class="mb-2"><i class="fa-solid fa-id-badge me-2 text-muted"></i>{{ $investor->nid_or_tin }}
                        </div>
                    @endif
                    <div class="mb-2"><i class="fa-solid fa-calendar me-2 text-muted"></i>Joined
                        {{ $investor->join_date?->format('d M Y') }}</div>
                    @if ($investor->type === 'shareholder')
                        <div class="mb-2"><i class="fa-solid fa-chart-pie me-2 text-muted"></i>Shares:
                            {{ number_format($investor->shares_owned ?? 0, 0) }}</div>
                    @endif
                    @if ($investor->notes)
                        <div class="mt-3 fs-13 text-muted">{{ $investor->notes }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Capital History</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Account</th>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($investor->capital as $c)
                                    <tr>
                                        <td class="fs-12">{{ $c->transaction_date->format('d M Y') }}</td>
                                        <td>
                                            @if ($c->type === 'inject')
                                                <span class="bp-badge bp-badge-success">Inject</span>
                                            @else
                                                <span class="bp-badge bp-badge-warning">Withdraw</span>
                                            @endif
                                        </td>
                                        <td>{{ $c->paymentAccount?->name }}</td>
                                        <td>{{ $c->reference ?: '' }}</td>
                                        <td>{{ money($c->amount) }}</td>
                                        <td>
                                            @bpCan('accounting.delete')
                                                <div class="dropdown">
                                                    <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                                            class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <form method="POST"
                                                                action="{{ route('investment.capital.destroy', $c) }}"
                                                                onsubmit="return confirm('Reverse this capital entry and its journal entry?')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger"><i
                                                                        class="fa-solid fa-rotate-left me-2"></i>
                                                                    Reverse</button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            @endbpCan
                                        </td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="6" icon="fa-solid fa-arrow-right-arrow-left"
                                        title="No capital transactions yet" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-percent me-2"></i>Profit Distributions Received</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Period</th>
                                    <th class="text-end">Share %</th>
                                    <th class="text-end">Net Profit</th>
                                    <th class="text-end">Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($investor->distributions as $d)
                                    <tr>
                                        <td class="fs-12">{{ $d->distribution_date->format('d M Y') }}</td>
                                        <td class="fs-12">{{ $d->period_start->format('d M Y') }} →
                                            {{ $d->period_end->format('d M Y') }}</td>
                                        <td class="text-end">{{ num($d->share_pct_used) }}%</td>
                                        <td class="text-end fs-12 text-muted">{{ money($d->net_profit_snapshot) }}</td>
                                        <td class="text-end fw-600">{{ money($d->distribution_amount) }}</td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="5" icon="fa-solid fa-percent"
                                        title="No distributions received" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
