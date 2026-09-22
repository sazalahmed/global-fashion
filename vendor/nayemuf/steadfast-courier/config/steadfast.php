<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SteadFast Courier API Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is used to authenticate with the SteadFast Courier API.
    | You should obtain these credentials from the SteadFast Courier service provider.
    |
    */

    'base_url' => env('STEADFAST_BASE_URL', 'https://portal.packzy.com/api/v1'),

    'api_key' => env('STEADFAST_API_KEY', 'your-api-key'),

    'secret_key' => env('STEADFAST_SECRET_KEY', 'your-secret-key'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Bearer Token
    |--------------------------------------------------------------------------
    |
    | This token is used to authenticate webhook requests from SteadFast
    | Courier. Set this in your SteadFast portal webhook settings.
    |
    */

    'webhook_bearer_token' => env('STEADFAST_BEARER_TOKEN', 'your-generated-bearer-token'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for API responses and rate limiting.
    |
    */

    'cache' => [
        'prefix' => env('STEADFAST_CACHE_PREFIX', 'steadfast_courier_'),
        'token_ttl' => env('STEADFAST_TOKEN_TTL', 432000), // 5 days in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent exceeding API limits.
    |
    */

    'rate_limit' => [
        'enabled' => env('STEADFAST_RATE_LIMIT_ENABLED', true),
        'requests_per_minute' => env('STEADFAST_RATE_LIMIT_PER_MINUTE', 60),
    ],
];

