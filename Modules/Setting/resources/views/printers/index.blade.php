@extends('core::layouts.master')

@section('title', __("Printers"))
@section('page-title', __("Printer Management"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Printers</span>
@endsection

@section('page-actions')
@bpCan('settings.edit')
<a href="{{ route('settings.printers.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Printer</a>
@endbpCan
@endsection

@section('content')

<div class="bp-card">
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>IP : Port</th>
            <th>Type</th>
            <th>Purpose</th>
            <th>Branch</th>
            <th>Default</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($printers as $printer)
            <tr>
              <td class="fw-700">{{ $printer->name }}</td>
              <td><code>{{ $printer->ip_address }}:{{ $printer->port }}</code></td>
              <td><span class="bp-badge bp-badge-dark">{{ $printer->printer_type_label }}</span></td>
              <td><span class="bp-badge bp-badge-primary">{{ ucfirst($printer->purpose) }}</span></td>
              <td>{{ $printer->branch->name ?? 'All' }}</td>
              <td>
                @if($printer->is_default)
                  <i class="fa-solid fa-star text-warning"></i>
                @endif
              </td>
              <td>
                <x-core::status-toggle :url="route('settings.printers.toggle-status', $printer->id)" :active="$printer->is_active" />
              </td>
              <td>
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><button type="button" class="dropdown-item btn-test-printer" data-id="{{ $printer->id }}"><i class="fa-solid fa-plug me-2"></i> Test Connection</button></li>
                    @bpCan('settings.edit')
                    <li><a class="dropdown-item" href="{{ route('settings.printers.edit', $printer) }}"><i class="fa-solid fa-pen me-2"></i> Edit</a></li>
                    <li>
                      <form action="{{ route('settings.printers.destroy', $printer) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $printer->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                      </form>
                    </li>
                    @endbpCan
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <x-core::table.empty :colspan="8" icon="fa-print" title="No printers configured." />
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';
$(function () {
    var testUrlTemplate = '{{ route('settings.printers.test', '__ID__') }}';

    $('.btn-test-printer').on('click', function () {
        var $btn = $(this);
        var id = $btn.data('id');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

        $.post(testUrlTemplate.replace('__ID__', id), {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function (res) {
            if (res.success) {
                $btn.html('<i class="fa-solid fa-check text-success"></i>');
                if (window.showToast) { window.showToast('Printer reachable — latency ' + res.latency_ms + 'ms.', 'success'); }
            } else {
                $btn.html('<i class="fa-solid fa-xmark text-danger"></i>');
                if (window.showToast) { window.showToast('Connection failed: ' + (res.error || 'printer unreachable') + '.', 'danger'); }
            }
        }).fail(function () {
            $btn.html('<i class="fa-solid fa-xmark text-danger"></i>');
            if (window.showToast) { window.showToast('Printer test request failed.', 'danger'); }
        }).always(function () {
            setTimeout(function () {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-plug"></i>');
            }, 3000);
        });
    });
});
</script>
@endpush
