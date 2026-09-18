<?php

namespace Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'html_body' => 'required|string',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'audience' => 'required|in:all,group,custom',
            'recipient_emails' => 'required_if:audience,custom|nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }
}
