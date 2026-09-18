@extends('core::layouts.master')

@section('title', __("Activity Detail"))
@section('page-title', __("Activity Detail"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('activities.index') }}">Activity Log</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Detail #{{ $activity->id }}</span>
@endsection

@section('page-actions')
  <a href="{{ route('activities.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>Back to Log</a>
@endsection

@section('content')

  <div class="row g-4">
    <!-- Activity Info -->
    <div class="col-lg-6">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Activity Information</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600 mb-1">Event</div>
              <div class="fw-700 fs-13">
                @switch($activity->event)
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
                    <span class="bp-badge bp-badge-secondary">{{ ucfirst($activity->event ?? 'N/A') }}</span>
                @endswitch
              </div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600 mb-1">Log Name</div>
              <div class="fw-700 fs-13"><span class="bp-badge bp-badge-primary">{{ ucfirst($activity->log_name) }}</span></div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600 mb-1">User</div>
              <div class="d-flex align-items-center gap-2">
                @if($activity->causer)
                  <div class="bp-user-avatar bp-avatar-sm">{{ strtoupper(substr($activity->causer->name ?? '?', 0, 2)) }}</div>
                  <span class="fw-700 fs-13">{{ $activity->causer->name ?? 'Unknown' }}</span>
                @else
                  <span class="fw-700 fs-13 text-muted">System</span>
                @endif
              </div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600 mb-1">Date/Time</div>
              <div class="fw-700 fs-13">{{ $activity->created_at->format('d M Y, h:i A') }}</div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600 mb-1">Subject Type</div>
              <div class="fw-700 fs-13">
                @if($activity->subject_type)
                  {{ class_basename($activity->subject_type) }}
                @else
                  <span class="text-muted">-</span>
                @endif
              </div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600 mb-1">Subject ID</div>
              <div class="fw-700 fs-13">
                @if($activity->subject_id)
                  #{{ $activity->subject_id }}
                @else
                  <span class="text-muted">-</span>
                @endif
              </div>
            </div>
            <div class="col-12">
              <div class="fs-12 text-muted fw-600 mb-1">Description</div>
              <div class="fw-700 fs-13">{{ $activity->description }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Changes (Before / After) -->
    <div class="col-lg-6">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-code-compare me-2"></i>Changes Made</h5>
        </div>
        <div class="bp-card-body p-0">
          @php
            $changes = $activity->changes;
            $oldValues = $changes['old'] ?? [];
            $newValues = $changes['new'] ?? [];
            $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
          @endphp

          @if(count($allKeys) > 0)
            <div class="bp-table-wrapper">
              <table class="bp-table">
                <thead>
                  <tr>
                    <th>Field</th>
                    <th>Old Value</th>
                    <th>New Value</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($allKeys as $key)
                    <tr>
                      <td class="fw-600 fs-13">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                      <td class="fs-13 text-danger">{{ $oldValues[$key] ?? '-' }}</td>
                      <td class="fs-13 text-success">{{ $newValues[$key] ?? '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="p-4 text-center text-muted fs-13">
              <i class="fa-solid fa-circle-info me-1"></i>No property changes recorded for this activity.
            </div>
          @endif
        </div>
      </div>

      @if($activity->properties && (isset($activity->properties['extra']) || count(array_diff(array_keys($activity->properties), ['old', 'new', 'attributes'])) > 0))
        <div class="bp-card mt-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-database me-2"></i>Raw Properties</h5>
          </div>
          <div class="bp-card-body">
            <pre class="mb-0 fs-12">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
          </div>
        </div>
      @endif
    </div>
  </div>

@endsection
