@extends('core::layouts.master')

@php
    $isEmployeePay = $partyType === 'employee' && $prefillParty;
    $backUrl = $isEmployeePay ? route('employee.show', $partyId) : route('payments.index');
    $backLabel = $isEmployeePay ? 'Back to ' . $prefillParty->name : 'Back to Payments';
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@section('breadcrumb')
    @foreach ($breadcrumb as $crumb)
        <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
        @if (!empty($crumb['route']))
            <a href="{{ route($crumb['route'], $crumb['params'] ?? []) }}">{{ $crumb['label'] }}</a>
        @else
            <span>{{ $crumb['label'] }}</span>
        @endif
    @endforeach
@endsection

@section('page-actions')
    <a href="{{ $backUrl }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> {{ $backLabel }}
    </a>
@endsection

@section('content')

    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">

            <form action="{{ route('payments.store') }}" method="POST" id="recordPaymentForm">
                @csrf

                @php $defaultPaymentType = old('payment_type', $paymentType ?: 'against_invoice'); @endphp

                @if ($prefillParty)
                    <input type="hidden" name="direction" value="{{ old('direction', $direction) }}">
                    <input type="hidden" name="party_type" value="{{ old('party_type', $partyType) }}">
                    <input type="hidden" name="party_id" value="{{ old('party_id', $partyId) }}">
                    <input type="hidden" name="payment_type" value="{{ $defaultPaymentType }}">
                @else
                    <div class="bp-card mb-4">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Payment
                                Information</h5>
                        </div>
                        <div class="bp-card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="bp-form-label">Payment Direction *</label>
                                    <div class="d-flex gap-2 mt-1">
                                        @if (!$directionLocked || old('direction', $direction) == 'receive')
                                            <input type="radio" class="btn-check" name="direction" id="dirReceive"
                                                value="receive"
                                                {{ old('direction', $direction) == 'receive' ? 'checked' : '' }}>
                                            <label class="bp-btn bp-btn-outline" for="dirReceive">
                                                <i class="fa-solid fa-arrow-down me-1"></i> Receive Payment
                                            </label>
                                        @endif
                                        @if (!$directionLocked || old('direction', $direction) == 'pay')
                                            <input type="radio" class="btn-check" name="direction" id="dirPay"
                                                value="pay"
                                                {{ old('direction', $direction) == 'pay' ? 'checked' : '' }}>
                                            <label class="bp-btn bp-btn-outline" for="dirPay">
                                                <i class="fa-solid fa-arrow-up me-1"></i> Make Payment
                                            </label>
                                        @endif
                                    </div>
                                    @error('direction')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Party Type --}}
                                <div class="col-md-6">
                                    <label class="bp-form-label">Party Type *</label>
                                    <select class="bp-form-select w-100" name="party_type" id="paymentPartyType" required>
                                        <option value="">Select Party Type</option>
                                        <option value="customer" @selected(old('party_type', $partyType) === 'customer')>Customer</option>
                                        <option value="supplier" @selected(old('party_type', $partyType) === 'supplier')>Supplier</option>
                                        <option value="employee" @selected(old('party_type', $partyType) === 'employee')>Employee</option>
                                    </select>
                                    @error('party_type')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Party Name --}}
                                <div class="col-md-6">
                                    <label class="bp-form-label">Party Name *</label>
                                    <select class="bp-form-select w-100" name="party_id" id="paymentPartyName" required>
                                        <option value="">Select Party</option>
                                    </select>
                                    @error('party_id')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Payment Type --}}
                                <div class="col-12">
                                    <label class="bp-form-label">Payment Type *</label>
                                    <div class="d-flex gap-3 mt-1">
                                        @if (!$paymentTypeLocked || $defaultPaymentType == 'against_invoice')
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_type"
                                                    id="typeInvoice" value="against_invoice"
                                                    {{ $defaultPaymentType == 'against_invoice' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-600 fs-13" for="typeInvoice">Against
                                                    Invoice /
                                                    PO</label>
                                            </div>
                                        @endif
                                        @if (!$paymentTypeLocked || $defaultPaymentType == 'advance_payment')
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_type"
                                                    id="typeAdvance" value="advance_payment"
                                                    {{ $defaultPaymentType == 'advance_payment' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-600 fs-13" for="typeAdvance">Advance
                                                    Payment</label>
                                            </div>
                                        @endif
                                        @if (!$paymentTypeLocked || $defaultPaymentType == 'advance_return')
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_type"
                                                    id="typeReturn" value="advance_return"
                                                    {{ $defaultPaymentType == 'advance_return' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-600 fs-13" for="typeReturn">Advance
                                                    Return</label>
                                            </div>
                                        @endif
                                    </div>
                                    @error('payment_type')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>
                @endif

                {{-- Outstanding Invoices / POs (shown when "Against Invoice/PO" selected) --}}
                @unless ($isEmployeePay)
                    <div class="bp-card mb-4" id="outstandingSection">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2"></i>Outstanding Invoices / POs
                            </h5>
                        </div>
                        <div class="bp-card-body p-0">
                            <div class="bp-table-wrapper" style="max-height: 300px; overflow-y: auto;">
                                <table class="bp-table">
                                    <thead>
                                        <tr>
                                            <th class="bp-th-checkbox"><input type="checkbox" class="bp-check-all"
                                                    title="Select All"></th>
                                            <th>Reference</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Due</th>
                                            <th>Paying</th>
                                            <th>Discount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="outstandingList">
                                        @forelse($outstandingInvoices as $i => $inv)
                                            @php
                                                $ref = $inv->invoice_number ?? ($inv->po_number ?? 'INV-' . $inv->id);
                                                $allocType =
                                                    $partyType === 'customer'
                                                        ? 'Modules\\Sale\\Models\\Sale'
                                                        : 'Modules\\Purchase\\Models\\Purchase';
                                            @endphp
                                            <tr>
                                                <td><input type="checkbox" class="form-check-input row-checkbox"
                                                        name="allocations[{{ $i }}][allocatable_id]"
                                                        value="{{ $inv->id }}" checked></td>
                                                <td class="fw-600">{{ $ref }}</td>
                                                <td>{{ \Carbon\Carbon::parse($inv->created_at)->format('d M Y') }}</td>
                                                <td>{{ currency_symbol() }} {{ number_format($inv->grand_total, 0) }}</td>
                                                <td class="text-danger fw-700">{{ currency_symbol() }}
                                                    {{ number_format($inv->due_amount, 0) }}</td>
                                                <td>
                                                    <input type="number" class="bp-form-control paying-amount"
                                                        name="allocations[{{ $i }}][amount]" placeholder="0"
                                                        step="0.01" max="{{ $inv->due_amount }}"
                                                        value="{{ $inv->due_amount }}">
                                                    <input type="hidden"
                                                        name="allocations[{{ $i }}][allocatable_type]"
                                                        value="{{ $allocType }}">
                                                </td>
                                                <td>
                                                    <input type="number" class="bp-form-control discount-amount"
                                                        name="allocations[{{ $i }}][discount_amount]"
                                                        placeholder="0" step="0.01" value="0" readonly>
                                                </td>
                                            </tr>
                                        @empty
                                            @if ($prefillParty)
                                                <tr>
                                                    <td colspan="7" class="text-center py-3 text-muted">No outstanding
                                                        invoices found.</td>
                                                </tr>
                                            @endif
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="bp-card-footer d-none" id="cpDueSummary">
                            <div class="bp-due-summary">
                                <div class="bp-due-summary-item">
                                    <span class="bp-due-summary-label">Total Due</span>
                                    <span class="bp-due-summary-value due">{{ currency_symbol() }} <span
                                            id="cpSumDue">0</span></span>
                                </div>
                                <div class="bp-due-summary-item">
                                    <span class="bp-due-summary-label">Paying</span>
                                    <span class="bp-due-summary-value paying">{{ currency_symbol() }} <span
                                            id="cpSumPaying">0</span></span>
                                </div>
                                <div class="bp-due-summary-item">
                                    <span class="bp-due-summary-label">Discount</span>
                                    <span class="bp-due-summary-value discount">{{ currency_symbol() }} <span
                                            id="cpSumDiscount">0</span></span>
                                </div>
                                <div class="bp-due-summary-item">
                                    <span class="bp-due-summary-label">Remaining Due</span>
                                    <span class="bp-due-summary-value remaining">{{ currency_symbol() }} <span
                                            id="cpSumRemaining">0</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endunless

                <!-- Payment Details -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-credit-card me-2"></i>Payment Details</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">

                            {{-- Total Amount + Discount (customer due receive only) + Date --}}
                            <div class="col-md-6">
                                <label class="bp-form-label">Total Amount ({{ currency_symbol() }}) *</label>
                                <input type="number" class="bp-form-control" name="amount" id="cpTotalAmount" required
                                    placeholder="0.00" step="0.01" value="{{ old('amount') }}" readonly>
                                @error('amount')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 d-none" id="cpDiscountGroup">
                                <label class="bp-form-label">Discount Amount ({{ currency_symbol() }})</label>
                                <input type="number" class="bp-form-control" id="cpDiscountAmount" placeholder="0.00"
                                    step="0.01" min="0" value="0">
                                <div class="fs-11 text-muted mt-1">Written off — reduces due without collecting cash.</div>
                                @error('allocations')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Date *</label>
                                <input type="date" class="bp-form-control" name="payment_date"
                                    value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                @error('payment_date')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Split Payment Rows --}}
                            <div class="col-12">
                                <label class="bp-form-label">Payment Splits *</label>
                                <div id="cpSplitRows">
                                    <div class="bp-split-payment-row" data-index="0">
                                        <div class="bp-pay-amount-col">
                                            <input type="number" class="bp-form-control cp-split-amount"
                                                name="splits[0][amount]" placeholder="Amount" step="0.01"
                                                min="0" value="{{ old('splits.0.amount', request('amount')) }}"
                                                required>
                                        </div>
                                        <div class="bp-pay-account-col">
                                            <select class="bp-form-select cp-split-account"
                                                name="splits[0][payment_account_id]" required>
                                                <option value="">Select Account</option>
                                                @foreach ($paymentAccounts as $account)
                                                    <option value="{{ $account->id }}"
                                                        data-type="{{ $account->account_type }}">
                                                        {{ $account->display_name }}</option>
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
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline mt-2" id="cpAddSplit"><i
                                        class="fa-solid fa-plus me-1"></i> Add Split Payment</button>
                                @error('splits')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Note --}}
                            <div class="col-12">
                                <label class="bp-form-label">Note</label>
                                <textarea class="bp-form-control" name="note" rows="3" placeholder="Optional note about this payment...">{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                    <a href="{{ $backUrl }}" class="bp-btn bp-btn-danger"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</a>
                    <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save
                        Payment</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(document).ready(function() {

            // ── Split Payment Logic ──
            var cpSplitIndex = 1;
            var cpAccountOptionsHtml = $('#cpSplitRows .bp-split-payment-row:first .cp-split-account').html();

            function cpRecalcSplits() {
                var total = 0;
                $('#cpSplitRows .cp-split-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                // A pure discount write-off is a legitimate 0 — only blank
                // the field (so it doesn't read as "0.00" before anything is
                // entered) when there's also no discount driving it.
                var discountVal = parseFloat($('#cpDiscountAmount').val()) || 0;
                $('#cpTotalAmount').val((total > 0 || discountVal > 0) ? window.numInput(total) : '');
                // Auto-distribute across outstanding invoices
                cpDistributeToInvoices(total);
                cpDistributeDiscount(discountVal);
                cpUpdateDueSummary(total, discountVal);
            }

            // Live Total Due / Paying / Discount / Remaining Due strip under the invoice list.
            function cpFmt(n) {
                return Number(n || 0).toLocaleString('en-BD', {
                    maximumFractionDigits: 2
                });
            }

            function cpUpdateDueSummary(paying, discount) {
                discount = discount || 0;
                var $rows = $('#outstandingList .paying-amount');
                if (!$rows.length) {
                    $('#cpDueSummary').addClass('d-none');
                    return;
                }
                var totalDue = 0;
                $rows.each(function() {
                    totalDue += parseFloat($(this).attr('max')) || 0;
                });
                var remaining = Math.max(0, totalDue - paying - discount);
                $('#cpSumDue').text(cpFmt(totalDue));
                $('#cpSumPaying').text(cpFmt(paying));
                $('#cpSumDiscount').text(cpFmt(discount));
                $('#cpSumRemaining').text(cpFmt(remaining));
                $('#cpDueSummary').removeClass('d-none')
                    .find('.bp-due-summary-value.remaining').toggleClass('cleared', remaining <= 0);
            }

            // Settle every step to paisa. Subtracting raw floats left a
            // remainder like 0.004999 on the next invoice, which is not a
            // payable amount but is above zero, so the form was rejected with
            // "allocations.1.amount must be at least 0.01".
            function cpToPaisa(value) {
                return Math.round((parseFloat(value) || 0) * 100) / 100;
            }

            function cpDistributeToInvoices(totalAmount) {
                var remaining = cpToPaisa(totalAmount);
                $('#outstandingList tr').each(function() {
                    var $checkbox = $(this).find('.row-checkbox');
                    var $payingInput = $(this).find('.paying-amount');
                    if (!$checkbox.length || !$payingInput.length) return;

                    if ($checkbox.is(':checked')) {
                        var maxDue = cpToPaisa($payingInput.attr('max'));
                        var allocate = cpToPaisa(Math.min(remaining, maxDue));
                        // Anything under a paisa is nothing to pay.
                        if (allocate < 0.01) allocate = 0;
                        $payingInput.val(allocate);
                        remaining = cpToPaisa(remaining - allocate);
                    } else {
                        $payingInput.val(0);
                    }
                });
            }

            // Fills whatever room is LEFT in each checked invoice's due after
            // the paying amount above already claimed its share — so
            // "paying + discount" can never exceed a row's due just from
            // client-side arithmetic (the server re-checks this against the
            // live due_amount regardless).
            function cpDistributeDiscount(discountAmount) {
                var remaining = cpToPaisa(discountAmount);
                $('#outstandingList tr').each(function() {
                    var $checkbox = $(this).find('.row-checkbox');
                    var $payingInput = $(this).find('.paying-amount');
                    var $discountInput = $(this).find('.discount-amount');
                    if (!$checkbox.length || !$payingInput.length || !$discountInput.length) return;

                    if ($checkbox.is(':checked')) {
                        var maxDue = cpToPaisa($payingInput.attr('max'));
                        var paying = cpToPaisa($payingInput.val());
                        var roomLeft = cpToPaisa(Math.max(0, maxDue - paying));
                        var allocate = cpToPaisa(Math.min(remaining, roomLeft));
                        if (allocate < 0.01) allocate = 0;
                        $discountInput.val(allocate);
                        remaining = cpToPaisa(remaining - allocate);
                    } else {
                        $discountInput.val(0);
                    }
                });
            }

            $('#cpDiscountAmount').on('input', function() {
                var total = 0;
                $('#cpSplitRows .cp-split-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                var discountVal = parseFloat($(this).val()) || 0;
                $('#cpTotalAmount').val((total > 0 || discountVal > 0) ? window.numInput(total) : '');
                cpDistributeDiscount(discountVal);
                cpUpdateDueSummary(total, discountVal);
            });

            // The discount write-off only makes sense collecting a customer's
            // due (direction=receive, payment_type=against_invoice). Hidden
            // otherwise so a $0 discount is always what's actually submitted.
            function cpToggleDiscountField() {
                var isReceive = $('input[name="direction"]:checked').val() ||
                    $('input[name="direction"][type=hidden]').val();
                var partyType = $('#paymentPartyType').val() ||
                    $('input[name="party_type"][type=hidden]').val();
                var paymentType = $('input[name="payment_type"]:checked').val() ||
                    $('input[name="payment_type"][type=hidden]').val();

                var show = isReceive === 'receive' && partyType === 'customer' && paymentType === 'against_invoice';
                $('#cpDiscountGroup').toggleClass('d-none', !show);
                if (!show) {
                    $('#cpDiscountAmount').val(0);
                    cpDistributeDiscount(0);
                    var total = 0;
                    $('#cpSplitRows .cp-split-amount').each(function() {
                        total += parseFloat($(this).val()) || 0;
                    });
                    cpUpdateDueSummary(total, 0);
                }
            }

            $('#cpAddSplit').on('click', function() {
                var $rows = $('#cpSplitRows');
                var idx = cpSplitIndex++;
                var html = '<div class="bp-split-payment-row" data-index="' + idx + '">' +
                    '<div class="bp-pay-amount-col">' +
                    '<input type="number" class="bp-form-control cp-split-amount" name="splits[' + idx +
                    '][amount]" placeholder="Amount" step="0.01" min="0" required>' +
                    '</div>' +
                    '<div class="bp-pay-account-col">' +
                    '<select class="bp-form-select cp-split-account" name="splits[' + idx +
                    '][payment_account_id]" required>' +
                    cpAccountOptionsHtml +
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
            });

            $(document).on('click', '#cpSplitRows .remove-split', function() {
                $(this).closest('.bp-split-payment-row').remove();
                var $rows = $('#cpSplitRows .bp-split-payment-row');
                if ($rows.length === 1) $rows.find('.remove-split').addClass('d-none');
                cpRecalcSplits();
            });

            $(document).on('input', '.cp-split-amount', function() {
                cpRecalcSplits();
            });

            // Re-distribute when a checkbox is toggled
            $(document).on('change', '#outstandingList .row-checkbox', function() {
                cpRecalcSplits();
            });

            // Toggle outstanding section based on payment type
            $('input[name="payment_type"]').on('change', function() {
                if ($(this).val() === 'against_invoice') {
                    $('#outstandingSection').removeClass('d-none');
                } else {
                    $('#outstandingSection').addClass('d-none');
                }
                cpToggleDiscountField();
            });

            // Initialize visibility on page load — works for both visible radios and hidden inputs (deep-link mode)
            var ptInitVal = $('input[name="payment_type"]:checked').val() ||
                $('input[name="payment_type"][type=hidden]').val() ||
                'against_invoice';
            if (ptInitVal !== 'against_invoice') {
                $('#outstandingSection').addClass('d-none');
            }
            cpToggleDiscountField();

            $('input[name="direction"]').on('change', cpToggleDiscountField);
            $('#paymentPartyType').on('change', cpToggleDiscountField);

            // Select all checkbox
            $('.bp-check-all').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('#outstandingList .row-checkbox').prop('checked', isChecked);
            });

            // Populate the Party Name dropdown for the selected party type. For
            // "against invoice" payments the list is limited to parties that owe
            // (payment_type is passed through); advance payments list everyone.
            // preselectId re-selects a party after (re)loading — used on initial
            // page load (deep link / validation restore) and payment-type change.
            function loadParties(preselectId) {
                var type = $('#paymentPartyType').val();
                var $select = $('#paymentPartyName');
                $select.html('<option value="">Select Party</option>');
                if (!type) return;

                $.get('{{ route('payments.party-search') }}', {
                    type: type,
                    q: '',
                    payment_type: $('input[name="payment_type"]:checked').val() || ''
                }, function(data) {
                    $.each(data, function(i, item) {
                        var label = item.name + (item.phone ? ' (' + item.phone + ')' : '');
                        $select.append($('<option>').val(item.id).text(label));
                    });
                    if (preselectId) {
                        $select.val(String(preselectId));
                    }
                    $select.trigger('change');
                });
            }

            $('#paymentPartyType').on('change', function() {
                loadParties(null);
            });

            // Switching payment type changes the dues filter (against-invoice →
            // only parties with dues), so reload while keeping the current pick.
            $('input[name="payment_type"]').on('change', function() {
                loadParties($('#paymentPartyName').val());
            });

            // Load outstanding invoices when party changes
            $('#paymentPartyName').on('change', function() {
                loadOutstandingInvoices();
            });

            // Populate on load when a party type is preset (deep link) or restore
            // the chosen party after a validation error.
            @if (!$prefillParty)
                @php $initPartyId = old('party_id', $partyId); @endphp
                if ($('#paymentPartyType').val()) {
                    loadParties(@json($initPartyId ?: null));
                }
            @endif

            function loadOutstandingInvoices() {
                var partyType = $('#paymentPartyType').val();
                var partyId = $('#paymentPartyName').val();
                var $tbody = $('#outstandingList');
                $tbody.empty();

                if (!partyType || !partyId || $('input[name="payment_type"]:checked').val() !== 'against_invoice')
                    return;

                $.get('{{ route('payments.outstanding-invoices') }}', {
                    party_type: partyType,
                    party_id: partyId
                }, function(data) {
                    if (data.length === 0) {
                        $tbody.html(
                            '<tr><td colspan="7" class="text-center py-3 text-muted">No outstanding invoices found.</td></tr>'
                        );
                        cpRecalcSplits();
                        return;
                    }
                    $.each(data, function(i, inv) {
                        var $ref = $('<span>').text(inv.invoice_number || inv.po_number || 'INV-' +
                            inv.id);
                        var date = inv.created_at ? new Date(inv.created_at).toLocaleDateString(
                            'en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            }) : '--';
                        var allocType = (partyType === 'customer' ?
                            'Modules\\\\Sale\\\\Models\\\\Sale' :
                            'Modules\\\\Purchase\\\\Models\\\\Purchase');
                        $tbody.append(
                            '<tr>' +
                            '<td><input type="checkbox" class="form-check-input row-checkbox" name="allocations[' +
                            i + '][allocatable_id]" value="' + inv.id + '"></td>' +
                            '<td class="fw-600">' + $ref.html() + '</td>' +
                            '<td>' + date + '</td>' +
                            '<td>{{ currency_symbol() }} ' + Number(inv.grand_total)
                            .toLocaleString() + '</td>' +
                            '<td class="text-danger fw-700">{{ currency_symbol() }} ' + Number(
                                inv.due_amount).toLocaleString() + '</td>' +
                            '<td><input type="number" class="bp-form-control paying-amount" name="allocations[' +
                            i + '][amount]" placeholder="0" step="0.01" max="' + inv
                            .due_amount + '">' +
                            '<input type="hidden" name="allocations[' + i +
                            '][allocatable_type]" value="' + allocType + '"></td>' +
                            '<td><input type="number" class="bp-form-control discount-amount" name="allocations[' +
                            i +
                            '][discount_amount]" placeholder="0" step="0.01" value="0" readonly></td>' +
                            '</tr>'
                        );
                    });
                    cpRecalcSplits();
                });
            }

            // Initialize split total on page load
            cpRecalcSplits();

            // Direction toggle visual state
            $('input[name="direction"]').on('change', function() {
                $('label[for="dirReceive"], label[for="dirPay"]').removeClass('bp-btn-primary').addClass(
                    'bp-btn-outline');
                $('label[for="' + $(this).attr('id') + '"]').removeClass('bp-btn-outline').addClass(
                    'bp-btn-primary');
            });

            // Initialize direction visual state on page load
            var checkedDir = $('input[name="direction"]:checked').attr('id');
            if (checkedDir) {
                $('label[for="' + checkedDir + '"]').removeClass('bp-btn-outline').addClass('bp-btn-primary');
            }

            // On page load: if party type is pre-selected, load the party list and outstanding invoices
            (function initFromQueryParams() {
                var partyType = $('#paymentPartyType').val();
                if (!partyType) return;

                var $partySelect = $('#paymentPartyName');
                var prefillId = $partySelect.val() || null;

                $.get('{{ route('payments.party-search') }}', {
                    type: partyType,
                    q: ''
                }, function(data) {
                    var currentVal = $partySelect.val();

                    $.each(data, function(i, item) {
                        if ($partySelect.find('option[value="' + item.id + '"]').length) return;
                        var label = item.name;
                        if (item.phone) label += ' (' + item.phone + ')';
                        $partySelect.append($('<option>').val(item.id).text(label));
                    });

                    // Re-select the prefilled value if it exists
                    if (prefillId) {
                        $partySelect.val(prefillId);
                    }

                    // Load outstanding invoices if party is selected
                    if ($partySelect.val()) {
                        loadOutstandingInvoices();
                    }
                });
            })();

        });
    </script>
@endpush
