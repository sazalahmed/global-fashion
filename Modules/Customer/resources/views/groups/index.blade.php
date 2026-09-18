@extends('core::layouts.master')

@section('title', __('Customer Groups'))
@section('page-title', __('Customer Groups'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('customers.index') }}">Customers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Groups</span>
@endsection

@section('page-actions')
    @bpCan('customers.create')
    <button class="bp-btn bp-btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
        <i class="fa-solid fa-plus me-1"></i> Add Group
    </button>
    @endbpCan
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Discount %</th>
                            <th class="text-center">Customers</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                            <tr>
                                <td class="fw-700">{{ $group->name }}</td>
                                <td class="text-muted fs-13">{{ $group->description ?? '' }}</td>
                                <td>
                                    @if ($group->discount_percentage > 0)
                                        <span class="bp-badge bp-badge-success">{{ $group->discount_percentage }}%</span>
                                    @endif
                                </td>
                                <td class="text-center fw-600">{{ $group->customers_count }}</td>
                                <td>
                                    <x-core::status-toggle :url="route('customer-groups.toggle-status', $group->id)" :active="$group->is_active" />
                                </td>
                                <td>
                                    @bpCanAny('customers.edit','customers.delete')
                                    <div class="dropdown">
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @bpCan('customers.edit')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-group"
                                                    data-id="{{ $group->id }}" data-name="{{ $group->name }}"
                                                    data-description="{{ $group->description }}"
                                                    data-discount="{{ num_input($group->discount_percentage) }}"
                                                    data-active="{{ $group->is_active ? '1' : '0' }}"><i class="fa-solid fa-pen me-2"></i> Edit</button>
                                            </li>
                                            @endbpCan
                                            @bpCan('customers.delete')
                                            @if ($group->customers_count === 0)
                                            <li>
                                                <form action="{{ route('customer-groups.destroy', $group) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                        data-name="{{ $group->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                                                </form>
                                            </li>
                                            @endif
                                            @endbpCan
                                        </ul>
                                    </div>
                                    @endbpCanAny
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="6" icon="fa-solid fa-user-group"
                                title="No customer groups found." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Group Modal -->
    <div class="modal fade" id="addGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('customer-groups.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700">Add Customer Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="bp-form-label">Group Name *</label>
                            <input type="text" class="bp-form-control" name="name" required maxlength="100"
                                placeholder="e.g. Premium">
                        </div>
                        <div class="mb-3">
                            <label class="bp-form-label">Description</label>
                            <input type="text" class="bp-form-control" name="description" maxlength="500"
                                placeholder="Brief description...">
                        </div>
                        <div class="mb-3">
                            <label class="bp-form-label">Group Discount (%)</label>
                            <input type="number" class="bp-form-control" name="discount_percentage" value="0"
                                min="0" max="100" step="0.01">
                        </div>
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addIsActive"
                                checked>
                            <label class="form-check-label" for="addIsActive">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                class="fa-solid fa-xmark me-1"></i>Cancel</button>
                        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>
                            Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Group Modal -->
    <div class="modal fade" id="editGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="editGroupForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700">Edit Customer Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="bp-form-label">Group Name *</label>
                            <input type="text" class="bp-form-control" name="name" id="editName" required
                                maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="bp-form-label">Description</label>
                            <input type="text" class="bp-form-control" name="description" id="editDescription"
                                maxlength="500">
                        </div>
                        <div class="mb-3">
                            <label class="bp-form-label">Group Discount (%)</label>
                            <input type="number" class="bp-form-control" name="discount_percentage" id="editDiscount"
                                value="0" min="0" max="100" step="0.01">
                        </div>
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                id="editIsActive">
                            <label class="form-check-label" for="editIsActive">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                class="fa-solid fa-xmark me-1"></i>Cancel</button>
                        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>
                            Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            $('.btn-edit-group').on('click', function() {
                var id = $(this).data('id');
                var baseUrl = '{{ url('admin/customer-groups') }}';
                $('#editGroupForm').attr('action', baseUrl + '/' + id);
                $('#editName').val($(this).data('name'));
                $('#editDescription').val($(this).data('description'));
                $('#editDiscount').val($(this).data('discount'));
                $('#editIsActive').prop('checked', $(this).data('active') == 1);
                new bootstrap.Modal('#editGroupModal').show();
            });
        });
    </script>
@endpush
