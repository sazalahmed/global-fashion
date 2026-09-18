@extends('core::layouts.master')

@section('title', __('Departments'))
@section('page-title', __('Departments'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('employee.index') }}">Staff</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Departments</span>
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-building me-2"></i>Departments</h5>
            @bpCan('hr.create')
            <button class="bp-btn bp-btn-sm bp-btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal"><i class="fa-solid fa-plus me-1"></i> Add Department</button>
            @endbpCan
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $department)
                            <tr>
                                <td class="fw-700">{{ $department->name }}</td>
                                <td>{{ $department->sort_order }}</td>
                                <td><x-core::status-toggle :url="route('departments.toggle-status', $department->id)" :active="$department->is_active" /></td>
                                <td>
                                    @bpCanAny('hr.edit','hr.delete')
                                    <div class="dropdown">
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @bpCan('hr.edit')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-department"
                                                    data-id="{{ $department->id }}" data-name="{{ $department->name }}"
                                                    data-sort="{{ $department->sort_order }}"
                                                    data-active="{{ $department->is_active ? 1 : 0 }}" data-bs-toggle="modal"
                                                    data-bs-target="#editDepartmentModal"><i class="fa-solid fa-pen me-2"></i> Edit</button>
                                            </li>
                                            @endbpCan
                                            @bpCan('hr.delete')
                                            <li>
                                                <form action="{{ route('departments.destroy', $department) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                        data-name="{{ $department->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                                                </form>
                                            </li>
                                            @endbpCan
                                        </ul>
                                    </div>
                                    @endbpCanAny
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="4" icon="fa-solid fa-building" title="No departments yet" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Department Modal -->
    @bpCan('hr.create')
    <div class="modal fade" id="addDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('departments.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700">Add Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8"><label class="bp-form-label">Name *</label><input type="text" class="bp-form-control" name="name" required placeholder="e.g. Marketing"></div>
                            <div class="col-md-4"><label class="bp-form-label">Sort</label><input type="number" min="0" class="bp-form-control" name="sort_order" value="0"></div>
                            <div class="col-12">
                                <div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="addDeptActive" checked><label class="form-check-label" for="addDeptActive">Active</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
                        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endbpCan

    <!-- Edit Department Modal -->
    @bpCan('hr.edit')
    <div class="modal fade" id="editDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="editDepartmentForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700">Edit Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8"><label class="bp-form-label">Name *</label><input type="text" class="bp-form-control" name="name" id="editDeptName" required></div>
                            <div class="col-md-4"><label class="bp-form-label">Sort</label><input type="number" min="0" class="bp-form-control" name="sort_order" id="editDeptSort"></div>
                            <div class="col-12">
                                <div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="editDeptActive"><label class="form-check-label" for="editDeptActive">Active</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
                        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endbpCan

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            var updateUrl = '{{ route('departments.update', '__ID__') }}';
            $(document).on('click', '.btn-edit-department', function() {
                var $b = $(this);
                $('#editDepartmentForm').attr('action', updateUrl.replace('__ID__', $b.data('id')));
                $('#editDeptName').val($b.data('name'));
                $('#editDeptSort').val($b.data('sort'));
                $('#editDeptActive').prop('checked', String($b.data('active')) === '1');
            });
        });
    </script>
@endpush
