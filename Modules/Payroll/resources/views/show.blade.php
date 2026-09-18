@extends('core::layouts.master')

@section('title', __('Payroll Detail'))
@section('page-title', __('Payroll Detail'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>HR</span>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('payroll.index') }}">Payroll</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $payroll->payroll_number }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('payroll.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Payroll
    </a>
@endsection

@section('content')

    <!-- Payroll Summary Header -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h4 class="fw-800 mb-1">{{ \Carbon\Carbon::parse($payroll->month . '-01')->format('F Y') }} Payroll</h4>
                    <div class="mb-1">
                        @if ($payroll->status === 'draft')
                            <span class="bp-badge bp-badge-warning">Draft</span>
                        @elseif($payroll->status === 'approved')
                            <span class="bp-badge bp-badge-primary">Approved</span>
                        @elseif($payroll->status === 'paid')
                            <span class="bp-badge bp-badge-success">Paid</span>
                        @elseif($payroll->status === 'cancelled')
                            <span class="bp-badge bp-badge-danger">Cancelled</span>
                        @endif
                        <span class="fs-12 text-muted ms-2">{{ $payroll->payroll_number }}</span>
                    </div>
                    <div class="fs-12 text-muted">
                        Branch: {{ $payroll->branch?->name ?? 'All Branches' }}
                        | Created by: {{ $payroll->creator?->name ?? '--' }}
                        @if ($payroll->approver)
                            | Approved by: {{ $payroll->approver->name }}
                        @endif
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-sm-3">
                            <div class="fs-11 text-muted text-uppercase fw-700">Employees</div>
                            <div class="fw-800 fs-5">{{ $payroll->total_employees }}</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="fs-11 text-muted text-uppercase fw-700">Gross Pay</div>
                            <div class="fw-800 fs-5">{{ currency_symbol() }} {{ number_format($payroll->total_gross, 0) }}
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="fs-11 text-muted text-uppercase fw-700">Deductions</div>
                            <div class="fw-800 fs-5 bp-text-danger">{{ currency_symbol() }}
                                {{ number_format($payroll->total_deductions, 0) }}</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="fs-11 text-muted text-uppercase fw-700">Net Pay</div>
                            <div class="fw-800 fs-5 bp-text-success">{{ currency_symbol() }}
                                {{ number_format($payroll->total_net, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php $anyApprovedUnpaid = $payroll->items->where('status', 'approved')->where('payment_status', '!=', 'paid')->count(); @endphp

    <!-- Editable Employee Payroll Grid -->
    <div class="bp-card">
        <div class="bp-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="bp-card-title"><i class="fa-solid fa-users me-2"></i>Employee Salaries</h5>
            @bpCan('hr.edit')
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if ($payroll->items->where('status', 'pending')->count())
                        <form action="{{ route('payroll.approve', $payroll) }}" method="POST"
                            onsubmit="return confirm('Approve all pending salaries?');">
                            @csrf
                            <button type="submit" class="bp-btn bp-btn-sm bp-btn-success"><i
                                    class="fa-solid fa-check-double me-1"></i> Approve All</button>
                        </form>
                    @endif
                    @if ($anyApprovedUnpaid)
                        <form action="{{ route('payroll.mark-paid', $payroll) }}" method="POST"
                            class="d-flex gap-2 align-items-center">
                            @csrf
                            <select name="payment_account_id" class="bp-form-select bp-form-select-sm" required>
                                <option value="">Payment Account</option>
                                <x-payment::account-options />
                            </select>
                            <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary"><i
                                    class="fa-solid fa-bangladeshi-taka-sign me-1"></i> Pay Approved</button>
                        </form>
                    @endif
                </div>
            @endbpCan
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Basic</th>
                            <th>Overtime</th>
                            <th>Bonus</th>
                            <th>Commission</th>
                            <th>Advance Ded.</th>
                            <th>Absent Ded.</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payroll->items as $item)
                            @php
                                $locked = $item->isApproved() || $item->payment_status === 'paid';
                                $fid = 'itemform-' . $item->id;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-700 fs-13">{{ $item->employee->name ?? '--' }}</div>
                                    <div class="fs-11 text-muted">{{ $item->employee->employee_id ?? '' }}</div>
                                    @if ($item->absent_days)
                                        <div class="fs-11 text-danger">Absent: {{ $item->absent_days }} day(s)</div>
                                    @endif
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" form="{{ $fid }}"
                                        name="basic_salary" value="{{ num_input($item->basic_salary) }}"
                                        class="bp-form-control bp-form-control-sm fw-600" style="max-width:110px"
                                        {{ $locked ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" form="{{ $fid }}"
                                        name="overtime" value="{{ num_input($item->overtime) }}"
                                        class="bp-form-control bp-form-control-sm" style="max-width:110px"
                                        {{ $locked ? 'disabled' : '' }}>
                                    @if ($item->overtime_hours > 0)
                                        <div class="fs-11 text-muted">{{ number_format($item->overtime_hours, 1) }} hrs
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" form="{{ $fid }}"
                                        name="bonus" value="{{ num_input($item->bonus) }}"
                                        class="bp-form-control bp-form-control-sm" style="max-width:110px"
                                        {{ $locked ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" form="{{ $fid }}"
                                        name="commission" value="{{ num_input($item->commission) }}"
                                        class="bp-form-control bp-form-control-sm" style="max-width:110px"
                                        {{ $locked ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" form="{{ $fid }}"
                                        name="advance_deduction" value="{{ num_input($item->advance_deduction) }}"
                                        class="bp-form-control bp-form-control-sm" style="max-width:110px"
                                        {{ $locked ? 'disabled' : '' }}>
                                    <div class="fs-11 text-muted">Bal:
                                        {{ number_format($item->employee->advance_balance ?? 0, 0) }}</div>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" form="{{ $fid }}"
                                        name="absent_deduction" value="{{ num_input($item->absent_deduction) }}"
                                        class="bp-form-control bp-form-control-sm" style="max-width:110px"
                                        {{ $locked ? 'disabled' : '' }}>
                                </td>
                                <td class="fw-800 bp-text-success">{{ currency_symbol() }}
                                    {{ number_format($item->net_salary, 0) }}</td>
                                <td>
                                    @if ($item->payment_status === 'paid')
                                        <span class="bp-badge bp-badge-success">Paid</span>
                                    @elseif($item->isApproved())
                                        <span class="bp-badge bp-badge-primary">Approved</span>
                                    @else
                                        <span class="bp-badge bp-badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        @bpCan('hr.edit')
                                            @unless ($locked)
                                                <form id="{{ $fid }}"
                                                    action="{{ route('payroll.items.update', $item) }}" method="POST">
                                                    @csrf @method('PUT')
                                                    <button type="submit" class="bp-btn bp-btn-sm bp-btn-outline"
                                                        title="Save"><i class="fa-solid fa-floppy-disk"></i></button>
                                                </form>
                                                <form action="{{ route('payroll.items.approve', $item) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="bp-btn bp-btn-sm bp-btn-success"
                                                        title="Approve"><i class="fa-solid fa-check"></i></button>
                                                </form>
                                            @elseif($item->isApproved() && $item->payment_status !== 'paid')
                                                <button type="button" class="bp-btn bp-btn-sm bp-btn-primary pay-item-btn"
                                                    title="Pay" data-pay-url="{{ route('payroll.items.pay', $item) }}"
                                                    data-employee-name="{{ $item->employee->name ?? 'employee' }}"
                                                    data-net-pay="{{ money($item->net_salary) }}">
                                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                                </button>
                                                <form action="{{ route('payroll.items.unapprove', $item) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="bp-btn bp-btn-sm bp-btn-warning"
                                                        title="Unapprove"><i class="fa-solid fa-rotate-left"></i></button>
                                                </form>
                                            @endunless
                                            @if ($item->payment_status === 'paid')
                                                {{-- Wrong amount paid? Undo reverses this line's journal
                           entry + advance recovery so it can be edited and re-paid. --}}
                                                <form action="{{ route('payroll.items.undo-pay', $item) }}" method="POST"
                                                    class="undo-pay-form"
                                                    data-employee-name="{{ $item->employee->name ?? 'employee' }}"
                                                    data-net-pay="{{ money($item->net_salary) }}">
                                                    @csrf
                                                    <button type="submit" class="bp-btn bp-btn-sm bp-btn-danger"
                                                        title="Undo Payment"><i class="fa-solid fa-rotate-left"></i></button>
                                                </form>
                                            @endif
                                        @endbpCan
                                        <a href="{{ route('payroll.items.payslip', $item) }}" target="_blank"
                                            rel="noopener" class="bp-btn bp-btn-sm bp-btn-outline" title="Payslip"><i
                                                class="fa-solid fa-file-invoice"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="10" icon="fa-solid fa-users"
                                title="No payroll items found" />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bp-table-total-row">
                            <td class="fw-700">Totals</td>
                            <td colspan="6" class="text-end text-muted fs-12">Gross {{ currency_symbol() }}
                                {{ number_format($payroll->total_gross, 0) }} &nbsp;•&nbsp; Deductions
                                {{ currency_symbol() }} {{ number_format($payroll->total_deductions, 0) }}</td>
                            <td class="fw-800 bp-text-success">{{ currency_symbol() }}
                                {{ number_format($payroll->total_net, 0) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @bpCan('hr.edit')
        <!-- Pay Salary Modal (shared — the row button fills employee + action URL) -->
        <div class="modal fade" id="payItemModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" id="payItemForm" action="">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Pay Salary — <span
                                    id="payItemEmployee"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="bp-info-row bp-info-row-last mb-3">
                                <div class="bp-info-label">Net Pay</div>
                                <div class="bp-info-value fw-800 bp-text-success" id="payItemNet"></div>
                            </div>
                            <label class="bp-form-label">Payment Account *</label>
                            <select name="payment_account_id" class="bp-form-select w-100" required>
                                <option value="">Select Account</option>
                                <x-payment::account-options />
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Pay
                                Salary</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endbpCan

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            $(document).on('submit', '.undo-pay-form', function(e) {
                var name = $(this).data('employee-name');
                var net = $(this).data('net-pay');
                if (!window.confirm('Undo the salary payment of ' + net + ' for ' + name +
                        '? The journal entry and any advance recovery will be reversed, and the line can then be edited and paid again.'
                    )) {
                    e.preventDefault();
                }
            });

            var modalEl = document.getElementById('payItemModal');
            if (!modalEl) return; // user lacks hr.edit — no modal rendered
            var payModal = new bootstrap.Modal(modalEl);
            $(document).on('click', '.pay-item-btn', function() {
                $('#payItemForm').attr('action', $(this).data('pay-url'));
                $('#payItemEmployee').text($(this).data('employee-name'));
                $('#payItemNet').text($(this).data('net-pay'));
                payModal.show();
            });
        });
    </script>
@endpush
