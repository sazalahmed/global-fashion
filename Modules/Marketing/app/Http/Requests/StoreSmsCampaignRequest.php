<?php

namespace Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmsCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'gateway' => 'required|in:bulksmsbd',
            'message' => 'required|string|max:160',
            'audience' => 'required|in:all,group,custom',
            'recipient_numbers' => 'required_if:audience,custom|nullable|string',
            'cost_per_sms' => 'nullable|numeric|min:0',
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }
}
