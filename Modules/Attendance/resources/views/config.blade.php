@extends('core::layouts.master')

@section('title', __("Attendance Configuration"))
@section('page-title', __("Attendance Configuration"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('attendance.index') }}">Attendance</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Configuration</span>
@endsection

@section('content')

<!-- Weekend Setup -->
<div class="bp-card mb-4">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-calendar-week me-2"></i>Weekend Days</h5>
  </div>
  <form action="{{ route('attendance.config.weekends') }}" method="POST">
    @csrf
    <div class="bp-card-body">
      <div class="row g-3">
        @foreach($dayNames as $num => $name)
          <div class="col-md-3 col-sm-4 col-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="weekends[]" value="{{ $num }}"
                     id="day{{ $num }}" {{ ($weekendDays->get($num)?->is_weekend ?? false) ? 'checked' : '' }}>
              <label class="form-check-label fw-600" for="day{{ $num }}">{{ $name }}</label>
            </div>
          </div>
        @endforeach
      </div>
      <div class="text-muted fs-12 mt-2">Check the days that are weekends (non-working days). Default for BD: Friday + Saturday.</div>
    </div>
    <div class="bp-card-footer text-end">
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Weekends</button>
    </div>
  </form>
</div>

<!-- Shift Timing -->
<div class="bp-card mb-4">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-clock me-2"></i>Shift Timing</h5>
  </div>
  <form action="{{ route('attendance.config.shift') }}" method="POST">
    @csrf
    <div class="bp-card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="bp-form-label">Shift Start Time</label>
          <input type="time" class="bp-form-control" name="shift_start" value="{{ $shiftStart }}" required>
          <div class="text-muted fs-12 mt-1">Check-ins after this time are recorded as late.</div>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Shift End Time</label>
          <input type="time" class="bp-form-control" name="shift_end" value="{{ $shiftEnd }}" required>
          <div class="text-muted fs-12 mt-1">Work beyond this time counts toward overtime.</div>
        </div>
      </div>
    </div>
    <div class="bp-card-footer text-end">
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Shift</button>
    </div>
  </form>
</div>

<!-- Holidays -->
<div class="bp-card">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-umbrella-beach me-2"></i>Holidays</h5>
    @bpCan('hr.create')
    <button class="bp-btn bp-btn-sm bp-btn-primary" data-bs-toggle="modal" data-bs-target="#addHolidayModal"><i class="fa-solid fa-plus me-1"></i> Add Holiday</button>
    @endbpCan
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Recurring</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($holidays as $holiday)
            <tr>
              <td class="fw-700">{{ $holiday->name }}</td>
              <td>{{ $holiday->start_date->format('d M Y') }}</td>
              <td>{{ $holiday->end_date ? $holiday->end_date->format('d M Y') : '-' }}</td>
              <td>
                @if($holiday->is_recurring)
                  <span class="bp-badge bp-badge-info">Yearly</span>
                @else
                  <span class="text-muted">One-time</span>
                @endif
              </td>
              <td><span class="bp-badge {{ $holiday->is_active ? 'bp-badge-success' : 'bp-badge-danger' }}">{{ $holiday->is_active ? 'Active' : 'Inactive' }}</span></td>
              <td>
                @bpCanAny('hr.edit','hr.delete')
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    @bpCan('hr.edit')
                    <li>
                      <button type="button" class="dropdown-item btn-edit-holiday"
                              data-id="{{ $holiday->id }}"
                              data-name="{{ $holiday->name }}"
                              data-start="{{ $holiday->start_date->format('Y-m-d') }}"
                              data-end="{{ $holiday->end_date?->format('Y-m-d') }}"
                              data-description="{{ $holiday->description }}"
                              data-recurring="{{ $holiday->is_recurring ? 1 : 0 }}"
                              data-active="{{ $holiday->is_active ? 1 : 0 }}"
                              data-bs-toggle="modal" data-bs-target="#editHolidayModal"><i class="fa-solid fa-pen me-2"></i> Edit</button>
                    </li>
                    @endbpCan
                    @bpCan('hr.delete')
                    <li>
                      <form action="{{ route('attendance.config.holidays.destroy', $holiday) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $holiday->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                      </form>
                    </li>
                    @endbpCan
                  </ul>
                </div>
                @endbpCanAny
              </td>
            </tr>
          @empty
            <x-core::table.empty colspan="6" icon="fa-solid fa-umbrella-beach" title="No holidays configured" />
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Holiday Modal -->
@bpCan('hr.create')
<div class="modal fade" id="addHolidayModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('attendance.config.holidays.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-700">Add Holiday</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="bp-form-label">Holiday Name *</label><input type="text" class="bp-form-control" name="name" required></div>
          <div class="row g-3">
            <div class="col-md-6"><label class="bp-form-label">Start Date *</label><input type="date" class="bp-form-control" name="start_date" required></div>
            <div class="col-md-6"><label class="bp-form-label">End Date</label><input type="date" class="bp-form-control" name="end_date"></div>
          </div>
          <div class="mb-3 mt-3"><label class="bp-form-label">Description</label><input type="text" class="bp-form-control" name="description"></div>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="recurring"><label class="form-check-label" for="recurring">Recurring every year</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button><button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>Save</button></div>
      </div>
    </form>
  </div>
</div>
@endbpCan

<!-- Edit Holiday Modal -->
@bpCan('hr.edit')
<div class="modal fade" id="editHolidayModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="editHolidayForm" method="POST">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-700">Edit Holiday</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="bp-form-label">Holiday Name *</label><input type="text" class="bp-form-control" name="name" id="editHolidayName" required></div>
          <div class="row g-3">
            <div class="col-md-6"><label class="bp-form-label">Start Date *</label><input type="date" class="bp-form-control" name="start_date" id="editHolidayStart" required></div>
            <div class="col-md-6"><label class="bp-form-label">End Date</label><input type="date" class="bp-form-control" name="end_date" id="editHolidayEnd"></div>
          </div>
          <div class="mb-3 mt-3"><label class="bp-form-label">Description</label><input type="text" class="bp-form-control" name="description" id="editHolidayDescription"></div>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="editHolidayRecurring"><label class="form-check-label" for="editHolidayRecurring">Recurring every year</label></div>
          <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="editHolidayActive"><label class="form-check-label" for="editHolidayActive">Active</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button><button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>Update</button></div>
      </div>
    </form>
  </div>
</div>
@endbpCan

@endsection

@push('scripts')
<script>
'use strict';
$(function () {
    var updateUrl = '{{ route('attendance.config.holidays.update', '__ID__') }}';
    $(document).on('click', '.btn-edit-holiday', function () {
        var $b = $(this);
        $('#editHolidayForm').attr('action', updateUrl.replace('__ID__', $b.data('id')));
        $('#editHolidayName').val($b.data('name'));
        $('#editHolidayStart').val($b.data('start'));
        $('#editHolidayEnd').val($b.data('end') || '');
        $('#editHolidayDescription').val($b.data('description') || '');
        $('#editHolidayRecurring').prop('checked', String($b.data('recurring')) === '1');
        $('#editHolidayActive').prop('checked', String($b.data('active')) === '1');
    });
});
</script>
@endpush
