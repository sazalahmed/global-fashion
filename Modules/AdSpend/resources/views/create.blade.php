@extends('core::layouts.master')

@section('title', __("Record Ad Spend"))
@section('page-title', __("Record Ad Spend"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('adspend.index') }}">Ad Spend</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Record</span>
@endsection

@section('content')

<form action="{{ route('adspend.store') }}" method="POST" enctype="multipart/form-data" id="adSpendForm">
  @csrf
  <div class="row g-4">
    <div class="col-xl-8">

      <!-- Campaign Details -->
      <div class="bp-card mb-4">
        <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-rectangle-ad me-2"></i>Campaign Details</h5></div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="bp-form-label">Platform *</label>
              <select class="bp-form-select w-100" name="ad_platform_id" id="platformSelect" required>
                <option value="">Select Platform</option>
                @foreach($platforms as $p)
                  <option value="{{ $p->id }}" data-icon="{{ $p->icon }}" data-color="{{ $p->color }}" {{ old('ad_platform_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
              </select>
              @error('ad_platform_id') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">Campaign Name *</label>
              <input type="text" class="bp-form-control" name="campaign_name" value="{{ old('campaign_name') }}" required placeholder="e.g. Summer Sale Promo">
              @error('campaign_name') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">External Campaign ID</label>
              <input type="text" class="bp-form-control" name="campaign_id_external" value="{{ old('campaign_id_external') }}" placeholder="Platform's campaign ID">
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Spend Date *</label>
              <input type="date" class="bp-form-control" name="spend_date" value="{{ old('spend_date', date('Y-m-d')) }}" required>
              @error('spend_date') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Status</label>
              <select class="bp-form-select w-100" name="status">
                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Financial -->
      <div class="bp-card mb-4">
        <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Financial</h5></div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="bp-form-label">Amount ({{ currency_symbol() }}) *</label>
              <input type="number" class="bp-form-control" name="amount" id="adsAmount" value="{{ old('amount') }}" min="0.01" step="0.01" required>
              @error('amount') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Tax</label>
              <input type="number" class="bp-form-control" name="tax_amount" id="adsTax" value="{{ old('tax_amount', 0) }}" min="0" step="0.01">
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Payment Account</label>
              <select class="bp-form-select w-100" name="payment_account_id">
                <option value="">Pay Later (Due)</option>
                @foreach($paymentAccounts as $pa)
                  <option value="{{ $pa->id }}" {{ old('payment_account_id') == $pa->id ? 'selected' : '' }}>{{ $pa->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Performance Metrics -->
      <div class="bp-card mb-4">
        <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-chart-bar me-2"></i>Performance Metrics</h5></div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="bp-form-label">Impressions</label>
              <input type="number" class="bp-form-control" name="impressions" id="adsImpressions" value="{{ old('impressions', 0) }}" min="0">
            </div>
            <div class="col-md-3">
              <label class="bp-form-label">Clicks</label>
              <input type="number" class="bp-form-control" name="clicks" id="adsClicks" value="{{ old('clicks', 0) }}" min="0">
            </div>
            <div class="col-md-3">
              <label class="bp-form-label">Conversions</label>
              <input type="number" class="bp-form-control" name="conversions" id="adsConversions" value="{{ old('conversions', 0) }}" min="0">
            </div>
            <div class="col-md-3">
              <label class="bp-form-label">Reach</label>
              <input type="number" class="bp-form-control" name="reach" value="{{ old('reach', 0) }}" min="0">
            </div>
          </div>
        </div>
      </div>

      <!-- Additional -->
      <div class="bp-card mb-4">
        <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-paperclip me-2"></i>Additional</h5></div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="bp-form-label">Target URL</label>
              <input type="url" class="bp-form-control" name="target_url" value="{{ old('target_url') }}" placeholder="https://...">
            </div>
            <div class="col-md-6 col-lg-3">
              <label class="bp-form-label">Receipt</label>
              <input type="file" class="bp-form-control" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf">
            </div>
            <div class="col-12">
              <label class="bp-form-label">Notes</label>
              <textarea class="bp-form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Summary -->
    <div class="col-xl-4">
      <div class="bp-card bp-card-sticky">
        <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>Summary</h5></div>
        <div class="bp-card-body">
          <div class="d-flex justify-content-between mb-2"><span class="text-muted">Amount</span><span class="fw-700" id="sumAmount">{{ currency_symbol() }} 0</span></div>
          <div class="d-flex justify-content-between mb-2"><span class="text-muted">Tax</span><span class="fw-600" id="sumTax">{{ currency_symbol() }} 0</span></div>
          <hr>
          <div class="d-flex justify-content-between mb-3"><span class="fw-800">Total</span><span class="fw-800 fs-14" id="sumTotal">{{ currency_symbol() }} 0</span></div>
          <hr>
          <div class="fs-12 fw-700 text-muted mb-2">Calculated Metrics</div>
          <div class="d-flex justify-content-between mb-1"><span class="fs-12 text-muted">CPC</span><span class="fw-600 fs-12" id="sumCpc">—</span></div>
          <div class="d-flex justify-content-between mb-1"><span class="fs-12 text-muted">CPM</span><span class="fw-600 fs-12" id="sumCpm">—</span></div>
          <div class="d-flex justify-content-between mb-1"><span class="fs-12 text-muted">CTR</span><span class="fw-600 fs-12" id="sumCtr">—</span></div>
          <div class="d-flex justify-content-between mb-3"><span class="fs-12 text-muted">Conv. Rate</span><span class="fw-600 fs-12" id="sumConvRate">—</span></div>
        </div>
        <div class="bp-card-footer d-flex flex-column gap-2">
          <button type="submit" class="bp-btn bp-btn-primary w-100"><i class="fa-solid fa-save me-1"></i> Record Ad Spend</button>
          <a href="{{ route('adspend.index') }}" class="bp-btn bp-btn-danger w-100"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        </div>
      </div>
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
'use strict';
$(function () {
    function recalc() {
        var amt = parseFloat($('#adsAmount').val()) || 0;
        var tax = parseFloat($('#adsTax').val()) || 0;
        var total = amt + tax;
        var imp = parseInt($('#adsImpressions').val()) || 0;
        var clicks = parseInt($('#adsClicks').val()) || 0;
        var conv = parseInt($('#adsConversions').val()) || 0;

        $('#sumAmount').text('{{ currency_symbol() }} ' + Math.round(amt).toLocaleString('en-IN'));
        $('#sumTax').text('{{ currency_symbol() }} ' + Math.round(tax).toLocaleString('en-IN'));
        $('#sumTotal').text('{{ currency_symbol() }} ' + Math.round(total).toLocaleString('en-IN'));
        $('#sumCpc').text(clicks > 0 ? '{{ currency_symbol() }} ' + window.fmtAmount(amt / clicks) : '—');
        $('#sumCpm').text(imp > 0 ? '{{ currency_symbol() }} ' + window.fmtAmount((amt / imp) * 1000) : '—');
        $('#sumCtr').text(imp > 0 ? window.fmtAmount((clicks / imp) * 100) + '%' : '—');
        $('#sumConvRate').text(clicks > 0 ? window.fmtAmount((conv / clicks) * 100) + '%' : '—');
    }
    $('#adsAmount, #adsTax, #adsImpressions, #adsClicks, #adsConversions').on('input', recalc);
    recalc();
});
</script>
@endpush
