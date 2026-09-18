@extends('core::layouts.master')

@section('title', __("Marketing Overview"))
@section('page-title', __("Marketing Overview"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Marketing</span>
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-comment-sms"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">SMS Campaigns</div>
          <div class="bp-stat-value">{{ number_format($stats['sms_campaigns']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-paper-plane"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">SMS Sent</div>
          <div class="bp-stat-value">{{ number_format($stats['sms_sent']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-envelope"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Email Campaigns</div>
          <div class="bp-stat-value">{{ number_format($stats['email_campaigns']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-star"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Loyalty Members</div>
          <div class="bp-stat-value">{{ number_format($loyaltyStats['active_customers']) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="row g-4">
    <div class="col-xl-4">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-comment-sms me-2"></i>SMS Campaigns</h5>
        </div>
        <div class="bp-card-body">
          <p class="fs-13 text-muted mb-3">Send targeted SMS messages to your customers via BulkSMSBD.</p>
          <div class="d-flex gap-2">
            <a href="{{ route('marketing.sms-campaigns') }}" class="bp-btn bp-btn-outline bp-btn-sm"><i class="fa-solid fa-list me-1"></i> View Campaigns</a>
            @bpCan('marketing.create')
              <a href="{{ route('marketing.sms-campaigns.create') }}" class="bp-btn bp-btn-primary bp-btn-sm"><i class="fa-solid fa-plus me-1"></i> New Campaign</a>
            @endbpCan
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-envelope me-2"></i>Email Campaigns</h5>
        </div>
        <div class="bp-card-body">
          <p class="fs-13 text-muted mb-3">Create and send email newsletters, promotions, and automated campaigns.</p>
          <div class="d-flex gap-2">
            <a href="{{ route('marketing.email') }}" class="bp-btn bp-btn-outline bp-btn-sm"><i class="fa-solid fa-list me-1"></i> View Campaigns</a>
            @bpCan('marketing.create')
              <a href="{{ route('marketing.email.create') }}" class="bp-btn bp-btn-primary bp-btn-sm"><i class="fa-solid fa-plus me-1"></i> New Campaign</a>
            @endbpCan
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-star me-2"></i>Loyalty Program</h5>
        </div>
        <div class="bp-card-body">
          <p class="fs-13 text-muted mb-3">Manage loyalty points, tiers, and reward your returning customers.</p>
          <div class="d-flex gap-2">
            <a href="{{ route('marketing.loyalty') }}" class="bp-btn bp-btn-outline bp-btn-sm"><i class="fa-solid fa-gears me-1"></i> Manage Program</a>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection
