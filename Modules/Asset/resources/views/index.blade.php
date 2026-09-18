@extends('core::layouts.master')

@section('title', __('Assets'))
@section('page-title', __('Assets'))

@push('styles')
    <link href="{{ asset('vendor/venobox/venobox.min.css') }}" rel="stylesheet">
@endpush

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Assets</span>
@endsection

@section('page-actions')
    @bpCan('finance.edit')
    <form action="{{ route('assets.depreciation') }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="month" value="{{ now()->format('Y-m') }}">
        <button type="submit" class="bp-btn bp-btn-warning"
            onclick="return confirm('Run depreciation for {{ now()->format('F Y') }}?')">
            <i class="fa-solid fa-chart-line me-1"></i> Run Depreciation
        </button>
    </form>
    @endbpCan
    @bpCan('finance.create')
    <a href="{{ route('assets.create') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-plus"></i> Add Asset
    </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-building"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Assets</div>
                    <div class="bp-stat-value">{{ number_format($stats['total_assets']) }}</div>
                    <div class="bp-stat-change"><span class="text-muted">{{ number_format($stats['active']) }} active</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Value</div>
                    <div class="bp-stat-value">{{ money($stats['total_value']) }}</div>
                    <div class="bp-stat-change"><span class="text-muted">Current book value</span></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-chart-line"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Depreciation</div>
                    <div class="bp-stat-value">{{ money($stats['total_depreciation']) }}
                    </div>
                    <div class="bp-stat-change"><span class="text-muted">Accumulated</span></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-wrench"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Maintenance Due</div>
                    <div class="bp-stat-value">{{ number_format($stats['maintenance_due']) }}</div>
                    <div class="bp-stat-change"><span class="text-muted">Within 7 days</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search name, asset ID, serial...">
                <select class="bp-form-select select2-search" name="category_id" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select class="bp-form-select d-none" name="branch_id" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}</option>
                    @endforeach
                </select>
                <select class="bp-form-select" name="status" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="under_maintenance" {{ request('status') === 'under_maintenance' ? 'selected' : '' }}>
                        Under Maintenance</option>
                    <option value="disposed" {{ request('status') === 'disposed' ? 'selected' : '' }}>Disposed</option>
                    <option value="written_off" {{ request('status') === 'written_off' ? 'selected' : '' }}>Written Off
                    </option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column :sortable="true" field="asset_code">Asset ID</x-core::table.column>
            <x-core::table.column>Image</x-core::table.column>
            <x-core::table.column :sortable="true" field="name">Asset Name</x-core::table.column>
            <x-core::table.column>Category</x-core::table.column>
            <th class="d-none">Location / Branch</th>
            <x-core::table.column :sortable="true" field="purchase_date">Purchase Date</x-core::table.column>
            <x-core::table.column :sortable="true" field="purchase_price">Purchase
                Price</x-core::table.column>
            <x-core::table.column :sortable="true" field="current_value">Current
                Value</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($assets as $asset)
                <tr>
                    <td><a href="{{ route('assets.show', $asset) }}" class="fw-700">{{ $asset->asset_code }}</a></td>
                    <td>
                        @if ($asset->photo)
                            <a class="venobox" data-gall="assets" href="{{ upload_url($asset->photo) }}"
                                title="{{ $asset->name }}">
                                <img src="{{ upload_url_sm($asset->photo) }}" alt="{{ $asset->name }}"
                                    class="bp-sale-thumb">
                            </a>
                        @else
                            <div class="bp-sale-thumb-placeholder"><i class="fa-solid fa-image"></i></div>
                        @endif
                    </td>
                    <td class="fw-600">{{ $asset->name }}</td>
                    <td><span class="bp-badge bp-badge-primary">{{ $asset->category->name ?? '' }}</span></td>
                    <td class="d-none">{{ $asset->branch->name ?? ($asset->location ?? '') }}</td>
                    <td>{{ $asset->purchase_date->format('d M Y') }}</td>
                    <td class="fw-700">{{ money($asset->purchase_price) }}
                    </td>
                    <td class="fw-700">{{ money($asset->current_value) }}</td>
                    <td>
                        @if ($asset->status === 'active')
                            <span class="bp-badge bp-badge-success">Active</span>
                        @elseif($asset->status === 'under_maintenance')
                            <span class="bp-badge bp-badge-warning">Under Maintenance</span>
                        @elseif($asset->status === 'disposed')
                            <span class="bp-badge bp-badge-danger">Disposed</span>
                        @elseif($asset->status === 'written_off')
                            <span class="bp-badge bp-badge-dark">Written Off</span>
                        @endif
                    </td>
                    <td>
                        @bpCanAny('finance.view','finance.edit','finance.delete')
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @bpCan('finance.view')
                                <li><a class="dropdown-item" href="{{ route('assets.show', $asset) }}"><i
                                            class="fa-solid fa-eye"></i> View</a></li>
                                @endbpCan
                                @bpCan('finance.edit')
                                <li><a class="dropdown-item" href="{{ route('assets.edit', $asset) }}"><i
                                            class="fa-solid fa-pen"></i> Edit</a></li>
                                @endbpCan
                                @bpCan('finance.delete')
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('assets.destroy', $asset) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger delete-confirm"
                                            data-name="{{ $asset->asset_code }}"><i class="fa-solid fa-trash"></i>
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
                <x-core::table.empty colspan="10" icon="fa-solid fa-building" title="No assets found" />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$assets" itemLabel="assets" />
        </x-slot:pagination>
    </x-core::table>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/venobox/venobox.min.js') }}"></script>
    <script>
        'use strict';

        $(function() {
            // Asset photo lightbox
            if ($.fn.venobox) {
                $('.venobox').venobox();
            }

            // Filter reset
            $('.bp-filter-reset').on('click', function() {
                // Clear all filters — go to the clean path (no query string).
                window.location.href = window.location.pathname;
            });
        });
    </script>
@endpush
