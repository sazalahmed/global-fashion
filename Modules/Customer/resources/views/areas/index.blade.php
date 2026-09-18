@extends('core::layouts.master')

@section('title', __("Customer Areas"))
@section('page-title', __("Customer Areas"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('customers.index') }}">Customers</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Areas</span>
@endsection

@section('page-actions')
@bpCan('customers.create')
<button class="bp-btn bp-btn-primary" data-bs-toggle="modal" data-bs-target="#addAreaModal">
  <i class="fa-solid fa-plus me-1"></i> Add Area
</button>
@endbpCan
@endsection

@section('content')

<div class="bp-card">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-location-dot me-2"></i>Area Hierarchy</h5>
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Level</th>
            <th>Delivery Charge</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($areas as $division)
            <tr class="fw-700">
              <td>{{ $division->name }}</td>
              <td><span class="bp-badge bp-badge-primary">Division</span></td>
              <td>{{ $division->delivery_charge > 0 ? currency_symbol() . ' ' . number_format($division->delivery_charge, 0) : '' }}</td>
              <td><x-core::status-toggle :url="route('customer-areas.toggle-status', $division->id)" :active="$division->is_active" /></td>
              <td>
                @bpCanAny('customers.edit','customers.delete')
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    @bpCan('customers.edit')
                    <li>
                      <button type="button" class="dropdown-item edit-area-btn"
                              data-id="{{ $division->id }}" data-name="{{ $division->name }}"
                              data-level="{{ $division->level }}" data-parent="{{ $division->parent_id }}"
                              data-charge="{{ num_input($division->delivery_charge) }}" data-active="{{ $division->is_active ? 1 : 0 }}"><i class="fa-solid fa-pen me-2"></i> {{ __('Edit') }}</button>
                    </li>
                    @endbpCan
                    @bpCan('customers.delete')
                    <li>
                      <form action="{{ route('customer-areas.destroy', $division) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $division->name }}"><i class="fa-solid fa-trash me-2"></i> {{ __('Delete') }}</button>
                      </form>
                    </li>
                    @endbpCan
                  </ul>
                </div>
                @endbpCanAny
              </td>
            </tr>
            @foreach($division->children as $district)
              <tr>
                <td class="ps-4">-- {{ $district->name }}</td>
                <td><span class="bp-badge bp-badge-info">District</span></td>
                <td>{{ $district->delivery_charge > 0 ? currency_symbol() . ' ' . number_format($district->delivery_charge, 0) : '' }}</td>
                <td><x-core::status-toggle :url="route('customer-areas.toggle-status', $district->id)" :active="$district->is_active" /></td>
                <td>
                  @bpCanAny('customers.edit','customers.delete')
                  <div class="dropdown">
                    <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      @bpCan('customers.edit')
                      <li>
                        <button type="button" class="dropdown-item edit-area-btn"
                                data-id="{{ $district->id }}" data-name="{{ $district->name }}"
                                data-level="{{ $district->level }}" data-parent="{{ $district->parent_id }}"
                                data-charge="{{ num_input($district->delivery_charge) }}" data-active="{{ $district->is_active ? 1 : 0 }}"><i class="fa-solid fa-pen me-2"></i> {{ __('Edit') }}</button>
                      </li>
                      @endbpCan
                      @bpCan('customers.delete')
                      <li>
                        <form action="{{ route('customer-areas.destroy', $district) }}" method="POST">
                          @csrf @method('DELETE')
                          <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $district->name }}"><i class="fa-solid fa-trash me-2"></i> {{ __('Delete') }}</button>
                        </form>
                      </li>
                      @endbpCan
                    </ul>
                  </div>
                  @endbpCanAny
                </td>
              </tr>
              @foreach($district->children as $area)
                <tr>
                  <td class="ps-5">---- {{ $area->name }}</td>
                  <td><span class="bp-badge bp-badge-dark">Area</span></td>
                  <td>{{ $area->delivery_charge > 0 ? currency_symbol() . ' ' . number_format($area->delivery_charge, 0) : '' }}</td>
                  <td><x-core::status-toggle :url="route('customer-areas.toggle-status', $area->id)" :active="$area->is_active" /></td>
                  <td>
                    @bpCanAny('customers.edit','customers.delete')
                    <div class="dropdown">
                      <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        @bpCan('customers.edit')
                        <li>
                          <button type="button" class="dropdown-item edit-area-btn"
                                  data-id="{{ $area->id }}" data-name="{{ $area->name }}"
                                  data-level="{{ $area->level }}" data-parent="{{ $area->parent_id }}"
                                  data-charge="{{ num_input($area->delivery_charge) }}" data-active="{{ $area->is_active ? 1 : 0 }}"><i class="fa-solid fa-pen me-2"></i> {{ __('Edit') }}</button>
                        </li>
                        @endbpCan
                        @bpCan('customers.delete')
                        <li>
                          <form action="{{ route('customer-areas.destroy', $area) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $area->name }}"><i class="fa-solid fa-trash me-2"></i> {{ __('Delete') }}</button>
                          </form>
                        </li>
                        @endbpCan
                      </ul>
                    </div>
                    @endbpCanAny
                  </td>
                </tr>
              @endforeach
            @endforeach
          @empty
            <x-core::table.empty colspan="5" icon="fa-solid fa-map-location-dot" title="No areas configured. Add divisions first." />
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit Area Modal -->
<div class="modal fade" id="editAreaModal" tabindex="-1">
  <div class="modal-dialog">
    {{-- Action is set per-row in JS from a route template (no hardcoded path). --}}
    <form method="POST" id="editAreaForm" data-action-template="{{ route('customer-areas.update', 'AREA_ID') }}">
      @csrf @method('PUT')
      {{-- Preserve the row's current status — update() defaults is_active to true when absent. --}}
      <input type="hidden" name="is_active" id="editAreaActive" value="1">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-700">{{ __('Edit Area') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="bp-form-label">{{ __('Level') }} *</label>
            <select class="bp-form-select w-100" name="level" id="editAreaLevel" required>
              <option value="division">{{ __('Division') }}</option>
              <option value="district">{{ __('District') }}</option>
              <option value="area">{{ __('Area') }}</option>
            </select>
          </div>
          <div class="mb-3" id="editParentSelect" style="display:none">
            <label class="bp-form-label">{{ __('Parent') }} *</label>
            <select class="bp-form-select w-100" name="parent_id" id="editParentId">
              <option value="">{{ __('Select Parent') }}</option>
              @foreach($areas as $div)
                <option value="{{ $div->id }}" data-level="division">{{ $div->name }}</option>
                @foreach($div->children as $dist)
                  <option value="{{ $dist->id }}" data-level="district">-- {{ $dist->name }}</option>
                @endforeach
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="bp-form-label">{{ __('Name') }} *</label>
            <input type="text" class="bp-form-control" name="name" id="editAreaName" required maxlength="150">
          </div>
          <div class="mb-3">
            <label class="bp-form-label">{{ __('Delivery Charge') }} ({{ currency_symbol() }})</label>
            <input type="number" class="bp-form-control" name="delivery_charge" id="editAreaCharge" value="0" min="0" step="0.01">
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button><button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>Update</button></div>
      </div>
    </form>
  </div>
</div>

<!-- Add Area Modal -->
<div class="modal fade" id="addAreaModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('customer-areas.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-700">Add Area</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="bp-form-label">Level *</label>
            <select class="bp-form-select w-100" name="level" id="areaLevel" required>
              <option value="division">Division</option>
              <option value="district">District</option>
              <option value="area">Area</option>
            </select>
          </div>
          <div class="mb-3" id="parentSelect" style="display:none">
            <label class="bp-form-label">Parent *</label>
            <select class="bp-form-select w-100" name="parent_id" id="parentId">
              <option value="">Select Parent</option>
              @foreach($areas as $div)
                <option value="{{ $div->id }}" data-level="division">{{ $div->name }}</option>
                @foreach($div->children as $dist)
                  <option value="{{ $dist->id }}" data-level="district">-- {{ $dist->name }}</option>
                @endforeach
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="bp-form-label">Name *</label>
            <input type="text" class="bp-form-control" name="name" required maxlength="150">
          </div>
          <div class="mb-3">
            <label class="bp-form-label">Delivery Charge ({{ currency_symbol() }})</label>
            <input type="number" class="bp-form-control" name="delivery_charge" value="0" min="0" step="0.01">
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button><button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>Save</button></div>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';
$(function () {
    // Toggle parent visibility + filter parent options by the chosen level.
    function toggleParent(levelSel, parentWrap, parentSel) {
        var level = $(levelSel).val();
        if (level === 'division') {
            $(parentWrap).hide();
            $(parentSel).val('');
        } else {
            $(parentWrap).show();
            var showLevel = level === 'district' ? 'division' : 'district';
            $(parentSel + ' option').each(function () {
                var optLevel = $(this).data('level');
                if (!optLevel) return;
                $(this).toggle(optLevel === showLevel);
            });
        }
    }

    $('#areaLevel').on('change', function () {
        toggleParent('#areaLevel', '#parentSelect', '#parentId');
    });

    $('#editAreaLevel').on('change', function () {
        toggleParent('#editAreaLevel', '#editParentSelect', '#editParentId');
    });

    // Populate and open the Edit Area modal from the clicked row (Bug_88).
    $(document).on('click', '.edit-area-btn', function () {
        var $btn = $(this);
        var template = $('#editAreaForm').data('action-template');
        $('#editAreaForm').attr('action', template.replace('AREA_ID', $btn.data('id')));
        $('#editAreaName').val($btn.data('name'));
        $('#editAreaCharge').val($btn.data('charge'));
        $('#editAreaActive').val(String($btn.data('active')) === '1' ? '1' : '0');

        // Level + parent. An area can't be its own parent, so disable that option.
        var selfId = String($btn.data('id'));
        $('#editParentId option').each(function () {
            $(this).prop('disabled', $(this).val() === selfId);
        });
        $('#editAreaLevel').val($btn.data('level') || 'division');
        toggleParent('#editAreaLevel', '#editParentSelect', '#editParentId');
        // Set the current parent AFTER toggling (toggle clears it for divisions).
        var parentId = $btn.data('parent');
        $('#editParentId').val(parentId ? String(parentId) : '');

        new bootstrap.Modal(document.getElementById('editAreaModal')).show();
    });
});
</script>
@endpush
