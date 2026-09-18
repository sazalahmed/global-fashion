@extends('core::layouts.master')

@section('title', __('Add Page — Website'))
@section('page-title', __('Add Page'))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.pages.index') }}">Pages</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Add</span>
@endsection

@section('content')
<form action="{{ route('ecommerce.pages.store') }}" method="POST" enctype="multipart/form-data">
  @csrf
  @include('ecommerce::pages._form', ['page' => null])
</form>
@endsection
