@extends('core::layouts.master')

@section('title', __("Purchase Return Types"))
@section('page-title', __("Purchase Return Types"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('purchase-returns.index') }}">Purchase Returns</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Return Types</span>
@endsection

@section('page-actions')
@bpCan('purchases.create')
<button class="bp-btn bp-btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
  <i class="fa-solid fa-plus me-1"></i> Add Type
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
            <th class="text-center">Used In</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($types as $type)
            <tr>
              <td class="fw-700">{{ $type->name }}</td>
              <td class="text-muted fs-13">{{ $type->description }}</td>
              <td class="text-center fw-600">{{ $type->purchase_returns_count }} returns</td>
              <td>
                <x-core::status-toggle :url="route('purchase-return-types.toggle-status', $type->id)" :active="$type->is_active" />
              </td>
              <td>
                @bpCanAny('purchases.edit','purchases.delete')
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    @bpCan('purchases.edit')
                    <li>
                      <button type="button" class="dropdown-item btn-edit-type"
                        data-id="{{ $type->id }}" data-name="{{ $type->name }}"
                        data-description="{{ $type->description }}" data-active="{{ $type->is_active ? '1' : '0' }}"><i class="fa-solid fa-pen me-2"></i> Edit</button>
                    </li>
                    @endbpCan
                    @if($type->purchase_returns_count === 0)
                      @bpCan('purchases.delete')
                      <li>
                        <form action="{{ route('purchase-return-types.destroy', $type) }}" method="POST">
                          @csrf @method('DELETE')
                          <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $type->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                        </form>
                      </li>
                      @endbpCan
                    @endif
                  </ul>
                </div>
                @endbpCanAny
              </td>
            </tr>
          @empty
            <x-core::table.empty colspan="5" icon="fa-solid fa-tags" title="No return types found." />
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addTypeModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('purchase-return-types.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-700">Add Return Type</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="bp-form-label">Name *</label><input type="text" class="bp-form-control" name="name" required maxlength="100"></div>
          <div class="mb-3"><label class="bp-form-label">Description</label><input type="text" class="bp-form-control" name="description" maxlength="500"></div>
          <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Active</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button><button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>Save</button></div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editTypeModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="editTypeForm" method="POST">
      @csrf @method('PUT')
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-700">Edit Return Type</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="bp-form-label">Name *</label><input type="text" class="bp-form-control" name="name" id="editName" required maxlength="100"></div>
          <div class="mb-3"><label class="bp-form-label">Description</label><input type="text" class="bp-form-control" name="description" id="editDesc" maxlength="500"></div>
          <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive"><label class="form-check-label">Active</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button><button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>Update</button></div>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';
$(function () {
    $('.btn-edit-type').on('click', function () {
        var baseUrl = '{{ url("admin/purchase-return-types") }}';
        $('#editTypeForm').attr('action', baseUrl + '/' + $(this).data('id'));
        $('#editName').val($(this).data('name'));
        $('#editDesc').val($(this).data('description'));
        $('#editActive').prop('checked', $(this).data('active') == 1);
        new bootstrap.Modal('#editTypeModal').show();
    });
});
</script>
@endpush
