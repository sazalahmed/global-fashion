@extends('core::layouts.master')

@section('title', $campaign->ad_number)
@section('page-title', $campaign->campaign_name)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('adspend.index') }}">Ad Spend</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $campaign->ad_number }}</span>
@endsection

@section('page-actions')
@if($campaign->meta_url)
<a href="{{ $campaign->meta_url }}" target="_blank" rel="noopener" class="bp-btn bp-btn-outline" title="View / edit on Meta Ads Manager"><i class="fa-brands fa-meta me-1" style="color:#1877F2"></i> View on Meta</a>
@endif
@bpCan('marketing.edit')
    <a href="{{ route('adspend.edit', $campaign) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-pen me-1"></i> Edit</a>
@endbpCan
@bpCan('marketing.delete')
    <form action="{{ route('adspend.destroy', $campaign) }}" method="POST" class="d-inline">
        @csrf @method('DELETE')
        <button type="submit" class="bp-btn bp-btn-danger delete-confirm" data-name="{{ $campaign->campaign_name }}"><i class="fa-solid fa-trash me-1"></i> Delete Campaign</button>
    </form>
@endbpCan
@endsection

@section('content')

<div class="row g-4">
  <!-- Left -->
  <div class="col-xl-8">

    <!-- Campaign Info -->
    <div class="bp-card mb-4">
      <div class="bp-card-header d-flex justify-content-between align-items-center">
        <h5 class="bp-card-title"><i class="{{ $campaign->platform->icon }}" style="color: {{ $campaign->platform->color }}"></i> {{ $campaign->platform->name }} Campaign</h5>
        @if($campaign->status === 'active')
          <span class="bp-badge bp-badge-success">Active</span>
        @elseif($campaign->status === 'paused')
          <span class="bp-badge bp-badge-warning">Paused</span>
        @else
          <span class="bp-badge bp-badge-dark">Completed</span>
        @endif
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="bp-info-row"><div class="bp-info-label">Ad Number</div><div class="bp-info-value fw-800">{{ $campaign->ad_number }}</div></div>
            <div class="bp-info-row"><div class="bp-info-label">Campaign Name</div><div class="bp-info-value fw-700">{{ $campaign->campaign_name }}</div></div>
            @if($campaign->campaign_type_label)
            <div class="bp-info-row"><div class="bp-info-label">Type</div><div class="bp-info-value"><span class="bp-badge bp-badge-secondary">{{ $campaign->campaign_type_label }}</span></div></div>
            @endif
            @if($campaign->campaign_id_external)
            <div class="bp-info-row"><div class="bp-info-label">External ID</div><div class="bp-info-value">
              @if($campaign->meta_url)
                <a href="{{ $campaign->meta_url }}" target="_blank" rel="noopener" title="View / edit on Meta Ads Manager"><code>{{ $campaign->campaign_id_external }}</code> <i class="fa-solid fa-arrow-up-right-from-square fa-2xs"></i></a>
              @else
                <code>{{ $campaign->campaign_id_external }}</code>
              @endif
            </div></div>
            @endif
            <div class="bp-info-row"><div class="bp-info-label">Spend Date</div><div class="bp-info-value">{{ $campaign->spend_date->format('d M Y') }}</div></div>
          </div>
          <div class="col-md-6">
            @if($campaign->target_url)
            <div class="bp-info-row"><div class="bp-info-label">Target URL</div><div class="bp-info-value"><a href="{{ $campaign->target_url }}" target="_blank" class="text-primary">{{ \Illuminate\Support\Str::limit($campaign->target_url, 40) }} <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i></a></div></div>
            @endif
            <div class="bp-info-row d-none"><div class="bp-info-label">Branch</div><div class="bp-info-value">{{ $campaign->branch->name ?? '—' }}</div></div>
            <div class="bp-info-row"><div class="bp-info-label">Created By</div><div class="bp-info-value">{{ $campaign->creator->name ?? '—' }}</div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Metrics -->
    <div class="bp-card mb-4">
      <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-chart-bar me-2"></i>Performance Metrics</h5></div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-3"><div class="bp-stat-card"><div class="bp-stat-content"><div class="bp-stat-label">Impressions</div><div class="bp-stat-value">{{ number_format($campaign->impressions) }}</div></div></div></div>
          <div class="col-md-3"><div class="bp-stat-card"><div class="bp-stat-content"><div class="bp-stat-label">Clicks</div><div class="bp-stat-value">{{ number_format($campaign->clicks) }}</div></div></div></div>
          @php $res = $campaign->result; @endphp
          <div class="col-md-3"><div class="bp-stat-card"><div class="bp-stat-content"><div class="bp-stat-label">{{ $res['label'] ?? 'Conversions' }}</div><div class="bp-stat-value">{{ number_format($res['count'] ?? $campaign->conversions) }}</div></div></div></div>
          <div class="col-md-3"><div class="bp-stat-card"><div class="bp-stat-content"><div class="bp-stat-label">Reach</div><div class="bp-stat-value">{{ number_format($campaign->reach) }}</div></div></div></div>
          <div class="col-md-3"><div class="bp-stat-card"><div class="bp-stat-content"><div class="bp-stat-label">CPC</div><div class="bp-stat-value">{{ $campaign->cpc !== null ? '$' . ' ' . num($campaign->cpc) : '—' }}</div></div></div></div>
          <div class="col-md-3"><div class="bp-stat-card"><div class="bp-stat-content"><div class="bp-stat-label">CPM</div><div class="bp-stat-value">{{ $campaign->cpm !== null ? '$' . ' ' . num($campaign->cpm) : '—' }}</div></div></div></div>
          <div class="col-md-3"><div class="bp-stat-card"><div class="bp-stat-content"><div class="bp-stat-label">CTR</div><div class="bp-stat-value">{{ $campaign->ctr !== null ? $campaign->ctr . '%' : '—' }}</div></div></div></div>
          <div class="col-md-3"><div class="bp-stat-card"><div class="bp-stat-content"><div class="bp-stat-label">Conv. Rate</div><div class="bp-stat-value">{{ $campaign->conversion_rate !== null ? $campaign->conversion_rate . '%' : '—' }}</div></div></div></div>
        </div>
      </div>
    </div>

    @if($campaign->notes)
    <div class="bp-card mb-4">
      <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2"></i>Notes</h5></div>
      <div class="bp-card-body">{{ $campaign->notes }}</div>
    </div>
    @endif
  </div>

  <!-- Right -->
  <div class="col-xl-4">
    <!-- Financial -->
    <div class="bp-card mb-4">
      <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Financial</h5></div>
      <div class="bp-card-body">
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Amount</span><span class="fw-700">{{ '$ ' . num($campaign->amount) }}</span></div>
        @if((float)$campaign->tax_amount > 0)
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Tax</span><span>{{ '$ ' . num($campaign->tax_amount) }}</span></div>
        @endif
        <hr>
        <div class="d-flex justify-content-between mb-2"><span class="fw-800">Total</span><span class="fw-800">{{ '$ ' . num($campaign->total_amount) }}</span></div>
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Paid</span><span class="text-success fw-700">{{ '$ ' . num($campaign->paid_amount) }}</span></div>
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Due</span><span class="text-danger fw-700">{{ '$ ' . num($campaign->due_amount) }}</span></div>
        <div class="text-end">
          @if($campaign->payment_status === 'paid')
            <span class="bp-badge bp-badge-success">Paid</span>
          @elseif($campaign->payment_status === 'partial')
            <span class="bp-badge bp-badge-warning">Partial</span>
          @else
            <span class="bp-badge bp-badge-danger">Unpaid</span>
          @endif
        </div>
      </div>
    </div>

    @if((float)$campaign->due_amount > 0)
    @bpCan('marketing.create')
    <div class="bp-card mb-4">
      <div class="bp-card-header"><h5 class="bp-card-title"><i class="fa-solid fa-credit-card me-2"></i>Record Payment</h5></div>
      <div class="bp-card-body">
        <form action="{{ route('adspend.payment', $campaign) }}" method="POST">
          @csrf
          <div class="mb-2">
            <label class="bp-form-label">Amount *</label>
            <input type="number" name="amount" class="bp-form-control" value="{{ $campaign->due_amount }}" min="0.01" max="{{ $campaign->due_amount }}" step="0.01" required>
          </div>
          <div class="mb-2">
            <label class="bp-form-label">Account *</label>
            <select name="payment_account_id" class="bp-form-select w-100" required>
              @foreach($paymentAccounts as $pa)
                <option value="{{ $pa->id }}">{{ $pa->name }}</option>
              @endforeach
            </select>
          </div>
          <button type="submit" class="bp-btn bp-btn-primary w-100 mt-2"><i class="fa-solid fa-bangladeshi-taka-sign me-1"></i> Pay</button>
        </form>
      </div>
    </div>
    @endbpCan
    @endif

    @if($campaign->receipt_path)
    <div class="bp-card mb-4">
      <div class="bp-card-body text-center">
        <a href="{{ upload_url($campaign->receipt_path) }}" target="_blank" class="bp-btn bp-btn-outline"><i class="fa-solid fa-paperclip me-1"></i> View Receipt</a>
      </div>
    </div>
    @endif

  </div>
</div>

@endsection
