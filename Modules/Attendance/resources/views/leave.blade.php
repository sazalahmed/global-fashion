@extends('core::layouts.master')

@section('title', __('Leave Management'))
@section('page-title', __('Leave Management'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('attendance.index') }}">Attendance</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Leave Management</span>
@endsection

@section('page-actions')
    <a href="{{ route('attendance.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Attendance
    </a>
    @bpCan('hr.create')
        <a href="{{ route('attendance.leave.create') }}" class="bp-btn bp-btn-success">
            <i class="fa-solid fa-plus"></i> Apply Leave
        </a>
    @endbpCan
@endsection

@section('content')

    @php
        $totalLeaves = $leaves->total();
        $approvedCount = $leaves->getCollection()->where('status', 'approved')->count();
        $pendingCount = $leaves->getCollection()->where('status', 'pending')->count();
        $rejectedCount = $leaves->getCollection()->where('status', 'rejected')->count();
    @endphp

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-file-lines"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Leave Applications</div>
                    <div class="bp-stat-value">{{ $totalLeaves }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Approved</div>
                    <div class="bp-stat-value">{{ $approvedCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Pending</div>
                    <div class="bp-stat-value">{{ $pendingCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Rejected</div>
                    <div class="bp-stat-value">{{ $rejectedCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search employee name, ID...">
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select class="bp-form-select" name="leave_type">
                    <option value="">All Leave Types</option>
                    <option value="casual" {{ request('leave_type') == 'casual' ? 'selected' : '' }}>Casual Leave</option>
                    <option value="sick" {{ request('leave_type') == 'sick' ? 'selected' : '' }}>Sick Leave</option>
                    <option value="annual" {{ request('leave_type') == 'annual' ? 'selected' : '' }}>Annual Leave</option>
                    <option value="maternity" {{ request('leave_type') == 'maternity' ? 'selected' : '' }}>Maternity Leave
                    </option>
                    <option value="paternity" {{ request('leave_type') == 'paternity' ? 'selected' : '' }}>Paternity Leave
                    </option>
                    <option value="unpaid" {{ request('leave_type') == 'unpaid' ? 'selected' : '' }}>Unpaid Leave</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Employee</x-core::table.column>
            <x-core::table.column>Leave Type</x-core::table.column>
            <x-core::table.column>From</x-core::table.column>
            <x-core::table.column>To</x-core::table.column>
            <x-core::table.column align="center">Days</x-core::table.column>
            <x-core::table.column>Reason</x-core::table.column>
            <x-core::table.column>Applied On</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($leaves as $leave)
                @php
                    $emp = $leave->employee;
                    $initials = $emp
                        ? initials($emp->name)
                        : '??';
                    $leaveTypeMap = [
                        'casual' => ['badge' => 'bp-badge-info', 'label' => 'Casual Leave'],
                        'sick' => ['badge' => 'bp-badge-danger', 'label' => 'Sick Leave'],
                        'annual' => ['badge' => 'bp-badge-primary', 'label' => 'Annual Leave'],
                        'maternity' => ['badge' => 'bp-badge-warning', 'label' => 'Maternity Leave'],
                        'paternity' => ['badge' => 'bp-badge-secondary', 'label' => 'Paternity Leave'],
                        'unpaid' => ['badge' => 'bp-badge-dark', 'label' => 'Unpaid Leave'],
                    ];
                    $typeInfo = $leaveTypeMap[$leave->leave_type] ?? [
                        'badge' => 'bp-badge-dark',
                        'label' => ucfirst($leave->leave_type),
                    ];
                    $statusMap = [
                        'pending' => ['badge' => 'bp-badge-warning', 'label' => 'Pending'],
                        'approved' => ['badge' => 'bp-badge-success', 'label' => 'Approved'],
                        'rejected' => ['badge' => 'bp-badge-danger', 'label' => 'Rejected'],
                    ];
                    $statusInfo = $statusMap[$leave->status] ?? [
                        'badge' => 'bp-badge-dark',
                        'label' => ucfirst($leave->status),
                    ];
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bp-user-avatar bp-avatar-sm">{{ $initials }}</div>
                            <div>
                                <div class="fw-700 fs-13">{{ $emp->name ?? 'N/A' }}</div>
                                <div class="fs-11 text-muted">{{ $emp->employee_id ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="bp-badge {{ $typeInfo['badge'] }}">{{ $typeInfo['label'] }}</span></td>
                    <td>{{ $leave->start_date->format('d M Y') }}</td>
                    <td>{{ $leave->end_date->format('d M Y') }}</td>
                    <td class="text-center fw-700">{{ $leave->total_days }}</td>
                    <td>{{ $leave->reason ?? '' }}</td>
                    <td>{{ $leave->created_at->format('d M Y') }}</td>
                    <td><span class="bp-badge {{ $statusInfo['badge'] }}">{{ $statusInfo['label'] }}</span></td>
                    <td>
                        @bpCanAny('hr.view', 'hr.edit')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                        class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if ($leave->status === 'pending')
                                        @bpCan('hr.edit')
                                            <li>
                                                <form action="{{ route('attendance.leave.approve', $leave) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success"
                                                        onclick="return confirm('Are you sure you want to approve this leave?')">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger leave-reject-btn" href="#"
                                                    data-id="{{ $leave->id }}" data-name="{{ $emp->name ?? 'N/A' }}">
                                                    <i class="fa-solid fa-xmark"></i> Reject
                                                </a>
                                            </li>
                                        @endbpCan
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                    @endif
                                    @if ($leave->rejection_reason)
                                        @bpCan('hr.view')
                                            <li><a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="alert('Rejection Reason:\n\n{{ addslashes($leave->rejection_reason) }}')"><i
                                                        class="fa-solid fa-eye"></i> View Reason</a></li>
                                        @endbpCan
                                    @endif
                                </ul>
                            </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="9" icon="fa-solid fa-calendar-minus" title="No leave applications found" />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$leaves" itemLabel="records" />
        </x-slot:pagination>
    </x-core::table>

    <!-- Reject Reason Form (hidden, submitted via JS) -->
    <form id="rejectForm" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="rejection_reason" id="rejectionReason">
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            // Filter reset
            $('.bp-filter-reset').on('click', function() {
                // Clear all filters — go to the clean path (no query string).
                window.location.href = window.location.pathname;
            });

            // Leave reject action
            $('.leave-reject-btn').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var name = $(this).data('name');
                var reason = prompt('Rejection reason for ' + name + '\'s leave (optional):');

                if (reason !== null) {
                    var $form = $('#rejectForm');
                    $form.attr('action', '/attendance/leave/' + id + '/reject');
                    $('#rejectionReason').val(reason);
                    $form.submit();
                }
            });
        });
    </script>
@endpush
