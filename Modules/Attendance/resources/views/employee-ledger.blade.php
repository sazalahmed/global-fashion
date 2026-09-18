@extends('core::layouts.master')

@section('title', 'Attendance Ledger — ' . $employee->name)
@section('page-title', $employee->name . ' — Attendance Ledger')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('attendance.index') }}">Attendance</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $employee->name }}</span>
@endsection

@section('page-actions')
    <form method="GET" class="bp-inline-filters">
        <label class="bp-form-label mb-0 fs-12">From</label>
        <input type="date" class="bp-form-control bp-form-control-sm" name="date_from" value="{{ request('date_from') }}">
        <label class="bp-form-label mb-0 fs-12">To</label>
        <input type="date" class="bp-form-control bp-form-control-sm" name="date_to" value="{{ request('date_to') }}">
        <select class="bp-form-select bp-form-select-sm" name="status">
            <option value="">All Status</option>
            @foreach (['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'half_day' => 'Half Day', 'on_leave' => 'On Leave'] as $value => $label)
                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary bp-filter-submit " title="Apply filters">
            <i class="fa-solid fa-filter"></i>
        </button>
        <a href="{{ route('attendance.employee-ledger', $employee->id) }}"
            class="bp-btn bp-btn-sm bp-btn-danger bp-filter-reset" title="Reset filters">
            <i class="fa-solid fa-rotate"></i>
        </a>
    </form>
    <a href="{{ route('attendance.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Attendance
    </a>
@endsection

@section('content')

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-user-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Present</div>
                    <div class="bp-stat-value">{{ (int) $summary->present }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-user-xmark"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Absent</div>
                    <div class="bp-stat-value">{{ (int) $summary->absent }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Late</div>
                    <div class="bp-stat-value">{{ (int) $summary->late }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-calendar-minus"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">On Leave</div>
                    <div class="bp-stat-value">{{ (int) $summary->on_leave }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Hours Worked</div>
                    <div class="bp-stat-value">{{ number_format((float) $summary->hours, 1) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-stopwatch"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Late By (total)</div>
                    <div class="bp-stat-value">{{ (int) $summary->late_minutes }}<span class="fs-13"> min</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>{{ $employee->employee_id }} —
                {{ $employee->department ?? 'No department' }}
            </h5>
            <span class="text-muted fs-13">{{ number_format($entries->total()) }} records</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours Worked</th>
                            <th>Status</th>
                            <th>Late By</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            @php
                                $statusMap = [
                                    'present' => ['badge' => 'bp-badge-success', 'label' => 'Present'],
                                    'absent' => ['badge' => 'bp-badge-danger', 'label' => 'Absent'],
                                    'late' => ['badge' => 'bp-badge-warning', 'label' => 'Late'],
                                    'half_day' => ['badge' => 'bp-badge-info', 'label' => 'Half Day'],
                                    'on_leave' => ['badge' => 'bp-badge-secondary', 'label' => 'On Leave'],
                                ];
                                $info = $statusMap[$entry->status] ?? [
                                    'badge' => 'bp-badge-dark',
                                    'label' => ucfirst((string) $entry->status),
                                ];
                                $date = \Carbon\Carbon::parse($entry->attendance_date);
                            @endphp
                            <tr>
                                <td class="text-nowrap fw-600">{{ $date->format('d M Y') }}</td>
                                <td class="fs-12 text-muted">{{ $date->format('D') }}</td>
                                <td>{{ $entry->check_in ? \Carbon\Carbon::parse($entry->check_in)->format('h:i A') : '—' }}
                                </td>
                                <td>{{ $entry->check_out ? \Carbon\Carbon::parse($entry->check_out)->format('h:i A') : '—' }}
                                </td>
                                <td>
                                    @if ($entry->hours_worked > 0)
                                        {{ floor($entry->hours_worked) }}h
                                        {{ round(($entry->hours_worked - floor($entry->hours_worked)) * 60) }}m
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><span class="bp-badge {{ $info['badge'] }}">{{ $info['label'] }}</span></td>
                                <td>
                                    @if ($entry->late_minutes > 0)
                                        <span class="text-danger fw-600">{{ $entry->late_minutes }} min</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="fs-12">{{ $entry->note ?? '' }}</td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="8" icon="fa-solid fa-clock-rotate-left"
                                title="No attendance records"
                                description="Nothing has been recorded for this employee in the selected period." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <x-core::table.pagination :paginator="$entries" itemLabel="records" />
    </div>

@endsection
