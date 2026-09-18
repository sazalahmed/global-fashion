@extends('core::layouts.master')

@section('title', __('Lenders'))
@section('page-title', __('Lenders'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('loans.index') }}">Loans</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Lenders</span>
@endsection

@section('page-actions')
    @bpCan('finance.create')
        <a href="{{ route('lenders.create') }}" class="bp-btn bp-btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Add Lender
        </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Lenders</div>
                    <div class="bp-stat-value">{{ number_format($stats['total_lenders']) }}</div>
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
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Outstanding</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_outstanding'], 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Borrowed</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_borrowed'], 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search lender name, phone...">
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Name</x-core::table.column>
            <x-core::table.column>Company</x-core::table.column>
            <x-core::table.column>Phone</x-core::table.column>
            <x-core::table.column>Total Borrowed</x-core::table.column>
            <x-core::table.column>Total Repaid</x-core::table.column>
            <x-core::table.column>Outstanding</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($lenders as $lender)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bp-user-avatar bp-avatar-sm">
                                @if ($lender->photo)
                                    <img src="{{ upload_url_sm($lender->photo) }}" alt="{{ $lender->name }}">
                                @else
                                    {{ $lender->initials }}
                                @endif
                            </div>
                            <div>
                                <div class="fw-700 fs-13">{{ $lender->name }}</div>
                                @if ($lender->email)
                                    <div class="fs-11 text-muted">{{ $lender->email }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="fs-13">{{ $lender->company_name ?? '' }}</td>
                    <td>{{ $lender->phone ?? '' }}</td>
                    <td class="fw-600">{{ currency_symbol() }} {{ number_format($lender->total_borrowed, 0) }}
                    </td>
                    <td class="fw-600 text-success">{{ currency_symbol() }}
                        {{ number_format($lender->total_repaid, 0) }}</td>
                    <td class="{{ $lender->outstanding > 0 ? 'text-danger fw-800' : 'text-success' }}">
                        {{ currency_symbol() }} {{ number_format($lender->outstanding, 0) }}
                    </td>
                    <td>
                        <x-core::status-toggle :url="route('lenders.toggle-status', $lender->id)" :active="$lender->status === 'active'" />
                    </td>
                    <td>
                        @bpCanAny('finance.view', 'finance.edit', 'finance.delete')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                        class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('finance.view')
                                        <li><a class="dropdown-item" href="{{ route('lenders.show', $lender) }}"><i
                                                    class="fa-solid fa-eye me-2"></i>View</a></li>
                                    @endbpCan
                                    @bpCan('finance.edit')
                                        <li><a class="dropdown-item" href="{{ route('lenders.edit', $lender) }}"><i
                                                    class="fa-solid fa-pen me-2"></i>Edit</a></li>
                                    @endbpCan
                                    @bpCan('finance.delete')
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <form action="{{ route('lenders.destroy', $lender) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $lender->name }}"><i
                                                        class="fa-solid fa-trash me-2"></i>Delete</button>
                                            </form>
                                        </li>
                                    @endbpCan
                                </ul>
                            </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="8" icon="fa-solid fa-users" title="No lenders found" />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$lenders" itemLabel="lenders" />
        </x-slot:pagination>
    </x-core::table>

@endsection
