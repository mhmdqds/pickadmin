<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| H-5 SECURITY HARDENING SUMMARY
|--------------------------------------------------------------------------
|
| All session cookie defaults have been hardened against the H-5 finding
| (CWE-614 / CWE-757 — Insecure Cookie / Weak Algorithm). The defaults
| below are SECURE-DEFAULT even when the corresponding `.env` variable
| is missing (the application falls back to the safe value, not to null).
|
|   • secure       : env('SESSION_SECURE_COOKIE', true)   — cookie only sent over HTTPS
|   • same_site    : env('SESSION_SAME_SITE', 'lax')      — CSRF mitigation
|   • encrypt      : env('SESSION_ENCRYPT',     true)     — at-rest encryption
|   • http_only    : true                                  — JS cannot read cookie
|   • domain       : env('SESSION_DOMAIN')                 — no fallback
|   • lifetime     : env('SESSION_LIFETIME',    60)       — short idle window
|   • driver       : env('SESSION_DRIVER',      'file')   — switch to redis/DB in prod
|
| ROTATING APP_KEY ON SUSPECTED COMPROMISE
|   If session integrity is in doubt (e.g. shared-hosting breach, leaked
|   APP_KEY, observed MitM), the operator MUST both:
|     1) Generate a new application key:
|          php artisan key:generate
|        This re-encrypts encrypted cookies and invalidates session
|        records that were sealed with the previous key.
|     2) Flush every active session:
|          php artisan session:flush
|          (or)  php artisan tinker --execute="DB::table('sessions')->delete();"
|        If the driver is `file`, additionally delete
|        storage/framework/sessions/* on every node of the cluster.
|
| OWASP / CWE references:
|   • CWE-614  — Sensitive Cookie in HTTPS Session Without 'Secure' Attribute
|   • CWE-757  — Selection of Less-Secure Algorithm During Negotiation
|   • OWASP A05:2021 — Security Misconfiguration
|
*/

return [

/*
|--------------------------------------------------------------------------
| Default Session Driver
|--------------------------------------------------------------------------
|
| This option controls the default session "driver" that will be used on
| requests. By default, we will use the lightweight native driver but
| you may specify any of the other wonderful drivers provided here.
|
| Supported: "file", "cookie", "database", "apc",
|            "memcached", "redis", "dynamodb", "array"
|
|
| H-5 fix: for production we strongly recommend switching to "redis" or
| "database" which both support encrypted connections natively and
| can be cleaned up via a cron.  "file" (the default) stores session
| files on local disk and is unsuitable for multi-instance production.
|
*/

'driver' => env('SESSION_DRIVER', 'file'),

/*
|--------------------------------------------------------------------------
| Session Lifetime
|--------------------------------------------------------------------------
|
| Here you may specify the number of minutes that you wish the session
| to be allowed to remain idle before it expires. If you want them
| to immediately expire on the browser closing, set that option.
|
|
| H-5 fix: reduced the default from 120 minutes to 60 minutes to
| minimise the window of opportunity for stolen-cookie replay.
| Operators may override via SESSION_LIFETIME.
|
*/

'lifetime' => env('SESSION_LIFETIME', 60),

'expire_on_close' => false,

/*
|--------------------------------------------------------------------------
| Session Encryption
|--------------------------------------------------------------------------
|
| This option allows you to easily specify that all of your session data
| should be encrypted before it is stored. All encryption will be run
| automatically by Laravel and you can use the Session like normal.
|
|
| H-5 fix: encrypted session storage is now the default.  This
| protects session contents at-rest on local disk / Redis / database.
| If you previously disabled encryption for performance reasons, set
| SESSION_ENCRYPT=false in `.env`.
|
*/

'encrypt' => env('SESSION_ENCRYPT', true),

/*
|--------------------------------------------------------------------------
| Session File Location
|--------------------------------------------------------------------------
|
| When using the native session driver, we need a location where session
| files may be stored. A default has been set for you but a different
| location may be specified. This is only needed for file sessions.
|
*/

'files' => storage_path('framework/sessions'),

/*
|--------------------------------------------------------------------------
| Session Database Connection
|--------------------------------------------------------------------------
|
| When using the "database" or "redis" session drivers, you may specify a
| connection that should be used to manage these sessions. This should
| correspond to a connection in your database configuration options.
|
*/

'connection' => env('SESSION_CONNECTION', null),

/*
|--------------------------------------------------------------------------
| Session Database Table
|--------------------------------------------------------------------------
|
| When using the "database" session driver, you may specify the table we
| should use to manage the sessions. Of course, a sensible default is
| provided for you; however, you are free to change this as needed.
|
*/

'table' => 'sessions',

/*
|--------------------------------------------------------------------------
| Session Cache Store
|--------------------------------------------------------------------------
|
| While using one of the framework's cache driven session backends you may
| list a cache store that should be used for these sessions. This value
| must match with one of the application's configured cache "stores".
|
| Affects: "apc", "dynamodb", "memcached", "redis"
|
*/

'store' => env('SESSION_STORE', null),

/*
|--------------------------------------------------------------------------
| Session Sweeping Lottery
|--------------------------------------------------------------------------
|
| Some session drivers must manually sweep their storage location to get
| rid of old sessions from storage. Here are the chances that it will
| happen on a given request. By default, the odds are 2 out of 100.
|
*/

'lottery' => [2, 100],

/*
|--------------------------------------------------------------------------
| Session Cookie Name
|--------------------------------------------------------------------------
|
| Here you may change the name of the cookie used to identify a session
| instance by ID. The name specified here will get used every time a
| new session cookie is created by the framework for every driver.
|
*/

'cookie' => env(
    'SESSION_COOKIE',
    Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
),

/*
|--------------------------------------------------------------------------
| Session Cookie Path
|--------------------------------------------------------------------------
|
| The session cookie path determines the path for which the cookie will
| be regarded as available. Typically, this will be the root path of
| your application but you are free to change this when necessary.
|
*/

'path' => '/',

/*
|--------------------------------------------------------------------------
| Session Cookie Domain
|--------------------------------------------------------------------------
|
| Here you may change the domain of the cookie used to identify a session
| in your application. This will determine which domains the cookie is
| available to in your application. A sensible default has been set.
|
|
| H-5 fix: now defaults to env('SESSION_DOMAIN') with **no fallback**.
| If unset, the cookie is restricted to the exact host that issued it
| (no subdomains).  This is the OWASP-recommended secure default.
|
*/

'domain' => env('SESSION_DOMAIN'),

/*
|--------------------------------------------------------------------------
| HTTPS Only Cookies
|--------------------------------------------------------------------------
|
| By setting this option to true, session cookies will only be sent back
| to the server if the browser has a HTTPS connection. This will keep
| the cookie from being sent to you if it can not be done securely.
|
|
| H-5 fix: now defaults to TRUE.  This is a CRITICAL secure default.
| The previous behaviour (env('SESSION_SECURE_COOKIE') with `null`
| fallback) silently downgraded to plaintext HTTP cookies, which is
| the #1 source of session hijacking on shared hosting.
|
| In a non-HTTPS local dev environment, set SESSION_SECURE_COOKIE=false
| in `.env` — the framework will honour that.
|
*/

'secure' => env('SESSION_SECURE_COOKIE', true),

/*
|--------------------------------------------------------------------------
| HTTP Access Only
|--------------------------------------------------------------------------
|
| Setting this value to true will prevent JavaScript from accessing the
| value of the cookie and the cookie will only be accessible through
| the HTTP protocol. You are free to modify this option if needed.
|
*/

'http_only' => true,

/*
|--------------------------------------------------------------------------
| Same-Site Cookies
|--------------------------------------------------------------------------
|
| This option determines how your cookies behave when cross-site requests
| take place, and can be used to mitigate CSRF attacks. By default, we
| will set this value to "lax" since this is a secure default value.
|
| Supported: "lax", "strict", "none", null
|
|
| H-5 fix: now defaults to "lax" (CSRF mitigation).  The previous
| `null` value silently disabled SameSite protection, which is
| required for OWASP-compliant CSRF defense.
|
*/

'same_site' => env('SESSION_SAME_SITE', 'lax'),

];
