@extends('core::layouts.master')

@section('title', __("Collections — Website"))
@section('page-title', __("Product Collections"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Collections</span>
@endsection

@section('page-actions')
@bpCan('ecommerce.create')
<a href="{{ route('ecommerce.collections.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> New Collection
</a>
@endbpCan
@endsection

@section('content')

<x-core::table>
  <x-core::table.header>
    <x-core::table.column>Name</x-core::table.column>
    <x-core::table.column>Type</x-core::table.column>
    <x-core::table.column align="center">Products</x-core::table.column>
    <x-core::table.column align="center">Order</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($collections as $collection)
    <tr>
      <td>
        <div class="fw-700">{{ $collection->name }}</div>
        @if($collection->description)
          <div class="fs-12 text-muted">{{ Str::limit($collection->description, 50) }}</div>
        @endif
      </td>
      <td>
        @if($collection->type === 'manual')
          <span class="bp-badge bp-badge-primary">Manual</span>
        @else
          <span class="bp-badge bp-badge-info">Auto</span>
        @endif
      </td>
      <td class="text-center fw-700">{{ number_format($collection->products_count) }}</td>
      <td class="text-center">{{ $collection->sort_order }}</td>
      <td>
        @bpCan('ecommerce.edit')
        <x-core::status-toggle :url="route('ecommerce.collections.toggle-status', $collection->id)" :active="$collection->is_active" />
        @endbpCan
      </td>
      <td>
        @bpCanAny('ecommerce.edit','ecommerce.delete')
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            @bpCan('ecommerce.edit')
            <li><a href="{{ route('ecommerce.collections.edit', $collection) }}" class="dropdown-item"><i class="fa-solid fa-pen"></i> Edit</a></li>
            @endbpCan
            @bpCan('ecommerce.delete')
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="{{ route('ecommerce.collections.destroy', $collection) }}" method="POST" class="delete-form">
                @csrf @method('DELETE')
                <button type="submit" class="dropdown-item text-danger delete-confirm"><i class="fa-solid fa-trash"></i> Delete</button>
              </form>
            </li>
            @endbpCan
          </ul>
        </div>
        @endbpCanAny
      </td>
    </tr>
    @empty
    <x-core::table.empty colspan="6" icon="fa-solid fa-layer-group" title="No collections found" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">Showing {{ $collections->firstItem() ?? 0 }}-{{ $collections->lastItem() ?? 0 }} of {{ number_format($collections->total()) }} collections</span>
        <nav>{{ $collections->appends(request()->query())->onEachSide(1)->links('core::components.table.pagination-links') }}</nav>
      </div>
    </div>
  </x-slot:pagination>
</x-core::table>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Delete confirmation is handled globally by .delete-confirm in app.js
});
</script>
@endpush
