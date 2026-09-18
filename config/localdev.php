<?php

/**
 * Values re-applied by `php artisan dev:restore-local-config` after a
 * production database is imported into a development environment.
 *
 * An imported database carries credentials encrypted with the production
 * APP_KEY, which a development key cannot read, and it overwrites local-only
 * settings. Nothing here is required — every value can also be passed to the
 * command as an option, so secrets need not live in .env if you would rather
 * they did not.
 */
return [

    'steadfast' => [
        'api_key'    => env('DEV_STEADFAST_API_KEY'),
        'api_secret' => env('DEV_STEADFAST_SECRET_KEY'),
    ],

    'business_start_date' => env('DEV_BUSINESS_START_DATE'),

];
