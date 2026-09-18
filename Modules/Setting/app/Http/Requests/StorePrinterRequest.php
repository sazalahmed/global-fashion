<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrinterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:100'],
            // IP is only required for network printers; USB/Bluetooth don't use it.
            'ip_address'      => [Rule::requiredIf($this->input('connection_type') === 'network'), 'nullable', 'ip'],
            'port'            => ['required', 'integer', 'min:1', 'max:65535'],
            'printer_type'    => ['required', 'in:thermal,thermal_58,a4,label'],
            'connection_type' => ['required', 'in:network,usb,bluetooth'],
            'paper_width'     => ['required', 'integer', 'in:58,80,210'],
            'purpose'         => ['required', 'in:receipt,invoice,barcode,kitchen,report'],
            'branch_id'       => ['nullable', 'exists:branches,id'],
            'is_default'      => ['nullable', 'boolean'],
            'is_active'       => ['nullable', 'boolean'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ip_address'      => 'IP address',
            'printer_type'    => 'printer type',
            'connection_type' => 'connection type',
            'paper_width'     => 'paper width',
        ];
    }
}
