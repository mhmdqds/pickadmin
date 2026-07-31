<?php

/*
|--------------------------------------------------------------------------
| H-? Cross-system signature configuration (F-12 fix)
|--------------------------------------------------------------------------
|
| Configuration for App\Http\Middleware\VerifyCrossSystemSignature.
| This middleware is mounted on the previously-CSRF-exempt server-to-server
| endpoints (e.g. /api/v1/customer/external-update-data, drivemond
| cross-login, item variation-generators) so that those endpoints are no
| longer reachable anonymously.
|
| - shared_secret  : fallback shared secret loaded from env CROSS_SYSTEM_SHARED_SECRET.
|                    Any caller not present in the `callers` map is validated
|                    against this single secret.  Setting it to an empty
|                    string causes the middleware to FAIL CLOSED (every
|                    request rejected) which is the secure default.
| - callers        : optional map of X-Caller-Id => per-caller shared secret.
|                    Use this when multiple upstream systems (e.g. Drivemond
|                    and a partner integration) need distinct credentials.
| - timestamp_tolerance_seconds : allowed clock skew between request X-Timestamp
|                    and server time.
|
| Always set CROSS_SYSTEM_SHARED_SECRET to a random 64+ char string in .env
| before deploying a host that needs the cross-system endpoints.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Single shared secret (fallback for callers not in the map)
    |--------------------------------------------------------------------------
    |
    | This is the secret that every caller (without a per-caller override)
    | must use when signing its requests.  Rotate periodically.
    |
    */

    'shared_secret' => env('CROSS_SYSTEM_SHARED_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Per-caller secrets (optional)
    |--------------------------------------------------------------------------
    |
    | Format:  'caller_id' => 'shared_secret_for_that_caller'
    |
    | Example: 'drivemond' => env('DRIVEMOND_SHARED_SECRET')
    |
    */

    'callers' => [
        // 'drivemond' => env('DRIVEMOND_SHARED_SECRET'),
        // 'partner_app' => env('PARTNER_SHARED_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed request timestamp skew
    |--------------------------------------------------------------------------
    |
    | The X-Timestamp header value MUST be within +/- this many seconds of
    | the server clock.  Set to 0 to disable skew tolerance; set to 300
    | (5 minutes) for normal operation.
    |
    */

    'timestamp_tolerance_seconds' => (int) env('CROSS_SYSTEM_TIMESTAMP_TOLERANCE', 300),

    /*
    |--------------------------------------------------------------------------
    | Allow per-caller secret to be empty in non-production environments
    |--------------------------------------------------------------------------
    |
    | When true, a missing secret (in either shared_secret or the caller map)
    | will be REPLACED with a development-only default that matches the
    | documented sample, allowing the dev environment to function without
    | provisioning a real secret.  ALWAYS LEAVE THIS FALSE IN PRODUCTION.
    |
    */

    'dev_auto_key' => filter_var(env('CROSS_SYSTEM_DEV_AUTO_KEY', false), FILTER_VALIDATE_BOOLEAN),

];
