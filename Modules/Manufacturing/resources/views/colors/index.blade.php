@extends('core::layouts.master')

@section('title', __("Colors"))
@section('page-title', __("Colors"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Colors</span>
@endsection

@section('content')

  @bpCanAny('manufacturing.create', 'manufacturing.edit')
  <!-- Add / Edit Color Form -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-palette me-2"></i><span id="colorFormTitle">Add Color</span></h5>
    </div>
    <div class="bp-card-body">
      <form id="colorForm">
        <input type="hidden" id="colorId" value="">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="bp-form-label">Name *</label>
            <input type="text" class="bp-form-control" id="colorName" required placeholder="Color name">
          </div>
          <div class="col-md-3">
            <label class="bp-form-label">Code</label>
            <input type="text" class="bp-form-control" id="colorCode" placeholder="e.g. RED, BLU">
          </div>
          <div class="col-md-3">
            <label class="bp-form-label">Hex Code</label>
            <div class="d-flex gap-2">
              <input type="color" class="form-control form-control-color" id="colorPicker" value="#000000">
              <input type="text" class="bp-form-control" id="colorHex" placeholder="#000000" maxlength="7">
            </div>
          </div>
          <div class="col-md-3">
            <button type="submit" class="bp-btn bp-btn-primary" id="colorSubmitBtn"><i class="fa-solid fa-plus me-1"></i> Add Color</button>
            <button type="button" class="bp-btn bp-btn-danger d-none" id="colorCancelBtn"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  @endbpCanAny

  <!-- Colors Table -->
  <x-core::table>
    <x-core::table.header>
      <x-core::table.column>Name</x-core::table.column>
      <x-core::table.column>Code</x-core::table.column>
      <x-core::table.column>Hex</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($colors as $color)
      <tr data-id="{{ $color->id }}">
        <td class="fw-700 fs-13">{{ $color->name }}</td>
        <td class="fs-12">{{ $color->code ?? '' }}</td>
        <td>
          @if($color->hex_code)
            <span class="d-inline-flex align-items-center gap-2">
              <span class="bp-color-swatch" data-hex="{{ $color->hex_code }}"></span>
              <span class="fs-12">{{ $color->hex_code }}</span>
            </span>
          @endif
        </td>
        <td>
          <x-core::status-toggle :url="route('manufacturing.colors.toggle-status', $color->id)" :active="$color->is_active" />
        </td>
        <td>
          @bpCanAny('manufacturing.edit','manufacturing.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('manufacturing.edit')
              <li><button type="button" class="dropdown-item btn-edit-color" data-id="{{ $color->id }}" data-name="{{ $color->name }}" data-code="{{ $color->code }}" data-hex="{{ $color->hex_code }}"><i class="fa-solid fa-pen me-2"></i> Edit</button></li>
              @endbpCan
              @bpCan('manufacturing.delete')
              <li><button type="button" class="dropdown-item text-danger btn-delete-color" data-id="{{ $color->id }}" data-name="{{ $color->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button></li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="5" icon="fa-solid fa-palette" title="No colors found." />
      @endforelse
    </tbody>
  </x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function() {
    var storeUrl = '{{ route("manufacturing.colors.store") }}';
    var updateUrlBase = '{{ url("manufacturing/colors") }}';
    var deleteUrlBase = '{{ url("manufacturing/colors") }}';

    // Sync color picker with hex input
    $('#colorPicker').on('input', function() {
        $('#colorHex').val($(this).val());
    });
    $('#colorHex').on('input', function() {
        var val = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            $('#colorPicker').val(val);
        }
    });

    // Submit form (Add / Update)
    $('#colorForm').on('submit', function(e) {
        e.preventDefault();

        var id = $('#colorId').val();
        var data = {
            name: $('#colorName').val(),
            code: $('#colorCode').val(),
            hex_code: $('#colorHex').val(),
            is_active: true
        };

        if (!data.name) {
            alert('Color name is required.');
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

    // Edit color
    $(document).on('click', '.btn-edit-color', function() {
        var $btn = $(this);
        $('#colorId').val($btn.data('id'));
        $('#colorName').val($btn.data('name'));
        $('#colorCode').val($btn.data('code'));
        $('#colorHex').val($btn.data('hex') || '');
        if ($btn.data('hex')) {
            $('#colorPicker').val($btn.data('hex'));
        }
        $('#colorFormTitle').text('Edit Color');
        $('#colorSubmitBtn').html('<i class="fa-solid fa-check me-1"></i> Update Color');
        $('#colorCancelBtn').removeClass('d-none');
        $('#colorName').focus();
    });

    // Cancel edit
    $('#colorCancelBtn').on('click', function() {
        $('#colorId').val('');
        $('#colorName').val('');
        $('#colorCode').val('');
        $('#colorHex').val('');
        $('#colorPicker').val('#000000');
        $('#colorFormTitle').text('Add Color');
        $('#colorSubmitBtn').html('<i class="fa-solid fa-plus me-1"></i> Add Color');
        $(this).addClass('d-none');
    });

    // Delete color
    $(document).on('click', '.btn-delete-color', function() {
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

    // Render color swatches
    $('.bp-color-swatch').each(function() {
        $(this).css({
            'display': 'inline-block',
            'width': '20px',
            'height': '20px',
            'border-radius': '4px',
            'background-color': $(this).data('hex'),
            'border': '1px solid var(--bp-border-color)'
        });
    });
});
</script>
@endpush
