@extends('core::layouts.master')

@section('title', __('Customer Advances'))
@section('page-title', __('Customer Advances'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('customers.index') }}">Customers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Advances</span>
@endsection

@section('page-actions')
    @bpCan('customers.export')
    <x-core::export-dropdown module="payments" />
    @endbpCan
    @bpCan('customers.create')
    <a href="{{ route('payments.create', ['direction' => 'receive', 'party_type' => 'customer', 'payment_type' => 'advance_payment']) }}"
        class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Receive Advance
    </a>
    @endbpCan
@endsection

@section('content')

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-wallet"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Advance Balance</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($advances['total_balance'], 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Customers with Advance</div>
                    <div class="bp-stat-value">{{ count($advances['items']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Advances Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search customer name, phone..." />
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Customer</x-core::table.column>
            <x-core::table.column>Phone</x-core::table.column>
            <x-core::table.column>Total Received</x-core::table.column>
            <x-core::table.column>Total Returned</x-core::table.column>
            <x-core::table.column>Current Balance</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($advances['items'] as $advance)
                @php
                    $customer = $advances['customers'][$advance->party_id] ?? null;
                    $balance = $advance->total_received - $advance->total_returned;
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if ($customer)
                                <div class="bp-user-avatar bp-avatar-sm">
                                    @if ($customer->photo)
                                        <img src="{{ upload_url($customer->photo) }}" alt="{{ $customer->name }}">
                                    @else
                                        {{ $customer->initials }}
                                    @endif
                                </div>
                                <div class="fw-700 fs-13">{{ $customer->name }}</div>
                            @else
                                <div class="fw-700 fs-13 text-muted">Customer #{{ $advance->party_id }}</div>
                            @endif
                        </div>
                    </td>
                    <td>{{ $customer->phone ?? '' }}</td>
                    <td class="text-end fw-600">{{ currency_symbol() }} {{ number_format($advance->total_received, 0) }}
                    </td>
                    <td class="text-end fw-600">{{ currency_symbol() }} {{ number_format($advance->total_returned, 0) }}
                    </td>
                    <td class="text-end fw-800 text-primary">{{ currency_symbol() }} {{ number_format($balance, 0) }}</td>
                    <td>
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if ($balance > 0)
                                <li>
                                    <a class="dropdown-item" href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'customer', 'party_id' => $advance->party_id, 'payment_type' => 'advance_return']) }}"><i class="fa-solid fa-rotate-left me-2"></i> Return Advance</a>
                                </li>
                                @endif
                                <li>
                                    <a class="dropdown-item" href="{{ route('customers.show', $advance->party_id) }}"><i class="fa-solid fa-eye me-2"></i> View Profile</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="6" icon="fa-solid fa-wallet" title="No customer advances"
                    description="No advance payments have been received from customers yet." />
            @endforelse
        </tbody>
    </x-core::table>

@endsection
