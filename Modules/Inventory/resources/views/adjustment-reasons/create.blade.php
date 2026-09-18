@extends('core::layouts.master')

@section('title', __('New Adjustment Reason'))
@section('page-title', __('New Adjustment Reason'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('inventory.adjustments') }}">Adjustments</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('inventory.adjustment-reasons.index') }}">Reasons</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>New</span>
@endsection

@section('page-actions')
    <a href="{{ route('inventory.adjustment-reasons.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Reasons
    </a>
@endsection

@section('content')

    <form action="{{ route('inventory.adjustment-reasons.store') }}" method="POST">
        @csrf
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-comment-dots me-2"></i>Adjustment Reason Details</h5>
            </div>
            <div class="bp-card-body">
                @include('inventory::adjustment-reasons._form', ['reason' => null])
            </div>
            <div class="bp-card-footer text-end">
                <a href="{{ route('inventory.adjustment-reasons.index') }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Reason</button>
            </div>
        </div>
    </form>

@endsection
