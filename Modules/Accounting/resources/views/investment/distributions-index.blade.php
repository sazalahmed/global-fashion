@extends('core::layouts.master')

@section('title', 'Profit Distributions')
@section('page-title', 'Profit Distributions')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('investment.dashboard') }}">Investment</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Distributions</span>
@endsection

@section('page-actions')
    @bpCan('accounting.create')
        <a href="{{ route('investment.distributions.create') }}" class="bp-btn bp-btn-success"><i
                class="fa-solid fa-percent me-1"></i>Distribute Profit</a>
    @endbpCan
    <a href="{{ route('investment.dashboard') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i>Back</a>
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Investor</th>
                            <th>Period</th>
                            <th>Share %</th>
                            <th>Net Profit</th>
                            <th>Amount</th>
                            <th>Account</th>
                            <th>Batch</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($distributions as $d)
                            <tr>
                                <td>{{ $d->distribution_date->format('d M Y') }}</td>
                                <td>
                                    @if ($d->investor)
                                        <a class="text-black fw-600"
                                            href="{{ route('investment.investors.show', $d->investor) }}">{{ $d->investor->name }}</a>
                                        <div class="fs-11"><span
                                                class="bp-badge {{ $d->investor->type === 'shareholder' ? 'bp-badge-primary' : 'bp-badge-info' }}">{{ ucfirst($d->investor->type) }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted">deleted</span>
                                    @endif
                                </td>
                                <td>{{ $d->period_start->format('d M Y') }} →
                                    {{ $d->period_end->format('d M Y') }}</td>
                                <td>{{ num($d->share_pct_used) }}%</td>
                                <td>{{ money($d->net_profit_snapshot) }}</td>
                                <td>{{ money($d->distribution_amount) }}</td>
                                <td>{{ $d->paymentAccount?->name }}</td>
                                <td>{{ $d->batch_ref }}</td>
                                <td>
                                    @bpCan('accounting.delete')
                                        <div class="dropdown">
                                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                                    class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <form method="POST"
                                                        action="{{ route('investment.distributions.destroy', $d) }}"
                                                        onsubmit="return confirm('Reverse this distribution and its journal entry?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"><i
                                                                class="fa-solid fa-rotate-left me-2"></i> Reverse</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    @endbpCan
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="9" icon="fa-solid fa-percent"
                                title="No distributions posted yet" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($distributions->hasPages())
            <div class="bp-card-footer">{{ $distributions->links('core::components.table.pagination-links') }}</div>
        @endif
    </div>

@endsection
