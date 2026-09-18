@extends('core::layouts.master')

@section('title', 'Loan — ' . $loan->loan_number)
@section('page-title', $loan->loan_number)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('loans.index') }}">Loans</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $loan->loan_number }}</span>
@endsection

@section('page-actions')
    @if (in_array($loan->status, ['active', 'overdue']))
        @bpCan('finance.edit')
            <button type="button" class="bp-btn bp-btn-warning" id="rescheduleToggle"><i class="fa-solid fa-calendar-pen me-1"></i>
                Reschedule</button>
        @endbpCan
    @endif
    @if ($loan->status === 'active' && $loan->total_repaid == 0)
        @bpCan('finance.edit')
            <button class="bp-btn bp-btn-danger" onclick="document.getElementById('cancelLoanForm').submit()"><i
                    class="fa-solid fa-xmark me-1"></i> Cancel Loan</button>
        @endbpCan
    @endif
    <a href="{{ route('loans.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

    <!-- Loan Hero Banner -->
    <div class="bp-invoice-hero mb-0">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="inv-number">{{ $loan->loan_number }}</div>
                <div class="inv-meta">
                    <span class="me-3"><i class="fa-solid fa-user me-1"></i> {{ $loan->lender->name ?? '-' }}</span>
                    <span class="me-3"><i class="fa-solid fa-calendar me-1"></i> Disbursed:
                        {{ $loan->disbursement_date->format('d M Y') }}</span>
                    <span><i class="fa-solid fa-wallet me-1"></i> {{ $loan->paymentAccount->name ?? '-' }}</span>
                </div>
                <div class="d-flex gap-2 mt-3">
                    @if ($loan->status === 'active')
                        <span class="bp-badge bp-badge-primary bp-badge-hero"><i class="fa-solid fa-spinner me-1"></i>
                            Active</span>
                    @elseif($loan->status === 'completed')
                        <span class="bp-badge bp-badge-success bp-badge-hero"><i class="fa-solid fa-check me-1"></i>
                            Completed</span>
                    @elseif($loan->status === 'overdue')
                        <span class="bp-badge bp-badge-danger bp-badge-hero"><i
                                class="fa-solid fa-triangle-exclamation me-1"></i> Overdue</span>
                    @elseif($loan->status === 'cancelled')
                        <span class="bp-badge bp-badge-dark bp-badge-hero"><i class="fa-solid fa-xmark me-1"></i>
                            Cancelled</span>
                    @endif

                    @php
                        $paidCount = $loan->schedules->where('status', 'paid')->count();
                    @endphp
                    <span class="bp-badge bp-badge-primary bp-badge-hero"><i class="fa-solid fa-calendar-check me-1"></i>
                        {{ $paidCount }} of {{ $loan->total_installments }} Paid</span>
                </div>
            </div>
            <div class="col-md-5 mt-3 mt-md-0">
                <div class="bp-invoice-hero-stat">
                    <div class="stat-label">Principal Amount</div>
                    <div class="stat-value">{{ money($loan->principal_amount) }}</div>
                    <div class="stat-sub"><i class="fa-solid fa-bangladeshi-taka-sign me-1"></i>
                        {{ money($loan->total_repaid) }} repaid &middot; {{ money($loan->total_remaining) }} remaining
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-4 mt-0">

        <!-- Left Column: Schedule -->
        <div class="col-xl-8">

            <!-- Lender Info -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-user me-2 text-primary"></i>Lender</h5>
                </div>
                <div class="bp-card-body">
                    <div class="fw-800 fs-14 mb-1">{{ $loan->lender->name ?? '-' }}</div>
                    @if ($loan->lender && $loan->lender->company_name)
                        <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-building fa-sm me-1"></i>
                            {{ $loan->lender->company_name }}</div>
                    @endif
                    @if ($loan->lender && $loan->lender->phone)
                        <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-phone fa-sm me-1"></i>
                            {{ $loan->lender->phone }}</div>
                    @endif
                    @if ($loan->lender && $loan->lender->email)
                        <div class="fs-13 text-muted"><i class="fa-solid fa-envelope fa-sm me-1"></i>
                            {{ $loan->lender->email }}</div>
                    @endif
                </div>
            </div>

            <!-- Repayment Schedule -->
            <form action="{{ route('loans.reschedule', $loan) }}" method="POST" id="rescheduleForm" class="d-none">
                @csrf
                @method('PUT')
            </form>

            <div class="bp-card mb-4">
                <div class="bp-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="bp-card-title mb-0"><i class="fa-solid fa-calendar-days me-2 text-primary"></i>Repayment
                            Schedule</h5>
                        <span class="bp-badge bp-badge-info">{{ $loan->total_installments }} Installments &middot;
                            {{ ucfirst(str_replace('_', '-', $loan->frequency)) }}</span>
                    </div>
                    @if (in_array($loan->status, ['active', 'overdue']))
                        <div id="rescheduleActions" class="d-none d-flex gap-2 align-items-center">
                            <span class="fs-12 fw-700 me-2" id="rescheduleTotal"></span>
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-danger" id="rescheduleCancel"><i
                                    class="fa-solid fa-xmark me-1"></i> Cancel</button>
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-success" id="rescheduleSave"><i
                                    class="fa-solid fa-save me-1"></i> Save Schedule</button>
                        </div>
                    @endif
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Due Date</th>
                                    <th class="text-end">Amount ({{ currency_symbol() }})</th>
                                    <th class="text-end">Paid ({{ currency_symbol() }})</th>
                                    <th class="text-end">Remaining</th>
                                    <th>Status</th>
                                    @if (in_array($loan->status, ['active', 'overdue']))
                                        <th class="bp-col-action">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($loan->schedules as $idx => $schedule)
                                    @php $isEditable = $schedule->status !== 'paid'; @endphp
                                    <tr data-schedule-id="{{ $schedule->id }}"
                                        data-editable="{{ $isEditable ? '1' : '0' }}">
                                        <td class="{{ $schedule->status === 'paid' ? 'text-muted' : 'fw-600' }}">
                                            {{ $schedule->installment_number }}</td>
                                        <td
                                            class="{{ $schedule->status === 'overdue' ? 'text-danger fw-600' : ($schedule->status === 'paid' ? '' : 'fw-600') }}">
                                            <span class="bp-view-mode">{{ $schedule->due_date->format('d M Y') }}</span>
                                            @if ($isEditable)
                                                <input type="date"
                                                    class="bp-form-control bp-edit-mode d-none reschedule-date"
                                                    name="schedules[{{ $idx }}][due_date]"
                                                    value="{{ $schedule->due_date->format('Y-m-d') }}"
                                                    form="rescheduleForm">
                                                <input type="hidden" name="schedules[{{ $idx }}][id]"
                                                    value="{{ $schedule->id }}" form="rescheduleForm">
                                            @endif
                                        </td>
                                        <td class="text-end fw-700">
                                            <span class="bp-view-mode">{{ money($schedule->amount) }}</span>
                                            @if ($isEditable)
                                                <input type="number"
                                                    class="bp-form-control bp-edit-mode d-none text-end reschedule-amount"
                                                    name="schedules[{{ $idx }}][amount]"
                                                    value="{{ number_format($schedule->amount, 2, '.', '') }}"
                                                    min="0.01" step="0.01" form="rescheduleForm">
                                            @endif
                                        </td>
                                        <td
                                            class="text-end {{ $schedule->paid_amount > 0 ? 'text-success fw-600' : 'text-muted' }}">
                                            {{ $schedule->paid_amount > 0 ? currency_symbol() . ' ' . num($schedule->paid_amount) : '-' }}
                                        </td>
                                        <td class="text-end fw-600">
                                            @php $scheduleRemaining = $schedule->amount - $schedule->paid_amount; @endphp
                                            {{ $scheduleRemaining > 0 ? currency_symbol() . ' ' . num($scheduleRemaining) : '-' }}
                                        </td>
                                        <td>
                                            @if ($schedule->status === 'paid')
                                                <span class="bp-badge bp-badge-success"><i
                                                        class="fa-solid fa-check me-1"></i>Paid</span>
                                                @if ($schedule->paid_date)
                                                    <div class="fs-11 text-muted mt-1">
                                                        {{ $schedule->paid_date->format('d M Y') }}</div>
                                                @endif
                                            @elseif($schedule->status === 'partial')
                                                <span class="bp-badge bp-badge-warning"><i
                                                        class="fa-solid fa-clock me-1"></i>Partial</span>
                                            @elseif($schedule->status === 'overdue')
                                                <span class="bp-badge bp-badge-danger"><i
                                                        class="fa-solid fa-triangle-exclamation me-1"></i>Overdue</span>
                                            @else
                                                <span class="bp-badge bp-badge-info">Upcoming</span>
                                            @endif
                                        </td>
                                        @if (in_array($loan->status, ['active', 'overdue']))
                                            <td class="bp-col-action">
                                                @if (in_array($schedule->status, ['upcoming', 'partial', 'overdue']))
                                                    @bpCan('finance.edit')
                                                        <button type="button"
                                                            class="bp-btn bp-btn-sm bp-btn-primary bp-pay-btn bp-view-mode"
                                                            data-schedule-id="{{ $schedule->id }}"
                                                            data-number="{{ $schedule->installment_number }}"
                                                            data-amount="{{ $schedule->amount - $schedule->paid_amount }}"
                                                            data-due="{{ $schedule->due_date->format('d M Y') }}"
                                                            data-bs-toggle="modal" data-bs-target="#repaymentModal">
                                                            <i class="fa-solid fa-bangladeshi-taka-sign me-1"></i> Pay
                                                        </button>
                                                    @endbpCan
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Details & Summary -->
        <div class="col-xl-4">

            <!-- Loan Metadata -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2 text-primary"></i>Loan Details</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Loan Number</div>
                        <div class="bp-info-value fw-700">{{ $loan->loan_number }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Principal</div>
                        <div class="bp-info-value fw-800">{{ money($loan->principal_amount) }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Disbursed On</div>
                        <div class="bp-info-value">{{ $loan->disbursement_date->format('d M Y') }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Received Into</div>
                        <div class="bp-info-value fw-600">{{ $loan->paymentAccount->name ?? '-' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Installments</div>
                        <div class="bp-info-value fw-600">{{ $loan->total_installments }} x
                            {{ ucfirst(str_replace('_', '-', $loan->frequency)) }}</div>
                    </div>
                    @if ($loan->reference)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Reference</div>
                            <div class="bp-info-value">{{ $loan->reference }}</div>
                        </div>
                    @endif
                    @if ($loan->note)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Note</div>
                            <div class="bp-info-value">{{ $loan->note }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row bp-info-row-last">
                        <div class="bp-info-label bp-info-label-lg">Status</div>
                        <div class="bp-info-value">
                            @if ($loan->status === 'active')
                                <span class="bp-badge bp-badge-primary">Active</span>
                            @elseif($loan->status === 'completed')
                                <span class="bp-badge bp-badge-success">Completed</span>
                            @elseif($loan->status === 'overdue')
                                <span class="bp-badge bp-badge-danger">Overdue</span>
                            @elseif($loan->status === 'cancelled')
                                <span class="bp-badge bp-badge-dark">Cancelled</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-chart-pie me-2 text-success"></i>Financial Summary
                    </h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-cart-summary-row">
                        <span>Principal Amount</span>
                        <span class="fw-700">{{ money($loan->principal_amount) }}</span>
                    </div>
                    <div class="bp-cart-summary-row">
                        <span>Total Repaid ({{ $paidCount }})</span>
                        <span class="text-success fw-700">{{ money($loan->total_repaid) }}</span>
                    </div>
                    <div class="bp-cart-summary-row total">
                        <span>Remaining Balance</span>
                        <span class="text-danger fw-800">{{ money($loan->total_remaining) }}</span>
                    </div>
                    @if ($loan->next_due_date)
                        <div class="bp-cart-summary-row">
                            <span>Next Due Date</span>
                            <span class="fw-700">{{ $loan->next_due_date->format('d M Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Progress -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-chart-simple me-2 text-info"></i>Progress</h5>
                </div>
                <div class="bp-card-body text-center">
                    <div class="fw-800 fs-18 mb-2">{{ $loan->progress_percentage }}%</div>
                    <div class="bp-progress-mini" style="height: 8px;">
                        <div class="bp-progress-bar {{ $loan->status === 'completed' ? 'bp-progress-bar-success' : ($loan->status === 'overdue' ? 'bp-progress-bar-danger' : '') }}"
                            style="width: {{ $loan->progress_percentage }}%"></div>
                    </div>
                    <div class="fs-12 text-muted mt-2">{{ $paidCount }} of {{ $loan->total_installments }}
                        installments repaid</div>
                </div>
            </div>

        </div>
    </div>

    <!-- Repayment Modal -->
    @bpCan('finance.edit')
        @if (in_array($loan->status, ['active', 'overdue']))
            @php
                $nextSchedule = $loan->schedules->whereIn('status', ['upcoming', 'partial', 'overdue'])->first();
            @endphp
            <div class="modal fade" id="repaymentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-700"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Record
                                Repayment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('loans.repayment', $loan) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div
                                            class="d-flex flex-column align-items-center justify-content-center py-3 border rounded">
                                            <div class="fs-12 text-muted" id="modalInstLabel">
                                                @if ($nextSchedule)
                                                    Installment #{{ $nextSchedule->installment_number }} of
                                                    {{ $loan->total_installments }}
                                                @endif
                                            </div>
                                            <div class="fw-800 fs-18" id="modalInstAmount">
                                                @if ($nextSchedule)
                                                    {{ money($nextSchedule->amount - $nextSchedule->paid_amount) }}
                                                @endif
                                            </div>
                                            <div class="fs-12 text-danger" id="modalInstDue">
                                                @if ($nextSchedule)
                                                    Due: {{ $nextSchedule->due_date->format('d M Y') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="schedule_id" id="modalScheduleId"
                                        value="{{ $nextSchedule ? $nextSchedule->id : '' }}">
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Amount ({{ currency_symbol() }}) *</label>
                                        <input type="number" class="bp-form-control" name="amount" id="modalPayAmount"
                                            value="{{ $nextSchedule ? num_input($nextSchedule->amount - $nextSchedule->paid_amount) : '' }}"
                                            min="0.01" step="0.01" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Payment Account *</label>
                                        <select class="bp-form-select w-100" name="payment_account_id" required>
                                            <option value="">Select Payment Account</option>
                                            @foreach ($paymentAccounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="bp-form-label">Note</label>
                                        <textarea class="bp-form-control" name="note" rows="2" placeholder="Optional note"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                        class="fa-solid fa-xmark me-1"></i>Cancel</button>
                                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i>
                                    Record Repayment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endbpCan

    <!-- Cancel Loan Form (hidden) -->
    @bpCan('finance.edit')
        @if ($loan->status === 'active' && $loan->total_repaid == 0)
            <form id="cancelLoanForm" action="{{ route('loans.cancel', $loan) }}" method="POST" class="d-none">
                @csrf
            </form>
        @endif
    @endbpCan

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Pay button — populate modal
            $('.bp-pay-btn').on('click', function() {
                var scheduleId = $(this).data('schedule-id');
                var number = $(this).data('number');
                var amount = $(this).data('amount');
                var due = $(this).data('due');

                $('#modalScheduleId').val(scheduleId);
                $('#modalPayAmount').val(parseFloat(amount).toFixed(2));
                $('#modalInstLabel').text('Installment #' + number +
                    ' of {{ $loan->total_installments }}');
                $('#modalInstAmount').text('{{ currency_symbol() }} ' + window.fmtAmount(amount));
                $('#modalInstDue').text('Due: ' + due);
            });

            // Reschedule — toggle edit mode
            var principal = {{ (float) $loan->principal_amount }};
            var paidTotal = {{ (float) $loan->schedules->where('status', 'paid')->sum('amount') }};
            var expectedUnpaid = Math.round((principal - paidTotal) * 100) / 100;

            $('#rescheduleToggle').on('click', function() {
                $('.bp-edit-mode').removeClass('d-none');
                $('.bp-view-mode').addClass('d-none');
                $('.bp-col-action').addClass('d-none');
                $('#rescheduleActions').removeClass('d-none').addClass('d-flex');
                $(this).addClass('d-none');
                updateRescheduleTotal();
            });

            $('#rescheduleCancel').on('click', function() {
                $('.bp-edit-mode').addClass('d-none');
                $('.bp-view-mode').removeClass('d-none');
                $('.bp-col-action').removeClass('d-none');
                $('#rescheduleActions').addClass('d-none').removeClass('d-flex');
                $('#rescheduleToggle').removeClass('d-none');
                // Reset inputs to original values
                $('.reschedule-amount').each(function() {
                    $(this).val($(this).prop('defaultValue'));
                });
                $('.reschedule-date').each(function() {
                    $(this).val($(this).prop('defaultValue'));
                });
            });

            $('#rescheduleSave').on('click', function() {
                var total = 0;
                $('.reschedule-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                total = Math.round(total * 100) / 100;

                if (total !== expectedUnpaid) {
                    alert('Total of unpaid installments ({{ currency_symbol() }} ' + total.toFixed(2) +
                        ') must equal remaining principal ({{ currency_symbol() }} ' + expectedUnpaid
                        .toFixed(2) + ').');
                    return;
                }
                $('#rescheduleForm').submit();
            });

            $(document).on('input', '.reschedule-amount', function() {
                updateRescheduleTotal();
            });

            function updateRescheduleTotal() {
                var total = 0;
                $('.reschedule-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                total = Math.round(total * 100) / 100;
                var match = total === expectedUnpaid;
                var cls = match ? 'text-success' : 'text-danger';
                $('#rescheduleTotal').attr('class', 'fs-12 fw-700 me-2 ' + cls)
                    .text('Total: {{ currency_symbol() }} ' + window.fmtAmount(total) +
                        ' / {{ currency_symbol() }} ' + window.fmtAmount(expectedUnpaid));
            }
        });
    </script>
@endpush
