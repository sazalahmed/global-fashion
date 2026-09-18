@extends('core::layouts.master')

@section('title', __("Activity Log"))
@section('page-title', __("Activity Log"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Activity Log</span>
@endsection

@section('page-actions')
  @bpCan('activities.delete')
  <form action="{{ route('activities.clear') }}" method="POST" class="d-inline" id="clearLogsForm">
    @csrf
    <input type="hidden" name="older_than_days" value="90">
    <button type="button" class="bp-btn bp-btn-danger" id="clearLogsBtn"><i class="fa-solid fa-trash me-1"></i>Clear Old Logs</button>
  </form>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Activities</div>
          <div class="bp-stat-value">{{ number_format($stats['total']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Today's Activities</div>
          <div class="bp-stat-value">{{ number_format($stats['today']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-calendar-week"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">This Week</div>
          <div class="bp-stat-value">{{ number_format($stats['this_week']) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Activity Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search description...">
        <select class="bp-form-select" name="log_name">
          <option value="">All Log Names</option>
          @foreach($logNames as $name)
            <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>{{ ucfirst($name) }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="event">
          <option value="">All Events</option>
          @foreach(['created', 'updated', 'deleted', 'login', 'logout'] as $event)
            <option value="{{ $event }}" {{ request('event') === $event ? 'selected' : '' }}>{{ ucfirst($event) }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="causer_id">
          <option value="">All Users</option>
          @foreach($users as $user)
            <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="subject_type">
          <option value="">All Subject Types</option>
          @foreach($subjectTypes as $type)
            <option value="{{ $type }}" {{ request('subject_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
          @endforeach
        </select>
        <input type="date" class="bp-form-control bp-filter-date" name="from_date" value="{{ request('from_date') }}">
        <span class="text-muted">to</span>
        <input type="date" class="bp-form-control bp-filter-date" name="to_date" value="{{ request('to_date') }}">
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column :sortable="true" field="created_at">Date/Time</x-core::table.column>
      <x-core::table.column>User</x-core::table.column>
      <x-core::table.column>Log Name</x-core::table.column>
      <x-core::table.column>Event</x-core::table.column>
      <x-core::table.column>Description</x-core::table.column>
      <x-core::table.column>Subject</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($activities as $log)
        <tr>
          <td class="fs-12">
            {{ $log->created_at->format('d M Y') }}
            <span class="text-muted d-block fs-11">{{ $log->created_at->format('h:i A') }}</span>
          </td>
          <td>
            @if($log->causer)
              <div class="d-flex align-items-center gap-2">
                <div class="bp-user-avatar bp-avatar-sm">{{ strtoupper(substr($log->causer->name ?? '?', 0, 2)) }}</div>
                <span class="fw-600 fs-13">{{ $log->causer->name ?? 'Unknown' }}</span>
              </div>
            @else
              <span class="text-muted fs-13">System</span>
            @endif
          </td>
          <td><span class="bp-badge bp-badge-primary">{{ ucfirst($log->log_name) }}</span></td>
          <td>
            @switch($log->event)
              @case('created')
                <span class="bp-badge bp-badge-success">Created</span>
                @break
              @case('updated')
                <span class="bp-badge bp-badge-info">Updated</span>
                @break
              @case('deleted')
                <span class="bp-badge bp-badge-danger">Deleted</span>
                @break
              @default
                <span class="bp-badge bp-badge-secondary">{{ ucfirst($log->event ?? 'N/A') }}</span>
            @endswitch
          </td>
          <td class="fs-13">{{ $log->description }}</td>
          <td class="fs-12">
            @if($log->subject_type)
              <span class="fw-600">{{ class_basename($log->subject_type) }}</span>
              @if($log->subject_id)
                <span class="text-muted">#{{ $log->subject_id }}</span>
              @endif
            @endif
          </td>
          <td>
            <div class="dropdown">
              <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('activities.show', $log->id) }}"><i class="fa-solid fa-eye me-2"></i> View Details</a></li>
              </ul>
            </div>
          </td>
        </tr>
      @empty
        <x-core::table.empty colspan="7" icon="fa-clock-rotate-left" title="No activity logs found." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$activities" itemLabel="activities" />
    </x-slot:pagination>
  </x-core::table>

@endsection

@push('scripts')
<script>
'use strict';
$(document).ready(function(){
  $('#clearLogsBtn').on('click', function(){
    if(confirm('Are you sure you want to clear old activity logs? This action cannot be undone. Logs older than 90 days will be permanently deleted.')) {
      $('#clearLogsForm').submit();
    }
  });
});
</script>
@endpush
