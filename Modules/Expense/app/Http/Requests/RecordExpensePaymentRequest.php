<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Payment\Http\Requests\Concerns\GuardsAccountBalance;

class RecordExpensePaymentRequest extends FormRequest
{
    use GuardsAccountBalance;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'                      => 'required|numeric|min:0.01',
            'payment_account_id'          => 'nullable|exists:payment_accounts,id',
            'payment_date'                => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'reference'                   => 'nullable|string|max:100',
            'splits'                      => 'nullable|array|min:1',
            'splits.*.amount'             => 'required_with:splits|numeric|min:0.01',
            'splits.*.payment_account_id' => 'required_with:splits|exists:payment_accounts,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $splits = $this->input('splits', []);
            $amount = (float) $this->input('amount', 0);

            // Falls back to the expense's own account when the form leaves it
            // blank, matching what ExpenseService::recordPayment() will charge.
            $fallback = $this->input('payment_account_id')
                ?? $this->route('expense')?->payment_account_id;

            $this->guardAccountBalance(
                $validator,
                $this->outflowsFromSplits($splits, $fallback, $amount),
                $this->input('payment_date'),
                'amount',
            );
        });
    }
}
