@extends('core::layouts.master')

@section('title', __("Brands"))
@section('page-title', __("Brands"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('products.index') }}">Products</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Brands</span>
@endsection

@section('page-actions')
  @bpCan('brands.export')
  <x-core::export-dropdown module="brands" />
  @endbpCan
  @bpCan('brands.create')
  <a href="{{ route('brands.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add Brand
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-tags"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Brands</div>
          <div class="bp-stat-value">{{ $stats['total'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active</div>
          <div class="bp-stat-value">{{ $stats['active'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-circle-xmark"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Inactive</div>
          <div class="bp-stat-value">{{ $stats['inactive'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-star"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Featured</div>
          <div class="bp-stat-value">{{ $stats['featured'] }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Brands Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar action="{{ route('brands.index') }}" searchPlaceholder="Search brand name, slug...">
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column>Logo</x-core::table.column>
      <x-core::table.column :sortable="true" field="name">Brand Name</x-core::table.column>
      <x-core::table.column>Slug</x-core::table.column>
      <x-core::table.column :sortable="true" field="products_count">Products</x-core::table.column>
      <x-core::table.column>Website</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Featured</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($brands as $brand)
      <tr>
        <td>
          <div class="bp-brand-logo-sm">
            @if($brand->logo)
              <img src="{{ upload_url($brand->logo) }}" alt="{{ $brand->name }}">
            @else
              <i class="fa-solid fa-image text-muted"></i>
            @endif
          </div>
        </td>
        <td class="fw-700 fs-13">{{ $brand->name }}</td>
        <td class="text-muted fs-12">{{ $brand->slug }}</td>
        <td class="fw-600">0</td>
        <td>
          @if($brand->website)
            <a href="{{ $brand->website }}" class="bp-link fs-12" target="_blank">{{ parse_url($brand->website, PHP_URL_HOST) }}</a>
          @endif
        </td>
        <td><x-core::status-toggle :url="route('brands.toggle-status', $brand->id)" :active="$brand->status === 'active'" /></td>
        <td><span class="bp-badge {{ $brand->is_featured ? 'bp-badge-primary' : 'bp-badge-secondary' }}">{{ $brand->is_featured ? 'Yes' : 'No' }}</span></td>
        <td>
          @bpCanAny('brands.edit','brands.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('brands.edit')
              <li><a class="dropdown-item" href="{{ route('brands.edit', $brand) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
              @endbpCan
              <li><hr class="dropdown-divider"></li>
              @bpCan('brands.delete')
              <li>
                <form action="{{ route('brands.destroy', $brand) }}" method="POST">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $brand->name }}"><i class="fa-solid fa-trash"></i> Delete</button>
                </form>
              </li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @empty
      <x-core::table.empty :colspan="8" icon="fa-tags" :title="__('No brands found')" />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$brands" itemLabel="brands" />
    </x-slot:pagination>
  </x-core::table>

@endsection
