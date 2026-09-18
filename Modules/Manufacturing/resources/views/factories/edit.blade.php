@extends('core::layouts.master')

@section('title', 'Edit Factory — ' . $factory->name)
@section('page-title', __("Edit Factory"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.factories.index') }}">Factories</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.factories.show', $factory) }}">{{ $factory->name }}</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Edit</span>
@endsection

@section('page-actions')
  <a href="{{ route('manufacturing.factories.show', $factory) }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Factory
  </a>
@endsection

@section('content')

  <form action="{{ route('manufacturing.factories.update', $factory) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-industry me-2"></i>Factory Information</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="bp-form-label">Factory Name *</label>
            <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $factory->name) }}" required placeholder="Factory name">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Contact Person</label>
            <input type="text" class="bp-form-control @error('contact_person') is-invalid @enderror" name="contact_person" value="{{ old('contact_person', $factory->contact_person) }}" placeholder="Primary contact name">
            @error('contact_person')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Phone</label>
            <input type="text" class="bp-form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $factory->phone) }}" placeholder="Phone number">
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Email</label>
            <input type="email" class="bp-form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $factory->email) }}" placeholder="factory@email.com">
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-12">
            <label class="bp-form-label">Address</label>
            <textarea class="bp-form-control @error('address') is-invalid @enderror" name="address" rows="2" placeholder="Factory address">{{ old('address', $factory->address) }}</textarea>
            @error('address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="bp-form-label">Payment Terms</label>
            <select class="bp-form-select w-100" name="payment_terms">
              <option value="">Select Payment Terms</option>
              @foreach(['Due on Receipt', 'Net 15', 'Net 30', 'Net 45', 'Net 60'] as $term)
                <option {{ old('payment_terms', $factory->payment_terms) == $term ? 'selected' : '' }}>{{ $term }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $factory->is_active) ? 'checked' : '' }}>
              <label class="form-check-label fw-600" for="is_active">Is Active</label>
            </div>
          </div>
          <div class="col-12">
            <label class="bp-form-label">Notes</label>
            <textarea class="bp-form-control" name="notes" rows="2" placeholder="Internal notes about this factory...">{{ old('notes', $factory->notes) }}</textarea>
          </div>
        </div>
      </div>
      <div class="bp-card-footer text-end">
        <a href="{{ route('manufacturing.factories.show', $factory) }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Update Factory</button>
      </div>
    </div>
  </form>

@endsection
