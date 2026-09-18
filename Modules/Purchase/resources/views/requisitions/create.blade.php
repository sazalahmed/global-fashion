@extends('core::layouts.master')

@section('title', __('New Requisition'))
@section('page-title', __('New Requisition'))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('requisitions.index') }}">Requisitions</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>New</span>
@endsection

@section('page-actions')
  <a href="{{ route('requisitions.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')
  <form action="{{ route('requisitions.store') }}" method="POST">
    @csrf
    @include('purchase::requisitions._form', ['requisition' => null])
  </form>
@endsection
