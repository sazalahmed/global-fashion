@extends('core::layouts.master')

@section('title', __('Employees'))
@section('page-title', __('Employees'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Employees</span>
@endsection

@section('page-actions')
    <x-core::export-dropdown module="employees" />
    @bpCan('hr.create')
        <a href="{{ route('employee.create') }}" class="bp-btn bp-btn-primary">
            <i class="fa-solid fa-user-plus"></i> Add Employee
        </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users-gear"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Employees</div>
                    <div class="bp-stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-user-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Active</div>
                    <div class="bp-stat-value">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-user-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">On Leave</div>
                    <div class="bp-stat-value">{{ $stats['on_leave'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Salary (Active)</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_salary'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Employees Table -->
    <x-core::table :selectable="true">
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search name, phone, email, employee ID...">
                <select class="bp-form-select" name="department_id">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}"
                            {{ (int) request('department_id') === $dept->id ? 'selected' : '' }}>{{ $dept->name }}
                        </option>
                    @endforeach
                </select>
                <select class="bp-form-select d-none" name="branch_id">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}"
                            {{ (int) request('branch_id') === $branch->id ? 'selected' : '' }}>{{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated
                    </option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-slot:bulkActions>
            <x-core::table.bulk-actions>
                @bpCan('hr.delete')
                    <button class="bp-btn bp-btn-sm bp-btn-danger"><i class="fa-solid fa-trash me-1"></i> Delete</button>
                @endbpCan
            </x-core::table.bulk-actions>
        </x-slot:bulkActions>

        <x-core::table.header :selectable="true">
            <x-core::table.column>Employee</x-core::table.column>
            <x-core::table.column :sortable="true" field="phone">Phone</x-core::table.column>
            <x-core::table.column>Department</x-core::table.column>
            <x-core::table.column>Designation</x-core::table.column>
            <th class="d-none">Branch</th>
            <x-core::table.column :sortable="true" field="salary">Salary</x-core::table.column>
            <x-core::table.column :sortable="true" field="advance_balance">Advance Balance</x-core::table.column>
            <x-core::table.column :sortable="true" field="joining_date">Joining Date</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($employees as $emp)
                <tr>
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $emp->id }}"></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bp-user-avatar bp-avatar-sm">
                                @if ($emp->photo)
                                    <img src="{{ upload_url_sm($emp->photo) }}" alt="{{ $emp->name }}">
                                @else
                                    {{ strtoupper(substr($emp->name, 0, 1)) }}{{ strtoupper(substr(strstr($emp->name, ' ') ?: '', 1, 1)) }}
                                @endif
                            </div>
                            <div>
                                <div class="fw-700 fs-13"><a href="{{ route('employee.show', $emp) }}"
                                        class="text-decoration-none text-dark">{{ $emp->name }}</a></div>
                                <div class="fs-11 text-muted">{{ $emp->employee_id }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $emp->phone ?? '' }}</td>
                    <td>
                        @if ($emp->department)
                            <span class="bp-badge bp-badge-primary">{{ $emp->department }}</span>
                        @endif
                    </td>
                    <td>{{ $emp->designation ?? '' }}</td>
                    <td class="d-none">{{ $emp->branch?->name ?? '' }}</td>
                    <td class="fw-700">{{ currency_symbol() }} {{ number_format($emp->salary, 0) }}</td>
                    <td class="{{ $emp->advance_balance > 0 ? 'text-danger fw-700' : '' }}">
                        {{ currency_symbol() }} {{ number_format($emp->advance_balance, 0) }}</td>
                    <td>{{ $emp->joining_date ? $emp->joining_date->format('d M Y') : '' }}</td>
                    <td>
                        @if ($emp->status === 'active')
                            <span class="bp-badge bp-badge-success">Active</span>
                        @elseif($emp->status === 'on_leave')
                            <span class="bp-badge bp-badge-warning">On Leave</span>
                        @elseif($emp->status === 'inactive')
                            <span class="bp-badge bp-badge-secondary">Inactive</span>
                        @elseif($emp->status === 'terminated')
                            <span class="bp-badge bp-badge-danger">Terminated</span>
                        @endif
                    </td>
                    <td>
                        @bpCanAny('hr.view', 'hr.edit', 'hr.delete')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                        class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('hr.view')
                                        <li><a class="dropdown-item" href="{{ route('employee.show', $emp) }}"><i
                                                    class="fa-solid fa-eye"></i> View Profile</a></li>
                                    @endbpCan
                                    @bpCan('hr.edit')
                                        <li><a class="dropdown-item" href="{{ route('employee.edit', $emp) }}"><i
                                                    class="fa-solid fa-pen"></i> Edit</a></li>
                                    @endbpCan
                                    @bpCan('hr.delete')
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <form action="{{ route('employee.destroy', $emp) }}" method="POST"
                                                class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $emp->name }}"><i class="fa-solid fa-trash"></i>
                                                    Delete</button>
                                            </form>
                                        </li>
                                    @endbpCan
                                </ul>
                            </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="11" icon="fa-solid fa-users-gear" title="No employees found" />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$employees" itemLabel="employees" />
        </x-slot:pagination>
    </x-core::table>

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            // Delete confirmation is handled globally by .delete-confirm in app.js
        });
    </script>
@endpush
