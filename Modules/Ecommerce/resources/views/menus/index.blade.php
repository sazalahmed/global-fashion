@extends('core::layouts.master')

@section('title', __('Menus'))
@section('page-title', __('Menus'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Menus</span>
@endsection

@section('content')
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-bars me-2"></i>Storefront Menus</h5>
            <span class="fs-12 text-muted">Manage every storefront menu location from one place</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table menu_manage_table">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Key</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($menus as $menu)
                            <tr>
                                <td class="fw-600">{{ $labels[$menu->location] ?? $menu->name }}</td>
                                <td><code class="fs-12">{{ $menu->location }}</code></td>
                                <td>{{ $menu->items_count }}</td>
                                <td>
                                    @bpCan('ecommerce.edit')
                                        <x-core::status-toggle :url="route('ecommerce.menus.toggle-status', $menu->id)" :active="$menu->is_active" />
                                    @endbpCan
                                </td>
                                <td>
                                    @bpCan('ecommerce.edit')
                                        <a class="bp-btn bp-btn-sm bp-btn-success"
                                            href="{{ route('ecommerce.menus.edit', $menu) }}"><i
                                                class="fa-solid fa-pen"></i></a>
                                    @endbpCan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
