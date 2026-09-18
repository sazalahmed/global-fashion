@extends('core::layouts.master')

@section('title', __('Leave Types'))
@section('page-title', __('Leave Types'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('attendance.leave') }}">Leave</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Types</span>
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2"></i>Leave Types</h5>
            @bpCan('hr.create')
            <button class="bp-btn bp-btn-sm bp-btn-primary" data-bs-toggle="modal" data-bs-target="#addLeaveTypeModal"><i
                    class="fa-solid fa-plus me-1"></i> Add Leave Type</button>
            @endbpCan
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Max Days / Year</th>
                            <th>Paid</th>
                            <th>Approval</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveTypes as $type)
                            <tr>
                                <td class="fw-700">{{ $type->name }}</td>
                                <td><code>{{ $type->code }}</code></td>
                                <td>{{ $type->max_days > 0 ? $type->max_days : 'Unlimited' }}</td>
                                <td><span
                                        class="bp-badge {{ $type->paid ? 'bp-badge-success' : 'bp-badge-secondary' }}">{{ $type->paid ? 'Paid' : 'Unpaid' }}</span>
                                </td>
                                <td>{{ $type->requires_approval ? 'Required' : 'Auto' }}</td>
                                <td><x-core::status-toggle :url="route('attendance.leave-types.toggle-status', $type->id)" :active="$type->is_active" /></td>
                                <td class="text-end">
                                    @bpCanAny('hr.edit','hr.delete')
                                    <div class="dropdown">
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @bpCan('hr.edit')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-leave-type"
                                                    data-id="{{ $type->id }}" data-name="{{ $type->name }}"
                                                    data-code="{{ $type->code }}" data-max="{{ $type->max_days }}"
                                                    data-paid="{{ $type->paid ? 1 : 0 }}"
                                                    data-approval="{{ $type->requires_approval ? 1 : 0 }}"
                                                    data-active="{{ $type->is_active ? 1 : 0 }}" data-bs-toggle="modal"
                                                    data-bs-target="#editLeaveTypeModal"><i class="fa-solid fa-pen me-2"></i> Edit</button>
                                            </li>
                                            @endbpCan
                                            @bpCan('hr.delete')
                                            <li>
                                                <form action="{{ route('attendance.leave-types.destroy', $type) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                        data-name="{{ $type->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                                                </form>
                                            </li>
                                            @endbpCan
                                        </ul>
                                    </div>
                                    @endbpCanAny
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="7" icon="fa-solid fa-tags" title="No leave types configured" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Leave Type Modal -->
    @bpCan('hr.create')
    <div class="modal fade" id="addLeaveTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('attendance.leave-types.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700">Add Leave Type</h5><button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12"><label class="bp-form-label">Name *</label><input type="text"
                                    class="bp-form-control" name="name" required placeholder="e.g. Casual Leave"></div>
                            <div class="col-md-6"><label class="bp-form-label">Code *</label><input type="text"
                                    class="bp-form-control" name="code" required placeholder="e.g. casual"
                                    pattern="[A-Za-z0-9_-]+" title="Letters, numbers, dashes, underscores"></div>
                            <div class="col-md-6"><label class="bp-form-label">Max Days / Year *</label><input
                                    type="number" min="0" max="365" class="bp-form-control" name="max_days"
                                    value="0" required></div>
                            <div class="col-md-6 d-flex align-items-end gap-3">
                                <div class="form-check form-switch"><input type="hidden" name="paid"
                                        value="0"><input class="form-check-input" type="checkbox" name="paid"
                                        value="1" id="addPaid" checked><label class="form-check-label"
                                        for="addPaid">Paid</label></div>
                                <div class="form-check form-switch"><input type="hidden" name="is_active"
                                        value="0"><input class="form-check-input" type="checkbox" name="is_active"
                                        value="1" id="addActive" checked><label class="form-check-label"
                                        for="addActive">Active</label></div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch"><input type="hidden" name="requires_approval"
                                        value="0"><input class="form-check-input" type="checkbox"
                                        name="requires_approval" value="1" id="addApproval" checked><label
                                        class="form-check-label" for="addApproval">Requires approval</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                class="fa-solid fa-xmark me-1"></i>Cancel</button><button type="submit"
                            class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endbpCan

    <!-- Edit Leave Type Modal -->
    @bpCan('hr.edit')
    <div class="modal fade" id="editLeaveTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="editLeaveTypeForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700">Edit Leave Type</h5><button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-7"><label class="bp-form-label">Name *</label><input type="text"
                                    class="bp-form-control" name="name" id="editLtName" required></div>
                            <div class="col-md-5"><label class="bp-form-label">Code *</label><input type="text"
                                    class="bp-form-control" name="code" id="editLtCode" required
                                    pattern="[A-Za-z0-9_-]+" title="Letters, numbers, dashes, underscores"></div>
                            <div class="col-md-6"><label class="bp-form-label">Max Days / Year *</label><input
                                    type="number" min="0" max="365" class="bp-form-control"
                                    name="max_days" id="editLtMax" required></div>
                            <div class="col-md-6 d-flex align-items-end gap-3">
                                <div class="form-check form-switch"><input type="hidden" name="paid"
                                        value="0"><input class="form-check-input" type="checkbox" name="paid"
                                        value="1" id="editLtPaid"><label class="form-check-label"
                                        for="editLtPaid">Paid</label></div>
                                <div class="form-check form-switch"><input type="hidden" name="is_active"
                                        value="0"><input class="form-check-input" type="checkbox" name="is_active"
                                        value="1" id="editLtActive"><label class="form-check-label"
                                        for="editLtActive">Active</label></div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch"><input type="hidden" name="requires_approval"
                                        value="0"><input class="form-check-input" type="checkbox"
                                        name="requires_approval" value="1" id="editLtApproval"><label
                                        class="form-check-label" for="editLtApproval">Requires approval</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="bp-btn bp-btn-danger"
                            data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button><button
                            type="submit" class="bp-btn bp-btn-success"><i
                                class="fa-solid fa-save me-1"></i>Update</button></div>
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
            var updateUrl = '{{ route('attendance.leave-types.update', '__ID__') }}';
            $(document).on('click', '.btn-edit-leave-type', function() {
                var $b = $(this);
                $('#editLeaveTypeForm').attr('action', updateUrl.replace('__ID__', $b.data('id')));
                $('#editLtName').val($b.data('name'));
                $('#editLtCode').val($b.data('code'));
                $('#editLtMax').val($b.data('max'));
                $('#editLtPaid').prop('checked', String($b.data('paid')) === '1');
                $('#editLtApproval').prop('checked', String($b.data('approval')) === '1');
                $('#editLtActive').prop('checked', String($b.data('active')) === '1');
            });
        });
    </script>
@endpush
