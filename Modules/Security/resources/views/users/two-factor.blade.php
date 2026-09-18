@extends('core::layouts.master')

@section('title', __('Two-Factor Authentication'))
@section('page-title', __('Two-Factor Authentication'))
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ __('Two-Factor Authentication') }}</span>
@endsection

@section('content')
<div class="row justify-content-center">
  <div class="col-xxl-8 col-lg-10">
    <div class="bp-card bp-2fa-card">

      @if($user->hasTwoFactorEnabled())
        {{-- ───────── Enabled state ───────── --}}
        <div class="bp-card-body">
          <div class="bp-2fa-hero">
            <div class="bp-2fa-badge bp-2fa-badge-on"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="bp-2fa-pill"><i class="fa-solid fa-circle-check"></i> {{ __('Protected') }}</div>
            <h3>{{ __('Two-factor is on') }}</h3>
            <p>{{ __('Password resets now require a code from your authenticator app instead of an SMS. Keep your app handy — you will need it to recover this account.') }}</p>
          </div>

          <div class="bp-2fa-danger">
            <div class="bp-2fa-danger-head">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <div>
                <div class="bp-2fa-danger-title">{{ __('Turn off two-factor') }}</div>
                <div class="bp-2fa-danger-sub">{{ __('You will fall back to SMS codes for password recovery. Confirm your password to continue.') }}</div>
              </div>
            </div>
            <form method="POST" action="{{ route('security.two-factor.disable') }}" class="bp-2fa-danger-form">
              @csrf
              @method('DELETE')
              <div class="bp-2fa-danger-row">
                <input type="password" name="current_password" class="bp-form-control" placeholder="{{ __('Current password') }}" autocomplete="current-password" required>
                <button type="submit" class="bp-btn bp-btn-danger"><i class="fa-solid fa-power-off me-1"></i>{{ __('Turn off') }}</button>
              </div>
              @error('current_password')<div class="bp-af-error mt-2">{{ $message }}</div>@enderror
            </form>
          </div>
        </div>

      @else
        {{-- ───────── Intro state (not yet set up) ───────── --}}
        <div class="bp-card-body" id="twofaIntro">
          <div class="bp-2fa-hero">
            <div class="bp-2fa-badge bp-2fa-badge-idle"><i class="fa-solid fa-shield-halved"></i></div>
            <h3>{{ __('Add an authenticator app') }}</h3>
            <p>{{ __('Turn on two-factor to protect password recovery. When it is on, resetting your password takes a one-time code from your phone instead of an SMS — so a leaked email or number is not enough to get in.') }}</p>
            <button type="button" class="bp-btn bp-btn-primary bp-btn-lg mt-3" id="enable2faBtn">
              <i class="fa-solid fa-qrcode me-1"></i>{{ __('Set up authenticator') }}
            </button>
            <div class="bp-2fa-apps">{{ __('Works with Google Authenticator, Authy, Microsoft Authenticator, or any TOTP app.') }}</div>
          </div>
        </div>

        {{-- ───────── Setup state (revealed after enable) ───────── --}}
        <div class="d-none" id="twofaSetup">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-mobile-screen-button me-2"></i>{{ __('Finish setup') }}</h5>
          </div>
          <div class="bp-card-body">
            <div class="bp-2fa-steps">

              {{-- Step 1 — Scan --}}
              <div class="bp-2fa-step">
                <div class="bp-2fa-step-eyebrow">{{ __('Step 1') }}</div>
                <div class="bp-2fa-step-title">{{ __('Scan with your app') }}</div>

                <div class="bp-2fa-scan">
                  <span class="bp-2fa-corner tl"></span><span class="bp-2fa-corner tr"></span>
                  <span class="bp-2fa-corner bl"></span><span class="bp-2fa-corner br"></span>
                  <img id="qrImage" src="" alt="{{ __('Two-factor QR code') }}">
                </div>

                <div class="bp-2fa-key-label">{{ __("Can't scan? Enter this key manually") }}</div>
                <div class="bp-2fa-key">
                  <code id="manualKey"></code>
                  <button type="button" class="bp-btn bp-btn-outline bp-btn-sm bp-btn-icon bp-2fa-copy" id="copyKeyBtn" title="{{ __('Copy key') }}">
                    <i class="fa-solid fa-copy"></i>
                  </button>
                </div>
              </div>

              {{-- Step 2 — Confirm --}}
              <div class="bp-2fa-step">
                <div class="bp-2fa-step-eyebrow">{{ __('Step 2') }}</div>
                <div class="bp-2fa-step-title">{{ __('Enter the 6-digit code') }}</div>
                <p class="bp-2fa-step-help">{{ __('Type the current code your authenticator app shows to finish turning on two-factor.') }}</p>

                <div class="bp-auth-alert bp-auth-alert-error d-none" id="confirm2faError">
                  <i class="fa-solid fa-circle-exclamation bp-auth-alert-icon"></i>
                  <span id="confirm2faErrorMsg"></span>
                </div>

                <div class="bp-otp-row bp-2fa-otp" id="confirmOtp">
                  <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0">
                  <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
                  <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
                  <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
                  <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
                  <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
                </div>

                <button type="button" class="bp-btn bp-btn-success w-100 justify-content-center" id="confirm2faBtn">
                  <i class="fa-solid fa-check me-1"></i>{{ __('Verify & turn on') }}
                </button>
              </div>

            </div>
          </div>
        </div>
      @endif

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
'use strict';
$(function () {
  var ENABLE_URL  = '{{ route("security.two-factor.enable") }}';
  var CONFIRM_URL = '{{ route("security.two-factor.confirm") }}';
  var $boxes = $('#confirmOtp .bp-otp-box');

  function showCodeError(text) {
    $('#confirm2faErrorMsg').text(text);
    $('#confirm2faError').removeClass('d-none');
  }

  /* Reveal the QR + setup steps. */
  $('#enable2faBtn').on('click', function () {
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> {{ __("Generating…") }}');
    $.ajax({
      url: ENABLE_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}' },
      success: function (res) {
        // res.qr is a trusted, server-generated data-URI (SVG). Render as an image.
        $('#qrImage').attr('src', res.qr);
        $('#manualKey').text(res.secret);
        $('#twofaIntro').addClass('d-none');
        $('#twofaSetup').removeClass('d-none');
        $boxes.val('').first().trigger('focus');
      },
      error: function () {
        $btn.prop('disabled', false).html('<i class="fa-solid fa-qrcode me-1"></i> {{ __("Set up authenticator") }}');
      }
    });
  });

  /* Copy the manual key. */
  $('#copyKeyBtn').on('click', function () {
    var key = $('#manualKey').text();
    var $btn = $(this);
    var done = function () {
      $btn.html('<i class="fa-solid fa-check"></i>');
      setTimeout(function () { $btn.html('<i class="fa-solid fa-copy"></i>'); }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(key).then(done, function () {});
    } else {
      var t = document.createElement('textarea');
      t.value = key; document.body.appendChild(t); t.select();
      try { document.execCommand('copy'); done(); } catch (e) {}
      document.body.removeChild(t);
    }
  });

  /* Auto-verify once all six digits are present (typing or paste), so the
     user doesn't have to click the button. */
  function autoSubmitIfComplete() {
    var code = $boxes.map(function () { return $(this).val(); }).get().join('');
    if (code.length === 6 && !$('#confirm2faBtn').prop('disabled')) {
      $('#confirm2faBtn').trigger('click');
    }
  }

  /* Segmented OTP box behaviour (auto-advance, backspace, paste). */
  $boxes.on('input', function () {
    var v = $(this).val().replace(/\D/g, ''); $(this).val(v);
    if (v) { var n = +$(this).data('index') + 1; if (n < 6) $boxes.eq(n).trigger('focus'); }
    $('#confirm2faError').addClass('d-none');
    autoSubmitIfComplete();
  });
  $boxes.on('keydown', function (e) {
    if (e.key === 'Backspace' && !$(this).val()) {
      var p = +$(this).data('index') - 1; if (p >= 0) $boxes.eq(p).trigger('focus').val('');
    }
  });
  $boxes.on('paste', function (e) {
    e.preventDefault();
    var t = (e.originalEvent.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
    t.split('').forEach(function (ch, i) { $boxes.eq(i).val(ch); });
    $boxes.eq(Math.min(t.length, 5)).trigger('focus');
    autoSubmitIfComplete();
  });

  /* Confirm the code. */
  $('#confirm2faBtn').on('click', function () {
    var code = $boxes.map(function () { return $(this).val(); }).get().join('');
    if (code.length < 6) { showCodeError('{{ __("Enter the complete 6-digit code.") }}'); return; }

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> {{ __("Verifying…") }}');
    $.ajax({
      url: CONFIRM_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}', code: code },
      success: function () { window.location.reload(); },
      error: function (xhr) {
        var msg = '{{ __("That code is incorrect. Please try again.") }}';
        if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
        showCodeError(msg);
        $boxes.val('').first().trigger('focus');
        $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> {{ __("Verify & turn on") }}');
      }
    });
  });
});
</script>
@endpush
