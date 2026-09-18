@extends('ecommerce::storefront.layouts.master')

@section('title', 'Edit Profile')
@section('breadcrumb_title', 'Edit Profile')

@section('breadcrumb')
    <li><a href="{{ route('storefront.customer.profile') }}">Dashboard</a></li>
    <li>Edit Profile</li>
@endsection

@section('content')
    <section class="dashboard mb_70">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 wow fadeInUp">
                    @include('ecommerce::storefront.partials.dashboard-sidebar')
                </div>
                <div class="col-lg-9">
                    <div class="dashboard_content mt_70">
                        <h3 class="dashboard_title">Edit Information
                            <a class="common_btn cancel_edit" href="{{ route('storefront.customer.profile') }}">Cancel</a>
                        </h3>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="dashboard_profile_info_edit">
                            <form class="info_edit_form" action="{{ route('storefront.customer.profile.update') }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-12">
                                        <div class="bp-avatar-upload">
                                            <div class="bp-avatar-ring m-0">
                                                <img id="profilePhotoPreview"
                                                    src="{{ $customer->photo ? upload_url($customer->photo) : asset('website/assets/images/dashboard_user_img.jpg') }}"
                                                    alt="{{ $customer->name }}">
                                                <label for="profilePhotoInput" class="bp-avatar-cam" title="Change photo">
                                                    <i class="fas fa-camera"></i>
                                                </label>
                                            </div>
                                            <input type="file" id="profilePhotoInput" name="photo"
                                                accept="image/jpeg,image/png,image/webp"
                                                class="d-none @error('photo') is-invalid @enderror">
                                            <small class="text-muted d-block mt-2 mb-3">JPG, PNG or WEBP. Max 2MB.</small>
                                            @error('photo')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Name *</label>
                                            <input type="text" name="name" value="{{ old('name', $customer->name) }}"
                                                required class="@error('name') is-invalid @enderror">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Email</label>
                                            <input type="email" name="email"
                                                value="{{ old('email', $customer->email) }}" placeholder="your@email.com"
                                                class="@error('email') is-invalid @enderror">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Phone</label>
                                            <input type="text" value="{{ $customer->phone }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>District</label>
                                            <select name="district" id="peDistrict" class="select_2">
                                                <option value="">Select District</option>
                                                @foreach ($districts as $d)
                                                    <option value="{{ $d->district_name }}"
                                                        {{ old('district', $customer->district) == $d->district_name ? 'selected' : '' }}>
                                                        {{ $d->district_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Thana</label>
                                            <select name="upazila" id="peThana" class="select_2"
                                                data-old="{{ old('upazila', $customer->upazila) }}">
                                                <option value="">Select District first</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Address</label>
                                            <input type="text" name="address"
                                                value="{{ old('address', $customer->address) }}"
                                                placeholder="Your address">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="single_input">
                                            <label>Shipping Address</label>
                                            <input type="text" name="shipping_address"
                                                value="{{ old('shipping_address', $customer->shipping_address) }}"
                                                placeholder="Shipping address (if different)">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="common_btn">Update Profile <i
                                                class="fas fa-long-arrow-right"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            // Live preview of the selected profile photo (Bug_25)
            $('#profilePhotoInput').on('change', function() {
                var file = this.files && this.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#profilePhotoPreview').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            });

            // ── District → Thana cascade (data embedded; storefront has no thana API) ──
            var BD_THANAS = @json($districts->mapWithKeys(fn($d) => [$d->district_name => $d->thanas->pluck('thana_name')->values()]));
            var $peDistrict = $('#peDistrict');
            var $peThana = $('#peThana');
            if ($peDistrict.length) {
                var fillThanas = function(selected) {
                    var list = BD_THANAS[$peDistrict.val()] || [];
                    var html = '<option value="">' + (list.length ? 'Select Thana' : 'Select District first') +
                        '</option>';
                    list.forEach(function(t) {
                        html += '<option value="' + t + '"' + (t === selected ? ' selected' : '') +
                            '>' + t + '</option>';
                    });
                    $peThana.html(html);
                    if ($peThana.hasClass('select2-hidden-accessible')) {
                        $peThana.trigger('change.select2');
                    }
                };
                $peDistrict.on('change', function() {
                    fillThanas(null);
                });
                if ($peDistrict.val()) {
                    fillThanas($peThana.data('old'));
                }
            }
        });
    </script>
@endpush
