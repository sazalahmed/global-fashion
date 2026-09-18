@extends('core::layouts.master')

@section('title', __('Edit Requisition'))
@section('page-title', __('Edit Requisition'))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('requisitions.index') }}">Requisitions</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('requisitions.show', $requisition) }}">{{ $requisition->requisition_number }}</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Edit</span>
@endsection

@section('page-actions')
  <a href="{{ route('requisitions.show', $requisition) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')
  <form action="{{ route('requisitions.update', $requisition) }}" method="POST">
    @csrf
    @method('PUT')
    @include('purchase::requisitions._form', ['requisition' => $requisition])
  </form>
@endsection
