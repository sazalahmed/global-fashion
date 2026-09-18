<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountId = $this->route('account')?->id ?? $this->route('account');

        return [
            'account_code' => 'required|string|max:20|unique:accounts,account_code,' . $accountId,
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'sub_type' => 'required|string|max:50',
            'parent_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string|max:2000',
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_balance_type' => 'required_with:opening_balance|in:debit,credit',
            'opening_balance_date' => 'nullable|date',
            'is_bank_account' => 'boolean',
            'bank_name' => 'required_if:is_bank_account,true|nullable|string|max:255',
            'bank_account_number' => 'required_if:is_bank_account,true|nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
