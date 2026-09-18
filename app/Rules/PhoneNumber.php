<?php

namespace App\Rules;

use App\Helpers\PhoneHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a phone number against the configured country's format
 * (Settings → Localization → Country, default Bangladesh). The format itself
 * is defined once in App\Helpers\PhoneHelper so backend and front-end stay in
 * sync — never hardcode the regex anywhere else.
 */
class PhoneNumber implements ValidationRule
{
    public function __construct(private ?string $countryCode = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! PhoneHelper::isValid((string) $value, $this->countryCode)) {
            $config = PhoneHelper::getConfig($this->countryCode);
            $fail("The :attribute must be a valid phone number (e.g. {$config['example']}).");
        }
    }
}
