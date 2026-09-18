@extends('core::layouts.master')

@section('title', __("Create SMS Campaign"))
@section('page-title', __("Create SMS Campaign"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Marketing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('marketing.sms-campaigns') }}">SMS Campaigns</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Create</span>
@endsection

@section('page-actions')
  <a href="{{ route('marketing.sms-campaigns') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Campaigns
  </a>
@endsection

@section('content')

  <form action="{{ route('marketing.sms-campaigns.store') }}" method="POST">
    @csrf
    @include('marketing::partials.sms-campaign-form')
  </form>

@endsection
