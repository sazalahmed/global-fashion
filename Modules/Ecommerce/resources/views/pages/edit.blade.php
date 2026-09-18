@extends('core::layouts.master')

@section('title', __('Edit Page — Website'))
@section('page-title', __('Edit Page'))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.pages.index') }}">Pages</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit</span>
@endsection

@section('content')
<form action="{{ route('ecommerce.pages.update', $page) }}" method="POST" enctype="multipart/form-data">
  @csrf
  @method('PUT')
  @include('ecommerce::pages._form', ['page' => $page])
</form>
@endsection
