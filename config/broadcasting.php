<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to any
    | of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used to
    | broadcast events to other systems or over websockets. Samples of each
    | available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 6001),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST') ?: '127.0.0.1',
                'port' => env('PUSHER_PORT', 6001),
                // CWE-319 / H-15 hardening: default to TLS in production.
                // Operators must explicitly set PUSHER_SCHEME=http only for local
                // development.  An unset env will now resolve to https, which
                // is the secure default (was: 'http' which silently downgraded
                // production to plaintext WebSockets).
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
                // CWE-295 / H-15 hardening: TLS verification is ALWAYS on.
                // No `getEnvMode() == 'test' ? false : ...` exception.  The CA
                // bundle of the underlying OS / CA-bundle is used.  In test
                // environments that need a private CA, operators should
                // configure `CURLOPT_CAINFO` via `additional_curl_options`.
                'curl_options' => [
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_SSL_VERIFYPEER => 1,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_TIMEOUT        => 30,
                ],
                // Extra hardening: allow operators to append additional
                // cURL options (e.g. CURLOPT_CAINFO for private CA) via env.
                // When unset, this is an empty array.
                'additional_curl_options' => env('PUSHER_EXTRA_CURL_OPTIONS_JSON', '[]')
                    ? json_decode((string) env('PUSHER_EXTRA_CURL_OPTIONS_JSON', '[]'), true)
                    : [],
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
