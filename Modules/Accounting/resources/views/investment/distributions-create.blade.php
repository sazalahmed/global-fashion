@extends('core::layouts.master')

@section('title', 'Distribute Profit')
@section('page-title', 'Distribute Profit')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('investment.dashboard') }}">Investment</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('investment.distributions.index') }}">Distributions</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>New</span>
@endsection

@section('page-actions')
    <a href="{{ route('investment.distributions.index') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i>Back to Distributions</a>
@endsection

@section('content')

    {{-- Step 1: pick the period --}}
    <form method="GET" action="{{ route('investment.distributions.create') }}" class="mb-3">
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-calendar-days me-2"></i>1. Select Period</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="bp-form-label">Period Start *</label>
                        <input type="date" class="bp-form-control" name="period_start" value="{{ $from }}"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Period End *</label>
                        <input type="date" class="bp-form-control" name="period_end" value="{{ $to }}"
                            required>
                    </div>
                    <div class="col-md-4">
                        <button class="bp-btn bp-btn-success"><i class="fa-solid fa-magnifying-glass-chart me-1"></i>Preview
                            Distribution</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if ($preview)
        {{-- Step 2: preview + confirm --}}
        <form method="POST" action="{{ route('investment.distributions.store') }}">
            @csrf
            <input type="hidden" name="period_start" value="{{ $from }}">
            <input type="hidden" name="period_end" value="{{ $to }}">
            <input type="hidden" name="net_profit_snapshot" value="{{ $preview['net_profit'] }}">

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Net Profit ({{ $from }} → {{ $to }})</div>
                            <div class="bp-stat-value">{{ money($preview['net_profit']) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-coins"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Investor Pool (off the top)</div>
                            <div class="bp-stat-value">{{ money($preview['investor_pool']) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-people-group"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Shareholder Pool</div>
                            <div class="bp-stat-value">{{ money($preview['shareholder_pool']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-list-check me-2"></i>2. Review & Edit Amounts</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Investor</th>
                                    <th>Type</th>
                                    <th>Share %</th>
                                    <th>Amount (editable)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($preview['rows'] as $i => $r)
                                    <tr>
                                        <td class="fw-600">{{ $r['investor']->name }}</td>
                                        <td><span
                                                class="bp-badge {{ $r['type'] === 'shareholder' ? 'bp-badge-primary' : 'bp-badge-info' }}">{{ ucfirst($r['type']) }}</span>
                                        </td>
                                        <td>{{ number_format($r['share_pct'], 4) }}%</td>
                                        <td>
                                            <input type="hidden" name="rows[{{ $i }}][investor_id]"
                                                value="{{ $r['investor']->id }}">
                                            <input type="hidden" name="rows[{{ $i }}][share_pct]"
                                                value="{{ $r['share_pct'] }}">
                                            <input type="number" step="0.01" min="0"
                                                class="bp-form-control" name="rows[{{ $i }}][amount]"
                                                value="{{ $r['amount'] }}"
                                                style="max-width: 180px; display: inline-block;">
                                        </td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="4" icon="fa-solid fa-users-slash"
                                        title="No active investors found for this period" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if (count($preview['rows']) > 0)
                <div class="bp-card">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-credit-card me-2"></i>3. Payout Details</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Distribution Date *</label>
                                <input type="date" class="bp-form-control" name="distribution_date"
                                    value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Pay From Account *</label>
                                <select name="payment_account_id" class="bp-form-select w-100" required>
                                    <option value="">Select</option>
                                    @foreach ($accounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="bp-form-label">Note</label>
                                <input type="text" class="bp-form-control" name="note"
                                    placeholder="Q1 2026 profit, etc.">
                            </div>
                        </div>
                    </div>
                    <div class="bp-card-footer text-end">
                        <a href="{{ route('investment.distributions.index') }}" class="bp-btn bp-btn-danger me-2"><i
                                class="fa-solid fa-xmark me-1"></i>Cancel</a>
                        <button class="bp-btn bp-btn-success"
                            onclick="return confirm('Post these distributions and create journal entries?')">
                            <i class="fa-solid fa-check me-1"></i>Post Distributions
                        </button>
                    </div>
                </div>
            @endif
        </form>
    @endif

@endsection
