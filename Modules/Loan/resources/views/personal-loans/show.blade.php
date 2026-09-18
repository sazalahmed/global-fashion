@extends('core::layouts.master')

@section('title', $borrower->name . ' — Personal Loan')
@section('page-title', $borrower->name)

@php($receivable = $borrower->receivable_outstanding)
@php($payable = $borrower->payable_outstanding)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('loans.index') }}">Loans</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('personal-loans.index') }}">Personal Loans</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $borrower->name }}</span>
@endsection

@section('page-actions')
    @bpCan('finance.create')
        <button type="button" class="bp-btn bp-btn-danger" data-bs-toggle="modal" data-bs-target="#giveLoanModal"><i
                class="fa-solid fa-hand-holding-dollar me-1"></i> {{ __('Give Loan') }}</button>
        <button type="button" class="bp-btn bp-btn-primary" data-bs-toggle="modal" data-bs-target="#takeLoanModal"><i
                class="fa-solid fa-hand-holding-dollar fa-flip-horizontal me-1"></i> {{ __('Take Loan') }}</button>
    @endbpCan
    @bpCan('finance.edit')
        @if ($receivable > 0)
            <button type="button" class="bp-btn bp-btn-success" data-bs-toggle="modal" data-bs-target="#receivePaymentModal"><i
                    class="fa-solid fa-bangladeshi-taka-sign me-1"></i> {{ __('Receive Payment') }}</button>
        @endif
        @if ($payable > 0)
            <button type="button" class="bp-btn bp-btn-warning" data-bs-toggle="modal" data-bs-target="#payBackModal"><i
                    class="fa-solid fa-rotate-left me-1"></i> {{ __('Pay Back') }}</button>
        @endif
        <a href="{{ route('personal-loans.edit', $borrower) }}" class="bp-btn bp-btn-success"><i
                class="fa-solid fa-pen me-1"></i> Edit</a>
    @endbpCan
    @bpCan('finance.export')
        <x-core::export-dropdown module="personal-loan-ledger" :params="['borrower_id' => $borrower->id]" />
    @endbpCan
    <a href="{{ route('personal-loans.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@section('content')

    <div class="row g-4">
        <!-- Left: party info -->
        <div class="col-xl-4">
            <div class="bp-card mb-4">
                <div class="bp-card-body text-center py-4">
                    <div class="bp-user-avatar mx-auto mb-2 w-80px">
                        @if ($borrower->photo)
                            <img src="{{ upload_url($borrower->photo) }}" alt="{{ $borrower->name }}">
                        @else
                            {{ $borrower->initials }}
                        @endif
                    </div>
                    <h4 class="fw-800 mb-1 text-capitalize">{{ $borrower->name }}</h4>
                    @if ($borrower->status === 'active')
                        <span class="bp-badge bp-badge-success">Active</span>
                    @else
                        <span class="bp-badge bp-badge-secondary">Inactive</span>
                    @endif
                </div>
                <div class="bp-card-body pt-0">
                    @if ($borrower->phone)
                        <div class="bp-info-row">
                            <div class="bp-info-label">Phone</div>
                            <div class="bp-info-value">{{ $borrower->phone }}</div>
                        </div>
                    @endif
                    @if ($borrower->email)
                        <div class="bp-info-row">
                            <div class="bp-info-label">Email</div>
                            <div class="bp-info-value">{{ $borrower->email }}</div>
                        </div>
                    @endif
                    @if ($borrower->address)
                        <div class="bp-info-row">
                            <div class="bp-info-label">Address</div>
                            <div class="bp-info-value">{{ $borrower->address }}</div>
                        </div>
                    @endif
                    @if ($borrower->notes)
                        <div class="bp-info-row bp-info-row-last">
                            <div class="bp-info-label">Notes</div>
                            <div class="bp-info-value">{{ $borrower->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">{{ __('Total Given') }}</div>
                            <div class="bp-stat-value">{{ number_format($totalGiven, 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">{{ __('Recovered') }}</div>
                            <div class="bp-stat-value">{{ number_format($totalRepaid, 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-primary"><i
                                class="fa-solid fa-hand-holding-dollar fa-flip-horizontal"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">{{ __('Total Taken') }}</div>
                            <div class="bp-stat-value">{{ number_format($totalTaken, 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-rotate-left"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">{{ __('Paid Back') }}</div>
                            <div class="bp-stat-value">{{ number_format($totalReturned, 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon {{ $currentBalance >= 0 ? 'icon-danger' : 'icon-warning' }}"><i
                                class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">
                                @if ($currentBalance > 0)
                                    {{ __('They Owe Us') }}
                                @elseif ($currentBalance < 0)
                                    {{ __('We Owe Them') }}
                                @else
                                    {{ __('Balance — Settled') }}
                                @endif
                            </div>
                            <div class="bp-stat-value">{{ currency_symbol() }}
                                {{ number_format(abs($currentBalance), 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: ledger -->
        <div class="col-xl-8">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-book me-2 text-primary"></i>Loan Ledger</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Type</th>
                                    <th>{{ __('Money Out') }}</th>
                                    <th>{{ __('Money In') }}</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledgerEntries as $entry)
                                    <tr>
                                        <td>{{ $entry['date'] ? \Carbon\Carbon::parse($entry['date'])->format('d M Y') : '—' }}
                                        </td>
                                        <td>{{ $entry['reference'] }}</td>
                                        <td>
                                            @if ($entry['type'] === 'Loan Given')
                                                <span class="bp-badge bp-badge-danger">{{ __($entry['type']) }}</span>
                                            @elseif ($entry['type'] === 'Repayment')
                                                <span class="bp-badge bp-badge-success">{{ __($entry['type']) }}</span>
                                            @elseif ($entry['type'] === 'Loan Taken')
                                                <span class="bp-badge bp-badge-primary">{{ __($entry['type']) }}</span>
                                            @elseif ($entry['type'] === 'Paid Back')
                                                <span class="bp-badge bp-badge-warning">{{ __($entry['type']) }}</span>
                                            @else
                                                <span class="bp-badge bp-badge-secondary">{{ __($entry['type']) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $entry['out'] > 0 ? money($entry['out']) : '—' }}</td>
                                        <td class="text-success">{{ $entry['in'] > 0 ? money($entry['in']) : '—' }}</td>
                                        <td class="fw-700 {{ $entry['balance'] < 0 ? 'text-warning' : '' }}">
                                            {{ money(abs($entry['balance'])) }}{{ $entry['balance'] < 0 ? ' ' . __('(we owe)') : '' }}
                                        </td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="6" icon="fa-solid fa-book"
                                        title="No transactions yet" />
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bp-table-total-row">
                                    <td colspan="5" class="text-end fw-800">
                                        @if ($currentBalance > 0)
                                            {{ __('They owe us') }}
                                        @elseif ($currentBalance < 0)
                                            {{ __('We owe them') }}
                                        @else
                                            {{ __('Settled') }}
                                        @endif
                                    </td>
                                    <td class="fw-800 {{ $currentBalance < 0 ? 'text-warning' : 'text-danger' }}">
                                        {{ money(abs($currentBalance)) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @bpCan('finance.create')
        <!-- Give Loan Modal -->
        <div class="modal fade" id="giveLoanModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('personal-loans.disburse', $borrower) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Give Loan') }} — {{ $borrower->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @include('loan::personal-loans._txn-fields')
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="bp-btn bp-btn-success"><i
                                    class="fa-solid fa-hand-holding-dollar me-1"></i> {{ __('Give Loan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Take Loan Modal -->
        <div class="modal fade" id="takeLoanModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('personal-loans.take', $borrower) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Take Loan') }} — {{ $borrower->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @include('loan::personal-loans._txn-fields')
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="bp-btn bp-btn-success"><i
                                    class="fa-solid fa-hand-holding-dollar fa-flip-horizontal me-1"></i>
                                {{ __('Take Loan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endbpCan

    @bpCan('finance.edit')
        @if ($receivable > 0)
            <!-- Receive Payment Modal -->
            <div class="modal fade" id="receivePaymentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('personal-loans.repay', $borrower) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('Receive Payment') }} — {{ $borrower->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="bp-info-row bp-info-row-last mb-2">
                                    <div class="bp-info-label">{{ __('They Owe (loans given)') }}</div>
                                    <div class="bp-info-value fw-800 text-danger">{{ money($receivable) }}</div>
                                </div>
                                @include('loan::personal-loans._txn-fields', ['maxAmount' => $receivable])
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="bp-btn bp-btn-outline" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i>
                                    {{ __('Receive Payment') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if ($payable > 0)
            <!-- Pay Back Modal -->
            <div class="modal fade" id="payBackModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('personal-loans.pay-back', $borrower) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('Pay Back') }} — {{ $borrower->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="bp-info-row bp-info-row-last mb-2">
                                    <div class="bp-info-label">{{ __('We Owe (loans taken)') }}</div>
                                    <div class="bp-info-value fw-800 text-warning">{{ money($payable) }}</div>
                                </div>
                                @include('loan::personal-loans._txn-fields', ['maxAmount' => $payable])
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="bp-btn bp-btn-outline" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i>
                                    {{ __('Pay Back') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endbpCan

    @push('scripts')
        <script>
            'use strict';
            $(function() {
                // Deep link from the list: ?action=give-loan / take-loan opens the modal.
                var params = new URLSearchParams(window.location.search);
                var action = params.get('action');
                var modalId = action === 'give-loan' ? 'giveLoanModal' : (action === 'take-loan' ? 'takeLoanModal' :
                    null);
                if (modalId) {
                    var el = document.getElementById(modalId);
                    if (el) new bootstrap.Modal(el).show();
                    // Strip the param so a reload (or the post-submit redirect back to
                    // this page) doesn't reopen the modal.
                    params.delete('action');
                    var qs = params.toString();
                    window.history.replaceState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
                }
            });
        </script>
    @endpush

@endsection
