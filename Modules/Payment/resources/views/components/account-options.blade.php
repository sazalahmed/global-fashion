@php
/**
 * Dynamic payment account options from the payment_accounts table.
 *
 * Usage:
 *   <select name="payment_account_id">
 *     <option value="">Select Payment Method</option>
 *     <x-payment::account-options :selected="old('payment_account_id', $model->payment_account_id ?? '')" />
 *   </select>
 *
 * Props:
 *   - selected (int|string|null): Pre-selected payment_account_id value
 *   - type (string|null): Filter by account_type ('cash', 'mobile_banking', 'bank', 'card')
 *
 * Each <option> includes:
 *   value = payment_account.id
 *   data-type = account_type (cash, mobile_banking, bank, card)
 *   data-default = 1 if this is the default account for its type
 */
$accounts = \Modules\Payment\Models\PaymentAccount::active()
    ->with('bank')
    ->when($type ?? null, fn ($q, $t) => $q->ofType($t))
    ->orderByRaw("FIELD(account_type, 'cash', 'mobile_banking', 'bank', 'card')")
    ->orderBy('name')
    ->get();

$selectedVal = $selected ?? null;
@endphp

@foreach($accounts as $account)
<option value="{{ $account->id }}"
  data-type="{{ $account->account_type }}"
  data-default="{{ $account->is_default ? '1' : '0' }}"
  {{ (string) $selectedVal === (string) $account->id ? 'selected' : '' }}>{{ $account->display_name }}</option>
@endforeach
