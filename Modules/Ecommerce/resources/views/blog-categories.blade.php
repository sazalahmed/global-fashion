@extends('core::layouts.master')

@section('title', __("Blog Categories — Website"))
@section('page-title', __("Blog Categories"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Blog Categories</span>
@endsection

@section('page-actions')
@bpCan('ecommerce.create')
<a href="{{ route('ecommerce.blog-categories.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> New Category
</a>
@endbpCan
@endsection

@section('content')

<x-core::table>
  <x-core::table.header>
    <x-core::table.column>Name</x-core::table.column>
    <x-core::table.column>Slug</x-core::table.column>
    <x-core::table.column align="center">Posts</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($categories as $category)
    <tr>
      <td><div class="fw-700">{{ $category->name }}</div></td>
      <td><code>{{ $category->slug }}</code></td>
      <td class="text-center fw-700">{{ number_format($category->posts_count) }}</td>
      <td>
        @bpCan('ecommerce.edit')
        <x-core::status-toggle :url="route('ecommerce.blog-categories.toggle-status', $category->id)" :active="$category->is_active" />
        @endbpCan
      </td>
      <td>
        @bpCanAny('ecommerce.edit','ecommerce.delete')
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            @bpCan('ecommerce.edit')
            <li><a href="{{ route('ecommerce.blog-categories.edit', $category) }}" class="dropdown-item"><i class="fa-solid fa-pen"></i> Edit</a></li>
            @endbpCan
            @bpCan('ecommerce.delete')
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="{{ route('ecommerce.blog-categories.destroy', $category) }}" method="POST" class="delete-form">
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
    <x-core::table.empty colspan="5" icon="fa-solid fa-folder-tree" title="No blog categories found" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">Showing {{ $categories->firstItem() ?? 0 }}-{{ $categories->lastItem() ?? 0 }} of {{ number_format($categories->total()) }} categories</span>
        <nav>{{ $categories->appends(request()->query())->onEachSide(1)->links('core::components.table.pagination-links') }}</nav>
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
