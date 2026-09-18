<?php

namespace Modules\Loan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id'        => 'required|exists:loan_schedules,id',
            'amount'             => 'required|numeric|min:0.01',
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'payment_date'       => 'nullable|date',
            'note'               => 'nullable|string|max:255',
        ];
    }
}
