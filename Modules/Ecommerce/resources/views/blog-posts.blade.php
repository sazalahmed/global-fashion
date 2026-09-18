@extends('core::layouts.master')

@section('title', __("Blog — Website"))
@section('page-title', __("Blog Posts"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Blog</span>
@endsection

@section('page-actions')
@bpCan('ecommerce.create')
<a href="{{ route('ecommerce.blog.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> New Post
</a>
@endbpCan
@endsection

@section('content')

<x-core::table>
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search posts...">
      <select class="bp-form-select" name="is_published">
        <option value="">All Status</option>
        <option value="1" {{ request('is_published') === '1' ? 'selected' : '' }}>Published</option>
        <option value="0" {{ request('is_published') === '0' ? 'selected' : '' }}>Draft</option>
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-core::table.header>
    <x-core::table.column>Image</x-core::table.column>
    <x-core::table.column>Title</x-core::table.column>
    <x-core::table.column>Category</x-core::table.column>
    <x-core::table.column>Author</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Published</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($posts as $post)
    <tr>
      <td>
        @if($post->featured_image)
          <img src="{{ upload_url($post->featured_image) }}" alt="{{ $post->title }}" class="bp-table-thumb">
        @else
          <span class="text-muted fs-12">No image</span>
        @endif
      </td>
      <td>
        <div class="fw-700">{{ $post->title }}</div>
        @if($post->excerpt)
          <div class="fs-12 text-muted">{{ Str::limit($post->excerpt, 50) }}</div>
        @endif
      </td>
      <td>
        @if($post->category)
          <span class="bp-badge bp-badge-info">{{ $post->category->name }}</span>
        @else
          <span class="text-muted">--</span>
        @endif
      </td>
      <td>{{ $post->author?->name ?? '' }}</td>
      <td>
        @if($post->is_published)
          <span class="bp-badge bp-badge-success">Published</span>
        @else
          <span class="bp-badge bp-badge-dark">Draft</span>
        @endif
      </td>
      <td>{{ $post->published_at ? $post->published_at->format('d M Y') : '' }}</td>
      <td>
        @bpCanAny('ecommerce.view','ecommerce.edit','ecommerce.delete')
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            @bpCan('ecommerce.view')
            @if($post->is_published)
              <li><a href="{{ route('storefront.blog.show', $post->slug) }}" class="dropdown-item" target="_blank" rel="noopener"><i class="fa-solid fa-eye"></i> View</a></li>
            @endif
            @endbpCan
            @bpCan('ecommerce.edit')
            <li><a href="{{ route('ecommerce.blog.edit', $post) }}" class="dropdown-item"><i class="fa-solid fa-pen"></i> Edit</a></li>
            @endbpCan
            @bpCan('ecommerce.delete')
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="{{ route('ecommerce.blog.destroy', $post) }}" method="POST" class="delete-form">
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
    <x-core::table.empty colspan="7" icon="fa-solid fa-newspaper" title="No blog posts found" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">Showing {{ $posts->firstItem() ?? 0 }}-{{ $posts->lastItem() ?? 0 }} of {{ number_format($posts->total()) }} posts</span>
        <nav>{{ $posts->appends(request()->query())->onEachSide(1)->links('core::components.table.pagination-links') }}</nav>
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
