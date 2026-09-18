@extends('core::layouts.master')

@section('title', __('Pages — Website'))
@section('page-title', __('Pages'))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Pages</span>
@endsection

@section('page-actions')
<a href="{{ route('ecommerce.pages.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> Add Page
</a>
@endsection

@section('content')

<div class="bp-card">
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>{{ __('Title') }}</th>
            <th>{{ __('URL') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pages as $page)
            <tr>
              <td class="fw-700 fs-13">{{ $page->title }}</td>
              <td><a href="{{ route('storefront.page.show', $page->slug) }}" target="_blank" class="fs-12">/{{ $page->slug }}</a></td>
              <td>
                <x-core::status-toggle :url="route('ecommerce.pages.toggle-status', $page)"
                                       :active="$page->is_published"
                                       :activeText="__('Published')"
                                       :inactiveText="__('Draft')" />
              </td>
              <td>
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-start">
                    <li><a class="dropdown-item" href="{{ route('ecommerce.pages.edit', $page) }}"><i class="fa-solid fa-pen me-2"></i>Edit</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form action="{{ route('ecommerce.pages.destroy', $page) }}" method="POST" class="delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $page->title }}">
                          <i class="fa-solid fa-trash me-2"></i> Delete
                        </button>
                      </form>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                {{ __('No pages yet. Create your first page.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection
