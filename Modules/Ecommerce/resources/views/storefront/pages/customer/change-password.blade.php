@extends('ecommerce::storefront.layouts.master')

@section('title', 'Change Password')

@section('content')
    <section class="dashboard mb_70">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 wow fadeInUp">
                    @include('ecommerce::storefront.partials.dashboard-sidebar')
                </div>
                <div class="col-lg-9">
                    <div class="dashboard_content mt_70">
                        <h3 class="dashboard_title">Change Password</h3>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="dashboard_profile_info_edit">
                            <form class="info_edit_form" action="{{ route('storefront.customer.password.update') }}"
                                method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="single_input">
                                            <label>Current Password *</label>
                                            <input type="password" name="current_password" required
                                                class="@error('current_password') is-invalid @enderror">
                                            @error('current_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>New Password *</label>
                                            <input type="password" name="password" required
                                                class="@error('password') is-invalid @enderror">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Confirm New Password *</label>
                                            <input type="password" name="password_confirmation" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="common_btn">Update Password <i
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
