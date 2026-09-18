@extends('core::layouts.master')

@section('title', __('Attendance'))
@section('page-title', __('Attendance'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Attendance</span>
@endsection

@section('page-actions')
    <a href="{{ route('attendance.leave') }}" class="bp-btn bp-btn-warning">
        <i class="fa-solid fa-calendar-minus"></i> Leave Management
    </a>
    <a href="{{ route('attendance.report') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-chart-bar"></i> Monthly Report
    </a>
    @bpCan('hr.create')
        <a href="{{ route('attendance.create') }}" class="bp-btn bp-btn-success">
            <i class="fa-solid fa-clipboard-check"></i> Mark Attendance
        </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-5">
        <div>
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-user-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Present</div>
                    <div class="bp-stat-value">{{ $stats['present'] }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-user-xmark"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Absent</div>
                    <div class="bp-stat-value">{{ $stats['absent'] }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Late</div>
                    <div class="bp-stat-value">{{ $stats['late'] }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-calendar-minus"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">On Leave</div>
                    <div class="bp-stat-value">{{ $stats['on_leave'] }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-user-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Not Marked</div>
                    <div class="bp-stat-value">{{ $stats['not_marked'] }}</div>
                    <div class="fs-11 text-muted">of {{ $stats['total_employees'] }} employees</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search employee name, ID...">
                <input type="date" class="bp-form-control" name="date" value="{{ request('date', date('Y-m-d')) }}">
                <select class="bp-form-select d-none" name="branch_id">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}</option>
                    @endforeach
                </select>
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="not_marked" {{ request('status') == 'not_marked' ? 'selected' : '' }}>Not Marked
                    </option>
                    <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                    <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                    <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                    <option value="half_day" {{ request('status') == 'half_day' ? 'selected' : '' }}>Half Day</option>
                    <option value="on_leave" {{ request('status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Employee</x-core::table.column>
            <x-core::table.column>Department</x-core::table.column>
            <th class="d-none">Branch</th>
            <x-core::table.column>Check In</x-core::table.column>
            <x-core::table.column>Check Out</x-core::table.column>
            <x-core::table.column>Hours Worked</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Late By</x-core::table.column>
            <x-core::table.column>Note</x-core::table.column>
            <th>Actions</th>
        </x-core::table.header>

        <tbody>
            @forelse($roster as $row)
                @php
                    $initials = initials($row->name);
                    $statusMap = [
                        'present' => ['badge' => 'bp-badge-success', 'label' => 'Present'],
                        'absent' => ['badge' => 'bp-badge-danger', 'label' => 'Absent'],
                        'late' => ['badge' => 'bp-badge-warning', 'label' => 'Late'],
                        'half_day' => ['badge' => 'bp-badge-info', 'label' => 'Half Day'],
                        'on_leave' => ['badge' => 'bp-badge-secondary', 'label' => 'On Leave'],
                    ];
                    $statusInfo = $row->attendance_id
                        ? $statusMap[$row->attendance_status] ?? [
                                'badge' => 'bp-badge-dark',
                                'label' => ucfirst((string) $row->attendance_status),
                            ]
                        : ['badge' => 'bp-badge-dark', 'label' => 'Not Marked'];
                @endphp
                <tr class="{{ $row->attendance_id ? '' : 'bp-row-muted' }}">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bp-user-avatar bp-avatar-sm">{{ $initials }}</div>
                            <div>
                                <a href="{{ route('attendance.employee-ledger', $row->id) }}"
                                    class="fw-700 fs-13">{{ $row->name }}</a>
                                <div class="fs-11 text-muted">{{ $row->employee_id }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $row->department ?? '' }}</td>
                    <td class="d-none">{{ $row->branch->name ?? '' }}</td>
                    <td>{{ $row->check_in ? \Carbon\Carbon::parse($row->check_in)->format('h:i A') : '—' }}</td>
                    <td>{{ $row->check_out ? \Carbon\Carbon::parse($row->check_out)->format('h:i A') : '—' }}</td>
                    <td>
                        @if ($row->hours_worked > 0)
                            {{ floor($row->hours_worked) }}h
                            {{ round(($row->hours_worked - floor($row->hours_worked)) * 60) }}m
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="bp-badge {{ $statusInfo['badge'] }}">{{ $statusInfo['label'] }}</span></td>
                    <td>
                        @if ($row->late_minutes > 0)
                            <span class="text-danger fw-600">{{ $row->late_minutes }} min</span>
                        @endif
                    </td>
                    <td>{{ $row->attendance_note ?? '' }}</td>
                    <td>
                        <a href="{{ route('attendance.employee-ledger', $row->id) }}"
                            class="bp-btn bp-btn-sm bp-btn-outline" title="Attendance ledger">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i>Ledger
                        </a>
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="10" icon="fa-solid fa-clipboard-check" title="No employees found"
                    description="No active employees match the current filters." />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$roster" itemLabel="employees" />
        </x-slot:pagination>
    </x-core::table>

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
        });
    </script>
@endpush
