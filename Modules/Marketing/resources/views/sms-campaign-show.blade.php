@extends('core::layouts.master')

@section('title', __('Campaign Report'))
@section('page-title', __('Campaign Report'))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Marketing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('marketing.sms-campaigns') }}">SMS Campaigns</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ $campaign->name }}</span>
@endsection

@section('page-actions')
  <a href="{{ route('marketing.sms-campaigns') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Campaigns
  </a>
  @bpCan('marketing.create')
    <form action="{{ route('marketing.sms-campaigns.duplicate', $campaign) }}" method="POST" class="d-inline">
      @csrf
      <button type="submit" class="bp-btn bp-btn-outline"><i class="fa-solid fa-copy me-1"></i> Duplicate</button>
    </form>
  @endbpCan
@endsection

@section('content')

  @php
    $delivered = max($campaign->sent_count - $campaign->failed_count, 0);
    $rate = $campaign->sent_count > 0 ? round(($delivered / $campaign->sent_count) * 100, 1) : 0;
  @endphp

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Recipients</div>
          <div class="bp-stat-value">{{ number_format($campaign->total_recipients) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-paper-plane"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Sent</div>
          <div class="bp-stat-value">{{ number_format($campaign->sent_count) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-check-double"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Delivery Rate</div>
          <div class="bp-stat-value">{{ $rate }}%</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-coins"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Cost</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($campaign->total_cost, 0, '.', ',') }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-xl-8">
      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-message me-2"></i>Message</h5>
        </div>
        <div class="bp-card-body">
          <div class="bp-sms-bubble">{{ $campaign->message }}</div>
          <div class="fs-11 text-muted mt-2">{{ mb_strlen($campaign->message) }}/160 characters &middot; {{ max(1, (int) ceil(mb_strlen($campaign->message) / 160)) }} SMS part(s)</div>
        </div>
      </div>

      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-chart-simple me-2"></i>Delivery Breakdown</h5>
        </div>
        <div class="bp-card-body p-0">
          <div class="bp-table-wrapper">
            <table class="bp-table">
              <tbody>
                <tr><td class="fw-600">Delivered</td><td class="text-end">{{ number_format($delivered) }}</td></tr>
                <tr><td class="fw-600">Failed</td><td class="text-end bp-text-danger">{{ number_format($campaign->failed_count) }}</td></tr>
                <tr><td class="fw-600">Total Sent</td><td class="text-end">{{ number_format($campaign->sent_count) }}</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Details</h5>
        </div>
        <div class="bp-card-body">
          <div class="d-flex justify-content-between fs-13 mb-2">
            <span class="text-muted">Status</span>
            <span>
              @switch($campaign->status)
                @case('completed') <span class="bp-badge bp-badge-success">Completed</span> @break
                @case('scheduled') <span class="bp-badge bp-badge-warning">Scheduled</span> @break
                @case('sending') <span class="bp-badge bp-badge-info">Sending</span> @break
                @case('failed') <span class="bp-badge bp-badge-danger">Failed</span> @break
                @default <span class="bp-badge bp-badge-dark">Draft</span>
              @endswitch
            </span>
          </div>
          <div class="d-flex justify-content-between fs-13 mb-2">
            <span class="text-muted">Gateway</span>
            <span class="fw-600">BulkSMSBD</span>
          </div>
          <div class="d-flex justify-content-between fs-13 mb-2">
            <span class="text-muted">Audience</span>
            <span class="fw-600 text-capitalize">{{ $campaign->audience }}</span>
          </div>
          <div class="d-flex justify-content-between fs-13 mb-2">
            <span class="text-muted">Created</span>
            <span class="fw-600">{{ $campaign->created_at->format('d M Y, h:i A') }}</span>
          </div>
          @if($campaign->scheduled_at)
            <div class="d-flex justify-content-between fs-13 mb-2">
              <span class="text-muted">Scheduled</span>
              <span class="fw-600">{{ $campaign->scheduled_at->format('d M Y, h:i A') }}</span>
            </div>
          @endif
          @if($campaign->sent_at)
            <div class="d-flex justify-content-between fs-13 mb-2">
              <span class="text-muted">Sent</span>
              <span class="fw-600">{{ $campaign->sent_at->format('d M Y, h:i A') }}</span>
            </div>
          @endif
          <div class="d-flex justify-content-between fs-13">
            <span class="text-muted">Created By</span>
            <span class="fw-600">{{ $campaign->creator->name ?? '—' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection
