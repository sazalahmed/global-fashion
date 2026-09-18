@extends('core::layouts.master')

@section('title', 'Account Ledger — ' . $paymentAccount->name)
@section('page-title', $paymentAccount->name . ' — Ledger')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('payment-accounts.index') }}">Payment Accounts</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $paymentAccount->name }} Ledger</span>
@endsection

@section('page-actions')
    <form action="{{ route('payment-accounts.ledger', $paymentAccount) }}" method="GET"
        class="d-flex gap-2 align-items-center payment_acc_ledger_filters pe-5">
        <input type="date" class="bp-form-control" name="from" value="{{ request('from') }}" placeholder="From">
        <span class="text-muted">to</span>
        <input type="date" class="bp-form-control" name="to" value="{{ request('to') }}" placeholder="To">
        <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary search_buttons" title="Apply Filters"><i
                class="fa-solid fa-filter"></i></button>
        <a href="{{ route('payment-accounts.ledger', $paymentAccount) }}"
            class="bp-btn bp-btn-sm bp-btn-danger search_buttons" title="Reset Filters"><i
                class="fa-solid fa-rotate"></i></a>
    </form>
    <a href="{{ route('payment-accounts.index') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-wallet"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">{{ request('from') ? 'Brought Forward' : 'Opening Balance' }}</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($openingBalance, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total In</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalIn, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Out</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalOut, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-scale-balanced"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Current Balance</div>
                    <div class="bp-stat-value {{ $balance >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ currency_symbol() }} {{ number_format($balance, 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title">
                <i class="fa-solid fa-list me-2"></i>
                {{ $paymentAccount->name }}
                <span
                    class="bp-badge bp-badge-primary ms-2">{{ ucfirst(str_replace('_', ' ', $paymentAccount->account_type)) }}</span>
            </h5>
            <span class="text-muted fs-13">{{ number_format($transactions->total()) }} transactions</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>IN (+)</th>
                            <th>OUT (-)</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            @php
                                $typeBadge = match ($txn->txn_type) {
                                    'receive' => 'bp-badge-success',
                                    'pay' => 'bp-badge-danger',
                                    'transfer_in' => 'bp-badge-info',
                                    'transfer_out' => 'bp-badge-warning',
                                    'bank_charge' => 'bp-badge-danger',
                                    'capital_in' => 'bp-badge-success',
                                    'capital_out' => 'bp-badge-warning',
                                    'distribution' => 'bp-badge-secondary',
                                    'expense' => 'bp-badge-danger',
                                    'loan_in' => 'bp-badge-success',
                                    'loan_out' => 'bp-badge-warning',
                                    'courier_withdrawal' => 'bp-badge-info',
                                    'asset' => 'bp-badge-warning',
                                    'payroll' => 'bp-badge-danger',
                                    default => 'bp-badge-dark',
                                };
                                $typeLabel = match ($txn->txn_type) {
                                    'receive' => 'Received',
                                    'pay' => 'Paid Out',
                                    'transfer_in' => 'Transfer In',
                                    'transfer_out' => 'Transfer Out',
                                    'bank_charge' => 'Bank Charge',
                                    'capital_in' => 'Capital In',
                                    'capital_out' => 'Capital Out',
                                    'distribution' => 'Distribution',
                                    'expense' => 'Expense',
                                    'loan_in' => 'Loan In',
                                    'loan_out' => 'Loan Out',
                                    'courier_withdrawal' => 'Courier',
                                    'asset' => 'Asset',
                                    'payroll' => 'Salary',
                                    default => $txn->txn_type,
                                };
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($txn->date)->format('d M Y') }}</td>
                                <td>{{ $txn->reference }}</td>
                                <td>{{ $txn->description }}</td>
                                <td><span class="bp-badge {{ $typeBadge }}">{{ $typeLabel }}</span></td>
                                <td class="fw-800 {{ $txn->in_amount > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ $txn->in_amount > 0 ? currency_symbol() . ' ' . number_format($txn->in_amount, 0) : '' }}
                                </td>
                                <td class="fw-800 {{ $txn->out_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                    {{ $txn->out_amount > 0 ? currency_symbol() . ' ' . number_format($txn->out_amount, 0) : '' }}
                                </td>
                                <td class="fw-800">{{ currency_symbol() }}
                                    {{ number_format($txn->running_balance, 0) }}</td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="7" icon="fa-solid fa-list"
                                title="No transactions found for this period" />
                        @endforelse

                        {{-- Oldest row last, so it belongs on the final page only --}}
                        @if (!$transactions->hasMorePages())
                            <tr class="bp-table-highlight">
                                <td></td>
                                <td></td>
                                <td>{{ request('from') ? 'Balance Brought Forward' : 'Opening Balance' }}</td>
                                <td><span
                                        class="bp-badge bp-badge-dark">{{ request('from') ? 'B/F' : 'Opening' }}</span>
                                </td>
                                <td></td>
                                <td></td>
                                <td class="fw-800">{{ currency_symbol() }}
                                    {{ number_format($openingBalance, 0) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <x-core::table.pagination :paginator="$transactions" itemLabel="transactions" />
    </div>

@endsection
