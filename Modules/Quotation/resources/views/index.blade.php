@extends('core::layouts.master')

@section('title', __('Quotations'))
@section('page-title', __('Quotations'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Quotations</span>
@endsection

@section('page-actions')
    @bpCan('quotations.export')
        <x-core::export-dropdown module="quotations" />
    @endbpCan
    @bpCan('quotations.create')
        <a href="{{ route('quotations.create') }}" class="bp-btn bp-btn-primary">
            <i class="fa-solid fa-plus"></i> New Quotation
        </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-file-lines"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Quotations</div>
                    <div class="bp-stat-value">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Pending</div>
                    <div class="bp-stat-value">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Accepted</div>
                    <div class="bp-stat-value">{{ number_format($stats['accepted']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-calendar-xmark"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Expired</div>
                    <div class="bp-stat-value">{{ number_format($stats['expired']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quotations Table -->
    <x-core::table :selectable="true">
        <x-slot:filters>
            {{-- Bulk actions — disabled until at least one row is selected (see JS). --}}
            <div class="bp-bulk-toolbar mb-3" id="bulkActionsBar">
                <span class="bp-table-bulk-count"><span id="bulkCount">0</span> {{ __('selected') }}</span>
                <select class="bp-form-select bp-form-select-sm bp-bulk-status-select" id="bulkStatusChange" disabled>
                    <option value="">{{ __('Change Status') }}</option>
                    <option value="draft">{{ __('Draft') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="accepted">{{ __('Accepted') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                    <option value="expired">{{ __('Expired') }}</option>
                </select>
                @bpCan('quotations.delete')
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-danger" id="bulkDelete" disabled>
                        <i class="fa-solid fa-trash me-1"></i> {{ __('Delete') }}
                    </button>
                @endbpCan
            </div>

            <x-core::table.filter-bar searchPlaceholder="Search quotation, customer...">
                <input type="date" class="bp-form-control bp-filter-date" name="date_from"
                    value="{{ request('date_from') }}">
                <span class="text-muted">to</span>
                <input type="date" class="bp-form-control bp-filter-date" name="date_to"
                    value="{{ request('date_to') }}">
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
                </select>
                <select class="bp-form-select" name="customer_id">
                    <option value="">All Customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}"
                            {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header :selectable="true">
            <x-core::table.column :sortable="true" field="quotation_number">Quotation #</x-core::table.column>
            <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
            <x-core::table.column>Customer</x-core::table.column>
            <x-core::table.column align="center">Items</x-core::table.column>
            <x-core::table.column :sortable="true" field="total">Total</x-core::table.column>
            <x-core::table.column :sortable="true" field="valid_until">Valid Until</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($quotations as $quotation)
                @php
                    $statusMap = [
                        'draft' => 'bp-badge-dark',
                        'pending' => 'bp-badge-warning',
                        'accepted' => 'bp-badge-success',
                        'rejected' => 'bp-badge-danger',
                        'expired' => 'bp-badge-danger',
                        'converted' => 'bp-badge-info',
                    ];
                    $badgeClass = $statusMap[$quotation->status] ?? 'bp-badge-dark';
                @endphp
                <tr>
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $quotation->id }}"></td>
                    <td><a href="{{ route('quotations.show', $quotation) }}"
                            class="fw-700">{{ $quotation->quotation_number }}</a></td>
                    <td>{{ $quotation->quotation_date->format('d M Y') }}</td>
                    <td class="fw-600">{{ $quotation->customer->name ?? 'Walk-in' }}</td>
                    <td class="text-center">{{ $quotation->items->count() }}</td>
                    <td class="fw-800">{{ currency_symbol() }} {{ number_format($quotation->grand_total, 0) }}
                    </td>
                    <td>{{ $quotation->valid_until->format('d M Y') }}</td>
                    <td><span class="bp-badge {{ $badgeClass }}">{{ ucfirst($quotation->status) }}</span></td>
                    <td>
                        @bpCanAny('quotations.view', 'quotations.edit', 'quotations.create', 'quotations.delete')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                        class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('quotations.view')
                                        <li><a class="dropdown-item" href="{{ route('quotations.show', $quotation) }}"><i
                                                    class="fa-solid fa-eye"></i> View</a></li>
                                    @endbpCan
                                    @bpCan('quotations.edit')
                                        @if (in_array($quotation->status, ['draft', 'pending']))
                                            <li><a class="dropdown-item" href="{{ route('quotations.edit', $quotation) }}"><i
                                                        class="fa-solid fa-pen"></i> Edit</a></li>
                                        @endif
                                    @endbpCan
                                    @bpCan('quotations.create')
                                        @if ($quotation->isConvertible())
                                            <li>
                                                <form action="{{ route('quotations.convert-to-sale', $quotation) }}"
                                                    method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i
                                                            class="fa-solid fa-cart-shopping"></i> Convert to Sale</button>
                                                </form>
                                            </li>
                                        @endif
                                    @endbpCan
                                    @bpCan('quotations.view')
                                        <li><a class="dropdown-item" href="{{ route('quotations.print', $quotation) }}"
                                                target="_blank"><i class="fa-solid fa-print"></i> Print</a></li>
                                    @endbpCan
                                    @bpCan('quotations.create')
                                        <li>
                                            <form action="{{ route('quotations.duplicate', $quotation) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item"><i class="fa-solid fa-copy"></i>
                                                    Duplicate</button>
                                            </form>
                                        </li>
                                    @endbpCan
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    @bpCan('quotations.delete')
                                        <li>
                                            <form action="{{ route('quotations.destroy', $quotation) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $quotation->quotation_number }}"><i
                                                        class="fa-solid fa-trash"></i> Delete</button>
                                            </form>
                                        </li>
                                    @endbpCan
                                </ul>
                            </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="9" icon="fa-solid fa-file-lines" title="No quotations found." />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$quotations" itemLabel="quotations" />
        </x-slot:pagination>
    </x-core::table>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            $('.bp-check-all').on('change', function() {
                $('.row-checkbox').prop('checked', $(this).prop('checked'));
                updateBulkBar();
            });
            $(document).on('change', '.row-checkbox', updateBulkBar);

            function getSelectedIds() {
                return $('.row-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
            }

            function updateBulkBar() {
                var count = $('.row-checkbox:checked').length;
                $('#bulkCount').text(count);
                // Enable the bulk controls only when at least one row is selected.
                $('#bulkActionsBar').find('button, select').prop('disabled', count === 0);
            }

            // Bulk status change
            $('#bulkStatusChange').on('change', function() {
                var status = $(this).val();
                if (!status) return;
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                if (!confirm('Update ' + ids.length + ' quotation(s) to "' + status + '"?')) {
                    $(this).val('');
                    return;
                }
                $.post('{{ route('quotations.bulk-status') }}', {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    ids: ids,
                    status: status
                }).done(function() {
                    location.reload();
                }).fail(function() {
                    alert('Failed to update status.');
                });
            });

            // Bulk delete
            $('#bulkDelete').on('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                if (!confirm('Delete ' + ids.length + ' quotation(s)? This cannot be undone.')) return;
                $.post('{{ route('quotations.bulk-delete') }}', {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'DELETE',
                    ids: ids
                }).done(function() {
                    location.reload();
                }).fail(function() {
                    alert('Failed to delete quotations.');
                });
            });
        });
    </script>
@endpush
