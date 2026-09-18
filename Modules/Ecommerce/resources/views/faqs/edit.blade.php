@extends('core::layouts.master')

@section('title', __('Edit FAQ — Website'))
@section('page-title', __('Edit FAQ'))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.faqs.index') }}">FAQs</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit</span>
@endsection

@section('content')
<form action="{{ route('ecommerce.faqs.update', $faq) }}" method="POST">
  @csrf
  @method('PUT')
  @include('ecommerce::faqs._form', ['faq' => $faq])
</form>
@endsection
