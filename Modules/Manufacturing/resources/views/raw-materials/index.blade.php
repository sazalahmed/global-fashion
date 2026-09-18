@extends('core::layouts.master')

@section('title', __("Raw Materials"))
@section('page-title', __("Raw Materials"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Raw Materials</span>
@endsection

@section('page-actions')
  @bpCan('manufacturing.create')
  <a href="{{ route('manufacturing.raw-materials.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add Raw Material
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-cubes"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Materials</div>
          <div class="bp-stat-value">{{ $rawMaterials->total() }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active</div>
          <div class="bp-stat-value">{{ $rawMaterials->where('is_active', true)->count() }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-scissors"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Fabric Items</div>
          <div class="bp-stat-value">{{ $rawMaterials->where('category', 'fabric')->count() }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Raw Materials Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search material name, code...">
        <select class="bp-form-select" name="category">
          <option value="">All Categories</option>
          @foreach($categories as $key => $label)
            <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="is_active">
          <option value="">All Status</option>
          <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
          <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column>Code</x-core::table.column>
      <x-core::table.column>Name</x-core::table.column>
      <x-core::table.column>Category</x-core::table.column>
      <x-core::table.column>Unit</x-core::table.column>
      <x-core::table.column align="end">Cost Price</x-core::table.column>
      <x-core::table.column>Reorder Level</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($rawMaterials as $material)
      <tr>
        <td class="fw-600 fs-12">{{ $material->code }}</td>
        <td class="fw-700 fs-13">{{ $material->name }}</td>
        <td>
          @switch($material->category)
            @case('fabric')
              <span class="bp-badge bp-badge-primary">Fabric</span>
              @break
            @case('thread')
              <span class="bp-badge bp-badge-info">Thread</span>
              @break
            @case('button')
              <span class="bp-badge bp-badge-secondary">Button</span>
              @break
            @case('zipper')
              <span class="bp-badge bp-badge-warning">Zipper</span>
              @break
            @default
              <span class="bp-badge bp-badge-dark">Other</span>
          @endswitch
        </td>
        <td>{{ ucfirst($material->unit) }}</td>
        <td class="text-end fw-600">{{ money($material->cost_price) }}</td>
        <td>{{ $material->reorder_level ?? '' }}</td>
        <td>
          <x-core::status-toggle :url="route('manufacturing.raw-materials.toggle-status', $material->id)" :active="$material->is_active" />
        </td>
        <td>
          @bpCanAny('manufacturing.view', 'manufacturing.edit', 'manufacturing.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('manufacturing.view')
              <li><a class="dropdown-item" href="{{ route('manufacturing.raw-materials.show', $material) }}"><i class="fa-solid fa-eye"></i> View</a></li>
              @endbpCan
              @bpCan('manufacturing.edit')
              <li><a class="dropdown-item" href="{{ route('manufacturing.raw-materials.edit', $material) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
              @endbpCan
              @bpCan('manufacturing.delete')
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="{{ route('manufacturing.raw-materials.destroy', $material) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete {{ $material->name }}?')"><i class="fa-solid fa-trash"></i> Delete</button>
                </form>
              </li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="8" icon="fa-solid fa-cubes" title="No raw materials found." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$rawMaterials" itemLabel="raw materials" />
    </x-slot:pagination>
  </x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function() {
    $('.bp-filter-reset').on('click', function() {
        // Clear all filters — go to the clean path (no query string).
        window.location.href = window.location.pathname;
    });
});
</script>
@endpush
