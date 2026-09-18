@extends('core::layouts.master')

@section('title', 'Adjustment Reasons')
@section('page-title', 'Adjustment Reasons')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('inventory.adjustments') }}">Adjustments</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Reasons</span>
@endsection

@section('page-actions')
    @bpCan('inventory.create')
        <a href="{{ route('inventory.adjustment-reasons.create') }}" class="bp-btn bp-btn-primary">
            <i class="fa-solid fa-plus me-1"></i> New Reason
        </a>
    @endbpCan
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-header">
            <form action="{{ route('inventory.adjustment-reasons.index') }}" method="GET" class="bp-filter-bar w-100">
                <div class="bp-table-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Search reasons..." value="{{ request('search') }}">
                </div>
                <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary bp-filter-submit" title="Apply Filters"><i
                        class="fa-solid fa-filter"></i></button>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-danger bp-filter-reset" title="Reset Filters"><i
                        class="fa-solid fa-rotate"></i></button>
            </form>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Used By</th>
                            <th>Status</th>
                            <th>Sort</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reasons as $reason)
                            <tr>
                                <td class="fw-700">{{ $reason->name }}</td>
                                <td>{{ number_format($reason->adjustments_count) }}
                                    {{ Str::plural('adjustment', $reason->adjustments_count) }}</td>
                                <td>
                                    <x-core::status-toggle :url="route('inventory.adjustment-reasons.toggle-status', $reason)" :active="$reason->is_active" />
                                </td>
                                <td>{{ $reason->sort_order }}</td>
                                <td>
                                    @bpCanAny('inventory.edit', 'inventory.delete')
                                        <div class="dropdown">
                                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                                    class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @bpCan('inventory.edit')
                                                    <li><a class="dropdown-item"
                                                            href="{{ route('inventory.adjustment-reasons.edit', $reason) }}"><i
                                                                class="fa-solid fa-pen"></i> Edit</a></li>
                                                @endbpCan
                                                @bpCan('inventory.delete')
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('inventory.adjustment-reasons.destroy', $reason) }}"
                                                            method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                                data-name="{{ $reason->name }}"><i class="fa-solid fa-trash"></i>
                                                                Delete</button>
                                                        </form>
                                                    </li>
                                                @endbpCan
                                            </ul>
                                        </div>
                                    @endbpCanAny
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="5" icon="fa-solid fa-comment-dots" title="No adjustment reasons yet.">
                                <a href="{{ route('inventory.adjustment-reasons.create') }}">Create your first one</a>.
                            </x-core::table.empty>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($reasons->hasPages())
            <div class="bp-card-footer">
                <div class="bp-pagination">
                    <span class="page-info">Showing {{ $reasons->firstItem() }}-{{ $reasons->lastItem() }} of
                        {{ $reasons->total() }}</span>
                    {{ $reasons->links('core::components.table.pagination-links') }}
                </div>
            </div>
        @endif
    </div>

@endsection
