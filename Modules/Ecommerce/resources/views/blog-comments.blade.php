@extends('core::layouts.master')

@section('title', __("Blog Comments — Website"))
@section('page-title', __("Blog Comments"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.blog') }}">Blog</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Comments</span>
@endsection

@section('content')

<x-core::table>
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search comments...">
      <select class="bp-form-select" name="status">
        <option value="">All Comments</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-core::table.header>
    <x-core::table.column>Author</x-core::table.column>
    <x-core::table.column>Comment</x-core::table.column>
    <x-core::table.column>Post</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Submitted</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($comments as $comment)
    <tr>
      <td>
        <div class="fw-700">{{ $comment->name }}</div>
        <div class="fs-12 text-muted">{{ $comment->email }}</div>
        @if($comment->phone)
          <div class="fs-12 text-muted">{{ $comment->phone }}</div>
        @endif
      </td>
      <td>
        @if($comment->subject)
          <div class="fw-600 fs-13">{{ $comment->subject }}</div>
        @endif
        <div class="fs-12 text-muted">{{ Str::limit($comment->comment, 120) }}</div>
      </td>
      <td>
        @if($comment->post)
          <a href="{{ route('storefront.blog.show', $comment->post->slug) }}" target="_blank" class="fs-12">{{ Str::limit($comment->post->title, 40) }}</a>
        @else
          <span class="text-muted">--</span>
        @endif
      </td>
      <td>
        @if($comment->is_approved)
          <span class="bp-badge bp-badge-success">Approved</span>
        @else
          <span class="bp-badge bp-badge-warning">Pending</span>
        @endif
      </td>
      <td class="fs-12">{{ $comment->created_at->format('d M Y') }}</td>
      <td>
        @bpCanAny('ecommerce.edit','ecommerce.delete')
        <div class="dropdown">
          <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
          <ul class="dropdown-menu dropdown-menu-end">
            @bpCan('ecommerce.edit')
            @if($comment->is_approved)
              <li>
                <form action="{{ route('ecommerce.blog-comments.unapprove', $comment) }}" method="POST">
                  @csrf @method('PATCH')
                  <button type="submit" class="dropdown-item"><i class="fa-solid fa-clock"></i> Move to Pending</button>
                </form>
              </li>
            @else
              <li>
                <form action="{{ route('ecommerce.blog-comments.approve', $comment) }}" method="POST">
                  @csrf @method('PATCH')
                  <button type="submit" class="dropdown-item text-success"><i class="fa-solid fa-check"></i> Approve</button>
                </form>
              </li>
            @endif
            @endbpCan
            @bpCan('ecommerce.delete')
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="{{ route('ecommerce.blog-comments.destroy', $comment) }}" method="POST" class="delete-form">
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
    <x-core::table.empty colspan="6" icon="fa-solid fa-comments" title="No comments found" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">Showing {{ $comments->firstItem() ?? 0 }}-{{ $comments->lastItem() ?? 0 }} of {{ number_format($comments->total()) }} comments</span>
        <nav>{{ $comments->appends(request()->query())->onEachSide(1)->links('core::components.table.pagination-links') }}</nav>
      </div>
    </div>
  </x-slot:pagination>
</x-core::table>

@endsection
