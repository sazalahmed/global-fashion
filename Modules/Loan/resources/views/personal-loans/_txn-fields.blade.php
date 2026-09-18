{{-- Shared amount/date/account fields for the give-loan & receive-payment modals.
     Expects $paymentAccounts; optional $maxAmount. --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="bp-form-label">Amount *</label>
        <input type="number" class="bp-form-control" name="amount" min="0.01" step="0.01"
            @isset($maxAmount) max="{{ $maxAmount }}" value="{{ num_input($maxAmount) }}" @endisset required>
    </div>
    <div class="col-md-6">
        <label class="bp-form-label">Date *</label>
        <input type="date" class="bp-form-control" name="txn_date" value="{{ date('Y-m-d') }}" required>
    </div>
    <div class="col-md-6">
        <label class="bp-form-label">Payment Account *</label>
        <select class="bp-form-select w-100" name="payment_account_id" required>
            <option value="">Select Account</option>
            @foreach($paymentAccounts as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ ucfirst(str_replace('_', ' ', $acc->account_type)) }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="bp-form-label">Reference</label>
        <input type="text" class="bp-form-control" name="reference" placeholder="e.g. bKash TrxID / cheque no">
    </div>
    <div class="col-12">
        <label class="bp-form-label">Note</label>
        <input type="text" class="bp-form-control" name="note" placeholder="Optional note">
    </div>
</div>
