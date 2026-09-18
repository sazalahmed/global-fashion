@extends('core::layouts.master')

@section('title', __("Generate Payroll"))
@section('page-title', __("Generate Payroll"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>HR</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('payroll.index') }}">Payroll</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Generate</span>
@endsection

@section('page-actions')
  <a href="{{ route('payroll.index') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to Payroll
  </a>
@endsection

@section('content')

  <form action="{{ route('payroll.generate.store') }}" method="POST">
    @csrf

    <!-- Payroll Period -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-calendar-days me-2"></i>Payroll Period</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="bp-form-label">Month *</label>
            <input type="month" class="bp-form-control @error('month') is-invalid @enderror" name="month" value="{{ old('month', now()->format('Y-m')) }}" required>
            @error('month')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted fs-11">Select the payroll month in YYYY-MM format</small>
          </div>
          <div class="col-md-6 d-none">
            <label class="bp-form-label">Branch</label>
            <select class="bp-form-select w-100 @error('branch_id') is-invalid @enderror" name="branch_id">
              <option value="">All Branches</option>
              @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ (int) old('branch_id') === $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
              @endforeach
            </select>
            @error('branch_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted fs-11">Leave empty to generate for all branches</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Info Card -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-info-circle me-2"></i>Generation Info</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-12">
            <div class="fs-13 text-muted">
              <p class="mb-2"><i class="fa-solid fa-circle-info me-1"></i> Payroll generation will:</p>
              <ul class="mb-0">
                <li>Include all active employees for the selected branch (or all branches)</li>
                <li>Calculate salaries based on assigned salary structures</li>
                <li>Prorate salaries based on attendance records (if available)</li>
                <li>Apply advance deductions (max 50% of basic salary)</li>
                <li>Create a draft payroll that must be approved before payment</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Generate Button -->
    <div class="bp-card">
      <div class="bp-card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="fs-13 text-muted">
              <i class="fa-solid fa-triangle-exclamation me-1 bp-text-warning"></i>
              A payroll can only be generated once per month per branch. Ensure attendance records are up to date before generating.
            </div>
          </div>
          <div class="col-md-4 text-end">
            <a href="{{ route('payroll.index') }}" class="bp-btn bp-btn-danger me-2"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
            <button type="submit" class="bp-btn bp-btn-primary bp-btn-lg">
              <i class="fa-solid fa-play me-1"></i> Generate Payroll
            </button>
          </div>
        </div>
      </div>
    </div>

  </form>

@endsection
