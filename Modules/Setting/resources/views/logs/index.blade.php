@extends('core::layouts.master')

@section('title', __("System Logs"))
@section('page-title', __("System Logs"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>System Logs</span>
@endsection

@section('content')

<div class="row g-3 mb-4">
  <div class="col-xl-4 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-circle-exclamation"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Errors Today</div>
        <div class="bp-stat-value {{ $todayErrors > 0 ? 'text-danger' : '' }}">{{ $todayErrors }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Warnings Today</div>
        <div class="bp-stat-value">{{ $todayWarnings }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-file-lines"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Log Files</div>
        <div class="bp-stat-value">{{ count($files) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="bp-card">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-folder-open me-2"></i>Log Files</h5>
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>File Name</th>
            <th>Size</th>
            <th>Last Modified</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($files as $file)
            <tr>
              <td class="fw-700"><i class="fa-solid fa-file-lines me-2 text-muted"></i>{{ $file['filename'] }}</td>
              <td>{{ $file['size_human'] }}</td>
              <td>{{ $file['last_modified_human'] }}</td>
              <td>
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('settings.system-logs.show', $file['filename']) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
                    <li><a class="dropdown-item" href="{{ route('settings.system-logs.download', $file['filename']) }}"><i class="fa-solid fa-download me-2"></i> Download</a></li>
                    @bpCan('settings.edit')
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form action="{{ route('settings.system-logs.clear', $file['filename']) }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item" onclick="return confirm('Clear all contents of {{ $file['filename'] }}?')"><i class="fa-solid fa-eraser me-2"></i> Clear</button>
                      </form>
                    </li>
                    <li>
                      <form action="{{ route('settings.system-logs.delete', $file['filename']) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete {{ $file['filename'] }}?')"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                      </form>
                    </li>
                    @endbpCan
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <x-core::table.empty :colspan="4" icon="fa-folder-open" title="No log files found." />
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection
