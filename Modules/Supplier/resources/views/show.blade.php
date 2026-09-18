@extends('core::layouts.master')

@section('title', __('Supplier Detail'))
@section('page-title', __('Supplier Detail'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('supplier.index') }}">Suppliers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $supplier->company_name }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('supplier.print', $supplier) }}" target="_blank" class="bp-btn bp-btn-warning"><i
            class="fa-solid fa-print"></i> Print</a>
    @bpCan('suppliers.export')
    <a href="{{ route('supplier.export', $supplier) }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-download"></i>
        Export</a>
    @endbpCan
    @bpCan('suppliers.edit')
    <a href="{{ route('supplier.edit', $supplier) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-pen"></i> Edit
        Supplier</a>
    @endbpCan
    @bpCan('payments.create')
    <a href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'supplier', 'party_id' => $supplier->id]) }}"
        class="bp-btn bp-btn-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i> Make Payment</a>
    @endbpCan
@endsection

@section('content')

    <!-- Supplier Info Card -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <div class="d-flex align-items-center gap-4 mb-4 pb-4 border-bottom">
                <div class="bp-user-avatar bp-avatar-lg">{{ $supplier->initials }}</div>
                <div class="flex-1">
                    <h4 class="fw-800 mb-1">{{ $supplier->company_name }}</h4>
                    @if ($supplier->contact_person)
                        <div class="fs-13 fw-600 text-muted mb-1">Contact: {{ $supplier->contact_person }}</div>
                    @endif
                    <div class="d-flex gap-3 flex-wrap fs-13 text-muted">
                        @if ($supplier->phone)
                            <span><i class="fa-solid fa-phone me-1"></i>{{ $supplier->phone }}</span>
                        @endif
                        @if ($supplier->email)
                            <span><i class="fa-solid fa-envelope me-1"></i>{{ $supplier->email }}</span>
                        @endif
                        @if ($supplier->address || $supplier->district)
                            <span><i
                                    class="fa-solid fa-location-dot me-1"></i>{{ collect([$supplier->address, $supplier->area, $supplier->district, $supplier->division])->filter()->implode(', ') }}</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        @if ($supplier->is_active)
                            <span class="bp-badge bp-badge-success">Active</span>
                        @else
                            <span class="bp-badge bp-badge-danger">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Payment Terms</div>
                    <div class="fw-700 fs-13">{{ $supplier->payment_terms ?? '--' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Credit Limit</div>
                    <div class="fw-700 fs-13">
                        {{ $supplier->credit_limit ? currency_symbol() . ' ' . number_format($supplier->credit_limit, 0) : '--' }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">TIN Number</div>
                    <div class="fw-700 fs-13">{{ $supplier->tin ?? '--' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">BIN Number</div>
                    <div class="fw-700 fs-13">{{ $supplier->bin ?? '--' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Trade License</div>
                    <div class="fw-700 fs-13">{{ $supplier->trade_license ?? '--' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Bank</div>
                    <div class="fw-700 fs-13">{{ $supplier->bank_name ?? '--' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Account Number</div>
                    <div class="fw-700 fs-13">{{ $supplier->account_number ?? '--' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Branch / Routing</div>
                    <div class="fw-700 fs-13">
                        {{ collect([$supplier->bank_branch, $supplier->routing_number])->filter()->implode(' / ') ?:'--' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Purchases</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($supplier->total_purchase, 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Due Balance</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($supplier->due_balance, 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Paid</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($supplier->total_paid, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Advance Balance</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($supplier->advance_balance, 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Payment Section -->
    @if ($supplier->due_balance > 0)
        <div class="bp-card mb-4" id="supplierQuickPayment">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2 text-success"></i>Quick Payment
                </h5>
            </div>
            <div class="bp-card-body">
                <form action="{{ route('payments.store') }}" method="POST" id="supplierPaymentForm">
                    @csrf
                    <input type="hidden" name="direction" value="pay">
                    <input type="hidden" name="party_type" value="supplier">
                    <input type="hidden" name="party_id" value="{{ $supplier->id }}">
                    <input type="hidden" name="payment_type" value="against_invoice">
                    <input type="hidden" name="amount" id="spTotalAmount" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="bp-form-label">Payment Date *</label>
                            <input type="date" class="bp-form-control" name="payment_date"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">Note</label>
                            <input type="text" class="bp-form-control" name="note" placeholder="Optional note">
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">Payment Splits *</label>
                            <div id="spSplitRows">
                                <div class="bp-split-payment-row" data-index="0">
                                    <div class="bp-pay-amount-col">
                                        <input type="number" class="bp-form-control sp-split-amount"
                                            name="splits[0][amount]" placeholder="Amount" step="0.01" min="0"
                                            max="{{ $supplier->due_balance }}" value="{{ $supplier->due_balance }}"
                                            required>
                                    </div>
                                    <div class="bp-pay-method-col">
                                        <select class="bp-form-select sp-split-account"
                                            name="splits[0][payment_account_id]" required>
                                            <option value="">Select Account</option>
                                            @foreach (\Modules\Payment\Models\PaymentAccount::where('is_active', true)->get() as $pa)
                                                <option value="{{ $pa->id }}" data-type="{{ $pa->account_type }}">
                                                    {{ $pa->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="bp-pay-ref-col">
                                        <input type="text" class="bp-form-control" name="splits[0][reference]"
                                            placeholder="Ref / TXN ID">
                                    </div>
                                    <button type="button" class="remove-split d-none" title="Remove"><i
                                            class="fa-solid fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-outline mt-2" id="spAddSplit"><i
                                    class="fa-solid fa-plus me-1"></i> Add Split Payment</button>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-700 fs-13">Total: <span id="spTotalDisplay">{{ currency_symbol() }}
                                        {{ number_format($supplier->due_balance, 0) }}</span> / Due:
                                    {{ currency_symbol() }} {{ number_format($supplier->due_balance, 0) }}</span>
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-check me-1"></i> Record Payment</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Tabs Section -->
    @php($isSimpleMode = \Modules\Setting\Services\SettingService::isSimpleMode())
    <div class="bp-card">
        <div class="bp-card-body">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#purchaseHistory">Purchase
                        History</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#paymentHistory">Payments</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab"
                        href="#ledgerTab">{{ $isSimpleMode ? 'Statement' : 'Ledger' }}</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#advanceTab">Advance</a></li>
            </ul>
            <div class="tab-content pt-3">

                <!-- Purchase History Tab -->
                <div class="tab-pane fade show active" id="purchaseHistory">
                    <x-core::table>
                        <x-core::table.header>
                            <x-core::table.column>PO #</x-core::table.column>
                            <x-core::table.column>Date</x-core::table.column>
                            <x-core::table.column align="end">Total</x-core::table.column>
                            <x-core::table.column align="end">Paid</x-core::table.column>
                            <x-core::table.column align="end">Due</x-core::table.column>
                            <x-core::table.column>Status</x-core::table.column>
                            <x-core::table.column>Actions</x-core::table.column>
                        </x-core::table.header>
                        <tbody>
                            @forelse($purchases as $po)
                                <tr>
                                    <td><a href="{{ route('purchases.show', $po) }}">{{ $po->po_number }}</a></td>
                                    <td>{{ $po->po_date->format('d M Y') }}</td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($po->grand_total, 0) }}</td>
                                    <td class="text-end text-success">{{ currency_symbol() }}
                                        {{ number_format($po->paid_amount, 0) }}</td>
                                    <td class="text-end text-danger">
                                        {{ $po->due_amount > 0 ? currency_symbol() . ' ' . number_format($po->due_amount, 0) : '0' }}
                                    </td>
                                    <td>
                                        @if ($po->payment_status === 'paid')
                                            <span class="bp-badge bp-badge-success">Paid</span>
                                        @elseif($po->payment_status === 'partial')
                                            <span class="bp-badge bp-badge-warning">Partial</span>
                                        @else
                                            <span class="bp-badge bp-badge-danger">Unpaid</span>
                                        @endif
                                    </td>
                                    <td>
                                        @bpCanAny('purchases.view','payments.create')
                                        <div class="dropdown">
                                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @bpCan('purchases.view')
                                                <li><a class="dropdown-item" href="{{ route('purchases.show', $po) }}"><i
                                                            class="fa-solid fa-eye"></i> View</a></li>
                                                @endbpCan
                                                @if ($po->due_amount > 0)
                                                    @bpCan('payments.create')
                                                    <li><a class="dropdown-item"
                                                            href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'supplier', 'party_id' => $supplier->id]) }}"><i
                                                                class="fa-solid fa-bangladeshi-taka-sign"></i> Pay</a></li>
                                                    @endbpCan
                                                @endif
                                                @bpCan('purchases.view')
                                                <li><a class="dropdown-item" href="#"><i
                                                            class="fa-solid fa-print"></i> Print</a></li>
                                                @endbpCan
                                            </ul>
                                        </div>
                                        @endbpCanAny
                                    </td>
                                </tr>
                            @empty
                                <x-core::table.empty colspan="7" icon="fa-solid fa-cart-plus"
                                    title="No purchase history found." />
                            @endforelse
                        </tbody>
                    </x-core::table>
                </div>

                <!-- Payments Tab -->
                <div class="tab-pane fade" id="paymentHistory">
                    <x-core::table>
                        <x-core::table.header>
                            <x-core::table.column>Date</x-core::table.column>
                            <x-core::table.column>Reference</x-core::table.column>
                            <x-core::table.column align="right">Amount</x-core::table.column>
                            <x-core::table.column>Method</x-core::table.column>
                            <x-core::table.column>Note</x-core::table.column>
                            <x-core::table.column>Actions</x-core::table.column>
                        </x-core::table.header>
                        <tbody>
                            @forelse($supplier->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                    <td>{{ $payment->payment_number }}</td>
                                    <td class="text-end fw-700 text-success">{{ currency_symbol() }}
                                        {{ number_format($payment->amount, 0) }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    <td>{{ $payment->note ?? '--' }}</td>
                                    <td>
                                        <button class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline"><i
                                                class="fa-solid fa-print"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <x-core::table.empty colspan="6" icon="fa-solid fa-bangladeshi-taka-sign"
                                    title="No payment records found." />
                            @endforelse
                        </tbody>
                    </x-core::table>
                </div>

                <!-- Ledger Tab -->
                <div class="tab-pane fade" id="ledgerTab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex gap-2">
                            <a href="{{ route('supplier.ledger', $supplier) }}"
                                class="bp-btn bp-btn-sm bp-btn-primary"><i class="fa-solid fa-book me-1"></i> Full
                                Ledger</a>
                        </div>
                        <button class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-download me-1"></i>
                            Export</button>
                    </div>
                    <x-core::table>
                        <x-core::table.header>
                            <x-core::table.column>Date</x-core::table.column>
                            <x-core::table.column>Description</x-core::table.column>
                            <x-core::table.column
                                align="right">{{ $isSimpleMode ? 'Bill' : 'Debit' }}</x-core::table.column>
                            <x-core::table.column
                                align="right">{{ $isSimpleMode ? 'Paid' : 'Credit' }}</x-core::table.column>
                            <x-core::table.column
                                align="right">{{ $isSimpleMode ? 'Due' : 'Balance' }}</x-core::table.column>
                        </x-core::table.header>
                        <tbody>
                            @forelse($ledger['entries'] as $entry)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</td>
                                    <td>{{ $entry->description }}</td>
                                    <td class="text-end">
                                        @if ($entry->debit > 0)
                                            <span class="text-danger">{{ currency_symbol() }}
                                                {{ number_format($entry->debit, 0) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($entry->credit > 0)
                                            <span class="text-success">{{ currency_symbol() }}
                                                {{ number_format($entry->credit, 0) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end fw-700">{{ currency_symbol() }}
                                        {{ number_format($entry->balance, 0) }}</td>
                                </tr>
                            @empty
                                <x-core::table.empty colspan="5" icon="fa-solid fa-book"
                                    title="No ledger entries found." />
                            @endforelse
                        </tbody>
                    </x-core::table>
                </div>

                <!-- Advance Tab -->
                <div class="tab-pane fade" id="advanceTab">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="text-center p-3 rounded bp-profile-stat bp-profile-stat-accent">
                                <div class="fs-12 text-muted fw-600">Advance Balance</div>
                                <div class="fw-800 fs-4 bp-text-accent">{{ currency_symbol() }}
                                    {{ number_format($advanceStats['balance'], 0) }}</div>
                                <div class="fs-12 text-muted">Available for adjustment</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 rounded bp-profile-stat bp-profile-stat-primary">
                                <div class="fs-12 text-muted fw-600">Total Advance Paid</div>
                                <div class="fw-800 fs-4 text-primary">{{ currency_symbol() }}
                                    {{ number_format($advanceStats['total_paid'], 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 rounded bp-profile-stat bp-profile-stat-success">
                                <div class="fs-12 text-muted fw-600">Total Returned</div>
                                <div class="fw-800 fs-4 text-success">{{ currency_symbol() }}
                                    {{ number_format($advanceStats['total_returned'], 0) }}</div>
                            </div>
                        </div>
                    </div>
                    <x-core::table>
                        <x-core::table.header>
                            <x-core::table.column>Date</x-core::table.column>
                            <x-core::table.column>Type</x-core::table.column>
                            <x-core::table.column align="right">Amount</x-core::table.column>
                            <x-core::table.column>Reference</x-core::table.column>
                            <x-core::table.column>Note</x-core::table.column>
                        </x-core::table.header>
                        <tbody>
                            @forelse($advanceTransactions as $txn)
                                <tr>
                                    <td>{{ $txn->payment_date->format('d M Y') }}</td>
                                    <td>
                                        @if ($txn->payment_type === 'advance_payment')
                                            <span class="bp-badge bp-badge-primary">Payment</span>
                                        @else
                                            <span class="bp-badge bp-badge-info">Return</span>
                                        @endif
                                    </td>
                                    <td
                                        class="text-end fw-700 {{ $txn->payment_type === 'advance_return' ? 'text-danger' : 'text-success' }}">
                                        {{ $txn->payment_type === 'advance_return' ? '-' : '' }}{{ currency_symbol() }}
                                        {{ number_format($txn->amount, 0) }}
                                    </td>
                                    <td>{{ $txn->reference ?? $txn->payment_number }}</td>
                                    <td>{{ $txn->note ?? '--' }}</td>
                                </tr>
                            @empty
                                <x-core::table.empty colspan="5" icon="fa-solid fa-wallet"
                                    title="No advance transactions found." />
                            @endforelse
                        </tbody>
                    </x-core::table>
                </div>

            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // ── Supplier Split Payment Logic ──
            var spSplitIndex = 1;
            var spMaxDue = parseFloat('{{ $supplier->due_balance }}') || 0;
            var spAccountOptionsHtml = $('#spSplitRows .bp-split-payment-row:first .sp-split-account').length ?
                $('#spSplitRows .bp-split-payment-row:first .sp-split-account').html() : '';

            function spRecalcSplits() {
                var total = 0;
                $('#spSplitRows .sp-split-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#spTotalAmount').val(total > 0 ? window.numInput(total) : '');
                $('#spTotalDisplay').text('{{ currency_symbol() }} ' + Math.round(total).toLocaleString('en-IN'));
            }

            $('#spAddSplit').on('click', function() {
                var $rows = $('#spSplitRows');
                var idx = spSplitIndex++;
                var paid = 0;
                $rows.find('.sp-split-amount').each(function() {
                    paid += parseFloat($(this).val()) || 0;
                });
                var remaining = Math.max(0, spMaxDue - paid);
                var html = '<div class="bp-split-payment-row" data-index="' + idx + '">' +
                    '<div class="bp-pay-amount-col">' +
                    '<input type="number" class="bp-form-control sp-split-amount" name="splits[' + idx +
                    '][amount]" placeholder="Amount" step="0.01" min="0" value="' + (remaining > 0 ?
                        remaining : '') + '" required>' +
                    '</div>' +
                    '<div class="bp-pay-method-col">' +
                    '<select class="bp-form-select sp-split-account" name="splits[' + idx +
                    '][payment_account_id]" required>' +
                    spAccountOptionsHtml +
                    '</select>' +
                    '</div>' +
                    '<div class="bp-pay-ref-col">' +
                    '<input type="text" class="bp-form-control" name="splits[' + idx +
                    '][reference]" placeholder="Ref / TXN ID">' +
                    '</div>' +
                    '<button type="button" class="remove-split" title="Remove"><i class="fa-solid fa-times"></i></button>' +
                    '</div>';
                $rows.append(html);
                $rows.find('.remove-split').removeClass('d-none');
                spRecalcSplits();
            });

            $(document).on('click', '#spSplitRows .remove-split', function() {
                $(this).closest('.bp-split-payment-row').remove();
                var $rows = $('#spSplitRows .bp-split-payment-row');
                if ($rows.length === 1) $rows.find('.remove-split').addClass('d-none');
                spRecalcSplits();
            });

            $(document).on('input', '.sp-split-amount', function() {
                spRecalcSplits();
            });

            // Validate total does not exceed due
            $('#supplierPaymentForm').on('submit', function(e) {
                var total = parseFloat($('#spTotalAmount').val()) || 0;
                if (total <= 0) {
                    e.preventDefault();
                    alert('Payment amount must be greater than zero.');
                    return false;
                }
                if (total > spMaxDue) {
                    e.preventDefault();
                    alert('Total payment ({{ currency_symbol() }} ' + total.toFixed(2) +
                        ') exceeds due amount ({{ currency_symbol() }} ' + spMaxDue.toFixed(2) + ').');
                    return false;
                }
            });

            // Initialize
            spRecalcSplits();
        });
    </script>
@endpush
