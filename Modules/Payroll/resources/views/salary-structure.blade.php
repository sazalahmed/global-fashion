@extends('core::layouts.master')

@section('title', __('Salary Structures'))
@section('page-title', __('Salary Structures'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>HR</span>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('payroll.index') }}">Payroll</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Salary Structures</span>
@endsection

@section('page-actions')
    @bpCan('hr.create')
        <a href="{{ route('payroll.salary-structures.create') }}" class="bp-btn bp-btn-success">
            <i class="fa-solid fa-plus"></i> Add Structure
        </a>
    @endbpCan
    <a href="{{ route('payroll.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Payroll
    </a>
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-sitemap"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Structures</div>
                    <div class="bp-stat-value">{{ $structures->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Active</div>
                    <div class="bp-stat-value">{{ $structures->where('is_active', true)->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-12">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-puzzle-piece"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Components</div>
                    <div class="bp-stat-value">{{ $structures->sum('components_count') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Salary Structures Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search structures...">
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column :sortable="true" field="name">Structure Name</x-core::table.column>
            <x-core::table.column>Code</x-core::table.column>
            <x-core::table.column>Description</x-core::table.column>
            <x-core::table.column align="center">Components</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($structures as $structure)
                <tr>
                    <td>
                        <div>
                            <span class="fw-700 fs-13">{{ $structure->name }}</span>
                        </div>
                    </td>
                    <td class="fs-13">
                        <span class="bp-badge bp-badge-info">{{ $structure->code }}</span>
                    </td>
                    <td class="fs-13 text-muted">{{ \Illuminate\Support\Str::limit($structure->description, 50) ?? '' }}
                    </td>
                    <td class="text-center fw-600 fs-13">{{ $structure->components_count }}</td>
                    <td>
                        <x-core::status-toggle :url="route('payroll.salary-structures.toggle-status', $structure->id)" :active="$structure->is_active" />
                    </td>
                    <td>
                        @bpCanAny('hr.view', 'hr.edit', 'hr.delete')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                        class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('hr.view')
                                        <li><a class="dropdown-item"
                                                href="{{ route('payroll.salary-structures.show', $structure) }}"><i
                                                    class="fa-solid fa-eye"></i> View</a></li>
                                    @endbpCan
                                    @bpCan('hr.edit')
                                        <li><a class="dropdown-item"
                                                href="{{ route('payroll.salary-structures.edit', $structure) }}"><i
                                                    class="fa-solid fa-pen"></i> Edit</a></li>
                                    @endbpCan
                                    @bpCan('hr.delete')
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <form action="{{ route('payroll.salary-structures.destroy', $structure) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $structure->name }}"><i class="fa-solid fa-trash"></i>
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
                <x-core::table.empty colspan="6" icon="fa-solid fa-sitemap" title="No salary structures found">
                    @bpCan('hr.create')
                        <a href="{{ route('payroll.salary-structures.create') }}" class="bp-btn bp-btn-sm bp-btn-primary"><i
                                class="fa-solid fa-plus me-1"></i> Add Structure</a>
                    @endbpCan
                </x-core::table.empty>
            @endforelse
        </tbody>
    </x-core::table>

@endsection
