@extends('core::layouts.master')

@section('title', 'Edit Email Template — ' . $template->name)
@section('page-title', __("Edit Email Template"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('settings.email') }}">Email Settings</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $template->name }}</span>
@endsection

@section('page-actions')
<a href="{{ route('settings.email') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

<form action="{{ route('settings.email.templates.update', $template) }}" method="POST">
  @csrf
  @method('PUT')

  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2"></i>{{ $template->name }}</h5>
      <code class="fs-11">{{ $template->slug }}</code>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="bp-form-label">Template Name</label>
          <input type="text" class="bp-form-control" name="name" value="{{ old('name', $template->name) }}" required>
        </div>
        <div class="col-md-6">
          <label class="bp-form-label">Email Subject</label>
          <input type="text" class="bp-form-control" name="subject" value="{{ old('subject', $template->subject) }}" required>
        </div>
        <div class="col-12">
          <label class="bp-form-label">Email Body</label>
          <textarea class="bp-form-control" name="body" rows="12" required>{{ old('body', $template->body) }}</textarea>
          <div class="fs-11 text-muted mt-1">Use placeholders like <code>@{{ '{{customer_name}}' }}</code>, <code>@{{ '{{invoice_number}}' }}</code>, <code>@{{ '{{amount}}' }}</code>, <code>@{{ '{{company_name}}' }}</code></div>
        </div>
        <div class="col-md-4">
          <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }}>
            <label class="form-check-label fw-600">Active</label>
          </div>
        </div>
      </div>
    </div>
    <div class="bp-card-footer text-end">
      <a href="{{ route('settings.email') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Update Template</button>
    </div>
  </div>
</form>

@endsection
