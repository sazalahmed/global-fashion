@extends('core::layouts.master')

@section('title', __("Create Blog Category — Website"))
@section('page-title', __("Create Blog Category"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.blog-categories') }}">Blog Categories</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Create</span>
@endsection

@section('page-actions')
<a href="{{ route('ecommerce.blog-categories') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Categories
</a>
@endsection

@section('content')

<form action="{{ route('ecommerce.blog-categories.store') }}" method="POST">
  @csrf
  @include('ecommerce::_blog-category-form', ['submitLabel' => 'Create Category'])
</form>

@endsection
