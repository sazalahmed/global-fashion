@extends('core::layouts.master')

@section('title', __('Add FAQ — Website'))
@section('page-title', __('Add FAQ'))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.faqs.index') }}">FAQs</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Add</span>
@endsection

@section('content')
<form action="{{ route('ecommerce.faqs.store') }}" method="POST">
  @csrf
  @include('ecommerce::faqs._form', ['faq' => null])
</form>
@endsection
