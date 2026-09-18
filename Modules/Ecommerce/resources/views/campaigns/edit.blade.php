@extends('core::layouts.master')

@section('title', __("Edit Campaign"))
@section('page-title', __("Edit Campaign"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.campaigns.index') }}">Campaigns</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $campaign->name }}</span>
@endsection

@section('page-actions')
<a href="{{ route('ecommerce.campaigns.index') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
</a>
@endsection

@section('content')

<form action="{{ route('ecommerce.campaigns.update', $campaign) }}" method="POST">
  @csrf @method('PUT')
  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-bullhorn me-2"></i>{{ __('Campaign Details') }}</h5>
    </div>
    <div class="bp-card-body">
      @include('ecommerce::campaigns._form')
    </div>
    <div class="bp-card-footer text-end">
      <a href="{{ route('ecommerce.campaigns.index') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</a>
      <button type="submit" class="bp-btn bp-btn-success">
        <i class="fa-solid fa-save me-1"></i> {{ __('Update Campaign') }}
      </button>
    </div>
  </div>
</form>

@endsection
