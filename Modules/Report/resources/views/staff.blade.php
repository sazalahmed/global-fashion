@extends('core::layouts.master')

@section('title', __('Staff Report'))
@section('page-title', __('Staff Report'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Staff Report</span>
@endsection

@section('page-actions')
    @bpCan('reports.export')
        <x-core::export-dropdown module="report-staff" />
    @endbpCan
    <button class="bp-btn bp-btn-primary" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Date Range & Filters -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <form class="row g-3 align-items-end bp-report-filters" method="GET" action="{{ route('reports.staff') }}">
                <div class="col-md-2">
                    <label class="bp-form-label">From Date</label>
                    <input type="date" class="bp-form-control" name="date_from"
                        value="{{ $filters['date_from'] ?? now()->startOfMonth()->toDateString() }}">
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">To Date</label>
                    <input type="date" class="bp-form-control" name="date_to"
                        value="{{ $filters['date_to'] ?? now()->toDateString() }}">
                </div>
                <div class="col-md-2 d-none">
                    <label class="bp-form-label">Branch</label>
                    <select class="bp-form-select w-100" name="branch_id">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}"
                                {{ ($filters['branch_id'] ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="bp-form-label">Department</label>
                    <select class="bp-form-select w-100" name="department">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}"
                                {{ ($filters['department'] ?? '') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('reports.staff') }}" class="bp-btn bp-btn-danger" title="Reset Filters">
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Employees</div>
                    <div class="bp-stat-value">{{ $stats['total_employees'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-user-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Present Today</div>
                    <div class="bp-stat-value">{{ $stats['present_today'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-user-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">On Leave</div>
                    <div class="bp-stat-value">{{ $stats['on_leave_today'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-chart-simple"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Average Attendance</div>
                    <div class="bp-stat-value">{{ $stats['avg_attendance'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Table -->
    <x-core::table>
        <x-core::table.header>
            <x-core::table.column>Employee</x-core::table.column>
            <x-core::table.column>Department</x-core::table.column>
            <x-core::table.column>Branch</x-core::table.column>
            <x-core::table.column>Present</x-core::table.column>
            <x-core::table.column>Absent</x-core::table.column>
            <x-core::table.column>Late</x-core::table.column>
            <x-core::table.column>Leave</x-core::table.column>
            <x-core::table.column>Hours Worked</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($data as $employee)
                <tr>
                    <td>
                        <div>
                            <span class="fw-700 fs-13">{{ $employee->name }}</span>
                            <div class="fs-11 text-muted">{{ $employee->employee_id }} &middot;
                                {{ $employee->designation }}</div>
                        </div>
                    </td>
                    <td class="fs-13">{{ $employee->department ?? '' }}</td>
                    <td class="fs-13">{{ $employee->branch->name ?? '' }}</td>
                    <td class="fw-600 bp-text-success">{{ $employee->present_days }}</td>
                    <td class="fw-600 bp-text-danger">{{ $employee->absent_days }}</td>
                    <td class="fw-600 bp-text-warning">{{ $employee->late_days }}</td>
                    <td class="fw-600">{{ $employee->leave_days }}</td>
                    <td class="fs-13">{{ number_format($employee->total_hours, 1) }}h</td>
                </tr>
            @empty
                <x-core::table.empty :colspan="8" icon="fa-users-gear"
                    title="No employee data found for the selected filters." />
            @endforelse
        </tbody>
    </x-core::table>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Print report
            $('#btnPrintReport').on('click', function() {
                window.print();
            });
        });
    </script>
@endpush
