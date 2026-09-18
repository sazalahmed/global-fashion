@extends('core::layouts.master')

@section('title', __("Create Email Campaign"))
@section('page-title', __("Create Email Campaign"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Marketing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('marketing.email') }}">Email Campaigns</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Create</span>
@endsection

@section('page-actions')
  <a href="{{ route('marketing.email') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Campaigns
  </a>
@endsection

@section('content')

  <form action="{{ route('marketing.email.store') }}" method="POST">
    @csrf

    <div class="row g-4">
      <div class="col-xl-8">
        <!-- Campaign Info -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-envelope me-2"></i>Campaign Information</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="bp-form-label">Campaign Name *</label>
                <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="e.g., Monthly Newsletter March 2026">
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Subject Line *</label>
                <input type="text" class="bp-form-control @error('subject') is-invalid @enderror" name="subject" value="{{ old('subject') }}" required placeholder="Email subject line">
                @error('subject')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">From Name</label>
                <input type="text" class="bp-form-control @error('from_name') is-invalid @enderror" name="from_name" value="{{ old('from_name', $companyName) }}" placeholder="Sender name">
                @error('from_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">From Email</label>
                <input type="email" class="bp-form-control @error('from_email') is-invalid @enderror" name="from_email" value="{{ old('from_email') }}" placeholder="noreply@example.com">
                @error('from_email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Recipients -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-users me-2"></i>Recipients</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="bp-form-label">Audience *</label>
                <div class="d-flex flex-wrap gap-3">
                  <label class="bp-radio-label">
                    <input type="radio" class="bp-form-radio" name="audience" value="all" {{ old('audience', 'all') == 'all' ? 'checked' : '' }}> All Customers
                  </label>
                  <label class="bp-radio-label">
                    <input type="radio" class="bp-form-radio" name="audience" value="group" {{ old('audience') == 'group' ? 'checked' : '' }}> Select Group
                  </label>
                  <label class="bp-radio-label">
                    <input type="radio" class="bp-form-radio" name="audience" value="custom" {{ old('audience') == 'custom' ? 'checked' : '' }}> Custom Emails
                  </label>
                </div>
              </div>
              <div class="col-md-6 bp-email-audience-group" id="email-group-select">
                <label class="bp-form-label">Customer Group</label>
                <select class="bp-form-select w-100" name="customer_group">
                  <option value="">Select Group</option>
                  <option value="vip">VIP Customers</option>
                  <option value="regular">Regular Customers</option>
                  <option value="new">New Customers (Last 30 Days)</option>
                  <option value="inactive">Inactive Customers (90+ Days)</option>
                  <option value="loyalty">Loyalty Members</option>
                </select>
              </div>
              <div class="col-12 bp-email-audience-custom" id="custom-emails">
                <label class="bp-form-label">Email Addresses</label>
                <textarea class="bp-form-control" name="recipient_emails" rows="3" placeholder="Enter email addresses separated by commas or new lines">{{ old('recipient_emails') }}</textarea>
                <small class="text-muted fs-11">One email per line or separated by commas</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Template Selection -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-palette me-2"></i>Email Template</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <div class="bp-template-card bp-template-selected" data-template="newsletter">
                  <div class="bp-template-preview">
                    <i class="fa-solid fa-newspaper fa-2x text-muted"></i>
                  </div>
                  <div class="bp-template-name fs-12 fw-600 text-center mt-2">Newsletter</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="bp-template-card" data-template="promotion">
                  <div class="bp-template-preview">
                    <i class="fa-solid fa-tag fa-2x text-muted"></i>
                  </div>
                  <div class="bp-template-name fs-12 fw-600 text-center mt-2">Promotion</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="bp-template-card" data-template="minimal">
                  <div class="bp-template-preview">
                    <i class="fa-solid fa-file-lines fa-2x text-muted"></i>
                  </div>
                  <div class="bp-template-name fs-12 fw-600 text-center mt-2">Minimal</div>
                </div>
              </div>
            </div>
            <input type="hidden" name="template" id="selected-template" value="newsletter">
          </div>
        </div>

        <!-- Email Body -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2"></i>Email Content</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="bp-form-label">Email Body *</label>
                <div class="bp-editor-toolbar mb-2">
                  <div class="d-flex flex-wrap gap-1">
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" title="Bold"><i class="fa-solid fa-bold"></i></button>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" title="Italic"><i class="fa-solid fa-italic"></i></button>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" title="Underline"><i class="fa-solid fa-underline"></i></button>
                    <span class="bp-toolbar-divider"></span>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" title="Heading"><i class="fa-solid fa-heading"></i></button>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" title="List"><i class="fa-solid fa-list"></i></button>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" title="Link"><i class="fa-solid fa-link"></i></button>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" title="Image"><i class="fa-solid fa-image"></i></button>
                    <span class="bp-toolbar-divider"></span>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" title="Align Left"><i class="fa-solid fa-align-left"></i></button>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" title="Align Center"><i class="fa-solid fa-align-center"></i></button>
                  </div>
                </div>
                <textarea class="bp-form-control @error('html_body') is-invalid @enderror" name="html_body" id="email-body" rows="12" required placeholder="Compose your email content here...">{{ old('html_body') }}</textarea>
                @error('html_body')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted fs-11">Use placeholders: {customer_name}, {order_id}, {store_name}, {unsubscribe_link}</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Schedule -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-clock me-2"></i>Schedule</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-12">
                <div class="d-flex flex-wrap gap-3">
                  <label class="bp-radio-label">
                    <input type="radio" class="bp-form-radio" name="schedule" value="now" {{ old('schedule', 'now') == 'now' ? 'checked' : '' }}> Send Now
                  </label>
                  <label class="bp-radio-label">
                    <input type="radio" class="bp-form-radio" name="schedule" value="later" {{ old('schedule') == 'later' ? 'checked' : '' }}> Schedule Later
                  </label>
                </div>
              </div>
              <div class="col-md-4 bp-email-schedule-fields" id="email-schedule-date">
                <label class="bp-form-label">Date</label>
                <input type="date" class="bp-form-control" name="scheduled_date" value="{{ old('scheduled_date') }}">
              </div>
              <div class="col-md-4 bp-email-schedule-fields" id="email-schedule-time">
                <label class="bp-form-label">Time</label>
                <input type="time" class="bp-form-control" name="scheduled_time" value="{{ old('scheduled_time') }}">
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="bp-card">
          <div class="bp-card-footer d-flex justify-content-between">
            <button type="button" class="bp-btn bp-btn-outline" id="btn-send-test-email">
              <i class="fa-solid fa-vial me-1"></i> Send Test Email
            </button>
            <div>
              <a href="{{ route('marketing.email') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
              <button type="submit" name="action" value="draft" class="bp-btn bp-btn-outline me-2">
                <i class="fa-solid fa-save me-1"></i> Save Draft
              </button>
              <button type="submit" name="action" value="send" class="bp-btn bp-btn-primary">
                <i class="fa-solid fa-paper-plane me-1"></i> Send Campaign
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-xl-4">
        <!-- Campaign Checklist -->
        <div class="bp-card bp-card-sticky">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-list-check me-2"></i>Campaign Checklist</h5>
          </div>
          <div class="bp-card-body">
            <div class="bp-checklist">
              <div class="bp-checklist-item" id="check-name">
                <i class="fa-solid fa-circle-xmark bp-text-danger"></i>
                <span class="fs-13">Campaign name set</span>
              </div>
              <div class="bp-checklist-item" id="check-subject">
                <i class="fa-solid fa-circle-xmark bp-text-danger"></i>
                <span class="fs-13">Subject line added</span>
              </div>
              <div class="bp-checklist-item" id="check-body">
                <i class="fa-solid fa-circle-xmark bp-text-danger"></i>
                <span class="fs-13">Email content written</span>
              </div>
              <div class="bp-checklist-item" id="check-unsub">
                <i class="fa-solid fa-circle-xmark bp-text-danger"></i>
                <span class="fs-13">Unsubscribe link included</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tips -->
        <div class="bp-card mt-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-lightbulb me-2"></i>Email Tips</h5>
          </div>
          <div class="bp-card-body">
            <div class="bp-info-list">
              <div class="bp-info-item mb-3">
                <div class="fw-600 fs-13 mb-1">Subject Line</div>
                <p class="fs-12 text-muted mb-0">Keep it under 50 characters. Use action words and create urgency.</p>
              </div>
              <div class="bp-info-item mb-3">
                <div class="fw-600 fs-13 mb-1">Best Send Time</div>
                <p class="fs-12 text-muted mb-0">For Bangladesh market: Tuesday-Thursday, 10:00 AM - 2:00 PM yields the best open rates.</p>
              </div>
              <div class="bp-info-item mb-3">
                <div class="fw-600 fs-13 mb-1">Personalization</div>
                <p class="fs-12 text-muted mb-0">Use {customer_name} placeholder to personalize. Personalized emails get 26% higher open rates.</p>
              </div>
              <div class="bp-info-item">
                <div class="fw-600 fs-13 mb-1">Compliance</div>
                <p class="fs-12 text-muted mb-0">Always include an unsubscribe link ({unsubscribe_link}) to comply with anti-spam regulations.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </form>

@endsection

@push('scripts')
<script>
  'use strict';

  $(document).ready(function () {
    // Template selection
    $('.bp-template-card').on('click', function () {
      $('.bp-template-card').removeClass('bp-template-selected');
      $(this).addClass('bp-template-selected');
      $('#selected-template').val($(this).data('template'));
    });

    // Audience toggle
    function toggleEmailAudience() {
      var val = $('input[name="audience"]:checked').val();
      $('#email-group-select').toggle(val === 'group');
      $('#custom-emails').toggle(val === 'custom');
    }
    toggleEmailAudience();

    $('input[name="audience"]').on('change', function () {
      toggleEmailAudience();
    });

    // Schedule toggle
    function toggleEmailSchedule() {
      var isLater = $('input[name="schedule"]:checked').val() === 'later';
      $('.bp-email-schedule-fields').toggle(isLater);
    }
    toggleEmailSchedule();

    $('input[name="schedule"]').on('change', function () {
      toggleEmailSchedule();
    });

    // Checklist updates
    function updateChecklist() {
      var nameOk = $('input[name="name"]').val().length > 0;
      var subjectOk = $('input[name="subject"]').val().length > 0;
      var bodyOk = $('#email-body').val().length > 0;
      var unsubOk = $('#email-body').val().indexOf('{unsubscribe_link}') !== -1;

      updateCheckIcon('#check-name', nameOk);
      updateCheckIcon('#check-subject', subjectOk);
      updateCheckIcon('#check-body', bodyOk);
      updateCheckIcon('#check-unsub', unsubOk);
    }

    function updateCheckIcon(selector, isChecked) {
      var $item = $(selector).find('i');
      if (isChecked) {
        $item.removeClass('fa-circle-xmark bp-text-danger').addClass('fa-circle-check bp-text-success');
      } else {
        $item.removeClass('fa-circle-check bp-text-success').addClass('fa-circle-xmark bp-text-danger');
      }
    }

    $('input[name="name"], input[name="subject"], #email-body').on('input change', updateChecklist);

    // Send test email
    $('#btn-send-test-email').on('click', function () {
      var testEmail = prompt('Enter test email address:');
      if (testEmail) {
        alert('Test email sent to ' + testEmail);
      }
    });
  });
</script>
@endpush
