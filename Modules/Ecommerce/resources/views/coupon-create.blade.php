@extends('core::layouts.master')

@section('title', __('Create Coupon — Website'))
@section('page-title', __('Create Coupon'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.coupons') }}">Coupons</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Create</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.coupons') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Coupons
    </a>
@endsection

@section('content')

    <form action="{{ route('ecommerce.coupons.store') }}" method="POST" id="couponForm">
        @csrf
        @include('ecommerce::_coupon-form-fields', ['coupon' => null, 'submitLabel' => 'Create Coupon'])
    </form>

@endsection

@include('ecommerce::_coupon-form-script')
