@extends('core::layouts.master')

@section('title', __('Meta Ads Integration'))
@section('page-title', __('Meta Ads Integration'))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('adspend.index') }}">Ad Spend</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Meta Ads</span>
@endsection

@section('page-actions')
  @bpCan('marketing.edit')
    <form action="{{ route('adspend.meta.sync') }}" method="POST" class="d-inline">
      @csrf
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-brands fa-meta me-1"></i> Sync from Meta</button>
    </form>
  @endbpCan
  <a href="{{ route('adspend.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

  <div class="row g-3">
    <div class="col-lg-8">
      <form action="{{ route('adspend.meta.save') }}" method="POST">
        @csrf

        <div class="bp-card">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-brands fa-meta me-2" style="color:#1877F2"></i>Meta Marketing API Credentials</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="bp-form-label">App ID</label>
                <input type="text" class="bp-form-control" name="app_id" value="{{ old('app_id', $config['app_id']) }}" placeholder="123456789012345">
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">App Secret</label>
                <input type="text" class="bp-form-control" name="app_secret" value="{{ old('app_secret', $config['app_secret']) }}" placeholder="••••••••••••••">
                <div class="fs-11 text-muted mt-1">Used for verifying webhook signatures (optional for basic sync).</div>
              </div>
              <div class="col-12">
                <label class="bp-form-label">System User Access Token *</label>
                <textarea class="bp-form-control" name="access_token" rows="3" placeholder="EAAxxxxxxxxxxxx...">{{ old('access_token', $config['access_token']) }}</textarea>
                <div class="fs-11 text-muted mt-1">
                  Long-lived System User token with <code>ads_read</code> scope (minimum). Generate at Business Manager →
                  Business Settings → System Users → Generate New Token.
                </div>
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Ad Account ID *</label>
                <input type="text" class="bp-form-control" name="ad_account_id" value="{{ old('ad_account_id', $config['ad_account_id']) }}" placeholder="act_1234567890 or 1234567890">
                <div class="fs-11 text-muted mt-1">The <code>act_</code> prefix is added automatically if missing.</div>
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Last Synced</label>
                <input type="text" class="bp-form-control" value="{{ $config['last_synced_at'] ?: 'Never' }}" readonly>
              </div>
            </div>
          </div>
          <div class="bp-card-footer d-flex justify-content-between">
            <button type="button" id="testConnBtn" class="bp-btn bp-btn-outline"><i class="fa-solid fa-plug me-1"></i> Test Connection</button>
            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Credentials</button>
          </div>
        </div>
      </form>

      <div id="testConnResult" class="alert d-none mt-3"></div>
    </div>

    <div class="col-lg-4">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>How to set up</h5>
        </div>
        <div class="bp-card-body fs-13">
          <ol class="ps-3 mb-0">
            <li class="mb-2">Go to <a href="https://developers.facebook.com/apps" target="_blank">developers.facebook.com/apps</a> and create a Business app.</li>
            <li class="mb-2">Add the <strong>Marketing API</strong> product.</li>
            <li class="mb-2">Copy the App ID + App Secret from the app's Basic Settings.</li>
            <li class="mb-2">Open <a href="https://business.facebook.com/settings" target="_blank">business.facebook.com/settings</a> → System Users → create one (Admin role).</li>
            <li class="mb-2">Assign your Ad Account to the System User.</li>
            <li class="mb-2">Generate a token with <code>ads_read</code> + <code>ads_management</code> scopes (token never expires).</li>
            <li class="mb-2">Paste the token here, plus the Ad Account ID (<code>act_xxxxxxxxx</code>).</li>
            <li>Click <strong>Test Connection</strong> → if it lists your ad accounts, click <strong>Sync from Meta</strong>.</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
'use strict';
$(function () {
  $('#testConnBtn').on('click', function () {
    var $btn = $(this);
    var $result = $('#testConnResult');
    $result.addClass('d-none').removeClass('alert-success alert-danger');
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Testing...');

    $.ajax({
      url: '{{ route("adspend.meta.test") }}',
      method: 'POST',
      data: { _token: '{{ csrf_token() }}' },
    }).done(function (res) {
      $result.removeClass('d-none')
             .addClass(res.success ? 'alert-success' : 'alert-danger')
             .html('<i class="fa-solid ' + (res.success ? 'fa-circle-check' : 'fa-circle-xmark') + ' me-1"></i> ' + res.message);
    }).fail(function (xhr) {
      $result.removeClass('d-none').addClass('alert-danger').text('Connection failed: ' + xhr.statusText);
    }).always(function () {
      $btn.prop('disabled', false).html('<i class="fa-solid fa-plug me-1"></i> Test Connection');
    });
  });
});
</script>
@endpush
