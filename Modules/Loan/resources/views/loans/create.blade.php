@extends('core::layouts.master')

@section('title', __('New Loan'))
@section('page-title', __('New Loan'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('loans.index') }}">Loans</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>New Loan</span>
@endsection

@section('page-actions')
    <a href="{{ route('loans.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Loans
    </a>
@endsection

@section('content')

    <form action="{{ route('loans.store') }}" method="POST" id="newLoanForm">
        @csrf

        <div class="row g-4">
            <!-- Left Column: Loan Details -->
            <div class="col-xl-8">

                <!-- Lender & Amount -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Loan Details</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Lender *</label>
                                <select class="bp-form-select w-100" name="lender_id" id="lenderSelect" required>
                                    <option value="">Select Lender</option>
                                    @foreach ($lenders as $lender)
                                        <option value="{{ $lender->id }}"
                                            {{ old('lender_id') == $lender->id ? 'selected' : '' }}>
                                            {{ $lender->name }}{{ $lender->company_name ? ' — ' . $lender->company_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('lender_id')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Principal Amount ({{ currency_symbol() }}) *</label>
                                <input type="number" class="bp-form-control" name="principal_amount" id="principalAmount"
                                    value="{{ old('principal_amount') }}" min="1" step="0.01" required>
                                @error('principal_amount')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Disbursement Date *</label>
                                <input type="date" class="bp-form-control" name="disbursement_date" id="disbursementDate"
                                    value="{{ old('disbursement_date', date('Y-m-d')) }}" required>
                                @error('disbursement_date')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Receive Into *</label>
                                <select class="bp-form-select w-100" name="disbursement_account_id"
                                    id="paymentAccountSelect" required>
                                    <option value="">Select Payment Account</option>
                                    @foreach ($paymentAccounts as $account)
                                        <option value="{{ $account->id }}"
                                            {{ old('disbursement_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('disbursement_account_id')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Repayment Terms -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>Repayment Terms</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="bp-form-label">Total Installments *</label>
                                <input type="number" class="bp-form-control" name="total_installments"
                                    id="totalInstallments" value="{{ old('total_installments') }}" min="1" required>
                                @error('total_installments')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="bp-form-label">Frequency *</label>
                                <select class="bp-form-select w-100" name="frequency" id="frequency" required>
                                    <option value="weekly" {{ old('frequency') === 'weekly' ? 'selected' : '' }}>Weekly
                                    </option>
                                    <option value="bi_weekly" {{ old('frequency') === 'bi_weekly' ? 'selected' : '' }}>
                                        Bi-weekly</option>
                                    <option value="monthly"
                                        {{ old('frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                                @error('frequency')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="bp-form-label">First Repayment Date *</label>
                                <input type="date" class="bp-form-control" name="start_date" id="firstRepaymentDate"
                                    value="{{ old('start_date') }}" required>
                                @error('start_date')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2"></i>Additional Information</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Reference</label>
                                <input type="text" class="bp-form-control" name="reference"
                                    value="{{ old('reference') }}" placeholder="Optional reference number">
                                @error('reference')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Note</label>
                                <textarea class="bp-form-control" name="note" rows="3" placeholder="Optional notes about this loan">{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Summary & Actions -->
            <div class="col-xl-4">

                <!-- Loan Summary -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2"></i>Loan Summary</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="bp-cart-summary-row">
                            <span>Principal Amount</span>
                            <span class="fw-700" id="summaryPrincipal">{{ currency_symbol() }} 0</span>
                        </div>
                        <div class="bp-cart-summary-row">
                            <span>Installments</span>
                            <span class="fw-600" id="summaryInstallments">0 x Monthly</span>
                        </div>
                        <div class="bp-cart-summary-row total">
                            <span>Per Installment</span>
                            <span id="summaryPerInstallment">{{ currency_symbol() }} 0</span>
                        </div>
                        <div class="bp-cart-summary-row">
                            <span>First Repayment</span>
                            <span class="fw-600" id="summaryFirstDue">-</span>
                        </div>
                        <div class="bp-cart-summary-row">
                            <span>Last Repayment</span>
                            <span class="fw-600" id="summaryLastDue">-</span>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex flex-column gap-2 mt-3">
                    <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center"><i
                            class="fa-solid fa-check me-1"></i> Create Loan</button>
                    <a href="{{ route('loans.index') }}" class="bp-btn bp-btn-danger w-100 justify-content-center"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</a>
                </div>

            </div>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            function formatBDT(num) {
                num = Math.round(num);
                var str = num.toString();
                var lastThree = str.substring(str.length - 3);
                var otherNumbers = str.substring(0, str.length - 3);
                if (otherNumbers !== '') {
                    lastThree = ',' + lastThree;
                }
                return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
            }

            function addMonths(date, n) {
                var d = new Date(date);
                d.setMonth(d.getMonth() + n);
                return d;
            }

            function addWeeks(date, n) {
                var d = new Date(date);
                d.setDate(d.getDate() + (n * 7));
                return d;
            }

            function formatDate(d) {
                return ('0' + d.getDate()).slice(-2) + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
            }

            function recalculate() {
                var principal = parseFloat($('#principalAmount').val()) || 0;
                var numInst = parseInt($('#totalInstallments').val()) || 0;
                var freq = $('#frequency').val();
                var startDate = new Date($('#firstRepaymentDate').val());

                $('#summaryPrincipal').text('{{ currency_symbol() }} ' + formatBDT(principal));

                if (numInst <= 0 || principal <= 0) {
                    $('#summaryInstallments').text('0 x Monthly');
                    $('#summaryPerInstallment').text('{{ currency_symbol() }} 0');
                    $('#summaryFirstDue').text('-');
                    $('#summaryLastDue').text('-');
                    return;
                }

                var perInstallment = Math.floor(principal / numInst);
                var freqLabel = freq === 'weekly' ? 'Weekly' : freq === 'bi_weekly' ? 'Bi-weekly' : 'Monthly';

                $('#summaryInstallments').text(numInst + ' x ' + freqLabel);
                $('#summaryPerInstallment').text('{{ currency_symbol() }} ' + formatBDT(perInstallment));

                if (!isNaN(startDate.getTime())) {
                    $('#summaryFirstDue').text(formatDate(startDate));

                    var lastDate;
                    if (freq === 'monthly') {
                        lastDate = addMonths(startDate, numInst - 1);
                    } else if (freq === 'bi_weekly') {
                        lastDate = addWeeks(startDate, (numInst - 1) * 2);
                    } else {
                        lastDate = addWeeks(startDate, numInst - 1);
                    }
                    $('#summaryLastDue').text(formatDate(lastDate));
                } else {
                    $('#summaryFirstDue').text('-');
                    $('#summaryLastDue').text('-');
                }
            }

            $('#principalAmount, #totalInstallments, #frequency, #firstRepaymentDate').on('change input',
                recalculate);

            recalculate();
        });
    </script>
@endpush
