@extends('core::layouts.master')

@section('title', 'Expense Categories')
@section('page-title', 'Expense Categories')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('expenses.index') }}">Expenses</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Categories</span>
@endsection

@section('page-actions')
    @bpCan('finance.create')
        <a href="{{ route('expense-categories.create') }}" class="bp-btn bp-btn-primary">
            <i class="fa-solid fa-plus me-1"></i> New Category
        </a>
    @endbpCan
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-header">
            <form action="{{ route('expense-categories.index') }}" method="GET" class="bp-filter-bar w-100">
                <div class="bp-table-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Search categories..." value="{{ request('search') }}">
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
                            <th>Status</th>
                            <th>Sort</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $cat)
                            <tr>
                                <td class="fw-700">
                                    <span class="bp-cat-tree depth-{{ $cat->depth ?? 0 }}">
                                        @if(($cat->depth ?? 0) > 0)
                                            <i class="fa-solid fa-arrow-turn-up fa-rotate-90 bp-cat-tree-icon"></i>
                                        @endif
                                        {{ $cat->name }}
                                    </span>
                                </td>
                                <td>
                                    <x-core::status-toggle :url="route('expense-categories.toggle-status', $cat->id)" :active="$cat->is_active" />
                                </td>
                                <td>{{ $cat->sort_order }}</td>
                                <td>
                                    @bpCanAny('finance.edit', 'finance.delete')
                                        <div class="dropdown">
                                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                                    class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @bpCan('finance.edit')
                                                    <li><a class="dropdown-item"
                                                            href="{{ route('expense-categories.edit', $cat) }}"><i
                                                                class="fa-solid fa-pen"></i> Edit</a></li>
                                                @endbpCan
                                                @bpCan('finance.delete')
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('expense-categories.destroy', $cat) }}"
                                                            method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                                data-name="{{ $cat->name }}"><i class="fa-solid fa-trash"></i>
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
                            <x-core::table.empty colspan="4" icon="fa-solid fa-tags" title="No categories yet.">
                                <a href="{{ route('expense-categories.create') }}">Create your first one</a>.
                            </x-core::table.empty>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
