@extends('core::layouts.master')

@section('title', __('Add Employee'))
@section('page-title', __('Add Employee'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('employee.index') }}">Employees</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add Employee</span>
@endsection

@section('page-actions')
    <a href="{{ route('employee.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Employees
    </a>
@endsection

@section('content')

    <form action="{{ route('employee.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-user-plus me-2"></i>Employee Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-12 category_img">
                        <x-core::image-upload name="photo" label="Photo"
                            accept="image/jpg,image/jpeg,image/png,image/webp" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Full Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            value="{{ old('name') }}" required placeholder="Employee full name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Employee ID</label>
                        <input type="text" class="bp-form-control" value="Auto-generated" readonly>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Phone Number</label>
                        <input type="text" class="bp-form-control @error('phone') is-invalid @enderror" name="phone"
                            value="{{ old('phone') }}" data-phone>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Email</label>
                        <input type="email" class="bp-form-control @error('email') is-invalid @enderror" name="email"
                            value="{{ old('email') }}" placeholder="employee@email.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Department</label>
                        <select class="bp-form-select w-100 @error('department_id') is-invalid @enderror"
                            name="department_id">
                            <option value="">Select Department</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ (int) old('department_id') === $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Designation</label>
                        <select class="bp-form-select w-100 @error('designation_id') is-invalid @enderror"
                            name="designation_id">
                            <option value="">Select Designation</option>
                            @foreach ($designations as $desig)
                                <option value="{{ $desig->id }}"
                                    {{ (int) old('designation_id') === $desig->id ? 'selected' : '' }}>
                                    {{ $desig->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 d-none">
                        <label class="bp-form-label">Branch</label>
                        <select class="bp-form-select w-100 @error('branch_id') is-invalid @enderror" name="branch_id">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ (int) old('branch_id') === $branch->id ? 'selected' : '' }}>{{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="bp-form-label">Joining Date</label>
                        <input type="date" class="bp-form-control @error('joining_date') is-invalid @enderror"
                            name="joining_date" value="{{ old('joining_date') }}">
                        @error('joining_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="bp-form-label">Salary ({{ currency_symbol() }}) *</label>
                        <input type="number" step="0.01" class="bp-form-control @error('salary') is-invalid @enderror"
                            name="salary" value="{{ old('salary', '0') }}" required placeholder="e.g., 15000">
                        @error('salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="bp-form-label">NID Number</label>
                        <input type="text" class="bp-form-control @error('nid') is-invalid @enderror" name="nid"
                            value="{{ old('nid') }}" placeholder="National ID Number">
                        @error('nid')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="bp-form-label">Status *</label>
                        <select class="bp-form-select w-100 @error('status') is-invalid @enderror" name="status" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active
                            </option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive
                            </option>
                            <option value="on_leave" {{ old('status') === 'on_leave' ? 'selected' : '' }}>On Leave
                            </option>
                            <option value="terminated" {{ old('status') === 'terminated' ? 'selected' : '' }}>Terminated
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4 d-none" id="leavingDateGroup">
                        <label class="bp-form-label">Leaving Date</label>
                        <input type="date" class="bp-form-control @error('leaving_date') is-invalid @enderror"
                            name="leaving_date" value="{{ old('leaving_date') }}">
                        @error('leaving_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Address</label>
                        <textarea class="bp-form-control @error('address') is-invalid @enderror" name="address" rows="2"
                            placeholder="Full address...">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="bp-card-footer text-end">
                <a href="{{ route('employee.index') }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save
                    Employee</button>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';
        // Show "Leaving Date" only for inactive/terminated employees.
        (function() {
            var statusSel = document.querySelector('select[name="status"]');
            var group = document.getElementById('leavingDateGroup');
            if (!statusSel || !group) {
                return;
            }
            var field = group.querySelector('input[name="leaving_date"]');

            function apply() {
                var show = ['inactive', 'terminated'].indexOf(statusSel.value) !== -1;
                group.classList.toggle('d-none', !show);
                if (!show && field) {
                    field.value = '';
                }
            }
            apply();
            statusSel.addEventListener('change', apply);
        })();
    </script>
@endpush
