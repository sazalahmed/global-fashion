@extends('core::layouts.master')

@section('title', __("Apply for Leave"))
@section('page-title', __("Apply for Leave"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('attendance.index') }}">Attendance</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('attendance.leave') }}">Leave Management</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Apply for Leave</span>
@endsection

@section('page-actions')
  <a href="{{ route('attendance.leave') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Leave Management
  </a>
@endsection

@section('content')

  <form action="{{ route('attendance.leave.store') }}" method="POST">
    @csrf

    <div class="row g-4">
      <!-- Leave Application Form -->
      <div class="col-lg-8">
        <div class="bp-card">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-calendar-minus me-2"></i>Leave Application</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="bp-form-label">Employee *</label>
                <select class="bp-form-select w-100 @error('employee_id') is-invalid @enderror" name="employee_id" id="employeeSelect" required>
                  <option value="">Select Employee</option>
                  @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                      {{ $employee->name }} ({{ $employee->employee_id }})
                    </option>
                  @endforeach
                </select>
                @error('employee_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Leave Type *</label>
                <select class="bp-form-select w-100 @error('leave_type') is-invalid @enderror" name="leave_type" required>
                  <option value="">Select Leave Type</option>
                  @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->code }}" {{ old('leave_type') == $lt->code ? 'selected' : '' }}>{{ $lt->name }}</option>
                  @endforeach
                </select>
                @error('leave_type')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">From Date *</label>
                <input type="date" class="bp-form-control @error('start_date') is-invalid @enderror" name="start_date" id="fromDate" value="{{ old('start_date') }}" required>
                @error('start_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">To Date *</label>
                <input type="date" class="bp-form-control @error('end_date') is-invalid @enderror" name="end_date" id="toDate" value="{{ old('end_date') }}" required>
                @error('end_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Total Days</label>
                <input type="text" class="bp-form-control" id="totalDays" value="0" readonly>
              </div>
              <div class="col-12">
                <label class="bp-form-label">Reason</label>
                <textarea class="bp-form-control @error('reason') is-invalid @enderror" name="reason" rows="4" placeholder="Please provide a reason for the leave application...">{{ old('reason') }}</textarea>
                @error('reason')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
          <div class="bp-card-footer text-end">
            <a href="{{ route('attendance.leave') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Submit Application</button>
          </div>
        </div>
      </div>

      <!-- Leave Balance Summary -->
      <div class="col-lg-4">
        <div class="bp-card">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-scale-balanced me-2"></i>Leave Balance</h5>
          </div>
          <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
              <table class="bp-table">
                <thead>
                  <tr>
                    <th>Leave Type</th>
                    <th>Allowed</th>
                    <th>Taken</th>
                    <th>Remaining</th>
                  </tr>
                </thead>
                <tbody id="leaveBalanceBody">
                  @forelse($leaveTypes as $lt)
                    <tr data-type="{{ $lt->code }}">
                      <td class="fw-600 fs-13">{{ $lt->name }}</td>
                      <td class="bal-allowed">{{ $lt->max_days > 0 ? $lt->max_days : '∞' }}</td>
                      <td class="bal-taken">-</td>
                      <td><span class="bp-badge bp-badge-secondary bal-remaining">-</span></td>
                    </tr>
                  @empty
                    <x-core::table.empty colspan="4" icon="fa-solid fa-tags" title="No leave types configured" />
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="bp-card mt-3">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-info-circle me-2"></i>Guidelines</h5>
          </div>
          <div class="bp-card-body">
            <ul class="fs-13 mb-0">
              <li class="mb-2">Leave applications must be submitted in advance (except sick leave).</li>
              <li class="mb-2">Sick leave of more than 2 days requires a medical certificate.</li>
              <li class="mb-2">Annual leave must be planned at least 7 days in advance.</li>
              <li>Unpaid leave is granted only when all paid leaves are exhausted.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </form>

@endsection

@push('scripts')
<script>
'use strict';
// Weekend day numbers (0=Sun..6=Sat) so the day count matches payroll. Holidays
// are additionally excluded server-side when the leave is saved.
window.weekendDayNumbers = @json($weekendDayNumbers ?? [5, 6]);
$(function() {
    // Auto-calculate total WORKING days (weekends excluded)
    function calculateDays() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();

        if (!fromDate || !toDate) { $('#totalDays').val('0'); return; }

        var from = new Date(fromDate);
        var to = new Date(toDate);
        if (to < from) { $('#totalDays').val('0'); return; }

        var weekend = window.weekendDayNumbers || [5, 6];
        var count = 0;
        for (var d = new Date(from); d <= to; d.setDate(d.getDate() + 1)) {
            if (weekend.indexOf(d.getDay()) === -1) { count++; }
        }
        $('#totalDays').val(count);
    }

    $('#fromDate, #toDate').on('change', calculateDays);

    // Set minimum to_date based on from_date
    $('#fromDate').on('change', function() {
        var fromVal = $(this).val();
        $('#toDate').attr('min', fromVal);
        if ($('#toDate').val() && $('#toDate').val() < fromVal) {
            $('#toDate').val(fromVal);
        }
        calculateDays();
    });

    // Calculate on page load if old values exist
    calculateDays();

    // Fetch real leave balance when an employee is selected.
    var balanceTpl = '{{ route('attendance.leave.balance', '__ID__') }}';
    $('select[name="employee_id"]').on('change', function () {
        var empId = this.value;
        if (!empId) { return; }
        fetch(balanceTpl.replace('__ID__', empId), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                rows.forEach(function (item) {
                    var $row = $('#leaveBalanceBody tr[data-type="' + item.type + '"]');
                    if (!$row.length) { return; }
                    $row.find('.bal-taken').text(item.taken);
                    var $rem = $row.find('.bal-remaining')
                        .removeClass('bp-badge-secondary bp-badge-success bp-badge-warning bp-badge-danger');
                    if (item.unlimited) { $rem.text('∞').addClass('bp-badge-success'); }
                    else if (item.remaining <= 0) { $rem.text(item.remaining).addClass('bp-badge-danger'); }
                    else if (item.remaining <= 3) { $rem.text(item.remaining).addClass('bp-badge-warning'); }
                    else { $rem.text(item.remaining).addClass('bp-badge-success'); }
                });
            })
            .catch(function () {});
    }).trigger('change');
});
</script>
@endpush
