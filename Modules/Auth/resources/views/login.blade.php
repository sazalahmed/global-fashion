@extends('core::layouts.auth')

@section('title', __("Admin Login"))

@section('left-tagline')
All-in-one business<br>management system
@endsection

@section('left-tagline-sub')
Built for Bangladeshi SMBs — manage your store, stock, accounts, and online orders from one place.
@endsection

@section('left-features')
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-cash-register"></i></div>
          <div class="bp-auth-feat-text">
            <strong>POS Terminal</strong>
            <span>Fast, barcode-ready sales counter</span>
          </div>
        </li>
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
          <div class="bp-auth-feat-text">
            <strong>Inventory &amp; Stock</strong>
            <span>Real-time stock tracking across warehouses</span>
          </div>
        </li>
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
          <div class="bp-auth-feat-text">
            <strong>Accounting &amp; VAT</strong>
            <span>Mushak 6.3, ledger, P&amp;L reports</span>
          </div>
        </li>
        <li>
          <div class="bp-auth-feat-icon"><i class="fa-solid fa-truck-fast"></i></div>
          <div class="bp-auth-feat-text">
            <strong>Courier &amp; Delivery</strong>
            <span>Pathao, Steadfast, eCourier integrated</span>
          </div>
        </li>
@endsection

@section('content')

      <!-- Header -->
      <div class="bp-auth-form-header">
        <div class="bp-auth-form-title">Welcome back</div>
        <div class="bp-auth-form-sub">Sign in to your admin account to continue</div>
      </div>

      <!-- Error alert (JS-driven) -->
      <div class="bp-auth-alert bp-auth-alert-error d-none" id="loginError">
        <i class="fa-solid fa-circle-exclamation bp-auth-alert-icon"></i>
        <span id="loginErrorMsg">Invalid email or password. Please try again.</span>
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

      <!-- Form -->
      <form id="loginForm" action="{{ route('login.submit') }}" method="POST" novalidate>
        @csrf

        <!-- Email -->
        <div class="bp-af">
          <label for="loginEmail">Email Address</label>
          <div class="bp-af-wrap">
            <i class="fa-solid fa-envelope af-icon"></i>
            <input type="email" id="loginEmail" name="email" value="{{ old('email') }}" placeholder="Enter your email" autocomplete="username" required>
          </div>
          @error('email')
          <div class="bp-af-error">{{ $message }}</div>
          @enderror
        </div>

        <!-- Password -->
        <div class="bp-af">
          <label for="loginPassword">Password</label>
          <div class="bp-af-wrap">
            <i class="fa-solid fa-lock af-icon"></i>
            <input type="password" id="loginPassword" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="af-eye" id="togglePw" tabindex="-1">
              <i class="fa-solid fa-eye" id="pwEyeIcon"></i>
            </button>
          </div>
          @error('password')
          <div class="bp-af-error">{{ $message }}</div>
          @enderror
        </div>

        <!-- Remember + Forgot -->
        <div class="bp-auth-meta-row">
          <label class="bp-auth-check">
            <input type="checkbox" id="rememberMe" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            Remember me
          </label>
          <a href="{{ route('password.request') }}" class="bp-auth-link">Forgot password?</a>
        </div>

        <!-- Submit -->
        <button type="submit" class="bp-auth-submit" id="loginBtn">
          <i class="fa-solid fa-right-to-bracket"></i>
          Sign In
        </button>

      </form>

      <div class="bp-auth-version">{{ $companyName }} &mdash; Admin Portal &mdash; v2.0</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {

  /* ── Password toggle ── */
  $('#togglePw').on('click', function () {
    var $i = $('#loginPassword');
    var isText = $i.attr('type') === 'text';
    $i.attr('type', isText ? 'password' : 'text');
    $('#pwEyeIcon').toggleClass('fa-eye', isText).toggleClass('fa-eye-slash', !isText);
  });

  /* ── Pre-fill remembered email ── */
  var rem = localStorage.getItem('bpRemember');
  if (rem) { $('#loginEmail').val(rem); $('#rememberMe').prop('checked', true); }

  /* ── Hide error on typing ── */
  $('#loginEmail, #loginPassword').on('input', function () { $('#loginError').addClass('d-none'); });

  /* ── Form submit ── */
  $('#loginForm').on('submit', function () {
    var email = $('#loginEmail').val().trim();
    var pw    = $('#loginPassword').val();

    if (!email || !pw) {
      showErr('Please enter both email and password.');
      return false;
    }

    /* Remember me (local storage) */
    if ($('#rememberMe').is(':checked')) {
      localStorage.setItem('bpRemember', email);
    } else {
      localStorage.removeItem('bpRemember');
    }

    var $btn = $('#loginBtn');
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Signing in…');

    /* Allow form to submit naturally to server */
    return true;
  });

  function showErr(msg) {
    $('#loginErrorMsg').text(msg);
    $('#loginError').removeClass('d-none');
    $('#loginPassword').val('').focus();
  }

});
</script>
@endpush
