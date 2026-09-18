@extends('core::layouts.master')

@section('title', __("Edit Blog Category — Website"))
@section('page-title', __("Edit Blog Category"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.blog-categories') }}">Blog Categories</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit</span>
@endsection

@section('page-actions')
<a href="{{ route('ecommerce.blog-categories') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Categories
</a>
@endsection

@section('content')

<form action="{{ route('ecommerce.blog-categories.update', $category) }}" method="POST">
  @csrf
  @method('PUT')
  @include('ecommerce::_blog-category-form', ['category' => $category, 'submitLabel' => 'Update Category'])
</form>

@endsection
