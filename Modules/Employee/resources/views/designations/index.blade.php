@extends('core::layouts.master')

@section('title', __('Designations'))
@section('page-title', __('Designations'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('employee.index') }}">Staff</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Designations</span>
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-id-card-clip me-2"></i>Designations</h5>
            @bpCan('hr.create')
            <button class="bp-btn bp-btn-sm bp-btn-primary" data-bs-toggle="modal" data-bs-target="#addDesignationModal"><i class="fa-solid fa-plus me-1"></i> Add Designation</button>
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
                        @forelse($designations as $designation)
                            <tr>
                                <td class="fw-700">{{ $designation->name }}</td>
                                <td>{{ $designation->sort_order }}</td>
                                <td><x-core::status-toggle :url="route('designations.toggle-status', $designation->id)" :active="$designation->is_active" /></td>
                                <td>
                                    @bpCanAny('hr.edit','hr.delete')
                                    <div class="dropdown">
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @bpCan('hr.edit')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-designation"
                                                    data-id="{{ $designation->id }}" data-name="{{ $designation->name }}"
                                                    data-sort="{{ $designation->sort_order }}"
                                                    data-active="{{ $designation->is_active ? 1 : 0 }}" data-bs-toggle="modal"
                                                    data-bs-target="#editDesignationModal"><i class="fa-solid fa-pen me-2"></i> Edit</button>
                                            </li>
                                            @endbpCan
                                            @bpCan('hr.delete')
                                            <li>
                                                <form action="{{ route('designations.destroy', $designation) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                        data-name="{{ $designation->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                                                </form>
                                            </li>
                                            @endbpCan
                                        </ul>
                                    </div>
                                    @endbpCanAny
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="4" icon="fa-solid fa-id-card-clip" title="No designations yet" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Designation Modal -->
    @bpCan('hr.create')
    <div class="modal fade" id="addDesignationModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('designations.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700">Add Designation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8"><label class="bp-form-label">Name *</label><input type="text" class="bp-form-control" name="name" required placeholder="e.g. Team Lead"></div>
                            <div class="col-md-4"><label class="bp-form-label">Sort</label><input type="number" min="0" class="bp-form-control" name="sort_order" value="0"></div>
                            <div class="col-12">
                                <div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="addDesigActive" checked><label class="form-check-label" for="addDesigActive">Active</label></div>
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

    <!-- Edit Designation Modal -->
    @bpCan('hr.edit')
    <div class="modal fade" id="editDesignationModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="editDesignationForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700">Edit Designation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8"><label class="bp-form-label">Name *</label><input type="text" class="bp-form-control" name="name" id="editDesigName" required></div>
                            <div class="col-md-4"><label class="bp-form-label">Sort</label><input type="number" min="0" class="bp-form-control" name="sort_order" id="editDesigSort"></div>
                            <div class="col-12">
                                <div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="editDesigActive"><label class="form-check-label" for="editDesigActive">Active</label></div>
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
            var updateUrl = '{{ route('designations.update', '__ID__') }}';
            $(document).on('click', '.btn-edit-designation', function() {
                var $b = $(this);
                $('#editDesignationForm').attr('action', updateUrl.replace('__ID__', $b.data('id')));
                $('#editDesigName').val($b.data('name'));
                $('#editDesigSort').val($b.data('sort'));
                $('#editDesigActive').prop('checked', String($b.data('active')) === '1');
            });
        });
    </script>
@endpush
