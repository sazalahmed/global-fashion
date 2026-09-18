@extends('core::layouts.master')

@section('title', 'Expense Detail — ' . $expense->expense_number)
@section('page-title', $expense->expense_number)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('expenses.index') }}">Expenses</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $expense->expense_number }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('expenses.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

    @php
        $statusClass = match ($expense->status) {
            'approved' => 'info',
            'paid' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };
        $statusLabel = ucfirst($expense->status);
    @endphp

    <div class="row g-4">

        <!-- Left Column: Expense Details -->
        <div class="col-xl-8">

            <!-- Expense Hero -->
            <div class="bp-invoice-hero mb-0">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="inv-number">{{ $expense->expense_number }}</div>
                        <div class="inv-meta">
                            <span class="me-3"><i class="fa-solid fa-calendar me-1"></i>
                                {{ $expense->expense_date->format('d M Y') }}</span>
                            <span class="me-3 d-none"><i class="fa-solid fa-code-branch me-1"></i>
                                {{ $expense->branch->name ?? 'N/A' }}</span>
                            <span><i class="fa-solid fa-user me-1"></i> Added by {{ $expense->creator->name }}</span>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <span class="bp-badge bp-badge-{{ $statusClass }} bp-badge-hero"><i
                                    class="fa-solid fa-check-circle me-1"></i> {{ $statusLabel }}</span>
                            <span class="bp-badge bp-badge-primary bp-badge-hero"><i class="fa-solid fa-bolt me-1"></i>
                                {{ $expense->category->name }}</span>
                        </div>
                    </div>
                    <div class="col-md-5 mt-3 mt-md-0">
                        <div class="bp-invoice-hero-stat">
                            <div class="stat-label">Expense Amount</div>
                            <div class="stat-value">{{ money($expense->total_amount) }}</div>
                            <div class="stat-sub"><i class="fa-solid fa-building-columns me-1"></i> Paid via
                                {{ $expense->payment_method_label }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expense Information -->
            <div class="row g-4 mt-0">
                <div class="col-12">
                    <div class="bp-card mb-4">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-money-bill-wave me-2 text-primary"></i>Expense
                                Information</h5>
                        </div>
                        <div class="bp-card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="bp-info-row bp-info-row-compact">
                                        <div class="bp-info-label bp-info-label-lg">Expense ID</div>
                                        <div class="bp-info-value fw-800">{{ $expense->expense_number }}</div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact">
                                        <div class="bp-info-label bp-info-label-lg">Date</div>
                                        <div class="bp-info-value fw-600">{{ $expense->expense_date->format('d M Y') }}
                                        </div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact">
                                        <div class="bp-info-label bp-info-label-lg">Category</div>
                                        <div class="bp-info-value"><span
                                                class="bp-badge bp-badge-primary">{{ $expense->category->name }}</span>
                                        </div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact">
                                        <div class="bp-info-label bp-info-label-lg">Reference #</div>
                                        <div class="bp-info-value fw-600">{{ $expense->reference ?? '—' }}</div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact d-none">
                                        <div class="bp-info-label bp-info-label-lg">Branch</div>
                                        <div class="bp-info-value">{{ $expense->branch->name ?? 'N/A' }}</div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact">
                                        <div class="bp-info-label bp-info-label-lg">Added By</div>
                                        <div class="bp-info-value fw-600">{{ $expense->creator->name }}</div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact">
                                        <div class="bp-info-label bp-info-label-lg">Status</div>
                                        <div class="bp-info-value"><span
                                                class="bp-badge bp-badge-{{ $statusClass }}">{{ $statusLabel }}</span>
                                        </div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact">
                                        <div class="bp-info-label bp-info-label-lg">Payment Method</div>
                                        <div class="bp-info-value fw-700"><i
                                                class="fa-solid fa-building-columns text-info me-1"></i>
                                            {{ $expense->payment_method_label }}</div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact">
                                        <div class="bp-info-label bp-info-label-lg">Amount</div>
                                        <div class="bp-info-value fw-800 text-danger">{{ money($expense->amount) }}</div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact">
                                        <div class="bp-info-label bp-info-label-lg">Tax</div>
                                        <div class="bp-info-value fw-600">{{ money($expense->tax_amount) }}</div>
                                    </div>
                                    <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                                        <div class="bp-info-label bp-info-label-lg">Total Amount</div>
                                        <div class="bp-info-value fw-800 text-danger">{{ money($expense->total_amount) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-align-left me-2 text-muted"></i>Description</h5>
                </div>
                <div class="bp-card-body">
                    <p class="mb-0 fs-13">{{ $expense->description ?: '—' }}</p>
                </div>
            </div>

            <!-- Journal Entry -->
            @if ($expense->journalEntry)
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-book me-2 text-info"></i>Journal Entry</h5>
                    </div>
                    <div class="bp-card-body">
                        <a href="{{ route('accounting.journal-entries.show', $expense->journalEntry) }}"
                            class="bp-btn bp-btn-sm bp-btn-outline">
                            <i class="fa-solid fa-eye me-1"></i> View Journal Entry
                            #{{ $expense->journalEntry->journal_number ?? $expense->journalEntry->id }}
                        </a>
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Column: Status, Approval, Actions -->
        <div class="col-xl-4">

            <!-- Expense Summary -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2 text-danger"></i>Expense Summary</h5>
                </div>
                <div class="bp-card-body">
                    <div class="text-center mb-3 pb-3 border-bottom">
                        <div class="fs-11 text-muted fw-600 text-uppercase">Total Amount</div>
                        <div class="fw-800 fs-2 text-danger">{{ money($expense->total_amount) }}</div>
                    </div>
                    <div class="bp-info-row bp-info-row-compact">
                        <div class="bp-info-label">Category</div>
                        <div class="bp-info-value"><span
                                class="bp-badge bp-badge-primary">{{ $expense->category->name }}</span></div>
                    </div>
                    <div class="bp-info-row bp-info-row-compact">
                        <div class="bp-info-label">Method</div>
                        <div class="bp-info-value fw-700"><i class="fa-solid fa-building-columns text-info me-1"></i>
                            {{ $expense->payment_method_label }}</div>
                    </div>
                    <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                        <div class="bp-info-label">Status</div>
                        <div class="bp-info-value"><span
                                class="bp-badge bp-badge-{{ $statusClass }}">{{ $statusLabel }}</span></div>
                    </div>
                </div>
            </div>

            <!-- Approval Actions -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-clipboard-check me-2 text-success"></i>Approval</h5>
                </div>
                <div class="bp-card-body">
                    <div class="text-center mb-3">
                        <span class="bp-badge bp-badge-{{ $statusClass }} bp-badge-hero"><i
                                class="fa-solid fa-check-circle me-1"></i> {{ $statusLabel }}</span>
                    </div>

                    @if ($expense->status === 'pending')
                        @bpCan('finance.edit')
                            <!-- Approve -->
                            <form action="{{ route('expenses.approve', $expense) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
                                    <i class="fa-solid fa-check me-2"></i> Approve Expense
                                </button>
                            </form>
                            <!-- Reject -->
                            <form action="{{ route('expenses.reject', $expense) }}" method="POST">
                                @csrf
                                <div class="mb-2">
                                    <label class="bp-form-label">Rejection Reason</label>
                                    <textarea class="bp-form-control" name="reason" rows="2" placeholder="State reason for rejection..."
                                        required></textarea>
                                </div>
                                <button type="submit" class="bp-btn bp-btn-warning w-100 justify-content-center">
                                    <i class="fa-solid fa-xmark me-2"></i> Reject Expense
                                </button>
                            </form>
                        @endbpCan
                    @elseif(in_array($expense->status, ['approved', 'paid']) && $expense->payment_status !== 'paid')
                        @bpCan('finance.edit')
                            <!-- Record Payment -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="bp-form-label mb-0">Outstanding Due</span>
                                    <span class="fw-800 text-danger">{{ currency_symbol() }}
                                        {{ number_format($expense->due_amount, 0) }}</span>
                                </div>
                                @if ((float) $expense->paid_amount > 0)
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fs-12 text-muted">Already Paid</span>
                                        <span class="fw-600 text-success">{{ currency_symbol() }}
                                            {{ number_format($expense->paid_amount, 0) }}</span>
                                    </div>
                                @endif
                            </div>
                            <form action="{{ route('expenses.record-payment', $expense) }}" method="POST"
                                id="expensePaymentForm">
                                @csrf
                                <input type="hidden" name="amount" id="epTotalAmount" value="{{ $expense->due_amount }}">

                                <div class="mb-2">
                                    <label class="bp-form-label">Payment Date *</label>
                                    <input type="date" class="bp-form-control" name="payment_date"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>

                                <div class="mb-2">
                                    <label class="bp-form-label">Payment Splits *</label>
                                    <div id="epSplitRows">
                                        <div class="bp-split-payment-row" data-index="0">
                                            <div class="bp-pay-amount-col">
                                                <input type="number" class="bp-form-control ep-split-amount"
                                                    name="splits[0][amount]" placeholder="Amount" step="0.01"
                                                    min="0" max="{{ $expense->due_amount }}"
                                                    value="{{ $expense->due_amount }}" required>
                                            </div>
                                            <div class="bp-pay-method-col">
                                                <select class="bp-form-select ep-split-account"
                                                    name="splits[0][payment_account_id]" required>
                                                    <option value="">Select Account</option>
                                                    @foreach (\Modules\Payment\Models\PaymentAccount::where('is_active', true)->get() as $pa)
                                                        <option value="{{ $pa->id }}"
                                                            data-type="{{ $pa->account_type }}"
                                                            {{ $pa->id == $expense->payment_account_id ? 'selected' : '' }}>
                                                            {{ $pa->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="button" class="remove-split d-none" title="Remove"><i
                                                    class="fa-solid fa-times"></i></button>
                                        </div>
                                    </div>
                                    <button type="button" class="bp-btn bp-btn-sm bp-btn-outline mt-2" id="epAddSplit"><i
                                            class="fa-solid fa-plus me-1"></i> Add Split</button>
                                </div>

                                <div class="mb-2">
                                    <label class="bp-form-label">Reference</label>
                                    <input type="text" name="reference" class="bp-form-control"
                                        placeholder="e.g. Receipt #, Cheque #">
                                </div>
                                <div class="mb-3 d-flex justify-content-between align-items-center">
                                    <span class="fs-12 fw-800">Total: <span id="epTotalDisplay">{{ currency_symbol() }}
                                            {{ number_format($expense->due_amount, 0) }}</span></span>
                                </div>




                                <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
                                    <i class="fa-solid fa-bangladeshi-taka-sign me-2"></i> Record Payment
                                </button>
                            </form>
                        @endbpCan
                    @elseif($expense->status === 'approved' && $expense->payment_status === 'paid')
                        <div class="text-center text-success py-2">
                            <i class="fa-solid fa-circle-check fa-2x mb-2"></i>
                            <div class="fw-700">Fully Paid</div>
                        </div>
                    @else
                        <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                            <div class="bp-info-label">Submitted By</div>
                            <div class="bp-info-value fw-600">{{ $expense->creator->name }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="d-flex flex-wrap justify-content-end gap-2 mb-4">
                @if ($expense->status === 'pending')
                    @bpCan('finance.edit')
                        <a href="{{ route('expenses.edit', $expense) }}"
                            class="bp-btn bp-btn-success justify-content-center"><i class="fa-solid fa-pen me-2"></i>
                            Edit Expense</a>
                    @endbpCan
                @endif
                @bpCan('finance.delete')
                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center delete-confirm"
                            data-name="{{ $expense->expense_number }}"><i class="fa-solid fa-trash me-2"></i> Delete
                            Expense</button>
                    </form>
                @endbpCan
            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // ── Expense Split Payment Logic ──
            var epSplitIndex = 1;
            var epMaxDue = parseFloat('{{ $expense->due_amount }}') || 0;
            var epAccountOptionsHtml = $('#epSplitRows .bp-split-payment-row:first .ep-split-account').length ?
                $('#epSplitRows .bp-split-payment-row:first .ep-split-account').html() : '';

            function epRecalcSplits() {
                var total = 0;
                $('#epSplitRows .ep-split-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#epTotalAmount').val(total > 0 ? window.numInput(total) : '');
                $('#epTotalDisplay').text('{{ currency_symbol() }} ' + Math.round(total).toLocaleString('en-IN'));
            }

            $('#epAddSplit').on('click', function() {
                var $rows = $('#epSplitRows');
                var idx = epSplitIndex++;
                var paid = 0;
                $rows.find('.ep-split-amount').each(function() {
                    paid += parseFloat($(this).val()) || 0;
                });
                var remaining = Math.max(0, epMaxDue - paid);
                var html = '<div class="bp-split-payment-row" data-index="' + idx + '">' +
                    '<div class="bp-pay-amount-col">' +
                    '<input type="number" class="bp-form-control ep-split-amount" name="splits[' + idx +
                    '][amount]" placeholder="Amount" step="0.01" min="0" value="' + (remaining > 0 ?
                        remaining : '') + '" required>' +
                    '</div>' +
                    '<div class="bp-pay-method-col">' +
                    '<select class="bp-form-select ep-split-account" name="splits[' + idx +
                    '][payment_account_id]" required>' +
                    epAccountOptionsHtml +
                    '</select>' +
                    '</div>' +
                    '<button type="button" class="remove-split" title="Remove"><i class="fa-solid fa-times"></i></button>' +
                    '</div>';
                $rows.append(html);
                $rows.find('.remove-split').removeClass('d-none');
                epRecalcSplits();
            });

            $(document).on('click', '#epSplitRows .remove-split', function() {
                $(this).closest('.bp-split-payment-row').remove();
                var $rows = $('#epSplitRows .bp-split-payment-row');
                if ($rows.length === 1) $rows.find('.remove-split').addClass('d-none');
                epRecalcSplits();
            });

            $(document).on('input', '.ep-split-amount', function() {
                epRecalcSplits();
            });

            // Validate total does not exceed due
            $('#expensePaymentForm').on('submit', function(e) {
                var total = parseFloat($('#epTotalAmount').val()) || 0;
                if (total <= 0) {
                    e.preventDefault();
                    alert('Payment amount must be greater than zero.');
                    return false;
                }
                if (total > epMaxDue) {
                    e.preventDefault();
                    alert('Total splits ({{ currency_symbol() }} ' + total.toFixed(2) +
                        ') exceed the due amount ({{ currency_symbol() }} ' + epMaxDue.toFixed(2) +
                        ').');
                    return false;
                }
            });
        });
    </script>
@endpush
