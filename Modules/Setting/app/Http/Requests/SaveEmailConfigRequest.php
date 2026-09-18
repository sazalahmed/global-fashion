<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveEmailConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mail_driver'       => ['required', 'string', 'in:smtp,sendmail,mailgun,ses,log'],
            'mail_host'         => ['nullable', 'string', 'max:255'],
            'mail_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_encryption'   => ['nullable', 'string', 'in:tls,ssl,null'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mail_driver'       => 'mail driver',
            'mail_host'         => 'mail host',
            'mail_port'         => 'mail port',
            'mail_username'     => 'mail username',
            'mail_password'     => 'mail password',
            'mail_encryption'   => 'mail encryption',
            'mail_from_address' => 'from address',
            'mail_from_name'    => 'from name',
        ];
    }
}
