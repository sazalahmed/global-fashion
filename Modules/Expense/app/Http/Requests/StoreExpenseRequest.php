<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'expense_date' => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'receipt' => 'nullable|file|mimes:jpg,png,pdf,webp|max:5120',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'required_if:is_recurring,true|nullable|in:weekly,monthly,quarterly,yearly',
            'branch_id' => 'nullable|exists:branches,id',
        ];
    }
}
