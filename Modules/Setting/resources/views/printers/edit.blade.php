@extends('core::layouts.master')

@section('title', 'Edit Printer — ' . $printer->name)
@section('page-title', __("Edit Printer"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('settings.printers.index') }}">Printers</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $printer->name }}</span>
@endsection

@section('content')

<form action="{{ route('settings.printers.update', $printer) }}" method="POST">
  @csrf @method('PUT')
  <div class="bp-card">
    <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-print me-2"></i>Printer Details</h5></div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="bp-form-label">Printer Name *</label>
          <input type="text" class="bp-form-control" name="name" value="{{ old('name', $printer->name) }}" required>
        </div>
        <div class="col-md-4 js-network-field">
          <label class="bp-form-label">IP Address *</label>
          <input type="text" class="bp-form-control" name="ip_address" value="{{ old('ip_address', $printer->ip_address) }}" required placeholder="192.168.1.100" pattern="^(\d{1,3}\.){3}\d{1,3}$" title="Enter a valid IPv4 address, e.g. 192.168.1.100">
        </div>
        <div class="col-md-2 js-network-field">
          <label class="bp-form-label">Port *</label>
          <input type="number" class="bp-form-control" name="port" value="{{ old('port', $printer->port) }}" required min="1" max="65535">
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Printer Type *</label>
          <select class="bp-form-select w-100" name="printer_type" required>
            @foreach(['thermal' => 'Thermal (80mm)', 'thermal_58' => 'Thermal (58mm)', 'a4' => 'A4 Printer', 'label' => 'Label Printer'] as $k => $v)
              <option value="{{ $k }}" {{ old('printer_type', $printer->printer_type) === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Purpose *</label>
          <select class="bp-form-select w-100" name="purpose" required>
            @foreach(['receipt', 'invoice', 'barcode', 'kitchen', 'report'] as $p)
              <option value="{{ $p }}" {{ old('purpose', $printer->purpose) === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Paper Width (mm) *</label>
          <select class="bp-form-select w-100" name="paper_width" required>
            @foreach([58, 80, 210] as $w)
              <option value="{{ $w }}" {{ old('paper_width', $printer->paper_width) == $w ? 'selected' : '' }}>{{ $w }}mm{{ $w === 210 ? ' (A4)' : '' }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Connection Type</label>
          <select class="bp-form-select w-100" name="connection_type">
            @foreach(['network' => 'Network (IP)', 'usb' => 'USB', 'bluetooth' => 'Bluetooth'] as $k => $v)
              <option value="{{ $k }}" {{ old('connection_type', $printer->connection_type) === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Branch</label>
          <select class="bp-form-select w-100" name="branch_id">
            <option value="">All Branches</option>
            @forelse($branches as $branch)
              <option value="{{ $branch->id }}" {{ old('branch_id', $printer->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @empty
              <option value="" disabled>No branches found — add branches first</option>
            @endforelse
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Notes</label>
          <input type="text" class="bp-form-control" name="notes" value="{{ old('notes', $printer->notes) }}">
        </div>
        <div class="col-md-6">
          <div class="d-flex gap-4 mt-3">
            <div class="form-check form-switch">
              <input type="hidden" name="is_default" value="0">
              <input class="form-check-input" type="checkbox" name="is_default" value="1" {{ old('is_default', $printer->is_default) ? 'checked' : '' }}>
              <label class="form-check-label fw-600">Set as Default</label>
            </div>
            <div class="form-check form-switch">
              <input type="hidden" name="is_active" value="0">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $printer->is_active) ? 'checked' : '' }}>
              <label class="form-check-label fw-600">Active</label>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="bp-card-footer text-end">
      <a href="{{ route('settings.printers.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Update Printer</button>
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
'use strict';
$(function () {
    // P-07: show IP/Port only for network printers; relax requiredness otherwise.
    var $conn = $('select[name="connection_type"]');
    var $networkFields = $('.js-network-field');
    var $ip = $('input[name="ip_address"]');
    function toggleNetworkFields() {
        var isNetwork = $conn.val() === 'network';
        $networkFields.toggle(isNetwork);
        $ip.prop('required', isNetwork);
    }
    toggleNetworkFields();
    $conn.on('change', toggleNetworkFields);

    // P-08: suggest paper width from printer type.
    var typeToWidth = { thermal: '80', thermal_58: '58', a4: '210', label: '58' };
    $('select[name="printer_type"]').on('change', function () {
        var w = typeToWidth[$(this).val()];
        if (w) { $('select[name="paper_width"]').val(w); }
    });
});
</script>
@endpush
