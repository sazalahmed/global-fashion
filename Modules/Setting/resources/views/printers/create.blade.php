@extends('core::layouts.master')

@section('title', __("Add Printer"))
@section('page-title', __("Add Printer"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('settings.printers.index') }}">Printers</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Add</span>
@endsection

@section('content')

<form action="{{ route('settings.printers.store') }}" method="POST">
  @csrf
  <div class="bp-card">
    <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-print me-2"></i>Printer Details</h5></div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="bp-form-label">Printer Name *</label>
          <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="e.g. Main Counter Printer">
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 js-network-field">
          <label class="bp-form-label">IP Address *</label>
          <input type="text" class="bp-form-control @error('ip_address') is-invalid @enderror" name="ip_address" value="{{ old('ip_address') }}" required placeholder="192.168.1.100" pattern="^(\d{1,3}\.){3}\d{1,3}$" title="Enter a valid IPv4 address, e.g. 192.168.1.100">
          @error('ip_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2 js-network-field">
          <label class="bp-form-label">Port *</label>
          <input type="number" class="bp-form-control" name="port" value="{{ old('port', 9100) }}" required min="1" max="65535">
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Printer Type *</label>
          <select class="bp-form-select w-100" name="printer_type" required>
            <option value="thermal" {{ old('printer_type') === 'thermal' ? 'selected' : '' }}>Thermal (80mm)</option>
            <option value="thermal_58" {{ old('printer_type') === 'thermal_58' ? 'selected' : '' }}>Thermal (58mm)</option>
            <option value="a4" {{ old('printer_type') === 'a4' ? 'selected' : '' }}>A4 Printer</option>
            <option value="label" {{ old('printer_type') === 'label' ? 'selected' : '' }}>Label Printer</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Purpose *</label>
          <select class="bp-form-select w-100" name="purpose" required>
            <option value="receipt" {{ old('purpose') === 'receipt' ? 'selected' : '' }}>Receipt</option>
            <option value="invoice" {{ old('purpose') === 'invoice' ? 'selected' : '' }}>Invoice</option>
            <option value="barcode" {{ old('purpose') === 'barcode' ? 'selected' : '' }}>Barcode</option>
            <option value="kitchen" {{ old('purpose') === 'kitchen' ? 'selected' : '' }}>Kitchen</option>
            <option value="report" {{ old('purpose') === 'report' ? 'selected' : '' }}>Report</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Paper Width (mm) *</label>
          <select class="bp-form-select w-100" name="paper_width" required>
            <option value="58" {{ old('paper_width') == '58' ? 'selected' : '' }}>58mm</option>
            <option value="80" {{ old('paper_width', '80') == '80' ? 'selected' : '' }}>80mm</option>
            <option value="210" {{ old('paper_width') == '210' ? 'selected' : '' }}>210mm (A4)</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Connection Type</label>
          <select class="bp-form-select w-100" name="connection_type">
            <option value="network">Network (IP)</option>
            <option value="usb">USB</option>
            <option value="bluetooth">Bluetooth</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Branch</label>
          <select class="bp-form-select w-100" name="branch_id">
            <option value="">All Branches</option>
            @forelse($branches as $branch)
              <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @empty
              <option value="" disabled>No branches found — add branches first</option>
            @endforelse
          </select>
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">Notes</label>
          <input type="text" class="bp-form-control" name="notes" value="{{ old('notes') }}" placeholder="Location, model, etc.">
        </div>
        <div class="col-md-6">
          <div class="d-flex gap-4 mt-3">
            <div class="form-check form-switch">
              <input type="hidden" name="is_default" value="0">
              <input class="form-check-input" type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
              <label class="form-check-label fw-600">Set as Default</label>
            </div>
            <div class="form-check form-switch">
              <input type="hidden" name="is_active" value="0">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
              <label class="form-check-label fw-600">Active</label>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="bp-card-footer text-end">
      <a href="{{ route('settings.printers.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Printer</button>
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
