@extends('core::layouts.master')

@section('title', 'Log Viewer — ' . $filename)
@section('page-title', $filename)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('settings.system-logs.index') }}">System Logs</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $filename }}</span>
@endsection

@section('page-actions')
<a href="{{ route('settings.system-logs.download', $filename) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-download me-1"></i> Download</a>
<a href="{{ route('settings.system-logs.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

<!-- Stats Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body py-2">
    <div class="d-flex gap-3 flex-wrap align-items-center">
      @php
        $levelColors = ['emergency' => 'danger', 'alert' => 'danger', 'critical' => 'danger', 'error' => 'danger', 'warning' => 'warning', 'notice' => 'info', 'info' => 'info', 'debug' => 'dark'];
      @endphp
      @foreach($stats as $level => $count)
        @if($count > 0)
          <span class="bp-badge bp-badge-{{ $levelColors[$level] ?? 'dark' }}">{{ strtoupper($level) }}: {{ $count }}</span>
        @endif
      @endforeach
    </div>
  </div>
</div>

<!-- Filters -->
<div class="bp-card mb-4">
  <div class="bp-card-body py-2">
    <form action="{{ route('settings.system-logs.show', $filename) }}" method="GET" class="bp-filter-bar">
      <select class="bp-form-select" name="level">
        <option value="">All Levels</option>
        @foreach(['EMERGENCY', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'] as $lvl)
          <option value="{{ $lvl }}" {{ ($filters['level'] ?? '') === $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
        @endforeach
      </select>
      <input type="text" class="bp-form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search log messages...">
      <input type="date" class="bp-form-control" name="date" value="{{ $filters['date'] ?? '' }}">
      <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary"><i class="fa-solid fa-filter"></i></button>
      <a href="{{ route('settings.system-logs.show', $filename) }}" class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-rotate"></i></a>
    </form>
  </div>
</div>

<!-- Log Entries -->
<div class="bp-card">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Log Entries</h5>
    <span class="text-muted fs-13">{{ count($entries) }} entries (newest first)</span>
  </div>
  <div class="bp-card-body">
    @forelse($entries as $entry)
      @php
        $badgeClass = match($entry['level']) {
            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'bp-badge-danger',
            'WARNING' => 'bp-badge-warning',
            'NOTICE', 'INFO' => 'bp-badge-info',
            default => 'bp-badge-dark',
        };
      @endphp
      <div class="bp-log-entry mb-3 p-3 border rounded">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <span class="bp-badge {{ $badgeClass }}">{{ $entry['level'] }}</span>
          <span class="text-muted fs-11">{{ $entry['timestamp'] }}</span>
        </div>
        <div class="fw-600 fs-13 mb-1">{{ $entry['message'] }}</div>
        @if(!empty($entry['stack_trace']))
          <details class="mt-1">
            <summary class="fs-11 text-muted cursor-pointer">Stack Trace</summary>
            <pre class="bg-dark text-light p-2 rounded mt-1 fs-11" style="max-height:300px;overflow:auto">{{ $entry['stack_trace'] }}</pre>
          </details>
        @endif
      </div>
    @empty
      <div class="text-center text-muted py-4">
        <i class="fa-solid fa-circle-check fa-2x mb-2 d-block text-success"></i>
        No log entries match your filters.
      </div>
    @endforelse
  </div>
</div>

@endsection
