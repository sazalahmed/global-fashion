@extends('core::layouts.master')

@section('title', __('Locations'))
@section('page-title', __('Locations'))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ __('Locations') }}</span>
@endsection

@section('content')

<ul class="nav nav-tabs bp-tabs mb-3" role="tablist">
  <li class="nav-item">
    <button class="nav-link {{ $tab === 'districts' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabDistricts" type="button">
      <i class="fa-solid fa-map me-1"></i> {{ __('Districts') }} <span class="bp-badge bp-badge-secondary ms-1">{{ $allDistricts->count() }}</span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link {{ $tab === 'thanas' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabThanas" type="button">
      <i class="fa-solid fa-map-pin me-1"></i> {{ __('Thanas') }} <span class="bp-badge bp-badge-secondary ms-1">{{ $thanas->total() }}</span>
    </button>
  </li>
</ul>

<div class="tab-content">

  {{-- ═══════════ DISTRICTS TAB ═══════════ --}}
  <div class="tab-pane fade {{ $tab === 'districts' ? 'show active' : '' }}" id="tabDistricts" role="tabpanel">
    <div class="row g-3">

      {{-- Add District form --}}
      @bpCan('locations.create')
      <div class="col-lg-4">
        <div class="bp-card">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-plus me-2"></i>{{ __('Add District') }}</h5>
          </div>
          <form action="{{ route('locations.districts.store') }}" method="POST">
            @csrf
            <div class="bp-card-body">
              <div class="mb-3">
                <label class="bp-form-label">{{ __('District Name (English)') }} *</label>
                <input type="text" class="bp-form-control" name="district_name" value="{{ old('district_name') }}" required placeholder="{{ __('e.g. Dhaka') }}">
                @error('district_name')<div class="text-danger fs-12 mt-1">{{ $message }}</div>@enderror
              </div>
              <div class="mb-3">
                <label class="bp-form-label">{{ __('Bengali Name') }}</label>
                <input type="text" class="bp-form-control" name="bn_name" value="{{ old('bn_name') }}" placeholder="যেমন: ঢাকা">
                @error('bn_name')<div class="text-danger fs-12 mt-1">{{ $message }}</div>@enderror
              </div>
              <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" class="form-check-input" role="switch" id="districtActive" name="is_active" value="1" checked>
                <label class="form-check-label" for="districtActive">{{ __('Active') }}</label>
              </div>
            </div>
            <div class="bp-card-footer text-end">
              <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> {{ __('Save') }}</button>
            </div>
          </form>
        </div>
      </div>
      @endbpCan

      {{-- Districts table --}}
      <div class="col-lg-8">
        <div class="bp-card">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>{{ __('All Districts') }}</h5>
            <form method="GET" class="bp-inline-search">
              <input type="hidden" name="tab" value="districts">
              <input type="text" name="district_search" class="bp-form-control bp-form-control-sm" placeholder="{{ __('Search district...') }}" value="{{ request('district_search') }}">
              <button class="bp-btn bp-btn-sm bp-btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
          </div>
          <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
              <table class="bp-table">
                <thead>
                  <tr>
                    <th>{{ __('District (English)') }}</th>
                    <th>{{ __('Bengali Name') }}</th>
                    <th>{{ __('Thanas') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($districts as $d)
                    <tr>
                      <td class="fw-600">{{ $d->district_name }}</td>
                      <td>{{ $d->bn_name }}</td>
                      <td><span class="bp-badge bp-badge-info">{{ $d->thanas_count }}</span></td>
                      <td>
                        @if($d->is_active)
                          <span class="bp-badge bp-badge-success">{{ __('Active') }}</span>
                        @else
                          <span class="bp-badge bp-badge-secondary">{{ __('Inactive') }}</span>
                        @endif
                      </td>
                      <td class="text-end">
                        @bpCanAny('locations.edit','locations.delete')
                        <div class="dropdown">
                          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            @bpCan('locations.edit')
                            <li>
                              <button type="button" class="dropdown-item btn-edit-district"
                                      data-id="{{ $d->id }}"
                                      data-name="{{ $d->district_name }}"
                                      data-bn="{{ $d->bn_name }}"
                                      data-active="{{ $d->is_active ? 1 : 0 }}"
                                      data-bs-toggle="modal" data-bs-target="#editDistrictModal">
                                <i class="fa-solid fa-pen me-2"></i> Edit
                              </button>
                            </li>
                            @endbpCan
                            @bpCan('locations.delete')
                            <li>
                              <form action="{{ route('locations.districts.destroy', $d) }}" method="POST" onsubmit="return confirm('{{ __('Delete this district?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                              </form>
                            </li>
                            @endbpCan
                          </ul>
                        </div>
                        @endbpCanAny
                      </td>
                    </tr>
                  @empty
                    <x-core::table.empty :colspan="5" icon="fa-map-location-dot" :title="__('No districts found.')" />
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          <div class="bp-card-footer">
            {{ $districts->links('core::components.table.pagination-links') }}
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ═══════════ THANAS TAB ═══════════ --}}
  <div class="tab-pane fade {{ $tab === 'thanas' ? 'show active' : '' }}" id="tabThanas" role="tabpanel">
    <div class="row g-3">

      {{-- Add Thana form --}}
      @bpCan('locations.create')
      <div class="col-lg-4">
        <div class="bp-card">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-plus me-2"></i>{{ __('Add Thana') }}</h5>
          </div>
          <form action="{{ route('locations.thanas.store') }}" method="POST">
            @csrf
            <div class="bp-card-body">
              <div class="mb-3">
                <label class="bp-form-label">{{ __('District') }} *</label>
                <select class="bp-form-select w-100" name="district_id" required>
                  <option value="">{{ __('Select district...') }}</option>
                  @foreach($allDistricts as $opt)
                    <option value="{{ $opt->id }}" {{ old('district_id') == $opt->id ? 'selected' : '' }}>{{ $opt->district_name }}</option>
                  @endforeach
                </select>
                @error('district_id')<div class="text-danger fs-12 mt-1">{{ $message }}</div>@enderror
              </div>
              <div class="mb-3">
                <label class="bp-form-label">{{ __('Thana Name (English)') }} *</label>
                <input type="text" class="bp-form-control" name="thana_name" value="{{ old('thana_name') }}" required placeholder="{{ __('e.g. Gulshan') }}">
                @error('thana_name')<div class="text-danger fs-12 mt-1">{{ $message }}</div>@enderror
              </div>
              <div class="mb-3">
                <label class="bp-form-label">{{ __('Bengali Name') }}</label>
                <input type="text" class="bp-form-control" name="bn_name" value="{{ old('bn_name') }}">
                @error('bn_name')<div class="text-danger fs-12 mt-1">{{ $message }}</div>@enderror
              </div>
              <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" class="form-check-input" role="switch" id="thanaActive" name="is_active" value="1" checked>
                <label class="form-check-label" for="thanaActive">{{ __('Active') }}</label>
              </div>
            </div>
            <div class="bp-card-footer text-end">
              <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> {{ __('Save') }}</button>
            </div>
          </form>
        </div>
      </div>
      @endbpCan

      {{-- Thanas table --}}
      <div class="col-lg-8">
        <div class="bp-card">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>{{ __('All Thanas') }}</h5>
            <form method="GET" class="bp-inline-search">
              <input type="hidden" name="tab" value="thanas">
              <select name="filter_district_id" class="bp-form-select bp-form-select-sm select2-search">
                <option value="">{{ __('All districts') }}</option>
                @foreach($allDistricts as $opt)
                  <option value="{{ $opt->id }}" {{ request('filter_district_id') == $opt->id ? 'selected' : '' }}>{{ $opt->district_name }}</option>
                @endforeach
              </select>
              <input type="text" name="thana_search" class="bp-form-control bp-form-control-sm" placeholder="{{ __('Search thana...') }}" value="{{ request('thana_search') }}">
              <button class="bp-btn bp-btn-sm bp-btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
          </div>
          <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
              <table class="bp-table">
                <thead>
                  <tr>
                    <th>{{ __('Thana (English)') }}</th>
                    <th>{{ __('Bengali Name') }}</th>
                    <th>{{ __('District') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($thanas as $t)
                    <tr>
                      <td class="fw-600">{{ $t->thana_name }}</td>
                      <td>{{ $t->bn_name }}</td>
                      <td>{{ $t->district?->district_name }}</td>
                      <td>
                        @if($t->is_active)
                          <span class="bp-badge bp-badge-success">{{ __('Active') }}</span>
                        @else
                          <span class="bp-badge bp-badge-secondary">{{ __('Inactive') }}</span>
                        @endif
                      </td>
                      <td class="text-end">
                        @bpCanAny('locations.edit','locations.delete')
                        <div class="dropdown">
                          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            @bpCan('locations.edit')
                            <li>
                              <button type="button" class="dropdown-item btn-edit-thana"
                                      data-id="{{ $t->id }}"
                                      data-district="{{ $t->district_id }}"
                                      data-name="{{ $t->thana_name }}"
                                      data-bn="{{ $t->bn_name }}"
                                      data-active="{{ $t->is_active ? 1 : 0 }}"
                                      data-bs-toggle="modal" data-bs-target="#editThanaModal">
                                <i class="fa-solid fa-pen me-2"></i> Edit
                              </button>
                            </li>
                            @endbpCan
                            @bpCan('locations.delete')
                            <li>
                              <form action="{{ route('locations.thanas.destroy', $t) }}" method="POST" onsubmit="return confirm('{{ __('Delete this thana?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                              </form>
                            </li>
                            @endbpCan
                          </ul>
                        </div>
                        @endbpCanAny
                      </td>
                    </tr>
                  @empty
                    <x-core::table.empty :colspan="5" icon="fa-location-dot" :title="__('No thanas found.')" />
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          <div class="bp-card-footer">
            {{ $thanas->links('core::components.table.pagination-links') }}
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- ── Edit District Modal ── --}}
<div class="modal fade" id="editDistrictModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form id="editDistrictForm" method="POST">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>{{ __('Edit District') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="bp-form-label">{{ __('District Name (English)') }} *</label>
            <input type="text" class="bp-form-control" name="district_name" id="editDistrictName" required>
          </div>
          <div class="mb-3">
            <label class="bp-form-label">{{ __('Bengali Name') }}</label>
            <input type="text" class="bp-form-control" name="bn_name" id="editDistrictBn">
          </div>
          <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" role="switch" id="editDistrictActive" name="is_active" value="1">
            <label class="form-check-label" for="editDistrictActive">{{ __('Active') }}</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</button>
          <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> {{ __('Update') }}</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- ── Edit Thana Modal ── --}}
<div class="modal fade" id="editThanaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form id="editThanaForm" method="POST">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>{{ __('Edit Thana') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="bp-form-label">{{ __('District') }} *</label>
            <select class="bp-form-select w-100" name="district_id" id="editThanaDistrict" required>
              @foreach($allDistricts as $opt)
                <option value="{{ $opt->id }}">{{ $opt->district_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="bp-form-label">{{ __('Thana Name (English)') }} *</label>
            <input type="text" class="bp-form-control" name="thana_name" id="editThanaName" required>
          </div>
          <div class="mb-3">
            <label class="bp-form-label">{{ __('Bengali Name') }}</label>
            <input type="text" class="bp-form-control" name="bn_name" id="editThanaBn">
          </div>
          <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" role="switch" id="editThanaActive" name="is_active" value="1">
            <label class="form-check-label" for="editThanaActive">{{ __('Active') }}</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</button>
          <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> {{ __('Update') }}</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
  // ── Edit District ──
  $(document).on('click', '.btn-edit-district', function () {
    var id     = $(this).data('id');
    var name   = $(this).data('name');
    var bn     = $(this).data('bn');
    var active = $(this).data('active');
    $('#editDistrictForm').attr('action', '{{ url('admin/locations/districts') }}/' + id);
    $('#editDistrictName').val(name);
    $('#editDistrictBn').val(bn);
    $('#editDistrictActive').prop('checked', String(active) === '1');
  });

  // ── Edit Thana ──
  $(document).on('click', '.btn-edit-thana', function () {
    var id       = $(this).data('id');
    var district = $(this).data('district');
    var name     = $(this).data('name');
    var bn       = $(this).data('bn');
    var active   = $(this).data('active');
    $('#editThanaForm').attr('action', '{{ url('admin/locations/thanas') }}/' + id);
    $('#editThanaDistrict').val(district);
    $('#editThanaName').val(name);
    $('#editThanaBn').val(bn);
    $('#editThanaActive').prop('checked', String(active) === '1');
  });

  // ── Debounced live search for districts & thanas ──
  ['district_search', 'thana_search'].forEach(function (name) {
    var input = document.querySelector('input[name="' + name + '"]');
    if (!input) { return; }
    var timer;
    input.addEventListener('keyup', function () {
      var form = this.closest('form');
      clearTimeout(timer);
      timer = setTimeout(function () { form.submit(); }, 400);
    });
  });
});
</script>
@endpush
