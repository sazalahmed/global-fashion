@extends('core::layouts.master')

@section('title', __('Monthly Attendance Report'))
@section('page-title', __('Monthly Attendance Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('attendance.index') }}">Attendance</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Monthly Report</span>
@endsection

@section('page-actions')
    <button class="bp-btn bp-btn-success" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Print
    </button>
    <a href="{{ route('attendance.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Attendance
    </a>
@endsection

@section('content')

    @php
        $totalPresent = $report->sum('present');
        $totalAbsent = $report->sum('absent');
        $totalLate = $report->sum('late');
        $totalHalfDay = $report->sum('half_day');
        $totalOnLeave = $report->sum('on_leave');
        $totalHours = $report->sum('total_hours');
        $workingDays = $report->first()['working_days'] ?? 0;
        $totalRecords = $totalPresent + $totalAbsent + $totalLate + $totalHalfDay + $totalOnLeave;
        $avgAttendance =
            $totalRecords > 0 ? round((($totalPresent + $totalLate + $totalHalfDay) / max($totalRecords, 1)) * 100) : 0;
    @endphp

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Working Days</div>
                    <div class="bp-stat-value">{{ $workingDays }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-chart-pie"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Average Attendance %</div>
                    <div class="bp-stat-value">{{ $avgAttendance }}%</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Late</div>
                    <div class="bp-stat-value">{{ $totalLate }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-calendar-minus"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Leave</div>
                    <div class="bp-stat-value">{{ $totalOnLeave }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Report Table -->
    <x-core::table>
        <x-slot:filters>
            {{-- Controls sit directly in bp-filter-bar: it is already a flex row, so
           an extra wrapper made them one child and bunched them to the left. --}}
            <form class="bp-filter-bar" method="GET" action="{{ route('attendance.report') }}">
                <input type="month" class="bp-form-control" name="month" value="{{ $month }}">
                <select class="bp-form-select d-none" name="branch_id">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary" title="Apply Filters"><i
                        class="fa-solid fa-filter"></i></button>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-danger bp-filter-reset" title="Reset Filters">
                    <i class="fa-solid fa-rotate"></i>
                </button>
            </form>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Employee</x-core::table.column>
            <x-core::table.column>Department</x-core::table.column>
            <x-core::table.column align="center">Present</x-core::table.column>
            <x-core::table.column align="center">Absent</x-core::table.column>
            <x-core::table.column align="center">Late</x-core::table.column>
            <x-core::table.column align="center">Half Day</x-core::table.column>
            <x-core::table.column align="center">Leave</x-core::table.column>
            <x-core::table.column>Working Hours</x-core::table.column>
            <x-core::table.column>Attendance %</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($report as $row)
                @php
                    $emp = $row['employee'];
                    $initials = initials($emp->name);
                    $totalMarked =
                        $row['present'] + $row['absent'] + $row['late'] + $row['half_day'] + $row['on_leave'];
                    $attendanceRate =
                        $totalMarked > 0
                            ? round((($row['present'] + $row['late'] + $row['half_day']) / $totalMarked) * 100)
                            : 0;
                    $rateBadge =
                        $attendanceRate >= 90
                            ? 'bp-badge-success'
                            : ($attendanceRate >= 75
                                ? 'bp-badge-warning'
                                : 'bp-badge-danger');
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bp-user-avatar bp-avatar-sm">{{ $initials }}</div>
                            <div>
                                <div class="fw-700 fs-13">{{ $emp->name }}</div>
                                <div class="fs-11 text-muted">{{ $emp->employee_id }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $emp->department ?? '' }}</td>
                    <td class="text-center fw-700 text-success">{{ $row['present'] }}</td>
                    <td class="text-center {{ $row['absent'] > 0 ? 'text-danger' : '' }}">{{ $row['absent'] }}</td>
                    <td class="text-center {{ $row['late'] > 0 ? 'text-warning' : '' }}">{{ $row['late'] }}</td>
                    <td class="text-center">{{ $row['half_day'] }}</td>
                    <td class="text-center">{{ $row['on_leave'] }}</td>
                    @php $th = max(0, (float) $row['total_hours']); @endphp
                    <td class="fw-700">{{ floor($th) }}h {{ round(($th - floor($th)) * 60) }}m</td>
                    <td><span class="bp-badge {{ $rateBadge }}">{{ $attendanceRate }}%</span></td>
                </tr>
            @empty
                <x-core::table.empty colspan="9" icon="fa-solid fa-chart-bar" title="No attendance data"
                    description="No attendance data for the selected month." />
            @endforelse

            @if ($report->count() > 0)
                <!-- Totals / Averages Row -->
                <tr class="fw-700 bp-table-footer-row">
                    <td colspan="2" class="text-end">Totals / Averages</td>
                    <td class="text-center text-success">{{ $totalPresent }}</td>
                    <td class="text-center text-danger">{{ $totalAbsent }}</td>
                    <td class="text-center text-warning">{{ $totalLate }}</td>
                    <td class="text-center">{{ $totalHalfDay }}</td>
                    <td class="text-center">{{ $totalOnLeave }}</td>
                    @php $tth = max(0, (float) $totalHours); @endphp
                    <td>{{ floor($tth) }}h {{ round(($tth - floor($tth)) * 60) }}m</td>
                    <td><span
                            class="bp-badge {{ $avgAttendance >= 90 ? 'bp-badge-success' : ($avgAttendance >= 75 ? 'bp-badge-warning' : 'bp-badge-danger') }}">{{ $avgAttendance }}%</span>
                    </td>
                </tr>
            @endif
        </tbody>

        <x-slot:pagination>
            <div class="bp-card-footer">
                <div class="bp-pagination">
                    <span class="page-info">Showing {{ $report->count() }} employees</span>
                </div>
            </div>
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
