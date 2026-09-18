@extends('core::layouts.auth')

@section('title', __("Forgot Password"))

@section('left-tagline')
Account recovery<br>made simple
@endsection

@section('left-tagline-sub')
Verify your identity with your Authenticator app or a one-time SMS code — no email required.
@endsection

@section('left-features')
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
          <div class="bp-auth-feat-text">
            <strong>Authenticator App</strong>
            <span>Use your Google Authenticator code</span>
          </div>
        </li>
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-comment-sms"></i></div>
          <div class="bp-auth-feat-text">
            <strong>SMS Fallback</strong>
            <span>6-digit code to your phone</span>
          </div>
        </li>
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <div class="bp-auth-feat-text">
            <strong>Secure Reset</strong>
            <span>Codes expire quickly</span>
          </div>
        </li>
@endsection

@section('content')

      <!-- ── STEP 1: Identify ── -->
      <div id="stepIdentify">

        <a href="{{ route('login') }}" class="bp-auth-back">
          <i class="fa-solid fa-arrow-left"></i> Back to Login
        </a>

        <div class="bp-auth-form-header">
          <div class="bp-auth-form-title">Forgot Password?</div>
          <div class="bp-auth-form-sub">Enter your registered email. We'll verify you with your Authenticator app, or text a code to your phone.</div>
        </div>

        <div class="bp-auth-alert bp-auth-alert-error d-none" id="identifyError">
          <i class="fa-solid fa-circle-exclamation bp-auth-alert-icon"></i>
          <span id="identifyErrorMsg"></span>
        </div>

        <form id="identifyForm" novalidate>
          @csrf
          <div class="bp-af">
            <label for="forgotEmail">Email Address</label>
            <div class="bp-af-wrap">
              <i class="fa-solid fa-envelope af-icon"></i>
              <input type="email" id="forgotEmail" name="email" value="{{ old('email') }}" placeholder="admin@bizpospro.com" autocomplete="username" required>
            </div>
          </div>

          <button type="submit" class="bp-auth-submit" id="identifyBtn">
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
            Continue
          </button>
        </form>

        <div class="bp-auth-version">Remembered your password? <a href="{{ route('login') }}" class="bp-auth-link">Sign in</a></div>
      </div>

      <!-- ── STEP 2: Code ── -->
      <div id="stepCode" class="d-none">

        <a href="#" class="bp-auth-back" id="backToIdentify">
          <i class="fa-solid fa-arrow-left"></i> Change Email
        </a>

        <div class="bp-auth-form-header">
          <div class="bp-auth-form-title" id="codeTitle">Enter Code</div>
          <div class="bp-auth-form-sub" id="codeSub"></div>
        </div>

        <div class="bp-auth-alert bp-auth-alert-error d-none" id="codeError">
          <i class="fa-solid fa-circle-exclamation bp-auth-alert-icon"></i>
          <span id="codeErrorMsg"></span>
        </div>

        <form id="codeForm">
          @csrf
          <div class="bp-otp-row" id="otpWrap">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
            <input class="bp-otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
          </div>

          <button type="button" class="bp-auth-submit" id="verifyBtn">
            <i class="fa-solid fa-shield-halved"></i>
            Verify &amp; Continue
          </button>
        </form>

        <div class="bp-auth-resend d-none" id="resendWrap">
          Didn't get the code?&nbsp;
          <button type="button" class="bp-auth-resend-btn" id="resendBtn" disabled>
            Resend (<span id="resendTimer">60</span>s)
          </button>
        </div>
      </div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
  var SEND_URL   = '{{ route("password.send-otp") }}';
  var VERIFY_URL = '{{ route("password.verify") }}';
  var RESET_URL  = '{{ route("password.reset") }}';
  var currentEmail = '';
  var allowResend  = false;

  var $boxes = $('.bp-otp-box');

  function showErr($box, $msg, text) { $msg.text(text); $box.removeClass('d-none'); }

  /* ── Step 1: Identify ── */
  $('#identifyForm').on('submit', function (e) {
    e.preventDefault();
    var email = $('#forgotEmail').val().trim();
    if (!email) { showErr($('#identifyError'), $('#identifyErrorMsg'), 'Please enter your email address.'); return; }

    var $btn = $('#identifyBtn');
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Checking…');

    $.ajax({
      url: SEND_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}', email: email },
      success: function (res) {
        $btn.prop('disabled', false).html('<i class="fa-solid fa-arrow-right-to-bracket"></i> Continue');
        if (res.method === 'unavailable') {
          showErr($('#identifyError'), $('#identifyErrorMsg'),
            "Password reset isn't available for this account — please contact your administrator.");
          return;
        }
        currentEmail = email;
        goToCodeStep(res);
      },
      error: function (xhr) {
        $btn.prop('disabled', false).html('<i class="fa-solid fa-arrow-right-to-bracket"></i> Continue');
        var msg = 'Something went wrong. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
        showErr($('#identifyError'), $('#identifyErrorMsg'), msg);
      }
    });
  });
  $('#forgotEmail').on('input', function () { $('#identifyError').addClass('d-none'); });

  function goToCodeStep(res) {
    if (res.method === 'totp') {
      $('#codeTitle').text('Open your Authenticator app');
      $('#codeSub').text('Enter the current 6-digit code from Google Authenticator.');
      $('#resendWrap').addClass('d-none');
      allowResend = false;
    } else { /* sms or generic */
      $('#codeTitle').text('Enter the SMS code');
      if (res.maskedPhone) {
        $('#codeSub').text('We sent a 6-digit code to ' + res.maskedPhone + '. It expires in 10 minutes.');
      } else {
        $('#codeSub').text('If an account exists, a 6-digit code has been sent. It expires in 10 minutes.');
      }
      $('#resendWrap').removeClass('d-none');
      allowResend = true;
      startCountdown();
    }
    $('#stepIdentify').addClass('d-none');
    $('#stepCode').removeClass('d-none');
    $boxes.val('').first().focus();
  }

  /* ── Back ── */
  $('#backToIdentify').on('click', function (e) {
    e.preventDefault();
    $('#stepCode').addClass('d-none');
    $('#stepIdentify').removeClass('d-none');
  });

  /* Auto-verify once all six digits are present (typing or paste). */
  function autoSubmitIfComplete() {
    var code = $boxes.map(function () { return $(this).val(); }).get().join('');
    if (code.length === 6 && !$('#verifyBtn').prop('disabled')) {
      $('#verifyBtn').trigger('click');
    }
  }

  /* ── OTP box behaviour ── */
  $boxes.on('input', function () {
    var v = $(this).val().replace(/\D/g, ''); $(this).val(v);
    if (v) { var n = +$(this).data('index') + 1; if (n < 6) $boxes.eq(n).focus(); }
    $('#codeError').addClass('d-none');
    autoSubmitIfComplete();
  });
  $boxes.on('keydown', function (e) {
    if (e.key === 'Backspace' && !$(this).val()) {
      var p = +$(this).data('index') - 1; if (p >= 0) $boxes.eq(p).focus().val('');
    }
  });
  $boxes.on('paste', function (e) {
    e.preventDefault();
    var t = (e.originalEvent.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
    t.split('').forEach(function (ch, i) { $boxes.eq(i).val(ch); });
    $boxes.eq(Math.min(t.length, 5)).focus();
    autoSubmitIfComplete();
  });

  /* ── Step 2: Verify ── */
  $('#verifyBtn').on('click', function () {
    var code = $boxes.map(function () { return $(this).val(); }).get().join('');
    if (code.length < 6) { showErr($('#codeError'), $('#codeErrorMsg'), 'Please enter the complete 6-digit code.'); return; }

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Verifying…');

    $.ajax({
      url: VERIFY_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}', email: currentEmail, code: code },
      success: function (res) {
        window.location.href = RESET_URL + '?email=' + encodeURIComponent(currentEmail) + '&token=' + (res.token || '');
      },
      error: function (xhr) {
        var msg = 'Invalid or expired code. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
        showErr($('#codeError'), $('#codeErrorMsg'), msg);
        $boxes.val('').first().focus();
        $btn.prop('disabled', false).html('<i class="fa-solid fa-shield-halved"></i> Verify &amp; Continue');
      }
    });
  });

  /* ── Resend (SMS only) ── */
  var timer;
  function startCountdown() {
    var s = 60;
    $('#resendBtn').prop('disabled', true).html('Resend (<span id="resendTimer">' + s + '</span>s)');
    clearInterval(timer);
    timer = setInterval(function () {
      s--; $('#resendTimer').text(s);
      if (s <= 0) { clearInterval(timer); $('#resendBtn').prop('disabled', false).text('Resend code'); }
    }, 1000);
  }
  $('#resendBtn').on('click', function () {
    if (!allowResend) return;
    $boxes.val('').first().focus(); $('#codeError').addClass('d-none');
    $.ajax({
      url: SEND_URL, method: 'POST',
      data: { _token: '{{ csrf_token() }}', email: currentEmail },
      success: function () { startCountdown(); },
      error: function () { showErr($('#codeError'), $('#codeErrorMsg'), 'Failed to resend. Please try again.'); }
    });
  });
});
</script>
@endpush
