@extends('core::layouts.master')

@section('title', __("Categories"))
@section('page-title', __("Categories"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Products</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Categories</span>
@endsection

@section('page-actions')
  @bpCan('categories.create')
  <a href="{{ route('categories.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add Category
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-layer-group"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Categories</div>
          <div class="bp-stat-value">{{ number_format($stats['total']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active</div>
          <div class="bp-stat-value">{{ number_format($stats['active']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-circle-xmark"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Inactive</div>
          <div class="bp-stat-value">{{ number_format($stats['inactive']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">With Products</div>
          <div class="bp-stat-value">{{ number_format($stats['withProducts']) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Categories Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search categories...">
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <select class="bp-form-select" name="parent_id">
          <option value="">All Parents</option>
          @foreach($parentOptions as $parent)
            <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
          @endforeach
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column class="bp-reorder-col" title="Drag to reorder">&nbsp;</x-core::table.column>
      <x-core::table.column>Image</x-core::table.column>
      <x-core::table.column :sortable="true" field="name">Name</x-core::table.column>
      <x-core::table.column>Parent Category</x-core::table.column>
      <x-core::table.column>Slug</x-core::table.column>
      <x-core::table.column :sortable="true" field="products_count">Products</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column :sortable="true" field="sort_order">Sort Order</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    @php
      // Drag-reorder is only safe on the full, unfiltered tree — a filtered
      // subset would re-sequence only visible rows and corrupt hidden siblings.
      $treeSortable = ! request()->filled('search')
        && ! request()->filled('status')
        && ! request()->filled('parent_id');
    @endphp
    <tbody class="{{ $treeSortable ? 'bp-reorderable bp-cat-tree-body' : '' }}"
           @if($treeSortable) data-reorder-tree="1" data-reorder-url="{{ route('categories.reorder') }}" @endif>
      @forelse($categories as $category)
        <tr data-id="{{ $category->id }}" data-parent="{{ $category->parent_id ?? 0 }}" data-depth="{{ $category->depth ?? 0 }}" @class(['bp-cat-sep' => $category->tree_separator ?? false])>
          <td class="bp-drag-handle" title="{{ $treeSortable ? __('Drag to reorder within the same parent') : __('Clear filters to reorder') }}">
            @if($treeSortable)<i class="fa-solid fa-grip-vertical"></i>@endif
          </td>
          <td>
            <div class="bp-category-img">
              @if($category->image)
                <img src="{{ upload_url($category->image) }}" alt="{{ $category->name }}">
              @else
                <i class="fa-solid fa-layer-group"></i>
              @endif
            </div>
          </td>
          <td class="fw-700 fs-13 bp-cat-name-col">
            <span class="bp-cat-tree depth-{{ min($category->depth ?? 0, 6) }}">
              @if(($category->depth ?? 0) > 0)
                <i class="fa-solid fa-arrow-turn-up fa-rotate-90 bp-cat-tree-icon"></i>
              @endif
              {{ $category->name }}
            </span>
          </td>
          <td class="fs-13">
            @if($category->parent)
              {{ $category->parent->name }}
            @endif
          </td>
          <td class="fs-12 text-muted">{{ $category->slug }}</td>
          <td class="fw-700">0</td>
          <td>
            <label class="form-check form-switch mb-0 bp-status-switch" title="Toggle active status">
              <input class="form-check-input bp-status-toggle" type="checkbox" role="switch"
                     data-url="{{ route('categories.toggle-status', $category->id) }}"
                     {{ $category->is_active ? 'checked' : '' }}>
              <span class="bp-status-switch-label fs-12 fw-600">{{ $category->is_active ? __('Active') : __('Inactive') }}</span>
            </label>
          </td>
          <td>{{ $category->sort_order }}</td>
          <td>
            @bpCanAny('categories.edit','categories.delete')
            <div class="dropdown">
              <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
              <ul class="dropdown-menu dropdown-menu-end">
                @bpCan('categories.edit')
                <li><a class="dropdown-item" href="{{ route('categories.edit', $category->id) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
                @endbpCan
                <li><hr class="dropdown-divider"></li>
                @bpCan('categories.delete')
                <li>
                  <form action="{{ route('categories.destroy', $category->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $category->name }}"><i class="fa-solid fa-trash"></i> Delete</button>
                  </form>
                </li>
                @endbpCan
              </ul>
            </div>
            @endbpCanAny
          </td>
        </tr>
      @empty
        <x-core::table.empty :colspan="9" icon="fa-layer-group" :title="__('No categories found')" />
      @endforelse
    </tbody>
  </x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Inline status toggle — flips active/inactive via AJAX without a reload.
    $(document).on('change', '.bp-status-toggle', function () {
        var $input = $(this);
        var $label = $input.closest('.bp-status-switch').find('.bp-status-switch-label');
        $input.prop('disabled', true);
        $.ajax({
            url: $input.data('url'),
            method: 'PATCH',
            success: function (res) {
                $input.prop('checked', res.is_active);
                $label.text(res.is_active ? '{{ __("Active") }}' : '{{ __("Inactive") }}');
                if (typeof showToast === 'function') {
                    showToast(res.message || '{{ __("Category status updated.") }}', 'success');
                }
            },
            error: function () {
                // Revert the visual state on failure.
                $input.prop('checked', !$input.prop('checked'));
                if (typeof showToast === 'function') {
                    showToast('{{ __("Failed to update status. Please try again.") }}', 'danger');
                }
            },
            complete: function () { $input.prop('disabled', false); }
        });
    });
});
</script>
@endpush
