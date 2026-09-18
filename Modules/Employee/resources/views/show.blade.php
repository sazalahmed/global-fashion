@extends('core::layouts.master')

@section('title', $employee->name . ' - Employee Profile')
@section('page-title', __('Employee Profile'))

@section('breadcrumb')
    <span class="sep">
        <i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('employee.index') }}">Employees</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $employee->name }}</span>
@endsection

@section('page-actions')
    @bpCan('hr.edit')
        <a href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'employee', 'party_id' => $employee->id, 'amount' => (int) $employee->salary]) }}"
            class="bp-btn bp-btn-success"><i class="fa-solid fa-bangladeshi-taka-sign me-1"></i>Pay Salary</a>
        <a href="{{ route('employee.edit', $employee) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-pen"></i> Edit
            Employee</a>
    @endbpCan
@endsection

@section('content')

    <div class="row g-4">
        <!-- Left Column: Employee Info -->
        <div class="col-lg-4">
            <div class="bp-card">
                <div class="bp-card-body text-center employee_view">
                    <div class="bp-user-avatar bp-avatar-xxl mx-auto mb-3">
                        @if ($employee->photo)
                            <img src="{{ upload_url($employee->photo) }}" alt="{{ $employee->name }}">
                        @else
                            {{ strtoupper(substr($employee->name, 0, 1)) }}{{ strtoupper(substr(strstr($employee->name, ' ') ?: '', 1, 1)) }}
                        @endif
                    </div>
                    <h4 class="fw-800 mb-1">{{ $employee->name }}</h4>
                    <div class="fs-13 text-muted mb-2">{{ $employee->employee_id }}</div>
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        @if ($employee->department)
                            <span class="bp-badge bp-badge-primary">{{ $employee->department }}</span>
                        @endif
                        @if ($employee->status === 'active')
                            <span class="bp-badge bp-badge-success">Active</span>
                        @elseif($employee->status === 'on_leave')
                            <span class="bp-badge bp-badge-warning">On Leave</span>
                        @elseif($employee->status === 'inactive')
                            <span class="bp-badge bp-badge-secondary">Inactive</span>
                        @elseif($employee->status === 'terminated')
                            <span class="bp-badge bp-badge-danger">Terminated</span>
                        @endif
                    </div>
                </div>
                <div class="bp-card-body border-top">
                    <div class="mb-3">
                        <div class="fs-12 text-muted fw-600 mb-1">Designation</div>
                        <div class="fw-700 fs-13">{{ $employee->designation ?? '--' }}</div>
                    </div>
                    <div class="mb-3 d-none">
                        <div class="fs-12 text-muted fw-600 mb-1">Branch</div>
                        <div class="fw-700 fs-13">{{ $employee->branch?->name ?? '--' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="fs-12 text-muted fw-600 mb-1">Phone</div>
                        <div class="fw-700 fs-13">
                            @if ($employee->phone)
                                <i class="fa-solid fa-phone me-1 text-muted"></i>{{ $employee->phone }}
                            @else
                                --
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="fs-12 text-muted fw-600 mb-1">Email</div>
                        <div class="fw-700 fs-13">
                            @if ($employee->email)
                                <i class="fa-solid fa-envelope me-1 text-muted"></i>{{ $employee->email }}
                            @else
                                --
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="fs-12 text-muted fw-600 mb-1">NID</div>
                        <div class="fw-700 fs-13">{{ $employee->nid ?? '--' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="fs-12 text-muted fw-600 mb-1">Address</div>
                        <div class="fw-700 fs-13">{{ $employee->address ?? '--' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="fs-12 text-muted fw-600 mb-1">Joining Date</div>
                        <div class="fw-700 fs-13">
                            {{ $employee->joining_date ? $employee->joining_date->format('d M Y') : '--' }}</div>
                    </div>
                    @if ($employee->leaving_date)
                        <div class="mb-3">
                            <div class="fs-12 text-muted fw-600 mb-1">Leaving Date</div>
                            <div class="fw-700 fs-13">{{ $employee->leaving_date->format('d M Y') }}</div>
                        </div>
                    @endif
                    <div>
                        <div class="fs-12 text-muted fw-600 mb-1">Monthly Salary</div>
                        <div class="fw-800 fs-13">{{ currency_symbol() }} {{ number_format($employee->salary, 0) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Stats + Tabs -->
        <div class="col-lg-8">
            <!-- Stats Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-sm-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Monthly Salary</div>
                            <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($employee->salary, 0) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Advance Balance</div>
                            <div class="bp-stat-value">{{ currency_symbol() }}
                                {{ number_format($employee->advance_balance, 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-clock"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Joining Date</div>
                            <div class="bp-stat-value">
                                {{ $employee->joining_date ? $employee->joining_date->format('d M Y') : '--' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Employee Details</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="fs-12 text-muted fw-600 mb-1">Created By</div>
                            <div class="fw-700 fs-13">{{ $employee->creator?->name ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fs-12 text-muted fw-600 mb-1">Created At</div>
                            <div class="fw-700 fs-13">{{ $employee->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fs-12 text-muted fw-600 mb-1">Last Updated</div>
                            <div class="fw-700 fs-13">{{ $employee->updated_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @if ($employee->user)
                            <div class="col-md-6">
                                <div class="fs-12 text-muted fw-600 mb-1">Linked User Account</div>
                                <div class="fw-700 fs-13">{{ $employee->user->name }} ({{ $employee->user->email }})</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Advances --}}
            <div class="bp-card mt-4">
                <div class="bp-card-header d-flex justify-content-between align-items-center">
                    <h5 class="bp-card-title"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Salary Advances</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('employee.advance-ledger', $employee) }}"
                            class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-book me-1"></i> Ledger</a>
                        @bpCan('hr.create')
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" data-bs-toggle="modal"
                                data-bs-target="#giveAdvanceModal"><i class="fa-solid fa-plus me-1"></i> Give Advance</button>
                        @endbpCan
                        @bpCan('hr.edit')
                            @if ($employee->advance_balance > 0)
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-success" data-bs-toggle="modal"
                                    data-bs-target="#recoverAdvanceModal"><i class="fa-solid fa-rotate-left me-1"></i> Record
                                    Recovery</button>
                            @endif
                        @endbpCan
                    </div>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row bp-info-row-last">
                        <div class="bp-info-label">Outstanding Advance</div>
                        <div
                            class="bp-info-value fw-800 {{ $employee->advance_balance > 0 ? 'text-danger' : 'text-success' }}">
                            {{ currency_symbol() }} {{ number_format($employee->advance_balance, 0) }}</div>
                    </div>
                </div>
            </div>

            {{-- Salary Increments --}}
            <div class="bp-card mt-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-arrow-trend-up me-2"></i>Salary Increments</h5>
                    @bpCan('hr.create')
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" data-bs-toggle="modal"
                            data-bs-target="#incrementModal"><i class="fa-solid fa-plus me-1"></i> Increment Salary</button>
                    @endbpCan
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Effective</th>
                                    <th>Previous</th>
                                    <th>New</th>
                                    <th>Change</th>
                                    <th>By</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($increments as $inc)
                                    <tr>
                                        <td>
                                            <div class="fw-600">{{ $inc->applied_at?->format('d M Y') }}</div>
                                            @if ($inc->note)
                                                <div class="fs-11 text-muted">{{ $inc->note }}</div>
                                            @endif
                                        </td>
                                        <td>{{ currency_symbol() }} {{ number_format($inc->previous_salary, 0) }}</td>
                                        <td class="fw-700">{{ currency_symbol() }}
                                            {{ number_format($inc->new_salary, 0) }}</td>
                                        <td>
                                            <span class="bp-badge bp-badge-success">+{{ currency_symbol() }}
                                                {{ number_format($inc->new_salary - $inc->previous_salary, 0) }}
                                                @if ($inc->increment_type === 'percentage')
                                                    ({{ rtrim(rtrim(number_format($inc->increment_value, 3, '.', ''), '0'), '.') }}%)
                                                @endif
                                            </span>
                                        </td>
                                        <td class="fs-12 text-muted">{{ $inc->incrementedBy?->name ?? '--' }}</td>
                                        <td class="text-end">
                                            @bpCanAny('hr.edit', 'hr.delete')
                                                <div class="dropdown">
                                                    <button class="bp-btn bp-btn-sm bp-btn-outline"
                                                        data-bs-toggle="dropdown"><i
                                                            class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @bpCan('hr.edit')
                                                            <li>
                                                                <button type="button" class="dropdown-item btn-edit-increment"
                                                                    data-id="{{ $inc->id }}"
                                                                    data-applied="{{ $inc->applied_at?->format('Y-m-d') }}"
                                                                    data-type="{{ $inc->increment_type }}"
                                                                    data-value="{{ $inc->increment_value }}"
                                                                    data-note="{{ $inc->note }}" data-bs-toggle="modal"
                                                                    data-bs-target="#editIncrementModal"><i
                                                                        class="fa-solid fa-pen me-2"></i> Edit</button>
                                                            </li>
                                                        @endbpCan
                                                        @bpCan('hr.delete')
                                                            <li>
                                                                <form
                                                                    action="{{ route('employee.salary-increment.destroy', $inc->id) }}"
                                                                    method="POST">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit"
                                                                        class="dropdown-item text-danger delete-confirm"
                                                                        data-name="increment dated {{ $inc->applied_at?->format('d M Y') }}"><i
                                                                            class="fa-solid fa-trash me-2"></i> Delete</button>
                                                                </form>
                                                            </li>
                                                        @endbpCan
                                                    </ul>
                                                </div>
                                            @endbpCanAny
                                        </td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="6" icon="fa-solid fa-arrow-trend-up"
                                        title="No salary increments recorded yet" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($increments->isNotEmpty())
                    <div class="bp-card-footer fs-11 text-muted"><i class="fa-solid fa-circle-info me-1"></i>Editing or
                        deleting the most recent increment adjusts the employee's current salary.</div>
                @endif
            </div>

        </div>
    </div>

    {{-- ── Increment Salary Modal ── --}}
    @bpCan('hr.create')
        <div class="modal fade" id="incrementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('employee.salary-increment.store', $employee->id) }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fa-solid fa-arrow-trend-up me-2"></i>Increment Salary</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info py-2 fs-12 mb-3">Current salary: <strong>{{ currency_symbol() }}
                                    {{ number_format($employee->salary, 0) }}</strong></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="bp-form-label">Increment Type *</label>
                                    <select class="bp-form-select w-100" name="increment_type" id="incType" required>
                                        <option value="amount">Fixed Amount ({{ currency_symbol() }})</option>
                                        <option value="percentage">Percentage (%)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="bp-form-label">Value *</label>
                                    <input type="number" step="0.01" min="0.01" class="bp-form-control"
                                        name="increment_value" id="incValue" required placeholder="e.g. 2000 or 10">
                                </div>
                                <div class="col-12">
                                    <label class="bp-form-label">Effective Date</label>
                                    <input type="date" class="bp-form-control" name="applied_at"
                                        value="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-12">
                                    <label class="bp-form-label">Note</label>
                                    <input type="text" class="bp-form-control" name="note" maxlength="255"
                                        placeholder="Reason (optional)">
                                </div>
                                <div class="col-12">
                                    <div class="fs-13">New salary preview: <strong id="incPreview"
                                            class="text-primary">—</strong></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                    class="fa-solid fa-xmark me-1"></i>Cancel</button>
                            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Apply
                                Increment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endbpCan

    {{-- ── Edit Increment Modal ── --}}
    @bpCan('hr.edit')
        <div class="modal fade" id="editIncrementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="editIncrementForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Increment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="bp-form-label">Effective Date *</label>
                                    <input type="date" class="bp-form-control" name="applied_at" id="editIncApplied"
                                        required>
                                </div>
                                <div class="col-lg-6">
                                    <label class="bp-form-label">Increment Type *</label>
                                    <select class="bp-form-select w-100" name="increment_type" id="editIncType" required>
                                        <option value="amount">Fixed Amount ({{ currency_symbol() }})</option>
                                        <option value="percentage">Percentage (%)</option>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label class="bp-form-label">Value *</label>
                                    <input type="number" step="0.01" min="0.01" class="bp-form-control"
                                        name="increment_value" id="editIncValue" required>
                                </div>
                                <div class="col-lg-6">
                                    <label class="bp-form-label">Note</label>
                                    <input type="text" class="bp-form-control" name="note" id="editIncNote"
                                        maxlength="255">
                                </div>
                            </div>
                            <div class="fs-11 text-muted mt-2"><i class="fa-solid fa-circle-info me-1"></i>The previous salary
                                stays fixed; the new salary is recalculated from it.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                    class="fa-solid fa-xmark me-1"></i>Cancel</button>
                            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i>
                                Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endbpCan

    {{-- Give Advance Modal --}}
    @bpCan('hr.create')
        <div class="modal fade" id="giveAdvanceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('employee.advance.store', $employee) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Give Advance —
                                {{ $employee->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @include('employee::_advance-fields')
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-check me-1"></i> Give
                                Advance</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endbpCan

    {{-- Record Recovery Modal --}}
    @bpCan('hr.edit')
        @if ($employee->advance_balance > 0)
            <div class="modal fade" id="recoverAdvanceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('employee.advance-recovery.store', $employee) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fa-solid fa-rotate-left me-2"></i>Record Recovery —
                                    {{ $employee->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="bp-info-row bp-info-row-last mb-2">
                                    <div class="bp-info-label">Outstanding Advance</div>
                                    <div class="bp-info-value fw-800 text-danger">{{ currency_symbol() }}
                                        {{ number_format($employee->advance_balance, 0) }}</div>
                                </div>
                                @include('employee::_advance-fields', [
                                    'maxAmount' => $employee->advance_balance,
                                ])
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i>
                                    Record Recovery</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endbpCan

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            var currentSalary = {{ (float) $employee->salary }};

            function computeNew(type, value) {
                value = parseFloat(value) || 0;
                var delta = type === 'percentage' ? Math.round(currentSalary * value / 100) : value;
                return currentSalary + delta;
            }

            function refreshPreview() {
                var v = $('#incValue').val();
                $('#incPreview').text(v ? '{{ currency_symbol() }} ' + computeNew($('#incType').val(), v)
                    .toLocaleString() : '—');
            }
            $('#incType, #incValue').on('input change', refreshPreview);

            var editUrl = '{{ route('employee.salary-increment.update', '__ID__') }}';
            $(document).on('click', '.btn-edit-increment', function() {
                var $b = $(this);
                $('#editIncrementForm').attr('action', editUrl.replace('__ID__', $b.data('id')));
                $('#editIncApplied').val($b.data('applied'));
                $('#editIncType').val($b.data('type'));
                $('#editIncValue').val($b.data('value'));
                $('#editIncNote').val($b.data('note') || '');
            });
        });
    </script>
@endpush
