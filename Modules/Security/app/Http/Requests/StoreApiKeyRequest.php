<?php

namespace Modules\Security\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'expiry'            => 'required|in:never,30,90,180,365',
            'rate_limit'        => 'required|integer|min:1|max:1000',
            'environment'       => 'required|in:live,test',
            'api_permissions'   => 'nullable|array',
            'api_permissions.*' => 'string',
        ];
    }
}
