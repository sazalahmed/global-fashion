@extends('core::layouts.master')

@section('title', __('Courier Providers'))
@section('page-title', __('Courier Providers'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Courier Providers</span>
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-header d-flex justify-content-between align-items-center">
            <h5 class="bp-card-title"><i class="fa-solid fa-truck-fast me-2"></i>Courier Providers</h5>
            <span class="bp-badge bp-badge-info">{{ $providers->count() }} Providers</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Provider</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>API Configured</th>
                            <th>Sort Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($providers as $index => $provider)
                            <tr>
                                <td class="text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($provider->logo)
                                            <img src="{{ asset($provider->logo) }}" alt="{{ $provider->name }}"
                                                class="bp-courier-logo">
                                        @else
                                            <div class="bp-courier-logo-placeholder"><i class="fa-solid fa-truck"></i></div>
                                        @endif
                                        <span class="fw-700">{{ $provider->name }}</span>
                                    </div>
                                </td>
                                <td><code>{{ $provider->slug }}</code></td>
                                <td>
                                    @bpCan('ecommerce.edit')
                                        <form action="{{ route('ecommerce.courier-providers.toggle', $provider->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit"
                                                class="bp-badge {{ $provider->is_active ? 'bp-badge-success' : 'bp-badge-danger' }} bp-badge-clickable">
                                                {{ $provider->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    @endbpCan
                                </td>
                                <td>
                                    @if ($provider->hasApiKey())
                                        <span class="bp-badge bp-badge-success"><i
                                                class="fa-solid fa-check me-1"></i>Configured</span>
                                    @else
                                        <span class="bp-badge bp-badge-warning"><i class="fa-solid fa-xmark me-1"></i>Not
                                            Set</span>
                                    @endif
                                </td>
                                <td>{{ $provider->sort_order }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                                class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if ($provider->slug === 'steadfast')
                                                <li><a class="dropdown-item"
                                                        href="{{ route('ecommerce.steadfast.index') }}"><i
                                                            class="fa-solid fa-chart-line me-2"></i> Live Operations</a>
                                                </li>
                                            @endif
                                            @bpCan('ecommerce.edit')
                                                <li><button type="button" class="dropdown-item bp-btn-configure"
                                                        data-provider-id="{{ $provider->id }}"><i
                                                            class="fa-solid fa-gears me-2"></i> Configure</button></li>
                                            @endbpCan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr class="bp-config-row" id="config-row-{{ $provider->id }}" data-visible="false">
                                <td colspan="7" class="p-0">
                                    <div class="bp-config-panel">
                                        @bpCan('ecommerce.edit')
                                            <form action="{{ route('ecommerce.courier-providers.update', $provider->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="bp-form-label">API Base URL</label>
                                                        <input type="url" name="base_url" class="bp-form-control"
                                                            value="{{ $provider->base_url }}"
                                                            placeholder="https://api.example.com">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="bp-form-label">Store ID</label>
                                                        <input type="text" name="store_id" class="bp-form-control"
                                                            value="{{ $provider->store_id }}"
                                                            placeholder="Store / Merchant ID">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="bp-form-label">API Key</label>
                                                        <input type="password" name="api_key" class="bp-form-control"
                                                            value="{{ $provider->hasApiKey() ? '********' : '' }}"
                                                            placeholder="Enter API Key">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="bp-form-label">API Secret</label>
                                                        <input type="password" name="api_secret" class="bp-form-control"
                                                            value="{{ $provider->hasApiSecret() ? '********' : '' }}"
                                                            placeholder="Enter API Secret">
                                                    </div>
                                                    <div class="col-12 text-end">
                                                        <button type="button"
                                                            class="bp-btn bp-btn-sm bp-btn-danger bp-btn-configure"
                                                            data-provider-id="{{ $provider->id }}"><i
                                                                class="fa-solid fa-xmark me-1"></i>Cancel</button>
                                                        <button type="submit" class="bp-btn bp-btn-sm bp-btn-success ms-2"><i
                                                                class="fa-solid fa-save me-1"></i> Save Credentials</button>
                                                    </div>
                                                </div>
                                            </form>
                                        @endbpCan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="7" icon="fa-solid fa-truck-fast"
                                title="No courier providers found" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            $('.bp-btn-configure').on('click', function() {
                var providerId = $(this).data('provider-id');
                var $row = $('#config-row-' + providerId);
                var isVisible = $row.attr('data-visible') === 'true';

                // Hide all other config rows
                $('.bp-config-row').attr('data-visible', 'false').hide();

                if (!isVisible) {
                    $row.attr('data-visible', 'true').show();
                }
            });
        });
    </script>
@endpush
