<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'  => ['required', 'email'],
            'verify' => ['nullable', 'boolean'],
            'otp'    => ['required_if:verify,1', 'nullable', 'string', 'size:6'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email'  => 'email',
            'verify' => 'verify',
            'otp'    => 'OTP code',
        ];
    }
}
