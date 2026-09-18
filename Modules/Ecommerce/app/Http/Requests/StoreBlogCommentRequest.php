<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreBlogCommentRequest extends FormRequest
{
    /** Minimum seconds a human is expected to spend before submitting. */
    private const MIN_SECONDS = 3;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'subject' => 'nullable|string|max:150',
            'comment' => 'required|string|max:2000',
            // Honeypot — hidden from real users; bots tend to fill it.
            'website' => 'prohibited',
        ];
    }

    /**
     * Time-trap: reject submissions that arrive implausibly fast (bots) or
     * whose signed timestamp is missing/tampered.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            try {
                $startedAt = (int) decrypt((string) $this->input('form_started_at'));
            } catch (\Throwable $e) {
                $validator->errors()->add('comment', __('Your submission could not be verified. Please reload the page and try again.'));

                return;
            }

            if (now()->timestamp - $startedAt < self::MIN_SECONDS) {
                $validator->errors()->add('comment', __('That was too quick — please take a moment and submit again.'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'website.prohibited' => __('Your submission could not be processed.'),
        ];
    }
}
