@extends('core::layouts.master')

@section('title', $customer->name)
@section('page-title', __('Customer Profile'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('customers.index') }}">Customers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $customer->name }}</span>
@endsection

@section('page-actions')
    @bpCan('payments.create')
    @if ($profileStats['due_amount'] > 0)
        <a href="{{ route('payments.create', ['direction' => 'receive', 'party_type' => 'customer', 'party_id' => $customer->id]) }}"
            class="bp-btn bp-btn-success">
            <i class="fa-solid fa-bangladeshi-taka-sign me-1"></i> Collect Payment
        </a>
    @endif
    @endbpCan
    @bpCan('customers.edit')
    <a href="{{ route('customers.edit', $customer) }}" class="bp-btn bp-btn-warning">
        <i class="fa-solid fa-pen me-1"></i> Edit Customer
    </a>
    @endbpCan
    <a href="{{ route('customers.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Customers
    </a>
@endsection

@section('content')

    <!-- Customer Header -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="bp-user-avatar bp-avatar-lg customer_profile_photo">
                    @if ($customer->photo)
                        <img src="{{ upload_url($customer->photo) }}" alt="{{ $customer->name }}">
                    @else
                        {{ $customer->initials }}
                    @endif
                </div>
                <div class="flex-1">
                    <h4 class="fw-800 mb-1">{{ $customer->name }}</h4>
                    <div class="d-flex gap-3 flex-wrap fs-13 text-muted">
                        @if ($customer->phone)
                            <span><i class="fa-solid fa-phone me-1"></i>{{ $customer->phone }}</span>
                        @endif
                        @if ($customer->email)
                            <span><i class="fa-solid fa-envelope me-1"></i>{{ $customer->email }}</span>
                        @endif
                        @if ($customer->upazila || $customer->district || $customer->division)
                            <span><i
                                    class="fa-solid fa-map me-1"></i>{{ collect([$customer->upazila, $customer->district, $customer->division])->filter()->implode(', ') }}</span>
                        @endif
                        @if ($customer->created_at)
                            <span><i class="fa-solid fa-calendar me-1"></i>{{ __('Since') }}
                                {{ $customer->created_at->format('d M Y') }}</span>
                        @endif
                    </div>
                    @if ($customer->address)
                        <div class="fs-13 text-muted mt-1">
                            <i class="fa-solid fa-location-dot me-1"></i>{{ $customer->address }}
                        </div>
                    @endif
                    <div class="d-flex gap-2 mt-2">
                        @if ($customer->customerGroup)
                            <span class="bp-badge bp-badge-primary">{{ $customer->customerGroup->name }}</span>
                        @endif
                        @if ($customer->is_active)
                            <span class="bp-badge bp-badge-success">Active</span>
                        @else
                            <span class="bp-badge bp-badge-danger">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row — summed over the same sales the Sales History tab lists,
         so the cards always agree with the table below. -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-center p-3 rounded bp-profile-stat bp-profile-stat-primary">
                <div class="fs-12 text-muted fw-600">Total Purchases</div>
                <div class="fw-800 fs-5 text-primary">{{ currency_symbol() }}
                    {{ number_format($profileStats['total_purchased'], 0) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3 rounded bp-profile-stat bp-profile-stat-danger">
                <div class="fs-12 text-muted fw-600">Due Balance</div>
                <div class="fw-800 fs-5 text-danger">{{ currency_symbol() }}
                    {{ number_format($profileStats['due_amount'], 0) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3 rounded bp-profile-stat bp-profile-stat-success">
                <div class="fs-12 text-muted fw-600">Total Paid</div>
                <div class="fw-800 fs-5 text-success">{{ currency_symbol() }}
                    {{ number_format($profileStats['total_paid'], 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            @php($isSimpleMode = \Modules\Setting\Services\SettingService::isSimpleMode())
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#cpurchases">Sales History</a>
                </li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#cpayments">Payments</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab"
                        href="#cledger">{{ $isSimpleMode ? 'Statement' : 'Ledger' }}</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#cadvance">Advance</a></li>
            </ul>
            <div class="tab-content pt-3">
                <!-- Sales History Tab -->
                <div class="tab-pane fade show active" id="cpurchases">
                    <div class="bp-ajax-tab">
                        @include('customer::partials.tab-sales')
                    </div>
                </div>

                <!-- Payments Tab -->
                <div class="tab-pane fade" id="cpayments">
                    <div class="bp-ajax-tab">
                        @include('customer::partials.tab-payments')
                    </div>
                </div>

                <!-- Ledger / Statement Tab -->
                <div class="tab-pane fade" id="cledger">
                    <div class="bp-ajax-tab">
                        @include('customer::partials.tab-ledger')
                    </div>
                </div>

                <!-- Advance Tab -->
                <div class="tab-pane fade" id="cadvance">
                    <div class="bp-ajax-tab">
                        @include('customer::partials.tab-advance')
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // AJAX pagination for the profile tabs. Pagination links inside a
            // .bp-ajax-tab point at the customers.tab fragment endpoint — fetch
            // the fragment and swap the wrapper's content instead of navigating.
            $(document).on('click', '.bp-ajax-tab .bp-pagination a.page-link', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (!url) return;

                var $wrap = $(this).closest('.bp-ajax-tab');
                $wrap.addClass('bp-ajax-tab-loading');

                $.get(url)
                    .done(function(html) {
                        $wrap.html(html);
                    })
                    .fail(function() {
                        $wrap.prepend(
                            '<div class="alert alert-danger fs-13 mb-2">{{ __('Failed to load — please try again.') }}</div>'
                        );
                    })
                    .always(function() {
                        $wrap.removeClass('bp-ajax-tab-loading');
                    });
            });
        });
    </script>
@endpush
