@extends('core::layouts.master')

@section('title', 'Payment Detail — ' . $payment->payment_number)
@section('page-title', $payment->payment_number)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('payments.index') }}">Payments</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $payment->payment_number }}</span>
@endsection

@section('page-actions')
    <a class="bp-btn bp-btn-success" href="{{ route('payments.print', $payment) }}" target="_blank" rel="noopener"><i class="fa-solid fa-print me-1"></i> Print Receipt</a>
    <a class="bp-btn bp-btn-warning" href="{{ route('payments.pdf', $payment) }}"><i class="fa-solid fa-download me-1"></i> PDF</a>
    <a href="{{ route('payments.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@section('content')

    @php
        $isReceive = $payment->direction === 'receive';
        $directionLabel = $isReceive ? 'Received' : 'Paid';
        $directionBadge = $isReceive ? 'bp-badge-success' : 'bp-badge-danger';
        $directionIcon = $isReceive ? 'fa-arrow-down' : 'fa-arrow-up';
        $partyLabel = $isReceive ? 'Received From' : 'Paid To';

        $methodIcons = [
            'cash' => 'fa-money-bill-wave text-success',
            'mobile_banking' => 'fa-mobile-screen text-warning',
            'bkash' => 'fa-mobile-screen text-danger',
            'nagad' => 'fa-mobile-screen text-warning',
            'rocket' => 'fa-mobile-screen text-info',
            'card' => 'fa-credit-card text-primary',
            'bank_transfer' => 'fa-building-columns text-info',
        ];
        $methodIcon = $methodIcons[$payment->payment_method] ?? 'fa-wallet text-muted';
        $methodLabel = ucwords(str_replace('_', ' ', $payment->payment_method));

        $companyName = \Modules\Setting\Models\Setting::get(
            'business',
            'company_name',
            config('app.name', 'BizPOS Pro'),
        );
        $currency = \Modules\Setting\Models\Setting::get('localization', 'currency', 'BDT');
    @endphp

    <div class="row g-4">

        <!-- Left: Receipt Card -->
        <div class="col-xl-8">

            <!-- Payment Receipt -->
            <div class="bp-card mb-4" id="paymentReceipt">
                <div class="bp-card-body">

                    <!-- Receipt Header -->
                    <div class="text-center mb-4 pb-3 border-bottom">
                        <h4 class="fw-800 mb-1">{{ $companyName }}</h4>
                        @if ($payment->branch)
                            <div class="fs-13 text-muted">{{ $payment->branch->name }}</div>
                        @endif
                        <div class="mt-3">
                            <span class="bp-badge {{ $directionBadge }} bp-badge-hero">
                                <i class="fa-solid {{ $directionIcon }} me-1"></i>
                                Payment {{ $directionLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- Receipt Details Grid -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-lg">Payment ID</div>
                                <div class="bp-info-value fw-800">{{ $payment->payment_number }}</div>
                            </div>
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-lg">Date</div>
                                <div class="bp-info-value fw-600">{{ $payment->payment_date->format('d M Y') }}</div>
                            </div>
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-lg">Direction</div>
                                <div class="bp-info-value">
                                    <span class="bp-badge {{ $directionBadge }}">
                                        <i class="fa-solid {{ $directionIcon }} me-1"></i>{{ $directionLabel }}
                                    </span>
                                </div>
                            </div>
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-lg">Party Type</div>
                                <div class="bp-info-value">{{ ucfirst($payment->party_type) }}</div>
                            </div>
                            <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                                <div class="bp-info-label bp-info-label-lg">Created By</div>
                                <div class="bp-info-value fw-600">{{ $payment->creator->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-lg">Payment Method</div>
                                <div class="bp-info-value fw-700">
                                    <i class="fa-solid {{ $methodIcon }} me-1"></i> {{ $methodLabel }}
                                </div>
                            </div>
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-lg">Payment Account</div>
                                <div class="bp-info-value fw-600">{{ $payment->paymentAccount->display_name ?? '—' }}</div>
                            </div>
                            @if ($payment->reference)
                                <div class="bp-info-row bp-info-row-compact">
                                    <div class="bp-info-label bp-info-label-lg">Reference / TrxID</div>
                                    <div class="bp-info-value fw-600">{{ $payment->reference }}</div>
                                </div>
                            @endif
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-lg">Payment Type</div>
                                <div class="bp-info-value">{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}
                                </div>
                            </div>
                            <div class="bp-info-row bp-info-row-compact bp-info-row-last d-none">
                                <div class="bp-info-label bp-info-label-lg">Branch</div>
                                <div class="bp-info-value">{{ $payment->branch->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <hr class="my-4">

                    <!-- Party Info -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-700 mb-3">
                                <i class="fa-solid fa-user me-2 text-primary"></i>{{ $partyLabel }}
                            </h6>
                            @php $party = $payment->party(); @endphp
                            @if ($party)
                                <div class="fw-800 fs-14 mb-1">{{ $party->name }}</div>
                                @if (!empty($party->phone))
                                    <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-phone fa-sm me-1"></i>
                                        {{ \App\Helpers\PhoneHelper::format($party->phone) }}</div>
                                @endif
                                @if (!empty($party->email))
                                    <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-envelope fa-sm me-1"></i>
                                        {{ $party->email }}</div>
                                @endif
                                @if (!empty($party->address))
                                    <div class="fs-13 text-muted"><i class="fa-solid fa-location-dot fa-sm me-1"></i>
                                        {{ $party->address }}</div>
                                @endif
                                <div class="mt-2">
                                    <span class="bp-badge bp-badge-info">{{ ucfirst($payment->party_type) }}</span>
                                </div>
                            @else
                                <div class="fs-13 text-muted">Party information not available.</div>
                            @endif
                        </div>

                        @if ($payment->allocations->isNotEmpty())
                            <div class="col-md-6">
                                <h6 class="fw-700 mb-3">
                                    <i class="fa-solid fa-file-invoice me-2 text-warning"></i>Applied To
                                </h6>
                                @foreach ($payment->allocations as $allocation)
                                    <div class="bp-card mb-2">
                                        <div class="bp-card-body p-3">
                                            <div class="bp-info-row bp-info-row-compact">
                                                <div class="bp-info-label">Document</div>
                                                <div class="bp-info-value fw-700">
                                                    @if ($allocation->allocatable)
                                                        {{ class_basename($allocation->allocatable_type) }} —
                                                        {{ $allocation->allocatable->invoice_number ?? '#' . $allocation->allocatable_id }}
                                                    @else
                                                        {{ class_basename($allocation->allocatable_type) }}
                                                        #{{ $allocation->allocatable_id }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="bp-info-row bp-info-row-compact {{ $allocation->discount_amount > 0 ? '' : 'bp-info-row-last' }}">
                                                <div class="bp-info-label">Allocated Amount</div>
                                                <div class="bp-info-value fw-700 text-success">{{ $currency }}
                                                    {{ num($allocation->amount) }}</div>
                                            </div>
                                            @if ($allocation->discount_amount > 0)
                                                <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                                                    <div class="bp-info-label">Discount</div>
                                                    <div class="bp-info-value fw-700 text-warning">{{ $currency }}
                                                        {{ num($allocation->discount_amount) }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Amount Section -->
                    <div class="bp-card mb-0">
                        <div class="bp-card-body text-center py-4">
                            <div class="fs-12 text-muted fw-600 text-uppercase mb-1">
                                Amount {{ $directionLabel }}
                            </div>
                            <div class="fw-800 bp-text-receipt-amount">{{ $currency }} {{ num($payment->amount) }}
                            </div>
                            @if ($payment->discount_amount > 0)
                                <div class="fs-12 text-warning fw-600 mt-1">
                                    + {{ $currency }} {{ num($payment->discount_amount) }} discount written off
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($payment->note)
                        <!-- Note -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="fs-12 fw-600 text-muted text-uppercase mb-1">Note</div>
                            <div class="fs-13">{{ $payment->note }}</div>
                        </div>
                    @endif

                    <!-- Receipt Footer -->
                    <div class="mt-4 pt-3 border-top text-center">
                        <div class="fs-11 text-muted">This is a computer-generated receipt and does not require a signature.
                        </div>
                        <div class="fs-11 text-muted mt-1">{{ $companyName }} &middot; Generated on
                            {{ now()->format('d M Y') }}</div>
                    </div>

                </div>
            </div>

            <!-- Journal Entry -->
            @if ($payment->journalEntry)
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-book me-2 text-info"></i>Journal Entry —
                            {{ $payment->journalEntry->entry_number }}</h5>
                        <span
                            class="bp-badge bp-badge-{{ $payment->journalEntry->status === 'posted' ? 'success' : ($payment->journalEntry->status === 'voided' ? 'danger' : 'dark') }}">
                            {{ ucfirst($payment->journalEntry->status) }}
                        </span>
                    </div>
                    <div class="bp-card-body p-0">
                        <div class="bp-table-wrapper">
                            <table class="bp-table payment_details_table_footer">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th>Debit ({{ $currency }})</th>
                                        <th>Credit ({{ $currency }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($payment->journalEntry->lines as $line)
                                        <tr>
                                            <td>
                                                {{ $line->account->account_code ?? '' }}
                                                @if ($line->account)
                                                    — {{ $line->account->account_name }}
                                                @endif
                                            </td>
                                            <td>{{ $line->description }}</td>
                                            <td>
                                                @if ($line->debit_amount > 0)
                                                    {{ num($line->debit_amount) }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if ($line->credit_amount > 0)
                                                    {{ num($line->credit_amount) }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end px-3 py-2">Totals</td>
                                        <td class="px-2 py-2">{{ num($payment->journalEntry->totalDebit()) }}</td>
                                        <td class="px-2 py-2">{{ num($payment->journalEntry->totalCredit()) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Right: Metadata & Related -->
        <div class="col-xl-4">

            <!-- Payment Summary -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2 text-success"></i>Payment
                        Summary</h5>
                </div>
                <div class="bp-card-body">
                    <div class="text-center mb-3 pb-3 border-bottom">
                        <div class="fs-11 text-muted fw-600 text-uppercase">Amount</div>
                        <div class="fw-800 fs-2 {{ $isReceive ? 'text-success' : 'text-danger' }}">
                            {{ $currency }} {{ num($payment->amount) }}
                        </div>
                    </div>
                    @if ($payment->discount_amount > 0)
                        <div class="bp-info-row bp-info-row-compact">
                            <div class="bp-info-label">Discount Written Off</div>
                            <div class="bp-info-value fw-700 text-warning">{{ $currency }}
                                {{ num($payment->discount_amount) }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row bp-info-row-compact">
                        <div class="bp-info-label">Direction</div>
                        <div class="bp-info-value">
                            <span class="bp-badge {{ $directionBadge }}">
                                <i class="fa-solid {{ $directionIcon }} me-1"></i> {{ $directionLabel }}
                            </span>
                        </div>
                    </div>
                    <div class="bp-info-row bp-info-row-compact">
                        <div class="bp-info-label">Method</div>
                        <div class="bp-info-value fw-700">
                            <i class="fa-solid {{ $methodIcon }} me-1"></i> {{ $methodLabel }}
                        </div>
                    </div>
                    <div class="bp-info-row bp-info-row-compact">
                        <div class="bp-info-label">Party Type</div>
                        <div class="bp-info-value">{{ ucfirst($payment->party_type) }}</div>
                    </div>
                    @if ($payment->party())
                        <div class="bp-info-row bp-info-row-compact">
                            <div class="bp-info-label">Party</div>
                            <div class="bp-info-value fw-700">{{ $payment->party()->name }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row bp-info-row-compact">
                        <div class="bp-info-label">Payment Type</div>
                        <div class="bp-info-value">{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}</div>
                    </div>
                    <div class="bp-info-row bp-info-row-compact">
                        <div class="bp-info-label">Account</div>
                        <div class="bp-info-value">{{ $payment->paymentAccount->display_name ?? '—' }}</div>
                    </div>
                    <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                        <div class="bp-info-label">Branch</div>
                        <div class="bp-info-value">{{ $payment->branch->name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Allocations (if any) -->
            @if ($payment->allocations->isNotEmpty())
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-link me-2 text-info"></i>Allocated Documents</h5>
                    </div>
                    <div class="bp-card-body">
                        @foreach ($payment->allocations as $allocation)
                            <div class="d-flex align-items-center gap-3 p-2 rounded mb-2 bp-linked-doc">
                                <div class="bp-stat-icon icon-primary bp-stat-icon-sm"><i
                                        class="fa-solid fa-file-invoice"></i></div>
                                <div class="flex-1">
                                    <div class="fw-700 fs-13">
                                        @if ($allocation->allocatable)
                                            {{ class_basename($allocation->allocatable_type) }} —
                                            {{ $allocation->allocatable->invoice_number ?? '#' . $allocation->allocatable_id }}
                                        @else
                                            {{ class_basename($allocation->allocatable_type) }}
                                            #{{ $allocation->allocatable_id }}
                                        @endif
                                    </div>
                                    <div class="fs-11 text-muted">Allocation</div>
                                </div>
                                <div class="fw-700 fs-13">{{ $currency }} {{ num($allocation->amount) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Actions -->
            @bpCan('payments.delete')
            <div class="d-flex flex-column gap-2 mb-4">
                <form action="{{ route('payments.destroy', $payment) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center delete-confirm"
                        data-name="{{ $payment->payment_number }}">
                        <i class="fa-solid fa-trash me-2"></i> Delete Payment
                    </button>
                </form>
            </div>
            @endbpCan

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Print Receipt and PDF are now server-rendered links (payments.print /
            // payments.pdf) — no client-side print handler needed.
        });
    </script>
@endpush
