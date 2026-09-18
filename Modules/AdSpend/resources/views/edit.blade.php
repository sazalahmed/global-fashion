@extends('core::layouts.master')

@section('title', 'Edit — ' . $campaign->ad_number)
@section('page-title', __("Edit Ad Spend"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('adspend.index') }}">Ad Spend</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('adspend.show', $campaign) }}">{{ $campaign->ad_number }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit</span>
@endsection

@section('content')

<form action="{{ route('adspend.update', $campaign) }}" method="POST" enctype="multipart/form-data">
  @csrf @method('PUT')
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="bp-card mb-4">
        <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-pen me-2"></i>Campaign Details</h5></div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="bp-form-label">Platform *</label>
              <select class="bp-form-select w-100" name="ad_platform_id" required>
                @foreach($platforms as $p)
                  <option value="{{ $p->id }}" {{ old('ad_platform_id', $campaign->ad_platform_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">Campaign Name *</label>
              <input type="text" class="bp-form-control" name="campaign_name" value="{{ old('campaign_name', $campaign->campaign_name) }}" required>
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">External ID</label>
              <input type="text" class="bp-form-control" name="campaign_id_external" value="{{ old('campaign_id_external', $campaign->campaign_id_external) }}">
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Spend Date *</label>
              <input type="date" class="bp-form-control" name="spend_date" value="{{ old('spend_date', $campaign->spend_date->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Status</label>
              <select class="bp-form-select w-100" name="status">
                <option value="active" {{ old('status', $campaign->status) === 'active' ? 'selected' : '' }}>Active</option>
                <option value="paused" {{ old('status', $campaign->status) === 'paused' ? 'selected' : '' }}>Paused</option>
                <option value="completed" {{ old('status', $campaign->status) === 'completed' ? 'selected' : '' }}>Completed</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Amount *</label>
              <input type="number" class="bp-form-control" name="amount" value="{{ old('amount', num_input($campaign->amount)) }}" min="0.01" step="0.01" required>
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Tax</label>
              <input type="number" class="bp-form-control" name="tax_amount" value="{{ old('tax_amount', num_input($campaign->tax_amount)) }}" min="0" step="0.01">
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Target URL</label>
              <input type="url" class="bp-form-control" name="target_url" value="{{ old('target_url', $campaign->target_url) }}">
            </div>
            <div class="col-md-3"><label class="bp-form-label">Impressions</label><input type="number" class="bp-form-control" name="impressions" value="{{ old('impressions', $campaign->impressions) }}" min="0"></div>
            <div class="col-md-3"><label class="bp-form-label">Clicks</label><input type="number" class="bp-form-control" name="clicks" value="{{ old('clicks', $campaign->clicks) }}" min="0"></div>
            <div class="col-md-3"><label class="bp-form-label">Conversions</label><input type="number" class="bp-form-control" name="conversions" value="{{ old('conversions', $campaign->conversions) }}" min="0"></div>
            <div class="col-md-3"><label class="bp-form-label">Reach</label><input type="number" class="bp-form-control" name="reach" value="{{ old('reach', $campaign->reach) }}" min="0"></div>
            <div class="col-md-6 col-lg-3"><label class="bp-form-label">Receipt</label><input type="file" class="bp-form-control" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf">
              @if($campaign->receipt_path)<div class="fs-11 text-muted mt-1"><i class="fa-solid fa-paperclip me-1"></i>{{ basename($campaign->receipt_path) }}</div>@endif
            </div>
            <div class="col-12"><label class="bp-form-label">Notes</label><textarea class="bp-form-control" name="notes" rows="2">{{ old('notes', $campaign->notes) }}</textarea></div>
          </div>
        </div>
        <div class="bp-card-footer d-flex justify-content-between">
          <a href="{{ route('adspend.show', $campaign) }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
          <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Update</button>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="bp-card">
        <div class="bp-card-header"><h5 class="bp-card-title">Info</h5></div>
        <div class="bp-card-body">
          <div class="d-flex justify-content-between mb-2"><span class="text-muted">Number</span><span class="fw-700">{{ $campaign->ad_number }}</span></div>
          <div class="d-flex justify-content-between mb-2"><span class="text-muted">Paid</span><span class="fw-700 text-success">{{ currency_symbol() }} {{ number_format($campaign->paid_amount, 0) }}</span></div>
          <div class="d-flex justify-content-between mb-2"><span class="text-muted">Due</span><span class="fw-700 text-danger">{{ currency_symbol() }} {{ number_format($campaign->due_amount, 0) }}</span></div>
        </div>
      </div>
    </div>
  </div>
</form>

@endsection
