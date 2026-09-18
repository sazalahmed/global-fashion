<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCapitalTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type'               => ['required', 'in:deposit,withdraw'],
            'payment_account_id' => ['required', 'integer', 'exists:payment_accounts,id'],
            'transaction_date'   => ['required', 'date'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'note'               => ['nullable', 'string', 'max:500'],
        ];
    }
}
