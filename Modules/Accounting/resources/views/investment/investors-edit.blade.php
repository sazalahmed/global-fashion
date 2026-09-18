@extends('core::layouts.master')

@section('title', 'Edit Investor')
@section('page-title', 'Edit Investor')

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('investment.dashboard') }}">Investment</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('investment.investors.index') }}">Investors</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $investor->name }}</span>
@endsection

@section('page-actions')
<a href="{{ route('investment.investors.show', $investor) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>Back</a>
@endsection

@section('content')

<form method="POST" action="{{ route('investment.investors.update', $investor) }}">
  @csrf @method('PUT')
  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-pen me-2"></i>Edit {{ $investor->name }}</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="bp-form-label">Type *</label>
          <select name="type" id="investorType" class="bp-form-select w-100" required>
            <option value="shareholder" @selected(old('type', $investor->type) === 'shareholder')>Shareholder (equity owner)</option>
            <option value="investor" @selected(old('type', $investor->type) === 'investor')>Investor (profit share only)</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="bp-form-label">Name *</label>
          <input type="text" class="bp-form-control" name="name" value="{{ old('name', $investor->name) }}" required>
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Join Date *</label>
          <input type="date" class="bp-form-control" name="join_date" value="{{ old('join_date', $investor->join_date?->toDateString()) }}" required>
        </div>

        <div class="col-md-3 {{ $investor->type === 'shareholder' ? '' : 'd-none' }}" id="sharesField">
          <label class="bp-form-label">Shares Owned *</label>
          <input type="number" step="0.01" min="0" class="bp-form-control" name="shares_owned" value="{{ old('shares_owned', num_input($investor->shares_owned)) }}">
        </div>
        <div class="col-md-3 {{ $investor->type === 'investor' ? '' : 'd-none' }}" id="pctField">
          <label class="bp-form-label">Profit Share % *</label>
          <input type="number" step="0.01" min="0" max="100" class="bp-form-control" name="profit_share_pct" value="{{ old('profit_share_pct', num_input($investor->profit_share_pct)) }}">
        </div>

        <div class="col-md-3">
          <label class="bp-form-label">Phone</label>
          <input type="text" class="bp-form-control" name="phone" value="{{ old('phone', $investor->phone) }}">
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Email</label>
          <input type="email" class="bp-form-control" name="email" value="{{ old('email', $investor->email) }}">
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">NID / TIN</label>
          <input type="text" class="bp-form-control" name="nid_or_tin" value="{{ old('nid_or_tin', $investor->nid_or_tin) }}">
        </div>
        <div class="col-md-12">
          <label class="bp-form-label">Address</label>
          <input type="text" class="bp-form-control" name="address" value="{{ old('address', $investor->address) }}">
        </div>
        <div class="col-md-12">
          <label class="bp-form-label">Notes</label>
          <textarea class="bp-form-control" name="notes" rows="2">{{ old('notes', $investor->notes) }}</textarea>
        </div>
        <div class="col-md-12">
          <label class="form-check-label">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', $investor->is_active) ? 'checked' : '' }}> Active
          </label>
        </div>
      </div>
    </div>
    <div class="bp-card-footer text-end">
      <a href="{{ route('investment.investors.show', $investor) }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
      <button class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i>Update</button>
    </div>
  </div>
</form>

<form method="POST" action="{{ route('investment.investors.destroy', $investor) }}" onsubmit="return confirm('Delete this investor? Only investors with no transactions can be removed.')" class="mt-3">
  @csrf @method('DELETE')
  <button type="submit" class="bp-btn bp-btn-danger"><i class="fa-solid fa-trash me-1"></i>Delete Investor</button>
</form>

@endsection

@push('scripts')
<script>
'use strict';
$(function () {
  $('#investorType').on('change', function () {
    if (this.value === 'shareholder') { $('#sharesField').removeClass('d-none'); $('#pctField').addClass('d-none'); }
    else { $('#sharesField').addClass('d-none'); $('#pctField').removeClass('d-none'); }
  });
});
</script>
@endpush
