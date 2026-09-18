@extends('core::layouts.master')

@section('title', __('Edit Borrower'))
@section('page-title', __('Edit Borrower'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('personal-loans.index') }}">Personal Loans</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('personal-loans.show', $borrower) }}">{{ $borrower->name }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit</span>
@endsection

@section('page-actions')
    <a href="{{ route('personal-loans.show', $borrower) }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
    <form action="{{ route('personal-loans.update', $borrower) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Borrower Information</h5>
            </div>
            <div class="bp-card-body">
                @include('loan::personal-loans._form', ['borrower' => $borrower])
            </div>
            <div class="bp-card-footer text-end">
                <a href="{{ route('personal-loans.show', $borrower) }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Update
                    Borrower</button>
            </div>
        </div>
    </form>
@endsection
