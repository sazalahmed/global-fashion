@extends('core::layouts.master')

@section('title', 'Record Capital')
@section('page-title', 'Record Capital')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('investment.dashboard') }}">Investment</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Record Capital</span>
@endsection

@section('page-actions')
    <a href="{{ route('investment.dashboard') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i>Back</a>
@endsection

@section('content')

    <form method="POST" action="{{ route('investment.capital.store') }}">
        @csrf
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Capital Transaction</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Investor *</label>
                        <select name="investor_id" class="bp-form-select w-100" required>
                            <option value="">Select investor</option>
                            @foreach ($investors as $i)
                                <option value="{{ $i->id }}" @selected(old('investor_id', $preselectInvestor) == $i->id)>{{ $i->name }}
                                    ({{ ucfirst($i->type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Type *</label>
                        <select name="type" class="bp-form-select w-100" required>
                            <option value="inject" @selected(old('type', 'inject') === 'inject')>Inject (money in)</option>
                            <option value="withdraw" @selected(old('type') === 'withdraw')>Withdraw (money out)</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Date *</label>
                        <input type="date" class="bp-form-control" name="transaction_date"
                            value="{{ old('transaction_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Amount ({{ currency_symbol() }}) *</label>
                        <input type="number" step="0.01" min="0.01" class="bp-form-control" name="amount"
                            value="{{ old('amount') }}" required>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Payment Account *</label>
                        <select name="payment_account_id" class="bp-form-select w-100" required>
                            <option value="">Select account</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected(old('payment_account_id') == $a->id)>{{ $a->name }}
                                    ({{ $a->account_type }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Reference</label>
                        <input type="text" class="bp-form-control" name="reference" value="{{ old('reference') }}"
                            placeholder="Cheque #, txn ID, etc.">
                    </div>
                    <div class="col-md-12">
                        <label class="bp-form-label">Note</label>
                        <textarea class="bp-form-control" name="note" rows="2">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="bp-card-footer text-end">
                <a href="{{ route('investment.dashboard') }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i>Record</button>
            </div>
        </div>
    </form>

@endsection
