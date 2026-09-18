@extends('core::layouts.auth')

@section('title', __("Reset Password"))

@section('left-tagline')
Create a strong<br>new password
@endsection

@section('left-tagline-sub')
Your new password should be unique and strong to keep your admin account secure.
@endsection

@section('left-features')
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-key"></i></div>
          <div class="bp-auth-feat-text">
            <strong>8+ Characters</strong>
            <span>Minimum length requirement</span>
          </div>
        </li>
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-font"></i></div>
          <div class="bp-auth-feat-text">
            <strong>Mixed Case</strong>
            <span>Uppercase &amp; lowercase letters</span>
          </div>
        </li>
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-star"></i></div>
          <div class="bp-auth-feat-text">
            <strong>Numbers &amp; Symbols</strong>
            <span>At least one of each required</span>
          </div>
        </li>
@endsection

@section('content')

      <!-- ── Form view ── -->
      <div id="resetView">

        <a href="{{ route('password.request') }}" class="bp-auth-back">
          <i class="fa-solid fa-arrow-left"></i> Back
        </a>

        <div class="bp-auth-form-header">
          <div class="bp-auth-form-title">Set New Password</div>
          <div class="bp-auth-form-sub">Choose a strong password you haven't used before.</div>
        </div>

        <!-- JS-driven error -->
        <div class="bp-auth-alert bp-auth-alert-error d-none" id="resetError">
          <i class="fa-solid fa-circle-exclamation bp-auth-alert-icon"></i>
          <span id="resetErrorMsg">Passwords do not match.</span>
        </div>

        <!-- Server-side validation errors -->
        @if($errors->any())
        <div class="bp-auth-alert bp-auth-alert-error">
          <i class="fa-solid fa-circle-exclamation bp-auth-alert-icon"></i>
          <span>
            @foreach($errors->all() as $error)
              {{ $error }}<br>
            @endforeach
          </span>
        </div>
        @endif

        <form id="resetForm" action="{{ route('password.update') }}" method="POST" novalidate>
          @csrf
          <input type="hidden" name="email" value="{{ request('email') }}">
          <input type="hidden" name="token" value="{{ request('token') }}">

          <!-- New Password -->
          <div class="bp-af">
            <label for="newPw">New Password</label>
            <div class="bp-af-wrap">
              <i class="fa-solid fa-lock af-icon"></i>
              <input type="password" id="newPw" name="password" placeholder="Create a strong password" autocomplete="new-password" required>
              <button type="button" class="af-eye" id="toggleNew" tabindex="-1">
                <i class="fa-solid fa-eye" id="newEyeIcon"></i>
              </button>
            </div>
            @error('password')
            <div class="bp-af-error">{{ $message }}</div>
            @enderror
            <!-- Strength bar -->
            <div class="bp-pw-strength" id="strengthWrap">
              <div class="bp-pw-strength-bar">
                <div class="bp-pw-strength-fill" id="strengthFill"></div>
              </div>
              <span class="bp-pw-strength-label" id="strengthLabel"></span>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="bp-af">
            <label for="confirmPw">Confirm New Password</label>
            <div class="bp-af-wrap">
              <i class="fa-solid fa-lock-open af-icon"></i>
              <input type="password" id="confirmPw" name="password_confirmation" placeholder="Re-enter your password" autocomplete="new-password" required>
              <button type="button" class="af-eye" id="toggleConfirm" tabindex="-1">
                <i class="fa-solid fa-eye" id="confirmEyeIcon"></i>
              </button>
            </div>
          </div>

          <!-- Rule pills -->
          <div class="bp-pw-rules">
            <span class="bp-pw-rule" id="rule-len"><i class="fa-solid fa-circle-xmark"></i>8+ chars</span>
            <span class="bp-pw-rule" id="rule-upper"><i class="fa-solid fa-circle-xmark"></i>Uppercase</span>
            <span class="bp-pw-rule" id="rule-lower"><i class="fa-solid fa-circle-xmark"></i>Lowercase</span>
            <span class="bp-pw-rule" id="rule-num"><i class="fa-solid fa-circle-xmark"></i>Number</span>
            <span class="bp-pw-rule" id="rule-special"><i class="fa-solid fa-circle-xmark"></i>Symbol</span>
          </div>

          <button type="submit" class="bp-auth-submit" id="resetBtn">
            <i class="fa-solid fa-key"></i>
            Reset Password
          </button>

        </form>

      </div>

      <!-- ── Success view ── -->
      <div id="successView" class="d-none">
        <div class="bp-auth-success-wrap">
          <div class="bp-auth-success-icon">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div class="bp-auth-form-title bp-auth-success-title">Password Changed!</div>
          <div class="bp-auth-form-sub bp-auth-success-sub">
            Your password has been updated successfully.<br>
            You can now sign in with your new credentials.
          </div>
          <a href="{{ route('login') }}" class="bp-auth-submit bp-auth-success-link">
            <i class="fa-solid fa-right-to-bracket"></i>
            Go to Login
          </a>
        </div>
      </div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {

  /* ── Eye toggles ── */
  function makeEye(btnId, inputId, iconId) {
    $('#' + btnId).on('click', function () {
      var $i = $('#' + inputId);
      var isText = $i.attr('type') === 'text';
      $i.attr('type', isText ? 'password' : 'text');
      $('#' + iconId).toggleClass('fa-eye', isText).toggleClass('fa-eye-slash', !isText);
    });
  }
  makeEye('toggleNew',     'newPw',     'newEyeIcon');
  makeEye('toggleConfirm', 'confirmPw', 'confirmEyeIcon');

  /* ── Password rules ── */
  var rules = {
    len:     function (p) { return p.length >= 8; },
    upper:   function (p) { return /[A-Z]/.test(p); },
    lower:   function (p) { return /[a-z]/.test(p); },
    num:     function (p) { return /[0-9]/.test(p); },
    special: function (p) { return /[^A-Za-z0-9]/.test(p); }
  };

  $('#newPw').on('input', function () {
    var pw = $(this).val();
    $('#resetError').addClass('d-none');

    if (!pw) { $('#strengthWrap').hide(); return; }
    $('#strengthWrap').show();

    var passed = 0;
    $.each(rules, function (key, fn) {
      var ok = fn(pw);
      if (ok) passed++;
      var $r = $('#rule-' + key);
      $r.toggleClass('ok', ok);
      $r.find('i').removeClass('fa-circle-xmark fa-circle-check').addClass(ok ? 'fa-circle-check' : 'fa-circle-xmark');
    });

    var pct   = (passed / 5) * 100;
    var color = passed <= 1 ? 'var(--bp-danger)' : passed <= 3 ? 'var(--bp-warning)' : 'var(--bp-success)';
    var label = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'][passed] || 'Weak';

    $('#strengthFill').css({ width: pct + '%', 'background-color': color });
    $('#strengthLabel').text(label).css('color', color);
  });

  $('#confirmPw').on('input', function () { $('#resetError').addClass('d-none'); });

  /* ── Submit ── */
  $('#resetForm').on('submit', function (e) {
    var newPw  = $('#newPw').val();
    var confPw = $('#confirmPw').val();

    var allOk = $.map(rules, function (fn) { return fn(newPw); }).indexOf(false) === -1;
    if (!allOk) {
      e.preventDefault();
      showErr('Password must be 8+ characters with uppercase, lowercase, number, and symbol.');
      return;
    }
    if (newPw !== confPw) {
      e.preventDefault();
      showErr('Passwords do not match. Please re-enter.');
      return;
    }

    var $btn = $('#resetBtn');
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Updating…');

    /* Allow form to submit to server */
    return true;
  });

  function showErr(msg) {
    $('#resetErrorMsg').text(msg);
    $('#resetError').removeClass('d-none');
  }

  /* ── Show success view if redirected with success ── */
  @if(session('password_reset_success'))
  $('#resetView').addClass('d-none');
  $('#successView').removeClass('d-none');
  @endif

});
</script>
@endpush
