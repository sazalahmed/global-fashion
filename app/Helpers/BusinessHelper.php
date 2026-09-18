<?php

use Carbon\Carbon;
use Modules\Setting\Models\Setting;

if (! function_exists('business_start_date')) {
    /**
     * The day the business began trading, as configured in
     * Settings → Business Profile. Reports use it to hide history that
     * predates the company's own records — most visibly courier settlements,
     * where the provider's account can go back further than the business does.
     *
     * Returns null when unset, which means "no lower bound".
     */
    function business_start_date(): ?Carbon
    {
        $value = Setting::get('business', 'business_start_date');

        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            // A malformed value must not take down every page that filters by
            // it; treat it as unset until someone corrects the setting.
            return null;
        }
    }
}
