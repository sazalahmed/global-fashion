@extends('core::layouts.master')

@section('title', __("Sizes"))
@section('page-title', __("Sizes"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Sizes</span>
@endsection

@section('content')

  @bpCanAny('manufacturing.create', 'manufacturing.edit')
  <!-- Add / Edit Size Form -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-ruler me-2"></i><span id="sizeFormTitle">Add Size</span></h5>
    </div>
    <div class="bp-card-body">
      <form id="sizeForm">
        <input type="hidden" id="sizeId" value="">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="bp-form-label">Name *</label>
            <input type="text" class="bp-form-control" id="sizeName" required placeholder="e.g. S, M, L, XL">
          </div>
          <div class="col-md-3">
            <label class="bp-form-label">Code</label>
            <input type="text" class="bp-form-control" id="sizeCode" placeholder="e.g. S, M, L">
          </div>
          <div class="col-md-3">
            <label class="bp-form-label">Sort Order</label>
            <input type="number" class="bp-form-control" id="sizeSortOrder" value="0" placeholder="0">
          </div>
          <div class="col-md-3">
            <button type="submit" class="bp-btn bp-btn-primary" id="sizeSubmitBtn"><i class="fa-solid fa-plus me-1"></i> Add Size</button>
            <button type="button" class="bp-btn bp-btn-danger d-none" id="sizeCancelBtn"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  @endbpCanAny

  <!-- Sizes Table -->
  <x-core::table>
    <x-core::table.header>
      <x-core::table.column>Name</x-core::table.column>
      <x-core::table.column>Code</x-core::table.column>
      <x-core::table.column>Sort Order</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($sizes as $size)
      <tr data-id="{{ $size->id }}">
        <td class="fw-700 fs-13">{{ $size->name }}</td>
        <td class="fs-12">{{ $size->code ?? '' }}</td>
        <td>{{ $size->sort_order ?? 0 }}</td>
        <td>
          <x-core::status-toggle :url="route('manufacturing.sizes.toggle-status', $size->id)" :active="$size->is_active" />
        </td>
        <td>
          @bpCanAny('manufacturing.edit','manufacturing.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('manufacturing.edit')
              <li><button type="button" class="dropdown-item btn-edit-size" data-id="{{ $size->id }}" data-name="{{ $size->name }}" data-code="{{ $size->code }}" data-sort="{{ $size->sort_order }}"><i class="fa-solid fa-pen me-2"></i> Edit</button></li>
              @endbpCan
              @bpCan('manufacturing.delete')
              <li><button type="button" class="dropdown-item text-danger btn-delete-size" data-id="{{ $size->id }}" data-name="{{ $size->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button></li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="5" icon="fa-solid fa-ruler" title="No sizes found." />
      @endforelse
    </tbody>
  </x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function() {
    var storeUrl = '{{ route("manufacturing.sizes.store") }}';
    var updateUrlBase = '{{ url("manufacturing/sizes") }}';
    var deleteUrlBase = '{{ url("manufacturing/sizes") }}';

    // Submit form (Add / Update)
    $('#sizeForm').on('submit', function(e) {
        e.preventDefault();

        var id = $('#sizeId').val();
        var data = {
            name: $('#sizeName').val(),
            code: $('#sizeCode').val(),
            sort_order: $('#sizeSortOrder').val() || 0,
            is_active: true
        };

        if (!data.name) {
            alert('Size name is required.');
            return;
        }

        var url = id ? (updateUrlBase + '/' + id) : storeUrl;
        var method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An error occurred.';
                alert(msg);
            }
        });
    });

    // Edit size
    $(document).on('click', '.btn-edit-size', function() {
        var $btn = $(this);
        $('#sizeId').val($btn.data('id'));
        $('#sizeName').val($btn.data('name'));
        $('#sizeCode').val($btn.data('code'));
        $('#sizeSortOrder').val($btn.data('sort'));
        $('#sizeFormTitle').text('Edit Size');
        $('#sizeSubmitBtn').html('<i class="fa-solid fa-check me-1"></i> Update Size');
        $('#sizeCancelBtn').removeClass('d-none');
        $('#sizeName').focus();
    });

    // Cancel edit
    $('#sizeCancelBtn').on('click', function() {
        $('#sizeId').val('');
        $('#sizeName').val('');
        $('#sizeCode').val('');
        $('#sizeSortOrder').val('0');
        $('#sizeFormTitle').text('Add Size');
        $('#sizeSubmitBtn').html('<i class="fa-solid fa-plus me-1"></i> Add Size');
        $(this).addClass('d-none');
    });

    // Delete size
    $(document).on('click', '.btn-delete-size', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        if (!confirm('Are you sure you want to delete "' + name + '"?')) {
            return;
        }

        $.ajax({
            url: deleteUrlBase + '/' + id,
            type: 'DELETE',
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An error occurred.';
                alert(msg);
            }
        });
    });
});
</script>
@endpush
