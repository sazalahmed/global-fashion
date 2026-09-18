@extends('core::layouts.master')

@section('title', __("Units"))
@section('page-title', __("Units"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('products.index') }}">Products</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Units</span>
@endsection

@section('page-actions')
  @bpCan('units.create')
  <a href="{{ route('units.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add Unit
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-ruler-combined"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Units</div>
          <div class="bp-stat-value">{{ $stats['total'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-cube"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Base Units</div>
          <div class="bp-stat-value">{{ $stats['base'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-arrows-turn-to-dots"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Sub Units</div>
          <div class="bp-stat-value">{{ $stats['sub'] }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Units Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search unit name, short name...">
        <select class="bp-form-select" name="type">
          <option value="">All Types</option>
          <option value="base" {{ request('type') === 'base' ? 'selected' : '' }}>Base Unit</option>
          <option value="sub" {{ request('type') === 'sub' ? 'selected' : '' }}>Sub Unit</option>
        </select>
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column>Unit Name</x-core::table.column>
      <x-core::table.column>Short Name</x-core::table.column>
      <x-core::table.column>Base Unit</x-core::table.column>
      <x-core::table.column>Conversion Factor</x-core::table.column>
      <x-core::table.column>Allows Decimal</x-core::table.column>
      <x-core::table.column>Products Using</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($units as $unit)
        <tr>
          <td class="fw-700 fs-13">{{ $unit->name }}</td>
          <td><span class="bp-badge {{ $unit->is_base ? 'bp-badge-primary' : 'bp-badge-secondary' }}">{{ $unit->short_name }}</span></td>
          <td>
            @if($unit->baseUnit)
              <span class="fw-600">{{ $unit->baseUnit->name }} ({{ $unit->baseUnit->short_name }})</span>
            @endif
          </td>
          <td>
            @if($unit->conversion_factor)
              <span class="fw-600">{{ $unit->conversion_factor }}</span>
            @endif
          </td>
          <td><span class="bp-badge {{ $unit->allow_decimal ? 'bp-badge-success' : 'bp-badge-danger' }}">{{ $unit->allow_decimal ? 'Yes' : 'No' }}</span></td>
          <td class="fw-600">0</td>
          <td><x-core::status-toggle :url="route('units.toggle-status', $unit->id)" :active="$unit->status === 'active'" /></td>
          <td>
            @bpCanAny('units.edit','units.delete')
            <div class="dropdown">
              <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
              <ul class="dropdown-menu dropdown-menu-end">
                @bpCan('units.edit')
                <li><a class="dropdown-item" href="{{ route('units.edit', $unit) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
                @endbpCan
                <li><hr class="dropdown-divider"></li>
                @bpCan('units.delete')
                <li>
                  <form action="{{ route('units.destroy', $unit) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $unit->name }}"><i class="fa-solid fa-trash"></i> Delete</button>
                  </form>
                </li>
                @endbpCan
              </ul>
            </div>
            @endbpCanAny
          </td>
        </tr>
      @empty
        <x-core::table.empty :colspan="8" icon="fa-ruler-combined" :title="__('No units found')" />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$units" itemLabel="units" />
    </x-slot:pagination>
  </x-core::table>

@endsection
