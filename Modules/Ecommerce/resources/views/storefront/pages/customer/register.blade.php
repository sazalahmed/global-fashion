@extends('ecommerce::storefront.layouts.master')

@section('title', 'Registration')

@section('content')
    <!--========================= SIGN UP PAGE START ==========================-->
    <section class="sign_up mt_70 mb_70">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-xxl-3 col-lg-4 col-xl-4 d-none d-lg-block wow fadeInLeft">
                    <div class="sign_in_img">
                        <img src="{{ storefront_image('auth_register_image', 'website/assets/images/sign_in_img_2.jpg') }}"
                            alt="Sign Up" class="img-fluid w-100">
                    </div>
                </div>
                <div class="col-xxl-5 col-lg-8 col-xl-6 col-sm-10 col-11 wow fadeInRight">
                    <div class="sign_in_form">
                        <h3>Sign Up to Continue 👋</h3>

                        <form action="{{ route('storefront.customer.register.post') }}" method="POST" id="registerForm">
                            @csrf

                            {{-- STEP 1: phone (OTP gate) --}}
                            <div id="regStep1" class="reg-step {{ $otpRequired && $verifiedPhone ? 'bp-hidden' : '' }}">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="single_input">
                                            <label>Phone Number *</label>
                                            <input type="tel" name="phone" id="regPhone" placeholder="01XXXXXXXXX"
                                                value="{{ old('phone', $verifiedPhone) }}" required
                                                @if ($otpRequired && $verifiedPhone) readonly @endif
                                                class="@error('phone') is-invalid @enderror">
                                            @error('phone')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    @if ($otpRequired)
                                        <div class="col-md-12">
                                            <div id="regSendMsg" class="reg-otp-msg"></div>
                                            <button type="button" id="regContinueBtn" class="common_btn">
                                                Continue <i class="fas fa-long-arrow-right"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if ($otpRequired)
                                {{-- STEP 2: OTP code --}}
                                <div id="regStep2" class="reg-step bp-hidden">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="single_input">
                                                <label>Verification Code *</label>
                                                <input type="text" id="regCode" inputmode="numeric" maxlength="6"
                                                    placeholder="6-digit code" class="reg-otp-input">
                                            </div>
                                            <div id="regVerifyMsg" class="reg-otp-msg"></div>
                                        </div>
                                        <div class="col-md-12 d-flex align-items-center gap-3 flex-wrap">
                                            <button type="button" id="regVerifyBtn" class="common_btn">
                                                Verify <i class="fas fa-check"></i>
                                            </button>
                                            <a href="#" id="regResend" class="reg-otp-link">Resend code</a>
                                            <span id="regCountdown" class="reg-otp-muted"></span>
                                            <a href="#" id="regChangeNumber" class="reg-otp-link">Change number</a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- STEP 3: remaining fields --}}
                            <div id="regStep3"
                                class="reg-step {{ $otpRequired && !$verifiedPhone ? 'bp-hidden' : '' }}">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="single_input">
                                            <label>Full Name *</label>
                                            <input type="text" name="name" placeholder="Enter your full name"
                                                value="{{ old('name') }}" class="@error('name') is-invalid @enderror">
                                            @error('name')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="single_input">
                                            <label>Email Address</label>
                                            <input type="email" name="email" placeholder="your@email.com (optional)"
                                                value="{{ old('email') }}" class="@error('email') is-invalid @enderror">
                                            @error('email')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>District</label>
                                            <select name="district" id="regDistrict" class="select_2">
                                                <option value="">Select District</option>
                                                @foreach ($districts as $d)
                                                    <option value="{{ $d->district_name }}"
                                                        {{ old('district') == $d->district_name ? 'selected' : '' }}>
                                                        {{ $d->district_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Thana</label>
                                            <select name="upazila" id="regThana" class="select_2"
                                                data-old="{{ old('upazila') }}">
                                                <option value="">Select District first</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="single_input">
                                            <label>Address</label>
                                            <input type="text" name="address" placeholder="Your address"
                                                value="{{ old('address') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Password *</label>
                                            <input type="password" name="password" placeholder="********"
                                                class="@error('password') is-invalid @enderror">
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Confirm Password *</label>
                                            <input type="password" name="password_confirmation" placeholder="********">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <button type="submit" class="common_btn">Sign Up <i
                                                class="fas fa-long-arrow-right"></i></button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <p class="dont_account">Already have an account? <a
                                href="{{ route('storefront.customer.login') }}">Sign In</a></p>
                        <p class="or">or</p>
                        <ul class="social_login_full">
                            <li>
                                <a href="{{ route('storefront.customer.google.redirect') }}">
                                    <span>
                                        <img src="{{ asset('website/assets/images/google_logo.png') }}" alt="google"
                                            class="img-fluid w-100">
                                    </span>
                                    Google
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--========================= SIGN UP PAGE END ==========================-->
@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            // ── District → Thana cascade (data embedded; storefront has no thana API) ──
            var BD_THANAS = @json($districts->mapWithKeys(fn($d) => [$d->district_name => $d->thanas->pluck('thana_name')->values()]));
            var $regDistrict = $('#regDistrict');
            var $regThana = $('#regThana');
            if ($regDistrict.length) {
                var fillThanas = function(selected) {
                    var list = BD_THANAS[$regDistrict.val()] || [];
                    var html = '<option value="">' + (list.length ? 'Select Thana' : 'Select District first') +
                        '</option>';
                    list.forEach(function(t) {
                        html += '<option value="' + t + '"' + (t === selected ? ' selected' : '') +
                            '>' + t + '</option>';
                    });
                    $regThana.html(html);
                    // Refresh the Select2 widget so the new options render.
                    if ($regThana.hasClass('select2-hidden-accessible')) {
                        $regThana.trigger('change.select2');
                    }
                };
                $regDistrict.on('change', function() {
                    fillThanas(null);
                });
                // Restore on validation bounce-back.
                if ($regDistrict.val()) {
                    fillThanas($regThana.data('old'));
                }
            }

            @if ($otpRequired && !$verifiedPhone)
                var sendOtpUrl = '{{ route('storefront.customer.register.send-otp') }}';
                var verifyOtpUrl = '{{ route('storefront.customer.register.verify-otp') }}';
                var cooldownTimer = null;

                function showMsg($el, text, isError) {
                    $el.text(text).toggleClass('error', !!isError);
                }

                function startCountdown(seconds) {
                    var remaining = seconds;
                    var $btn = $('#regResend');
                    var $cd = $('#regCountdown');
                    $btn.addClass('bp-hidden');
                    $cd.text('Resend in ' + remaining + 's');
                    clearInterval(cooldownTimer);
                    cooldownTimer = setInterval(function() {
                        remaining -= 1;
                        if (remaining <= 0) {
                            clearInterval(cooldownTimer);
                            $cd.text('');
                            $btn.removeClass('bp-hidden');
                        } else {
                            $cd.text('Resend in ' + remaining + 's');
                        }
                    }, 1000);
                }

                function sendOtp() {
                    var phone = $.trim($('#regPhone').val());
                    if (!phone) {
                        showMsg($('#regSendMsg'), 'Please enter your phone number.', true);
                        return;
                    }
                    $('#regContinueBtn, #regResend').prop('disabled', true);
                    $.ajax({
                            url: sendOtpUrl,
                            method: 'POST',
                            data: {
                                phone: phone
                            }
                        })
                        .done(function(res) {
                            if (res.ok) {
                                // Hide the phone + Continue step so only the OTP step shows.
                                $('#regStep1').addClass('bp-hidden');
                                $('#regStep2').removeClass('bp-hidden');
                                showMsg($('#regVerifyMsg'), 'A 6-digit code was sent to ' + phone + '.', false);
                                $('#regCode').trigger('focus');
                                startCountdown(60);
                            } else {
                                showMsg($('#regSendMsg'), res.message || 'Unable to send code.', true);
                            }
                        })
                        .fail(function(xhr) {
                            var msg;
                            if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.phone) {
                                msg = xhr.responseJSON.errors.phone[0]; // real validation error
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message; // server-provided reason
                            } else if (xhr.status === 419) {
                                msg = 'Your session expired. Please refresh the page and try again.';
                            } else {
                                msg = 'Could not send the code. Please try again.';
                            }
                            showMsg($('#regSendMsg'), msg, true);
                        })
                        .always(function() {
                            $('#regContinueBtn, #regResend').prop('disabled', false);
                        });
                }

                function verifyOtp() {
                    var phone = $.trim($('#regPhone').val());
                    var code = $.trim($('#regCode').val());
                    if (code.length !== 6) {
                        showMsg($('#regVerifyMsg'), 'Enter the 6-digit code.', true);
                        return;
                    }
                    $('#regVerifyBtn').prop('disabled', true);
                    $.ajax({
                            url: verifyOtpUrl,
                            method: 'POST',
                            data: {
                                phone: phone,
                                code: code
                            }
                        })
                        .done(function(res) {
                            if (res.ok) {
                                clearInterval(cooldownTimer);
                                $('#regPhone').prop('readonly', true);
                                $('#regStep2').addClass('bp-hidden');
                                $('#regContinueBtn').addClass('bp-hidden');
                                $('#regStep3').removeClass('bp-hidden');
                            } else {
                                showMsg($('#regVerifyMsg'), res.message || 'Invalid code.', true);
                            }
                        })
                        .fail(function() {
                            showMsg($('#regVerifyMsg'), 'Verification failed. Try again.', true);
                        })
                        .always(function() {
                            $('#regVerifyBtn').prop('disabled', false);
                        });
                }

                $('#regContinueBtn').on('click', sendOtp);
                $('#regVerifyBtn').on('click', verifyOtp);
                $('#regResend').on('click', function(e) {
                    e.preventDefault();
                    sendOtp();
                });
                $('#regChangeNumber').on('click', function(e) {
                    e.preventDefault();
                    clearInterval(cooldownTimer);
                    // Bring back the phone + Continue step, hide the OTP step.
                    $('#regStep1').removeClass('bp-hidden');
                    $('#regPhone').prop('readonly', false).trigger('focus');
                    $('#regStep2').addClass('bp-hidden');
                    $('#regStep3').addClass('bp-hidden');
                    $('#regCode').val('');
                    showMsg($('#regVerifyMsg'), '', false);
                    showMsg($('#regSendMsg'), '', false);
                });
                $('#regCode').on('keypress', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        verifyOtp();
                    }
                });
            @endif
        });
    </script>
@endpush
