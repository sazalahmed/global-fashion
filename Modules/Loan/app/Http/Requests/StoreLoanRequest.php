<?php

namespace Modules\Loan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lender_id'              => 'required|exists:lenders,id',
            'principal_amount'       => 'required|numeric|min:1',
            'disbursement_date'      => 'required|date',
            'disbursement_account_id' => 'required|exists:payment_accounts,id',
            'total_installments'     => 'required|integer|min:1|max:360',
            'frequency'              => 'required|in:weekly,bi_weekly,monthly',
            'start_date'             => 'required|date|after_or_equal:disbursement_date',
            'reference'              => 'nullable|string|max:255',
            'note'                   => 'nullable|string',
        ];
    }
}
