<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Setting\Models\Setting;

class AllowedTransactionDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        $date = \Carbon\Carbon::parse($value);
        $today = \Carbon\Carbon::today();

        // Future dates are always allowed
        if ($date->gte($today)) {
            return;
        }

        // Check if backdate is allowed
        $allowBackdate = Setting::get('business', 'allow_backdate', '0');

        if ($allowBackdate !== '1') {
            $fail('Back-dated transactions are not allowed. The date must be today or later.');
            return;
        }

        // Check backdate limit
        $limitDays = (int) Setting::get('business', 'backdate_limit_days', 365);
        $minDate = $today->copy()->subDays($limitDays);

        if ($date->lt($minDate)) {
            $fail("The date cannot be more than {$limitDays} days in the past.");
        }
    }
}
