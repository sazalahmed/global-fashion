@extends('core::layouts.master')

@section('title', __("Landing Pages"))
@section('page-title', __("Landing Pages"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Landing Pages</span>
@endsection

@section('page-actions')
<form action="{{ route('landing-pages.deactivate') }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="bp-btn bp-btn-outline"><i class="fa-solid fa-globe me-1"></i> Switch to Full Site</button>
</form>
<a href="{{ route('landing-pages.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i> New Landing Page</a>
@endsection

@section('content')

<div class="bp-card">
    <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
            <table class="bp-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Template</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                    <tr>
                        <td class="fw-700">{{ $page->name }}</td>
                        <td><span class="bp-badge bp-badge-primary">{{ $page->template_name }}</span></td>
                        <td>{{ count($page->product_ids ?? []) }} products</td>
                        <td>
                            @if($page->is_active)
                                <span class="bp-badge bp-badge-success"><i class="fa-solid fa-check me-1"></i>Active</span>
                            @else
                                <span class="bp-badge bp-badge-dark">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $page->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('landing-pages.preview', $page) }}" target="_blank"><i class="fa-solid fa-eye me-2"></i> Preview</a></li>
                                    <li><a class="dropdown-item" href="{{ route('landing-pages.edit', $page) }}"><i class="fa-solid fa-pen me-2"></i> Edit</a></li>
                                    @if(!$page->is_active)
                                    <li>
                                        <form action="{{ route('landing-pages.activate', $page) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item"><i class="fa-solid fa-rocket me-2"></i> Activate</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form action="{{ route('landing-pages.destroy', $page) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $page->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                                        </form>
                                    </li>
                                    @else
                                    <li>
                                        <form action="{{ route('landing-pages.deactivate') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item"><i class="fa-solid fa-power-off me-2"></i> Deactivate</button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <x-core::table.empty :colspan="6" icon="fa-rocket" title="No landing pages yet.">
                        <a href="{{ route('landing-pages.create') }}" class="bp-btn bp-btn-sm bp-btn-primary"><i class="fa-solid fa-plus me-1"></i>Create one</a>
                    </x-core::table.empty>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pages->hasPages())
    <x-core::table.pagination :paginator="$pages" itemLabel="landing pages" />
    @endif
</div>

@endsection
