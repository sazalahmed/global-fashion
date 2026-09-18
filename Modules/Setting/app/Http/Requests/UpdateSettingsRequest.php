<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'business_name'          => ['sometimes', 'string', 'max:255'],
            'business_phone'         => ['sometimes', 'string', 'max:20'],
            'business_email'         => ['sometimes', 'email', 'max:255'],
            'business_address'       => ['sometimes', 'string', 'max:500'],
            // The day the business began trading. Reports use it to hide
            // history that predates the company's records.
            'business_start_date'    => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'tax_number'             => ['sometimes', 'string', 'max:50'],
            'vat_registration'       => ['sometimes', 'string', 'max:50'],
            'default_tax_rate'       => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'currency'               => ['sometimes', 'string', 'max:10'],
            'currency_symbol'        => ['sometimes', 'string', 'max:10'],
            'date_format'            => ['sometimes', 'string', 'max:50'],
            'timezone'               => ['sometimes', 'string', 'max:50'],
            'invoice_prefix'         => ['sometimes', 'string', 'max:20'],
            // Invoice numbering settings form a standalone series; sales use their
            // own S{date}{seq} scheme, so this is validated as a plain positive int.
            'next_invoice_number'    => ['sometimes', 'integer', 'min:1'],
            'invoice_format'         => ['sometimes', 'string', 'in:INV-YYYY-NNNNN,INV-NNNNN,BRANCH-YYYY-NNNNN'],
            // Print/PDF brand accent (quotation & invoice templates)
            'accent_color'           => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'low_stock_threshold'    => ['sometimes', 'integer', 'min:0'],
            'provider'               => ['sometimes', 'string', 'max:50'],
            'api_key'                => ['sometimes', 'nullable', 'string', 'max:255'],
            'sender_id'              => ['sometimes', 'nullable', 'string', 'max:20'],
            // Tracking & Analytics — only validated when present (empty strings are
            // normalised to null by Laravel, so nullable lets them pass).
            'gtm_container_id'       => ['sometimes', 'nullable', 'string', 'regex:/^GTM-[A-Z0-9]{4,10}$/i'],
            'fbpixel_id'             => ['sometimes', 'nullable', 'string', 'regex:/^\d{10,20}$/'],
            'fbpixel_access_token'   => ['sometimes', 'nullable', 'string', 'max:500'],
            'fbpixel_test_event_code'    => ['sometimes', 'nullable', 'string', 'max:64'],
            'ga4_measurement_id'         => ['sometimes', 'nullable', 'string', 'regex:/^G-[A-Z0-9]{4,12}$/i'],
            'ga4_api_secret'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'purchase_block_risk_levels' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];

        if ($this->route('group') === 'landing_page' && class_exists(\Modules\LandingPage\Models\LandingPage::class)) {
            $rules['mode'] = ['required', 'in:full_site,landing_page'];
            $rules['active_landing_page_id'] = [
                'nullable',
                'integer',
                'exists:landing_pages,id',
                function ($attribute, $value, $fail) {
                    if ($this->input('mode') === 'landing_page' && empty($value)) {
                        $fail(__('Please select an active landing page when "Landing Page Mode" is selected.'));
                    }
                },
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'business_name'       => 'business name',
            'business_phone'      => 'business phone',
            'business_email'      => 'business email',
            'business_address'    => 'business address',
            'tax_number'          => 'tax number',
            'vat_registration'    => 'VAT registration',
            'default_tax_rate'    => 'default tax rate',
            'invoice_prefix'      => 'invoice prefix',
            'next_invoice_number' => 'next invoice number',
            'invoice_format'      => 'invoice number format',
            'low_stock_threshold' => 'low stock threshold',
            'provider'            => 'SMS provider',
            'api_key'             => 'SMS API key',
            'sender_id'           => 'SMS sender ID',
            'gtm_container_id'    => 'GTM Container ID',
            'fbpixel_id'          => 'Facebook Pixel ID',
            'fbpixel_access_token'=> 'Conversions API access token',
            'ga4_measurement_id'  => 'GA4 Measurement ID',
            'ga4_api_secret'      => 'GA4 API secret',
        ];
    }
}
