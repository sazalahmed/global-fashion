@extends('core::layouts.master')

@section('title', 'Add Shipping Zone')
@section('page-title', 'Add Shipping Zone')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.shipping') }}">Shipping</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add Zone</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.shipping') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i>Back</a>
@endsection

@section('content')

    <form action="{{ route('ecommerce.shipping.store') }}" method="POST">
        @csrf
        @include('ecommerce::shipping-form', [
            'zone' => null,
            'selectedIds' => [],
            'assignedDistrictIds' => $assignedDistrictIds,
            'districts' => $districts,
        ])
    </form>

@endsection

@push('scripts')
    @include('ecommerce::shipping-form-script')
@endpush
