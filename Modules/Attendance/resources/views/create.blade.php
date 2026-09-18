@extends('core::layouts.master')

@section('title', __("Mark Attendance"))
@section('page-title', __("Mark Attendance"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('attendance.index') }}">Attendance</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Mark</span>
@endsection

@section('page-actions')
  <a href="{{ route('attendance.index') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back
  </a>
@endsection

@section('content')

  @if($isOffDay)
    <div class="alert alert-warning d-flex align-items-center gap-2">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <div>
        <strong>{{ \Illuminate\Support\Carbon::parse($date)->format('l, d M Y') }}</strong>
        is a {{ $isHoliday ? 'holiday' : 'weekend' }}.
        Only employees you mark as <strong>Present (Overtime)</strong> will be recorded — as overtime.
        Everyone left as <strong>Off</strong> is skipped (not marked absent).
      </div>
    </div>
  @endif

  <form action="{{ route('attendance.store') }}" method="POST">
    @csrf

    <!-- Date & Branch Selection -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-clipboard-check me-2"></i>Attendance Details</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="bp-form-label">Date *</label>
            <input type="date" class="bp-form-control @error('attendance_date') is-invalid @enderror" name="attendance_date" id="attendanceDate" value="{{ old('attendance_date', $date) }}" required>
            @error('attendance_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4 d-none">
            <label class="bp-form-label">Branch</label>
            <select class="bp-form-select w-100" name="branch_id" id="branchFilter">
              <option value="">All Branches</option>
              @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ ($branchId == $branch->id) ? 'selected' : '' }}>{{ $branch->name }}</option>
              @endforeach
            </select>
          </div>
          @unless($isOffDay)
          <div class="col-md-4 d-flex align-items-end gap-2">
            <button type="button" class="bp-btn bp-btn-success" id="markAllPresent">
              <i class="fa-solid fa-check-double me-1"></i> Mark All Present
            </button>
            <button type="button" class="bp-btn bp-btn-danger" id="markAllAbsent">
              <i class="fa-solid fa-xmark me-1"></i> Mark All Absent
            </button>
          </div>
          @endunless
        </div>
      </div>
    </div>

    <!-- Bulk Attendance Table -->
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-users me-2"></i>Employee Attendance ({{ $employees->count() }} employees)</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Status</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Note</th>
              </tr>
            </thead>
            <tbody>
              @forelse($employees as $index => $employee)
                @php
                  $initials = initials($employee->name);
                  $record = $existing->get($employee->id);
                  $currentStatus = $record ? $record->status : ($isOffDay ? 'off' : 'present');
                  $currentCheckIn = $record ? ($record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('H:i') : '') : $shiftStart;
                  $currentCheckOut = $record ? ($record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('H:i') : '') : $shiftEnd;
                  $currentNote = $record ? $record->note : '';
                @endphp
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="bp-user-avatar bp-avatar-sm">{{ $initials }}</div>
                      <div>
                        <div class="fw-700 fs-13">{{ $employee->name }}</div>
                        <div class="fs-11 text-muted">{{ $employee->employee_id }}</div>
                      </div>
                    </div>
                    <input type="hidden" name="records[{{ $index }}][employee_id]" value="{{ $employee->id }}">
                  </td>
                  <td>{{ $employee->department ?? '-' }}</td>
                  <td>
                    @if($isOffDay)
                    <select class="bp-form-select w-100 attendance-status" name="records[{{ $index }}][status]">
                      <option value="off" {{ $currentStatus == 'off' ? 'selected' : '' }}>Off (Not Working)</option>
                      <option value="present" {{ $currentStatus == 'present' ? 'selected' : '' }}>Present (Overtime)</option>
                    </select>
                    @else
                    <select class="bp-form-select w-100 attendance-status" name="records[{{ $index }}][status]">
                      <option value="present" {{ $currentStatus == 'present' ? 'selected' : '' }}>Present</option>
                      <option value="absent" {{ $currentStatus == 'absent' ? 'selected' : '' }}>Absent</option>
                      <option value="late" {{ $currentStatus == 'late' ? 'selected' : '' }}>Late</option>
                      <option value="half_day" {{ $currentStatus == 'half_day' ? 'selected' : '' }}>Half Day</option>
                      <option value="on_leave" {{ $currentStatus == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    </select>
                    @endif
                  </td>
                  <td>
                    <input type="time" class="bp-form-control check-in-time" name="records[{{ $index }}][check_in]"
                      value="{{ $currentCheckIn }}"
                      {{ in_array($currentStatus, ['absent', 'on_leave', 'off']) ? 'disabled' : '' }}>
                  </td>
                  <td>
                    <input type="time" class="bp-form-control check-out-time" name="records[{{ $index }}][check_out]"
                      value="{{ $currentCheckOut }}"
                      {{ in_array($currentStatus, ['absent', 'on_leave', 'off']) ? 'disabled' : '' }}>
                  </td>
                  <td>
                    <input type="text" class="bp-form-control" name="records[{{ $index }}][note]" value="{{ $currentNote }}" placeholder="Optional note">
                  </td>
                </tr>
              @empty
                <x-core::table.empty colspan="6" icon="fa-solid fa-users" title="No active employees found" />
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if($employees->count() > 0)
        <div class="bp-card-footer text-end">
          <a href="{{ route('attendance.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
          <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Attendance</button>
        </div>
      @endif
    </div>
  </form>

@endsection

@push('scripts')
<script>
'use strict';
// Shift window from Attendance Configuration — drives the default check-in/out.
var shiftStart = @json($shiftStart);
var shiftEnd = @json($shiftEnd);
$(function() {
    // Reload page when branch or date changes
    $('#branchFilter').on('change', function() {
        var date = $('#attendanceDate').val();
        var branchId = $(this).val();
        var url = '{{ route("attendance.create") }}?date=' + date;
        if (branchId) url += '&branch_id=' + branchId;
        window.location.href = url;
    });

    $('#attendanceDate').on('change', function() {
        var date = $(this).val();
        var branchId = $('#branchFilter').val();
        var url = '{{ route("attendance.create") }}?date=' + date;
        if (branchId) url += '&branch_id=' + branchId;
        window.location.href = url;
    });

    // Mark All Present
    $('#markAllPresent').on('click', function() {
        $('.attendance-status').val('present');
        $('input.check-in-time').prop('disabled', false).val(shiftStart);
        $('input.check-out-time').prop('disabled', false).val(shiftEnd);
    });

    // Mark All Absent
    $('#markAllAbsent').on('click', function() {
        $('.attendance-status').val('absent');
        $('input.check-in-time').val('').prop('disabled', true);
        $('input.check-out-time').val('').prop('disabled', true);
    });

    // Toggle time fields based on status
    $('.attendance-status').on('change', function() {
        var $row = $(this).closest('tr');
        var status = $(this).val();
        var $checkIn = $row.find('input.check-in-time');
        var $checkOut = $row.find('input.check-out-time');

        if (status === 'absent' || status === 'on_leave' || status === 'off') {
            $checkIn.val('').prop('disabled', true);
            $checkOut.val('').prop('disabled', true);
        } else {
            $checkIn.prop('disabled', false);
            $checkOut.prop('disabled', false);
            if (status === 'present' || status === 'late') {
                if (!$checkIn.val()) $checkIn.val(shiftStart);
                if (!$checkOut.val()) $checkOut.val(shiftEnd);
            } else if (status === 'half_day') {
                if (!$checkIn.val()) $checkIn.val(shiftStart);
                if (!$checkOut.val()) $checkOut.val('13:00');
            }
        }
    });
});
</script>
@endpush
