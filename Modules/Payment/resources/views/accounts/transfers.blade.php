@extends('core::layouts.master')

@section('title', __('Balance Transfers'))
@section('page-title', __('Balance Transfers'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('payment-accounts.index') }}">Payment Accounts</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Transfers</span>
@endsection

@section('page-actions')
    <a href="{{ route('payment-accounts.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@section('content')

    <div class="row g-4">
        <!-- Transfer Form -->
        <div class="col-xl-5">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-arrows-left-right me-2"></i>New Transfer</h5>
                </div>
                <div class="bp-card-body">
                    <form action="{{ route('payment-accounts.transfers.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">From Account *</label>
                                <select class="bp-form-select w-100 @error('from_account_id') is-invalid @enderror"
                                    name="from_account_id" required>
                                    <option value="">Select source</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}"
                                            {{ old('from_account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}
                                            ({{ $acc->account_type_label }}) —
                                            {{ currency_symbol() }} {{ number_format($accountBalances[$acc->id] ?? 0, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('from_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="bp-form-hint">A transfer cannot exceed the source account's balance.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">To Account *</label>
                                <select class="bp-form-select w-100 @error('to_account_id') is-invalid @enderror"
                                    name="to_account_id" required>
                                    <option value="">Select destination</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}"
                                            {{ old('to_account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}
                                            ({{ $acc->account_type_label }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Amount ({{ currency_symbol() }}) *</label>
                                <input type="number" class="bp-form-control @error('amount') is-invalid @enderror"
                                    name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required
                                    placeholder="0.00">
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Date *</label>
                                <input type="date" class="bp-form-control @error('date') is-invalid @enderror"
                                    name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Note</label>
                                <textarea class="bp-form-control" name="note" rows="2" placeholder="Transfer reason...">{{ old('note') }}</textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="bp-btn bp-btn-success justify-content-center w-100"><i
                                        class="fa-solid fa-arrows-left-right me-1"></i> Transfer</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Transfer History -->
        <div class="col-xl-7">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-clock-rotate-left me-2"></i>Transfer History</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Amount</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $t)
                                    <tr>
                                        <td class="fs-12">{{ $t->date->format('d M Y') }}</td>
                                        <td class="fw-600 fs-12">{{ $t->fromAccount->name ?? '' }}</td>
                                        <td class="fw-600 fs-12">{{ $t->toAccount->name ?? '' }}</td>
                                        <td class="fw-700">{{ money($t->amount) }}</td>
                                        <td class="fs-12 text-muted">{{ Str::limit($t->note, 30) }}</td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="5" icon="fa-solid fa-arrows-left-right"
                                        title="No transfers recorded" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <x-core::table.pagination :paginator="$transfers" itemLabel="transfers" />
            </div>
        </div>
    </div>

@endsection
