@extends('core::layouts.master')

@section('title', 'Shipping Zones')
@section('page-title', 'Shipping Zones')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Shipping</span>
@endsection

@section('page-actions')
    @bpCan('ecommerce.create')
    <a href="{{ route('ecommerce.shipping.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i>Add
        Zone</a>
    @endbpCan
@endsection

@section('content')

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-map-location-dot"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Zones</div>
                    <div class="bp-stat-value">{{ $zones->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Active Zones</div>
                    <div class="bp-stat-value">{{ $zones->where('is_active', true)->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Avg. Shipping Rate</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ $zones->count() > 0 ? number_format($zones->avg('flat_rate'), 0) : 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bp-card">
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table shipping_table">
                    <thead>
                        <tr>
                            <th>Zone Name</th>
                            <th>Districts</th>
                            <th>Charge</th>
                            <th>Free Above</th>
                            <th>Est. Days</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($zones as $zone)
                            <tr>
                                <td>
                                    <h6>{{ $zone->name }}</h6>
                                </td>
                                <td>
                                    @if ($zone->districts->count())
                                        @foreach ($zone->districts->take(5) as $d)
                                            <span class="bp-badge bp-badge-dark">{{ $d->district_name }}</span>
                                        @endforeach
                                        @if ($zone->districts->count() > 5)
                                            <span class="text-muted fs-11 ms-1">+{{ $zone->districts->count() - 5 }}
                                                more</span>
                                        @endif
                                    @else
                                        <span class="text-muted fs-12">No districts assigned</span>
                                    @endif
                                </td>
                                <td class="fw-800">{{ currency_symbol() }} {{ number_format($zone->flat_rate) }}
                                </td>
                                <td>
                                    @if ($zone->free_shipping_threshold)
                                        {{ currency_symbol() }} {{ number_format($zone->free_shipping_threshold) }}
                                    @endif
                                </td>
                                <td>{{ $zone->estimated_days }}</td>
                                <td>
                                    @bpCan('ecommerce.edit')
                                    <x-core::status-toggle :url="route('ecommerce.shipping.toggle-status', $zone->id)" :active="$zone->is_active" />
                                    @endbpCan
                                </td>
                                <td>
                                    @bpCanAny('ecommerce.edit','ecommerce.delete')
                                    <div class="dropdown">
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @bpCan('ecommerce.edit')
                                            <li><a class="dropdown-item" href="{{ route('ecommerce.shipping.edit', $zone) }}"><i class="fa-solid fa-pen me-2"></i> Edit</a></li>
                                            @endbpCan
                                            @bpCan('ecommerce.delete')
                                            <li>
                                                <form action="{{ route('ecommerce.shipping.destroy', $zone) }}" method="POST" onsubmit="return confirm('Delete this shipping zone?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                                                </form>
                                            </li>
                                            @endbpCan
                                        </ul>
                                    </div>
                                    @endbpCanAny
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="7" icon="fa-solid fa-map-location-dot" title="No shipping zones yet">
                                @bpCan('ecommerce.create')
                                <a href="{{ route('ecommerce.shipping.create') }}" class="bp-btn bp-btn-sm bp-btn-primary"><i class="fa-solid fa-plus me-1"></i>Add Zone</a>
                                @endbpCan
                            </x-core::table.empty>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
