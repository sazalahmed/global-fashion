@extends('core::layouts.master')

@section('title', 'Edit Shipping Zone')
@section('page-title', 'Edit Shipping Zone')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.shipping') }}">Shipping</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $zone->name }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.shipping') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i>Back</a>
@endsection

@section('content')

    <form action="{{ route('ecommerce.shipping.update', $zone) }}" method="POST">
        @csrf @method('PUT')
        @include('ecommerce::shipping-form', [
            'zone' => $zone,
            'selectedIds' => $selectedIds,
            'assignedDistrictIds' => $assignedDistrictIds,
            'districts' => $districts,
        ])
    </form>

    {{-- Delete action lives in its own form (different method/route); its button is
         rendered in the shared form footer above via the form="zoneDeleteForm" attribute. --}}
    <form id="zoneDeleteForm" method="POST" action="{{ route('ecommerce.shipping.destroy', $zone) }}"
        onsubmit="return confirm('Delete this shipping zone?')">
        @csrf @method('DELETE')
    </form>

@endsection

@push('scripts')
    @include('ecommerce::shipping-form-script')
@endpush
