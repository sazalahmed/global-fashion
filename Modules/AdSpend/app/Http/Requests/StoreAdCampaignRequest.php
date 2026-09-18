<?php

namespace Modules\AdSpend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ad_platform_id'       => 'required|exists:ad_platforms,id',
            'campaign_name'        => 'required|string|max:255',
            'campaign_id_external' => 'nullable|string|max:100',
            'spend_date'           => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'amount'               => 'required|numeric|min:0.01',
            'tax_amount'           => 'nullable|numeric|min:0',
            'payment_account_id'   => 'nullable|exists:payment_accounts,id',
            'payment_method'       => 'nullable|string|max:50',
            'status'               => 'nullable|in:active,paused,completed',
            'impressions'          => 'nullable|integer|min:0',
            'clicks'               => 'nullable|integer|min:0',
            'conversions'          => 'nullable|integer|min:0',
            'reach'                => 'nullable|integer|min:0',
            'target_url'           => 'nullable|url|max:500',
            'notes'                => 'nullable|string',
            'receipt'              => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'splits'               => 'nullable|array|min:1',
            'splits.*.amount'      => 'required_with:splits|numeric|min:0.01',
            'splits.*.payment_account_id' => 'required_with:splits|exists:payment_accounts,id',
        ];
    }
}
