@extends('core::layouts.master')

@section('title', __("POS Settings"))
@section('page-title', __("POS Settings"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>POS Settings</span>
@endsection

@section('content')

<form action="{{ route('pos.settings.save') }}" method="POST">
  @csrf

  <div class="row g-4">
    <!-- General POS Settings -->
    <div class="col-xl-6">
      <div class="bp-card h-100">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-cash-register me-2"></i>General</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="bp-form-label">Default Customer</label>
              <select class="bp-form-select w-100" name="default_customer_id">
                <option value="">Walk-in Customer</option>
                @foreach($customers as $c)
                  <option value="{{ $c->id }}" {{ ($posSettings['default_customer_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">Default Payment Method</label>
              <select class="bp-form-select w-100" name="default_payment_method">
                <x-payment::account-options :selected="$posSettings['default_payment_method'] ?? ''" />
              </select>
            </div>
            <div class="col-12">
              <div class="form-check form-switch mb-2">
                <input type="hidden" name="sound_enabled" value="0">
                <input class="form-check-input" type="checkbox" name="sound_enabled" value="1" {{ ($posSettings['sound_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-600">Enable scan beep sound</label>
              </div>
              <div class="form-check form-switch mb-2">
                <input type="hidden" name="show_stock_qty" value="0">
                <input class="form-check-input" type="checkbox" name="show_stock_qty" value="1" {{ ($posSettings['show_stock_qty'] ?? '1') === '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-600">Show stock quantity on products</label>
              </div>
              <div class="form-check form-switch">
                <input type="hidden" name="allow_negative_stock" value="0">
                <input class="form-check-input" type="checkbox" name="allow_negative_stock" value="1" {{ ($posSettings['allow_negative_stock'] ?? '0') === '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-600">Allow selling below zero stock</label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Receipt Settings -->
    <div class="col-xl-6">
      <div class="bp-card h-100">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2"></i>Receipt</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-12">
              <div class="form-check form-switch mb-2">
                <input type="hidden" name="print_full_invoice" value="0">
                <input class="form-check-input" type="checkbox" name="print_full_invoice" value="1" {{ ($posSettings['print_full_invoice'] ?? '1') === '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-600">{{ __('Print full page invoice (A4)') }}</label>
              </div>
              <div class="form-check form-switch mb-2">
                <input type="hidden" name="print_thermal_receipt" value="0">
                <input class="form-check-input" type="checkbox" name="print_thermal_receipt" value="1" {{ ($posSettings['print_thermal_receipt'] ?? '1') === '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-600">{{ __('Print thermal receipt (80mm)') }}</label>
              </div>
            </div>
            <hr class="my-1">
            <div class="col-12">
              <label class="bp-form-label">Receipt Header Text</label>
              <textarea class="bp-form-control" name="receipt_header" rows="2" placeholder="Custom header text shown above receipt items...">{{ $posSettings['receipt_header'] ?? '' }}</textarea>
            </div>
            <div class="col-12">
              <label class="bp-form-label">Receipt Footer Text</label>
              <textarea class="bp-form-control" name="receipt_footer" rows="2" placeholder="Thank you for shopping with us!">{{ $posSettings['receipt_footer'] ?? 'Thank you for shopping with us!' }}</textarea>
            </div>
            <div class="col-12">
              <div class="form-check form-switch mb-2">
                <input type="hidden" name="receipt_show_logo" value="0">
                <input class="form-check-input" type="checkbox" name="receipt_show_logo" value="1" {{ ($posSettings['receipt_show_logo'] ?? '1') === '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-600">Show logo on receipt</label>
              </div>
              <div class="form-check form-switch mb-2">
                <input type="hidden" name="receipt_show_customer" value="0">
                <input class="form-check-input" type="checkbox" name="receipt_show_customer" value="1" {{ ($posSettings['receipt_show_customer'] ?? '1') === '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-600">Show customer info on receipt</label>
              </div>
              <div class="form-check form-switch">
                <input type="hidden" name="receipt_show_barcode" value="0">
                <input class="form-check-input" type="checkbox" name="receipt_show_barcode" value="1" {{ ($posSettings['receipt_show_barcode'] ?? '1') === '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-600">Show barcode on receipt</label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="bp-card mt-4">
    <div class="bp-card-footer text-end">
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save POS Settings</button>
    </div>
  </div>

</form>

@endsection
