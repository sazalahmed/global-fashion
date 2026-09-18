@extends('core::layouts.master')

@section('title', __('Asset Categories'))
@section('page-title', __('Asset Categories'))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('assets.index') }}">Assets</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Categories</span>
@endsection

@section('page-actions')
  @bpCan('finance.create')
  <a href="{{ route('asset-categories.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i> New Category</a>
  @endbpCan
@endsection

@section('content')

<div class="bp-card">
  <div class="bp-card-header">
    <form action="{{ route('asset-categories.index') }}" method="GET" class="bp-filter-bar w-100">
      <div class="bp-table-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" placeholder="Search categories..." value="{{ request('search') }}">
      </div>
      <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary bp-filter-submit" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
      <button type="button" class="bp-btn bp-btn-sm bp-btn-danger bp-filter-reset" title="Reset Filters"><i class="fa-solid fa-rotate"></i></button>
    </form>
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Method</th>
            <th>Useful Life</th>
            <th>Rate</th>
            <th>Assets</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($categories as $cat)
            <tr>
              <td class="fw-700">{{ $cat->name }}</td>
              <td class="fs-13">{{ ucfirst(str_replace('_', ' ', $cat->depreciation_method ?? '')) }}</td>
              <td>{{ $cat->useful_life_years ? $cat->useful_life_years . ' yrs' : '' }}</td>
              <td>{{ $cat->depreciation_rate ? num($cat->depreciation_rate) . '%' : '—' }}</td>
              <td><span class="bp-badge bp-badge-info">{{ $cat->assets_count }}</span></td>
              <td>
                @bpCanAny('finance.edit','finance.delete')
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    @bpCan('finance.edit')
                    <li><a class="dropdown-item" href="{{ route('asset-categories.edit', $cat) }}"><i class="fa-solid fa-pen me-2"></i> Edit</a></li>
                    @endbpCan
                    @bpCan('finance.delete')
                    <li>
                      <form action="{{ route('asset-categories.destroy', $cat) }}" method="POST" onsubmit="return confirm('Delete this category?');">
                        @csrf @method('DELETE')
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
            <x-core::table.empty colspan="6" icon="fa-solid fa-layer-group" title="No categories yet" />
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($categories->hasPages())
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">Showing {{ $categories->firstItem() }}-{{ $categories->lastItem() }} of {{ $categories->total() }}</span>
        {{ $categories->links('core::components.table.pagination-links') }}
      </div>
    </div>
  @endif
</div>

@endsection
