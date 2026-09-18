<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Payment\Http\Requests\Concerns\GuardsAccountBalance;

class StoreBalanceTransferRequest extends FormRequest
{
    use GuardsAccountBalance;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => 'required|exists:payment_accounts,id',
            'to_account_id'   => 'required|exists:payment_accounts,id|different:from_account_id',
            'date'            => 'required|date',
            'amount'          => 'required|numeric|min:0.01',
            'charge'          => 'nullable|numeric|min:0',
            'note'            => 'nullable|string|max:500',
        ];
    }

    /**
     * Refuse to move out more than the account holds. Checked here rather than
     * as a rule because it needs the source account, the date and the amount
     * together, and only once they are known to be individually valid.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $leaving = (float) $this->input('amount') + (float) $this->input('charge', 0);

            $this->guardAccountBalance(
                $validator,
                [$this->input('from_account_id') => $leaving],
                $this->input('date'),
                'amount',
            );
        });
    }
}
