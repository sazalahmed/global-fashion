<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID Keys
    |--------------------------------------------------------------------------
    |
    | Voluntary Application Server Identification keys are used to authenticate
    | push messages sent to the browser push services (FCM, Mozilla, Apple).
    | Generate a new pair with: php artisan webpush:vapid
    |
    */
    'vapid' => [
        'subject'     => env('VAPID_SUBJECT', 'mailto:admin@bizpos.test'),
        'public_key'  => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'icon'  => '/images/icons/icon-192x192.png',
        'badge' => '/images/icons/icon-72x72.png',
        'ttl'   => 86400, // 24h — how long the push service keeps the notification if device is offline
    ],

];
